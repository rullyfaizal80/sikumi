<?php

namespace App\Controllers;

class PerangkatController extends BaseController
{
    public function analisis_cp()
    {
        $db = \Config\Database::connect();
        
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();

        $classOptions = [];
        $subjectOptions = [];
        $draftElemen = [];

        if ($tahunAktif && $userId) {
            
            // Ambil Pilihan Kelas Berdasarkan Rombel
            $kelasQuery = $db->table('class_rombel cr')
                ->select('mc.id, mc.class_name, mc.curriculum_phase')
                ->join('master_classes mc', 'mc.id = cr.master_class_id')
                ->where('cr.academic_year_id', $tahunAktif['id'])
                ->groupBy('mc.id') 
                ->orderBy('mc.class_name', 'ASC')
                ->get()->getResultArray();
                
            foreach ($kelasQuery as $k) {
                $classOptions[$k['id']] = 'Kelas ' . ($k['class_name'] ?? '');
            }

            // Ambil Pilihan Mapel (Tunggal & Gabungan)
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
                if (!empty($row['combined_subject_id'])) {
                    $subjectOptions['C_' . $row['combined_subject_id']] = $row['combined_name'] ?? 'Mapel Gabungan';
                } elseif (!empty($row[$kolomSubjectId])) {
                    $subjectOptions['S_' . $row[$kolomSubjectId]] = $row['subject_name'] ?? 'Mapel Tunggal';
                }
            }
        }

        // TANGKAP ID MAPEL & KELAS YANG SEDANG DIPILIH (Atau ambil urutan pertama jika kosong)
        $selectedMapelId = $this->request->getGet('mapel_id') ?? array_key_first($subjectOptions);
        $selectedKelasId = $this->request->getGet('kelas_id') ?? array_key_first($classOptions);

        // Ambil Data Draft sesuai filter (Hanya jika jadwal tersedia)
        if ($tahunAktif && $userId && $selectedMapelId && $selectedKelasId) {
            $draftElemen = $db->table('kurikulum_cp_drafts')
                ->where('teacher_id', $userId)
                ->where('academic_year_id', $tahunAktif['id'])
                ->where('mapel_id', $selectedMapelId)
                ->where('master_class_id', $selectedKelasId)
                ->get()->getResultArray();
        }

        // PASTIKAN SEMUA VARIABEL DIKIRIM KE VIEW
        return view('guru/analisis_cp', [
            'tahunAktif'      => $tahunAktif,
            'classOptions'    => $classOptions,
            'subjectOptions'  => $subjectOptions,
            'draftElemen'     => $draftElemen,
            'selectedMapelId' => $selectedMapelId,
            'selectedKelasId' => $selectedKelasId
        ]);
    }

    public function save_draft_elemen() 
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        $mapelId = $this->request->getPost('mapel_id');
        $kelasId = $this->request->getPost('master_class_id');

        $data = [
            'teacher_id'       => $userId,
            'academic_year_id' => $tahunAktif['id'] ?? 0,
            'semester'         => $tahunAktif['semester'] ?? 'Ganjil',
            'mapel_id'         => $mapelId, 
            'master_class_id'  => $kelasId, 
            'nama_elemen'      => $this->request->getPost('nama_elemen'),
            'deskripsi_cp'     => $this->request->getPost('deskripsi_cp'),
            'created_at'       => date('Y-m-d H:i:s')
        ];
        
        $db->table('kurikulum_cp_drafts')->insert($data);
        
        // Redirect dan muat ulang halaman dengan Mapel & Kelas yang sama
        return redirect()->to(base_url("guru/analisis-cp?mapel_id={$mapelId}&kelas_id={$kelasId}"))
                         ->with('success', 'Elemen CP berhasil disimpan ke tabel.');
    }

    public function delete_draft_elemen($id) 
    {
        $db = \Config\Database::connect();
        $draft = $db->table('kurikulum_cp_drafts')->where('id', $id)->get()->getRowArray();
        
        if ($draft) {
            $db->table('kurikulum_cp_drafts')->where('id', $id)->delete();
            return redirect()->to(base_url("guru/analisis-cp?mapel_id={$draft['mapel_id']}&kelas_id={$draft['master_class_id']}"))
                             ->with('success', 'Elemen CP berhasil dihapus dari tabel.');
        }
        
        return redirect()->back();
    }

}
