<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class KktpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // ==============================================================
        // 1. INFO HEADER (BACKEND ONLY)
        // ==============================================================
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);
        
        $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray() : null;
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
                
                $masterClassId = $r['master_class_id'] ?? $r['id'];
                break;
            }
        }

        $rombelTingkatSama = [];
        foreach ($daftarRombel as $r) {
            $rMaster = $r['master_class_id'] ?? $r['id'];
            if ($rMaster == $masterClassId && $r['id'] != $selectedRombelId) {
                $rombelTingkatSama[] = $r;
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
    
            // Mapel Gabungan Guru
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
            
            // Mapel Reguler Guru
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

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : '');

        // ==============================================================
        // 4. LOAD DATA CP & KKTP (STRICT BERDASARKAN ROMBEL & ATP)
        // ==============================================================
        $dataKktp = [];
        if (!empty($selectedRombelId) && !empty($selectedMapelId) && $db->tableExists('kurikulum_atp')) {
            $cleanMapelId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            $builder = $db->table('kurikulum_atp a')
                          // Ambil urutan dari tabel ATP (a.urutan), indikator dari KKTP (k.indikator)
                          ->select('d.id, d.tujuan_pembelajaran, d.kktp as acuan_kktp, a.urutan as no_tp, k.indikator, k.skor_sangat_baik, k.skor_baik, k.skor_cukup, k.skor_perlu_bimbingan')
                          ->join('kurikulum_cp_details d', 'd.id = a.cp_detail_id')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id')
                          // Join KKTP menggunakan rombel yang spesifik
                          ->join('kurikulum_kktp k', "k.cp_detail_id = d.id AND k.rombel_id = $selectedRombelId", 'left')
                          // 🌟 FILTER KETAT: Hanya ambil ATP milik Rombel yang dipilih
                          ->groupStart()
                              ->where('a.rombel_id', $selectedRombelId)
                              ->orWhere('a.rombel_id IS NULL') // Antisipasi jika guru mengatur ATP paralel 1 tingkat
                          ->groupEnd()
                          ->where('h.master_class_id', $masterClassId);

            // Filter Mapel Gabungan / Reguler
            $builder->groupStart()
                        ->where('h.mapel_id', $selectedMapelId)
                        ->orWhere('h.mapel_id', $cleanMapelId)
                    ->groupEnd();
            
            if ($tahunAktif) {
                $builder->where('h.academic_year_id', $tahunAktif['id']);
            }

            // Urutkan berdasarkan urutan nomor TP di ATP
            $dataKktp = $builder->orderBy('a.urutan', 'ASC')->get()->getResultArray();
        }

        $data = [
            'tahunAktif' => $tahunAktif,
            'daftarRombel' => $daftarRombel,
            'rombelTingkatSama' => $rombelTingkatSama, // 🌟 TAMBAHAN
            'daftarMapel' => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId' => $selectedMapelId,
            'namaRombelAktif' => $namaRombelAktif,
            'tingkatKelas' => $tingkatKelas,
            'dataKktp' => $dataKktp
        ];

        return view('guru/kktp_manage', $data);
    }

    public function simpan()
    {
        $db = \Config\Database::connect();
        $request = $this->request->getPost();
        $dataRows = json_decode($request['data_kktp'], true);

        if (empty($dataRows)) return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data untuk disimpan.']);

        $db->transStart();
        foreach ($dataRows as $row) {
            $db->table('kurikulum_kktp')->replace([
                'cp_detail_id'         => $row['cp_id'],
                'rombel_id'            => $request['rombel_id'],
                'indikator'            => $row['indikator'], // 🌟 KEMBALIKAN PENYIMPANAN INDIKATOR
                'skor_sangat_baik'     => $row['sangat_baik'],
                'skor_baik'            => $row['baik'],
                'skor_cukup'           => $row['cukup'],
                'skor_perlu_bimbingan' => $row['perlu_bimbingan'],
            ]);
        }
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
        }
        return $this->response->setJSON(['status' => 'success', 'message' => 'Rubrik KKTP berhasil disimpan!']);
    }

    public function reset()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

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
        $db->table('kurikulum_kktp')->where('rombel_id', $rombelId)->whereIn('cp_detail_id', $cpIds)->delete();
        $db->transComplete();

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data KKTP untuk kelas ini berhasil direset!']);
    }

    // ==============================================================
    // FUNGSI UNTUK COPY DATA KKTP DARI ROMBEL LAIN (TINGKAT SAMA)
    // ==============================================================
    public function copyKktp()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        if (!$request->isAJAX()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses tidak sah.']);
        }

        $fromRombelId = $request->getPost('from_rombel_id');
        $toRombelId   = $request->getPost('to_rombel_id');
        $cpIdsJson    = $request->getPost('cp_ids');
        
        if (empty($fromRombelId) || empty($toRombelId) || empty($cpIdsJson)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        $cpIds = json_decode($cpIdsJson, true);

        if (empty($cpIds)) {
             return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada target materi yang akan disalin.']);
        }

        $db->transStart();

        // 1. Ambil data rubrik KKTP dari rombel sumber
        $sourceData = $db->table('kurikulum_kktp')
                         ->where('rombel_id', $fromRombelId)
                         ->whereIn('cp_detail_id', $cpIds)
                         ->get()->getResultArray();

        if (empty($sourceData)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Rombel sumber belum memiliki data Rubrik KKTP untuk mapel ini.']);
        }

        // 2. Hapus data KKTP yang sudah ada di rombel tujuan agar tidak numpuk/double
        $db->table('kurikulum_kktp')
           ->where('rombel_id', $toRombelId)
           ->whereIn('cp_detail_id', $cpIds)
           ->delete();

        // 3. Masukkan data dari rombel sumber ke rombel tujuan
        $insertData = [];
        foreach ($sourceData as $sd) {
            $insertData[] = [
                'cp_detail_id'         => $sd['cp_detail_id'],
                'rombel_id'            => $toRombelId,
                'indikator'            => $sd['indikator'],
                'skor_sangat_baik'     => $sd['skor_sangat_baik'],
                'skor_baik'            => $sd['skor_baik'],
                'skor_cukup'           => $sd['skor_cukup'],
                'skor_perlu_bimbingan' => $sd['skor_perlu_bimbingan'],
                'created_at'           => date('Y-m-d H:i:s')
            ];
        }

        if (!empty($insertData)) {
            $db->table('kurikulum_kktp')->insertBatch($insertData);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyalin data dari database.']);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Rubrik KKTP berhasil disalin!']);
    }

    // ==============================================================
    // 🌟 PERBAIKAN TOTAL: FUNGSI UNTUK CETAK/PRINT RUBRIK KKTP
    // ==============================================================
    public function printKktp()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // 1. INFO HEADER (SINKRONISASI TOTAL DATABASE)
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);
        
        $namaMadrasah  = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray() : null;
        if (!$namaMadrasah) {
            $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        }
        $titiMangsa    = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $npkKepala     = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray() : null;

        // Tarik NPK Guru (SINKRON DENGAN INDEX)
        $guruNpk = '.....................................';
        if ($db->tableExists('teacher_profiles')) {
            $guruProfile = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guruProfile) {
                $guruNpk = $guruProfile['nip'] ?? $guruProfile['npk'] ?? '.....................................';
            }
        }

        // Tarik Nama Guru dari teacher_profiles / users (SINKRON DENGAN INDEX)
        $namaGuruCetak = '.....................................';
        if ($db->tableExists('teacher_profiles')) {
            $guru = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guru) {
                $namaGuruCetak = $guru['nama_guru'] ?? $guru['nama'] ?? $guru['full_name'] ?? 'Guru Pengampu';
            }
        }
        if (($namaGuruCetak == '.....................................' || $namaGuruCetak == 'Guru Pengampu') && $db->tableExists('users')) {
            $guruData = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if ($guruData) {
                $namaGuruCetak = $guruData['fullname'] ?? $guruData['name'] ?? $guruData['username'] ?? 'Nama Guru Belum Diatur';
            }
        }

        // 2. PARAMETER FILTER & AMBIL NAMA ROMBEL
        $selectedRombelId = $request->getGet('rombel_id');
        $selectedMapelId  = $request->getGet('mapel_id');

        $namaRombelAktif = '-';
        $namaMapelAktif  = '-';
        $faseAktif       = '-';
        $kelasAktif      = '-';
        $masterClassId   = null;

        // --- AMBIL DATA ROMBEL & TINGKAT ---
        if (!empty($selectedRombelId)) {
            $r = $db->table('class_rombel cr')
                    ->select('cr.rombel_name, mc.class_name, cr.master_class_id')
                    ->join('master_classes mc', 'mc.id = cr.master_class_id', 'left')
                    ->where('cr.id', $selectedRombelId)
                    ->get()->getRowArray();

            if ($r) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                $kelasAktif      = $className;
                $masterClassId   = $r['master_class_id'];
                
                $angkaTingkat = preg_replace('/[^0-9]/', '', $className); 
                if (!empty($angkaTingkat)) {
                    $faseAktif = ($angkaTingkat >= 7 && $angkaTingkat <= 9) ? 'D' : (($angkaTingkat == 10) ? 'E' : 'F');
                } else {
                    $upperClass = strtoupper($className);
                    if (strpos($upperClass, 'VII') !== false || strpos($upperClass, 'VIII') !== false || strpos($upperClass, 'IX') !== false) { $faseAktif = 'D'; }
                    elseif (strpos($upperClass, 'XI') !== false || strpos($upperClass, 'XII') !== false) { $faseAktif = 'F'; }
                    elseif (strpos($upperClass, 'X') !== false) { $faseAktif = 'E'; }
                }
            }
        }

        // --- 🌟 AMBIL DATA NAMA MATA PELAJARAN (FIX BLANK) ---
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
                    
                    // Dicari menggunakan ID bersih maupun ID dengan awalan prefix
                    $m = $db->table($tabelMapel)->where('id', $cleanMapelId)->orWhere('id', $selectedMapelId)->get()->getRowArray();
                    if ($m) $namaMapelAktif = $m[$kolomNamaMapel] ?? '-';
                }
            }
        }

        // 3. LOAD DATA KKTP SINKRON DENGAN TABEL MANAGE & FILTER TINGKAT
        $kktpData = [];
        if (!empty($selectedRombelId) && !empty($selectedMapelId)) {
            $cleanMapelId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            $tabelCpHeader = $db->tableExists('kurikulum_cp_headers_1') ? 'kurikulum_cp_headers_1' : 'kurikulum_cp_headers';
            $tabelCpDetail = $db->tableExists('kurikulum_cp_details_1') ? 'kurikulum_cp_details_1' : 'kurikulum_cp_details';

            $builder = $db->table($tabelCpDetail . ' d')
                ->join($tabelCpHeader . ' h', 'd.header_id = h.id')
                ->join('kurikulum_atp a', 'a.cp_detail_id = d.id AND a.rombel_id = ' . $db->escape($selectedRombelId), 'left')
                ->join('kurikulum_kktp k', 'k.cp_detail_id = d.id AND k.rombel_id = ' . $db->escape($selectedRombelId), 'left')
                ->groupStart()
                    ->where('h.mapel_id', $selectedMapelId)
                    ->orWhere('h.mapel_id', $cleanMapelId)
                ->groupEnd();

            if (!empty($masterClassId)) {
                $builder->where('h.master_class_id', $masterClassId);
            }

            if ($tahunAktif) {
                $builder->where('h.academic_year_id', $tahunAktif['id']);
            }

            $kktpData = $builder->select('d.id, d.tujuan_pembelajaran, a.urutan as no_tp, k.indikator, k.skor_sangat_baik, k.skor_baik, k.skor_cukup, k.skor_perlu_bimbingan')
                ->orderBy('COALESCE(a.urutan, 999)', 'ASC', false) 
                ->orderBy('d.id', 'ASC')
                ->get()->getResultArray();
        }

        // 4. MAPPING VARIABEL GANDA (Mengunci kompatibilitas variabel view)
        $data = [
            'tahunAktif'       => $tahunAktif,
            'namaMadrasah'     => $namaMadrasah ? $namaMadrasah['value'] : 'MTs MIFTAHUL HUDA (MIMHa)',
            'titiMangsa'       => $titiMangsa ? $titiMangsa['value'] : 'Bandung, ' . date('d F Y'),
            
            // Mengirim 2 versi key kepala sekolah agar aman
            'kepalaNama'       => $kepalaSekolah ? $kepalaSekolah['value'] : 'Rully Faizal, S.T.',
            'kepalaSekolah'    => $kepalaSekolah ? $kepalaSekolah['value'] : 'Rully Faizal, S.T.',
            'kepalaNpk'        => $npkKepala ? $npkKepala['value'] : '-',
            
            'namaGuruCetak'    => $namaGuruCetak,
            'guruNpk'          => $guruNpk,
            'userId'           => $userId,
            'namaRombelAktif'  => $namaRombelAktif,
            
            // 🌟 DOUBLE-KEY MAPEL: Menjamin nama mapel ter-render di view manapun
            'namaMapelAktif'   => $namaMapelAktif,
            'selectedMapelName'=> $namaMapelAktif, 
            
            'faseAktif'        => $faseAktif,
            'kelasAktif'       => $kelasAktif,
            'kktpData'         => $kktpData
        ];

        return view('guru/print_kktp', $data);
    }

}
