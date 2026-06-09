<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ModulAjarController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // =====================================================================
        // 1. DETEKSI HAK AKSES & USER ID (Persis AtpController)
        // =====================================================================
        $uri = $this->request->getUri();
        $segment = $uri->getSegment(1); 
        $isGuru = (strtolower($segment) === 'guru');

        $userId = null;
        if (function_exists('user_id')) { $userId = user_id(); }
        elseif (session()->has('user_id')) { $userId = session()->get('user_id'); }
        elseif (session()->has('id')) { $userId = session()->get('id'); }

        $selectedTeacherId = $userId;

        // ==============================================================
        // 2. DINAMISASI ROMBEL
        // ==============================================================
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

        // ==============================================================
        // 3. DINAMISASI MAPEL GURU (Termasuk Gabungan)
        // ==============================================================
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
        // 4. LOAD DATA CP & ATP (MENDUKUNG MULTI-ELEMEN HEADER)
        // ==============================================================
        $dataAtpTersimpan = [];
        $totalJpAtp = 0;

        if ($tahunAktif && $selectedTeacherId && $db->tableExists('kurikulum_cp_headers') && $db->tableExists('kurikulum_cp_details')) {
            
            // Konversi format ID Mapel dari Dropdown (1 / C1) menjadi format DB (S_1 / C_1)
            $dbMapelId = '';
            if (strpos($selectedMapelId, 'C') === 0) {
                $dbMapelId = 'C_' . substr($selectedMapelId, 1);
            } else {
                $dbMapelId = (strpos($selectedMapelId, 'S_') === 0) ? $selectedMapelId : 'S_' . $selectedMapelId;
            }

            // LANGKAH 4: Cari SEMUA Header yang cocok (Karena 1 Mapel bisa punya banyak Elemen)
            // PERBAIKAN: Gunakan getResultArray() bukan getRowArray()
            $headers = $db->table('kurikulum_cp_headers')
                          ->where('academic_year_id', $tahunAktif['id'])
                          ->where('master_class_id', $masterClassId)
                          ->where('mapel_id', $dbMapelId)
                          ->where('teacher_id', $selectedTeacherId)
                          ->get()->getResultArray();

            // LANGKAH 5: Jika Header ditemukan, kumpulkan ID-nya dan tarik Detailnya
            if (!empty($headers)) {
                
                // Ekstrak semua ID Header menjadi array (misal: [10, 11, 12])
                $headerIds = array_column($headers, 'id');
                
                // Gunakan kurikulum_cp_details sebagai tabel dasar
                $builder = $db->table('kurikulum_cp_details d')
                              ->select('d.id as cp_detail_id, d.tujuan_pembelajaran as tp, d.lingkup_materi, d.estimasi_jp');
                
                // Jika tabel kurikulum_atp ada, LEFT JOIN untuk mengambil id dan urutan
                if ($db->tableExists('kurikulum_atp')) {
                    $builder->select('a.id as atp_id, a.urutan')
                            ->join('kurikulum_atp a', "a.cp_detail_id = d.id AND (a.rombel_id = {$selectedRombelId} OR a.rombel_id IS NULL)", 'left')
                            ->orderBy('a.urutan', 'ASC'); // Urutkan berdasarkan susunan ATP
                }

                // PERBAIKAN: Gunakan whereIn untuk menarik TP dari BANYAK Header sekaligus
                $dataAtpTersimpan = $builder->whereIn('d.header_id', $headerIds)
                                            ->orderBy('d.id', 'ASC') // Fallback jika urutan ATP kosong
                                            ->get()->getResultArray();

                // Format Nomor TP (Tingkat + Urutan) & Hitung Total JP
                foreach($dataAtpTersimpan as $idx => &$row) {
                    // Jika kolom urutan dari tabel ATP kosong, pakai urutan array asli
                    $urutan = (!empty($row['urutan'])) ? $row['urutan'] : ($idx + 1);
                    
                    $row['nomor_atp'] = $tingkatKelas . '.' . $urutan;
                    
                    // Suntikkan nilai default lewat PHP agar tabel/view tidak error
                    $row['status_modul'] = 0; 
                    $row['modul_id'] = null;
                    
                    $totalJpAtp += (int)($row['estimasi_jp'] ?? 0);
                }
                unset($row);
            }
        }

        // Dummy Target JP Sementara
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
}