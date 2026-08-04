<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class AdminRaporBerjalanController extends BaseController
{
    // 1. HALAMAN FILTER PENCARIAN
    public function index()
    {
        $db = Database::connect();
        
        // Ambil daftar kelas untuk dropdown
        $daftarRombel = [];
        if ($db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        }

        $data = [
            'daftarRombel' => $daftarRombel,
            'tahun'        => date('Y') // Default tahun saat ini
        ];

        return view('admin/rapor_berjalan_index', $data);
    }

    // 2. AJAX UNTUK MENGAMBIL DAFTAR SISWA BERDASARKAN KELAS
    public function getSiswa()
    {
        $db = Database::connect();
        $rombel_id = $this->request->getPost('rombel_id');

        $siswa = $db->table('class_rombel_students crs')
                    ->join('users u', 'u.id = crs.student_id')
                    ->select('u.id, u.username as name')
                    ->where('crs.rombel_id', $rombel_id)
                    ->orderBy('u.username', 'ASC')
                    ->get()->getResultArray();

        return $this->response->setJSON($siswa);
    }

    // 3. HALAMAN HASIL RAPOR BERJALAN
    public function lihat()
    {
        $db = Database::connect();
        $request = \Config\Services::request();

        // Ambil parameter dari form filter
        $student_id = $request->getGet('student_id');
        $semester   = $request->getGet('semester') ?? 'ganjil';
        $tahun      = $request->getGet('tahun') ?? date('Y');

        if (empty($student_id)) {
            return redirect()->to(base_url('admin/rapor-berjalan'))->with('error', 'Silakan pilih siswa terlebih dahulu.');
        }

        // =========================================================
        // LOGIKA DI BAWAH INI SAMA PERSIS DENGAN RAPOR SISWA
        // =========================================================
        
        // 1. AMBIL DATA PROFIL SISWA
        $dataSiswa = $db->table('users u')
                        ->join('student_profiles sp', 'sp.user_id = u.id', 'left')
                        ->select('u.id, u.username as name, sp.nisn, sp.nis, sp.gender')
                        ->where('u.id', $student_id)
                        ->get()->getRowArray();

        $kelasSiswa = $db->table('class_rombel_students crs')
                         ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                         ->select('cr.rombel_name')
                         ->where('crs.student_id', $student_id)
                         ->get()->getRowArray();
                         
        $dataSiswa['kelas'] = $kelasSiswa ? $kelasSiswa['rombel_name'] : '-';

        // 2. TENTUKAN BULAN YANG SUDAH DILALUI[cite: 5]
        $arrayBulanSemester = ($semester === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        $tahunLaporan  = (int) $tahun;
        $tahunSekarang = (int) date('Y');
        $bulanSekarang = (int) date('m');
        $hariSekarang  = (int) date('d');
        $bulanAktif = [];

        $isLaporanLampau = false;
        if ($tahunLaporan < $tahunSekarang) {
            $isLaporanLampau = true;
        } elseif ($tahunLaporan == $tahunSekarang && $semester === 'ganjil' && $bulanSekarang < 7) {
            $isLaporanLampau = true; 
        }

        if ($isLaporanLampau) {
            $bulanAktif = $arrayBulanSemester;
        } else {
            if ($hariSekarang >= 6) {
                $batasBulan = $bulanSekarang - 1;
            } else {
                $batasBulan = $bulanSekarang - 2;
            }
            if ($batasBulan == 0) $batasBulan = 12;
            if ($batasBulan == -1) $batasBulan = 11;

            foreach ($arrayBulanSemester as $b) {
                $intB = (int)$b;
                if ($semester === 'ganjil') {
                    if ($batasBulan >= 7 && $intB <= $batasBulan) {
                        $bulanAktif[] = $b;
                    }
                } else {
                    if ($batasBulan >= 1 && $batasBulan <= 6 && $intB <= $batasBulan) {
                        $bulanAktif[] = $b;
                    }
                }
            }
        }

        // Bypass untuk uji coba tampilan[cite: 5]
        if (empty($bulanAktif)) {
            if ($semester === 'ganjil') {
                $bulanAktif = ['07', '08', '09', '10', '11', '12'];
            } else {
                $bulanAktif = ['01', '02', '03', '04', '05', '06'];
            }
        }

        // ==============================================================
        // 3. TARIK DATA ABSENSI & HITUNG PERSENTASE + MENIT TERLAMBAT
        // ==============================================================
        
        // A. Ambil Data Hari Efektif dari Database
        $hariEfektifDb = $db->table('hari_efektif')
                            ->where('tahun', $tahun)
                            ->whereIn('bulan', $bulanAktif)
                            ->get()->getResultArray();
        
        // Petakan hari efektif berdasarkan bulan agar mudah dicari
        $mapHariEfektif = [];
        foreach ($hariEfektifDb as $he) {
            // Pastikan format bulan selaras (2 digit string seperti '07', '08')
            $b = str_pad($he['bulan'], 2, '0', STR_PAD_LEFT);
            $mapHariEfektif[$b] = (int)$he['jumlah_hari'];
        }

        // B. Tarik Data Mentah Absensi Siswa
        $absenRaw = $db->table('absensi a')
                       ->join('absensi_details ad', 'a.id = ad.absensi_id')
                       ->select('
                           LPAD(MONTH(a.tanggal), 2, "0") as bulan,
                           SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                           SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                           SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                           SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                           SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t,
                           SUM(ad.keterlambatan_menit) as total_menit
                       ')
                       ->where('ad.student_id', $student_id)
                       ->whereIn('LPAD(MONTH(a.tanggal), 2, "0")', $bulanAktif)
                       ->where('YEAR(a.tanggal)', $tahun)
                       ->groupBy('LPAD(MONTH(a.tanggal), 2, "0")') // Pastikan group by-nya konsisten
                       ->get()->getResultArray();

        // Petakan data raw absensi berdasarkan bulan agar mempermudah perhitungan
        $mapAbsenRaw = [];
        foreach ($absenRaw as $ar) {
            $mapAbsenRaw[$ar['bulan']] = $ar;
        }

        // 3a. Siapkan array default dengan tanda '-'
        $matrixAbsen = ['H' => [], 'S' => [], 'I' => [], 'A' => [], 'T' => [], 'M' => []];
        foreach ($bulanAktif as $b) {
            foreach (['H', 'S', 'I', 'A', 'T', 'M'] as $kode) {
                $matrixAbsen[$kode][$b] = '-';
            }
        }

        // Variabel untuk melacak total akumulasi mentah selama 1 semester
        $totalMentah = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0, 'M' => 0, 'HariEfektif' => 0];

        // 3b. Proses konversi data mentah menjadi persentase & angka absolut
        foreach ($bulanAktif as $b) {
            // Ambil target hari efektif di bulan ini (default 0 jika blm disetting admin)
            $hariEfektif = $mapHariEfektif[$b] ?? 0;
            $totalMentah['HariEfektif'] += $hariEfektif;

            // Tarik data absen siswa di bulan tsb (jika tidak ada data sama sekali, anggap 0)
            $ar = $mapAbsenRaw[$b] ?? [
                'total_h' => 0, 'total_s' => 0, 'total_i' => 0, 
                'total_a' => 0, 'total_t' => 0, 'total_menit' => 0
            ];

            // Jika admin sudah set hari efektif > 0, hitung persentasenya
            if ($hariEfektif > 0) {
                // min(100, ...) digunakan agar persentase tidak tembus > 100% jika ada dobel input absensi
                $matrixAbsen['H'][$b] = min(100, round(($ar['total_h'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['S'][$b] = min(100, round(($ar['total_s'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['I'][$b] = min(100, round(($ar['total_i'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['A'][$b] = min(100, round(($ar['total_a'] / $hariEfektif) * 100)) . '%';
                $matrixAbsen['T'][$b] = min(100, round(($ar['total_t'] / $hariEfektif) * 100)) . '%';
            }
            
            // Masukkan data menit keterlambatan sebagai nilai absolut
            if ($ar['total_menit'] > 0) {
                $matrixAbsen['M'][$b] = $ar['total_menit'] . ' mnt';
            }

            // Tambahkan data mentah ke total semester
            $totalMentah['H'] += $ar['total_h'];
            $totalMentah['S'] += $ar['total_s'];
            $totalMentah['I'] += $ar['total_i'];
            $totalMentah['A'] += $ar['total_a'];
            $totalMentah['T'] += $ar['total_t'];
            $totalMentah['M'] += $ar['total_menit'];
        }

        // 3c. Hitung persentase kolom "Total" di ujung kanan tabel (Rata-rata 1 semester)
        $totalAbsen = ['H' => '-', 'S' => '-', 'I' => '-', 'A' => '-', 'T' => '-', 'M' => '-'];
        
        // Kalkulasi persentase grand total terhadap grand total hari efektif
        if ($totalMentah['HariEfektif'] > 0) {
            $totalHari = $totalMentah['HariEfektif'];
            $totalAbsen['H'] = min(100, round(($totalMentah['H'] / $totalHari) * 100)) . '%';
            $totalAbsen['S'] = min(100, round(($totalMentah['S'] / $totalHari) * 100)) . '%';
            $totalAbsen['I'] = min(100, round(($totalMentah['I'] / $totalHari) * 100)) . '%';
            $totalAbsen['A'] = min(100, round(($totalMentah['A'] / $totalHari) * 100)) . '%';
            $totalAbsen['T'] = min(100, round(($totalMentah['T'] / $totalHari) * 100)) . '%';
        }

        // Total akumulasi menit dalam 1 semester
        if ($totalMentah['M'] > 0) {
            $totalAbsen['M'] = $totalMentah['M'] . ' mnt';
        }

       // ==============================================================
        // 4. TARIK DATA KEPATUHAN & KARAKTER (SPIRITUAL & SOSIAL)
        // ==============================================================
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('m');
        $namaBulanLokal = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

        // --------------------------------------------------------------
        // A. KEPATUHAN (Menghitung Total Kasus & Kelompokkan Keterangan)
        // --------------------------------------------------------------
        $kepatuhanRaw = $db->table('kepatuhan')
            ->select('
                LPAD(MONTH(tanggal), 2, "0") as bulan, 
                SUM(seragam) as seragam, 
                SUM(atribut) as atribut, 
                SUM(bersih_diri) as bersih_diri, 
                SUM(terlambat) as terlambat, 
                SUM(aturan_kelas) as aturan_kelas, 
                SUM(masjid) as masjid,
                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR " | ") as gabungan_keterangan
            ')
            ->where('student_id', $student_id)
            ->whereIn('LPAD(MONTH(tanggal), 2, "0")', $bulanAktif)
            ->where('YEAR(tanggal)', $tahun)
            ->groupBy('MONTH(tanggal)')
            ->get()->getResultArray();

        $kepatuhanKolom = ['seragam', 'atribut', 'bersih_diri', 'terlambat', 'aturan_kelas', 'masjid'];
        $kepatuhan = ['matrix' => [], 'totals' => []];
        
        foreach ($kepatuhanKolom as $kolom) {
            $kepatuhan['totals'][$kolom] = 0;
            foreach ($bulanAktif as $b) {
                // LOGIKA BARU: Hanya tampilkan 0 jika bulan tersebut SUDAH LEWAT (< $currentMonth)
                $isBerjalan = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);
                $kepatuhan['matrix'][$kolom][$b] = $isBerjalan ? 0 : '-';
            }
        }

        $rincianSemester = [];
        foreach ($kepatuhanRaw as $kr) {
            $b = $kr['bulan'];
            
            // 1. Masukkan angka pelanggaran ke dalam matriks
            foreach ($kepatuhanKolom as $kolom) {
                if ($kr[$kolom] > 0) {
                    $kepatuhan['matrix'][$kolom][$b] = $kr[$kolom];
                    $kepatuhan['totals'][$kolom] += $kr[$kolom];
                }
            }
            
            // 2. Olah catatan "keterangan" asli dari database
            if (!empty(trim($kr['gabungan_keterangan']))) {
                $catatanArray = array_filter(array_map('trim', explode('|', $kr['gabungan_keterangan'])));
                
                $hitungCatatan = [];
                foreach ($catatanArray as $catatan) {
                    if (empty($catatan)) continue;
                    
                    $kunci = strtolower($catatan); // Case-insensitive
                    
                    if (!isset($hitungCatatan[$kunci])) {
                        $hitungCatatan[$kunci] = [
                            'teks'   => ucfirst($kunci),
                            'jumlah' => 0
                        ];
                    }
                    $hitungCatatan[$kunci]['jumlah']++;
                }
                
                // 3. Rangkai kembali menjadi format: Nama Keterangan (Nx)
                $catatanFinal = [];
                foreach ($hitungCatatan as $item) {
                    $catatanFinal[] = $item['teks'] . ' (' . $item['jumlah'] . 'x)';
                }
                
                $teksCatatan = implode(' | ', $catatanFinal);
                
                if (!empty($teksCatatan)) {
                    $rincianSemester[] = '<b>' . $namaBulanLokal[$b] . '</b>: ' . esc($teksCatatan);
                }
            }
        }

        $keteranganPelanggaran = count($rincianSemester) > 0 ? implode('<br>', $rincianSemester) : '-';

        // --------------------------------------------------------------
        // B. KARAKTER (Spiritual & Sosial)
        // --------------------------------------------------------------
        $buildMatrix = function($tabel, $kolomArray) use ($db, $student_id, $bulanAktif, $tahun, $currentYear, $currentMonth) {
            $select = "LPAD(MONTH(tanggal), 2, '0') as bulan";
            foreach ($kolomArray as $kolom) {
                $select .= ", SUM($kolom) as $kolom";
            }
            $select .= ", GROUP_CONCAT(NULLIF(keterangan, '') SEPARATOR ' | ') as keterangan";

            $dataRaw = $db->table($tabel)
                          ->select($select)
                          ->where('student_id', $student_id)
                          ->whereIn('LPAD(MONTH(tanggal), 2, "0")', $bulanAktif)
                          ->where('YEAR(tanggal)', $tahun)
                          ->groupBy('MONTH(tanggal)')
                          ->get()->getResultArray();

            // Helper untuk konversi nilai ke predikat
            $getPredikat = function($nilai) {
                if ($nilai == 0) return 'A';
                if ($nilai >= 1 && $nilai <= 2) return 'B';
                if ($nilai >= 3 && $nilai <= 4) return 'C';
                return 'D';
            };

            $matrix = []; 
            $totals = [];
            $keterangan = [];

            // 1. Set nilai default matriks
            foreach ($kolomArray as $kolom) {
                $totals[$kolom] = 0;
                foreach ($bulanAktif as $b) {
                    // Hanya anggap valid jika bulan SUDAH LEWAT (< $currentMonth)
                    $isSudahLewat = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);
                    
                    // Jika bulan sudah lewat -> default 'A', jika bulan berjalan/akan datang -> '-'
                    $matrix[$kolom][$b] = $isSudahLewat ? 'A' : '-'; 
                }
            }

            // Set default keterangan '-'
            foreach ($bulanAktif as $b) {
                $keterangan[$b] = '-';
            }

            // 2. Timpa nilai matriks berdasarkan data dari Database
            foreach ($dataRaw as $dr) {
                $b = $dr['bulan'];
                
                // Pastikan HANYA memproses data jika bulan tersebut SUDAH LEWAT
                $isSudahLewat = ($tahun < $currentYear) || ($tahun == $currentYear && (int)$b < $currentMonth);

                if ($isSudahLewat) {
                    foreach ($kolomArray as $kolom) {
                        if (isset($dr[$kolom])) {
                            $nilai = (int) $dr[$kolom];
                            $totals[$kolom] += $nilai;
                            $matrix[$kolom][$b] = $getPredikat($nilai);
                        }
                    }
                    if (!empty($dr['keterangan'])) {
                        $keterangan[$b] = $dr['keterangan'];
                    }
                }
            }

            // 3. Hitung Predikat akumulasi total semester (Hanya dari bulan yang sudah lewat)
            $totalsPredikat = [];
            foreach ($totals as $kolom => $nilaiTotal) {
                $totalsPredikat[$kolom] = $getPredikat($nilaiTotal);
            }

            return [
                'matrix'          => $matrix, 
                'totals_raw'      => $totals,
                'totals_predikat' => $totalsPredikat,
                'keterangan'      => $keterangan
            ];
        };

        $spiritual = $buildMatrix('aspek_spiritual', ['berdoa', 'kalimat_thoyibah', 'shalat', 'salam', 'syukur', 'lingkungan', 'toleransi']);
        $sosial    = $buildMatrix('aspek_sosial', ['disiplin', 'jujur', 'percaya_diri', 'santun', 'kerjasama', 'tanggung_jawab', 'adil']);
        
        // ==============================================================
        // 5. TARIK NILAI SUMATIF
        // ==============================================================
        
        // A. Tentukan nama tabel & kolom untuk mapel reguler
        $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
        $kolomNamaMapel = in_array('subject_name', $db->getFieldNames($tabelMapel)) ? 'subject_name' : (in_array('nama_mapel', $db->getFieldNames($tabelMapel)) ? 'nama_mapel' : 'name');
        
        // B. Tarik data nilai mentah
        $sumatifRaw = $db->table('nilai_sumatif')
                         ->select("LPAD(bulan, 2, '0') as bulan, mapel_id, nilai_angka")
                         ->where('student_id', $student_id)
                         ->whereIn('bulan', $bulanAktif)
                         ->get()->getResultArray();

        // C. Buat Kamus/Mapping Nama Mapel Reguler
        $refMapel = [];
        if ($db->tableExists($tabelMapel)) {
            $mapelDb = $db->table($tabelMapel)->select("id, {$kolomNamaMapel} as nama_mapel")->get()->getResultArray();
            foreach ($mapelDb as $m) {
                $refMapel['S_' . $m['id']] = $m['nama_mapel']; 
                $refMapel[$m['id']] = $m['nama_mapel']; // Jaga-jaga jika ada data tanpa prefix
            }
        }

        // D. Buat Kamus/Mapping Nama Mapel Gabungan (Combined Subjects)
        if ($db->tableExists('schedule_combined_subjects')) {
            $gabunganDb = $db->table('schedule_combined_subjects')->select('id, combined_name')->get()->getResultArray();
            foreach ($gabunganDb as $g) {
                $refMapel['C_' . $g['id']] = $g['combined_name'];
            }
        }

        // ==============================================================
        // E. Susun Matriks Nilai (REVISI FINAL - DINAMIS TANPA HARDCODE)
        // ==============================================================
        
        $matrixSumatif = [];
        
        // 1. Mapel yang disembunyikan mutlak (berdasarkan request Anda sebelumnya)
        $mapelSembunyi = ['Seni dan Budaya', 'Bahasa Sunda', 'Bimbingan Konseling'];
        
        // 2. Lacak Mapel Anak secara DINAMIS dari database
        $mapelAnakGabungan = [];
        if ($db->tableExists('schedule_combined_details') && $db->tableExists('master_subjects')) {
            $anakDb = $db->table('schedule_combined_details scd')
                         ->select('ms.subject_name')
                         ->join('master_subjects ms', 'ms.id = scd.master_subject_id', 'left')
                         ->get()->getResultArray();
                         
            foreach ($anakDb as $anak) {
                if (!empty($anak['subject_name'])) {
                    $mapelAnakGabungan[] = $anak['subject_name'];
                }
            }
        }
        
        // Gabungkan semua daftar mapel yang tidak boleh tampil
        $semuaMapelDihide = array_merge($mapelSembunyi, $mapelAnakGabungan);
        
        $namaMapelUnik = []; // Tracker untuk mencegah mapel gabungan tampil double

        // 3. Inisialisasi SEMUA mapel dari referensi ke dalam matriks
        foreach ($refMapel as $key => $namaMapel) {
            // A. Ganti nama PJOK (Mencegat berbagai variasi penulisan)
            if (stripos($namaMapel, 'Pendidikan Jasmani') !== false && stripos($namaMapel, 'Olahraga') !== false) {
                $namaMapel = 'PJOK';
            }

            // B. Cek apakah mapel ini masuk daftar blacklist atau sudah pernah dicetak
            $isSembunyi = in_array($namaMapel, $semuaMapelDihide);
            $isDouble = in_array($namaMapel, $namaMapelUnik);

            // C. Masukkan ke matriks HANYA jika lolos semua syarat di atas
            if ((strpos($key, 'S_') === 0 || strpos($key, 'C_') === 0) && !$isSembunyi && !$isDouble) {
                $matrixSumatif[$key] = [
                    'nama_mapel' => $namaMapel, 
                    'nilai'      => [], 
                    'total'      => 0, 
                    'count'      => 0
                ];
                // Isi default semua bulan dengan null
                foreach ($bulanAktif as $b) { 
                    $matrixSumatif[$key]['nilai'][$b] = null; 
                }
                
                $namaMapelUnik[] = $namaMapel; // Catat nama untuk mencegah duplikasi
            }
        }

        // 4. Timpa dengan nilai asli dari table nilai_sumatif
        foreach ($sumatifRaw as $sr) {
            $mId = $sr['mapel_id'];
            $bln = $sr['bulan'];
            
            // Proses standarisasi nama yang sama seperti langkah 3
            $rawName = isset($refMapel[$mId]) ? $refMapel[$mId] : '';
            if (stripos($rawName, 'Pendidikan Jasmani') !== false && stripos($rawName, 'Olahraga') !== false) {
                $rawName = 'PJOK';
            }

            // Abaikan penarikan nilai jika ini milik mapel yang disembunyikan
            if (in_array($rawName, $semuaMapelDihide)) {
                continue;
            }

            // Jika mapelnya gabungan (beda ID tapi namanya sama), satukan nilainya ke row yang ada
            $targetKey = $mId;
            if (!isset($matrixSumatif[$mId])) {
                 $foundKey = null;
                 foreach ($matrixSumatif as $k => $v) {
                     if ($v['nama_mapel'] === $rawName) {
                         $foundKey = $k; break;
                     }
                 }
                 if ($foundKey) {
                     $targetKey = $foundKey;
                 }
            }

            // Inject nilainya ke bulan yang tepat
            if (isset($matrixSumatif[$targetKey]) && is_numeric($sr['nilai_angka'])) {
                $matrixSumatif[$targetKey]['nilai'][$bln] = (float)$sr['nilai_angka'];
                $matrixSumatif[$targetKey]['total'] += (float)$sr['nilai_angka'];
                $matrixSumatif[$targetKey]['count']++;
            }
        }

        // F. Urutkan matriks berdasarkan nama mapel secara Alfabet
        uasort($matrixSumatif, function($a, $b) {
            return strcmp($a['nama_mapel'], $b['nama_mapel']);
        });
        
        // 6. AMBIL CATATAN ANEKDOT & PRESTASI[cite: 5]
        $anekdot = $db->table('catatan_anekdot')
                      ->select('tanggal, kejadian')
                      ->where('student_id', $student_id)
                      ->whereIn('MONTH(tanggal)', $bulanAktif)
                      ->where('YEAR(tanggal)', $tahun)
                      ->orderBy('tanggal', 'ASC')->get()->getResultArray();

        $prestasi = $db->table('catatan_prestasi')
                       ->select('nama_prestasi, keterangan, created_at')
                       ->where('student_id', $student_id)
                       ->whereIn('MONTH(created_at)', $bulanAktif)
                       ->where('YEAR(created_at)', $tahun)
                       ->orderBy('created_at', 'ASC')->get()->getResultArray();

        // ==============================================================
        // 7. TARIK DATA NILAI AL-QUR'AN
        // ==============================================================
        // Siapkan kerangka matriks untuk 3 Aspek Utama
        $matrixQuran = [
            'Tahsin'  => ['nilai' => [], 'total' => 0, 'count' => 0],
            'Tahfidz' => ['nilai' => [], 'total' => 0, 'count' => 0],
            'Kitabah' => ['nilai' => [], 'total' => 0, 'count' => 0],
        ];

        // Inisialisasi setiap bulan dengan nilai null (kosong)
        foreach (['Tahsin', 'Tahfidz', 'Kitabah'] as $aspek) {
            foreach ($bulanAktif as $b) {
                $matrixQuran[$aspek]['nilai'][$b] = null;
            }
        }

        $quranRaw = [];
        if ($db->tableExists('quran_penilaian')) { // Menggunakan tabel yang benar
            $quranRaw = $db->table('quran_penilaian')
                           ->select('bulan, tahsin_nilai, tahfidz_nilai, kitabah_nilai')
                           ->where('student_id', $student_id)
                           ->whereIn('bulan', $bulanAktif)
                           ->where('tahun', $tahun)
                           ->get()->getResultArray();
        }

        // Siapkan array sementara karena 1 bulan bisa memiliki lebih dari 1 pekan
        $tempNilai = ['Tahsin' => [], 'Tahfidz' => [], 'Kitabah' => []];
        foreach ($bulanAktif as $b) {
            $tempNilai['Tahsin'][$b]  = [];
            $tempNilai['Tahfidz'][$b] = [];
            $tempNilai['Kitabah'][$b] = [];
        }

        // Kelompokkan nilai mentah ke dalam bulan yang sesuai
        foreach ($quranRaw as $qr) {
            $bln = $qr['bulan'];
            
            // Konversi koma ke titik (jika guru input desimal pakai koma) dan jadikan tipe angka
            if (!empty(trim($qr['tahsin_nilai']))) {
                $tempNilai['Tahsin'][$bln][] = (float)str_replace(',', '.', $qr['tahsin_nilai']);
            }
            if (!empty(trim($qr['tahfidz_nilai']))) {
                $tempNilai['Tahfidz'][$bln][] = (float)str_replace(',', '.', $qr['tahfidz_nilai']);
            }
            if (!empty(trim($qr['kitabah_nilai']))) {
                $tempNilai['Kitabah'][$bln][] = (float)str_replace(',', '.', $qr['kitabah_nilai']);
            }
        }

        // Hitung rata-rata per bulan untuk dimasukkan ke Rapor
        foreach (['Tahsin', 'Tahfidz', 'Kitabah'] as $aspek) {
            foreach ($bulanAktif as $b) {
                $kumpulanNilai = $tempNilai[$aspek][$b];
                
                if (count($kumpulanNilai) > 0) {
                    // Rata-rata bulan tersebut (Total nilai pekan / jumlah pekan yang diinput)
                    $rataBulan = array_sum($kumpulanNilai) / count($kumpulanNilai);
                    $matrixQuran[$aspek]['nilai'][$b] = round($rataBulan, 1); 
                    
                    // Tambahkan ke kalkulasi total 1 semester untuk kolom 'Rata-Rata/Total'
                    $matrixQuran[$aspek]['total'] += $rataBulan;
                    $matrixQuran[$aspek]['count']++;
                }
            }
        }

        // ==============================================================
        // 8. TARIK DATA EKSTRAKURIKULER, PRAMUKA & PEMINATAN
        // ==============================================================

        // A. Deteksi Kelas Siswa untuk Penamaan Peminatan
        $rombelName     = $dataSiswa['rombel_name'] ?? $dataSiswa['nama_kelas'] ?? $dataSiswa['kelas'] ?? '';
        $labelPeminatan = 'Peminatan';

        if (preg_match('/(7|VII)/i', $rombelName)) {
            $labelPeminatan = 'Peminatan (IT)';
        } elseif (preg_match('/(8|VIII)/i', $rombelName)) {
            $labelPeminatan = 'Peminatan (English)';
        } elseif (preg_match('/(9|IX)/i', $rombelName)) {
            $labelPeminatan = 'Peminatan (TKA)';
        }

        // B. Helper Konversi & Pencatatan Nilai
        $getPredikatEskul = function($nilai) {
            if ($nilai === null || $nilai === '' || $nilai === '-') return '-';
            if (is_numeric($nilai)) {
                $n = (float) $nilai;
                if ($n >= 90) return 'A';
                if ($n >= 80) return 'B';
                if ($n >= 70) return 'C';
                return 'D';
            }
            return strtoupper(trim($nilai));
        };

        $getNumericFromPredikat = function($pred) {
            switch (strtoupper(trim($pred))) {
                case 'A': return 95;
                case 'B': return 85;
                case 'C': return 75;
                case 'D': return 65;
                default: return null;
            }
        };

        // Helper untuk memasukkan nilai ke matriks sesuai format key bulanAktif
        $assignScore = function(&$matrixRow, &$rawList, $dbBulan, $nilai) use ($bulanAktif, $getPredikatEskul, $getNumericFromPredikat) {
            $pred = $getPredikatEskul($nilai);
            if ($pred === '-') return;

            foreach ($bulanAktif as $b) {
                if ((int)$b === (int)$dbBulan) {
                    $matrixRow['bulan'][$b] = $pred;
                    
                    $num = is_numeric($nilai) ? (float)$nilai : $getNumericFromPredikat($pred);
                    if ($num !== null) {
                        $rawList[] = $num;
                    }
                    break;
                }
            }
        };

        // C. Cari Nama Eskul yang Diikuti Siswa
        $namaEskulSiswa = '';
        if ($db->tableExists('eskul_grades') && $db->tableExists('eskul_groups')) {
            $eskulNamaRow = $db->table('eskul_grades eg')
                              ->join('eskul_groups grp', 'grp.id = eg.group_id', 'left')
                              ->select('grp.nama_kelompok')
                              ->where('eg.student_id', $student_id)
                              ->get()->getRowArray();
            if ($eskulNamaRow && !empty($eskulNamaRow['nama_kelompok'])) {
                $namaEskulSiswa = trim($eskulNamaRow['nama_kelompok']);
            }
        }
        $labelEskul = !empty($namaEskulSiswa) ? "Ekstrakurikuler ({$namaEskulSiswa})" : "Ekstrakurikuler";

        // D. Inisialisasi Matriks untuk View
        $matrixEskul = [
            'Pramuka'         => ['label' => 'Pramuka', 'bulan' => [], 'predikat_akhir' => '-'],
            'Ekstrakurikuler' => ['label' => $labelEskul, 'bulan' => [], 'predikat_akhir' => '-'],
            'Peminatan'       => ['label' => $labelPeminatan, 'bulan' => [], 'predikat_akhir' => '-']
        ];
        $rawScores = ['Pramuka' => [], 'Ekstrakurikuler' => [], 'Peminatan' => []];

        foreach ($bulanAktif as $b) {
            $matrixEskul['Pramuka']['bulan'][$b]         = '-';
            $matrixEskul['Ekstrakurikuler']['bulan'][$b] = '-';
            $matrixEskul['Peminatan']['bulan'][$b]       = '-';
        }

        // E. Ambil Nilai Pramuka dari pramuka_grades
        if ($db->tableExists('pramuka_grades')) {
            $pramukaRaw = $db->table('pramuka_grades')
                             ->select('bulan, nilai')
                             ->where('student_id', $student_id)
                             ->whereIn('bulan', $bulanAktif)
                             ->get()->getResultArray();
                             
            foreach ($pramukaRaw as $pr) {
                $assignScore($matrixEskul['Pramuka'], $rawScores['Pramuka'], $pr['bulan'], $pr['nilai']);
            }
        }

        // F. Ambil Nilai Peminatan dari peminatan_grades
        if ($db->tableExists('peminatan_grades')) {
            $peminatanRaw = $db->table('peminatan_grades')
                               ->select('bulan, nilai')
                               ->where('student_id', $student_id)
                               ->whereIn('bulan', $bulanAktif)
                               ->get()->getResultArray();
                               
            foreach ($peminatanRaw as $pm) {
                $assignScore($matrixEskul['Peminatan'], $rawScores['Peminatan'], $pm['bulan'], $pm['nilai']);
            }
        }

        // G. Ambil Nilai Eskul dari eskul_grades
        if ($db->tableExists('eskul_grades')) {
            $eskulRaw = $db->table('eskul_grades')
                             ->select('bulan, nilai')
                             ->where('student_id', $student_id)
                             ->whereIn('bulan', $bulanAktif)
                             ->get()->getResultArray();
                             
            foreach ($eskulRaw as $er) {
                $assignScore($matrixEskul['Ekstrakurikuler'], $rawScores['Ekstrakurikuler'], $er['bulan'], $er['nilai']);
            }
        }

        // H. Hitung Predikat Akhir (Rata-rata Semester)
        foreach (['Pramuka', 'Ekstrakurikuler', 'Peminatan'] as $key) {
            if (!empty($rawScores[$key])) {
                $avg = array_sum($rawScores[$key]) / count($rawScores[$key]);
                $matrixEskul[$key]['predikat_akhir'] = $getPredikatEskul($avg);
            }
        }

        // F. Passing Data ke View
        $data = [
            'dataSiswa'             => $dataSiswa,
            'semester'              => ucfirst($semester),
            'tahun'                 => $tahun,
            'bulanAktif'            => $bulanAktif,
            'namaBulanIndo'         => ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'],
            'matrixAbsen'           => $matrixAbsen,
            'totalAbsen'            => $totalAbsen,
            'kepatuhan'             => $kepatuhan,
            'keteranganPelanggaran' => $keteranganPelanggaran,
            'spiritual'             => $spiritual,
            'sosial'                => $sosial,
            'matrixSumatif'         => $matrixSumatif,
            'anekdot'               => $anekdot,
            'prestasi'              => $prestasi,
            'matrixQuran'           => $matrixQuran,
            'matrixEskul'           => $matrixEskul
        ];

        return view('admin/rapor_berjalan_cetak', $data);
    }
}