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
            // Variabel Khusus Tab 1
            'classSchedules'       => $classSchedules,
            'usedJpNormal'         => $usedJpNormal,
            'usedJpCombined'       => $usedJpCombined,
            'slotGrid'             => $slotGrid,
            'maxSlot'              => $maxSlot,
            'matrixDays'           => $matrixDays
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

        // 1. Kamus untuk pesan error
        $slots = $db->table('schedule_time_slots')->where('version_id', $v)->get()->getResultArray();
        $slotDict = []; foreach($slots as $s) { $slotDict[$s['id']] = $s['day_name'] . ' (Jam Ke-' . $s['slot_number'] . ')'; }
        $teachers = $db->table('users')->get()->getResultArray(); 
        $teacherDict = []; foreach($teachers as $t) { $teacherDict[$t['id']] = $t['username'] ?? 'Guru ID '.$t['id']; }
        $rombels = $db->table('class_rombel cr')->select('cr.id, cr.rombel_name, mc.class_name')->join('master_classes mc', 'mc.id = cr.master_class_id')->get()->getResultArray();
        $rombelDict = []; foreach($rombels as $r) { $rombelDict[$r['id']] = $r['class_name'] . '-' . $r['rombel_name']; }

        // 2. PROSES VALIDASI
        $insertData = [];
        $teacherSlotCheck = []; // [slot_id][teacher_id] = rombel_id
        $bentrokMessages = [];

        foreach ($matrix as $slotId => $rombelsData) {
            foreach($rombelsData as $rombelId => $val) {
                if (empty($val)) continue;
                
                // Jika Kegiatan Umum -> Lanjut, tidak perlu dicek bentrok
                if (strpos($val, 'ACT_') === 0) {
                    $insertData[] = [ 'academic_year_id' => $ta, 'version_id' => $v, 'rombel_id' => $rombelId, 'slot_id' => $slotId, 'activity_id' => str_replace('ACT_', '', $val) ];
                    continue;
                }

                // Jika Mapel/Gabungan -> Harus cek Guru
                $teacherId = null;
                $row = [ 'academic_year_id' => $ta, 'version_id' => $v, 'rombel_id' => $rombelId, 'slot_id' => $slotId ];

                if (strpos($val, 'SUB_') === 0) {
                    $parts = explode('_', str_replace('SUB_', '', $val));
                    $row['subject_id'] = $parts[0];
                    $teacherId = $parts[1] ?? null;
                } elseif (strpos($val, 'COM_') === 0) {
                    $parts = explode('_', str_replace('COM_', '', $val));
                    $row['combined_subject_id'] = $parts[0];
                    $teacherId = $parts[1] ?? null;
                }
                $row['teacher_id'] = $teacherId;

                // VALIDASI: Cek apakah guru ini sudah mengajar di kelas lain pada jam (slot) yang sama
                if (!empty($teacherId)) {
                    if (isset($teacherSlotCheck[$slotId][$teacherId])) {
                        $kelasAwal = $rombelDict[$teacherSlotCheck[$slotId][$teacherId]];
                        $kelasTujuan = $rombelDict[$rombelId];
                        $bentrokMessages[] = "<b>{$teacherDict[$teacherId]}</b> bentrok di {$slotDict[$slotId]} (Mengajar di {$kelasAwal} & {$kelasTujuan})";
                        continue; 
                    }
                    $teacherSlotCheck[$slotId][$teacherId] = $rombelId;
                }
                $insertData[] = $row;
            }
        }

        $db->transStart();
        $db->table('class_schedules')->where(['version_id' => $v])->delete();
        if (!empty($insertData)) $db->table('class_schedules')->insertBatch($insertData);
        $db->transComplete();

        if (!empty($bentrokMessages)) {
            return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('error', "⚠️ Bentrok terdeteksi:<br>".implode("<br>", array_unique($bentrokMessages)));
        }
        return redirect()->to(base_url("admin/schedule?tab=matriks&ta=$ta&v=$v"))->with('sukses', '✅ Matriks tersimpan!');
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

}