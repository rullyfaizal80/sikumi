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
            
            // A. Ambil Mapel Reguler Guru
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
                    $daftarMapel[] = ['id' => $m['id'], 'subject_name' => $m['subject_name']];
                }
            }        
        }

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : 1);

        // ==============================================================
        // 4. LOAD DATA ANALISIS CP (DIJAMIN MUNCUL)
        // ==============================================================
        $dataAtp = [];
        $tabelAnalisis = 'analisis_cp_data'; 
        
        if ($db->tableExists($tabelAnalisis)) {
             $analisisFields = $db->getFieldNames($tabelAnalisis);
             
             $builder = $db->table($tabelAnalisis)->where('mapel_id', $selectedMapelId);
             
             // Jaring Pengaman Cerdas: Cari dari Tingkat, atau Master Kelas, atau Rombel ID
             $builder->groupStart();
             if (in_array('tingkat', $analisisFields)) { $builder->orWhere('tingkat', $tingkatKelas); }
             if (in_array('kelas_id', $analisisFields)) {
                 $builder->orWhere('kelas_id', $masterClassId);
                 $builder->orWhere('kelas_id', $selectedRombelId);
             }
             $builder->groupEnd();

             $dataAtp = $builder->get()->getResultArray();
        }

        // ==============================================================
        // 5. LOAD TANGGAL JADWAL
        // ==============================================================
        $listTanggal = [];
        if ($db->tableExists('class_schedules')) {
            $scheduleFields = $db->getFieldNames('class_schedules');
            $kolomTgl = in_array('tanggal_pembelajaran', $scheduleFields) ? 'tanggal_pembelajaran' : (in_array('tanggal', $scheduleFields) ? 'tanggal' : null);
            
            $isCombined = (strpos($selectedMapelId, 'C') === 0);
            $searchMapelId = $isCombined ? str_replace('C', '', $selectedMapelId) : $selectedMapelId;

            if ($kolomTgl) {
                $queryJadwal = $db->table('class_schedules')
                                  ->select($kolomTgl)
                                  ->where('rombel_id', $selectedRombelId); 
                
                if ($isCombined && in_array('combined_subject_id', $scheduleFields)) {
                    $queryJadwal->where('combined_subject_id', $searchMapelId);
                } else {
                    $queryJadwal->where($kolomSubjectId ?? 'subject_id', $searchMapelId);
                }

                if ($jadwalAktif) {
                    $queryJadwal->where('version_id', $jadwalAktif['id']);
                }
                
                $hasilJadwal = $queryJadwal->orderBy($kolomTgl, 'ASC')->get()->getResultArray();
                foreach ($hasilJadwal as $j) {
                    if(!empty($j[$kolomTgl])) $listTanggal[] = $j[$kolomTgl];
                }
            }
        }

        foreach ($dataAtp as $idx => &$row) {
            $row['tanggal'] = $listTanggal[$idx] ?? 'Tentukan Jadwal';
        }

        // ==============================================================
        // 6. RENDER KE VIEW
        // ==============================================================
        $data = [
            'tahunAktif'    => $tahunAktif,
            'daftarRombel'  => $daftarRombel,
            'daftarMapel'   => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'tingkatKelas'     => $tingkatKelas,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtp'          => $dataAtp,
            
            'namaMadrasah' => $namaMadrasah['value'] ?? 'MIMHa',
            'titiMangsa'   => $titiMangsa['value'] ?? date('d F Y'),
            'kepalaNama'   => $kepalaSekolah['value'] ?? '-',
            'namaGuruCetak'=> $namaGuruCetak,
            'listProfilLulusan' => ['DPL1'=>'Keimanan','DPL2'=>'Kewargaan','DPL3'=>'Penalaran Kritis','DPL4'=>'Kreativitas','DPL5'=>'Kolaborasi','DPL6'=>'Kemandirian','DPL7'=>'Kesehatan','DPL8'=>'Komunikasi'],
            'listPancaCinta'    => ['P1'=>'Cinta Allah/Rasul','P2'=>'Cinta Ilmu','P3'=>'Cinta Diri/Sesama','P4'=>'Cinta Lingkungan','P5'=>'Cinta Tanah Air']
        ];

        return view('guru/atp_manage', $data);
    }
}