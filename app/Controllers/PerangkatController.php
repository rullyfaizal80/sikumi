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
        
        $totalJpTersedia = 0; // Variabel baru untuk menampung JP
        $namaMapelAktif = '';
        $namaKelasAktif = '';

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
                $classOptions[$k['id']] = 'Kelas ' . ($k['class_name'] ?? '') . ' (Fase ' . ($k['curriculum_phase'] ?? '-') . ')';
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

        $selectedMapelId = $this->request->getGet('mapel_id') ?? array_key_first($subjectOptions);
        $selectedKelasId = $this->request->getGet('kelas_id') ?? array_key_first($classOptions);

        if ($tahunAktif && $userId && $selectedMapelId && $selectedKelasId) {
            
            // Simpan nama mapel & kelas aktif untuk dikirim ke prompt AI
            $namaMapelAktif = $subjectOptions[$selectedMapelId] ?? '';
            $namaKelasAktif = $classOptions[$selectedKelasId] ?? '';
            
            // Hitung JP Minimum dari Jadwal & Kaldik
            $totalJpTersedia = $this->_calculateMinTotalJp($db, $userId, $tahunAktif, $selectedMapelId, $selectedKelasId, $kolomIdGuru, $kolomSubjectId);

            $draftElemen = $db->table('kurikulum_cp_drafts')
                ->where('teacher_id', $userId)
                ->where('academic_year_id', $tahunAktif['id'])
                ->where('mapel_id', $selectedMapelId)
                ->where('master_class_id', $selectedKelasId)
                ->get()->getResultArray();
        }

        return view('guru/analisis_cp', [
            'tahunAktif'      => $tahunAktif,
            'classOptions'    => $classOptions,
            'subjectOptions'  => $subjectOptions,
            'draftElemen'     => $draftElemen,
            'selectedMapelId' => $selectedMapelId,
            'selectedKelasId' => $selectedKelasId,
            'totalJpTersedia' => $totalJpTersedia, // Kirim ke View
            'namaMapelAktif'  => $namaMapelAktif,  // Kirim ke View
            'namaKelasAktif'  => $namaKelasAktif   // Kirim ke View
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

    /**
     * FUNGSI PRIVATE: Mengadopsi logika HEB untuk mencari Total JP paling sedikit dari suatu tingkat kelas.
     */
    private function _calculateMinTotalJp($db, $userId, $tahunAktif, $selectedMapelId, $selectedKelasId, $kolomIdGuru, $kolomSubjectId) 
    {
        $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        if (!$jadwalAktif) return 0;

        $isCombined = (strpos($selectedMapelId, 'C_') === 0);
        $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

        $builder = $db->table('class_schedules cs')
                      ->select('cs.rombel_id')
                      ->join('class_rombel r', 'r.id = cs.rombel_id')
                      ->where('cs.version_id', $jadwalAktif['id'])
                      ->where("cs.{$kolomIdGuru}", $userId)
                      ->where('r.master_class_id', $selectedKelasId);

        if ($isCombined) { $builder->where('cs.combined_subject_id', $realSubjectId); }
        else { $builder->where("cs.{$kolomSubjectId}", $realSubjectId); }

        $rombels = $builder->groupBy('cs.rombel_id')->get()->getResultArray();
        if (empty($rombels)) return 0;

        $tahunSplit = explode('/', $tahunAktif['academic_year']);
        $tahunStart = (int)trim($tahunSplit[0]);
        $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;
        $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
        $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
        $hariNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

        $kaldikEvents = $db->tableExists('academic_calendars') ? $db->table('academic_calendars')->where('academic_year_id', $tahunAktif['id'])->where('class_id', $selectedKelasId)->get()->getResultArray() : [];

        $minTotalJp = null;

        foreach ($rombels as $rombel) {
            $schBuilder = $db->table('class_schedules cs')
                             ->join('schedule_time_slots ts', 'ts.id = cs.slot_id')
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where('cs.rombel_id', $rombel['rombel_id'])
                             ->where("cs.{$kolomIdGuru}", $userId);
                             
            if ($isCombined) { $schBuilder->where('cs.combined_subject_id', $realSubjectId); }
            else { $schBuilder->where("cs.{$kolomSubjectId}", $realSubjectId); }
            
            $schedules = $schBuilder->get()->getResultArray();
            $jpPerHari = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
            
            foreach ($schedules as $sch) {
                if (isset($jpPerHari[$sch['day_name']])) { $jpPerHari[$sch['day_name']] += 1; }
            }

            $grandTotalJp = 0;
            foreach ($bulanList as $bln) {
                $tahunTerkait = ($isGanjil) ? $tahunStart : $tahunEnd;
                $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);
                
                $hebBulanIni = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
                for ($d = 1; $d <= $jmlHariBulan; $d++) {
                    $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                    $dayOfWeek = date('N', strtotime($dateStr));
                    if ($dayOfWeek <= 5) {
                        $isLibur = false;
                        foreach ($kaldikEvents as $ev) {
                            if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) { $isLibur = true; break; }
                        }
                        if (!$isLibur) $hebBulanIni[$hariNames[$dayOfWeek]]++;
                    }
                }
                foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
                    $grandTotalJp += ($hebBulanIni[$hari] * $jpPerHari[$hari]);
                }
            }

            if ($minTotalJp === null || $grandTotalJp < $minTotalJp) { $minTotalJp = $grandTotalJp; }
        }

        return $minTotalJp ?? 0;
    }

}
