<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class MonitoringController extends BaseController
{
    public function index()
    {
        $db      = Database::connect();
        $request = \Config\Services::request();

        // 1. Logika Pintar Filter Default Berdasarkan Tanggal Hari Ini
        $currentDay = (int) date('j');
        $defaultMonth = ($currentDay <= 15) ? date('m', strtotime('-1 month')) : date('m');
        $defaultYear  = ($currentDay <= 15) ? date('Y', strtotime('-1 month')) : date('Y');

        // Ambil dari parameter GET, jika kosong gunakan default pintar di atas
        $bulan = $request->getGet('bulan') ?? $defaultMonth;
        $tahun = $request->getGet('tahun') ?? $defaultYear;

        // 2. Ambil Hari Efektif & Tahun Ajaran Aktif
        $cekHari = $db->table('hari_efektif')->where(['bulan' => $bulan, 'tahun' => $tahun])->get()->getRowArray();
        $hariEfektif = $cekHari ? (int) $cekHari['jumlah_hari'] : 0;

        $idTahunAjaran = 0;
        if ($db->tableExists('academic_years')) {
            $cekTahun = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
        }

        // ====================================================================
        // BAGIAN A: MONITORING PER KELAS / ROMBEL
        // (Hanya: Absensi, Yaumiyah, Peminatan, Pramuka)
        // ====================================================================
        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        $monitoringKelas = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            $jmlSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();

            if ($jmlSiswa == 0) continue; 

            // 1. Persentase Absensi
            $cekAbsen = $db->table('absensi')
                           ->select('COUNT(DISTINCT tanggal) as hari_diinput')
                           ->where('rombel_id', $rombel_id)
                           ->where('MONTH(tanggal)', $bulan)
                           ->where('YEAR(tanggal)', $tahun)
                           ->get()->getRowArray();
            $hariDiinput = (int)($cekAbsen['hari_diinput'] ?? 0);
            $persenAbsen = $hariEfektif > 0 ? min(100, ($hariDiinput / $hariEfektif) * 100) : 0;

            // 2. Persentase Yaumiyah
            $targetYaumiyah = $jmlSiswa * $hariEfektif;
            $cekYaumiyah = $db->table('yaumiyah y')
                              ->join('class_rombel_students crs', 'crs.student_id = y.student_id')
                              ->where('crs.rombel_id', $rombel_id)
                              ->where('MONTH(y.tanggal)', $bulan)
                              ->where('YEAR(y.tanggal)', $tahun)
                              ->select('COUNT(y.id) as record_diinput')
                              ->get()->getRowArray();
            $recordYaumiyah = (int)($cekYaumiyah['record_diinput'] ?? 0);
            $persenYaumiyah = $targetYaumiyah > 0 ? min(100, ($recordYaumiyah / $targetYaumiyah) * 100) : 0;

            // 3. Persentase Peminatan
            $cekPem = $db->table('peminatan_grades')
                         ->where('rombel_id', $rombel_id)
                         ->where('bulan', $bulan)
                         ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                         ->get()->getRowArray();
            $persenPem = min(100, round((($cekPem['siswa_dinilai'] ?? 0) / $jmlSiswa) * 100));

            // 4. Persentase Pramuka
            $cekPra = $db->table('pramuka_grades')
                         ->where('rombel_id', $rombel_id)
                         ->where('bulan', $bulan)
                         ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                         ->get()->getRowArray();
            $persenPra = min(100, round((($cekPra['siswa_dinilai'] ?? 0) / $jmlSiswa) * 100));

            $monitoringKelas[] = [
                'rombel_name'      => $rombel['rombel_name'],
                'jml_siswa'        => $jmlSiswa,
                'persen_absen'     => round($persenAbsen),
                'persen_yaumiyah'  => round($persenYaumiyah),
                'persen_peminatan' => $persenPem,
                'persen_pramuka'   => $persenPra
            ];
        }

        // ====================================================================
        // BAGIAN B: MONITORING PER KELOMPOK AL-QUR'AN (KHUSUS REGULER)
        // ====================================================================
        $quranGroups = $db->table('quran_groups qg')
                          ->select('qg.id, qg.nama_kelompok, u.username as pembimbing')
                          ->join('users u', 'u.id = qg.pembimbing_id', 'left')
                          ->where('qg.jenis_kelompok', 'Reguler')
                          ->orderBy('qg.nama_kelompok', 'ASC')
                          ->get()->getResultArray();

        $monitoringQuran = [];

        foreach ($quranGroups as $qg) {
            $groupId = $qg['id'];
            $jmlSiswaQuran = $db->table('quran_group_students')->where('group_id', $groupId)->countAllResults();
            
            if ($jmlSiswaQuran == 0) continue;

            $penilaianQuran = $db->table('quran_penilaian qp')
                                 ->join('quran_group_students qgs', 'qgs.student_id = qp.student_id')
                                 ->where('qgs.group_id', $groupId)
                                 ->where('qp.bulan', sprintf('%02d', $bulan))
                                 ->where('qp.tahun', $tahun)
                                 ->select('qp.student_id, qp.tahsin_nilai, qp.tahfidz_nilai, qp.kitabah_nilai')
                                 ->get()->getResultArray();

            $siswaTahsin = []; $siswaTahfidz = []; $siswaKitabah = [];
            
            foreach ($penilaianQuran as $pq) {
                $sId = $pq['student_id'];
                if (!empty(trim($pq['tahsin_nilai']))) $siswaTahsin[$sId] = true;
                if (!empty(trim($pq['tahfidz_nilai']))) $siswaTahfidz[$sId] = true;
                if (!empty(trim($pq['kitabah_nilai']))) $siswaKitabah[$sId] = true;
            }

            $monitoringQuran[] = [
                'nama_kelompok'  => $qg['nama_kelompok'],
                'pembimbing'     => $qg['pembimbing'],
                'jml_siswa'      => $jmlSiswaQuran,
                'persen_tahsin'  => min(100, round((count($siswaTahsin) / $jmlSiswaQuran) * 100)),
                'persen_tahfidz' => min(100, round((count($siswaTahfidz) / $jmlSiswaQuran) * 100)),
                'persen_kitabah' => min(100, round((count($siswaKitabah) / $jmlSiswaQuran) * 100)),
            ];
        }

        // ====================================================================
        // BAGIAN C: MONITORING EKSTRAKURIKULER (KHUSUS REGULER)
        // ====================================================================
        $monitoringEskul = [];
        if ($db->tableExists('eskul_groups')) {
            $eskulGroups = $db->table('eskul_groups eg')
                              ->select('eg.id, eg.nama_kelompok, u.username as pembimbing')
                              ->join('users u', 'u.id = eg.pembimbing_id', 'left')
                              ->where('eg.jenis_kelompok', 'Reguler')
                              ->orderBy('eg.nama_kelompok', 'ASC')
                              ->get()->getResultArray();

            foreach ($eskulGroups as $eg) {
                $groupId = $eg['id'];
                $jmlSiswaEskul = 0;
                
                if ($db->tableExists('eskul_group_students')) {
                    $jmlSiswaEskul = $db->table('eskul_group_students')->where('group_id', $groupId)->countAllResults();
                }
                
                if ($jmlSiswaEskul == 0) continue;

                $dinilai = 0;
                if ($db->tableExists('eskul_grades')) {
                    $cekEskul = $db->table('eskul_grades')
                                   ->where('group_id', $groupId)
                                   ->where('bulan', $bulan)
                                   ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                                   ->get()->getRowArray();
                    $dinilai = (int)($cekEskul['siswa_dinilai'] ?? 0);
                }

                $monitoringEskul[] = [
                    'nama_kelompok'  => $eg['nama_kelompok'],
                    'pembimbing'     => $eg['pembimbing'],
                    'jml_siswa'      => $jmlSiswaEskul,
                    'persen_nilai'   => min(100, round(($dinilai / $jmlSiswaEskul) * 100)),
                ];
            }
        }

        // ====================================================================
        // BAGIAN D: MONITORING NILAI SUMATIF PER MAPEL & PER KELAS
        // ====================================================================
        $daftarMapel = [];
        $mapelDitemukan = []; 
        $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
        $mapelFields = $db->getFieldNames($tabelMapel);
        $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
        $hasCombinedTable = $db->tableExists('schedule_combined_subjects');

        $jadwalAktif = $db->tableExists('schedule_versions') ? 
                       $db->table('schedule_versions')->where('academic_year_id', $idTahunAjaran)->where('is_active', 1)->get()->getRowArray() : null;
        if (!$jadwalAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('is_active', 1)->get()->getRowArray();
        }

        $classSubjects = []; 
        $monitoringSumatif = [];
        
        if ($jadwalAktif) {
            $jadwalAktifId = $jadwalAktif['id'];
            $csFields = $db->getFieldNames('class_schedules');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;

            $schedules = $db->table('class_schedules')
                            ->where('version_id', $jadwalAktifId)
                            ->get()->getResultArray();
            foreach ($schedules as $sch) {
                $rId = $sch['rombel_id'];
                if (!isset($classSubjects[$rId])) $classSubjects[$rId] = [];
                
                if ($kolomCombinedId && !empty($sch[$kolomCombinedId])) {
                    $classSubjects[$rId]['C_' . $sch[$kolomCombinedId]] = true;
                } else if (!empty($sch[$kolomSubjectId])) {
                    $classSubjects[$rId]['S_' . $sch[$kolomSubjectId]] = true;
                }
            }

            if ($kolomCombinedId && $hasCombinedTable) {
                $mapelGabungan = $db->table('class_schedules cs')
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name")  
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktifId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}") 
                             ->get()->getResultArray();
                             
                foreach ($mapelGabungan as $mg) {
                    $namaMapel = trim($mg['combined_name'] ?? '');
                    if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') continue;
                    $cId = 'C_' . $mg['combined_id'];
                    if (!in_array($cId, $mapelDitemukan)) {
                        $daftarMapel[] = ['id' => $cId, 'nama_mapel' => $namaMapel];
                        $mapelDitemukan[] = $cId;
                    }
                }
            }

            $queryReguler = $db->table('class_schedules cs')
                          ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                          ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                          ->where('cs.version_id', $jadwalAktifId)
                          ->where("cs.{$kolomSubjectId} IS NOT NULL")
                          ->where("cs.{$kolomSubjectId} !=", 0);
                          
            if ($kolomCombinedId) {
                $queryReguler->groupStart()
                             ->where("cs.{$kolomCombinedId} IS NULL")
                             ->orWhere("cs.{$kolomCombinedId}", 0)
                             ->groupEnd();
            }
            
            $mapelReguler = $queryReguler->groupBy("cs.{$kolomSubjectId}")->get()->getResultArray();
                          
            foreach ($mapelReguler as $m) {
                $namaMapel = trim($m['subject_name'] ?? '');
                if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') continue;
                $mId = 'S_' . $m['id'];
                if (!in_array($mId, $mapelDitemukan)) {
                    $daftarMapel[] = ['id' => $mId, 'nama_mapel' => $namaMapel];
                    $mapelDitemukan[] = $mId;
                }
            }
            
            usort($daftarMapel, function($a, $b) {
                return strcmp($a['nama_mapel'], $b['nama_mapel']);
            });
            
            foreach ($daftarRombel as $rombel) {
                $rombel_id = $rombel['id'];
                $jmlSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();
                if ($jmlSiswa == 0) continue;

                $rowMapel = [];
                foreach ($daftarMapel as $mapel) {
                    $mapel_id = $mapel['id'];
                    $isTaught = isset($classSubjects[$rombel_id][$mapel_id]);

                    if (!$isTaught) {
                        $rowMapel[$mapel_id] = -1;
                    } else {
                        $cekSumatif = $db->table('nilai_sumatif')
                                         ->where('rombel_id', $rombel_id)
                                         ->where('mapel_id', $mapel_id)
                                         ->where('academic_year_id', $idTahunAjaran)
                                         ->where('bulan', $bulan)
                                         ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                                         ->get()->getRowArray();
                        $siswaDinilaiS = (int)($cekSumatif['siswa_dinilai'] ?? 0);
                        $rowMapel[$mapel_id] = min(100, round(($siswaDinilaiS / $jmlSiswa) * 100));
                    }
                }
                
                $monitoringSumatif[] = [
                    'rombel_name' => $rombel['rombel_name'],
                    'mapel'       => $rowMapel
                ];
            }
        }

        $data = [
            'bulan'             => $bulan,
            'tahun'             => $tahun,
            'hariEfektif'       => $hariEfektif,
            'monitoringKelas'   => $monitoringKelas,
            'monitoringQuran'   => $monitoringQuran,
            'monitoringEskul'   => $monitoringEskul,
            'daftarMapel'       => $daftarMapel,
            'monitoringSumatif' => $monitoringSumatif
        ];

        return view('admin/monitoring/index', $data);
    }
}