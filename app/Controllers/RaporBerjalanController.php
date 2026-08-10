<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class RaporBerjalanController extends BaseController
{
    public function index()
    {
        $db = Database::connect();
        $request = \Config\Services::request();

        // Mengambil ID dari dalam array 'user' di session
        $sessionUser = session()->get('user');
        $student_id  = $sessionUser['id'] ?? null; 
        
        $semester   = $request->getGet('semester') ?? 'ganjil';
        $tahun      = $request->getGet('tahun') ?? date('Y');

        if (empty($student_id)) {
            return redirect()->to(base_url('login'))->with('error', 'Sesi Anda telah habis. Silakan login kembali.');
        }

        // 1. AMBIL DATA PROFIL SISWA
        $dataSiswa = $db->table('users u')
                        ->join('student_profiles sp', 'sp.user_id = u.id', 'left')
                        ->select('u.id, u.username as name, sp.nisn, sp.nis, sp.gender')
                        ->where('u.id', $student_id)
                        ->get()->getRowArray();

        // Ambil nama kelas siswa saat ini
        $kelasSiswa = $db->table('class_rombel_students crs')
                         ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                         ->select('cr.rombel_name')
                         ->where('crs.student_id', $student_id)
                         ->get()->getRowArray();
                         
        $dataSiswa['kelas'] = $kelasSiswa ? $kelasSiswa['rombel_name'] : '-';

        // =========================================================
        // 2. TENTUKAN BULAN YANG SUDAH DILALUI (Logika Titi Mangsa Tgl 5)
        // =========================================================
        $arrayBulanSemester = ($semester === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        
        $tahunLaporan  = (int) $tahun;
        $tahunSekarang = (int) date('Y');
        $bulanSekarang = (int) date('m');
        $hariSekarang  = (int) date('d');

        $bulanAktif = [];

        // A. Cek apakah ini laporan masa lalu (semester yang sudah lewat sama sekali)
        $isLaporanLampau = false;
        if ($tahunLaporan < $tahunSekarang) {
            $isLaporanLampau = true;
        } elseif ($tahunLaporan == $tahunSekarang && $semester === 'ganjil' && $bulanSekarang < 7) {
            // Kasus khusus: Akses laporan ganjil (Jul-Des) di bulan Jan-Jun tahun berikutnya
            $isLaporanLampau = true; 
        }

        if ($isLaporanLampau) {
            // Jika masa lalu, langsung buka semua bulan di semester tersebut
            $bulanAktif = $arrayBulanSemester;
        } else {
            // B. Logika Titi Mangsa (Tanggal 5) untuk semester berjalan
            // Tentukan bulan terakhir yang sudah 'Tutup Buku'
            if ($hariSekarang >= 6) {
                // Tanggal 6 ke atas: Bulan lalu sudah tutup buku
                $batasBulan = $bulanSekarang - 1;
            } else {
                // Tanggal 1 s/d 5: Masih masa input, jadi mundur 2 bulan
                $batasBulan = $bulanSekarang - 2;
            }

            // Penyesuaian angka bulan jika melintasi pergantian tahun (Januari -> Desember)
            if ($batasBulan == 0) $batasBulan = 12;
            if ($batasBulan == -1) $batasBulan = 11;

            // Filter bulan-bulan di semester terpilih yang belum melewati batas
            foreach ($arrayBulanSemester as $b) {
                $intB = (int)$b;
                
                if ($semester === 'ganjil') {
                    // Semester Ganjil (Bulan 7 s/d 12)
                    if ($batasBulan >= 7 && $intB <= $batasBulan) {
                        $bulanAktif[] = $b;
                    }
                } else {
                    // Semester Genap (Bulan 1 s/d 6)
                    if ($batasBulan >= 1 && $batasBulan <= 6 && $intB <= $batasBulan) {
                        $bulanAktif[] = $b;
                    }
                }
            }
        }

       // =======================================================
        // KODE ASLI (Komentari/matikan dulu selama masa uji coba)
        // =======================================================
        /*
        if (empty($bulanAktif)) {
            return view('siswa/rapor_belum_tersedia');
        }
        */

        // =======================================================
        // KODE BYPASS UNTUK UJI COBA TAMPILAN
        // =======================================================
        if (empty($bulanAktif)) {
            // Jika semester ganjil, paksa tampilkan bulan Juli s.d. Desember
            if ($semester === 'ganjil') {
                $bulanAktif = ['07', '08', '09', '10', '11', '12'];
            } 
            // Jika semester genap, paksa tampilkan bulan Januari s.d. Juni
            else {
                $bulanAktif = ['01', '02', '03', '04', '05', '06'];
            }
        }

        // =========================================================
        // 3. TARIK DATA ABSENSI (GROUP BY BULAN)
        // =========================================================
        $absenRaw = $db->table('absensi a')
                       ->join('absensi_details ad', 'a.id = ad.absensi_id')
                       ->select('
                           LPAD(MONTH(a.tanggal), 2, "0") as bulan,
                           SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                           SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                           SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                           SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                           SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t
                       ')
                       ->where('ad.student_id', $student_id)
                       ->whereIn('MONTH(a.tanggal)', $bulanAktif)
                       ->where('YEAR(a.tanggal)', $tahun)
                       ->groupBy('MONTH(a.tanggal)')
                       ->get()->getResultArray();

        $matrixAbsen = ['H' => [], 'S' => [], 'I' => [], 'A' => [], 'T' => []];
        $totalAbsen  = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'T' => 0];
        
        // Inisialisasi default 0
        foreach ($bulanAktif as $b) {
            $matrixAbsen['H'][$b] = 0; $matrixAbsen['S'][$b] = 0; $matrixAbsen['I'][$b] = 0; $matrixAbsen['A'][$b] = 0; $matrixAbsen['T'][$b] = 0;
        }

        // Isi dengan data dari database
        foreach ($absenRaw as $ar) {
            $b = $ar['bulan'];
            $matrixAbsen['H'][$b] = $ar['total_h']; $totalAbsen['H'] += $ar['total_h'];
            $matrixAbsen['S'][$b] = $ar['total_s']; $totalAbsen['S'] += $ar['total_s'];
            $matrixAbsen['I'][$b] = $ar['total_i']; $totalAbsen['I'] += $ar['total_i'];
            $matrixAbsen['A'][$b] = $ar['total_a']; $totalAbsen['A'] += $ar['total_a'];
            $matrixAbsen['T'][$b] = $ar['total_t']; $totalAbsen['T'] += $ar['total_t'];
        }

        // =========================================================
        // 4. TARIK DATA KEPATUHAN & KARAKTER (GROUP BY BULAN)
        // =========================================================
        // Fungsi bantu (Helper) untuk membuat matrix total kasus per bulan
        $buildMatrix = function($tabel, $kolomArray) use ($db, $student_id, $bulanAktif, $tahun) {
            $select = "LPAD(MONTH(tanggal), 2, '0') as bulan";
            foreach ($kolomArray as $kolom) {
                $select .= ", SUM($kolom) as $kolom";
            }
            
            $dataRaw = $db->table($tabel)
                          ->select($select)
                          ->where('student_id', $student_id)
                          ->whereIn('MONTH(tanggal)', $bulanAktif)
                          ->where('YEAR(tanggal)', $tahun)
                          ->groupBy('MONTH(tanggal)')
                          ->get()->getResultArray();

            $matrix = []; $totals = [];
            foreach ($kolomArray as $kolom) {
                $totals[$kolom] = 0;
                foreach ($bulanAktif as $b) {
                    $matrix[$kolom][$b] = 0;
                }
            }
            foreach ($dataRaw as $dr) {
                $b = $dr['bulan'];
                foreach ($kolomArray as $kolom) {
                    $matrix[$kolom][$b] = $dr[$kolom];
                    $totals[$kolom] += $dr[$kolom];
                }
            }
            return ['matrix' => $matrix, 'totals' => $totals];
        };

        $kepatuhan = $buildMatrix('kepatuhan', ['seragam', 'atribut', 'bersih_diri', 'terlambat', 'aturan_kelas', 'masjid']);
        $spiritual = $buildMatrix('aspek_spiritual', ['berdoa', 'kalimat_thoyibah', 'shalat', 'salam', 'syukur', 'lingkungan', 'toleransi']);
        $sosial    = $buildMatrix('aspek_sosial', ['disiplin', 'jujur', 'percaya_diri', 'santun', 'kerjasama', 'tanggung_jawab', 'adil']);

        // =========================================================
        // 5. TARIK NILAI SUMATIF (AKADEMIK)
        // =========================================================
        $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
        $kolomNamaMapel = in_array('subject_name', $db->getFieldNames($tabelMapel)) ? 'subject_name' : 'nama_mapel';
        
        // Ambil semua nilai di semester ini untuk siswa
        $sumatifRaw = $db->table('nilai_sumatif ns')
                         ->join($tabelMapel . ' m', 'm.id = ns.mapel_id', 'left')
                         ->select("LPAD(ns.bulan, 2, '0') as bulan, ns.mapel_id, ns.nilai_angka, m.{$kolomNamaMapel} as nama_mapel")
                         ->where('ns.student_id', $student_id)
                         ->whereIn('ns.bulan', $bulanAktif)
                         ->orderBy("m.{$kolomNamaMapel}", 'ASC')
                         ->get()->getResultArray();

        $matrixSumatif = [];
        foreach ($sumatifRaw as $sr) {
            $mId = $sr['mapel_id'];
            $bln = $sr['bulan'];
            
            if (!isset($matrixSumatif[$mId])) {
                $matrixSumatif[$mId] = [
                    'nama_mapel' => $sr['nama_mapel'] ?? 'Mapel Tidak Diketahui',
                    'nilai' => [], 'total' => 0, 'count' => 0
                ];
                foreach ($bulanAktif as $b) {
                    $matrixSumatif[$mId]['nilai'][$b] = null;
                }
            }
            
            $matrixSumatif[$mId]['nilai'][$bln] = (float)$sr['nilai_angka'];
            $matrixSumatif[$mId]['total'] += (float)$sr['nilai_angka'];
            $matrixSumatif[$mId]['count']++;
        }

        // =========================================================
        // 6. AMBIL CATATAN ANEKDOT & PRESTASI[cite: 5]
        // =========================================================
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

        // =========================================================
        // 7. TARIK DATA NILAI AL-QUR'AN (GROUP BY BULAN)
        // =========================================================
        // Asumsi tabel: nilai_quran (student_id, bulan, tahun, aspek, nilai_angka)
        // Aspek contoh: "Tahfidz", "Tahsin", "Tarjumah"
        $quranRaw = [];
        if ($db->tableExists('nilai_quran')) {
            $quranRaw = $db->table('nilai_quran')
                           ->select("LPAD(bulan, 2, '0') as bulan, aspek, nilai_angka")
                           ->where('student_id', $student_id)
                           ->whereIn('LPAD(bulan, 2, "0")', $bulanAktif)
                           ->where('tahun', $tahun)
                           ->get()->getResultArray();
        }

        $matrixQuran = [];
        foreach ($quranRaw as $qr) {
            $aspek = $qr['aspek'];
            $bln   = $qr['bulan'];
            
            if (!isset($matrixQuran[$aspek])) {
                $matrixQuran[$aspek] = ['nilai' => [], 'total' => 0, 'count' => 0];
                foreach ($bulanAktif as $b) {
                    $matrixQuran[$aspek]['nilai'][$b] = null;
                }
            }
            $matrixQuran[$aspek]['nilai'][$bln] = (float)$qr['nilai_angka'];
            $matrixQuran[$aspek]['total'] += (float)$qr['nilai_angka'];
            $matrixQuran[$aspek]['count']++;
        }

        // =========================================================
        // 8. TARIK DATA EKSTRAKURIKULER & PRAMUKA (GROUP BY BULAN)
        // =========================================================
        // Asumsi tabel: nilai_eskul (student_id, bulan, tahun, nama_eskul, predikat)
        // Predikat biasanya berupa huruf/teks: "A", "B", "Sangat Baik", "Aktif", dll.
        $eskulRaw = [];
        if ($db->tableExists('nilai_eskul')) {
            $eskulRaw = $db->table('nilai_eskul')
                           ->select("LPAD(bulan, 2, '0') as bulan, nama_eskul, predikat")
                           ->where('student_id', $student_id)
                           ->whereIn('LPAD(bulan, 2, "0")', $bulanAktif)
                           ->where('tahun', $tahun)
                           ->get()->getResultArray();
        }

        $matrixEskul = [];
        foreach ($eskulRaw as $er) {
            $nama = $er['nama_eskul'];
            $bln  = $er['bulan'];
            
            if (!isset($matrixEskul[$nama])) {
                $matrixEskul[$nama] = [];
                foreach ($bulanAktif as $b) {
                    $matrixEskul[$nama][$b] = '-';
                }
            }
            $matrixEskul[$nama][$bln] = $er['predikat'];
        }

        // =========================================================
        // KEMAS DATA KE VIEW
        // =========================================================
        $data = [
            'dataSiswa'     => $dataSiswa,
            'semester'      => ucfirst($semester),
            'tahun'         => $tahun,
            'bulanAktif'    => $bulanAktif,
            'namaBulanIndo' => ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'],
            'matrixAbsen'   => $matrixAbsen,
            'totalAbsen'    => $totalAbsen,
            'kepatuhan'     => $kepatuhan,
            'spiritual'     => $spiritual,
            'sosial'        => $sosial,
            'matrixSumatif' => $matrixSumatif,
            'anekdot'       => $anekdot,
            'prestasi'      => $prestasi,
            'matrixQuran'   => $matrixQuran,
            'matrixEskul'   => $matrixEskul
        ];

        return view('siswa/rapor_berjalan', $data);
    }
}