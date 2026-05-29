<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AnalysisController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // =====================================================================
        // 🚦 1. DETEKSI HAK AKSES CERDAS
        // =====================================================================
        $uri = $this->request->getUri();
        $segment = $uri->getSegment(1); 
        $isGuru = (strtolower($segment) === 'guru');
        $displayRole = $isGuru ? 'GURU' : 'ADMIN/WAKA';

        $userId = null;
        if (function_exists('user_id')) { $userId = user_id(); }
        elseif (session()->has('user_id')) { $userId = session()->get('user_id'); }
        elseif (session()->has('id')) { $userId = session()->get('id'); }

        // =====================================================================
        // 🕵️‍♂️ 2. AUTO-DETEKSI TABEL & KOLOM DATABASE
        // =====================================================================
        $csFields = $db->getFieldNames('class_schedules');
        $kolomIdGuruDiJadwal = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
        $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';

        $tabelGuru = 'users'; 
        if ($db->tableExists('master_teachers')) $tabelGuru = 'master_teachers';
        elseif ($db->tableExists('guru')) $tabelGuru = 'guru';
        
        $guruFields = $db->getFieldNames($tabelGuru);
        $kolomNamaGuru = 'username';
        foreach (['nama_guru', 'nama', 'fullname', 'name', 'nama_lengkap'] as $f) {
            if (in_array($f, $guruFields)) { $kolomNamaGuru = $f; break; }
        }

        $tabelMapel = 'master_subjects';
        if (!$db->tableExists($tabelMapel)) {
            $tabelMapel = $db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran';
        }
        $mapelFields = $db->getFieldNames($tabelMapel);
        $kolomNamaMapel = 'subject_name';
        foreach (['nama_mapel', 'name', 'mapel'] as $f) {
            if (in_array($f, $mapelFields)) { $kolomNamaMapel = $f; break; }
        }

        // =====================================================================
        // 🚀 3. PROSES DATA JADWAL & HEB
        // =====================================================================
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$tahunAktif) return redirect()->back()->with('error', 'Tidak ada Tahun Ajaran aktif.');

        $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        if (!$jadwalAktif) return redirect()->back()->with('error', 'Belum ada Jadwal Pelajaran yang diaktifkan.');

        $teachers = [];
        $rawTeachers = $db->table('class_schedules cs')
                          ->select("cs.{$kolomIdGuruDiJadwal} as id, g.{$kolomNamaGuru} as nama_guru")
                          ->join("{$tabelGuru} g", "g.id = cs.{$kolomIdGuruDiJadwal}", 'left')
                          ->where('cs.version_id', $jadwalAktif['id'])
                          ->where("cs.{$kolomIdGuruDiJadwal} IS NOT NULL")
                          ->groupBy("cs.{$kolomIdGuruDiJadwal}")
                          ->get()->getResultArray();

        foreach($rawTeachers as $t) {
            $teachers[] = [
                'id' => $t['id'],
                'nama_guru' => $t['nama_guru'] ?? 'Guru (ID: '.$t['id'].')'
            ];
        }

        $selectedTeacherId = $this->request->getGet('teacher_id');
        if ($isGuru) { $selectedTeacherId = $userId; } 
        elseif (empty($selectedTeacherId) && !empty($teachers)) { $selectedTeacherId = $teachers[0]['id']; }

        $rombelOptions = []; 
        $subjectOptions = [];
        $selectedRombelId = $this->request->getGet('rombel_id');
        $selectedSubjectId = $this->request->getGet('subject_id');

        if ($selectedTeacherId) {
            // PERBAIKAN: Join ke tabel master_subjects DAN schedule_combined_subjects
            $teacherTargets = $db->table('class_schedules cs')
                                 ->select("cs.rombel_id, cs.{$kolomSubjectId}, cs.combined_subject_id, r.rombel_name, r.master_class_id, s.{$kolomNamaMapel} as subject_name, c.combined_name")
                                 ->join('class_rombel r', 'r.id = cs.rombel_id')
                                 ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                                 ->join('schedule_combined_subjects c', 'c.id = cs.combined_subject_id', 'left') // <- Tarik nama mapel gabungan
                                 ->where('cs.version_id', $jadwalAktif['id'])
                                 ->where("cs.{$kolomIdGuruDiJadwal}", $selectedTeacherId)
                                 ->groupStart()
                                    ->where("cs.{$kolomSubjectId} IS NOT NULL")
                                    ->orWhere('cs.combined_subject_id IS NOT NULL')
                                 ->groupEnd()
                                 ->groupBy("cs.rombel_id, cs.{$kolomSubjectId}, cs.combined_subject_id")
                                 ->get()->getResultArray();

            foreach ($teacherTargets as $tgt) {
                $rombelOptions[$tgt['rombel_id']] = [
                    'rombel_name' => $tgt['rombel_name'],
                    'master_class_id' => $tgt['master_class_id']
                ];
                
                if ($selectedRombelId && $tgt['rombel_id'] == $selectedRombelId) {
                    // PERBAIKAN: Beri prefix agar ID mapel biasa & gabungan tidak bentrok
                    if (!empty($tgt['combined_subject_id'])) {
                        $optId = 'C_' . $tgt['combined_subject_id']; // C = Combined
                        $subjectOptions[$optId] = ['subject_name' => $tgt['combined_name'] ?? 'Mapel Gabungan'];
                    } elseif (!empty($tgt[$kolomSubjectId])) {
                        $optId = 'S_' . $tgt[$kolomSubjectId]; // S = Subject Biasa
                        $subjectOptions[$optId] = ['subject_name' => $tgt['subject_name'] ?? 'Mapel Tidak Diketahui'];
                    }
                }
            }
        }

        if (empty($selectedRombelId) && !empty($rombelOptions)) $selectedRombelId = array_key_first($rombelOptions);
        if (empty($selectedSubjectId) && !empty($subjectOptions)) $selectedSubjectId = array_key_first($subjectOptions);

        $analysisData = [];
        $grandTotalJp = 0;
        $hariMengajar = [];

        if ($selectedRombelId && $selectedSubjectId) {
            
            // PERBAIKAN: Deteksi apakah yang dipilih itu Mapel Gabungan atau Biasa
            $isCombined = (strpos($selectedSubjectId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedSubjectId);

            $builder = $db->table('class_schedules cs')
                            ->join('schedule_time_slots ts', 'ts.id = cs.slot_id')
                            ->where('cs.version_id', $jadwalAktif['id'])
                            ->where('cs.rombel_id', $selectedRombelId)
                            ->where("cs.{$kolomIdGuruDiJadwal}", $selectedTeacherId);

            // Filter sesuai jenis mapelnya
            if ($isCombined) {
                $builder->where('cs.combined_subject_id', $realSubjectId);
            } else {
                $builder->where("cs.{$kolomSubjectId}", $realSubjectId);
            }

            $schedules = $builder->get()->getResultArray();

            $jpPerHari = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
            foreach ($schedules as $sch) {
                if (isset($jpPerHari[$sch['day_name']])) {
                    $jpPerHari[$sch['day_name']] += 1;
                    $hariMengajar[$sch['day_name']] = true;
                }
            }

            $tahunSplit = explode('/', $tahunAktif['academic_year']);
            $tahunStart = (int)trim($tahunSplit[0]);
            $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;

            $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
            $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
            $namaBulanIndo = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'Nopember',12=>'Desember'];

            $masterClassId = $rombelOptions[$selectedRombelId]['master_class_id'] ?? 1;
            $kaldikEvents = $db->tableExists('academic_calendars') ? $db->table('academic_calendars')->where('academic_year_id', $tahunAktif['id'])->where('class_id', $masterClassId)->get()->getResultArray() : [];

            $hariNamesNumeric = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

            foreach ($bulanList as $bln) {
                $tahunTerkait = ($isGanjil) ? $tahunStart : $tahunEnd;
                $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);
                $hebBulanIni = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];

                for ($d = 1; $d <= $jmlHariBulan; $d++) {
                    $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                    $dayOfWeek = date('N', strtotime($dateStr)); 
                    
                    if ($dayOfWeek <= 5) {
                        $namaHari = $hariNamesNumeric[$dayOfWeek];
                        $isLibur = false;
                        foreach ($kaldikEvents as $ev) {
                            if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) {
                                $isLibur = true; break;
                            }
                        }
                        if (!$isLibur) $hebBulanIni[$namaHari]++;
                    }
                }

                $totalJpBulan = 0;
                $detailHari = [];
                foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari) {
                    $heb = $hebBulanIni[$hari];
                    $jp = $jpPerHari[$hari];
                    $jumlah = $heb * $jp;
                    $totalJpBulan += $jumlah;
                    $detailHari[] = ['hari' => $hari, 'heb' => $heb, 'jp' => $jp > 0 ? $jp : '', 'jumlah' => $jumlah];
                }

                $analysisData[] = ['nama_bulan' => $namaBulanIndo[$bln], 'detail' => $detailHari, 'total_jp_bulan' => $totalJpBulan];
                $grandTotalJp += $totalJpBulan;
            }
        }

        $data = [
            'displayRole' => $displayRole,
            'isGuru' => $isGuru,
            'tahunAktif' => $tahunAktif,
            'teachers' => $teachers,
            'selectedTeacherId' => $selectedTeacherId,
            'rombelOptions' => $rombelOptions,
            'selectedRombelId' => $selectedRombelId,
            'subjectOptions' => $subjectOptions,
            'selectedSubjectId' => $selectedSubjectId,
            'analysisData' => $analysisData,
            'grandTotalJp' => $grandTotalJp,
            'hariMengajarText' => !empty($hariMengajar) ? implode(', ', array_keys($hariMengajar)) : '-'
        ];

        return view('admin/schedule/heb_analysis', $data);
    }
}