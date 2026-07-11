<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AbsensiController extends BaseController
{

    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Cari Tahun Ajaran yang sedang aktif
        $taAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        if (!$taAktif) {
            // Jika belum ada tahun ajaran aktif, kembalikan ke dashboard dengan pesan error
            return redirect()->to(base_url('/'))->with('error', 'Tidak ada tahun ajaran aktif. Silakan seting terlebih dahulu.');
        }

        // 2. Ambil daftar rombel HANYA pada tahun ajaran yang aktif tersebut
        $daftarRombel = $db->table('class_rombel')
                           ->where('academic_year_id', $taAktif['id'])
                           ->orderBy('rombel_name', 'ASC')
                           ->get()->getResultArray();

        $data = [
            'daftarRombel' => $daftarRombel
        ];

        return view('admin/absensi/index', $data);
    }

    public function input($rombel_id)
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        
        // 1. Ambil tanggal dari URL (jika user ganti tanggal), default hari ini
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');

        // Ambil Tahun Ajaran Aktif
        $taAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$taAktif) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        // Ambil Data Rombel
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // Ambil Daftar Siswa
        $siswaKelas = $db->table('class_rombel_students crs')
                         ->select('u.id as student_id, u.username')
                         ->join('users u', 'u.id = crs.student_id')
                         ->where('crs.rombel_id', $rombel_id)
                         ->orderBy('u.username', 'ASC')
                         ->get()->getResultArray();

        // 2. CEK RIWAYAT ABSENSI DI TANGGAL TERSEBUT
        $cekAbsensi = $db->table('absensi')
                         ->where('rombel_id', $rombel_id)
                         ->where('tanggal', $tanggal)
                         ->get()->getRowArray();

        $absensiDetails = [];
        if ($cekAbsensi) {
            $details = $db->table('absensi_details')->where('absensi_id', $cekAbsensi['id'])->get()->getResultArray();
            // Ubah format array agar mudah dibaca di view (key = student_id)
            foreach ($details as $d) {
                $absensiDetails[$d['student_id']] = $d;
            }
        }

        $data = [
            'taAktif'        => $taAktif,
            'rombel'         => $rombel,
            'siswaKelas'     => $siswaKelas,
            'tanggal'        => $tanggal,
            'absensiDetails' => $absensiDetails // Kirim riwayat ke View
        ];

        return view('admin/absensi/input', $data);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $rombel_id        = $request->getPost('rombel_id');
        $academic_year_id = $request->getPost('academic_year_id');
        $tanggal          = $request->getPost('tanggal');
        $pencatat_id      = session()->get('user_id') ?? session()->get('id') ?? 1; 
        $siswaData        = $request->getPost('siswa'); 

        if (empty($siswaData)) {
             return redirect()->back()->with('error', 'Gagal: Data absensi kosong.');
        }

        try {
            $db->transStart();

            // 1. Cek apakah absensi sudah ada
            $cekAbsensi = $db->table('absensi')
                             ->where('rombel_id', $rombel_id)
                             ->where('tanggal', $tanggal)
                             ->get()->getRowArray();

            if ($cekAbsensi) {
                // JIKA SUDAH ADA: Update parent dan hapus detail lama
                $absensi_id = $cekAbsensi['id'];
                
                $db->table('absensi')->where('id', $absensi_id)->update([
                    'pencatat_id' => $pencatat_id,
                    'updated_at'  => date('Y-m-d H:i:s')
                ]);
                
                $db->table('absensi_details')->where('absensi_id', $absensi_id)->delete();
                $pesanSukses = 'Data absensi harian berhasil diperbarui (Revisi).';
            } else {
                // JIKA BELUM ADA: Insert baru
                $db->table('absensi')->insert([
                    'academic_year_id' => $academic_year_id,
                    'rombel_id'        => $rombel_id,
                    'tanggal'          => $tanggal,
                    'pencatat_id'      => $pencatat_id,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s')
                ]);
                $absensi_id = $db->insertID();
                $pesanSukses = 'Data absensi harian berhasil disimpan.';
            }

            // 2. Siapkan Data Detail Baru (Untuk Insert/Update)
            $batchDetails = [];
            foreach ($siswaData as $student_id => $dataAbsen) {
                $batchDetails[] = [
                    'absensi_id'          => $absensi_id,
                    'student_id'          => $student_id,
                    'status'              => $dataAbsen['status'],
                    'keterlambatan_menit' => empty($dataAbsen['terlambat']) ? 0 : $dataAbsen['terlambat'],
                    'keterangan'          => empty($dataAbsen['keterangan']) ? null : $dataAbsen['keterangan'],
                ];
            }

            // 3. Simpan Massal ke Tabel Detail
            if (!empty($batchDetails)) {
                $db->table('absensi_details')->insertBatch($batchDetails);
            }

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                return redirect()->back()->with('error', 'Transaksi dibatalkan oleh database (Rollback).');
            }

            // Redirect kembali ke form di tanggal yang sama
            return redirect()->to(base_url("admin/absensi/input/{$rombel_id}?tanggal={$tanggal}"))->with('sukses', $pesanSukses);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PESAN ERROR ASLI: ' . $e->getMessage());
        }
    }
}