<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AtpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // ==============================================================
        // 1. AMBIL PENGATURAN MADRASAH & PROFIL GURU
        // ==============================================================
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        
        $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        $titiMangsa = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;

        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $namaGuruCetak = '.....................................';

        if ($db->tableExists('teacher_profiles')) {
            $guruProfile = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            if ($guruProfile) {
                $namaGuruCetak = $guruProfile['nama_guru'] ?? $guruProfile['nama'] ?? $guruProfile['full_name'] ?? $namaGuruCetak;
            }
        }
        if ($namaGuruCetak == '.....................................' && $db->tableExists('users')) {
            $userData = $db->table('users')->where('id', $userId)->get()->getRowArray();
            if ($userData) {
                $namaGuruCetak = $userData['fullname'] ?? $userData['name'] ?? $userData['username'] ?? $namaGuruCetak;
            }
        }

        // ==============================================================
        // 2. DINAMISASI DAFTAR ROMBEL (Sesuai ScheduleController)
        // ==============================================================
        $daftarRombel = [];
        if ($tahunAktif && $db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel cr')
                               ->select('cr.id, cr.rombel_name, mc.class_name, mc.level_type')
                               ->join('master_classes mc', 'mc.id = cr.master_class_id')
                               ->where('cr.academic_year_id', $tahunAktif['id'])
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('cr.rombel_name', 'ASC')
                               ->get()->getResultArray();
        } elseif ($db->tableExists('master_classes')) {
             $daftarRombel = $db->table('master_classes')->get()->getResultArray();
        }

        $selectedRombelId = $this->request->getGet('rombel_id') ?? (!empty($daftarRombel) ? $daftarRombel[0]['id'] : 1);

        $tingkatKelas = 7;
        $namaRombelAktif = '-';
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                
                // Cari angka tingkatan dari level_type, atau ekstrak otomatis dari nama kelas
                $tingkatKelas = $r['level_type'] ?? (preg_replace('/[^0-9]/', '', $className) ?: 7);
                break;
            }
        }

        // ==============================================================
        // 3. DINAMISASI MAPEL GURU (Sesuai AnalysisController)
        // ==============================================================
        $daftarMapel = [];
        
        if ($db->tableExists('class_schedules')) {
            // Auto-deteksi struktur tabel jadwal dan master mapel
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuruDiJadwal = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
    
            $tabelMapel = 'master_subjects';
            if (!$db->tableExists($tabelMapel)) {
                $tabelMapel = $db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran';
            }
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = 'subject_name';
            foreach (['nama_mapel', 'name', 'mapel'] as $f) {
                if (in_array($f, $mapelFields)) { $kolomNamaMapel = $f; break; }
            }
    
            // Cari jadwal pelajaran yang aktif
            $jadwalAktif = null;
            if ($tahunAktif && $db->tableExists('schedule_versions')) {
                $jadwalAktif = $db->table('schedule_versions')
                                  ->where('academic_year_id', $tahunAktif['id'])
                                  ->where('is_active', 1)
                                  ->get()->getRowArray();
            }
    
            // Tarik mapel khusus yang diajar oleh guru ini
            if ($jadwalAktif) {
                 $daftarMapel = $db->table('class_schedules cs')
                              ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                              ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                              ->where('cs.version_id', $jadwalAktif['id'])
                              ->where("cs.{$kolomIdGuruDiJadwal}", $userId)
                              ->where("cs.{$kolomSubjectId} IS NOT NULL")
                              ->groupBy("cs.{$kolomSubjectId}")
                              ->get()->getResultArray();
            }
        }

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : 1);

        // ==============================================================
        // 4. TARIK DATA DARI HASIL ANALISIS CP
        // ==============================================================
        $dataAtp = [];
        $tabelAnalisis = 'analisis_cp_data'; // Sesuaikan dengan DB 
        
        if ($db->tableExists($tabelAnalisis)) {
             $dataAtp = $db->table($tabelAnalisis)
                           ->where('mapel_id', $selectedMapelId)
                           ->where('kelas_id', $selectedRombelId)
                           ->get()->getResultArray();
        }

        // ==============================================================
        // 5. DATA CEKLIS STATIS KBC KEMENAG
        // ==============================================================
        $listProfilLulusan = [
            'DPL1' => 'Keimanan dan Ketakwaan terhadap Tuhan YME',
            'DPL2' => 'Kewargaan',
            'DPL3' => 'Penalaran Kritis',
            'DPL4' => 'Kreativitas',
            'DPL5' => 'Kolaborasi',
            'DPL6' => 'Kemandirian',
            'DPL7' => 'Kesehatan',
            'DPL8' => 'Komunikasi'
        ];

        $listPancaCinta = [
            'P1' => 'Cinta kepada Allah dan Rasul-Nya',
            'P2' => 'Cinta kepada Ilmu',
            'P3' => 'Cinta kepada Diri Sendiri dan Sesama',
            'P4' => 'Cinta kepada Lingkungan',
            'P5' => 'Cinta kepada Tanah Air'
        ];

        $data = [
            'tahunAktif' => $tahunAktif,
            'daftarRombel' => $daftarRombel,
            'daftarMapel'  => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'tingkatKelas'     => $tingkatKelas,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtp'          => $dataAtp,
            
            'namaMadrasah' => $namaMadrasah ? $namaMadrasah['value'] : 'MTs MIFTAHUL HUDA (MIMHa)',
            'titiMangsa'   => $titiMangsa ? $titiMangsa['value'] : 'Bandung, ' . date('d F Y'),
            'kepalaNama'   => $kepalaSekolah ? $kepalaSekolah['value'] : '-',
            'namaGuruCetak'=> $namaGuruCetak,
            'listProfilLulusan' => $listProfilLulusan,
            'listPancaCinta'    => $listPancaCinta
        ];

        return view('guru/atp_manage', $data);
    }
}