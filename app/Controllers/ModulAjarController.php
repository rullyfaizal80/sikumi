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

        $tingkatKelas = 7; // Default
        $masterClassId = 1; // Default
        $namaRombelAktif = '-';
        
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                
                if (!empty($r['master_class_id'])) {
                    $masterClassId = $r['master_class_id'];
                }
                
                $angkaTingkat = preg_replace('/[^0-9]/', '', $className); 
                
                if (!empty($angkaTingkat)) {
                    $tingkatKelas = $angkaTingkat;
                } else {
                    $upperClass = strtoupper($className);
                    if (strpos($upperClass, 'VIII') !== false) { $tingkatKelas = 8; }
                    elseif (strpos($upperClass, 'VII') !== false) { $tingkatKelas = 7; }
                    elseif (strpos($upperClass, 'IX') !== false) { $tingkatKelas = 9; }
                    elseif (strpos($upperClass, 'XII') !== false) { $tingkatKelas = 12; }
                    elseif (strpos($upperClass, 'XI') !== false) { $tingkatKelas = 11; }
                    elseif (strpos($upperClass, 'X') !== false) { $tingkatKelas = 10; }
                }
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
        $totalJpModul = 0; 

        if ($tahunAktif && $selectedTeacherId && $db->tableExists('kurikulum_atp') && $db->tableExists('kurikulum_cp_details')) {
            $dbMapelId = '';
            if (strpos($selectedMapelId, 'C') === 0) {
                $dbMapelId = 'C_' . substr($selectedMapelId, 1);
            } else {
                $dbMapelId = (strpos($selectedMapelId, 'S_') === 0) ? $selectedMapelId : 'S_' . $selectedMapelId;
            }

            $builder = $db->table('kurikulum_atp a')
                          ->select('a.id as atp_id, a.cp_detail_id as id, a.urutan, a.modul_id, a.alokasi_tanggal')
                          ->select('d.tujuan_pembelajaran as tp, d.lingkup_materi, d.estimasi_jp')
                          ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'inner')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id', 'inner')
                          ->where('a.rombel_id', $selectedRombelId)
                          ->where('h.mapel_id', $dbMapelId)
                          ->where('h.academic_year_id', $tahunAktif['id'])
                          ->orderBy('a.urutan', 'ASC'); 

            $dataAtpTersimpan = $builder->get()->getResultArray();

            foreach($dataAtpTersimpan as $idx => &$row) {
                $urutan = (!empty($row['urutan'])) ? $row['urutan'] : ($idx + 1);
                $row['nomor_atp'] = $angkaTingkat . '.' . $urutan; 
                $row['status_modul'] = !empty($row['modul_id']) ? 1 : 0;
                $row['tanggal'] = !empty($row['alokasi_tanggal']) ? $row['alokasi_tanggal'] : 'Belum Diatur';
                
                $estimasi = (int)($row['estimasi_jp'] ?? 0);
                $totalJpAtp += $estimasi;
                
                if ($row['status_modul'] == 1) {
                    $totalJpModul += $estimasi;
                }
            }
            unset($row);
        }

       $data = [
            'daftarRombel'     => $daftarRombel,
            'daftarMapel'      => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtpTersimpan' => $dataAtpTersimpan,
            'totalJpModul'     => $totalJpModul,
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
            
            if (!empty($modulData['tanggal_pelaksanaan'])) {
                $tanggalPelaksanaan = $modulData['tanggal_pelaksanaan'];
            }
        }

        $menitPerJp = (int)($modulData['menit_per_jp'] ?? 30);
        $totalWaktu = $totalJp * $menitPerJp;

        $defaultAwal = round(($totalWaktu * 0.15) / 5) * 5;
        if ($defaultAwal == 0 && $totalWaktu > 0) $defaultAwal = 5;

        $defaultPenutup = $defaultAwal;
        $defaultInti    = $totalWaktu - $defaultAwal - $defaultPenutup;
        if ($defaultInti < 0) $defaultInti = 0;

        $menitAwal    = $kegiatan['awal']['menit'] ?? $defaultAwal;
        $menitInti    = $kegiatan['inti']['menit'] ?? $defaultInti;
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
            'gabunganDpl'     => $gabunganDpl,
            'gabunganPilar'   => $gabunganPilar,
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
            'tanggal_pelaksanaan'    => $request->getPost('tanggal_pelaksanaan'),
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

    // ==============================================================
    // FUNGSI SIKUMI AI GENERATOR (OPTIMIZED FOR GPT-OSS-120B)
    // ==============================================================
    public function generateAi()
    {
        $request = \Config\Services::request();
        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        $mapel = $request->getPost('mapel');
        $rombel = $request->getPost('rombel');
        $materi = $request->getPost('materi');
        $tp = $request->getPost('tp');
        $instruksi = $request->getPost('instruksi');
        $dpl = $request->getPost('dpl');
        $pancaCinta = $request->getPost('panca_cinta');
        
        $emptyFieldsJson = $request->getPost('empty_fields');
        $emptyFields = json_decode($emptyFieldsJson, true);

        if (empty($emptyFields)) {
            return $this->response->setJSON(['status' => 'success', 'data' => [], 'message' => 'Semua kolom sudah terisi.']);
        }

        $db = \Config\Database::connect();

        $cpAsli = "Tidak ada referensi CP Asli.";
        $atpIdsPost = $request->getPost('atp_ids'); 
        
        if (!empty($atpIdsPost)) {
            $atpIdsArray = explode(',', $atpIdsPost);
            $builder = $db->table('kurikulum_atp a')
                          ->select('h.teks_cp_asli')
                          ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id', 'left')
                          ->whereIn('a.id', $atpIdsArray)
                          ->where('h.teks_cp_asli IS NOT NULL')
                          ->groupBy('h.id');
            
            $queryCp = $builder->get()->getResultArray();
            if (!empty($queryCp)) {
                $cpTexts = array_column($queryCp, 'teks_cp_asli');
                $cpAsli = implode("\n", $cpTexts);
            }
        }

        $session = session();
        $userId = $session->get('id') ?? $session->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);
        $apiKey = '';

        if ($userId && $db->tableExists('users')) {
            $userRow = $db->table('users')->select('api_key_ai')->where('id', $userId)->get()->getRowArray();
            if ($userRow && !empty(trim($userRow['api_key_ai']))) {
                $apiKey = trim($userRow['api_key_ai']);
            }
        }

        if (empty($apiKey) && $db->tableExists('settings')) {
            $apiKeySetting = $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray();
            if ($apiKeySetting && !empty(trim($apiKeySetting['value']))) {
                $apiKey = trim($apiKeySetting['value']);
            }
        }

        if (empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'API Key AI belum dikonfigurasi di profil Anda maupun Pengaturan sistem.'
            ]);
        }

        // Endpoint diutamakan dari setting, Fallback diarahkan ke ekosistem OpenRouter (umum untuk OSS-120B)
        $apiUrl = 'https://openrouter.ai/api/v1/chat/completions'; 
        if ($db->tableExists('settings')) {
            $providerSetting = $db->table('settings')->where('key', 'ai_provider')->get()->getRowArray();
            if ($providerSetting && !empty(trim($providerSetting['value']))) {
                $apiUrl = trim($providerSetting['value']);
            }
        }

        // Mapping Kolom Form ke JSON Keys
        $keyMapping = [
            'capaian_pembelajaran' => 'capaian_pembelajaran',
            'insersi_kbc' => 'insersi_kbc',
            'kesiapan_murid' => 'kesiapan_murid',
            'lintas_disiplin' => 'lintas_disiplin', 
            'topik_pembelajaran' => 'topik_pembelajaran',     
            'praktik_pedagogis' => 'praktik_pedagogis',       
            'kemitraan_pembelajaran' => 'kemitraan_pembelajaran', 
            'lingkungan_pembelajaran' => 'lingkungan_pembelajaran', 
            'pemanfaatan_digital' => 'pemanfaatan_digital',   
            'kegiatan[awal][isi]' => 'kegiatan_awal',
            'kegiatan[inti][memahami]' => 'kegiatan_inti_memahami',
            'kegiatan[inti][mengaplikasikan]' => 'kegiatan_inti_mengaplikasikan',
            'kegiatan[inti][merefleksi]' => 'kegiatan_inti_merefleksi',
            'kegiatan[penutup][isi]' => 'kegiatan_penutup',
            'lampiran_materi' => 'lampiran_materi',
            'lampiran_lkm' => 'lampiran_lkm',
            'lampiran_rubrik' => 'lampiran_rubrik',
            'sumber_belajar' => 'sumber_belajar',
            'contoh_produk' => 'contoh_produk',
            'asesmen_awal' => 'asesmen_awal',         
            'asesmen_proses' => 'asesmen_proses', 
            'asesmen_akhir' => 'asesmen_akhir'            
        ];

        // OPTIMASI: Peta Instruksi Spesifik (Hanya dikirim jika kolom diminta)
        $instructionMap = [
            'insersi_kbc' => "Tulis strategi menanamkan nilai KBC ke materi utama secara konkret.",
            'kesiapan_murid' => "Tulis kondisi murid (Pengetahuan, Fisik, Mental) & strategi asesmen awal.",
            'capaian_pembelajaran' => "Rangkum CP asli pemerintah agar relevan dengan TP hari ini.",
            'lintas_disiplin' => "Jelaskan irisan materi ini dengan mapel SMP lainnya secara fungsional.",
            'topik_pembelajaran' => "Deskripsi mendalam esensi topik, ruang lingkup, dan relevansi dunia nyata.",
            'praktik_pedagogis' => "Sebutkan metode belajar (PBL/PjBL dll) dan penerapannya (student-centered).",
            'kemitraan_pembelajaran' => "Rencana kolaborasi internal/eksternal yang mendukung proses belajar.",
            'lingkungan_pembelajaran' => "Pengaturan fisik/virtual dan budaya kelas yang aman & inklusif.",
            'pemanfaatan_digital' => "Platform digital spesifik dan cara pemanfaatannya.",
            'kegiatan_awal' => "Langkah 1-5 berurutan di baris baru: 1. Salam, 2. Doa, 3. Presensi, 4. Pemantik, 5. Tujuan.",
            'kegiatan_inti_memahami' => "Langkah 6-8 di baris baru: Sajian masalah, Eksplorasi, Bagi LKPD.",
            'kegiatan_inti_mengaplikasikan' => "Langkah 9-11 di baris baru: Diskusi kelompok, Susun karya, Presentasi.",
            'kegiatan_inti_merefleksi' => "Langkah 12-14 di baris baru: Apresiasi, Penguatan materi, Kesimpulan.",
            'kegiatan_penutup' => "Langkah 15-17 di baris baru: Refleksi perasaan, Kisi-kisi besok, Doa & Salam.",
            'asesmen_awal' => "Teknik, instrumen diagnostik, dan 1-2 pertanyaan pemantik.",
            'asesmen_proses' => "Sebutkan teknik & rubrik singkat utk: a. Sikap, b. Pengetahuan, c. Keterampilan.",
            'asesmen_akhir' => "Mekanisme tes sumatif, instrumen, dan fokus evaluasi.",
            'lampiran_materi' => "Ringkasan bahan ajar. Akhiri dengan: '🔗 Tautan Dokumen Materi: [Link]'",
            'lampiran_lkm' => "Kisi-kisi LKPD. Akhiri dengan: '🔗 Tautan LKPD: [Link]'",
            'lampiran_rubrik' => "Kriteria pedoman skor. Akhiri dengan: '🔗 Tautan Rubrik: [Link]'",
            'sumber_belajar' => "Referensi tambahan. Akhiri dengan: '🔗 Tautan Sumber: [Link]'",
            'contoh_produk' => "Contoh hasil karya ideal. Akhiri dengan: '🔗 Tautan Contoh Produk: [Link]'"
        ];

        $jsonKeysRequested = [];
        $dynamicInstructions = "";
        
        foreach($emptyFields as $field) {
            $key = $keyMapping[$field] ?? $field;
            $jsonKeysRequested[] = $key;
            if (isset($instructionMap[$key])) {
                $dynamicInstructions .= "- Untuk key '" . $key . "': " . $instructionMap[$key] . "\n";
            }
        }
        
        $jsonStructureString = "{";
        $count = count($jsonKeysRequested);
        for($i=0; $i<$count; $i++) {
            $jsonStructureString .= '"' . $jsonKeysRequested[$i] . '":"..."';
            if ($i < $count - 1) $jsonStructureString .= ', ';
        }
        $jsonStructureString .= "}";

        // OPTIMASI: Kompresi Knowledge Base
        $panduanKbcMateri = "Ringkasan KBC:
        1. Cinta Allah & Rasul: Keimanan, welas asih, teladan sifat Rasulullah.
        2. Cinta Ilmu: Adab belajar, penalaran kritis, inovasi, pembelajar hayat.
        3. Cinta Lingkungan: Rahmatan lil 'alamin, jaga alam, thaharah, cegah kerusakan.
        4. Cinta Diri & Sesama: Self-compassion, akhlak terpuji, toleransi, empati, ukhuwah.
        5. Cinta Tanah Air: Ukhuwah wathaniyah, persatuan, kontribusi kebangsaan.";

        $systemInstruction = "Anda adalah Master Trainer Kurikulum Merdeka & KBC. "
                           . "Tugas Anda mengisi kolom JSON Modul Ajar yang kosong dengan standar pedagogis tinggi.\n\n"
                           . "ATURAN KONTEN SPESIFIK:\n"
                           . $dynamicInstructions . "\n"
                           . "ATURAN FORMAT JSON (MUTLAK):\n"
                           . "- Tipe data value wajib STRING. Dilarang membuat Object/Array bersarang di dalam value.\n"
                           . "- Gunakan literal '\\n' untuk baris baru. Dilarang menggunakan enter asli (raw newline) di dalam string.\n"
                           . "- Setiap angka/poin list WAJIB turun ke baris baru menggunakan '\\n'.\n"
                           . "- WAJIB menggunakan KUTIP GANDA (\") untuk membungkus Key dan Value. Jika butuh kutip dalam teks, gunakan pelarian (\\\").\n\n"
                           . "Hasilkan HANYA format JSON valid berikut ini:\n"
                           . $jsonStructureString;

        $userPrompt = "Lengkapi Modul Ajar:\n"
                    . "- Mapel: $mapel\n"
                    . "- Rombel: $rombel\n"
                    . "- Materi Utama: $materi\n"
                    . "- DPL: " . (!empty($dpl) ? $dpl : 'Sesuaikan') . "\n"
                    . "- Nilai KBC: " . (!empty($pancaCinta) ? $pancaCinta : 'Sesuaikan') . "\n\n"
                    . "[PANDUAN KBC]\n$panduanKbcMateri\n\n"
                    . "[TUJUAN PEMBELAJARAN (TP)]\n$tp\n\n"
                    . "[TEKS CP ASLI]\n$cpAsli\n\n";
        
        if (!empty($instruksi)) {
            $userPrompt .= "[INSTRUKSI GURU]\n$instruksi\n";
        }

        // OPTIMASI API: Hapus response_format agar tidak ditolak model Open-Source 120b
        $data = [
            'model' => 'openai/gpt-oss-120b', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.65, 
            'max_tokens' => 6000
        ];

        $headers = [ 'Authorization: Bearer ' . $apiKey, 'Content-Type: application/json' ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($responseRaw, true);

        if ($httpCode == 429) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kuota AI habis (Limit API). Silakan tunggu beberapa saat.']);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $aiText = $responseData['choices'][0]['message']['content'];
                
                // SANITIZER: Bersihkan Markdown JSON block
                $aiText = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', $aiText);
                
                if (preg_match('/\{[\s\S]*\}/', $aiText, $matches)) {
                    $aiText = $matches[0];
                }
                
                // SANITIZER: Ganti raw newline tersembunyi yang sering merusak parsing
                $aiText = str_replace(["\r\n", "\r", "\n", "\t"], ["\\n", "\\n", "\\n", " "], trim($aiText));
                
                $jsonOutput = json_decode($aiText, true);

                if($jsonOutput !== null) {
                    return $this->response->setJSON(['status' => 'success', 'data' => $jsonOutput]);
                } else {
                    $jsonError = json_last_error_msg();
                    return $this->response->setJSON(['status' => 'error', 'message' => "Kesalahan format AI: $jsonError. Silakan klik Generate lagi."]);
                }
            }
        }

        $errorMessage = $responseData['error']['message'] ?? 'Kesalahan dari server AI.';
        return $this->response->setJSON(['status' => 'error', 'message' => $errorMessage]);
    }

    public function printModul($modulId)
    {
        $db = \Config\Database::connect();

        $modulData = $db->table('kurikulum_modul_ajar')->where('id', $modulId)->get()->getRowArray();
        if (!$modulData) {
            return redirect()->back()->with('error', 'Data modul tidak ditemukan.');
        }

        $rombel = $db->tableExists('class_rombel') ? $db->table('class_rombel')->where('id', $modulData['rombel_id'])->get()->getRowArray() : null;
        $namaRombel = $rombel ? $rombel['rombel_name'] : '-';

        $rawMapelId = $modulData['mapel_id'];
        $namaMapelAktif = $rawMapelId; 

        if (strpos($rawMapelId, 'S_') === 0) {
            $idMapel = str_replace('S_', '', $rawMapelId);
            $mapelRow = $db->tableExists('master_subjects') ? $db->table('master_subjects')->where('id', $idMapel)->get()->getRowArray() : null;
            if ($mapelRow) $namaMapelAktif = $mapelRow['subject_name'] ?? $mapelRow['nama_mapel'] ?? $mapelRow['name'] ?? $rawMapelId;
        } elseif (strpos($rawMapelId, 'C_') === 0) {
            $idMapel = str_replace('C_', '', $rawMapelId);
            $mapelRow = $db->tableExists('schedule_combined_subjects') ? $db->table('schedule_combined_subjects')->where('id', $idMapel)->get()->getRowArray() : null;
            if ($mapelRow) $namaMapelAktif = $mapelRow['combined_name'] ?? $rawMapelId;
        }

        $atpList = $db->table('kurikulum_atp a')
                      ->select('d.tujuan_pembelajaran, a.dpl_terpilih, a.panca_cinta_terpilih') 
                      ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left')
                      ->where('a.modul_id', $modulId)
                      ->get()->getResultArray();
                      
        $tpTexts = [];
        $dplArray = [];
        $pancaCintaArray = [];

        foreach($atpList as $idx => $atp) {
            if (!empty($atp['tujuan_pembelajaran'])) {
                $tpTexts[] = ($idx + 1) . ". " . $atp['tujuan_pembelajaran'];
            }
            if(!empty($atp['dpl_terpilih'])) {
                $ex1 = explode(',', $atp['dpl_terpilih']);
                foreach($ex1 as $e) $dplArray[] = trim($e);
            }
            if(!empty($atp['panca_cinta_terpilih'])) {
                $ex2 = explode(',', $atp['panca_cinta_terpilih']);
                foreach($ex2 as $e) $pancaCintaArray[] = trim($e);
            }
        }
        
        $tujuanPembelajaranTeks = empty($tpTexts) ? "Tujuan Pembelajaran belum dirumuskan." : implode("\n", $tpTexts);
        $dplArray = array_unique(array_filter($dplArray));
        $pancaCintaArray = array_unique(array_filter($pancaCintaArray));

        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);

        $namaMadrasahRow = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray() : null;
        if (!$namaMadrasahRow) {
            $namaMadrasahRow = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        }
        $titiMangsaRow    = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolahRow = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $npkKepalaRow     = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray() : null;

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

        $namaGuruCetak = trim(str_ireplace(['Nama :', 'Nama: ', 'Nama '], '', $namaGuruCetak));
        $guruNpk = trim(str_ireplace(['NIM :', 'NIM: ', 'NIM ', 'NPK :', 'NPK: ', 'NPK ', 'NIP :', 'NIP: '], '', $guruNpk));

        $titimangsaValue = $titiMangsaRow ? trim($titiMangsaRow['value']) : 'Bandung';
        if (strpos($titimangsaValue, ',') !== false) {
            $titiMangsa = $titimangsaValue;
        } else {
            $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tanggalCetak = date('j') . ' ' . $bulanIndo[(int)date('m')] . ' ' . date('Y');
            $titiMangsa = $titimangsaValue . ', ' . $tanggalCetak;
        }

        $data = [
            'modulId'                => $modulId,
            'modulData'              => $modulData,
            'namaMadrasah'           => $namaMadrasahRow ? $namaMadrasahRow['value'] : 'MTs MIFTAHUL HUDA (MIMHa)',
            'tahunAktif'             => $tahunAktif,
            'namaMapelAktif'         => $namaMapelAktif, 
            'namaRombel'             => $namaRombel,
            'tujuanPembelajaranTeks' => $tujuanPembelajaranTeks,
            'topikPembelajaranTeks'  => $modulData['topik_pembelajaran'] ?? '-',
            'dplArray'               => $dplArray,                 
            'pancaCintaArray'        => $pancaCintaArray,   
            'kepalaNama'             => $kepalaSekolahRow ? $kepalaSekolahRow['value'] : 'Rully Faizal, S.T.',
            'kepalaSekolah'          => $kepalaSekolahRow ? $kepalaSekolahRow['value'] : 'Rully Faizal, S.T.',
            'kepalaNpk'              => $npkKepalaRow ? trim($npkKepalaRow['value']) : '-',
            'titiMangsa'             => $titiMangsa,
            'userId'                 => $userId,
            'namaGuruCetak'          => trim($namaGuruCetak),
            'guruNpk'                => trim($guruNpk)
        ];

        return view('guru/modul_ajar_print', $data);
    }

    public function copyAllModul()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $fromRombelId = $request->getPost('from_rombel_id');
        $toRombelId   = $request->getPost('to_rombel_id');
        $mapelId      = $request->getPost('mapel_id');
        
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);

        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $tahunAktifId = $tahunAktif ? $tahunAktif['id'] : 0;

        if (!$fromRombelId || !$toRombelId || !$mapelId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter pengiriman tidak lengkap.']);
        }

        $fromRombel = $db->table('class_rombel')->where('id', $fromRombelId)->get()->getRowArray();
        $toRombel   = $db->table('class_rombel')->where('id', $toRombelId)->get()->getRowArray();

        if (!$fromRombel || !$toRombel) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data Kelas/Rombel tidak ditemukan.']);
        }

        if ($fromRombel['master_class_id'] != $toRombel['master_class_id']) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Gagal! Penyalinan masal hanya diperbolehkan untuk Rombel di tingkat yang sama.'
            ]);
        }

        $cleanMapelId = preg_replace('/[^0-9]/', '', $mapelId);
        $kemungkinanMapelId = [
            $mapelId,
            $cleanMapelId,
            'S_' . $cleanMapelId,
            'C_' . $cleanMapelId,
            'M_' . $cleanMapelId
        ];

        $sourceModuls = $db->table('kurikulum_modul_ajar')
                           ->where('academic_year_id', $tahunAktifId)
                           ->where('rombel_id', $fromRombelId)
                           ->whereIn('mapel_id', $kemungkinanMapelId)
                           ->get()->getResultArray();

        if (empty($sourceModuls)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => "Rombel sumber belum memiliki data Modul Ajar untuk mapel ini."
            ]);
        }

        $db->transStart();

        $db->table('kurikulum_modul_ajar')
           ->where('rombel_id', $toRombelId)
           ->whereIn('mapel_id', $kemungkinanMapelId) 
           ->delete();

        $targetAtpList = $db->table('kurikulum_atp')
                            ->where('rombel_id', $toRombelId)
                            ->get()->getResultArray();
                            
        $targetAtpIds = array_column($targetAtpList, 'id');
        
        if (!empty($targetAtpIds)) {
            $db->table('kurikulum_atp')->whereIn('id', $targetAtpIds)->update(['modul_id' => null]);
        }

        $insertedCount = 0;

        foreach ($sourceModuls as $modul) {
            
            $sourceAtps = $db->table('kurikulum_atp')
                             ->where('modul_id', $modul['id'])
                             ->get()->getResultArray();
                             
            $resolvedTanggal = null;
            $matchedTargetAtpIds = []; 
            
            foreach ($sourceAtps as $sAtp) {
                $cpDetailId = $sAtp['cp_detail_id'];
                
                $matchTargetAtp = $db->table('kurikulum_atp')
                                     ->where('rombel_id', $toRombelId)
                                     ->where('cp_detail_id', $cpDetailId)
                                     ->get()->getRowArray();
                                     
                if ($matchTargetAtp) {
                    $matchedTargetAtpIds[] = $matchTargetAtp['id'];
                    
                    if ($resolvedTanggal === null) {
                        $resolvedTanggal = $matchTargetAtp['alokasi_tanggal'] ?? $matchTargetAtp['tanggal'] ?? null;
                    }
                }
            }

            if (empty($resolvedTanggal)) {
                $resolvedTanggal = $modul['tanggal_pelaksanaan'];
            }

            $newModulData = [
                'academic_year_id'        => $tahunAktifId,
                'master_class_id'         => $toRombel['master_class_id'],
                'mapel_id'                => $modul['mapel_id'], 
                'rombel_id'               => $toRombelId,
                'teacher_id'              => $userId,
                'pertemuan_ke'            => $modul['pertemuan_ke'],
                'tanggal_pelaksanaan'     => $resolvedTanggal, 
                'alokasi_jp'              => $modul['alokasi_jp'] ?? 0,
                'menit_per_jp'            => $modul['menit_per_jp'] ?? 30,
                'kesiapan_murid'          => $modul['kesiapan_murid'],
                'lintas_disiplin'         => $modul['lintas_disiplin'],
                'topik_pembelajaran'      => $modul['topik_pembelajaran'],
                'praktik_pedagogis'       => $modul['praktik_pedagogis'],
                'kemitraan_pembelajaran'  => $modul['kemitraan_pembelajaran'],
                'lingkungan_pembelajaran' => $modul['lingkungan_pembelajaran'],
                'pemanfaatan_digital'     => $modul['pemanfaatan_digital'],
                'insersi_kbc'             => $modul['insersi_kbc'],
                'capaian_pembelajaran'    => $modul['capaian_pembelajaran'],
                'kegiatan_pembelajaran'   => $modul['kegiatan_pembelajaran'],
                'sumber_belajar'          => $modul['sumber_belajar'],
                'contoh_produk'           => $modul['contoh_produk'],
                'asesmen_awal'            => $modul['asesmen_awal'],
                'asesmen_proses'          => $modul['asesmen_proses'],
                'asesmen_akhir'           => $modul['asesmen_akhir'],
                'lampiran_materi'         => $modul['lampiran_materi'],
                'lampiran_lkm'            => $modul['lampiran_lkm'],
                'lampiran_rubrik'         => $modul['lampiran_rubrik'],
                'created_at'              => date('Y-m-d H:i:s')
            ];

            $db->table('kurikulum_modul_ajar')->insert($newModulData);
            $newModulId = $db->insertID(); 
            $insertedCount++;

            if (!empty($matchedTargetAtpIds)) {
                $db->table('kurikulum_atp')
                   ->whereIn('id', $matchedTargetAtpIds)
                   ->update(['modul_id' => $newModulId]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Terjadi kegagalan sistem saat menyalin data ke database.']);
        }

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => 'Sempurna! Berhasil menyalin ' . $insertedCount . ' Modul Ajar.'
        ]);
    }
}
