<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class JurnalGuruController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        
        // Dapatkan ID Guru yang sedang login
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);

        // 1. Tentukan Tahun Ajaran Aktif & Semester
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $semester = $tahunAktif ? strtolower($tahunAktif['semester']) : 'ganjil'; // default 'ganjil' atau 'genap'
        $tahun = date('Y');

        // 2. Daftar Bulan Berdasarkan Semester
        if (strpos($semester, 'ganjil') !== false) {
            $listBulan = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        } else {
            $listBulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni'];
        }

        // Ambil bulan dari parameter URL (?bulan=03), jika kosong ambil bulan saat ini atau default semester
        $bulanPilih = $request->getGet('bulan') ?? date('m');
        if (!array_key_exists($bulanPilih, $listBulan)) {
            $bulanPilih = array_key_first($listBulan); 
        }

        // 3. Query Ambil Data ATP + Tujuan Pembelajaran + Kelas
        // Asumsi: Guru hanya melihat jadwal rombel yang dia ampu, tapi disini kita load berdasarkan alokasi_tanggal yang tidak kosong
        $builder = $db->table('kurikulum_atp a')
                      ->select('a.id as atp_id, a.alokasi_tanggal, cr.rombel_name, mc.class_name, d.tujuan_pembelajaran, d.estimasi_jp')
                      ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left') // 🌟 JOIN untuk ambil TP
                      ->join('class_rombel cr', 'cr.id = a.rombel_id', 'left')
                      ->join('master_classes mc', 'mc.id = cr.master_class_id', 'left')
                      ->where('a.alokasi_tanggal IS NOT NULL')
                      ->where('a.alokasi_tanggal !=', '');
                      
        // Jika sistem Anda menyimpan guru_id di tabel kurikulum_atp, aktifkan baris di bawah ini:
        // ->where('a.guru_id', $userId);
        
        $atpData = $builder->get()->getResultArray();

        // 4. Pemecahan Tanggal dan Penggabungan Data Jurnal Manual
        $jurnalList = [];
        foreach ($atpData as $atp) {
            // Pecah jika tanggal digabung pakai koma (,) atau titik koma (;)
            $tanggals = preg_split('/[,;]/', $atp['alokasi_tanggal']);
            
            foreach ($tanggals as $tgl) {
                $tgl = trim($tgl);
                if (empty($tgl) || strlen($tgl) < 10) continue; // Pastikan format tanggal valid

                // Cek apakah tanggal pecahan ini masuk ke dalam bulan yang dipilih guru
                $bulanTgl = date('m', strtotime($tgl));
                
                if ($bulanTgl === $bulanPilih) {
                    // Cek apakah guru sudah pernah mengetik jurnal untuk atp_id & tanggal ini
                    $jurnalTersimpan = $db->table('jurnal_mengajar')
                                          ->where('atp_id', $atp['atp_id'])
                                          ->where('tanggal', $tgl)
                                          ->where('guru_id', $userId)
                                          ->get()->getRowArray();

                    $jurnalList[] = [
                        'atp_id'              => $atp['atp_id'],
                        'tanggal_asli'        => $tgl,
                        'hari_tanggal'        => $this->formatTanggalIndo($tgl),
                        'kelas'               => trim(($atp['class_name'] ?? '') . ' ' . ($atp['rombel_name'] ?? '')),
                        'jp'                  => $atp['estimasi_jp'] ?? 0,
                        'tujuan_pembelajaran' => $atp['tujuan_pembelajaran'] ?? 'TP Belum Diisi',
                        
                        // Menarik isian manual jika ada, jika belum kosongkan
                        'kegiatan'            => $jurnalTersimpan['kegiatan'] ?? '',
                        'refleksi'            => $jurnalTersimpan['refleksi'] ?? '',
                        'absen'               => $jurnalTersimpan['siswa_absen'] ?? ''
                    ];
                }
            }
        }

        // 5. Urutkan Array Jurnal Berdasarkan Tanggal (Ascending / Dari awal bulan ke akhir)
        usort($jurnalList, function($a, $b) {
            return strtotime($a['tanggal_asli']) - strtotime($b['tanggal_asli']);
        });

        // 6. Kirim ke View
        $data = [
            'listBulan'  => $listBulan,
            'bulanPilih' => $bulanPilih,
            'jurnalList' => $jurnalList,
            'namaBulan'  => $listBulan[$bulanPilih]
        ];

        return view('guru/jurnal_mengajar_index', $data);
    }

    // =========================================================
    // FUNGSI AJAX UNTUK MENYIMPAN KETIKAN GURU (REAL-TIME)
    // =========================================================
    public function simpanJurnal()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);

        if (!$userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Sesi login habis. Silakan login ulang.']);
        }

        $atpId    = $request->getPost('atp_id');
        $tanggal  = $request->getPost('tanggal');
        $kegiatan = $request->getPost('kegiatan');
        $refleksi = $request->getPost('refleksi');
        $absen    = $request->getPost('absen');

        // Cek apakah data sudah ada
        $existing = $db->table('jurnal_mengajar')
                       ->where('atp_id', $atpId)
                       ->where('tanggal', $tanggal)
                       ->where('guru_id', $userId)
                       ->get()->getRowArray();
        
        $dataSimpan = [
            'guru_id'     => $userId,
            'kegiatan'    => $kegiatan,
            'refleksi'    => $refleksi,
            'siswa_absen' => $absen,
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            // Update jika sudah pernah mengetik
            $db->table('jurnal_mengajar')->where('id', $existing['id'])->update($dataSimpan);
        } else {
            // Insert baru
            $dataSimpan['atp_id'] = $atpId;
            $dataSimpan['tanggal'] = $tanggal;
            $dataSimpan['created_at'] = date('Y-m-d H:i:s');
            $db->table('jurnal_mengajar')->insert($dataSimpan);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Jurnal tersimpan!']);
    }

    // Helper: Mengubah '2026-03-02' menjadi 'Senin,<br>02/03/2026'
    private function formatTanggalIndo($tanggal)
    {
        $hariInggris = date('l', strtotime($tanggal));
        $hariIndo = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        
        $namaHari = $hariIndo[$hariInggris] ?? '';
        return $namaHari . ", <br>" . date('d/m/Y', strtotime($tanggal));
    }
}