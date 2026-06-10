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
                          ->select('d.id, d.tujuan_pembelajaran, a.urutan as no_tp, k.indikator, k.skor_sangat_baik, k.skor_baik, k.skor_cukup, k.skor_perlu_bimbingan')
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
}
