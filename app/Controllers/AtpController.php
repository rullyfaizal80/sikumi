<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AtpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // =====================================================================
        // 🚦 1. DETEKSI HAK AKSES & USER ID (Sama seperti AnalysisController)
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

        // Deteksi Tabel Mapel
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
        // 🚀 3. VALIDASI TAHUN AJARAN & JADWAL AKTIF
        // =====================================================================
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$tahunAktif) return redirect()->back()->with('error', 'Tidak ada Tahun Ajaran aktif.');

        $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        if (!$jadwalAktif) return redirect()->back()->with('error', 'Belum ada Jadwal Pelajaran yang diaktifkan.');

        // =====================================================================
        // 🔍 4. GET OPTIONS UNTUK DROPDOWN (MAPEL & KELAS)
        // =====================================================================
        $subjectOptions = [];
        $classOptions = [];

        if ($userId) {
            // Ambil daftar Mapel yang diampu oleh guru ini berdasarkan jadwal aktif
            $rawSubjects = $db->table('class_schedules cs')
                              ->select("cs.{$kolomSubjectId}, cs.combined_subject_id, s.{$kolomNamaMapel} as subject_name, c.combined_name")
                              ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                              ->join('schedule_combined_subjects c', 'c.id = cs.combined_subject_id', 'left')
                              ->where('cs.version_id', $jadwalAktif['id'])
                              ->where("cs.{$kolomIdGuruDiJadwal}", $userId)
                              ->groupStart()
                                 ->where("cs.{$kolomSubjectId} IS NOT NULL")
                                 ->orWhere('cs.combined_subject_id IS NOT NULL')
                              ->groupEnd()
                              ->groupBy("cs.{$kolomSubjectId}, cs.combined_subject_id")
                              ->get()->getResultArray();

            foreach ($rawSubjects as $row) {
                if (!empty($row['combined_subject_id'])) {
                    $subjectOptions['C_' . $row['combined_subject_id']] = $row['combined_name'] ?? 'Mapel Gabungan';
                } elseif (!empty($row[$kolomSubjectId])) {
                    $subjectOptions['S_' . $row[$kolomSubjectId]] = $row['subject_name'] ?? 'Mapel Tidak Diketahui';
                }
            }

            // Ambil daftar Kelas/Fase (master_class) yang diajar oleh guru ini
            $rawClasses = $db->table('class_schedules cs')
                             ->select('r.master_class_id, mc.class_name') // Menyesuaikan relasi tingkat kelas
                             ->join('class_rombel r', 'r.id = cs.rombel_id')
                             ->join('master_classes mc', 'mc.id = r.master_class_id') // Asumsi nama tabel master kelas
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where("cs.{$kolomIdGuruDiJadwal}", $userId)
                             ->groupBy('r.master_class_id')
                             ->orderBy('r.master_class_id', 'ASC')
                             ->get()->getResultArray();

            foreach ($rawClasses as $row) {
                $classOptions[$row['master_class_id']] = $row['class_name'];
            }
        }

        // Menentukan Mapel & Kelas Terpilih (dari GET request atau default index pertama)
        $selectedMapelId = $this->request->getGet('mapel_id');
        if (empty($selectedMapelId) && !empty($subjectOptions)) { $selectedMapelId = array_key_first($subjectOptions); }

        $selectedKelasId = $this->request->getGet('kelas_id');
        if (empty($selectedKelasId) && !empty($classOptions)) { $selectedKelasId = array_key_first($classOptions); }

        // =====================================================================
        // 📊 5. KALKULASI REAL JP BERDASARKAN HEB (HARI EFEKTIF BELAJAR)
        // =====================================================================
        $totalJpTersedia = 0;

        if ($userId && $selectedMapelId && $selectedKelasId) {
            $isCombined = (strpos($selectedMapelId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            // Ambil Rombel pertama di kelas ini untuk acuan hitung HEB (karena rombel paralel memilki jumlah HEB yang sama)
            $rombelAcuan = $db->table('class_schedules cs')
                              ->select('cs.rombel_id')
                              ->join('class_rombel r', 'r.id = cs.rombel_id')
                              ->where('cs.version_id', $jadwalAktif['id'])
                              ->where('r.master_class_id', $selectedKelasId)
                              ->where("cs.{$kolomIdGuruDiJadwal}", $userId)
                              ->get()->getRowArray();

            if ($rombelAcuan) {
                // Ambil Slot Jadwal mengajar untuk rombel acuan ini
                $builderSch = $db->table('class_schedules cs')
                                 ->join('schedule_time_slots ts', 'ts.id = cs.slot_id')
                                 ->where('cs.version_id', $jadwalAktif['id'])
                                 ->where('cs.rombel_id', $rombelAcuan['rombel_id'])
                                 ->where("cs.{$kolomIdGuruDiJadwal}", $userId);
                
                if ($isCombined) { $builderSch->where('cs.combined_subject_id', $realSubjectId); } 
                else { $builderSch->where("cs.{$kolomSubjectId}", $realSubjectId); }
                $schedules = $builderSch->get()->getResultArray();

                // Hitung JP per hari
                $jpPerHari = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0];
                foreach ($schedules as $sch) {
                    if (isset($jpPerHari[$sch['day_name']])) { $jpPerHari[$sch['day_name']] += 1; }
                }

                // Ambil data Kalender Akademik untuk deteksi hari libur
                $kaldikEvents = $db->tableExists('academic_calendars') ? $db->table('academic_calendars')->where('academic_year_id', $tahunAktif['id'])->where('class_id', $selectedKelasId)->get()->getResultArray() : [];

                // Parsing Bulan sesuai Semester
                $tahunSplit = explode('/', $tahunAktif['academic_year']);
                $tahunStart = (int)trim($tahunSplit[0]);
                $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;
                $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
                $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
                $hariNamesNumeric = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

                // Loop perhitungan tanggal efektif
                foreach ($bulanList as $bln) {
                    $tahunTerkait = ($isGanjil) ? $tahunStart : $tahunEnd;
                    $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);

                    for ($d = 1; $d <= $jmlHariBulan; $d++) {
                        $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                        $dayOfWeek = date('N', strtotime($dateStr)); 
                        if ($dayOfWeek <= 5) {
                            $namaHari = $hariNamesNumeric[$dayOfWeek];
                            $isLibur = false;
                            foreach ($kaldikEvents as $ev) {
                                if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) { $isLibur = true; break; }
                            }
                            if (!$isLibur) {
                                // Jika hari ini tidak libur, tambahkan JP berdasarkan jadwal hari tersebut
                                $totalJpTersedia += $jpPerHari[$namaHari];
                            }
                        }
                    }
                }
            }
        }

        // =====================================================================
        // 📦 6. KIRIM DATA KE VIEW
        // =====================================================================
        $data = [
            'displayRole'       => $displayRole,
            'isGuru'            => $isGuru,
            'tahunAktif'        => $tahunAktif,
            'subjectOptions'    => $subjectOptions,
            'classOptions'      => $classOptions,
            'selectedMapelId'   => $selectedMapelId,
            'selectedKelasId'   => $selectedKelasId,
            'totalJpTersedia'   => $totalJpTersedia,
        ];

        // Ganti dengan path folder view ATP Anda yang sebenarnya
        return view('guru/atp_manage', $data); 
    }
}