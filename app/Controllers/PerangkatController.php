<?php

namespace App\Controllers;

class PerangkatController extends BaseController
{
    public function analisis_cp()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil ID Guru (Dibersihkan agar lebih ringkas)
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);

        // 2. Deteksi Tahun Ajaran Aktif
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();

        $classOptions = [];
        $subjectOptions = [];
        $draftElemen = [];

        if ($tahunAktif && $userId) {
            
            // 3. Ambil Pilihan Kelas Berdasarkan Rombel
            $classOptions = $db->table('class_rombel cr')
                ->select('mc.id, mc.class_name, mc.curriculum_phase')
                ->join('master_classes mc', 'mc.id = cr.master_class_id')
                ->where('cr.academic_year_id', $tahunAktif['id'])
                ->groupBy('mc.id') 
                ->orderBy('mc.class_name', 'ASC')
                ->get()->getResultArray();

            // 4. Ambil Pilihan Mapel (Tunggal & Gabungan) Sesuai Logika AnalysisController
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : 'subjects';
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');

            $mapelJadwal = $db->table('class_schedules cs')
                ->select("cs.{$kolomSubjectId}, cs.combined_subject_id, ms.{$kolomNamaMapel} as subject_name, c.combined_name")
                ->join("{$tabelMapel} ms", "ms.id = cs.{$kolomSubjectId}", 'left')
                ->join('schedule_combined_subjects c', 'c.id = cs.combined_subject_id', 'left')
                ->where('cs.academic_year_id', $tahunAktif['id'])
                ->where("cs.{$kolomIdGuru}", $userId)
                ->groupStart()
                    ->where("cs.{$kolomSubjectId} IS NOT NULL")
                    ->orWhere('cs.combined_subject_id IS NOT NULL')
                ->groupEnd()
                ->groupBy("cs.{$kolomSubjectId}, cs.combined_subject_id")
                ->get()->getResultArray();

            foreach ($mapelJadwal as $row) {
                // Beri kode C_ untuk Gabungan dan S_ untuk Single/Tunggal
                if (!empty($row['combined_subject_id'])) {
                    $subjectOptions['C_' . $row['combined_subject_id']] = $row['combined_name'] ?? 'Mapel Gabungan';
                } elseif (!empty($row[$kolomSubjectId])) {
                    $subjectOptions['S_' . $row[$kolomSubjectId]] = $row['subject_name'] ?? 'Mapel Tunggal';
                }
            }

            // 5. Ambil data draft
            $draftElemen = $db->table('kurikulum_cp_drafts')
                ->where('teacher_id', $userId)
                ->where('academic_year_id', $tahunAktif['id'])
                ->get()->getResultArray();
        }

        return view('guru/analisis_cp', [
            'tahunAktif'     => $tahunAktif,
            'classOptions'   => $classOptions,
            'subjectOptions' => $subjectOptions,
            'draftElemen'    => $draftElemen
        ]);
    }

    public function save_draft_elemen() 
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        if (!$tahunAktif) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tahun ajaran aktif tidak ditemukan.']);
        }
        
        $data = [
            'teacher_id'       => $userId,
            'academic_year_id' => $tahunAktif['id'],
            'semester'         => $tahunAktif['semester'] ?? 'Ganjil',
            'mapel_id'         => $this->request->getPost('mapel_id'), // Menghapus (int) agar bisa menerima C_1 atau S_2
            'master_class_id'  => $this->request->getPost('master_class_id'), 
            'nama_elemen'      => $this->request->getPost('nama_elemen'),
            'deskripsi_cp'     => $this->request->getPost('deskripsi_cp'),
            'created_at'       => date('Y-m-d H:i:s')
        ];
        
        if ($db->table('kurikulum_cp_drafts')->insert($data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Draft elemen berhasil disimpan.']);
        }
        
        return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
    }
}
