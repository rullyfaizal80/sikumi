<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ModulAjarController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $uri = $this->request->getUri();
        $segment = $uri->getSegment(1); 
        $isGuru = (strtolower($segment) === 'guru');

        $userId = null;
        if (function_exists('user_id')) { $userId = user_id(); }
        elseif (session()->has('user_id')) { $userId = session()->get('user_id'); }
        elseif (session()->has('id')) { $userId = session()->get('id'); }

        $selectedTeacherId = $userId;

        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
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
        $tingkatKelas = 7;
        $masterClassId = 1;
        $namaRombelAktif = '-';
        
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                $tingkatKelas = $r['level_type'] ?? (preg_replace('/[^0-9]/', '', $className) ?: 7);
                $masterClassId = $r['master_class_id'] ?? $r['id'];
                break;
            }
        }

        $daftarMapel = [];
        $jadwalAktif = null;
        $kolomSubjectId = 'subject_id';
        
        if ($tahunAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        }

        if ($jadwalAktif && $db->tableExists('class_schedules')) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuruDiJadwal = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
    
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = 'subject_name';
            foreach (['nama_mapel', 'name', 'mapel'] as $f) {
                if (in_array($f, $mapelFields)) { $kolomNamaMapel = $f; break; }
            }

            if ($selectedTeacherId) {
                $teacherTargets = $db->table('class_schedules cs')
                                     ->select("cs.{$kolomSubjectId}, cs.combined_subject_id, s.{$kolomNamaMapel} as subject_name, c.combined_name")
                                     ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                                     ->join('schedule_combined_subjects c', 'c.id = cs.combined_subject_id', 'left')
                                     ->where('cs.version_id', $jadwalAktif['id'])
                                     ->where("cs.{$kolomIdGuruDiJadwal}", $selectedTeacherId)
                                     ->groupStart()
                                        ->where("cs.{$kolomSubjectId} IS NOT NULL")
                                        ->orWhere('cs.combined_subject_id IS NOT NULL')
                                     ->groupEnd()
                                     ->groupBy("cs.{$kolomSubjectId}, cs.combined_subject_id")
                                     ->get()->getResultArray();

                foreach ($teacherTargets as $tgt) {
                    if (!empty($tgt['combined_subject_id'])) {
                        $daftarMapel[] = ['id' => 'C' . $tgt['combined_subject_id'], 'subject_name' => '🗂️ [Gabungan] ' . ($tgt['combined_name'] ?? 'Mapel Gabungan')];
                    } elseif (!empty($tgt[$kolomSubjectId])) {
                        $daftarMapel[] = ['id' => $tgt[$kolomSubjectId], 'subject_name' => $tgt['subject_name'] ?? 'Mapel Tidak Diketahui'];
                    }
                }
            }
        }

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : null);

        $dataAtpTersimpan = [];
        $totalJpAtp = 0;

        if ($tahunAktif && $selectedTeacherId && $db->tableExists('kurikulum_cp_headers') && $db->tableExists('kurikulum_cp_details')) {
            
            $dbMapelId = '';
            if (strpos($selectedMapelId, 'C') === 0) {
                $dbMapelId = 'C_' . substr($selectedMapelId, 1);
            } else {
                $dbMapelId = (strpos($selectedMapelId, 'S_') === 0) ? $selectedMapelId : 'S_' . $selectedMapelId;
            }

            $headers = $db->table('kurikulum_cp_headers')
                          ->where('academic_year_id', $tahunAktif['id'])
                          ->where('master_class_id', $masterClassId)
                          ->where('mapel_id', $dbMapelId)
                          ->where('teacher_id', $selectedTeacherId)
                          ->get()->getResultArray();

            if (!empty($headers)) {
                $headerIds = array_column($headers, 'id');
                
                $listTanggal = [];
                $teachingDays = [];
                
                if ($jadwalAktif && $db->tableExists('class_schedules') && $db->tableExists('schedule_time_slots')) {
                    $csFields = $db->getFieldNames('class_schedules');
                    $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
                    $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;
                    
                    $searchMapelId = $selectedMapelId;
                    if (strpos($searchMapelId, 'C') === 0) $searchMapelId = substr($searchMapelId, 1);
                    elseif (strpos($searchMapelId, 'S_') === 0) $searchMapelId = substr($searchMapelId, 2);

                    $builderJadwal = $db->table('class_schedules cs')
                                      ->select('ts.day_name')
                                      ->join('schedule_time_slots ts', 'ts.id = cs.slot_id', 'left')
                                      ->where('cs.version_id', $jadwalAktif['id'])
                                      ->where('cs.rombel_id', $selectedRombelId);
                                      
                    if (strpos($selectedMapelId, 'C') === 0 && $kolomCombinedId) {
                        $builderJadwal->where("cs.{$kolomCombinedId}", $searchMapelId);
                    } else {
                        $builderJadwal->where("cs.{$kolomSubjectId}", $searchMapelId);
                    }

                    $hariJadwal = $builderJadwal->groupBy('ts.day_name')->get()->getResultArray();
                    
                    $mapHariIndo = ['senin'=>1, 'selasa'=>2, 'rabu'=>3, 'kamis'=>4, 'jumat'=>5, 'sabtu'=>6, 'minggu'=>7];
                    foreach ($hariJadwal as $hj) {
                        $hari = strtolower($hj['day_name'] ?? '');
                        if (isset($mapHariIndo[$hari])) {
                            $teachingDays[] = $mapHariIndo[$hari]; 
                        }
                    }
                }

                if (!empty($teachingDays) && $tahunAktif) {
                    $semester = strtolower($tahunAktif['semester'] ?? 'ganjil');
                    $tahunSplit = explode('/', $tahunAktif['name'] ?? date('Y'));
                    $tahunAwal = (int)$tahunSplit[0];
                    $tahunAkhir = isset($tahunSplit[1]) ? (int)$tahunSplit[1] : $tahunAwal + 1;

                    if (strpos($semester, 'genap') !== false) {
                        $startDate = strtotime("$tahunAkhir-01-01");
                        $endDate = strtotime("$tahunAkhir-06-30");
                    } else {
                        $startDate = strtotime("$tahunAwal-07-01");
                        $endDate = strtotime("$tahunAwal-12-31");
                    }

                    $libur = [];
                    if ($db->tableExists('academic_calendars')) {
                        $dataLibur = $db->table('academic_calendars')
                                        ->where('academic_year_id', $tahunAktif['id'])
                                        ->where('class_id', $masterClassId)
                                        ->get()->getResultArray();
                        foreach ($dataLibur as $dl) {
                            $libur[] = ['start' => strtotime($dl['start_date']), 'end' => strtotime($dl['end_date'])];
                        }
                    }

                    $rawHebDates = [];
                    $currentDate = $startDate;
                    
                    while ($currentDate <= $endDate) {
                        $dayOfWeek = (int)date('N', $currentDate);
                        
                        if (in_array($dayOfWeek, $teachingDays)) {
                            $isLibur = false;
                            foreach ($libur as $l) {
                                if ($currentDate >= $l['start'] && $currentDate <= $l['end']) {
                                    $isLibur = true;
                                    break;
                                }
                            }
                            if (!$isLibur) {
                                $rawHebDates[] = $currentDate; 
                            }
                        }
                        $currentDate = strtotime('+1 day', $currentDate);
                    }

                    $groupedWeeks = [];
                    foreach ($rawHebDates as $timestamp) {
                        $weekKey = date('o-W', $timestamp); 
                        $groupedWeeks[$weekKey][] = $timestamp;
                    }

                    $bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    foreach ($groupedWeeks as $week => $dates) {
                        $bln = $bulanIndo[(int)date('n', end($dates)) - 1]; 
                        $thn = date('Y', end($dates));
                        
                        if (count($dates) == 1) {
                            $tgl = date('j', $dates[0]);
                            $listTanggal[] = "$tgl $bln $thn";
                        } elseif (count($dates) == 2) {
                            $tgl1 = date('j', $dates[0]);
                            $tgl2 = date('j', $dates[1]);
                            $listTanggal[] = "$tgl1 dan $tgl2 $bln $thn"; 
                        } else {
                            $tglAwal = date('j', $dates[0]);
                            $tglAkhir = date('j', end($dates));
                            $listTanggal[] = "$tglAwal s.d $tglAkhir $bln $thn"; 
                        }
                    }
                }

                $builder = $db->table('kurikulum_cp_details d')
                              ->select('d.id as cp_detail_id, d.tujuan_pembelajaran as tp, d.lingkup_materi, d.estimasi_jp')
                              ->select('a.id as atp_id, a.urutan, a.modul_id')
                              ->join('kurikulum_atp a', "a.cp_detail_id = d.id AND (a.rombel_id = {$selectedRombelId} OR a.rombel_id IS NULL)", 'left')
                              ->orderBy('a.urutan', 'ASC'); 

                $dataAtpTersimpan = $builder->whereIn('d.header_id', $headerIds)
                                            ->orderBy('d.id', 'ASC') 
                                            ->get()->getResultArray();

                $angkaTingkat = preg_replace('/[^0-9]/', '', $tingkatKelas);
                if (empty($angkaTingkat)) $angkaTingkat = 7;

                foreach($dataAtpTersimpan as $idx => &$row) {
                    $urutan = (!empty($row['urutan'])) ? $row['urutan'] : ($idx + 1);
                    $row['nomor_atp'] = $angkaTingkat . '.' . $urutan; 
                    
                    $row['modul_id'] = $row['modul_id'] ?? null;
                    $row['status_modul'] = !empty($row['modul_id']) ? 1 : 0; 
                    
                    $row['tanggal'] = $listTanggal[$idx] ?? 'Belum Diatur';
                    $totalJpAtp += (int)($row['estimasi_jp'] ?? 0);
                }
                unset($row);
            }
        }

        $totalJpTersedia = 0; 

        $data = [
            'daftarRombel'     => $daftarRombel,
            'daftarMapel'      => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtpTersimpan' => $dataAtpTersimpan,
            'totalJpTersedia'  => $totalJpTersedia,
            'totalJpAtp'       => $totalJpAtp
        ];

        return view('guru/modul_ajar_manage', $data);
    }

    public function create()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $atpIdsStr = $request->getGet('atp_ids');
        $rombelId = $request->getGet('rombel_id');
        $mapelId = $request->getGet('mapel_id');
        
        // PERBAIKAN: Menangkap parameter tanggal dari URL
        $tglStr = $request->getGet('tgl');
        $tanggalPelaksanaan = $tglStr ? str_replace(';', ',', urldecode($tglStr)) : '';

        if (empty($atpIdsStr)) {
            return redirect()->to(base_url('guru/modul-ajar'))->with('error', 'Pilih minimal 1 TP untuk dibuatkan Modul.');
        }

        $atpIds = explode(',', $atpIdsStr);

        $namaMadrasah = 'MTs / Sekolah';
        if ($db->tableExists('settings')) {
            $settingMadrasah = $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray();
            if ($settingMadrasah && !empty($settingMadrasah['value'])) $namaMadrasah = $settingMadrasah['value'];
        }

        $namaRombel = 'Rombel Tidak Diketahui';
        if ($db->tableExists('class_rombel')) {
            $rombelData = $db->table('class_rombel cr')
                             ->select('cr.rombel_name, mc.class_name')
                             ->join('master_classes mc', 'mc.id = cr.master_class_id', 'left')
                             ->where('cr.id', $rombelId)
                             ->get()->getRowArray();
            if ($rombelData) $namaRombel = ($rombelData['class_name'] ?? '') . ' - ' . ($rombelData['rombel_name'] ?? '');
        }
        
        $namaMapelAktif = 'Mata Pelajaran';
        if ($db->tableExists('schedule_combined_subjects') && strpos($mapelId, 'C') === 0) {
            $cId = preg_replace('/^C_?/', '', $mapelId); 
            $cm = $db->table('schedule_combined_subjects')->where('id', $cId)->get()->getRowArray();
            if ($cm) $namaMapelAktif = '🗂️ [Gabungan] ' . $cm['combined_name'];
        } elseif ($db->tableExists('master_subjects')) {
            $sId = (strpos($mapelId, 'S_') === 0) ? substr($mapelId, 2) : $mapelId;
            $sm = $db->table('master_subjects')->where('id', $sId)->get()->getRowArray();
            if ($sm) $namaMapelAktif = $sm['subject_name'];
        }

        $builder = $db->table('kurikulum_atp a')
                      ->select('a.*, d.tujuan_pembelajaran, d.lingkup_materi, d.estimasi_jp')
                      ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id')
                      ->whereIn('a.id', $atpIds)
                      ->orderBy('a.urutan', 'ASC');
        
        $selectedAtpData = $builder->get()->getResultArray();

        $totalJp = 0;
        $gabunganMateri = [];
        $gabunganDpl = [];
        $gabunganPilar = [];

        foreach ($selectedAtpData as $row) {
            $totalJp += (int)$row['estimasi_jp'];
            if (!in_array($row['lingkup_materi'], $gabunganMateri)) $gabunganMateri[] = $row['lingkup_materi'];
            
            if (!empty($row['dpl_terpilih'])) {
                $dpls = explode(',', $row['dpl_terpilih']);
                foreach ($dpls as $d) { 
                    $cleanD = strtoupper(preg_replace('/\s+/', '', $d)); 
                    if (!in_array($cleanD, $gabunganDpl)) $gabunganDpl[] = $cleanD; 
                }
            }
            if (!empty($row['panca_cinta_terpilih'])) {
                $pilars = explode(',', $row['panca_cinta_terpilih']);
                foreach ($pilars as $p) { 
                    $cleanP = strtoupper(preg_replace('/\s+/', '', $p)); 
                    if (!in_array($cleanP, $gabunganPilar)) $gabunganPilar[] = $cleanP; 
                }
            }
        }

        $modulId = $selectedAtpData[0]['modul_id'] ?? null;
        $modulData = [];
        $kegiatan = [];

        if (!empty($modulId)) {
            $modulData = $db->table('kurikulum_modul_ajar')->where('id', $modulId)->get()->getRowArray() ?? [];
            if (!empty($modulData['kegiatan_pembelajaran'])) {
                $kegiatan = json_decode($modulData['kegiatan_pembelajaran'], true);
            }
            
            // Menggunakan Tanggal dari DB jika modul pernah disimpan
            if (!empty($modulData['tanggal_pelaksanaan'])) {
                $tanggalPelaksanaan = $modulData['tanggal_pelaksanaan'];
            }
        }

        $menitPerJp = $modulData['menit_per_jp'] ?? 30;
        $totalWaktu = $totalJp * $menitPerJp;

        $defaultAwal = round($totalWaktu / 6);
        $defaultPenutup = round($totalWaktu / 6);
        $defaultInti = $totalWaktu - $defaultAwal - $defaultPenutup; 

        $menitAwal = $kegiatan['awal']['menit'] ?? $defaultAwal;
        $menitInti = $kegiatan['inti']['menit'] ?? $defaultInti;
        $menitPenutup = $kegiatan['penutup']['menit'] ?? $defaultPenutup;

        $data = [
            'rombelId'        => $rombelId,
            'mapelId'         => $mapelId,
            'namaMadrasah'    => $namaMadrasah,  
            'namaRombel'      => $namaRombel,    
            'namaMapelAktif'  => $namaMapelAktif,
            'atpIdsStr'       => $atpIdsStr, 
            'tanggalPelaksanaan'=> $tanggalPelaksanaan,
            'selectedAtpData' => $selectedAtpData,
            'totalJp'         => $totalJp,
            'gabunganMateri'  => implode('; ', $gabunganMateri),
            
            // --- DUA BARIS INI YANG SEBELUMNYA TERLEWAT ---
            'gabunganDpl'     => $gabunganDpl,
            'gabunganPilar'   => $gabunganPilar,
            // ----------------------------------------------
            
            'modulId'         => $modulId,
            'modulData'       => $modulData,
            'kegiatan'        => $kegiatan,
            'menitAwal'       => $menitAwal,
            'menitInti'       => $menitInti,
            'menitPenutup'    => $menitPenutup,
            
            'listProfilLulusan' => ['DPL1'=>'Keimanan dan ketakwaan terhadap Tuhan YME','DPL2'=>'Kewargaan','DPL3'=>'Penalaran Kritis','DPL4'=>'Kreativitas','DPL5'=>'Kolaborasi','DPL6'=>'Kemandirian','DPL7'=>'Kesehatan','DPL8'=>'Komunikasi'],
            'listPancaCinta'    => ['P1'=>'Topik 1 : Cinta Allah dan Rasul-Nya','P2'=>'Topik 2 : Cinta Ilmu','P3'=>'Topik 3 : Cinta Lingkungan','P4'=>'Topik 4 : Cinta Diri dan Sesama Manusia','P5'=>'Topik 5 : Cinta Tanah Air']
        ];

        return view('guru/modul_ajar_create', $data);
    
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $rombelId = $request->getPost('rombel_id');
        $rawMapelId = $request->getPost('mapel_id');
        $atpIdsStr = $request->getPost('atp_ids');
        $modulId = $request->getPost('modul_id');

        if (strpos($rawMapelId, 'C') === 0) {
            $cleanId = preg_replace('/^C_?/', '', $rawMapelId);
            $finalMapelId = 'C_' . $cleanId;
        } else {
            $cleanId = preg_replace('/^S_?/', '', $rawMapelId);
            $finalMapelId = 'S_' . $cleanId;
        }

        $cleanAtpIds = [];
        if (!empty($atpIdsStr)) {
            $exp = explode(',', $atpIdsStr);
            foreach ($exp as $id) {
                if (trim($id) !== '') {
                    $cleanAtpIds[] = (int) trim($id);
                }
            }
        }

        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $rombel = $db->table('class_rombel')->where('id', $rombelId)->get()->getRowArray();
        $userId = session()->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);

        $kegiatan = $request->getPost('kegiatan');
        $kegiatanJson = json_encode($kegiatan);

        $dataModul = [
            'academic_year_id'       => $tahunAktif['id'] ?? 0,
            'master_class_id'        => $rombel['master_class_id'] ?? 0,
            'mapel_id'               => $finalMapelId,
            'rombel_id'              => $rombelId,
            'teacher_id'             => $userId,
            'pertemuan_ke'           => $request->getPost('pertemuan_ke'),
            'tanggal_pelaksanaan'    => $request->getPost('tanggal_pelaksanaan'), // Disimpan ke database
            'alokasi_jp'             => $request->getPost('alokasi_jp'),
            'menit_per_jp'           => $request->getPost('menit_per_jp'),
            'kesiapan_murid'         => $request->getPost('kesiapan_murid'),
            'insersi_kbc'            => $request->getPost('insersi_kbc'),
            'capaian_pembelajaran'   => $request->getPost('capaian_pembelajaran'),
            'lintas_disiplin'        => $request->getPost('lintas_disiplin'),
            'topik_pembelajaran'     => $request->getPost('topik_pembelajaran'),
            'praktik_pedagogis'      => $request->getPost('praktik_pedagogis'),
            'kemitraan_pembelajaran' => $request->getPost('kemitraan_pembelajaran'),
            'lingkungan_pembelajaran'=> $request->getPost('lingkungan_pembelajaran'),
            'pemanfaatan_digital'    => $request->getPost('pemanfaatan_digital'),
            'kegiatan_pembelajaran'  => $kegiatanJson,
            'asesmen_awal'           => $request->getPost('asesmen_awal'),
            'asesmen_proses'         => $request->getPost('asesmen_proses'),
            'asesmen_akhir'          => $request->getPost('asesmen_akhir'),
            'lampiran_materi'        => $request->getPost('lampiran_materi'),
            'lampiran_lkm'           => $request->getPost('lampiran_lkm'),
            'lampiran_rubrik'        => $request->getPost('lampiran_rubrik'),
            'sumber_belajar'         => $request->getPost('sumber_belajar'),
            'contoh_produk'          => $request->getPost('contoh_produk')
        ];

        $db->transStart();

        if (!empty($modulId)) {
            $db->table('kurikulum_modul_ajar')->where('id', $modulId)->update($dataModul);
        } else {
            $dataModul['created_at'] = date('Y-m-d H:i:s');
            $db->table('kurikulum_modul_ajar')->insert($dataModul);
            $modulId = $db->insertID();
            
            if (empty($modulId) || $modulId == 0) {
                $lastRow = $db->table('kurikulum_modul_ajar')->orderBy('id', 'DESC')->get()->getRowArray();
                $modulId = $lastRow['id'];
            }
        }

        if (!empty($cleanAtpIds) && !empty($modulId)) {
            foreach($cleanAtpIds as $aId) {
                $db->query("UPDATE kurikulum_atp SET modul_id = ? WHERE id = ?", [$modulId, $aId]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Sistem Gagal menyimpan Modul Ajar.');
        }

        return redirect()->to(base_url("guru/modul-ajar?rombel_id={$rombelId}&mapel_id={$rawMapelId}"))
                         ->with('success', 'Modul Ajar KBC berhasil disimpan dengan sukses!');
    }

    public function resetModul()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $modulId = $request->getPost('modul_id');
        $rombelId = $request->getPost('rombel_id');
        $mapelId = $request->getPost('mapel_id');
        
        if (!empty($modulId)) {
            $db->transStart();
            $db->query("UPDATE kurikulum_atp SET modul_id = NULL WHERE modul_id = ?", [$modulId]);
            $db->query("DELETE FROM kurikulum_modul_ajar WHERE id = ?", [$modulId]);
            $db->transComplete();
        }

        return redirect()->to(base_url("guru/modul-ajar?rombel_id={$rombelId}&mapel_id={$mapelId}"))
                         ->with('success', 'Modul Ajar berhasil direset / dihapus.');
    }
}
