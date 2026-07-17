<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class NilaiSumatifController extends BaseController
{
    /**
     * Menampilkan Halaman Input Nilai Sumatif
     *
     * @return string
     */
    public function index(): string
    {
        $db = \Config\Database::connect();
        
        // Ambil User ID dengan aman
        $sessionUserId = session()->get('user_id');
        $userId = $sessionUserId ?? (function_exists('user_id') ? user_id() : 0);
        
        // ==============================================================
        // 1. INFO TAHUN AJARAN & JADWAL AKTIF
        // ==============================================================
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $tahunAktifId = $tahunAktif['id'] ?? 0;
        
        $jadwalAktif = null;
        if ($tahunAktifId > 0 && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')
                              ->where('academic_year_id', $tahunAktifId)
                              ->where('is_active', 1)
                              ->get()->getRowArray();
        }
        $jadwalAktifId = $jadwalAktif['id'] ?? 0;

        // ==============================================================
        // 2. DINAMISASI ROMBEL AKTIF
        // ==============================================================
        $daftarRombel = [];
        if ($tahunAktifId > 0 && $db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel cr')
                               ->select('cr.id, cr.rombel_name, mc.class_name, mc.level_type, mc.id as master_class_id')
                               ->join('master_classes mc', 'mc.id = cr.master_class_id')
                               ->where('cr.academic_year_id', $tahunAktifId)
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('cr.rombel_name', 'ASC')
                               ->get()->getResultArray();
        }
        
        $reqRombelId = $this->request->getGet('rombel_id');
        $selectedRombelId = $reqRombelId ?? (!empty($daftarRombel) ? $daftarRombel[0]['id'] : null);

        // ==============================================================
        // 3. LOGIKA MAPEL: REGULER DAN GABUNGAN
        // ==============================================================
        $daftarMapel = [];
        
        if ($jadwalAktifId > 0 && $db->tableExists('class_schedules')) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;
    
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
    
            // A. Ambil Mapel Reguler Terlebih Dahulu
            $mapelReguler = $db->table('class_schedules cs')
                          ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                          ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                          ->where('cs.version_id', $jadwalAktifId)
                          ->where("cs.{$kolomIdGuru}", $userId)
                          ->where("cs.{$kolomSubjectId} IS NOT NULL")
                          ->where("cs.{$kolomSubjectId} !=", 0)
                          ->groupBy("cs.{$kolomSubjectId}")
                          ->get()->getResultArray();
                          
            foreach ($mapelReguler as $m) {
                if (!empty($m['id'])) {
                    $daftarMapel[] = [
                        'id' => 'S_' . $m['id'],
                        'subject_name' => $m['subject_name'],
                        'type' => 'reguler'
                    ];
                }
            }    

            // B. Ambil Mapel Gabungan KEMUDIAN
            if ($kolomCombinedId && $db->tableExists('schedule_combined_subjects')) { 
                $mapelGabungan = $db->table('class_schedules cs')
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name")  
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktifId)
                             ->where("cs.{$kolomIdGuru}", $userId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}")
                             ->get()->getResultArray();

                foreach ($mapelGabungan as $mg) {
                    if (!empty($mg['combined_id'])) {
                        $daftarMapel[] = [
                            'id' => 'C_' . $mg['combined_id'],
                            'subject_name' => $mg['combined_name'] ?? 'Mapel Gabungan',
                            'type' => 'gabungan'
                        ];
                    }
                }
            }
        }
        
        $reqMapelId = $this->request->getGet('mapel_id');
        $selectedMapelId = $reqMapelId ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : null);

        // ==============================================================
        // 4. LOGIKA BULAN, SEMESTER & LOCK BULAN BELUM BERJALAN
        // ==============================================================
        $semesterAktif = $tahunAktif['semester'] ?? 'ganjil';
        $isGanjil = strtolower($semesterAktif) === 'ganjil';
        $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
        $namaBulan = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        
        $statusBulan = [];
        $academicYearString = $tahunAktif['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1));
        $tahunSplit = explode('/', $academicYearString);
        $thnStart = (int)trim($tahunSplit[0]);
        $thnEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $thnStart + 1;

        foreach ($bulanList as $bln) {
            $tahunBulan = ($isGanjil) ? $thnStart : $thnEnd;
            if (!$isGanjil && $bln > 6) { 
                $tahunBulan = $thnStart; 
            }

            $isLocked = false;
            if ($tahunBulan > $currentYear) {
                $isLocked = true;
            } elseif ($tahunBulan === $currentYear && $bln > $currentMonth) {
                $isLocked = true;
            }

            $statusBulan[] = [
                'id_bulan' => $bln,
                'nama_bulan' => $namaBulan[$bln],
                'is_locked' => $isLocked
            ];
        }

        // ==============================================================
        // 5. AMBIL DATA SISWA & NILAI SUMATIF
        // ==============================================================
        $siswaData = [];
        if ($selectedRombelId && $db->tableExists('class_rombel_students')) {
            $siswaData = $db->table('class_rombel_students crs')
                            ->select('u.id as student_id, u.username as name') // Diubah ke tabel users & u.username sesuai modul Kepatuhan
                            ->join('users u', 'u.id = crs.student_id') // Diubah ke tabel users
                            ->where('crs.rombel_id', $selectedRombelId)
                            ->orderBy('u.username', 'ASC')
                            ->get()->getResultArray();
                            
            if ($db->tableExists('nilai_sumatif')) {
                foreach ($siswaData as &$siswa) {
                    $nilaiRecord = $db->table('nilai_sumatif')
                                      ->where('student_id', $siswa['student_id'])
                                      ->where('rombel_id', $selectedRombelId)
                                      ->where('mapel_id', $selectedMapelId)
                                      ->where('academic_year_id', $tahunAktifId)
                                      ->get()->getResultArray();
                                      
                    $siswa['nilai'] = [];
                    foreach ($nilaiRecord as $nr) {
                        $siswa['nilai'][$nr['bulan']] = $nr['nilai_angka'];
                    }
                }
                unset($siswa); // Hapus referensi pointer setelah loop
            }
        }

        $data = [
            'tahunAktifId'     => $tahunAktifId,
            'daftarRombel'     => $daftarRombel,
            'daftarMapel'      => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'statusBulan'      => $statusBulan,
            'siswaData'        => $siswaData,
        ];

        return view('guru/nilai_sumatif_manage', $data);
    }

    /**
     * Fungsi untuk menyimpan nilai via AJAX
     *
     * @return ResponseInterface
     */
    public function simpanNilai(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        $rombelId = $request->getPost('rombel_id');
        $mapelId = $request->getPost('mapel_id'); 
        $academicYearId = $request->getPost('academic_year_id');
        $dataNilai = $request->getPost('data_nilai'); 

        if (empty($rombelId) || empty($mapelId) || empty($dataNilai) || !is_array($dataNilai)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $db->transStart();

        foreach ($dataNilai as $studentId => $bulanNilai) {
            foreach ($bulanNilai as $bulan => $nilaiAngka) {
                // Filter nilai kosong atau non-numerik (kecuali string kosong dari input)
                if ($nilaiAngka === '' || $nilaiAngka === null) {
                    continue; 
                }

                $existing = $db->table('nilai_sumatif')
                               ->where('student_id', $studentId)
                               ->where('rombel_id', $rombelId)
                               ->where('mapel_id', $mapelId)
                               ->where('bulan', $bulan)
                               ->where('academic_year_id', $academicYearId)
                               ->get()->getRowArray();

                if ($existing) {
                    $db->table('nilai_sumatif')
                       ->where('id', $existing['id'])
                       ->update([
                           'nilai_angka' => $nilaiAngka,
                           'updated_at'  => date('Y-m-d H:i:s')
                       ]);
                } else {
                    $db->table('nilai_sumatif')->insert([
                        'student_id'       => $studentId,
                        'rombel_id'        => $rombelId,
                        'mapel_id'         => $mapelId,
                        'bulan'            => $bulan,
                        'academic_year_id' => $academicYearId,
                        'nilai_angka'      => $nilaiAngka,
                        'created_at'       => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan nilai.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Nilai sumatif berhasil disimpan!']);
    }
}