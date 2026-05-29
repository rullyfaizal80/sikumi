<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ScheduleController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // [AUTO-PATCH] Mencegah Error jika kolom belum ada
        if ($db->tableExists('schedule_jp_targets') && !$db->fieldExists('combined_subject_id', 'schedule_jp_targets')) {
            $db->query("ALTER TABLE `schedule_jp_targets` ADD `combined_subject_id` INT(11) NULL DEFAULT NULL AFTER `subject_id`");
        }

        $daftarTahun = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();
        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) { $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray(); } 
        if (empty($tahunAktif)) {
            $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            if (!$tahunAktif && !empty($daftarTahun)) $tahunAktif = $daftarTahun[0];
        }

        $activeTab = $this->request->getGet('tab') ?? 'matriks';

        $versions = [];
        $activeVersion = null;
        if ($tahunAktif) {
            $versions = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->orderBy('id', 'ASC')->get()->getResultArray();
            $selectedVersionId = $this->request->getGet('v');
            if (!empty($selectedVersionId)) $activeVersion = $db->table('schedule_versions')->where('id', $selectedVersionId)->get()->getRowArray();
            if (empty($activeVersion) && !empty($versions)) $activeVersion = $versions[0]; 
        }

        $rombels = [];
        if ($tahunAktif) {
            $rombels = $db->table('class_rombel cr')->select('cr.*, mc.class_name, mc.level_type')->join('master_classes mc', 'mc.id = cr.master_class_id')->where('cr.academic_year_id', $tahunAktif['id'])->orderBy('mc.id', 'ASC')->orderBy('cr.rombel_name', 'ASC')->get()->getResultArray();
        }

        $kegiatan = $db->table('master_activities')->get()->getResultArray();

        $timeSlots = [];
        if ($activeVersion) {
            $timeSlots = $db->table('schedule_time_slots')->where('version_id', $activeVersion['id'])->orderBy('day_name', 'ASC')->orderBy('slot_number', 'ASC')->get()->getResultArray();
        }

        // ==========================================
        // TAB 2: DATA PLOTTING MAPEL & GURU OTOMATIS
        // ==========================================
        $subjects = []; $teachers = []; $plottingDataNormal = []; $plottingDataCombined = [];
        $assignedTeachers = []; $combinedSubjects = []; $combinedChildIds = [];

        if ($activeTab == 'plotting' || $activeTab == 'matriks') {
            if ($db->tableExists('master_subjects')) $subjects = $db->table('master_subjects')->get()->getResultArray();
            elseif ($db->tableExists('subjects')) $subjects = $db->table('subjects')->get()->getResultArray();

            if ($db->tableExists('master_teachers')) $teachers = $db->table('master_teachers')->get()->getResultArray();
            elseif ($db->tableExists('users')) $teachers = $db->table('users')->get()->getResultArray();

            $teacherDict = [];
            foreach($teachers as $t) { $teacherDict[$t['id']] = $t['teacher_name'] ?? $t['nama_guru'] ?? $t['name'] ?? $t['fullname'] ?? 'Guru ID: '.$t['id']; }

            if ($db->tableExists('class_subject_teachers')) {
                $cst_data = $db->table('class_subject_teachers')->get()->getResultArray();
                foreach($cst_data as $row) {
                    $assignedTeachers[$row['master_subject_id']][$row['rombel_id']] = [
                        'teacher_id'   => $row['teacher_id'],
                        'teacher_name' => $teacherDict[$row['teacher_id']] ?? 'Guru ID: '.$row['teacher_id']
                    ];
                }
            }

            if ($activeVersion && $db->tableExists('schedule_jp_targets')) {
                $targets = $db->table('schedule_jp_targets')->where('version_id', $activeVersion['id'])->get()->getResultArray();
                foreach($targets as $t) {
                    if (!empty($t['combined_subject_id'])) { $plottingDataCombined[$t['combined_subject_id']][$t['rombel_id']] = $t; } 
                    else { $plottingDataNormal[$t['subject_id']][$t['rombel_id']] = $t; }
                }
            }

            if ($tahunAktif && $db->tableExists('schedule_combined_subjects')) {
                $comb = $db->table('schedule_combined_subjects')->where('academic_year_id', $tahunAktif['id'])->get()->getResultArray();
                foreach ($comb as $c) {
                    $details = $db->table('schedule_combined_details')->where('combined_subject_id', $c['id'])->get()->getResultArray();
                    $detailIds = array_column($details, 'master_subject_id');
                    $c['detail_ids'] = $detailIds;
                    $combinedChildIds = array_merge($combinedChildIds, $detailIds);
                    
                    $detailNames = [];
                    foreach($subjects as $sub) { if (in_array($sub['id'], $detailIds)) { $detailNames[] = $sub['subject_name'] ?? $sub['nama_mapel']; } }
                    $c['detail_names_string'] = implode(' + ', $detailNames);
                    $c['detail_ids_string'] = implode(',', $detailIds);
                    $combinedSubjects[] = $c;
                }
            }
        }

        // ==========================================
        // TAB 1: LOGIKA MATRIKS JADWAL SEMUA KELAS
        // ==========================================
        $classSchedules = []; $usedJpNormal = []; $usedJpCombined = [];
        $slotGrid = []; $maxSlot = 0; $matrixDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        if ($activeTab == 'matriks' && $activeVersion) {
            if ($db->tableExists('class_schedules')) {
                // Tarik seluruh jadwal pada versi ini sekaligus
                $schData = $db->table('class_schedules')->where(['version_id' => $activeVersion['id']])->get()->getResultArray();
                foreach($schData as $sd) {
                    // Simpan dengan format 2 Dimensi: [ID_SLOT][ID_ROMBEL]
                    $classSchedules[$sd['slot_id']][$sd['rombel_id']] = $sd; 
                    
                    // Hitung jumlah JP yang sudah digunakan per Rombel
                    if (!empty($sd['subject_id'])) {
                        $usedJpNormal[$sd['subject_id']][$sd['rombel_id']] = ($usedJpNormal[$sd['subject_id']][$sd['rombel_id']] ?? 0) + 1;
                    }
                    if (!empty($sd['combined_subject_id'])) {
                        $usedJpCombined[$sd['combined_subject_id']][$sd['rombel_id']] = ($usedJpCombined[$sd['combined_subject_id']][$sd['rombel_id']] ?? 0) + 1;
                    }
                }
            }
            // Petakan ke Grid Waktu
            foreach($timeSlots as $ts) {
                $slotGrid[$ts['slot_number']][$ts['day_name']] = $ts;
                if($ts['slot_number'] > $maxSlot) $maxSlot = $ts['slot_number'];
            }
        }

        // Ambil semua versi dari seluruh semester untuk fitur Copy Lintas Semester
        $allVersions = $db->table('schedule_versions sv')
                          ->select('sv.*, ay.academic_year, ay.semester')
                          ->join('academic_years ay', 'ay.id = sv.academic_year_id')
                          ->orderBy('ay.id', 'DESC')
                          ->orderBy('sv.id', 'ASC')
                          ->get()->getResultArray();

        $data = [
            'title'                => 'Manajemen Jadwal - SiKuMi',
            'daftarTahun'          => $daftarTahun,
            'tahunAktif'           => $tahunAktif,
            'activeTab'            => $activeTab,
            'rombels'              => $rombels,
            'kegiatan'             => $kegiatan,
            'versions'             => $versions,
            'activeVersion'        => $activeVersion,
            'timeSlots'            => $timeSlots,
            'subjects'             => $subjects,
            'teachers'             => $teachers,
            'plottingDataNormal'   => $plottingDataNormal,
            'plottingDataCombined' => $plottingDataCombined,
            'assignedTeachers'     => $assignedTeachers,
            'combinedSubjects'     => $combinedSubjects,
            'combinedChildIds'     => $combinedChildIds,
            // Variabel Khusus Tab 1 Matriks
            'classSchedules'       => $classSchedules,
            'usedJpNormal'         => $usedJpNormal,
            'usedJpCombined'       => $usedJpCombined,
            'slotGrid'             => $slotGrid,
            'maxSlot'              => $maxSlot,
            'matrixDays'           => $matrixDays,
            // Variabel Khusus Copy Lintas Semester (Ini yang terlewat!)
            'allVersions'          => $allVersions 
        ];

        return view('admin/schedule/index', $data);
    }

    // ==========================================
    // FUNGSI SIMPAN TAB 1 (MATRIKS JADWAL)
    // ==========================================
    public function saveMatrix()
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getPost('ta');
        $v = $this->request->getPost('v');
        $matrix = $this->request->getPost('matrix'); 

        if (empty($matrix)) {
            $db->table('class_schedules')->where(['version_id' => $v])->delete();
            return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '✅ Matriks Jadwal berhasil dikosongkan!');
        }

        // --- 1. SIAPKAN KAMUS UNTUK PESAN ERROR ---
        $slots = $db->table('schedule_time_slots')->where('version_id', $v)->get()->getResultArray();
        $slotDict = []; foreach($slots as $s) { $slotDict[$s['id']] = $s['day_name'] . ' (Jam Ke-' . $s['slot_number'] . ')'; }
        
        $teachers = $db->table('users')->get()->getResultArray(); 
        $teacherDict = []; foreach($teachers as $t) { $teacherDict[$t['id']] = $t['username'] ?? 'Guru ID '.$t['id']; }
        
        $rombels = $db->table('class_rombel cr')->select('cr.id, cr.rombel_name, mc.class_name')->join('master_classes mc', 'mc.id = cr.master_class_id')->get()->getResultArray();
        $rombelDict = []; foreach($rombels as $r) { $rombelDict[$r['id']] = $r['class_name'] . '-' . $r['rombel_name']; }

        if ($db->tableExists('master_subjects')) $subjects = $db->table('master_subjects')->get()->getResultArray();
        elseif ($db->tableExists('subjects')) $subjects = $db->table('subjects')->get()->getResultArray();
        $subjectDict = []; foreach($subjects as $su) { $subjectDict[$su['id']] = $su['subject_name'] ?? $su['nama_mapel'] ?? 'Mapel'; }

        if ($db->tableExists('schedule_combined_subjects')) {
            $combs = $db->table('schedule_combined_subjects')->get()->getResultArray();
            $combDict = []; foreach($combs as $c) { $combDict[$c['id']] = $c['combined_name']; }
        } else {
            $combDict = [];
        }

        $targets = $db->table('schedule_jp_targets')->where('version_id', $v)->get()->getResultArray();
        $targetJpDict = []; 
        foreach($targets as $t) {
            if (!empty($t['subject_id'])) $targetJpDict[$t['rombel_id']]['SUB_'.$t['subject_id']] = $t['target_jp'];
            elseif (!empty($t['combined_subject_id'])) $targetJpDict[$t['rombel_id']]['COM_'.$t['combined_subject_id']] = $t['target_jp'];
        }

        // --- 2. PROSES DATA & DETEKSI BENTROK/OVERLOAD ---
        $insertData = [];
        $teacherSlotCheck = []; 
        $bentrokMessages = [];
        $plotCount = []; 

        foreach ($matrix as $slotId => $rombelsData) {
            foreach($rombelsData as $rombelId => $val) {
                if (empty($val)) continue;
                
                $slotData = $db->table('schedule_time_slots')->where('id', $slotId)->get()->getRowArray();
                if (!$slotData) continue;

                // 🔴 KUNCI PERBAIKAN BUG KOSONG: Struktur array WAJIB IDENTIK untuk semua jenis opsi
                $row = [
                    'academic_year_id'    => $ta,
                    'version_id'          => $v,
                    'rombel_id'           => $rombelId,
                    'day_name'            => $slotData['day_name'],
                    'slot_id'             => $slotId,
                    'subject_id'          => null,
                    'combined_subject_id' => null,
                    'teacher_id'          => null,
                    'activity_id'         => null,
                ];

                if (strpos($val, 'ACT_') === 0) {
                    $row['activity_id'] = str_replace('ACT_', '', $val);
                    $insertData[] = $row; // Masukkan ke wadah, lanjut ke data berikutnya
                    continue;
                }

                $teacherId = null;
                $subjectKey = '';
                $namaMapelStr = '';

                if (strpos($val, 'SUB_') === 0) {
                    $parts = explode('_', str_replace('SUB_', '', $val));
                    $row['subject_id'] = $parts[0];
                    $teacherId = $parts[1] ?? null;
                    $subjectKey = 'SUB_'.$parts[0];
                    $namaMapelStr = $subjectDict[$parts[0]] ?? 'Mapel Reguler';
                } elseif (strpos($val, 'COM_') === 0) {
                    $parts = explode('_', str_replace('COM_', '', $val));
                    $row['combined_subject_id'] = $parts[0];
                    $teacherId = $parts[1] ?? null;
                    $subjectKey = 'COM_'.$parts[0];
                    $namaMapelStr = $combDict[$parts[0]] ?? 'Mapel Gabungan';
                }
                $row['teacher_id'] = $teacherId;

                // VALIDASI 1: Cek Batas Maksimal JP
                if (!isset($plotCount[$rombelId][$subjectKey])) $plotCount[$rombelId][$subjectKey] = 0;
                $plotCount[$rombelId][$subjectKey]++;
                
                $batasJp = $targetJpDict[$rombelId][$subjectKey] ?? 0;
                if ($plotCount[$rombelId][$subjectKey] > $batasJp) {
                    $kelasTxt = $rombelDict[$rombelId] ?? 'Kelas Ybs';
                    $bentrokMessages[] = "⚠️ <b>Beban JP Berlebih:</b> {$namaMapelStr} di {$kelasTxt} (Diisi {$plotCount[$rombelId][$subjectKey]} JP, padahal target maksimal hanya {$batasJp} JP).";
                    continue; 
                }

                // VALIDASI 2: Cek Guru Bentrok
                if (!empty($teacherId)) {
                    if (isset($teacherSlotCheck[$slotId][$teacherId])) {
                        $kelasAwal = $rombelDict[$teacherSlotCheck[$slotId][$teacherId]];
                        $kelasTujuan = $rombelDict[$rombelId];
                        $namaGuru = $teacherDict[$teacherId] ?? 'Guru Ybs';
                        $bentrokMessages[] = "⛔ <b>Guru Bentrok:</b> {$namaGuru} pada {$slotDict[$slotId]} (Mengajar di {$kelasAwal} & {$kelasTujuan} bersamaan).";
                        continue; 
                    }
                    $teacherSlotCheck[$slotId][$teacherId] = $rombelId;
                }
                
                $insertData[] = $row;
            }
        }

        // --- 3. EKSEKUSI DATABASE ---
        $db->transStart();
        $db->table('class_schedules')->where(['version_id' => $v])->delete();
        if (!empty($insertData)) {
            $db->table('class_schedules')->insertBatch($insertData);
        }
        $db->transComplete();

        if (!empty($bentrokMessages)) {
            $pesan = "Ada jadwal yang otomatis dibatalkan karena menyalahi aturan:<br><ul class='mb-0 mt-1' style='font-size:13px;'>";
            foreach(array_unique($bentrokMessages) as $msg) { $pesan .= "<li>$msg</li>"; }
            $pesan .= "</ul>";
            return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('error', $pesan);
        }

        return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '✅ Matriks Jadwal berhasil disimpan dengan sempurna!');
    }

    // ==========================================
    // FUNGSI SIMPAN TAB 2 (PLOTTING JP & GABUNGAN)
    // ==========================================
    public function savePlotting()
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getPost('ta'); $v = $this->request->getPost('v');
        $mapelActive = $this->request->getPost('mapel_active'); $targetJp = $this->request->getPost('target_jp'); $teacherId = $this->request->getPost('teacher_id'); 
        $combinedActive = $this->request->getPost('combined_active'); $targetJpCombined = $this->request->getPost('target_jp_combined'); $teacherIdCombined = $this->request->getPost('teacher_id_combined'); 

        $db->table('schedule_jp_targets')->where('version_id', $v)->delete();
        $insertData = [];

        if (!empty($mapelActive) && is_array($mapelActive)) {
            foreach ($mapelActive as $subjectId => $val) {
                if (isset($targetJp[$subjectId])) {
                    foreach ($targetJp[$subjectId] as $rombelId => $jp) {
                        $tId = $teacherId[$subjectId][$rombelId] ?? null;
                        if ($jp > 0 && !empty($tId)) { $insertData[] = ['academic_year_id' => $ta, 'version_id' => $v, 'rombel_id' => $rombelId, 'subject_id' => $subjectId, 'combined_subject_id' => null, 'teacher_id' => $tId, 'target_jp' => $jp ]; }
                    }
                }
            }
        }

        if (!empty($combinedActive) && is_array($combinedActive)) {
            foreach ($combinedActive as $combId => $val) {
                if (isset($targetJpCombined[$combId])) {
                    foreach ($targetJpCombined[$combId] as $rombelId => $jp) {
                        $tId = $teacherIdCombined[$combId][$rombelId] ?? null;
                        if ($jp > 0 && !empty($tId)) { $insertData[] = ['academic_year_id' => $ta, 'version_id' => $v, 'rombel_id' => $rombelId, 'subject_id' => null, 'combined_subject_id' => $combId, 'teacher_id' => $tId, 'target_jp' => $jp ]; }
                    }
                }
            }
        }

        if (!empty($insertData)) $db->table('schedule_jp_targets')->insertBatch($insertData);
        return redirect()->to(base_url("admin/schedule?tab=plotting&ta=$ta&v=$v"))->with('sukses', '🎯 Plotting Beban JP berhasil disimpan!');
    }

    private function validateSameTeacher($subjectIds)
    {
        $db = \Config\Database::connect();
        $cstData = $db->table('class_subject_teachers')->whereIn('master_subject_id', $subjectIds)->get()->getResultArray();
        $teacherCheck = [];
        foreach($cstData as $row) { if(!empty($row['teacher_id'])) { $teacherCheck[$row['rombel_id']][] = $row['teacher_id']; } }
        foreach($teacherCheck as $rombelId => $teachers) { if(count(array_unique($teachers)) > 1) return false; }
        return true;
    }

    public function saveCombined()
    {
        try {
            $db = \Config\Database::connect();
            $ta = $this->request->getPost('ta'); $v = $this->request->getPost('v');
            $name = $this->request->getPost('combined_name'); $subjectIds = $this->request->getPost('subject_ids'); 
            if (empty($name) || empty($subjectIds)) return redirect()->back()->with('error', 'Minimal 1 mapel harus dicentang.');
            if (!$this->validateSameTeacher($subjectIds)) return redirect()->back()->with('error', '⚠️ GAGAL MENGGABUNGKAN: Guru pengampu harus sama di setiap kelas.');
            $db->table('schedule_combined_subjects')->insert(['academic_year_id' => $ta, 'combined_name' => $name]);
            $newId = $db->insertID();
            $insertDetails = []; foreach ($subjectIds as $sId) { $insertDetails[] = ['combined_subject_id' => $newId, 'master_subject_id' => $sId]; }
            $db->table('schedule_combined_details')->insertBatch($insertDetails);
            return redirect()->to(base_url("admin/schedule?tab=plotting&ta=$ta&v=$v"))->with('sukses', '🔗 Mapel Gabungan ('.esc($name).') berhasil dibuat!');
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Error: ' . $e->getMessage()); }
    }

    public function updateCombined()
    {
        try {
            $db = \Config\Database::connect();
            $ta = $this->request->getPost('ta'); $v = $this->request->getPost('v'); $id = $this->request->getPost('id');
            $name = $this->request->getPost('combined_name'); $subjectIds = $this->request->getPost('subject_ids'); 
            if (empty($name) || empty($subjectIds)) return redirect()->back()->with('error', 'Minimal 1 mapel harus dicentang.');
            if (!$this->validateSameTeacher($subjectIds)) return redirect()->back()->with('error', '⚠️ GAGAL MENGGABUNGKAN: Guru pengampu harus sama di setiap kelas.');
            $db->table('schedule_combined_subjects')->where('id', $id)->update(['combined_name' => $name]);
            $db->table('schedule_combined_details')->where('combined_subject_id', $id)->delete();
            $insertDetails = []; foreach ($subjectIds as $sId) { $insertDetails[] = ['combined_subject_id' => $id, 'master_subject_id' => $sId]; }
            $db->table('schedule_combined_details')->insertBatch($insertDetails);
            return redirect()->to(base_url("admin/schedule?tab=plotting&ta=$ta&v=$v"))->with('sukses', '✏️ Mapel Gabungan berhasil diperbarui!');
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Error: ' . $e->getMessage()); }
    }

    public function deleteCombined($id)
    {
        $db = \Config\Database::connect();
        $db->table('schedule_combined_details')->where('combined_subject_id', $id)->delete();
        $db->table('schedule_combined_subjects')->where('id', $id)->delete();
        $ta = $this->request->getGet('ta'); $v = $this->request->getGet('v');
        return redirect()->to(base_url("admin/schedule?tab=plotting&ta=$ta&v=$v"))->with('sukses', '🗑️ Mapel Gabungan berhasil dihapus.');
    }

    public function createVersion() 
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getPost('ta');
        
        $db->table('schedule_versions')->insert([
            'academic_year_id' => $ta,
            'version_name'     => $this->request->getPost('version_name'),
            'schedule_title'   => $this->request->getPost('schedule_title'),
            'is_active'        => 1
        ]);
        
        $newId = $db->insertID();
        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$newId"))->with('sukses', '✅ Versi jadwal baru berhasil dibuat!');
    }

    public function generateTime()
    {
        $db = \Config\Database::connect();
        
        $days = (array) $this->request->getPost('day_names'); 
        $start = $this->request->getPost('start_time'); 
        $interval = (int)$this->request->getPost('interval_minutes');
        $total = (int)$this->request->getPost('total_slots');
        
        $ta = $this->request->getPost('ta'); 
        $v = (int)$this->request->getPost('v');
        
        if (empty($days) || empty($v)) {
            return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))
                             ->with('error', 'Gagal Generate: Data Versi Jadwal tidak terdeteksi oleh sistem.');
        }

        foreach ($days as $day) {
            $db->table('schedule_time_slots')->where('version_id', $v)->where('day_name', $day)->delete();
            $currentStartTime = strtotime($start);
            
            for ($i = 1; $i <= $total; $i++) {
                $endTime = strtotime("+$interval minutes", $currentStartTime);
                $db->table('schedule_time_slots')->insert([
                    'version_id'  => $v,
                    'day_name'    => $day,
                    'slot_number' => $i,
                    'slot_label'  => "Jam Ke-" . $i,
                    'start_time'  => date('H:i', $currentStartTime),
                    'end_time'    => date('H:i', $endTime),
                    'is_break'    => 0
                ]);
                $currentStartTime = $endTime;
            }
        }

        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('sukses', "✅ Slot waktu berhasil di-generate.");
    }

    public function updateTime()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $duration = (int)$this->request->getPost('duration_minutes');
        $label = $this->request->getPost('slot_label');
        
        $ta = $this->request->getPost('ta');
        $v = $this->request->getPost('v');

        $slot = $db->table('schedule_time_slots')->where('id', $id)->get()->getRowArray();
        if (!$slot) return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('error', 'Data slot tidak ditemukan.');

        $startTime = strtotime($slot['start_time']);
        $newEndTime = strtotime("+$duration minutes", $startTime);

        // Update tanpa is_break
        $db->table('schedule_time_slots')->where('id', $id)->update([
            'slot_label' => $label,
            'end_time'   => date('H:i', $newEndTime)
        ]);

        $subsequentSlots = (array) $db->table('schedule_time_slots')
                              ->where('version_id', $v)
                              ->where('day_name', $slot['day_name'])
                              ->where('slot_number >', $slot['slot_number'])
                              ->orderBy('slot_number', 'ASC')
                              ->get()->getResultArray();

        $prevEndTime = $newEndTime;
        foreach ($subsequentSlots as $s) {
            $oldDuration = (strtotime($s['end_time']) - strtotime($s['start_time'])) / 60; 
            $newSEnd = strtotime("+$oldDuration minutes", $prevEndTime);
            $db->table('schedule_time_slots')->where('id', $s['id'])->update(['start_time' => date('H:i', $prevEndTime), 'end_time' => date('H:i', $newSEnd)]);
            $prevEndTime = $newSEnd;
        }

        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('sukses', "✅ Slot diperbarui dan waktu otomatis bergeser!");
    }

    public function deleteSlotTime($id) 
    {
        $db = \Config\Database::connect();
        $db->table('schedule_time_slots')->where('id', $id)->delete();
        $ta = $this->request->getGet('ta'); $v = $this->request->getGet('v');
        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('sukses', '🗑️ Satu baris waktu berhasil dihapus.');
    }

    public function deleteDayTime($day)
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getGet('ta'); $v = $this->request->getGet('v');
        $db->table('schedule_time_slots')->where('version_id', $v)->where('day_name', urldecode($day))->delete();
        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('sukses', "🗑️ Slot hari " . urldecode($day) . " dihapus.");
    }

    public function resetAllSlots()
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getGet('ta'); $v = $this->request->getGet('v');
        
        // Hapus HANYA slot pada versi yang aktif
        $db->table('schedule_time_slots')->where('version_id', $v)->delete();
        return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('sukses', '🗑️ Seluruh data slot pada versi ini berhasil dihapus!');
    }

    // ==========================================
    // FUNGSI MANAJEMEN KEGIATAN UMUM (DINAMIS)
    // ==========================================
    public function saveActivity()
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getPost('ta'); 
        $v = $this->request->getPost('v');
        $name = $this->request->getPost('activity_name');
        
        if (empty($name)) return redirect()->back()->with('error', 'Nama kegiatan tidak boleh kosong.');
        
        $db->table('master_activities')->insert(['activity_name' => $name]);
        return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '✨ Kegiatan Umum berhasil ditambahkan!');
    }

    public function updateActivity()
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getPost('ta'); 
        $v = $this->request->getPost('v');
        $id = $this->request->getPost('id'); 
        $name = $this->request->getPost('activity_name');
        
        if (empty($name)) return redirect()->back()->with('error', 'Nama kegiatan tidak boleh kosong.');
        
        $db->table('master_activities')->where('id', $id)->update(['activity_name' => $name]);
        return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '✨ Kegiatan Umum berhasil diperbarui!');
    }

    public function deleteActivity($id)
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getGet('ta'); 
        $v = $this->request->getGet('v');
        
        // Hapus kegiatannya
        $db->table('master_activities')->where('id', $id)->delete();
        
        // Bersihkan jadwal yang terlanjur memakai kegiatan ini agar tidak error
        if ($db->tableExists('class_schedules')) {
            $db->table('class_schedules')->where('activity_id', $id)->update(['activity_id' => null]);
        }
        
        return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '🗑️ Kegiatan Umum berhasil dihapus!');
    }

    // ==========================================
    // FUNGSI HAPUS & COPY VERSI (LINTAS SEMESTER)
    // ==========================================
    public function deleteVersion($id)
    {
        $db = \Config\Database::connect();
        $ta = $this->request->getGet('ta');

        $db->transStart();
        $db->table('class_schedules')->where('version_id', $id)->delete();
        $db->table('schedule_jp_targets')->where('version_id', $id)->delete();
        $db->table('schedule_time_slots')->where('version_id', $id)->delete();
        $db->table('schedule_versions')->where('id', $id)->delete();
        $db->transComplete();

        if ($db->transStatus() === false) return redirect()->back()->with('error', 'Gagal menghapus versi jadwal.');
        return redirect()->to(base_url("admin/schedule?ta=$ta"))->with('sukses', '🗑️ Versi jadwal beserta seluruh isinya berhasil dihapus permanen.');
    }

    public function copyVersion()
    {
        $db = \Config\Database::connect();
        $sourceVersionId = $this->request->getPost('source_version_id');
        $targetTaId = $this->request->getPost('target_ta_id'); 
        $newVersionName = $this->request->getPost('new_version_name');
        $newScheduleTitle = $this->request->getPost('new_schedule_title');

        if (empty($sourceVersionId) || empty($targetTaId) || empty($newVersionName)) {
            return redirect()->back()->with('error', 'Data tidak lengkap untuk melakukan copy.');
        }

        $sourceVersion = $db->table('schedule_versions')->where('id', $sourceVersionId)->get()->getRowArray();
        if (!$sourceVersion) return redirect()->back()->with('error', 'Versi sumber tidak ditemukan.');

        $sourceTaId = $sourceVersion['academic_year_id'];
        $db->transStart();

        // 1. Buat Versi Baru di Semester Target
        $db->table('schedule_versions')->insert([ 'academic_year_id' => $targetTaId, 'version_name' => $newVersionName, 'schedule_title' => $newScheduleTitle, 'is_active' => 1 ]);
        $newVersionId = $db->insertID();

        // 2. Duplikasi Slot Waktu (Jam Ke-)
        $timeSlots = $db->table('schedule_time_slots')->where('version_id', $sourceVersionId)->get()->getResultArray();
        $slotMapping = []; // Menyimpan ID Slot Lama => ID Slot Baru
        foreach ($timeSlots as $ts) {
            $db->table('schedule_time_slots')->insert([ 'version_id' => $newVersionId, 'day_name' => $ts['day_name'], 'slot_number' => $ts['slot_number'], 'slot_label' => $ts['slot_label'], 'start_time' => $ts['start_time'], 'end_time' => $ts['end_time'] ]);
            $slotMapping[$ts['id']] = $db->insertID();
        }

        // --- MAPPING PINTAR LINTAS SEMESTER (ROMBEL & MAPEL GABUNGAN) ---
        $rombelMapping = [];
        $combMapping = [];
        
        if ($sourceTaId != $targetTaId) {
            // Pencocokan Rombel (Berdasarkan Nama)
            $oldRombels = $db->table('class_rombel')->where('academic_year_id', $sourceTaId)->get()->getResultArray();
            $newRombels = $db->table('class_rombel')->where('academic_year_id', $targetTaId)->get()->getResultArray();
            foreach ($oldRombels as $old) { foreach ($newRombels as $new) { if ($old['rombel_name'] == $new['rombel_name']) { $rombelMapping[$old['id']] = $new['id']; break; } } }

            // Pencocokan Mapel Gabungan
            if ($db->tableExists('schedule_combined_subjects')) {
                $oldCombs = $db->table('schedule_combined_subjects')->where('academic_year_id', $sourceTaId)->get()->getResultArray();
                $newCombs = $db->table('schedule_combined_subjects')->where('academic_year_id', $targetTaId)->get()->getResultArray();
                foreach ($oldCombs as $old) {
                    $found = false;
                    foreach ($newCombs as $new) { if (strtolower($old['combined_name']) == strtolower($new['combined_name'])) { $combMapping[$old['id']] = $new['id']; $found = true; break; } }
                    if (!$found) {
                        $db->table('schedule_combined_subjects')->insert(['academic_year_id' => $targetTaId, 'combined_name' => $old['combined_name']]);
                        $newCombId = $db->insertID();
                        $combMapping[$old['id']] = $newCombId;
                        $details = $db->table('schedule_combined_details')->where('combined_subject_id', $old['id'])->get()->getResultArray();
                        $newDetails = []; foreach ($details as $d) { $newDetails[] = ['combined_subject_id' => $newCombId, 'master_subject_id' => $d['master_subject_id']]; }
                        if(!empty($newDetails)) $db->table('schedule_combined_details')->insertBatch($newDetails);
                    }
                }
            }
        } else {
            // Jika Semester Sama, ID tetap
            $oldRombels = $db->table('class_rombel')->where('academic_year_id', $sourceTaId)->get()->getResultArray(); foreach ($oldRombels as $old) { $rombelMapping[$old['id']] = $old['id']; }
            if ($db->tableExists('schedule_combined_subjects')) { $oldCombs = $db->table('schedule_combined_subjects')->where('academic_year_id', $sourceTaId)->get()->getResultArray(); foreach ($oldCombs as $old) { $combMapping[$old['id']] = $old['id']; } }
        }

        // 3. Duplikasi Plotting Beban JP Target
        $targets = $db->table('schedule_jp_targets')->where('version_id', $sourceVersionId)->get()->getResultArray();
        $newTargets = [];
        foreach ($targets as $t) {
            if (!isset($rombelMapping[$t['rombel_id']])) continue;
            $newCombId = !empty($t['combined_subject_id']) && isset($combMapping[$t['combined_subject_id']]) ? $combMapping[$t['combined_subject_id']] : null;
            $newTargets[] = [ 'academic_year_id' => $targetTaId, 'version_id' => $newVersionId, 'rombel_id' => $rombelMapping[$t['rombel_id']], 'subject_id' => $t['subject_id'], 'combined_subject_id' => $newCombId, 'teacher_id' => $t['teacher_id'], 'target_jp' => $t['target_jp'] ];
        }
        if (!empty($newTargets)) $db->table('schedule_jp_targets')->insertBatch($newTargets);

        // 4. Duplikasi Papan Catur Matriks
        $schedules = $db->table('class_schedules')->where('version_id', $sourceVersionId)->get()->getResultArray();
        $newSchedules = [];
        foreach ($schedules as $sch) {
            if (!isset($rombelMapping[$sch['rombel_id']]) || !isset($slotMapping[$sch['slot_id']])) continue;
            $newCombId = !empty($sch['combined_subject_id']) && isset($combMapping[$sch['combined_subject_id']]) ? $combMapping[$sch['combined_subject_id']] : null;
            $newSchedules[] = [ 'academic_year_id' => $targetTaId, 'version_id' => $newVersionId, 'rombel_id' => $rombelMapping[$sch['rombel_id']], 'day_name' => $sch['day_name'], 'slot_id' => $slotMapping[$sch['slot_id']], 'subject_id' => $sch['subject_id'], 'combined_subject_id' => $newCombId, 'teacher_id' => $sch['teacher_id'], 'activity_id' => $sch['activity_id'] ];
        }
        if (!empty($newSchedules)) $db->table('class_schedules')->insertBatch($newSchedules);

        $db->transComplete();

        if ($db->transStatus() === false) return redirect()->back()->with('error', 'Gagal menyalin jadwal.');
        return redirect()->to(base_url("admin/schedule?ta=$targetTaId&v=$newVersionId"))->with('sukses', '♻️ Ajaib! Seluruh Waktu, Plotting, dan Matriks Kelas berhasil di-copy ke semester/versi ini.');
    }

    // ==========================================
    // FUNGSI AUTO-GENERATE CERDAS (PREVIEW LAYAR)
    // ==========================================
    public function autoGenerateMatrix()
    {
        $ta = $this->request->getPost('ta');
        $v = $this->request->getPost('v');
        $matrix = $this->request->getPost('matrix'); // BACA DARI LAYAR SAAT INI (Bukan DB)
        $db = \Config\Database::connect();

        $slots = $db->table('schedule_time_slots')->where('version_id', $v)->orderBy('slot_number', 'ASC')->get()->getResultArray();
        $slotsByDay = []; 
        foreach ($slots as $s) { $slotsByDay[$s['day_name']][] = $s; }

        $teacherSlots = []; $rombelSlots = []; $teacherDaily = []; $rombelDailySubject = [];
        $usedJpDict = [];

        // 1. PETAKAN JADWAL YANG SEDANG ADA DI LAYAR (Termasuk Kegiatan Umum yg belum disave)
        if (!empty($matrix)) {
            foreach ($matrix as $slotId => $rombelsData) {
                foreach ($rombelsData as $rombelId => $val) {
                    if (empty($val)) continue;
                    
                    $dayName = '';
                    foreach($slots as $s) { if($s['id'] == $slotId) { $dayName = $s['day_name']; break; } }
                    
                    // Kunci slot ini agar tidak diisi mapel lain
                    $rombelSlots[$slotId][$rombelId] = true;

                    if (strpos($val, 'SUB_') === 0 || strpos($val, 'COM_') === 0) {
                        $parts = explode('_', $val);
                        $type = $parts[0]; $subjectId = $parts[1]; $teacherId = $parts[2] ?? null;
                        
                        $key = $type . '_' . $subjectId;
                        $usedJpDict[$rombelId][$key] = ($usedJpDict[$rombelId][$key] ?? 0) + 1;
                        $rombelDailySubject[$dayName][$rombelId][$key] = true;
                        
                        if (!empty($teacherId)) {
                            $teacherSlots[$slotId][$teacherId] = $rombelId;
                            $teacherDaily[$dayName][$teacherId] = ($teacherDaily[$dayName][$teacherId] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        // 2. HITUNG SISA TARGET JP YANG BELUM MASUK KE LAYAR
        $targets = $db->table('schedule_jp_targets')->where('version_id', $v)->get()->getResultArray();
        $remainingTargets = [];
        foreach ($targets as $t) {
            $key = !empty($t['subject_id']) ? 'SUB_' . $t['subject_id'] : 'COM_' . $t['combined_subject_id'];
            $used = $usedJpDict[$t['rombel_id']][$key] ?? 0;
            $sisa = $t['target_jp'] - $used;
            if ($sisa > 0) {
                $remainingTargets[] = [
                    'rombel_id' => $t['rombel_id'], 'type' => !empty($t['subject_id']) ? 'SUB' : 'COM',
                    'subject_id' => !empty($t['subject_id']) ? $t['subject_id'] : $t['combined_subject_id'],
                    'teacher_id' => $t['teacher_id'], 'sisa' => $sisa
                ];
            }
        }

        // Urutkan mapel dengan sisa JP terbesar agar diproses duluan
        usort($remainingTargets, function($a, $b) { return $b['sisa'] <=> $a['sisa']; });

        $generatedData = [];

        // 3. EKSEKUSI PENCARIAN SLOT KOSONG
        foreach ($remainingTargets as $rt) {
            $sisa = $rt['sisa'];
            $blocks = [];
            
            if ($sisa == 4) $blocks = [2, 2];
            elseif ($sisa == 3) $blocks = [3];
            elseif ($sisa == 5) $blocks = [3, 2];
            elseif ($sisa == 6) $blocks = [2, 2, 2];
            else {
                while ($sisa >= 3) { $blocks[] = 3; $sisa -= 3; }
                if ($sisa == 2) { $blocks[] = 2; $sisa -= 2; }
                if ($sisa == 1) { $blocks[] = 1; $sisa -= 1; }
            }

            foreach ($blocks as $blockSize) {
                $this->placeBlock($blockSize, $rt['subject_id'], $rt['teacher_id'], $rt['rombel_id'], $rt['type'], 
                                  $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData);
            }
        }

        // 4. KEMBALIKAN DATA KE LAYAR SEBAGAI JSON (TIDAK MASUK DATABASE!)
        return $this->response->setJSON(['status' => 'success', 'data' => $generatedData]);
    }

    private function placeBlock($blockSize, $subjectId, $teacherId, $rombelId, $type, &$teacherSlots, &$rombelSlots, &$teacherDaily, &$rombelDailySubject, $slotsByDay, &$generatedData) 
    {
        $primaryDays = ['Senin', 'Selasa', 'Rabu', 'Kamis']; 
        $allDays = array_keys($slotsByDay);

        $placed = $this->findAndBookSlot($primaryDays, $blockSize, $subjectId, $teacherId, $rombelId, $type, $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData, true);
        if (!$placed) $placed = $this->findAndBookSlot($allDays, $blockSize, $subjectId, $teacherId, $rombelId, $type, $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData, true);
        if (!$placed) $placed = $this->findAndBookSlot($allDays, $blockSize, $subjectId, $teacherId, $rombelId, $type, $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData, false);
        
        // Pecah paksa jika slot penuh
        if (!$placed && $blockSize > 1) {
            $this->placeBlock($blockSize - 1, $subjectId, $teacherId, $rombelId, $type, $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData);
            $this->placeBlock(1, $subjectId, $teacherId, $rombelId, $type, $teacherSlots, $rombelSlots, $teacherDaily, $rombelDailySubject, $slotsByDay, $generatedData);
            return true; 
        }
        return $placed;
    }

    private function findAndBookSlot($days, $blockSize, $subjectId, $teacherId, $rombelId, $type, &$teacherSlots, &$rombelSlots, &$teacherDaily, &$rombelDailySubject, $slotsByDay, &$generatedData, $strictDifferentDay)
    {
        $bestDay = null; $bestStartIndex = -1; $minTeacherLoad = 999;
        $subjectKey = $type . '_' . $subjectId;

        foreach ($days as $day) {
            if (!isset($slotsByDay[$day])) continue;
            if ($strictDifferentDay && isset($rombelDailySubject[$day][$rombelId][$subjectKey])) continue;

            $daySlots = $slotsByDay[$day];
            $teacherLoad = $teacherDaily[$day][$teacherId] ?? 0;
            
            for ($i = 0; $i <= count($daySlots) - $blockSize; $i++) {
                $canFit = true;
                for ($j = 0; $j < $blockSize; $j++) {
                    $s = $daySlots[$i + $j];
                    if (isset($rombelSlots[$s['id']][$rombelId])) { $canFit = false; break; }
                    if (!empty($teacherId) && isset($teacherSlots[$s['id']][$teacherId])) { $canFit = false; break; }
                }

                if ($canFit && $teacherLoad < $minTeacherLoad) {
                    $minTeacherLoad = $teacherLoad;
                    $bestDay = $day;
                    $bestStartIndex = $i;
                }
            }
        }

        if ($bestDay !== null) {
            $daySlots = $slotsByDay[$bestDay];
            $val = $type . '_' . $subjectId . '_' . $teacherId;

            for ($j = 0; $j < $blockSize; $j++) {
                $s = $daySlots[$bestStartIndex + $j];
                
                $rombelSlots[$s['id']][$rombelId] = true;
                if (!empty($teacherId)) {
                    $teacherSlots[$s['id']][$teacherId] = $rombelId;
                    $teacherDaily[$bestDay][$teacherId] = ($teacherDaily[$bestDay][$teacherId] ?? 0) + 1;
                }
                $rombelDailySubject[$bestDay][$rombelId][$subjectKey] = true;
                
                $generatedData[] = [
                    'slot_id' => $s['id'],
                    'rombel_id' => $rombelId,
                    'value' => $val
                ];
            }
            return true;
        }
        return false;
    }

}