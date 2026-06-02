<?php

namespace App\Controllers;

class PerangkatController extends BaseController
{
    public function analisis_cp()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil ID Guru yang sedang login (Multi-opsi deteksi session)
        $userId = null;
        if (function_exists('user_id')) { $userId = user_id(); }
        elseif (session()->has('user_id')) { $userId = session()->get('user_id'); }
        elseif (session()->has('id')) { $userId = session()->get('id'); }

        // 2. Deteksi Tahun Ajaran yang berstatus Aktif
        $tahunAktif = $db->table('academic_years')
                         ->where('is_active', 1)
                         ->get()
                         ->getRowArray();

        // 3. Ambil Pilihan Kelas Berdasarkan Rombel yang Aktif
        $classOptions = [];
        if ($tahunAktif) {
            $classOptions = $db->table('class_rombel cr')
                               ->select('mc.id, mc.class_name, mc.curriculum_phase')
                               ->join('master_classes mc', 'mc.id = cr.master_class_id')
                               ->where('cr.academic_year_id', $tahunAktif['id'])
                               ->groupBy('mc.id') // Menghindari duplikasi kelas
                               ->orderBy('mc.class_name', 'ASC')
                               ->get()
                               ->getResultArray();
        }

        // 4. Ambil Pilihan Mata Pelajaran (Tunggal & Gabungan sekaligus)
        // Disamakan persis dengan logika pendeteksian kolom di AnalysisController.php
        $subjectOptions = [];
        if ($tahunAktif && $userId) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            
            // KUNCI: AnalysisController menggunakan fallback 'mapel_id' bukan 'master_subject_id'
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';

            // Query tunggal ini otomatis menarik mapel tunggal maupun gabungan 
            // karena kita tidak mengunci/menyaring 'schedule_version_id' (persis seperti AnalysisController)
            $subjectOptions = $db->table('class_schedules cs')
                                 ->select('ms.id, ms.subject_name')
                                 ->join('master_subjects ms', 'ms.id = cs.' . $kolomSubjectId)
                                 ->where('cs.academic_year_id', $tahunAktif['id'])
                                 ->where('cs.' . $kolomIdGuru, $userId)
                                 ->groupBy('ms.id')
                                 ->orderBy('ms.subject_name', 'ASC')
                                 ->get()
                                 ->getResultArray();
        }

        // 5. Ambil data draft elemen yang sudah pernah disimpan oleh guru ini
        $draftElemen = [];
        if ($tahunAktif && $userId) {
            $draftElemen = $db->table('kurikulum_cp_drafts')
                              ->where('teacher_id', $userId)
                              ->where('academic_year_id', $tahunAktif['id'])
                              ->get()
                              ->getResultArray();
        }

        // 6. Ikat semua data ke dalam satu variabel $data untuk dikirim ke view
        $data = [
            'tahunAktif'     => $tahunAktif,
            'classOptions'   => $classOptions,
            'subjectOptions' => $subjectOptions,
            'draftElemen'    => $draftElemen
        ];

        return view('guru/analisis_cp', $data);
    }

    /**
     * FUNGSI: Menyimpan Draft Elemen via AJAX (Lebih Sederhana & Aman)
     */
    public function save_draft_elemen() 
    {
        $db = \Config\Database::connect();
        
        // Ambil ID Guru yang sedang login
        $userId = null;
        if (function_exists('user_id')) { $userId = user_id(); }
        elseif (session()->has('user_id')) { $userId = session()->get('user_id'); }
        elseif (session()->has('id')) { $userId = session()->get('id'); }

        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        if (!$tahunAktif) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tahun ajaran aktif tidak ditemukan.']);
        }

        // Karena ID sudah murni angka (berasal dari tabel yang sama), langsung ambil tanpa str_replace
        $mapelId = $this->request->getPost('mapel_id');
        
        $data = [
            'teacher_id'       => $userId ?? 0,
            'academic_year_id' => $tahunAktif['id'] ?? 0,
            'semester'         => $tahunAktif['semester'] ?? 'Ganjil',
            'mapel_id'         => (int) $mapelId, 
            'master_class_id'  => $this->request->getPost('master_class_id'), 
            'nama_elemen'      => $this->request->getPost('nama_elemen'),
            'deskripsi_cp'     => $this->request->getPost('deskripsi_cp'),
            'created_at'       => date('Y-m-d H:i:s')
        ];
        
        $simpan = $db->table('kurikulum_cp_drafts')->insert($data);
        
        if ($simpan) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Draft elemen berhasil disimpan.']);
        } else {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal menyimpan ke database.']);
        }
    }
}