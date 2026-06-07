<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AtpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // ==============================================================
        // 1. INFO HEADER (BACKEND ONLY)
        // ==============================================================
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);
        
        $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        $titiMangsa = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $namaGuruCetak = 'Guru Pengampu';

        if ($db->tableExists('teacher_profiles')) {
            $guru = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            $namaGuruCetak = $guru['nama_guru'] ?? $guru['nama'] ?? $guru['full_name'] ?? 'Guru Pengampu';
        }

        // ==============================================================
        // 2. DINAMISASI ROMBEL
        // ==============================================================
        $daftarRombel = [];
        if ($tahunAktif && $db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel cr')
                               ->select('cr.id, cr.rombel_name, mc.class_name, mc.level_type, mc.id as master_class_id')
                               ->join('master_classes mc', 'mc.id = cr.master_class_id')
                               ->where('cr.academic_year_id', $tahunAktif['id'])
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('cr.rombel_name', 'ASC')
                               ->get()->getResultArray();
        }

        $selectedRombelId = $this->request->getGet('rombel_id') ?? (!empty($daftarRombel) ? $daftarRombel[0]['id'] : 1);

       $tingkatKelas = 7; // Default
        $masterClassId = 1; 
        $namaRombelAktif = '-';
        
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                
                // 🌟 PERBAIKAN: Deteksi Angka atau Romawi
                $angkaTingkat = preg_replace('/[^0-9]/', '', $className); // Cari angka biasa
                
                if (!empty($angkaTingkat)) {
                    $tingkatKelas = $angkaTingkat;
                } else {
                    // Jika tidak ada angka (misal pakai "Kelas VII"), kita konversi Romawi ke Angka
                    $upperClass = strtoupper($className);
                    if (strpos($upperClass, 'VIII') !== false) { $tingkatKelas = 8; }
                    elseif (strpos($upperClass, 'VII') !== false) { $tingkatKelas = 7; }
                    elseif (strpos($upperClass, 'IX') !== false) { $tingkatKelas = 9; }
                    elseif (strpos($upperClass, 'XII') !== false) { $tingkatKelas = 12; }
                    elseif (strpos($upperClass, 'XI') !== false) { $tingkatKelas = 11; }
                    elseif (strpos($upperClass, 'X') !== false) { $tingkatKelas = 10; }
                }
                
                $masterClassId = $r['master_class_id'] ?? $r['id'];
                break;
            }
        }

        // ==============================================================
        // 3. KUNCI MAPEL & MAPEL GABUNGAN (KHUSUS GURU AKTIF)
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
    
            // B. Ambil Mapel Gabungan Guru
            if ($kolomCombinedId && $db->tableExists('schedule_combined_subjects')) { 
                $mapelGabungan = $db->table('class_schedules cs')
                             // PERBAIKAN 2: Cukup ambil 'c.combined_name' sesuai dengan struktur di controller referensi
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name") 
                             // PERBAIKAN 3: Join ke tabel yang benar
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where("cs.{$kolomIdGuru}", $userId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}")
                             ->get()->getResultArray();

                foreach($mapelGabungan as $mg) {
                    if(!empty($mg['combined_id'])) {
                        $namaGabungan = $mg['combined_name'] ?? 'Mapel Gabungan';
                        $daftarMapel[] = [
                            // Saya tambahkan underscore 'C_' agar format ID-nya sama persis dengan yang di controller referensi
                            'id' => 'C_' . $mg['combined_id'], 
                            'subject_name' => $namaGabungan
                        ];
                    }
                }
            }
            
            // A. Ambil Mapel Reguler Guru KEMUDIAN
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
                    // 🌟 PERBAIKAN: Tambahkan 'S_' di depan ID mapel reguler
                    $daftarMapel[] = [
                        'id' => 'S_' . $m['id'], 
                        'subject_name' => $m['subject_name']
                    ];
                }
            }    
        }

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : 1);

        // ==============================================================
        // 4. LOAD DATA ANALISIS CP (PERBAIKAN QUERY KE KURIKULUM_CP)
        // ==============================================================
        $dataAtp = [];
        
        if ($db->tableExists('kurikulum_cp_headers') && $db->tableExists('kurikulum_cp_details')) {
             $builder = $db->table('kurikulum_cp_headers h')
                          ->select('d.*, h.mapel_id, h.master_class_id')
                          ->join('kurikulum_cp_details d', 'd.header_id = h.id', 'inner')
                          ->where('h.mapel_id', $selectedMapelId)
                          ->where('h.master_class_id', $masterClassId)
                          ->orderBy('d.urutan', 'ASC')
                          ->orderBy('d.id', 'ASC');

             $dataAtp = $builder->get()->getResultArray();
        }

        // ... (Kode sebelumnya yang mengambil data mentah dari Analisis CP ke variabel $dataAtp) ...

        // ==============================================================
        // 🌟 SINKRONISASI DENGAN DATABASE ATP (Tarik Urutan & Centang Tersimpan)
        // ==============================================================
        if (!empty($dataAtp) && !empty($selectedRombelId)) {
            // Ambil semua ID CP Detail yang sedang dimuat
            $cpIds = array_column($dataAtp, 'id');
            
            // Cari data simpanan di tabel kurikulum_atp
            $savedAtpQuery = $db->table('kurikulum_atp')
                                ->where('rombel_id', $selectedRombelId)
                                ->whereIn('cp_detail_id', $cpIds)
                                ->get()->getResultArray();
            
            $savedAtpMap = [];
            foreach ($savedAtpQuery as $s) {
                $savedAtpMap[$s['cp_detail_id']] = $s;
            }

            // Gabungkan data mentah dengan data tersimpan
            foreach ($dataAtp as &$row) {
                $cpId = $row['id'];
                if (isset($savedAtpMap[$cpId])) {
                    // Jika sudah pernah disimpan, timpa datanya
                    $row['urutan'] = $savedAtpMap[$cpId]['urutan'];
                    $row['aktivitas_tarl'] = $savedAtpMap[$cpId]['aktivitas_kognitif'];
                    
                    // Pecah string "DPL1,DPL3" menjadi Array agar mudah dicentang di View
                    $row['dpl_terpilih'] = explode(',', $savedAtpMap[$cpId]['dpl_terpilih'] ?? '');
                    $row['panca_cinta_terpilih'] = explode(',', $savedAtpMap[$cpId]['panca_cinta_terpilih'] ?? '');
                } else {
                    // Jika belum pernah disimpan (Baru ditambahkan), taruh di paling bawah
                    $row['urutan'] = 9999;
                    $row['dpl_terpilih'] = [];
                    $row['panca_cinta_terpilih'] = [];
                }
            }
            unset($row);

            // URUTKAN ULANG ARRAY berdasarkan kolom 'urutan'
            usort($dataAtp, function($a, $b) {
                return $a['urutan'] <=> $b['urutan'];
            });
        }

        // ==============================================================
        // 5. LOAD TANGGAL JADWAL & HITUNG TOTAL JP MINIMAL...
        // ==============================================================

        // ==============================================================
        // 5. LOAD TANGGAL JADWAL & HITUNG TOTAL JP MINIMAL (CERDAS & AMAN)
        // ==============================================================
        $listTanggal = [];
        $totalJpTersedia = 0;

        if ($jadwalAktif && $tahunAktif && !empty($selectedRombelId)) {
            $isCombined = (strpos($selectedMapelId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);
            
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';

            // --- PERSIAPAN VARIABEL KALENDER (Mengikuti Pola Aman AnalysisController) ---
            $kaldikEvents = $db->tableExists('academic_calendars') ? $db->table('academic_calendars')->where('academic_year_id', $tahunAktif['id'])->get()->getResultArray() : [];
            $tahunSplit = explode('/', $tahunAktif['academic_year']);
            $tahunStart = (int)trim($tahunSplit[0]);
            $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;
            $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
            $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
            
            // Definisikan 7 hari penuh agar aman dari Undefined Key
            $hariNamesNumeric = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

            // ==============================================================
            // 5a. CARI NILAI JP MINIMAL KHUSUS UNTUK TINGKAT YANG SAMA (SESUAI HEB)
            // ==============================================================
            // 1. Ambil master_class_id dari Rombel yang sedang aktif dibuka saat ini
            $currentRombel = $db->table('class_rombel')->where('id', $selectedRombelId)->get()->getRowArray();
            $currentMasterClassId = $currentRombel['master_class_id'] ?? null;

            // 2. Ambil seluruh rombel paralel di tingkat yang sama yang diajar oleh guru ini
            $builderRombelParalel = $db->table('class_schedules cs')
                                ->select('cs.rombel_id, r.rombel_name, r.master_class_id')
                                ->join('class_rombel r', 'r.id = cs.rombel_id')
                                ->where('cs.version_id', $jadwalAktif['id'])
                                ->where("cs.{$kolomIdGuru}", $userId);
            
            // Kunci filter hanya untuk tingkat kelas yang sama (Misal: Sama-sama kelas 8)
            if (!empty($currentMasterClassId)) {
                $builderRombelParalel->where('r.master_class_id', $currentMasterClassId);
            }

            if ($isCombined) { 
                $builderRombelParalel->where('cs.combined_subject_id', $realSubjectId); 
            } else { 
                $builderRombelParalel->where("cs.{$kolomSubjectId}", $realSubjectId); 
            }
            $rombelParalel = $builderRombelParalel->groupBy('cs.rombel_id, r.rombel_name, r.master_class_id')->get()->getResultArray();

            // Persiapan format tahun dan bulan persis sesuai AnalysisController
            $tahunSplit = explode('/', $tahunAktif['academic_year']);
            $tahunStart = (int)trim($tahunSplit[0]);
            $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;
            $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
            $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
            $hariNamesNumeric = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

            $kumpulanJpRombel = [];

            // 3. Hitung Alokasi Waktu HEB Rombel Paralel satu per satu
            foreach ($rombelParalel as $rp) {
                // 🌟 PERBAIKAN UTAMA: Wajib JOIN ke schedule_time_slots untuk mendapatkan day_name yang valid
                $builderSch = $db->table('class_schedules cs')
                                 ->join('schedule_time_slots ts', 'ts.id = cs.slot_id')
                                 ->where('cs.version_id', $jadwalAktif['id'])
                                 ->where('cs.rombel_id', $rp['rombel_id'])
                                 ->where("cs.{$kolomIdGuru}", $userId);
                
                if ($isCombined) { $builderSch->where('cs.combined_subject_id', $realSubjectId); } 
                else { $builderSch->where("cs.{$kolomSubjectId}", $realSubjectId); }
                $schedules = $builderSch->get()->getResultArray();

                $jpPerHari = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
                foreach ($schedules as $sch) {
                    if (isset($jpPerHari[$sch['day_name']])) {
                        $jpPerHari[$sch['day_name']] += 1;
                    }
                }

                // Ambil kaldik libur berdasarkan master_class_id masing-masing rombel
                $kaldikEvents = $db->tableExists('academic_calendars') 
                    ? $db->table('academic_calendars')
                         ->where('academic_year_id', $tahunAktif['id'])
                         ->where('class_id', $rp['master_class_id'])
                         ->get()->getResultArray() 
                    : [];

                $grandTotalJpRombel = 0;

                // Loop perhitungan tanggal HEB (Senin s.d Jumat) sesuai blueprint AnalysisController
                foreach ($bulanList as $bln) {
                    $tahunTerkait = ($isGanjil) ? $tahunStart : $tahunEnd;
                    $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);
                    $hebBulanIni = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];

                    for ($d = 1; $d <= $jmlHariBulan; $d++) {
                        $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                        $dayOfWeek = date('N', strtotime($dateStr)); 
                        if ($dayOfWeek <= 5) {
                            $namaHari = $hariNamesNumeric[$dayOfWeek];
                            $isLibur = false;
                            foreach ($kaldikEvents as $ev) {
                                if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) { 
                                    $isLibur = true; 
                                    break; 
                                }
                            }
                            if (!$isLibur) {
                                $hebBulanIni[$namaHari]++;
                            }
                        }
                    }

                    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
                        $grandTotalJpRombel += $hebBulanIni[$hari] * $jpPerHari[$hari];
                    }
                }

                $kumpulanJpRombel[] = $grandTotalJpRombel;
            }

            // MENDAPATKAN BENCHMARK JP MINIMUM KHUSUS TINGKAT TERKAIT
            $totalJpTersedia = !empty($kumpulanJpRombel) ? min($kumpulanJpRombel) : 0;

            // ==============================================================
            // 5b. DISTRIBUSI TANGGAL MINGGUAN KHUSUS UNTUK ROMBEL YANG DIPILIH
            // ==============================================================
            $hariMengajar = [];
            // PERBAIKAN: Join time_slots untuk mendapat day_name
            $builderSch = $db->table('class_schedules cs')
                             ->select('ts.day_name')
                             ->join('schedule_time_slots ts', 'ts.id = cs.slot_id')
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where('cs.rombel_id', $selectedRombelId);
            
            if ($isCombined) { $builderSch->where('cs.combined_subject_id', $realSubjectId); } 
            else { $builderSch->where("cs.{$kolomSubjectId}", $realSubjectId); }
            
            foreach ($builderSch->get()->getResultArray() as $sch) {
                if (!empty($sch['day_name']) && !in_array($sch['day_name'], $hariMengajar)) {
                    $hariMengajar[] = $sch['day_name'];
                }
            }

            // PERBAIKAN: Tarik event Kaldik KHUSUS untuk master_class_id Rombel saat ini
            $currentKaldikEvents = $db->tableExists('academic_calendars') 
                ? $db->table('academic_calendars')
                     ->where('academic_year_id', $tahunAktif['id'])
                     ->where('class_id', $currentMasterClassId)
                     ->get()->getResultArray() 
                : [];

            $rawHebDates = [];
            if (!empty($hariMengajar)) {
                foreach ($bulanList as $bln) {
                    $tahunTerkait = ($isGanjil && $bln >= 7) ? $tahunStart : (($isGanjil) ? $tahunStart : $tahunEnd);
                    if (!$isGanjil && $bln <= 6) { $tahunTerkait = $tahunEnd; }
                    
                    $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);
                    for ($d = 1; $d <= $jmlHariBulan; $d++) {
                        $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                        $dayNum = (int)date('N', strtotime($dateStr));
                        $namaHari = $hariNamesNumeric[$dayNum] ?? '';

                        if (in_array($namaHari, $hariMengajar)) {
                            $isLibur = false;
                            foreach ($currentKaldikEvents as $ev) { // PERBAIKAN: Pengecekan pakai currentKaldikEvents
                                if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) { 
                                    $isLibur = true; 
                                    break; 
                                }
                            }
                            if (!$isLibur) { 
                                $rawHebDates[] = $dateStr; 
                            }
                        }
                    }
                }
            }

            // ==============================================================
            // 5c. GABUNGKAN TANGGAL BERDASARKAN MINGGU YANG SAMA
            // ==============================================================
            $listTanggal = [];
            if (!empty($rawHebDates)) {
                $weeklyDates = [];
                foreach ($rawHebDates as $dStr) {
                    $weekKey = date('o-W', strtotime($dStr));
                    $weeklyDates[$weekKey][] = $dStr;
                }

                $namaBulanIndo = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Agu', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'];

                foreach ($weeklyDates as $week => $dates) {
                    $firstTime = strtotime($dates[0]);
                    $firstM = (int)date('n', $firstTime);
                    $firstY = date('Y', $firstTime);
                    
                    $sameMonthYear = true;
                    foreach($dates as $d) {
                         if((int)date('n', strtotime($d)) != $firstM || date('Y', strtotime($d)) != $firstY) {
                             $sameMonthYear = false; break;
                         }
                    }
                    
                    if($sameMonthYear) {
                        $daysArr = array_map(function($d) { return date('j', strtotime($d)); }, $dates);
                        if (count($daysArr) > 2) {
                            $lastDay = array_pop($daysArr);
                            $listTanggal[] = implode(', ', $daysArr) . ' dan ' . $lastDay . ' ' . $namaBulanIndo[$firstM] . ' ' . $firstY;
                        } else {
                            $listTanggal[] = implode(' dan ', $daysArr) . ' ' . $namaBulanIndo[$firstM] . ' ' . $firstY;
                        }
                    } else {
                        $formattedDates = [];
                        foreach($dates as $d) {
                            $t = strtotime($d);
                            $formattedDates[] = date('j', $t) . ' ' . $namaBulanIndo[(int)date('n', $t)] . ' ' . date('Y', $t);
                        }
                        if (count($formattedDates) > 2) {
                            $lastDate = array_pop($formattedDates);
                            $listTanggal[] = implode(', ', $formattedDates) . ' dan ' . $lastDate;
                        } else {
                            $listTanggal[] = implode(' dan ', $formattedDates);
                        }
                    }
                }
            }

        // ==============================================================
        // 5d. DISTRIBUSI TANGGAL KE TABEL ATP & HITUNG TOTAL JP
        // ==============================================================
        
        $totalAtp = count($dataAtp);
        $totalTgl = count($listTanggal);
        $totalJpAtp = 0;

        // Jika data Analisis CP sudah ada, kita distribusikan
        if ($totalAtp > 0) {
            
            // SKENARIO A: Distribusi tanggal ke materi yang ada (Jika Materi > Jadwal, otomatis 'Jadwal Habis')
            foreach ($dataAtp as $idx => &$row) {
                $row['nomor_atp'] = $tingkatKelas . '.' . ($idx + 1);
                $row['tanggal']   = $listTanggal[$idx] ?? 'Jadwal Habis / Belum Diatur';
                
                // Hitung total JP sekaligus di sini agar lebih efisien
                $totalJpAtp += (int)($row['estimasi_jp'] ?? $row['jp'] ?? 0);
            }
            unset($row); // WAJIB untuk memutuskan referensi PHP

            // SKENARIO B: Jika masih ada sisa tanggal jadwal (Jadwal > Materi), tambahkan baris kosong
            if ($totalTgl > $totalAtp) {
                for ($i = $totalAtp; $i < $totalTgl; $i++) {
                    $dataAtp[] = [
                        'nomor_atp'           => $tingkatKelas . '.' . ($i + 1),
                        'tanggal'             => $listTanggal[$i],
                        'tujuan_pembelajaran' => '', 
                        'lingkup_materi'      => '',
                        'aktivitas_tarl'      => '',
                        'estimasi_jp'         => 0,  
                        'kognitif'            => '', 
                        'dimensi'             => '',
                        'pilar'               => ''
                    ];
                }
            }
        }
        }

        // ==============================================================
        // 6. RENDER KE VIEW (DAN AMBIL DATA SEKOLAH / GURU UNTUK CETAK)
        // ==============================================================
        // 🌟 PERBAIKAN: Gunakan kolom 'key' dan tambahkan prefix 'kaldik_'
        $namaMadrasah  = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray() : null;
        $titiMangsa    = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $npkKepala     = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray() : null;

        // Tarik NPK Guru dari tabel teacher_profiles
        $guruNpk = '.....................................';
        if ($db->tableExists('teacher_profiles')) {
            $guruProfile = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guruProfile && !empty($guruProfile['nip'])) {
                $guruNpk = $guruProfile['nip'];
            }
        }

        // 🌟 TAMBAHKAN KODE INI: Tarik Nama Guru dari tabel users
        $namaGuruCetak = '.....................................';
        if ($db->tableExists('users')) {
            $guruData = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if ($guruData) {
                // Otomatis mencari kolom fullname, name, atau username di tabel users
                $namaGuruCetak = $guruData['fullname'] ?? $guruData['name'] ?? $guruData['username'] ?? 'Nama Guru Belum Diatur';
            }
        }

        // Cari Nama Mapel yang sedang dipilih
        $selectedMapelName = '-';
        if (!empty($daftarMapel)) {
            foreach ($daftarMapel as $m) {
                $mId = $m['id'] ?? $m['subject_id'] ?? $m['mapel_id'] ?? null;
                if ($mId == $selectedMapelId) {
                    $selectedMapelName = $m['subject_name'] ?? $m['nama_mapel'] ?? '-';
                    break;
                }
            }
        }

        $listProfilLulusan = [
            'DPL1' => 'Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa',
            'DPL2' => 'Kewargaan',
            'DPL3' => 'Penalaran Kritis',
            'DPL4' => 'Kreativitas',
            'DPL5' => 'Kolaborasi',
            'DPL6' => 'Kemandirian',
            'DPL7' => 'Kesehatan',
            'DPL8' => 'Komunikasi'
        ];

        $listPancaCinta = [
            'P1' => 'Cinta kepada Allah SWT dan Rasul-Nya',
            'P2' => 'Cinta kepada Ilmu',
            'P3' => 'Cinta kepada Diri dan Sesama',
            'P4' => 'Cinta kepada Alam dan Lingkungan',
            'P5' => 'Cinta kepada Bangsa, Tanah Air, dan Negara'
        ];

        $data = [
            'tahunAktif'       => $tahunAktif,
            'daftarRombel'     => $daftarRombel,
            'daftarMapel'      => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'selectedMapelName'=> $selectedMapelName, // 🌟 Pastikan masuk
            'tingkatKelas'     => $tingkatKelas,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtp'          => $dataAtp,
            
            'totalJpTersedia'  => $totalJpTersedia ?? 0,
            'totalJpAtp'       => $totalJpAtp ?? 0,
            
            // 🌟 Data Identitas untuk Cetak
            'namaMadrasah'     => $namaMadrasah ? $namaMadrasah['value'] : 'MTs MIFTAHUL HUDA (MIMHa)',
            'titiMangsa'       => $titiMangsa ? $titiMangsa['value'] : 'Bandung, ' . date('d F Y'),
            'kepalaNama'       => $kepalaSekolah ? $kepalaSekolah['value'] : 'Rully Faizal, S.T.',
            'kepalaNpk'        => $npkKepala ? $npkKepala['value'] : '-',
            'guruNpk'          => $guruNpk,
            'namaGuruCetak'    => $namaGuruCetak,
            'userId'           => $userId,
            
            'listProfilLulusan'=> $listProfilLulusan,
            'listPancaCinta'   => $listPancaCinta
        ];

        // JIKA ADA PARAMETER ?print=true DI URL, ARAHKAN KE HALAMAN CETAK
        if ($this->request->getGet('print') === 'true') {
            return view('guru/print_atp', $data);
        }

        return view('guru/atp_manage', $data);
    }

    // ==============================================================
    // FUNGSI UNTUK MENYIMPAN DATA ATP KE DATABASE
    // ==============================================================
    public function simpanAtp()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // Cek apakah request datang dari AJAX
        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        $rombelId = $request->getPost('rombel_id');
        $dataAtpJson = $request->getPost('data_atp');
        
        if (empty($rombelId) || empty($dataAtpJson)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $dataAtp = json_decode($dataAtpJson, true);
        $cpDetailIds = array_column($dataAtp, 'cp_detail_id');

        if (empty($cpDetailIds)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Tabel kosong, tidak ada yang disimpan.']);
        }

        $db->transStart();

        // 1. Bersihkan data ATP lama untuk rombel & CP ini (Mencegah duplikasi saat Update)
        $db->table('kurikulum_atp')
           ->where('rombel_id', $rombelId)
           ->whereIn('cp_detail_id', $cpDetailIds)
           ->delete();

        // 2. Siapkan array untuk Insert Massal (Batch)
        $dataToInsert = [];
        foreach ($dataAtp as $item) {
            $dataToInsert[] = [
                'cp_detail_id'         => $item['cp_detail_id'],
                'rombel_id'            => $rombelId,
                'urutan'               => $item['urutan'],
                'aktivitas_kognitif'   => $item['aktivitas_kognitif'],
                'dpl_terpilih'         => $item['dpl'], // Sudah berbentuk "DPL1,DPL3"
                'panca_cinta_terpilih' => $item['pilar'], // Sudah berbentuk "P1,P5"
                'created_at'           => date('Y-m-d H:i:s')
            ];
        }

        // 3. Masukkan data susunan yang baru
        if (!empty($dataToInsert)) {
            $db->table('kurikulum_atp')->insertBatch($dataToInsert);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Susunan ATP berhasil disimpan permanen!']);
    }

    // ==============================================================
    // FUNGSI UNTUK MERESET DATA ATP DARI DATABASE
    // ==============================================================
    public function resetAtp()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        $rombelId = $request->getPost('rombel_id');
        $cpIdsJson = $request->getPost('cp_ids');
        
        if (empty($rombelId) || empty($cpIdsJson)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $cpIds = json_decode($cpIdsJson, true);

        if (empty($cpIds)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data untuk dihapus.']);
        }

        $db->transStart();

        // Menghapus data dari tabel kurikulum_atp secara spesifik untuk kelas & TP tersebut
        $db->table('kurikulum_atp')
           ->where('rombel_id', $rombelId)
           ->whereIn('cp_detail_id', $cpIds)
           ->delete();

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal mereset data di database.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Susunan ATP berhasil dikembalikan ke posisi semula!']);
    }

}