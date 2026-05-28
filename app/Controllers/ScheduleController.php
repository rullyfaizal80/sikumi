<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ScheduleController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $daftarTahun = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();
        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) {
            $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray();
        } 
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
            if (!empty($selectedVersionId)) {
                $activeVersion = $db->table('schedule_versions')->where('id', $selectedVersionId)->get()->getRowArray();
            }
            if (empty($activeVersion) && !empty($versions)) {
                $activeVersion = $versions[0]; 
            }
        }

        $rombels = [];
        if ($tahunAktif) {
            $rombels = $db->table('class_rombel cr')
                          ->select('cr.*, mc.class_name, mc.level_type')
                          ->join('master_classes mc', 'mc.id = cr.master_class_id')
                          ->where('cr.academic_year_id', $tahunAktif['id'])
                          ->orderBy('mc.id', 'ASC')
                          ->orderBy('cr.rombel_name', 'ASC')
                          ->get()->getResultArray();
        }

        $kegiatan = $db->table('master_activities')->get()->getResultArray();

        // PENARIKAN DATA NORMAL (Tanpa Mode Diagnostik Kuning)
        $timeSlots = [];
        if ($activeVersion) {
            $timeSlots = $db->table('schedule_time_slots')
                            ->where('version_id', $activeVersion['id'])
                            ->orderBy('day_name', 'ASC')
                            ->orderBy('slot_number', 'ASC')
                            ->get()->getResultArray();
        }

        $data = [
            'title'            => 'Manajemen Jadwal - SiKuMi',
            'daftarTahun'      => $daftarTahun,
            'tahunAktif'       => $tahunAktif,
            'activeTab'        => $activeTab,
            'rombels'          => $rombels,
            'kegiatan'         => $kegiatan,
            'versions'         => $versions,
            'activeVersion'    => $activeVersion,
            'timeSlots'        => $timeSlots
        ];

        return view('admin/schedule/index', $data);
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
        $isBreak = $this->request->getPost('is_break') ? 1 : 0;
        $ta = $this->request->getPost('ta');
        $v = $this->request->getPost('v');

        $slot = $db->table('schedule_time_slots')->where('id', $id)->get()->getRowArray();
        if (!$slot) return redirect()->to(base_url("admin/schedule?tab=waktu&ta=$ta&v=$v"))->with('error', 'Data slot tidak ditemukan.');

        $startTime = strtotime($slot['start_time']);
        $newEndTime = strtotime("+$duration minutes", $startTime);

        $db->table('schedule_time_slots')->where('id', $id)->update([
            'slot_label' => $label,
            'end_time'   => date('H:i', $newEndTime),
            'is_break'   => $isBreak
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
}