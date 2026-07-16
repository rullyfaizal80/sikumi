<?php

namespace App\Controllers;

class PeminatanController extends BaseController
{
    // =========================================================================
    // HALAMAN INPUT NILAI PEMINATAN PER KELAS
    // =========================================================================
    public function input($rombel_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');
        $db = \Config\Database::connect();

        // 1. Ambil data Kelas (Rombel)
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();
        if (!$rombel) return redirect()->back()->with('error', 'Kelas tidak ditemukan.');

        // 2. Ambil anggota siswa berdasarkan Kelas
        $anggota = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username as nama_siswa')
            ->join('users u', 'u.id = crs.student_id')
            ->where('crs.rombel_id', $rombel_id)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // 3. Konfigurasi Tahun Ajaran & Semester (Otomatis dari Sistem)
        $bulan_sekarang = (int) date('m');
        $tahun_sekarang = (int) date('Y');

        if ($bulan_sekarang >= 7) {
            $tahun_ajaran = $tahun_sekarang . '/' . ($tahun_sekarang + 1);
            $semester     = 'Ganjil';
            $list_bulan = [
                ['angka' => 7,  'nama' => 'Juli'],
                ['angka' => 8,  'nama' => 'Agustus'],
                ['angka' => 9,  'nama' => 'September'],
                ['angka' => 10, 'nama' => 'Oktober'],
                ['angka' => 11, 'nama' => 'November'],
                ['angka' => 12, 'nama' => 'Desember']
            ];
            $tahun_kalender = substr($tahun_ajaran, 0, 4);
        } else {
            $tahun_ajaran = ($tahun_sekarang - 1) . '/' . $tahun_sekarang;
            $semester     = 'Genap';
            $list_bulan = [
                ['angka' => 1,  'nama' => 'Januari'],
                ['angka' => 2,  'nama' => 'Februari'],
                ['angka' => 3,  'nama' => 'Maret'],
                ['angka' => 4,  'nama' => 'April'],
                ['angka' => 5,  'nama' => 'Mei'],
                ['angka' => 6,  'nama' => 'Juni']
            ];
            $tahun_kalender = substr($tahun_ajaran, 5, 4);
        }

        // 4. Logika Validasi Waktu (Kunci bulan yang belum datang)
        $current_Ym = date('Y-m'); 
        foreach ($list_bulan as &$bln) {
            $kolom_Ym = $tahun_kalender . '-' . str_pad($bln['angka'], 2, '0', STR_PAD_LEFT);
            $bln['is_locked'] = ($kolom_Ym > $current_Ym);
        }

        // 5. Ambil nilai yang sudah ada di database
        $gradesRaw = $db->table('peminatan_grades')
            ->where('rombel_id', $rombel_id)
            ->where('tahun_ajaran', $tahun_ajaran)
            ->where('semester', $semester)
            ->get()->getResultArray();

        $grades = [];
        foreach ($gradesRaw as $g) {
            $grades[$g['student_id']][$g['bulan']] = $g['nilai'];
        }

        $data = [
            'title'        => 'Input Nilai Peminatan',
            'rombel'       => $rombel,
            'anggota'      => $anggota,
            'list_bulan'   => $list_bulan,
            'grades'       => $grades,
            'tahun_ajaran' => $tahun_ajaran,
            'semester'     => $semester
        ];

        return view('guru/peminatan/input', $data);
    }

    // =========================================================================
    // PROSES SIMPAN NILAI PEMINATAN (SINKRONISASI BATCH)
    // =========================================================================
    public function saveNilai($rombel_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');
        
        $db = \Config\Database::connect();
        $post = $this->request->getPost();
        
        $tahun_ajaran = $post['tahun_ajaran'];
        $semester     = $post['semester'];
        $input_grades = $post['grades'] ?? []; 

        if (empty($input_grades)) {
            return redirect()->back()->with('error', 'Tidak ada data nilai yang dikirim.');
        }

        $dataToInsert = [];
        foreach ($input_grades as $student_id => $bulans) {
            foreach ($bulans as $bulan_angka => $nilai) {
                $nilai_clean = trim($nilai);
                if ($nilai_clean !== '') {
                    // Konversi koma ke titik untuk database
                    $nilai_db = str_replace(',', '.', $nilai_clean);
                    $dataToInsert[] = [
                        'rombel_id'    => $rombel_id,
                        'student_id'   => $student_id,
                        'tahun_ajaran' => $tahun_ajaran,
                        'semester'     => $semester,
                        'bulan'        => $bulan_angka,
                        'nilai'        => $nilai_db
                    ];
                }
            }
        }

        $db->transStart();

        // Hapus nilai lama (SINKRONISASI)
        $db->table('peminatan_grades')
           ->where('rombel_id', $rombel_id)
           ->where('tahun_ajaran', $tahun_ajaran)
           ->where('semester', $semester)
           ->delete();

        // Masukkan data baru
        if (!empty($dataToInsert)) {
            $db->table('peminatan_grades')->insertBatch($dataToInsert);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai.');
        }

        return redirect()->back()->with('success', 'Nilai Peminatan berhasil disimpan!');
    }
}