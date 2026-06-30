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
        $semester = $tahunAktif ? strtolower($tahunAktif['semester']) : 'ganjil'; 
        $tahun = date('Y');

        // 2. Daftar Bulan Berdasarkan Semester
        if (strpos($semester, 'ganjil') !== false) {
            $listBulan = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        } else {
            $listBulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni'];
        }

        $bulanPilih = $request->getGet('bulan') ?? date('m');
        if (!array_key_exists($bulanPilih, $listBulan)) {
            $bulanPilih = array_key_first($listBulan); 
        }

        // ==============================================================
        // 3. KUNCI MAPEL & MAPEL GABUNGAN (KHUSUS GURU AKTIF) - ADAPTASI ATP
        // ==============================================================
        $daftarMapel = [];
        $jadwalAktif = null;
        
        if ($tahunAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        }

        if ($jadwalAktif && $db->tableExists('class_schedules')) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;
    
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
    
            // A. Ambil Mapel Gabungan Guru
            if ($kolomCombinedId && $db->tableExists('schedule_combined_subjects')) { 
                $mapelGabungan = $db->table('class_schedules cs')
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name") 
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where("cs.{$kolomIdGuru}", $userId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}")
                             ->get()->getResultArray();

                foreach($mapelGabungan as $mg) {
                    if(!empty($mg['combined_id'])) {
                        $daftarMapel[] = [
                            'id' => 'C_' . $mg['combined_id'], 
                            'subject_name' => $mg['combined_name'] ?? 'Mapel Gabungan'
                        ];
                    }
                }
            }
            
            // B. Ambil Mapel Reguler Guru
            $mapelReguler = $db->table('class_schedules cs')
                          ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                          ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                          ->where('cs.version_id', $jadwalAktif['id'])
                          ->where("cs.{$kolomIdGuru}", $userId)
                          ->where("cs.{$kolomSubjectId} IS NOT NULL")
                          ->where("cs.{$kolomSubjectId} !=", 0)
                          ->groupBy("cs.{$kolomSubjectId}")
                          ->get()->getResultArray();
                          
            foreach($mapelReguler as $m) {
                if(!empty($m['id'])) {
                    $daftarMapel[] = [
                        'id' => 'S_' . $m['id'], 
                        'subject_name' => $m['subject_name']
                    ];
                }
            }    
        }

        // Tangkap Mapel yang dipilih
        $selectedMapelId = $request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : '');

        // ==============================================================
        // 4. KUNCI ROMBEL YANG DIAJAR (Agar Jurnal Tidak Tertukar)
        // ==============================================================
        $listRombelDiajar = [];
        if ($jadwalAktif && !empty($selectedMapelId)) {
            $isCombined = (strpos($selectedMapelId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            $builderRombel = $db->table('class_schedules cs')
                                ->select('cs.rombel_id')
                                ->where('cs.version_id', $jadwalAktif['id'])
                                ->where("cs.{$kolomIdGuru}", $userId);
            
            if ($isCombined) {
                $builderRombel->where("cs.{$kolomCombinedId}", $realSubjectId);
            } else {
                $builderRombel->where("cs.{$kolomSubjectId}", $realSubjectId);
            }
            
            $rombels = $builderRombel->groupBy('cs.rombel_id')->get()->getResultArray();
            $listRombelDiajar = array_column($rombels, 'rombel_id');
        }

        // ==============================================================
        // 5. QUERY AMBIL DATA ATP + FILTER MAPEL (JOIN KE HEADER)
        // ==============================================================
        $jurnalList = [];
        
        if (!empty($listRombelDiajar)) {
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            $builderAtp = $db->table('kurikulum_atp a')
                          ->select('a.id as atp_id, a.alokasi_tanggal, cr.rombel_name, mc.class_name, d.tujuan_pembelajaran, d.estimasi_jp')
                          ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id', 'left')
                          ->join('class_rombel cr', 'cr.id = a.rombel_id', 'left')
                          ->join('master_classes mc', 'mc.id = cr.master_class_id', 'left')
                          ->where('a.alokasi_tanggal IS NOT NULL')
                          ->where('a.alokasi_tanggal !=', '')
                          ->whereIn('a.rombel_id', $listRombelDiajar);
            
            // Menggunakan groupStart/groupEnd agar fleksibel mencocokkan mapel dengan atau tanpa prefix
            $builderAtp->groupStart()
                       ->where('h.mapel_id', $selectedMapelId)
                       ->orWhere('h.mapel_id', $realSubjectId)
                       ->groupEnd();
            
            $atpData = $builderAtp->get()->getResultArray();

            // ==============================================================
            // 6. PEMECAHAN TANGGAL & PENGGABUNGAN DATA JURNAL (DIPERBAIKI 🌟)
            // ==============================================================
            foreach ($atpData as $atp) {
                // Dipecah berdasarkan ampersand (&), koma (,), atau titik koma (;)
                $tanggals = preg_split('/[&,;]/', $atp['alokasi_tanggal']);
                
                foreach ($tanggals as $tgl) {
                    $tgl = trim($tgl);
                    if (empty($tgl)) continue;

                    // Terjemahkan singkatan bulan Indonesia ke Inggris agar bisa dibaca strtotime()
                    $tglEnglish = str_ireplace(
                        ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                        $tgl
                    );

                    $timestamp = strtotime($tglEnglish);
                    if (!$timestamp) continue;

                    $bulanTgl = date('m', $timestamp);
                    $ymdTgl = date('Y-m-d', $timestamp); // Gunakan Y-m-d standar untuk pemrosesan internal dan DB
                    
                    if ($bulanTgl === $bulanPilih) {
                        $jurnalTersimpan = $db->table('jurnal_mengajar')
                                              ->where('atp_id', $atp['atp_id'])
                                              ->where('tanggal', $ymdTgl)
                                              ->where('guru_id', $userId)
                                              ->get()->getRowArray();

                        $jurnalList[] = [
                            'atp_id'              => $atp['atp_id'],
                            'tanggal_asli'        => $ymdTgl,
                            'hari_tanggal'        => $this->formatTanggalIndo($ymdTgl),
                            'kelas'               => $atp['rombel_name'] ?? '',
                            'jp'                  => $atp['estimasi_jp'] ?? 0,
                            'tujuan_pembelajaran' => $atp['tujuan_pembelajaran'] ?? 'TP Belum Diisi',
                            'kegiatan'            => $jurnalTersimpan['kegiatan'] ?? '',
                            'refleksi'            => $jurnalTersimpan['refleksi'] ?? '',
                            'absen'               => $jurnalTersimpan['siswa_absen'] ?? ''
                        ];
                    }
                }
            }

            // Urutkan jadwal secara kronologis berdasarkan tanggal terdekat
            usort($jurnalList, function($a, $b) {
                return strtotime($a['tanggal_asli']) - strtotime($b['tanggal_asli']);
            });
        }

        // 7. KIRIM KE VIEW
        $data = [
            'listBulan'       => $listBulan,
            'bulanPilih'      => $bulanPilih,
            'jurnalList'      => $jurnalList,
            'namaBulan'       => $listBulan[$bulanPilih],
            'daftarMapel'     => $daftarMapel,
            'selectedMapelId' => $selectedMapelId
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

    public function printJurnal()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // 1. INFO HEADER & TAHUN AKTIF
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);
        $tahun = date('Y');
        
        $jadwalAktif = null;
        if ($tahunAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        }

        // ==============================================================
        // 📥 AMBIL PENGATURAN MADRASAH & TTD GURU
        // ==============================================================
        $namaMadrasah  = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray() : null;
        if (!$namaMadrasah) {
            $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        }
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $npkKepala     = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray() : null;

        $guruNpk = '-';
        $namaGuruCetak = '.....................................';

        if ($db->tableExists('teacher_profiles')) {
            $guruProfile = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guruProfile) {
                $guruNpk = $guruProfile['nip'] ?? $guruProfile['npk'] ?? '-';
                $namaGuruCetak = $guruProfile['nama_guru'] ?? $guruProfile['nama'] ?? $guruProfile['full_name'] ?? $namaGuruCetak;
            }
        }
        if ($namaGuruCetak == '.....................................' && $db->tableExists('users')) {
            $userData = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if ($userData) {
                $namaGuruCetak = $userData['fullname'] ?? $userData['name'] ?? $userData['username'] ?? $namaGuruCetak;
            }
        }
        if ($namaGuruCetak == '.....................................') {
            $namaGuruCetak = session()->get('nama_guru') ?? session()->get('fullname') ?? session()->get('name') ?? 'Guru Pengampu';
        }

        // ==============================================================
        // 2. PARAMETER FILTER (DIBACA DARI LINK TOMBOL CETAK)
        // ==============================================================
        $selectedMapelId  = $request->getGet('mapel_id');
        $bulanPilih       = $request->getGet('bulan') ?? date('m'); 

        // Mapping Seluruh Bulan Indonesia
        $allBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
        $namaBulanPilih = $allBulan[$bulanPilih] ?? '';

        // 📅 LOGIKA TITI MANGSA: Otomatis mencari tanggal terakhir dari bulan terpilih
        $hariTerakhir = date('t', strtotime("$tahun-$bulanPilih-01"));
        $titiMangsaOtomatis = "Bandung, $hariTerakhir $namaBulanPilih $tahun";

        // --- AMBIL DATA NAMA MATA PELAJARAN ---
        $namaMapelAktif  = '-';
        if (!empty($selectedMapelId)) {
            $isCombined = strpos($selectedMapelId, 'C_') === 0;
            $cleanMapelId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            if ($isCombined && $db->tableExists('schedule_combined_subjects')) {
                $m = $db->table('schedule_combined_subjects')->where('id', $cleanMapelId)->get()->getRowArray();
                if ($m) $namaMapelAktif = $m['combined_name'] ?? 'Mapel Gabungan';
            } else {
                $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
                if ($db->tableExists($tabelMapel)) {
                    $mapelFields = $db->getFieldNames($tabelMapel);
                    $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
                    
                    $m = $db->table($tabelMapel)->where('id', $cleanMapelId)->orWhere('id', $selectedMapelId)->get()->getRowArray();
                    if ($m) $namaMapelAktif = $m[$kolomNamaMapel] ?? '-';
                }
            }
        }

        // ==============================================================
        // 3. KUNCI ROMBEL YANG DIAJAR (SINKRON DENGAN METHOD INDEX)
        // ==============================================================
        $listRombelDiajar = [];
        if ($jadwalAktif && !empty($selectedMapelId) && $db->tableExists('class_schedules')) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;

            $isCombined = (strpos($selectedMapelId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            $builderRombel = $db->table('class_schedules cs')
                                ->select('cs.rombel_id')
                                ->where('cs.version_id', $jadwalAktif['id'])
                                ->where("cs.{$kolomIdGuru}", $userId);
            
            if ($isCombined && $kolomCombinedId) {
                $builderRombel->where("cs.{$kolomCombinedId}", $realSubjectId);
            } else {
                $builderRombel->where("cs.{$kolomSubjectId}", $realSubjectId);
            }
            
            $rombels = $builderRombel->groupBy('cs.rombel_id')->get()->getResultArray();
            $listRombelDiajar = array_column($rombels, 'rombel_id');
        }

        // ==============================================================
        // 4. LOAD DATA JURNAL (REVISED: MENDUKUNG PEMISAH & DAN BULAN INDO)
        // ==============================================================
        $jurnalList = [];
        
        if (!empty($listRombelDiajar) && !empty($selectedMapelId)) {
            
            $builderAtp = $db->table('kurikulum_atp a')
                          ->select('a.id as atp_id, a.alokasi_tanggal, cr.rombel_name, mc.class_name, d.tujuan_pembelajaran, d.estimasi_jp')
                          ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id', 'left')
                          ->join('class_rombel cr', 'cr.id = a.rombel_id', 'left')
                          ->join('master_classes mc', 'mc.id = cr.master_class_id', 'left')
                          ->where('a.alokasi_tanggal IS NOT NULL')
                          ->where('a.alokasi_tanggal !=', '')
                          ->whereIn('a.rombel_id', $listRombelDiajar)
                          ->where('h.mapel_id', $selectedMapelId);
            
            $atpData = $builderAtp->get()->getResultArray();

            foreach ($atpData as $atp) {
                // Memecah string tanggal dengan menyertakan karakter ampersand (&)
                $tanggals = preg_split('/[,;&]/', $atp['alokasi_tanggal']);
                
                foreach ($tanggals as $tgl) {
                    $tgl = trim($tgl);
                    if (empty($tgl) || strlen($tgl) < 10) continue;

                    // Konversi singkatan bulan Indonesia ke Inggris agar strtotime() tidak mengembalikan false
                    $blnIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    $blnEng  = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    $tglEng  = str_replace($blnIndo, $blnEng, $tgl);

                    $timestamp = strtotime($tglEng);
                    if (!$timestamp) continue; // Lewati jika konversi gagal

                    $bulanTgl = date('m', $timestamp);
                    
                    // 🌟 PERBAIKAN: Buat format Y-m-d untuk mencari data di tabel jurnal_mengajar
                    $ymdTgl = date('Y-m-d', $timestamp); 
                    
                    if ($bulanTgl === $bulanPilih) {
                        // Mencari data di jurnal_mengajar menggunakan format Y-m-d ($ymdTgl)
                        $jurnalTersimpan = $db->table('jurnal_mengajar')
                                              ->where('atp_id', $atp['atp_id'])
                                              ->where('tanggal', $ymdTgl) // <--- FIX: Gunakan $ymdTgl, bukan $tgl
                                              ->where('guru_id', $userId)
                                              ->get()->getRowArray();

                        $jurnalList[] = [
                            'atp_id'              => $atp['atp_id'],
                            'tanggal_asli'        => $ymdTgl, // <--- FIX: Gunakan $ymdTgl agar sorting ke bawah lebih aman
                            'hari_tanggal'        => $this->formatTanggalIndo($ymdTgl), // <--- FIX: Sinkron dengan format tanggal Indo
                            'kelas'               => $atp['rombel_name'] ?? '',
                            'jp'                  => $atp['estimasi_jp'] ?? 0,
                            'tujuan_pembelajaran' => $atp['tujuan_pembelajaran'] ?? 'TP Belum Diisi',
                            'kegiatan'            => $jurnalTersimpan['kegiatan'] ?? '',
                            'refleksi'            => $jurnalTersimpan['refleksi'] ?? '',
                            'absen'               => $jurnalTersimpan['siswa_absen'] ?? ''
                        ];
                    }
                }
            }

            // Urutkan jadwal secara kronologis berdasarkan tanggal asli (sekarang bekerja 100% karena menggunakan $tglEng)
            usort($jurnalList, function($a, $b) {
                return strtotime($a['tanggal_asli']) - strtotime($b['tanggal_asli']);
            });
        }

        // ==============================================================
        // 5. MAPPING VARIABEL KE VIEW PRINT
        // ==============================================================
        $data = [
            'tahunAktif'       => $tahunAktif,
            'namaMadrasah'     => $namaMadrasah ? $namaMadrasah['value'] : 'MTs MIFTAHUL HUDA (MIMHa)',
            'titiMangsa'       => $titiMangsaOtomatis, // Menampilkan tanggal AKHIR BULAN pilihan
            
            'kepalaNama'       => $kepalaSekolah ? $kepalaSekolah['value'] : 'Rully Faizal, S.T.',
            'kepalaSekolah'    => $kepalaSekolah ? $kepalaSekolah['value'] : 'Rully Faizal, S.T.',
            'kepalaNpk'        => $npkKepala ? $npkKepala['value'] : '-',
            
            'namaGuruCetak'    => $namaGuruCetak,
            'guruNpk'          => $guruNpk,
            'userId'           => $userId,
            
            // 🔄 TINGKAT / KELAS SEKARANG BERISI NAMA BULAN YANG DIPILIH
            'namaRombelAktif'  => $namaBulanPilih, 
            'kelas'            => $namaBulanPilih,
            'tingkat'          => $namaBulanPilih,
            
            'namaMapelAktif'   => $namaMapelAktif,
            'selectedMapelName'=> $namaMapelAktif, 
            
            'jurnalList'       => $jurnalList,
            'kktpData'         => $jurnalList 
        ];

        return view('guru/print_jurnal', $data);
    }

}