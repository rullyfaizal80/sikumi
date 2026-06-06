<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AtpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // ==============================================================
        // 1. INFO HEADER (BACKEND ONLY)
        // ==============================================================
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $userId = session()->get('user_id') ?? (function_exists('user_id') ? user_id() : 0);
        
        $namaMadrasah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'nama_madrasah')->get()->getRowArray() : null;
        $titiMangsa = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray() : null;
        $kepalaSekolah = $db->tableExists('settings') ? $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray() : null;
        $namaGuruCetak = 'Guru Pengampu';

        if ($db->tableExists('teacher_profiles')) {
            $guru = $db->table('teacher_profiles')->where('user_id', $userId)->get()->getRowArray();
            $namaGuruCetak = $guru['nama_guru'] ?? $guru['nama'] ?? $guru['full_name'] ?? 'Guru Pengampu';
        }

        // ==============================================================
        // 2. DINAMISASI ROMBEL
        // ==============================================================
        $daftarRombel = [];
        if ($tahunAktif && $db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel cr')
                               ->select('cr.id, cr.rombel_name, mc.class_name, mc.level_type, mc.id as master_class_id')
                               ->join('master_classes mc', 'mc.id = cr.master_class_id')
                               ->where('cr.academic_year_id', $tahunAktif['id'])
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('cr.rombel_name', 'ASC')
                               ->get()->getResultArray();
        }

        $selectedRombelId = $this->request->getGet('rombel_id') ?? (!empty($daftarRombel) ? $daftarRombel[0]['id'] : 1);

       $tingkatKelas = 7; // Default
        $masterClassId = 1; 
        $namaRombelAktif = '-';
        
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $className = $r['class_name'] ?? '';
                $rombelName = $r['rombel_name'] ?? '';
                $namaRombelAktif = $className . ($rombelName ? ' - ' . $rombelName : '');
                
                // 🌟 PERBAIKAN: Deteksi Angka atau Romawi
                $angkaTingkat = preg_replace('/[^0-9]/', '', $className); // Cari angka biasa
                
                if (!empty($angkaTingkat)) {
                    $tingkatKelas = $angkaTingkat;
                } else {
                    // Jika tidak ada angka (misal pakai "Kelas VII"), kita konversi Romawi ke Angka
                    $upperClass = strtoupper($className);
                    if (strpos($upperClass, 'VIII') !== false) { $tingkatKelas = 8; }
                    elseif (strpos($upperClass, 'VII') !== false) { $tingkatKelas = 7; }
                    elseif (strpos($upperClass, 'IX') !== false) { $tingkatKelas = 9; }
                    elseif (strpos($upperClass, 'XII') !== false) { $tingkatKelas = 12; }
                    elseif (strpos($upperClass, 'XI') !== false) { $tingkatKelas = 11; }
                    elseif (strpos($upperClass, 'X') !== false) { $tingkatKelas = 10; }
                }
                
                $masterClassId = $r['master_class_id'] ?? $r['id'];
                break;
            }
        }

        // ==============================================================
        // 3. KUNCI MAPEL & MAPEL GABUNGAN (KHUSUS GURU AKTIF)
        // ==============================================================
        $daftarMapel = [];
        $jadwalAktif = null;
        
        if ($tahunAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $tahunAktif['id'])->where('is_active', 1)->get()->getRowArray();
        }

        if ($jadwalAktif && $db->tableExists('class_schedules')) {
            $csFields = $db->getFieldNames('class_schedules');
            $kolomIdGuru = in_array('teacher_id', $csFields) ? 'teacher_id' : (in_array('guru_id', $csFields) ? 'guru_id' : 'user_id');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;
    
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
    
            // B. Ambil Mapel Gabungan Guru
            if ($kolomCombinedId && $db->tableExists('schedule_combined_subjects')) { 
                $mapelGabungan = $db->table('class_schedules cs')
                             // PERBAIKAN 2: Cukup ambil 'c.combined_name' sesuai dengan struktur di controller referensi
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name") 
                             // PERBAIKAN 3: Join ke tabel yang benar
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where("cs.{$kolomIdGuru}", $userId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}")
                             ->get()->getResultArray();

                foreach($mapelGabungan as $mg) {
                    if(!empty($mg['combined_id'])) {
                        $namaGabungan = $mg['combined_name'] ?? 'Mapel Gabungan';
                        $daftarMapel[] = [
                            // Saya tambahkan underscore 'C_' agar format ID-nya sama persis dengan yang di controller referensi
                            'id' => 'C_' . $mg['combined_id'], 
                            'subject_name' => $namaGabungan
                        ];
                    }
                }
            }
            
            // A. Ambil Mapel Reguler Guru KEMUDIAN
            $mapelReguler = $db->table('class_schedules cs')
                          ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                          ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                          ->where('cs.version_id', $jadwalAktif['id'])
                          ->where("cs.{$kolomIdGuru}", $userId)
                          ->where("cs.{$kolomSubjectId} IS NOT NULL")
                          ->where("cs.{$kolomSubjectId} !=", 0)
                          ->groupBy("cs.{$kolomSubjectId}")
                          ->get()->getResultArray();
                          
            foreach($mapelReguler as $m) {
                if(!empty($m['id'])) {
                    // 🌟 PERBAIKAN: Tambahkan 'S_' di depan ID mapel reguler
                    $daftarMapel[] = [
                        'id' => 'S_' . $m['id'], 
                        'subject_name' => $m['subject_name']
                    ];
                }
            }    
        }

        $selectedMapelId = $this->request->getGet('mapel_id') ?? (!empty($daftarMapel) ? $daftarMapel[0]['id'] : 1);

        // ==============================================================
        // 4. LOAD DATA ANALISIS CP (PERBAIKAN QUERY KE KURIKULUM_CP)
        // ==============================================================
        $dataAtp = [];
        
        if ($db->tableExists('kurikulum_cp_headers') && $db->tableExists('kurikulum_cp_details')) {
             $builder = $db->table('kurikulum_cp_headers h')
                          ->select('d.*, h.mapel_id, h.master_class_id')
                          ->join('kurikulum_cp_details d', 'd.header_id = h.id', 'inner')
                          ->where('h.mapel_id', $selectedMapelId)
                          ->where('h.master_class_id', $masterClassId)
                          ->orderBy('d.urutan', 'ASC')
                          ->orderBy('d.id', 'ASC');

             $dataAtp = $builder->get()->getResultArray();
        }

        // ==============================================================
        // 5. LOAD TANGGAL JADWAL (BERDASARKAN ANALISIS HEB & KALDIK)
        // ==============================================================
        $listTanggal = [];

        if ($jadwalAktif && $tahunAktif && !empty($selectedRombelId)) {
            $isCombined = (strpos($selectedMapelId, 'C_') === 0);
            $realSubjectId = str_replace(['S_', 'C_'], '', $selectedMapelId);

            // 5a. Dapatkan hari apa saja guru ini mengajar mapel tsb di Rombel ini
            $hariMengajar = [];
            $builderSch = $db->table('class_schedules cs')
                             ->select('cs.day_name') // Ambil nama hari dari jadwal
                             ->where('cs.version_id', $jadwalAktif['id'])
                             ->where('cs.rombel_id', $selectedRombelId);
            
            // Filter berdasarkan mapel reguler atau gabungan
            if ($isCombined) { 
                $builderSch->where('cs.combined_subject_id', $realSubjectId); 
            } else { 
                $kolomSubjectId = in_array('subject_id', $db->getFieldNames('class_schedules')) ? 'subject_id' : 'mapel_id';
                $builderSch->where("cs.{$kolomSubjectId}", $realSubjectId); 
            }
            
            $schedules = $builderSch->get()->getResultArray();
            foreach ($schedules as $sch) {
                // Simpan hari unik (Senin, Selasa, dst) ke dalam array
                if (!empty($sch['day_name']) && !in_array($sch['day_name'], $hariMengajar)) {
                    $hariMengajar[] = $sch['day_name'];
                }
            }

            // 5b. Jika ternyata ada jadwal harinya, kita petakan ke Kalender
            if (!empty($hariMengajar)) {
                // Ambil data Kaldik untuk deteksi libur (seperti di AnalysisController)
                $kaldikEvents = $db->tableExists('academic_calendars') ? $db->table('academic_calendars')->where('academic_year_id', $tahunAktif['id'])->get()->getResultArray() : [];

                // Atur jangkauan bulan berdasarkan semester
                $tahunSplit = explode('/', $tahunAktif['academic_year']);
                $tahunStart = (int)trim($tahunSplit[0]);
                $tahunEnd = isset($tahunSplit[1]) ? (int)trim($tahunSplit[1]) : $tahunStart + 1;
                $isGanjil = strtolower($tahunAktif['semester']) == 'ganjil';
                $bulanList = $isGanjil ? [7, 8, 9, 10, 11, 12] : [1, 2, 3, 4, 5, 6];
                
                // Mapping index hari PHP (1=Senin ... 7=Minggu)
                $hariNamesNumeric = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                $namaBulanIndo = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'Mei', 6=>'Jun', 7=>'Jul', 8=>'Agu', 9=>'Sep', 10=>'Okt', 11=>'Nov', 12=>'Des'];

                // Loop setiap bulan aktif di semester ini
                foreach ($bulanList as $bln) {
                    $tahunTerkait = ($isGanjil && $bln >= 7) ? $tahunStart : (($isGanjil) ? $tahunStart : $tahunEnd);
                    if (!$isGanjil && $bln <= 6) { $tahunTerkait = $tahunEnd; } // Fix untuk genap
                    
                    $jmlHariBulan = cal_days_in_month(CAL_GREGORIAN, $bln, $tahunTerkait);

                    for ($d = 1; $d <= $jmlHariBulan; $d++) {
                        $dateStr = sprintf("%04d-%02d-%02d", $tahunTerkait, $bln, $d);
                        $dayOfWeek = date('N', strtotime($dateStr)); 
                        $namaHari = $hariNamesNumeric[$dayOfWeek];

                        // Jika hari tersebut adalah hari guru mengajar
                        if (in_array($namaHari, $hariMengajar)) {
                            // Cek apakah tanggal ini masuk masa libur di Kaldik
                            $isLibur = false;
                            foreach ($kaldikEvents as $ev) {
                                if ($dateStr >= $ev['start_date'] && $dateStr <= $ev['end_date']) { 
                                    $isLibur = true; 
                                    break; 
                                }
                            }
                            
                            // Jika bukan libur, masukkan ke dalam daftar tanggal tersedia!
                            if (!$isLibur) {
                                // Format tampilan misal: "12 Jul 2026"
                                $tglFormat = sprintf("%02d %s %04d", $d, $namaBulanIndo[$bln], $tahunTerkait);
                                $listTanggal[] = $tglFormat;
                            }
                        }
                    }
                }
            }
        }

        // ==============================================================
        // 5c. Distribusikan Tanggal ke Tabel ATP
        // ==============================================================
        foreach ($dataAtp as $idx => &$row) {
            // Karena tidak pakai array reference ($row), pastikan penomoran jalan
            $row['nomor_atp'] = $tingkatKelas . '.' . ($idx + 1);
            
            // Masukkan tanggal berdasarkan index, jika habis beri keterangan
            $row['tanggal'] = $listTanggal[$idx] ?? 'Jadwal Habis / Belum Diatur';
        }
        
        // ==============================================================
        // 6. RENDER KE VIEW
        // ==============================================================
        $data = [
            'tahunAktif'    => $tahunAktif,
            'daftarRombel'  => $daftarRombel,
            'daftarMapel'   => $daftarMapel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId'  => $selectedMapelId,
            'tingkatKelas'     => $tingkatKelas,
            'namaRombelAktif'  => $namaRombelAktif,
            'dataAtp'          => $dataAtp,
            
            'namaMadrasah' => $namaMadrasah['value'] ?? 'MIMHa',
            'titiMangsa'   => $titiMangsa['value'] ?? date('d F Y'),
            'kepalaNama'   => $kepalaSekolah['value'] ?? '-',
            'namaGuruCetak'=> $namaGuruCetak,
            'listProfilLulusan' => ['DPL1'=>'Keimanan','DPL2'=>'Kewargaan','DPL3'=>'Penalaran Kritis','DPL4'=>'Kreativitas','DPL5'=>'Kolaborasi','DPL6'=>'Kemandirian','DPL7'=>'Kesehatan','DPL8'=>'Komunikasi'],
            'listPancaCinta'    => ['P1'=>'Cinta Allah/Rasul','P2'=>'Cinta Ilmu','P3'=>'Cinta Diri/Sesama','P4'=>'Cinta Lingkungan','P5'=>'Cinta Tanah Air']
        ];

        return view('guru/atp_manage', $data);
    }
}