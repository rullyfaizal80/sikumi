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
                
                // 🌟 TANGKAP MASTER CLASS ID SECARA DINAMIS DARI DATABASE
                if (!empty($r['master_class_id'])) {
                    $masterClassId = $r['master_class_id'];
                }
                
                // Deteksi Angka atau Romawi
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

        // ==============================================================
        // 4. LOAD DATA ATP TERSIMPAN (LANGSUNG DARI TABEL ATP)
        // ==============================================================
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

            // 🌟 KODE PERBAIKAN: Beri alias a.cp_detail_id menjadi id
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
                // Penomoran ATP
                $urutan = (!empty($row['urutan'])) ? $row['urutan'] : ($idx + 1);
                $row['nomor_atp'] = $angkaTingkat . '.' . $urutan; 
                
                // Status Modul Ajar
                $row['status_modul'] = !empty($row['modul_id']) ? 1 : 0;
                
                // 🌟 KUNCI PERBAIKAN: Langsung panggil tanggal dari kolom alokasi_tanggal
                $row['tanggal'] = !empty($row['alokasi_tanggal']) ? $row['alokasi_tanggal'] : 'Belum Diatur';
                
                // Perhitungan JP
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
            'totalJpModul'     => $totalJpModul, // PERBAIKAN: Ganti nama variabel
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

   // ==============================================================
    // FUNGSI SIKUMI AI GENERATOR (GROQ LLaMA - STRICT & DYNAMIC CP)
    // ==============================================================
    public function generateAi()
    {
        $request = \Config\Services::request();
        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        // 1. Tangkap Konteks dari Form
        $mapel = $request->getPost('mapel');
        $rombel = $request->getPost('rombel');
        $materi = $request->getPost('materi');
        $tp = $request->getPost('tp');
        $instruksi = $request->getPost('instruksi');
        $dpl = $request->getPost('dpl');
        $pancaCinta = $request->getPost('panca_cinta');
        
        // Tangkap Array Kolom Kosong
        $emptyFieldsJson = $request->getPost('empty_fields');
        $emptyFields = json_decode($emptyFieldsJson, true);

        if (empty($emptyFields)) {
            return $this->response->setJSON(['status' => 'success', 'data' => [], 'message' => 'Semua kolom sudah terisi.']);
        }

        $db = \Config\Database::connect();

        // 2. Mengambil Teks CP Asli dari Database berdasarkan ATP yang dipilih
        $cpAsli = "Tidak ada referensi CP Asli.";
        $atpIdsPost = $request->getPost('atp_ids'); 
        
        if (!empty($atpIdsPost)) {
            $atpIdsArray = explode(',', $atpIdsPost);
            
            $builder = $db->table('kurikulum_atp a');
            $builder->select('h.teks_cp_asli');
            $builder->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id', 'left');
            $builder->join('kurikulum_cp_headers h', 'h.id = d.header_id', 'left');
            $builder->whereIn('a.id', $atpIdsArray);
            $builder->where('h.teks_cp_asli IS NOT NULL');
            $builder->groupBy('h.id');
            
            $queryCp = $builder->get()->getResultArray();
            
            if (!empty($queryCp)) {
                $cpTexts = [];
                foreach($queryCp as $row) {
                    $cpTexts[] = $row['teks_cp_asli'];
                }
                $cpAsli = implode("\n", $cpTexts);
            }
        }

        // 3. Ambil Pengaturan API
        $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
        $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';

        $apiProviderSetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_provider')->get()->getRowArray() : null;
        $apiUrl = (!empty($apiProviderSetting['value'])) ? trim($apiProviderSetting['value']) : 'https://api.groq.com/openai/v1/chat/completions';

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kunci akses API belum dipasang.']);
        }

        // 4. Mapping Nama HTML ke Format JSON AI
        $keyMapping = [
            'capaian_pembelajaran' => 'capaian_pembelajaran', // Tambahan untuk rangkuman CP
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

        $jsonKeysRequested = [];
        foreach($emptyFields as $field) {
            $jsonKeysRequested[] = $keyMapping[$field] ?? $field;
        }
        
        $jsonStructureString = "{";
        $count = count($jsonKeysRequested);
        for($i=0; $i<$count; $i++) {
            $jsonStructureString .= '"' . $jsonKeysRequested[$i] . '":"..."';
            if ($i < $count - 1) $jsonStructureString .= ', ';
        }
        $jsonStructureString .= "}";

        // KNOWLEDGE BASE: BUKU PANDUAN PANCA CINTA KBC
        $panduanKbcMateri = "
        1. Topik: Cinta Allah Swt. dan Rasul-Nya
           - Tujuan: Menumbuhkan pemahaman mengenai sifat Allah yang Maha Cinta serta Rasulullah sebagai sosok teladan penuh cinta; Mengenal sifat jamaliyah dan jalaliyah secara seimbang menggantikan citra Allah maha penghukum; Memahami welas asih (rahmah) Allah lebih dominan daripada murka-Nya sehingga tumbuh rasa cinta dalam beribadah.
           - Materi Pokok: Keimanan & ketakwaan; Meneladani Asmaul Husna (ar-Rahman, ar-Rahim, al-'Adl, al-Latif, ar-Rauf); Ibadah khusyu (salat, doa, zikir, Al-Qur'an); Mensyukuri nikmat; Sirah Nabawiyah tentang kasih sayang; Sifat Rasulullah (cerdas, jujur, amanah, lemah lembut, dermawan); Hadis cinta dan akhlak mulia.
        
        2. Topik: Cinta Ilmu
           - Tujuan: Menumbuhkan pemahaman bahwa dengan ilmu manusia mampu membuka tabir keagungan penciptaan dan merasakan getaran cinta Ilahi yang universal melalui alam, sejarah, dan ajaran agama.
           - Materi Pokok: Pilar sukses mencari ilmu (niat, tekun, tawakal, wara', yakin, syukur); Alat transformasi sosial; Literasi sumber ilmu; Pembelajar sepanjang hayat; Adab kepada guru; Pemanfaatan teknologi; Inovasi & penalaran kritis; Keseimbangan hidup; Keberagaman sejarah/budaya; Sumber ilmu qauliyah & kauniyah.
        
        3. Topik: Cinta Lingkungan
           - Tujuan: Memahami alam semesta sebagai manifestasi cinta Allah; Membangun relasi non-transaksional dilandasi cinta/kepedulian pada alam; Menghayati sunnatullah sebagai sistem keseimbangan ciptaan demi keberlanjutan.
           - Materi Pokok: Islam Rahmatan lil 'alamin; Adab pada alam/lingkungan; Menghindari fasad/kerusakan (QS. Al-A'raf: 56, QS. Ar-Rum: 41); Praktik thaharah (kebersihan) & hemat energi (larangan ishraf).
        
        4. Topik: Cinta Diri dan Sesama Manusia
           - Tujuan: Mengenal Allah melalui pengenalan diri sebagai tajalli cinta-Nya; Penerapan self-compassion (welas asih diri fisik, emosi, spiritual); Keterampilan SES (Social Emotional Skill) untuk kesejahteraan mental; Memahami kesatuan manusia yang setara; Menerima keragaman sebagai fitrah; Menerapkan prinsip persaudaraan (tasamuh, tawasuth, syura).
           - Materi Pokok: Akhlak terpuji diri (tawakal, ikhtiar, syukur, sabar, qanaah, kreatif, produktif, inovatif); Hindari akhlak tercela diri (ananiah, putus asa, ghadab, tamak); Menjaga kebersihan/kesehatan tubuh; Ukhuwah Islamiyah & Insaniyah; Adab kepada orang tua, saudara, tetangga, teman, sesama/antar umat beragama; Akhlak terpuji sesama (ta'awun, tafahum, tasamuh, tawadhu, husnuzhan); Hindari akhlak tercela sesama (ananiah, rafast, gadhab, su'uzhan, ghibah, fitnah, namimah).
        
        5. Topik: Cinta Tanah Air
           - Tujuan: Menumbuhkan semangat cinta tanah air sebagai bagian dari iman.
           - Materi Pokok: Ukhuwah wathaniyah (persaudaraan kebangsaan); Hubbul Wathan minal Iman; Menghormati perbedaan suku, budaya, agama (QS. Al-Hujurat: 13); Berkontribusi menjaga kedaulatan/keamanan negara.
        ";

       // 5. PROMPT DENGAN ATURAN LOGIKA KBC & CP UTAMA (REVISI TAMBAHAN CP)
        $systemInstruction = "Anda adalah Master Trainer Kurikulum Merdeka & Kurikulum Berbasis Cinta (KBC) MIMHa. "
                           . "Tugas Anda mengisi kolom Modul Ajar yang kosong dengan standar pedagogis tinggi.\n\n"
                           . "ATURAN KHUSUS UNTUK KEY TERTENTU:\n"
                           . "1. Jika meminta key 'insersi_kbc':\n"
                           . "   - Pahami TOPIK, TUJUAN, dan MATERI POKOK yang sesuai dari [DOKUMEN PANDUAN PANCA CINTA KBC].\n"
                           . "   - Tuliskan HANYA KESIMPULAN STRATEGI INTEGRASI-nya saja (1-2 paragraf singkat) yang langsung menarasikan BAGAIMANA cara guru menanamkan nilai KBC tersebut ke dalam Materi Utama ($materi) dan Tujuan Pembelajaran secara konkret.\n"
                           . "   - Contoh gaya bahasa awal kalimat: 'Menanamkan integrasi nilai ini dengan memberikan contoh...'\n\n"
                           . "2. Jika meminta key 'kesiapan_murid':\n"
                           . "   - Tuliskan kondisi murid meliputi aspek Pengetahuan, Fisik, Mental, Sosial, dan Spiritual.\n"
                           . "   - Berikan strategi instrumen Asesmen Awal (Diagnostik) untuk mengukur pengetahuan awal serta rencana tindak lanjutnya nyata bagi murid.\n\n"
                           . "3. Jika meminta key 'capaian_pembelajaran':\n" // 🌟 TAMBAHKAN ATURAN INI
                           . "   - Analisis [TEKS CP ASLI PEMERINTAH] yang disediakan dan sesuaikan dengan [TUJUAN PEMBELAJARAN (TP)].\n"
                           . "   - Ambil atau rangkum HANYA potongan kalimat inti dari CP asli tersebut yang paling relevan dan mendasari TP pada modul ajar ini.\n"
                           . "   - Jangan mengarang kalimat kompetensi baru, fokuslah memotong/merangkum kalimat dari dokumen CP utama secara cerdas agar pas dengan batasan ruang lingkup materi pada pertemuan ini.\n\n"
                           // 🌟 TAMBAHAN 6 KATEGORI BARU 🌟
                           . "4. Jika meminta key 'lintas_disiplin':\n"
                           . "   - JANGAN hanya menyebutkan nama mata pelajaran lain secara singkat.\n"
                           . "   - Tuliskan penjelasan deskriptif mengenai bagaimana materi pembelajaran utama ini beririsan secara fungsional dengan mata pelajaran tingkat SMP lainnya (misalnya dikaitkan dengan konsep IPA Terpadu, IPS Terpadu, Informatika, Matematika, Prakarya, Bahasa, atau Seni Budaya SMP) untuk membangun pemahaman yang utuh dan holistik bagi murid.\n\n"
                           . "5. Jika meminta key 'topik_pembelajaran':\n"
                           . "   - JANGAN hanya menuliskan ulang judul bab atau ringkasan materi materi utama.\n"
                           . "   - Tuliskan deskripsi mendalam yang menjelaskan esensi dari tema besar/topik yang dipelajari pada pertemuan ini, batasan ruang lingkup bahasannya, serta relevansi mengapa topik ini sangat krusial dan bermakna untuk dipelajari dalam kehidupan nyata murid.\n\n"
                           . "6. Jika meminta key 'praktik_pedagogis':\n"
                           . "   - Tuliskan Model/Strategi/Metode pembelajaran yang dipilih (seperti Pembelajaran Berbasis Masalah/PBL, Berbasis Proyek/PjBL, Inkuiri, atau Kontekstual).\n"
                           . "   - Berikan penjelasan singkat bagaimana metode tersebut dijalankan secara taktis di kelas untuk memicu keterlibatan aktif murid (student-centered).\n\n"
                           . "7. Jika meminta key 'kemitraan_pembelajaran':\n"
                           . "   - Tuliskan rencana kolaborasi nyata baik internal sekolah (antar guru mapel, antar kelas) maupun eksternal (orang tua, komunitas, tokoh masyarakat, dunia usaha/industri, atau praktisi profesional).\n"
                           . "   - Jelaskan bagaimana peran kemitraan tersebut dilibatkan dalam mendukung proses atau pembuktian hasil belajar murid.\n\n"
                           . "8. Jika meminta key 'lingkungan_pembelajaran':\n"
                           . "   - Deskripsikan pengaturan lingkungan belajar yang aman, nyaman, dan saling memuliakan sesuai spirit KBC.\n"
                           . "   - Paparkan bentuk budaya belajar kelas (seperti memberikan ruang aman untuk berpendapat), pengelolaan ruang fisik kelas, dan/atau pemanfaatan ruang virtual secara kondusif.\n\n"
                           . "9. Jika meminta key 'pemanfaatan_digital':\n"
                           . "   - Tuliskan perangkat atau platform digital secara konkret (seperti video interaktif, LMS, perpustakaan digital, forum diskusi daring, atau aplikasi penilaian/kuiz).\n"
                           . "   - Jelaskan pemanfaatannya demi menciptakan interaksi belajar yang lebih kolaboratif, interaktif, dan kontekstual.\n\n"
                           . "10. Jika meminta key 'kegiatan_awal':\n"
                           . "    - Tuliskan langkah-langkah pembuka secara berurutan menggunakan angka 1 sampai 5.\n"
                           . "    - Alur wajib:\n"
                           . "      1. Guru membuka pelajaran dengan salam, menyapa murid, dan menanyakan kabar.\n"
                           . "      2. Guru mengajak murid berdoa bersama sebelum belajar.\n"
                           . "      3. Guru mengecek kehadiran murid.\n"
                           . "      4. Guru melakukan apersepsi (mengaitkan materi sebelumnya atau pengalaman sehari-hari) dan memberikan contoh kalimat PERTANYAAN PEMANTIK yang spesifik sesuai materi.\n"
                           . "      5. Guru menyampaikan Tujuan Pembelajaran yang akan dicapai.\n\n"
                           . "11. Jika meminta key 'kegiatan_inti_memahami':\n"
                           . "    - Tuliskan langkah awal (eksplorasi). Penomoran WAJIB melanjutkan dari kegiatan awal, yaitu menggunakan angka 6, 7, dan 8.\n"
                           . "    - JANGAN gunakan kata 'Fase'. Langsung tuliskan aktivitasnya.\n"
                           . "    - Alur wajib:\n"
                           . "      6. Guru menyajikan masalah/gambar/video terkait topik utama.\n"
                           . "      7. Murid mengamati dan memahami sajian masalah tersebut secara saksama.\n"
                           . "      8. Guru membagi murid ke dalam beberapa kelompok heterogen dan membagikan Lembar Kerja Peserta Didik (LKPD).\n\n"
                           . "12. Jika meminta key 'kegiatan_inti_mengaplikasikan':\n"
                           . "    - Tuliskan langkah praktik dan kolaborasi. Penomoran WAJIB melanjutkan langkah sebelumnya, yaitu menggunakan angka 9, 10, dan 11.\n"
                           . "    - JANGAN gunakan kata 'Fase'.\n"
                           . "    - Alur wajib:\n"
                           . "      9. Murid berdiskusi dan berkolaborasi di dalam kelompoknya untuk memecahkan masalah yang ada pada LKPD.\n"
                           . "      10. Murid menyusun hasil diskusi kelompok menjadi sebuah karya atau laporan jawaban.\n"
                           . "      11. Perwakilan kelompok mempresentasikan hasil karya atau jawaban diskusi mereka di depan kelas.\n\n"
                           . "13. Jika meminta key 'kegiatan_inti_merefleksi':\n"
                           . "    - Tuliskan langkah pemaknaan dan penguatan. Penomoran WAJIB melanjutkan langkah sebelumnya, yaitu menggunakan angka 12, 13, dan 14.\n"
                           . "    - JANGAN gunakan kata 'Fase'.\n"
                           . "    - Alur wajib:\n"
                           . "      12. Guru memberikan apresiasi berupa tepuk tangan/pujian dan umpan balik terhadap hasil presentasi murid.\n"
                           . "      13. Guru memberikan penguatan materi, mengklarifikasi, atau meluruskan miskonsepsi yang terjadi selama diskusi.\n"
                           . "      14. Murid bersama guru menyimpulkan solusi atau inti sari dari materi pemecahan masalah yang telah dipelajari.\n\n"
                           . "14. Jika meminta key 'kegiatan_penutup':\n"
                           . "    - Tuliskan langkah penutup seluruh proses pembelajaran hari ini. Penomoran WAJIB melanjutkan langkah sebelumnya, yaitu menggunakan angka 15, 16, dan 17.\n"
                           . "    - Alur wajib:\n"
                           . "      15. Guru bersama murid melakukan refleksi singkat mengenai manfaat pembelajaran yang baru saja selesai serta menanyakan perasaan mereka (internalisasi nilai kebahagiaan/Joy).\n"
                           . "      16. Guru memberikan informasi atau kisi-kisi singkat mengenai rencana kegiatan atau materi yang akan dipelajari pada pertemuan berikutnya.\n"
                           . "      17. Guru bersama murid menutup rangkaian pembelajaran dengan doa bersama dan mengucapkan salam penutup.\n\n"
                           . "15. Jika meminta key 'asesmen_awal':\n"
                           . "    - Tuliskan rencana asesmen awal pembelajaran (diagnostik) kognitif maupun non-kognitif.\n"
                           . "    - JANGAN HANYA menyebutkan jenis asesmennya. JELASKAN secara singkat BAGAIMANA asesmen ini dilakukan di kelas (misal: melalui permainan interaktif, tanya jawab klasikal, atau kuisioner singkat).\n"
                           . "    - Sebutkan Teknik, Instrumennya, dan WAJIB berikan 1-2 contoh pertanyaan pemantik asesmen awal yang relevan dengan materi hari ini.\n\n"
                           . "16. Jika meminta key 'asesmen_proses':\n"
                           . "    - Tuliskan rencana asesmen formatif. WAJIB dibagi menjadi 3 aspek dengan format huruf (a, b, c). JANGAN HANYA menyebutkan tekniknya, tapi berikan PENJELASAN:\n"
                           . "      a. Penilaian Sikap: Sebutkan tekniknya (observasi) dan JELASKAN indikator spesifik yang diamati (misal: kekhusyukan saat berdoa, tingkat kepedulian, atau kolaborasi saat diskusi).\n"
                           . "      b. Penilaian Pengetahuan: Sebutkan tekniknya (misal: LKPD/Tanya jawab) dan JELASKAN bagaimana proses penilaian dilakukan selama aktivitas kelas berlangsung.\n"
                           . "      c. Penilaian Keterampilan: Sebutkan tekniknya (misal: presentasi/unjuk kerja) dan JELASKAN rubrik atau aspek apa saja yang dinilai dari keterampilan murid tersebut.\n\n"
                           . "17. Jika meminta key 'asesmen_akhir':\n"
                           . "    - Tuliskan rencana asesmen sumatif di akhir proses pembelajaran.\n"
                           . "    - JANGAN HANYA menyebutkan bentuk tesnya. JELASKAN mekanisme pelaksanaannya dan indikator keberhasilannya secara singkat.\n"
                           . "    - Sebutkan Teknik (misal: Tes tertulis, Proyek akhir, atau Portofolio), Instrumennya (Soal PG/Esai/Rubrik), dan jelaskan fokus materi yang ditekankan dalam evaluasi ini.\n\n"
                           . "18. Jika meminta key 'lampiran_materi':\n"
                           . "    - Tuliskan rekomendasi dan penjelasan ringkas mengenai isi bahan ajar utama yang harus dipelajari murid hari ini (misal: ringkasan teori, infografis, atau poin presentasi slides).\n"
                           . "    - Di akhir teks, wajib berikan ruang kosong profesional bagi guru untuk menaruh link, contoh format: '🔗 Tautan Dokumen Materi: [Masukkan Link File Materi di Sini]'.\n\n"
                           . "19. Jika meminta key 'lampiran_lkm':\n"
                           . "    - Tuliskan rekomendasi deskripsi atau kisi-kisi Lembar Kerja Murid / LKPD yang digunakan kelompok saat kegiatan diskusi inti agar sesuai topik materi hari ini.\n"
                           . "    - Di akhir teks, wajib berikan ruang kosong untuk link, contoh format: '🔗 Tautan LKPD Kelompok: [Masukkan Link File LKM/LKPD di Sini]'.\n\n"
                           . "20. Jika meminta key 'lampiran_rubrik':\n"
                           . "    - Tuliskan rekomendasi kriteria pedoman penilaian atau rubrik skor (misal: rubrik penilaian sikap gotong royong, rubrik penilaian unjuk kerja presentasi, atau rubrik penilaian pengetahuan).\n"
                           . "    - Di akhir teks, wajib berikan ruang kosong untuk link, contoh format: '🔗 Tautan Rubrik Penilaian: [Masukkan Link Rubrik di Sini]'.\n\n"
                           . "21. Jika meminta key 'sumber_belajar':\n"
                           . "    - Tuliskan rekomendasi referensi belajar digital tambahan yang relevan dengan topik (misal: artikel web terpercaya, buku paket cetak halaman tertentu, atau video edukasi YouTube).\n"
                           . "    - Di akhir teks, wajib berikan ruang kosong untuk link, contoh format: '🔗 Tautan Video / Sumber Belajar Tambahan: [Masukkan Link Referensi di Sini]'.\n\n"
                           . "22. Jika meminta key 'contoh_produk':\n"
                           . "    - Tuliskan penjelasan atau contoh konkrit hasil produk/karya nyata murid yang ideal dan diharapkan setelah pembelajaran hari ini selesai (misal: bentuk resume, poster, mind mapping, atau laporan praktikum).\n"
                           . "    - Di akhir teks, wajib berikan ruang kosong untuk link, contoh format: '🔗 Tautan Contoh Hasil Karya Terbaik Murid: [Masukkan Link Portofolio Produk di Sini]'.\n\n"                         
                           . "PANDUAN PEDAGOGIS KELAS:\n"
                           . "- KEGIATAN AWAL: Apersepsi kreatif, kesiapan emosional (Mind), pertanyaan pemantik.\n"
                           . "- KEGIATAN INTI: Berorientasi pada murid (student-centered), aktif, mendalam (Meaning).\n"
                           . "- KEGIATAN PENUTUP: Refleksi perasaan (Joy), penarikan kesimpulan oleh murid, internalisasi nilai.\n\n"
                           . "ATURAN FORMAT JSON (MUTLAK):\n"
                           . "- Tipe data value wajib STRING. Dilarang membuat Array/Object bersarang di dalam value.\n"
                           . "- Gunakan literal '\\n' untuk baris baru. Dilarang enter asli.\n"
                           . "- Gunakan kutip tunggal (') untuk teks di dalam nilai JSON, dilarang menggunakan kutip ganda (\").\n\n"
                           . "HANYA HASILKAN KEY JSON BERIKUT:\n"
                           . $jsonStructureString;

        $userPrompt = "Lengkapi rancangan Modul Ajar dengan data spesifik berikut:\n"
                    . "- Mata Pelajaran: $mapel\n"
                    . "- Target Kelas/Rombel: $rombel\n"
                    . "- Materi Pembelajaran Utama: $materi\n"
                    . "- Dimensi Profil Lulusan (DPL): " . (!empty($dpl) ? $dpl : 'Sesuaikan materi') . "\n"
                    . "- Nilai Panca Cinta (KBC) Terpilih: " . (!empty($pancaCinta) ? $pancaCinta : 'Sesuaikan materi') . "\n\n"
                    . "[DOKUMEN PANDUAN PANCA CINTA KBC - REFERENSI UTAMA]\n" . $panduanKbcMateri . "\n\n"
                    . "[TUJUAN PEMBELAJARAN (TP)]\n$tp\n\n"
                    . "[TEKS CP ASLI PEMERINTAH]\n$cpAsli\n\n";
        
        if (!empty($instruksi)) {
            $userPrompt .= "[INSTRUKSI KHUSUS GURU]\n$instruksi\n";
        }

        // 6. Konfigurasi Paket Kiriman API
        $data = [
            'model' => 'llama-3.3-70b-versatile', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.65, 
            'max_tokens' => 6000,
            'response_format' => ['type' => 'json_object'] 
        ];

        $headers = [ 'Authorization: Bearer ' . $apiKey, 'Content-Type: application/json' ];

        // 7. Eksekusi cURL
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
            return $this->response->setJSON(['status' => 'error', 'message' => 'Kuota SiKuMi habis (Limit API). Silakan tunggu beberapa saat.']);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $aiText = $responseData['choices'][0]['message']['content'];
                
                if (preg_match('/\{[\s\S]*\}/', $aiText, $matches)) {
                    $aiText = $matches[0];
                }
                
                $jsonOutput = json_decode(trim($aiText), true);

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

    // ==============================================================
    // FUNGSI CETAK (PRINT) MODUL AJAR (FULL DINAMIS & LAYOUT TABEL)
    // ==============================================================
    public function printModul($modulId)
    {
        $db = \Config\Database::connect();

        // 1. Ambil Data Modul Utama
        $modulData = $db->table('kurikulum_modul_ajar')->where('id', $modulId)->get()->getRowArray();
        if (!$modulData) {
            return redirect()->back()->with('error', 'Data modul tidak ditemukan.');
        }

        // 2. Ambil Nama Rombel
        $rombel = $db->tableExists('class_rombel') ? $db->table('class_rombel')->where('id', $modulData['rombel_id'])->get()->getRowArray() : null;
        $namaRombel = $rombel ? $rombel['rombel_name'] : '-';

        // 3. Menerjemahkan Kode Mapel (S_x atau C_x)
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

        // 4. Ambil Teks Tujuan Pembelajaran, DPL, dan Panca Cinta
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

        // 5. Ambil Pengaturan Global
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $settings = $db->tableExists('settings') ? $db->table('settings')->get()->getResultArray() : [];
        $set = []; foreach($settings as $s) { $set[$s['key']] = $s['value']; }

        $namaMadrasah = $set['nama_madrasah'] ?? $set['nama_sekolah'] ?? 'MTs MIFTAHUL HUDA';
        $kepalaNama = $set['kaldik_kepala_nama'] ?? $set['kepala_nama'] ?? 'Yana Purnama, S.Pd.';
        $kepalaNpk = $set['kaldik_kepala_npk'] ?? $set['kepala_npk'] ?? '-';

        // 6. Tangkap Identitas Guru (Diperkuat agar tidak gagal load)
        $userId = session()->get('user_id') ?? session()->get('id') ?? (function_exists('user_id') ? user_id() : 0);
        $namaGuruCetak = session()->get('nama') ?? session()->get('full_name') ?? 'Guru Pengampu'; 
        $guruNpk = session()->get('npk') ?? session()->get('nim') ?? session()->get('nuptk') ?? '-';

        if ($db->tableExists('teacher_profiles')) {
            $guru = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guru) {
                $namaGuruCetak = !empty($guru['nama_guru']) ? $guru['nama_guru'] : (!empty($guru['nama']) ? $guru['nama'] : (!empty($guru['full_name']) ? $guru['full_name'] : $namaGuruCetak));
                $guruNpk = !empty($guru['npk']) ? $guru['npk'] : (!empty($guru['nip']) ? $guru['nip'] : (!empty($guru['nuptk']) ? $guru['nuptk'] : $guruNpk));
            }
        }

        // Membersihkan dari kata "Nama:" atau "NIM:" yang dobel
        $namaGuruCetak = trim(str_ireplace(['Nama :', 'Nama: ', 'Nama '], '', $namaGuruCetak));
        $guruNpk = trim(str_ireplace(['NIM :', 'NIM: ', 'NIM ', 'NPK :', 'NPK: ', 'NPK '], '', $guruNpk));

        // 7. Format Titi Mangsa (Mencegah double tanggal)
        $titimangsaSetting = trim($set['kaldik_titi_mangsa'] ?? $set['titimangsa_print'] ?? 'Bandung');
        if (strpos($titimangsaSetting, ',') !== false) {
            // Jika di pengaturan sudah ada koma (misal: "Bandung, 1 Juli 2026"), pakai yang itu saja
            $titiMangsa = $titimangsaSetting;
        } else {
            // Jika hanya nama kota, tambahkan tanggal hari ini
            $bulanIndo = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $tanggalCetak = date('j') . ' ' . $bulanIndo[(int)date('m')] . ' ' . date('Y');
            $titiMangsa = $titimangsaSetting . ', ' . $tanggalCetak;
        }

        // 8. Susun Variabel untuk View
        $data = [
            'modulId' => $modulId,
            'modulData' => $modulData,
            'namaMadrasah' => $namaMadrasah,
            'tahunAktif' => $tahunAktif,
            'namaMapelAktif' => $namaMapelAktif, 
            'namaRombel' => $namaRombel,
            'tujuanPembelajaranTeks' => $tujuanPembelajaranTeks,
            'dplArray' => $dplArray,                 
            'pancaCintaArray' => $pancaCintaArray,   
            'kepalaNama' => $kepalaNama,
            'kepalaNpk' => trim($kepalaNpk),
            'titiMangsa' => $titiMangsa,
            'userId' => $userId,
            'namaGuruCetak' => trim($namaGuruCetak),
            'guruNpk' => trim($guruNpk)
        ];

        return view('guru/modul_ajar_print', $data);
    }

}
