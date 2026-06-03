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
        $analisisData = [];
        
        $totalJpTersedia = 0; 
        $namaMapelAktif = '';
        $namaKelasAktif = '';

        if ($tahunAktif && $userId) {
            
            // 1. Ambil Pilihan Kelas Berdasarkan Rombel
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

            // 2. Ambil Pilihan Mapel (Tunggal & Gabungan)
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

        // TANGKAP ID MAPEL & KELAS YANG SEDANG DIPILIH
        $selectedMapelId = $this->request->getGet('mapel_id') ?? array_key_first($subjectOptions);
        $selectedKelasId = $this->request->getGet('kelas_id') ?? array_key_first($classOptions);

        if ($tahunAktif && $userId && $selectedMapelId && $selectedKelasId) {
            
            // Simpan nama mapel & kelas aktif untuk dikirim ke prompt AI
            $namaMapelAktif = $subjectOptions[$selectedMapelId] ?? '';
            $namaKelasAktif = $classOptions[$selectedKelasId] ?? '';
            
            // Hitung JP Minimum dari Jadwal & Kaldik
            $totalJpTersedia = $this->_calculateMinTotalJp($db, $userId, $tahunAktif, $selectedMapelId, $selectedKelasId, $kolomIdGuru, $kolomSubjectId);

            // Ambil Data Draft Elemen (Tabel Langkah 1)
            $draftElemen = $db->table('kurikulum_cp_drafts')
                ->where('teacher_id', $userId)
                ->where('academic_year_id', $tahunAktif['id'])
                ->where('mapel_id', $selectedMapelId)
                ->where('master_class_id', $selectedKelasId)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

            // Ambil Data Analisis CP (Tabel Langkah 2)
            $analisisData = $db->table('kurikulum_cp_details d')
                ->select('d.*, h.elemen_cp')
                ->join('kurikulum_cp_headers h', 'h.id = d.header_id')
                ->where('h.teacher_id', $userId)
                ->where('h.academic_year_id', $tahunAktif['id'])
                ->where('h.mapel_id', $selectedMapelId)
                ->where('h.master_class_id', $selectedKelasId)
                ->orderBy('h.id', 'ASC')->orderBy('d.id', 'ASC')
                ->get()->getResultArray();
        }

        // KIRIM SEMUA DATA KE VIEW
        return view('guru/analisis_cp', [
            'tahunAktif'      => $tahunAktif,
            'classOptions'    => $classOptions,
            'subjectOptions'  => $subjectOptions,
            'draftElemen'     => $draftElemen,
            'analisisData'    => $analisisData,
            'selectedMapelId' => $selectedMapelId,
            'selectedKelasId' => $selectedKelasId,
            'totalJpTersedia' => $totalJpTersedia,
            'namaMapelAktif'  => $namaMapelAktif,
            'namaKelasAktif'  => $namaKelasAktif
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
        
        // 1. Cari data draft yang akan dihapus
        $draft = $db->table('kurikulum_cp_drafts')->where('id', $id)->get()->getRowArray();
        
        if ($draft) {
            // 2. Lacak apakah draft ini sudah pernah dianalisis ke tabel Header & Detail
            $header = $db->table('kurikulum_cp_headers')
                         ->where([
                             'teacher_id'      => $draft['teacher_id'],
                             'mapel_id'        => $draft['mapel_id'],
                             'master_class_id' => $draft['master_class_id'],
                             'elemen_cp'       => $draft['nama_elemen']
                         ])->get()->getRowArray();
                         
            // 3. Jika ketemu, sapu bersih Detail dan Header-nya sekaligus
            if ($header) {
                $db->table('kurikulum_cp_details')->where('header_id', $header['id'])->delete();
                $db->table('kurikulum_cp_headers')->where('id', $header['id'])->delete();
            }
            
            // 4. Terakhir, hapus Draft itu sendiri
            $db->table('kurikulum_cp_drafts')->where('id', $id)->delete();
        }
        
        return redirect()->back()->with('success', 'Draft Elemen CP berhasil dihapus.');
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

    public function update_draft_elemen() 
    {
        $db = \Config\Database::connect();
        
        $id = $this->request->getPost('draft_id');
        $mapelId = $this->request->getPost('mapel_id');
        $kelasId = $this->request->getPost('master_class_id');

        $data = [
            'nama_elemen'  => $this->request->getPost('nama_elemen'),
            'deskripsi_cp' => $this->request->getPost('deskripsi_cp')
        ];
        
        // Update ke database
        $db->table('kurikulum_cp_drafts')->where('id', $id)->update($data);
        
        // Redirect kembali dengan pesan sukses
        return redirect()->to(base_url("guru/analisis-cp?mapel_id={$mapelId}&kelas_id={$kelasId}"))
                         ->with('success', 'Elemen CP berhasil diperbarui.');
    }

    // =========================================================================
    // CRUD UNTUK TABEL ANALISIS CP (LANGKAH 2)
    // =========================================================================
    public function save_analisis_manual() 
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        $mapelId = $this->request->getPost('mapel_id');
        $kelasId = $this->request->getPost('master_class_id');
        $draftId = $this->request->getPost('draft_id'); // ID dari elemen yang dipilih
        
        // 1. Ambil nama elemen dari tabel draft
        $draft = $db->table('kurikulum_cp_drafts')->where('id', $draftId)->get()->getRowArray();
        
        // 2. Cek apakah Header untuk Elemen ini sudah ada
        $header = $db->table('kurikulum_cp_headers')
                     ->where(['teacher_id' => $userId, 'academic_year_id' => $tahunAktif['id'], 'mapel_id' => $mapelId, 'master_class_id' => $kelasId, 'elemen_cp' => $draft['nama_elemen']])
                     ->get()->getRowArray();
                     
        if (!$header) {
            // Jika belum ada, buat Header baru
            $db->table('kurikulum_cp_headers')->insert([
                'academic_year_id' => $tahunAktif['id'],
                'master_class_id'  => $kelasId,
                'mapel_id'         => $mapelId,
                'teacher_id'       => $userId,
                'elemen_cp'        => $draft['nama_elemen'],
                'teks_cp_asli'     => $draft['deskripsi_cp'],
                'created_at'       => date('Y-m-d H:i:s')
            ]);
            $headerId = $db->insertID();
        } else {
            $headerId = $header['id'];
        }

        // 3. Simpan ke Tabel Detail
        $db->table('kurikulum_cp_details')->insert([
            'header_id'           => $headerId,
            'kompetensi'          => '', 
            'lingkup_materi'      => $this->request->getPost('lingkup_materi'),
            'tujuan_pembelajaran' => $this->request->getPost('tujuan_pembelajaran'),
            'kktp'                => $this->request->getPost('kktp'),
            'estimasi_jp'         => $this->request->getPost('estimasi_jp'),
            'aktivitas_tarl'      => $this->request->getPost('aktivitas'),
            'created_at'          => date('Y-m-d H:i:s')
        ]);

        return redirect()->back()->with('success', 'Analisis CP berhasil ditambahkan.');
    }

    public function update_analisis_manual() 
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        $detailId = $this->request->getPost('detail_id');
        $mapelId = $this->request->getPost('mapel_id');
        $kelasId = $this->request->getPost('master_class_id');
        $draftId = $this->request->getPost('draft_id'); // Menangkap Elemen CP dari dropdown edit
        
        // 1. Ambil nama elemen dari tabel draft berdasarkan pilihan baru
        $draft = $db->table('kurikulum_cp_drafts')->where('id', $draftId)->get()->getRowArray();
        
        if ($draft && $tahunAktif) {
            // 2. Cek apakah Header untuk Elemen ini sudah ada
            $header = $db->table('kurikulum_cp_headers')
                         ->where(['teacher_id' => $userId, 'academic_year_id' => $tahunAktif['id'], 'mapel_id' => $mapelId, 'master_class_id' => $kelasId, 'elemen_cp' => $draft['nama_elemen']])
                         ->get()->getRowArray();
                         
            if (!$header) {
                // Jika belum ada, buat Header baru
                $db->table('kurikulum_cp_headers')->insert([
                    'academic_year_id' => $tahunAktif['id'],
                    'master_class_id'  => $kelasId,
                    'mapel_id'         => $mapelId,
                    'teacher_id'       => $userId,
                    'elemen_cp'        => $draft['nama_elemen'],
                    'teks_cp_asli'     => $draft['deskripsi_cp'],
                    'created_at'       => date('Y-m-d H:i:s')
                ]);
                $headerId = $db->insertID();
            } else {
                $headerId = $header['id']; // Jika sudah ada, gunakan ID yang sudah ada
            }
            
            // 3. Update Tabel Detail (Termasuk pindah header_id)
            $db->table('kurikulum_cp_details')->where('id', $detailId)->update([
                'header_id'           => $headerId, // <--- Ini kunci untuk memindahkan elemen
                'lingkup_materi'      => $this->request->getPost('lingkup_materi'),
                'tujuan_pembelajaran' => $this->request->getPost('tujuan_pembelajaran'),
                'kktp'                => $this->request->getPost('kktp'),
                'estimasi_jp'         => $this->request->getPost('estimasi_jp'),
                'aktivitas_tarl'      => $this->request->getPost('aktivitas'),
                'updated_at'          => date('Y-m-d H:i:s')
            ]);
        }
        
        return redirect()->back()->with('success', 'Analisis CP berhasil diperbarui.');
    }

    public function delete_analisis_manual($id) 
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil info detail (TP) sebelum dihapus untuk mengetahui ID Header-nya
        $detail = $db->table('kurikulum_cp_details')->where('id', $id)->get()->getRowArray();
        
        if ($detail) {
            $headerId = $detail['header_id'];
            
            // 2. Hapus baris detail (TP) tersebut
            $db->table('kurikulum_cp_details')->where('id', $id)->delete();
            
            // 3. Cek apakah Header (Elemen) tersebut masih memiliki TP lain yang tersisa?
            $cekSisaDetail = $db->table('kurikulum_cp_details')->where('header_id', $headerId)->countAllResults();
            
            // 4. Jika sudah kosong melompong (0), hapus juga Header-nya agar tidak jadi sampah!
            if ($cekSisaDetail == 0) {
                $db->table('kurikulum_cp_headers')->where('id', $headerId)->delete();
            }
        }

        return redirect()->back()->with('success', 'Data Analisis CP berhasil dihapus.');
    }

    public function save_analisis_batch() 
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        $mapelId = $this->request->getPost('mapel_id');
        $kelasId = $this->request->getPost('master_class_id');
        $dataRows = json_decode($this->request->getPost('data_rows'), true);

        if (empty($dataRows) || !is_array($dataRows)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data yang dipilih atau format salah.']);
        }

        foreach ($dataRows as $row) {
            $namaElemen = trim($row['elemen']);
            
            // 1. Cek Header berdasarkan nama Elemen
            $header = $db->table('kurikulum_cp_headers')
                         ->where(['teacher_id' => $userId, 'academic_year_id' => $tahunAktif['id'], 'mapel_id' => $mapelId, 'master_class_id' => $kelasId, 'elemen_cp' => $namaElemen])
                         ->get()->getRowArray();
                         
            if (!$header) {
                // Jika Header belum ada, ambil teks asli dari draft
                $draft = $db->table('kurikulum_cp_drafts')->where(['teacher_id' => $userId, 'mapel_id' => $mapelId, 'master_class_id' => $kelasId, 'nama_elemen' => $namaElemen])->get()->getRowArray();
                $teksAsli = $draft ? $draft['deskripsi_cp'] : 'Hasil Analisis AI SiKuMi';

                $db->table('kurikulum_cp_headers')->insert([
                    'academic_year_id' => $tahunAktif['id'],
                    'master_class_id'  => $kelasId,
                    'mapel_id'         => $mapelId,
                    'teacher_id'       => $userId,
                    'elemen_cp'        => $namaElemen,
                    'teks_cp_asli'     => $teksAsli,
                    'created_at'       => date('Y-m-d H:i:s')
                ]);
                $headerId = $db->insertID();
            } else {
                $headerId = $header['id'];
            }

            // 2. Insert ke Tabel Detail
            $db->table('kurikulum_cp_details')->insert([
                'header_id'           => $headerId,
                'kompetensi'          => '', 
                'lingkup_materi'      => $row['lingkup'],
                'tujuan_pembelajaran' => $row['tp'],
                'kktp'                => $row['kktp'],
                'estimasi_jp'         => (int)$row['jp'],
                'aktivitas_tarl'      => $row['aktivitas'],
                'created_at'          => date('Y-m-d H:i:s')
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'Data AI berhasil dipindahkan ke Tabel Analisis Manual!']);
    }

    public function copy_draft_elemen()
    {
        $db = \Config\Database::connect();
        $userId = session()->has('user_id') ? session()->get('user_id') : (function_exists('user_id') ? user_id() : 0);
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();

        $mapelId = $this->request->getPost('mapel_id');
        $kelasAsalId = $this->request->getPost('kelas_asal_id');
        $kelasTujuanId = $this->request->getPost('kelas_tujuan_id');

        if (!$kelasAsalId || !$kelasTujuanId || !$mapelId) {
            return redirect()->back()->with('error', 'Data tidak lengkap untuk menyalin CP.');
        }

        // 1. Ambil data draft dari kelas asal
        $draftsAsal = $db->table('kurikulum_cp_drafts')
            ->where([
                'teacher_id' => $userId,
                'academic_year_id' => $tahunAktif['id'],
                'mapel_id' => $mapelId,
                'master_class_id' => $kelasAsalId
            ])->get()->getResultArray();

        if (empty($draftsAsal)) {
            return redirect()->back()->with('error', 'Tidak ada data Draft Elemen di kelas asal tersebut.');
        }

        $count = 0;
        foreach ($draftsAsal as $draft) {
            // 2. Cek apakah elemen ini sudah ada di kelas tujuan agar tidak dobel
            $cek = $db->table('kurikulum_cp_drafts')
                ->where([
                    'teacher_id' => $userId,
                    'academic_year_id' => $tahunAktif['id'],
                    'mapel_id' => $mapelId,
                    'master_class_id' => $kelasTujuanId,
                    'nama_elemen' => $draft['nama_elemen']
                ])->countAllResults();

            // 3. Jika belum ada, Insert (Salin)!
            if ($cek == 0) {
                $db->table('kurikulum_cp_drafts')->insert([
                    'teacher_id'       => $userId,
                    'academic_year_id' => $tahunAktif['id'],
                    'semester'         => $tahunAktif['semester'] ?? 'Ganjil',
                    'mapel_id'         => $mapelId,
                    'master_class_id'  => $kelasTujuanId,
                    'nama_elemen'      => $draft['nama_elemen'],
                    'deskripsi_cp'     => $draft['deskripsi_cp'],
                    'created_at'       => date('Y-m-d H:i:s')
                ]);
                $count++;
            }
        }

        if ($count > 0) {
            return redirect()->back()->with('success', "Berhasil menyalin $count Elemen CP dari kelas lain.");
        } else {
            return redirect()->back()->with('error', "Tidak ada elemen baru yang disalin (Mungkin elemen sudah ada di kelas ini).");
        }
    }

}
