<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class RekapSekolahController extends BaseController
{
    public function index()
    {
        $db      = Database::connect();
        $request = \Config\Services::request();

        // =========================================================
        // 1. AMBIL FILTER DARI URL (GET)
        // =========================================================
        $tipe_filter = $request->getGet('tipe_filter') ?? 'bulan';
        $bulan       = $request->getGet('bulan') ?? date('m');
        $semester    = $request->getGet('semester') ?? 'ganjil';
        $tahun       = $request->getGet('tahun') ?? date('Y');

        // Tentukan Array Bulan berdasarkan filter[cite: 3]
        if ($tipe_filter === 'semester') {
            if ($semester === 'ganjil') {
                $bulan_array = ['07', '08', '09', '10', '11', '12'];
            } else {
                $bulan_array = ['01', '02', '03', '04', '05', '06'];
            }
            // Jumlahkan total hari efektif di semester tersebut[cite: 3]
            $cekHari = $db->table('hari_efektif')
                          ->whereIn('bulan', $bulan_array)
                          ->where('tahun', $tahun)
                          ->selectSum('jumlah_hari')
                          ->get()->getRowArray();
            $hariEfektif = $cekHari['jumlah_hari'] ? (int) $cekHari['jumlah_hari'] : 0;
        } else {
            $bulan_array = [$bulan];
            $cekHari = $db->table('hari_efektif')
                          ->where('bulan', $bulan)
                          ->where('tahun', $tahun)
                          ->get()->getRowArray();
            $hariEfektif = $cekHari ? (int) $cekHari['jumlah_hari'] : 0;
        }

        // =========================================================
        // 2. LOGIKA REKAP ABSENSI (Dari AbsensiController Lama)
        // =========================================================
        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();

        $rekapAbsensi = []; 
        $tingkatCounts = [];
        $rekapTingkat  = [];
        
        $totalTargetSekolah = 0;
        $totalSemuaH = 0; $totalSemuaS = 0; $totalSemuaI = 0; $totalSemuaA = 0; $totalSemuaT = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            
            preg_match('/\d+/', $rombel['rombel_name'], $matches);
            $tingkat = $matches[0] ?? 'Lainnya';
            
            $jumlahSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();
            $targetKehadiran = $jumlahSiswa * $hariEfektif;
            $totalTargetSekolah += $targetKehadiran;

            // Hitung Absen berdasarkan Array Bulan[cite: 3]
            $absen = $db->table('absensi a')
                        ->join('absensi_details ad', 'a.id = ad.absensi_id')
                        ->select('
                            SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                            SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                            SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                            SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                            SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t
                        ')
                        ->where('a.rombel_id', $rombel_id)
                        ->whereIn('MONTH(a.tanggal)', $bulan_array)
                        ->where('YEAR(a.tanggal)', $tahun)
                        ->get()->getRowArray();

            $h = (int)($absen['total_h'] ?? 0);
            $s = (int)($absen['total_s'] ?? 0);
            $i = (int)($absen['total_i'] ?? 0);
            $a = (int)($absen['total_a'] ?? 0);
            $t = (int)($absen['total_t'] ?? 0);

            $totalSemuaH += $h; $totalSemuaS += $s; $totalSemuaI += $i; $totalSemuaA += $a; $totalSemuaT += $t;

            $rekapAbsensi[] = [
                'rombel_name' => $rombel['rombel_name'],
                'tingkat'     => $tingkat,
                'persen_h'    => $targetKehadiran > 0 ? ($h / $targetKehadiran) * 100 : 0,
                'persen_s'    => $targetKehadiran > 0 ? ($s / $targetKehadiran) * 100 : 0,
                'persen_i'    => $targetKehadiran > 0 ? ($i / $targetKehadiran) * 100 : 0,
                'persen_a'    => $targetKehadiran > 0 ? ($a / $targetKehadiran) * 100 : 0,
                'persen_t'    => $targetKehadiran > 0 ? ($t / $targetKehadiran) * 100 : 0,
            ];

            if (!isset($tingkatCounts[$tingkat])) {
                $tingkatCounts[$tingkat] = 0;
                $rekapTingkat[$tingkat] = ['h' => 0, 'target' => 0];
            }
            $tingkatCounts[$tingkat]++;
            $rekapTingkat[$tingkat]['h'] += $h;
            $rekapTingkat[$tingkat]['target'] += $targetKehadiran;
        }

        // Ambil SEMUA data hari efektif di tahun tersebut untuk JavaScript AJAX Form[cite: 3]
        $allHariEfektif = $db->table('hari_efektif')->where('tahun', $tahun)->get()->getResultArray();
        $hariEfektifList = [];
        foreach ($allHariEfektif as $h) {
            $hariEfektifList[$h['bulan']] = $h['jumlah_hari'];
        }

        // =========================================================
        // 3. LOGIKA REKAP KEPATUHAN (Akumulasi per Kelas)
        // =========================================================
        $total_sekolah_seragam = 0; $total_sekolah_atribut = 0;
        $total_sekolah_bersih  = 0; $total_sekolah_lambat  = 0;
        $total_sekolah_aturan  = 0; $total_sekolah_masjid  = 0;

        $rekapKepatuhan = [];

        // Kita gunakan looping daftar kelas yang sama dengan absensi
        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Ambil total pelanggaran khusus untuk kelas ini pada bulan/semester terpilih
            $kepatuhan = $db->table('kepatuhan')
                            ->select('
                                SUM(seragam) as seragam,
                                SUM(atribut) as atribut,
                                SUM(bersih_diri) as bersih_diri,
                                SUM(terlambat) as terlambat,
                                SUM(aturan_kelas) as aturan_kelas,
                                SUM(masjid) as masjid,
                                GROUP_CONCAT(keterangan SEPARATOR ", ") as keterangan
                            ')
                            ->where('rombel_id', $rombel_id)
                            ->whereIn('MONTH(tanggal)', $bulan_array)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            // Pastikan jika null/kosong, diubah menjadi angka 0
            $s  = (int)($kepatuhan['seragam'] ?? 0);
            $a  = (int)($kepatuhan['atribut'] ?? 0);
            $b  = (int)($kepatuhan['bersih_diri'] ?? 0);
            $t  = (int)($kepatuhan['terlambat'] ?? 0);
            $ak = (int)($kepatuhan['aturan_kelas'] ?? 0);
            $m  = (int)($kepatuhan['masjid'] ?? 0);
            $ket = $kepatuhan['keterangan'] ?? '';

            // Hitung akumulasi untuk total bawah tabel
            $total_sekolah_seragam += $s;
            $total_sekolah_atribut += $a;
            $total_sekolah_bersih  += $b;
            $total_sekolah_lambat  += $t;
            $total_sekolah_aturan  += $ak;
            $total_sekolah_masjid  += $m;

            // Masukkan ke dalam array View
            $rekapKepatuhan[] = [
                'rombel_name'  => $rombel['rombel_name'],
                'seragam'      => $s,
                'atribut'      => $a,
                'bersih_diri'  => $b,
                'terlambat'    => $t,
                'aturan_kelas' => $ak,
                'masjid'       => $m,
                'keterangan'   => $ket
            ];
        }
        
        $grand_total_kasus = $total_sekolah_seragam + $total_sekolah_atribut + $total_sekolah_bersih + $total_sekolah_lambat + $total_sekolah_aturan + $total_sekolah_masjid;

        // =========================================================
        // 3B. LOGIKA REKAP YAUMIYAH (Siswa -> % -> Rata Kelas -> Rata Sekolah)
        // =========================================================
        $rekapYaumiyah = [];

        // Penyesuaian target berdasarkan filter (Bulan vs Semester)
        $targetHarian = $hariEfektif;
        $targetMingguan = ceil($hariEfektif / 5);
        
        // Target Shaum: 2x per bulan, tapi jika hanya 1 bulan dan hari efektif <= 15, maka 1x
        $jumlahBulanAktif = count($bulan_array);
        $targetShaum = $jumlahBulanAktif * 2; 
        if ($jumlahBulanAktif == 1 && $hariEfektif <= 15) {
            $targetShaum = 1;
        }

        // Variabel penampung untuk menghitung rata-rata sekolah dari rata-rata kelas
        $sum_kelas_p = [
            'dzuhur' => 0, 'ashar' => 0, 'bakdiah' => 0, 'duha' => 0, 
            'tahajud' => 0, 'tilawah' => 0, 'infaq' => 0, 'shaum' => 0, 'literasi' => 0
        ];
        $jumlahKelasAktif = 0;

        // Helper fungsi konversi nilai ke persentase (Maksimal 100%)
        $calcP = function($total, $target) {
            if ($target == 0) return 0;
            $p = ($total / $target) * 100;
            return $p > 100 ? 100 : $p;
        };

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            
            // Ambil jumlah seluruh siswa yang terdaftar di kelas ini
            $jumlahSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();
            
            // Inisialisasi total akumulasi persentase siswa dalam 1 kelas
            $sum_siswa_p = [
                'dz' => 0, 'as' => 0, 'bd' => 0, 'dh' => 0, 
                'th' => 0, 'tl' => 0, 'if' => 0, 'sh' => 0, 'lt' => 0
            ];

            if ($jumlahSiswa > 0) {
                // TAHAP 1: Ambil data yaumiyah dan kelompokkan (GROUP BY) PER SISWA
                $yDataSiswa = $db->table('yaumiyah y')
                            ->join('class_rombel_students crs', 'crs.student_id = y.student_id')
                            ->select('
                                y.student_id,
                                SUM(y.dzuhur) as t_dz, SUM(y.ashar) as t_as, SUM(y.bakdiah_dzuhur) as t_bd,
                                SUM(y.duha) as t_dh, SUM(y.tahajud) as t_th, SUM(y.tilawah) as t_tl,
                                SUM(y.infaq) as t_if, SUM(y.shaum) as t_sh, SUM(y.literasi) as t_lt
                            ')
                            ->where('crs.rombel_id', $rombel_id)
                            ->whereIn('MONTH(y.tanggal)', $bulan_array)
                            ->where('YEAR(y.tanggal)', $tahun)
                            ->where('DAYOFWEEK(y.tanggal) !=', 1) // Abaikan Minggu
                            ->where('DAYOFWEEK(y.tanggal) !=', 7) // Abaikan Sabtu
                            ->groupBy('y.student_id')
                            ->get()->getResultArray();

                // TAHAP 2: Ubah ke persentase (dilimit 100) masing-masing siswa, lalu jumlahkan ke variabel kelas
                foreach ($yDataSiswa as $siswa) {
                    $sum_siswa_p['dz'] += $calcP((int)($siswa['t_dz'] ?? 0), $targetHarian);
                    $sum_siswa_p['as'] += $calcP((int)($siswa['t_as'] ?? 0), $targetHarian);
                    $sum_siswa_p['bd'] += $calcP((int)($siswa['t_bd'] ?? 0), $targetHarian);
                    $sum_siswa_p['dh'] += $calcP((int)($siswa['t_dh'] ?? 0), $targetHarian);
                    $sum_siswa_p['th'] += $calcP((int)($siswa['t_th'] ?? 0), $targetMingguan);
                    $sum_siswa_p['tl'] += $calcP((int)($siswa['t_tl'] ?? 0), $targetHarian);
                    $sum_siswa_p['if'] += $calcP((int)($siswa['t_if'] ?? 0), $targetMingguan);
                    $sum_siswa_p['sh'] += $calcP((int)($siswa['t_sh'] ?? 0), $targetShaum);
                    $sum_siswa_p['lt'] += $calcP((int)($siswa['t_lt'] ?? 0), $targetHarian);
                }

                $jumlahKelasAktif++;
            }

            // TAHAP 3: Hitung Rata-rata Kelas (Total Persentase Siswa dibagi Jumlah Total Siswa di kelas)
            // Siswa yang tidak mengisi sama sekali otomatis nilainya 0 dan akan menurunkan rata-rata.
            $rata_kls_dz = $jumlahSiswa > 0 ? ($sum_siswa_p['dz'] / $jumlahSiswa) : 0;
            $rata_kls_as = $jumlahSiswa > 0 ? ($sum_siswa_p['as'] / $jumlahSiswa) : 0;
            $rata_kls_bd = $jumlahSiswa > 0 ? ($sum_siswa_p['bd'] / $jumlahSiswa) : 0;
            $rata_kls_dh = $jumlahSiswa > 0 ? ($sum_siswa_p['dh'] / $jumlahSiswa) : 0;
            $rata_kls_th = $jumlahSiswa > 0 ? ($sum_siswa_p['th'] / $jumlahSiswa) : 0;
            $rata_kls_tl = $jumlahSiswa > 0 ? ($sum_siswa_p['tl'] / $jumlahSiswa) : 0;
            $rata_kls_if = $jumlahSiswa > 0 ? ($sum_siswa_p['if'] / $jumlahSiswa) : 0;
            $rata_kls_sh = $jumlahSiswa > 0 ? ($sum_siswa_p['sh'] / $jumlahSiswa) : 0;
            $rata_kls_lt = $jumlahSiswa > 0 ? ($sum_siswa_p['lt'] / $jumlahSiswa) : 0;

            // Masukkan data untuk baris tabel per kelas
            $rekapYaumiyah[] = [
                'rombel_name' => $rombel['rombel_name'],
                'p_dzuhur'    => $rata_kls_dz,
                'p_ashar'     => $rata_kls_as,
                'p_bakdiah'   => $rata_kls_bd,
                'p_duha'      => $rata_kls_dh,
                'p_tahajud'   => $rata_kls_th,
                'p_tilawah'   => $rata_kls_tl,
                'p_infaq'     => $rata_kls_if,
                'p_shaum'     => $rata_kls_sh,
                'p_literasi'  => $rata_kls_lt,
            ];

            // Akumulasikan persentase rata-rata kelas untuk dihitung tingkat sekolah
            if ($jumlahSiswa > 0) {
                $sum_kelas_p['dzuhur']   += $rata_kls_dz;
                $sum_kelas_p['ashar']    += $rata_kls_as;
                $sum_kelas_p['bakdiah']  += $rata_kls_bd;
                $sum_kelas_p['duha']     += $rata_kls_dh;
                $sum_kelas_p['tahajud']  += $rata_kls_th;
                $sum_kelas_p['tilawah']  += $rata_kls_tl;
                $sum_kelas_p['infaq']    += $rata_kls_if;
                $sum_kelas_p['shaum']    += $rata_kls_sh;
                $sum_kelas_p['literasi'] += $rata_kls_lt;
            }
        }

        // TAHAP 4: Hitung Rata-rata Sekolah (Total Rata-rata Kelas dibagi Jumlah Kelas)
        $rata_yaumiyah = [
            'dzuhur'   => $jumlahKelasAktif > 0 ? ($sum_kelas_p['dzuhur'] / $jumlahKelasAktif) : 0,
            'ashar'    => $jumlahKelasAktif > 0 ? ($sum_kelas_p['ashar'] / $jumlahKelasAktif) : 0,
            'bakdiah'  => $jumlahKelasAktif > 0 ? ($sum_kelas_p['bakdiah'] / $jumlahKelasAktif) : 0,
            'duha'     => $jumlahKelasAktif > 0 ? ($sum_kelas_p['duha'] / $jumlahKelasAktif) : 0,
            'tahajud'  => $jumlahKelasAktif > 0 ? ($sum_kelas_p['tahajud'] / $jumlahKelasAktif) : 0,
            'tilawah'  => $jumlahKelasAktif > 0 ? ($sum_kelas_p['tilawah'] / $jumlahKelasAktif) : 0,
            'infaq'    => $jumlahKelasAktif > 0 ? ($sum_kelas_p['infaq'] / $jumlahKelasAktif) : 0,
            'shaum'    => $jumlahKelasAktif > 0 ? ($sum_kelas_p['shaum'] / $jumlahKelasAktif) : 0,
            'literasi' => $jumlahKelasAktif > 0 ? ($sum_kelas_p['literasi'] / $jumlahKelasAktif) : 0,
        ];

        // =========================================================
        // 3C. LOGIKA REKAP AL-QUR'AN (Rata-rata Nilai Kelas)
        // =========================================================
        $rekapQuran = [];
        $tot_tahsin_sek = 0; $tot_tahfidz_sek = 0; $tot_kitabah_sek = 0;
        $count_tahsin_sek = 0; $count_tahfidz_sek = 0; $count_kitabah_sek = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            
            // Ambil data penilaian Quran untuk kelas ini
            $qData = $db->table('quran_penilaian qp')
                        ->join('class_rombel_students crs', 'crs.student_id = qp.student_id')
                        ->select('qp.tahsin_nilai, qp.tahfidz_nilai, qp.kitabah_nilai')
                        ->where('crs.rombel_id', $rombel_id)
                        ->whereIn('qp.bulan', $bulan_array)
                        ->where('qp.tahun', $tahun)
                        ->get()->getResultArray();

            $tahsin_sum = 0; $tahfidz_sum = 0; $kitabah_sum = 0;
            $tahsin_count = 0; $tahfidz_count = 0; $kitabah_count = 0;

            // Kalkulasi rata-rata per siswa dengan mengkonversi koma ke titik
            foreach ($qData as $qd) {
                if (!empty(trim($qd['tahsin_nilai']))) {
                    $tahsin_sum += (float)str_replace(',', '.', $qd['tahsin_nilai']);
                    $tahsin_count++;
                }
                if (!empty(trim($qd['tahfidz_nilai']))) {
                    $tahfidz_sum += (float)str_replace(',', '.', $qd['tahfidz_nilai']);
                    $tahfidz_count++;
                }
                if (!empty(trim($qd['kitabah_nilai']))) {
                    $kitabah_sum += (float)str_replace(',', '.', $qd['kitabah_nilai']);
                    $kitabah_count++;
                }
            }

            $avg_tahsin_kls  = $tahsin_count > 0 ? ($tahsin_sum / $tahsin_count) : 0;
            $avg_tahfidz_kls = $tahfidz_count > 0 ? ($tahfidz_sum / $tahfidz_count) : 0;
            $avg_kitabah_kls = $kitabah_count > 0 ? ($kitabah_sum / $kitabah_count) : 0;

            // Akumulasi rata-rata total seluruh sekolah
            $tot_tahsin_sek += $tahsin_sum; $count_tahsin_sek += $tahsin_count;
            $tot_tahfidz_sek += $tahfidz_sum; $count_tahfidz_sek += $tahfidz_count;
            $tot_kitabah_sek += $kitabah_sum; $count_kitabah_sek += $kitabah_count;

            $rekapQuran[] = [
                'rombel_name' => $rombel['rombel_name'],
                'avg_tahsin'  => $avg_tahsin_kls,
                'avg_tahfidz' => $avg_tahfidz_kls,
                'avg_kitabah' => $avg_kitabah_kls,
            ];
        }

        $rata_quran_sekolah = [
            'tahsin'  => $count_tahsin_sek > 0 ? ($tot_tahsin_sek / $count_tahsin_sek) : 0,
            'tahfidz' => $count_tahfidz_sek > 0 ? ($tot_tahfidz_sek / $count_tahfidz_sek) : 0,
            'kitabah' => $count_kitabah_sek > 0 ? ($tot_kitabah_sek / $count_kitabah_sek) : 0,
        ];

       // =========================================================
        // 3D. LOGIKA REKAP SPIRITUAL (Akumulasi Insiden / Catatan)
        // =========================================================
        $total_sekolah_berdoa     = 0; $total_sekolah_kalimat = 0;
        $total_sekolah_shalat     = 0; $total_sekolah_salam   = 0;
        $total_sekolah_syukur     = 0; $total_sekolah_lingkungan = 0;
        $total_sekolah_toleransi  = 0;

        $rekapSpiritual = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Ambil total catatan spiritual khusus untuk kelas ini pada bulan/semester terpilih
            $spiritual = $db->table('aspek_spiritual')
                            ->select('
                                SUM(berdoa) as berdoa,
                                SUM(kalimat_thoyibah) as kalimat_thoyibah,
                                SUM(shalat) as shalat,
                                SUM(salam) as salam,
                                SUM(syukur) as syukur,
                                SUM(lingkungan) as lingkungan,
                                SUM(toleransi) as toleransi,
                                GROUP_CONCAT(keterangan SEPARATOR ", ") as keterangan
                            ')
                            ->where('rombel_id', $rombel_id)
                            ->whereIn('MONTH(tanggal)', $bulan_array)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            $b  = (int)($spiritual['berdoa'] ?? 0);
            $kt = (int)($spiritual['kalimat_thoyibah'] ?? 0);
            $sh = (int)($spiritual['shalat'] ?? 0);
            $sl = (int)($spiritual['salam'] ?? 0);
            $sy = (int)($spiritual['syukur'] ?? 0);
            $l  = (int)($spiritual['lingkungan'] ?? 0);
            $t  = (int)($spiritual['toleransi'] ?? 0);
            $ket = $spiritual['keterangan'] ?? '';

            // Akumulasi total sekolah
            $total_sekolah_berdoa     += $b;
            $total_sekolah_kalimat    += $kt;
            $total_sekolah_shalat     += $sh;
            $total_sekolah_salam      += $sl;
            $total_sekolah_syukur     += $sy;
            $total_sekolah_lingkungan += $l;
            $total_sekolah_toleransi  += $t;

            $rekapSpiritual[] = [
                'rombel_name'      => $rombel['rombel_name'],
                'berdoa'           => $b,
                'kalimat_thoyibah' => $kt,
                'shalat'           => $sh,
                'salam'            => $sl,
                'syukur'           => $sy,
                'lingkungan'       => $l,
                'toleransi'        => $t,
                'keterangan'       => $ket
            ];
        }

        $grand_total_spiritual = $total_sekolah_berdoa + $total_sekolah_kalimat + $total_sekolah_shalat + $total_sekolah_salam + $total_sekolah_syukur + $total_sekolah_lingkungan + $total_sekolah_toleransi;

        // =========================================================
        // 3E. LOGIKA REKAP SOSIAL (Akumulasi Insiden / Catatan)
        // =========================================================
        $total_sekolah_disiplin       = 0; $total_sekolah_jujur   = 0;
        $total_sekolah_percaya_diri   = 0; $total_sekolah_santun  = 0;
        $total_sekolah_kerjasama      = 0; $total_sekolah_tj      = 0;
        $total_sekolah_adil           = 0;

        $rekapSosial = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Ambil total catatan sosial disesuaikan dengan field di database
            $sosial = $db->table('aspek_sosial')
                            ->select('
                                SUM(disiplin) as disiplin,
                                SUM(jujur) as jujur,
                                SUM(percaya_diri) as percaya_diri,
                                SUM(santun) as santun,
                                SUM(kerjasama) as kerjasama,
                                SUM(tanggung_jawab) as tanggung_jawab,
                                SUM(adil) as adil,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR ", ") as keterangan
                            ')
                            ->where('rombel_id', $rombel_id)
                            ->whereIn('MONTH(tanggal)', $bulan_array)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            $ds  = (int)($sosial['disiplin'] ?? 0);
            $jj  = (int)($sosial['jujur'] ?? 0);
            $pd  = (int)($sosial['percaya_diri'] ?? 0);
            $st  = (int)($sosial['santun'] ?? 0);
            $kj  = (int)($sosial['kerjasama'] ?? 0);
            $tj  = (int)($sosial['tanggung_jawab'] ?? 0);
            $ad  = (int)($sosial['adil'] ?? 0);
            $ket = $sosial['keterangan'] ?? '';

            // Akumulasi total sekolah
            $total_sekolah_disiplin       += $ds;
            $total_sekolah_jujur          += $jj;
            $total_sekolah_percaya_diri   += $pd;
            $total_sekolah_santun         += $st;
            $total_sekolah_kerjasama      += $kj;
            $total_sekolah_tj             += $tj;
            $total_sekolah_adil           += $ad;

            $rekapSosial[] = [
                'rombel_name'      => $rombel['rombel_name'],
                'disiplin'         => $ds,
                'jujur'            => $jj,
                'percaya_diri'     => $pd,
                'santun'           => $st,
                'kerjasama'        => $kj,
                'tanggung_jawab'   => $tj,
                'adil'             => $ad,
                'keterangan'       => $ket
            ];
        }

        $grand_total_sosial = $total_sekolah_disiplin + $total_sekolah_jujur + $total_sekolah_percaya_diri + $total_sekolah_santun + $total_sekolah_kerjasama + $total_sekolah_tj + $total_sekolah_adil;
        
        // =========================================================
        // 3F. LOGIKA REKAP PEMINATAN & PRAMUKA (Per Kelas)
        // =========================================================
        $rekapPemPra = [];
        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Menggunakan AVG() untuk mencari rata-rata
            $pemData = $db->table('peminatan_grades')
                            ->select('AVG(nilai) as rata_peminatan')
                            ->where('rombel_id', $rombel_id)
                            ->whereIn('bulan', $bulan_array) 
                            // ->where('tahun_ajaran', $tahun_ajaran) // Buka jika diperlukan
                            ->get()->getRowArray();

            $praData = $db->table('pramuka_grades')
                            ->select('AVG(nilai) as rata_pramuka')
                            ->where('rombel_id', $rombel_id)
                            ->whereIn('bulan', $bulan_array)
                            // ->where('tahun_ajaran', $tahun_ajaran) // Buka jika diperlukan
                            ->get()->getRowArray();

            $rekapPemPra[] = [
                'rombel_name' => $rombel['rombel_name'],
                // round() digunakan agar angka di belakang koma lebih rapi (maksimal 2 digit)
                'peminatan'   => round((float)($pemData['rata_peminatan'] ?? 0), 2),
                'pramuka'     => round((float)($praData['rata_pramuka'] ?? 0), 2)
            ];
        }

        // =========================================================
        // 3G. LOGIKA REKAP ESKUL (Per Kelompok)
        // =========================================================
        $rekapEskul = [];
        
        if ($db->tableExists('eskul_groups')) {
            $daftarKelompok = $db->table('eskul_groups')->orderBy('nama_kelompok', 'ASC')->get()->getResultArray();
            
            foreach ($daftarKelompok as $kelompok) {
                $kelompok_id = $kelompok['id'];

                // Menggunakan AVG() untuk mencari rata-rata
                $eskulData = $db->table('eskul_grades')
                                ->select('AVG(nilai) as rata_nilai')
                                ->where('group_id', $kelompok_id)
                                ->whereIn('bulan', $bulan_array)
                                // ->where('tahun_ajaran', $tahun_ajaran) // Buka jika diperlukan
                                ->get()->getRowArray();

                $rekapEskul[] = [
                    'nama_kelompok' => $kelompok['nama_kelompok'],
                    // Kehadiran dihilangkan, langsung ke nilai rata-rata
                    'nilai'         => round((float)($eskulData['rata_nilai'] ?? 0), 2)
                ];
            }
        }

        // =========================================================
        // 3H. LOGIKA REKAP NILAI SUMATIF (Seluruh Sekolah & Filter BK)
        // =========================================================
        
        // 1. Dapatkan ID Tahun Ajaran
        $idTahunAjaran = $tahunAktifId ?? ($tahun_ajaran_id ?? 0);
        if ($idTahunAjaran == 0 && isset($tahun) && $db->tableExists('academic_years')) {
            $cekTahun = $db->table('academic_years')->where('academic_year', $tahun)->orWhere('id', $tahun)->get()->getRowArray();
            if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
        }
        if ($idTahunAjaran == 0 && $db->tableExists('academic_years')) {
            $cekTahun = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
        }

        $daftarMapel = [];
        $mapelDitemukan = []; 

        // 2. Setup Tabel Master
        $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
        $mapelFields = $db->getFieldNames($tabelMapel);
        $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
        $hasCombinedTable = $db->tableExists('schedule_combined_subjects');

        // 3. Ambil Jadwal Aktif Sekolah
        $jadwalAktif = $db->tableExists('schedule_versions') ? 
                       $db->table('schedule_versions')->where('academic_year_id', $idTahunAjaran)->where('is_active', 1)->get()->getRowArray() : null;
                       
        if (!$jadwalAktif && $db->tableExists('schedule_versions')) {
            $jadwalAktif = $db->table('schedule_versions')->where('is_active', 1)->get()->getRowArray();
        }

        // 4. Tarik Semua Mapel dari Jadwal Tingkat Sekolah (Bukan Per Guru)
        if ($jadwalAktif) {
            $jadwalAktifId = $jadwalAktif['id'];
            $csFields = $db->getFieldNames('class_schedules');
            $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
            $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;

            // A. AMBIL MAPEL GABUNGAN TERLEBIH DAHULU
            if ($kolomCombinedId && $hasCombinedTable) {
                $mapelGabungan = $db->table('class_schedules cs')
                             ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name")  
                             ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                             ->where('cs.version_id', $jadwalAktifId)
                             ->where("cs.{$kolomCombinedId} IS NOT NULL")
                             ->where("cs.{$kolomCombinedId} !=", 0)
                             ->groupBy("cs.{$kolomCombinedId}") // Ambil unik se-sekolah
                             ->get()->getResultArray();
                             
                foreach ($mapelGabungan as $mg) {
                    $namaMapel = trim($mg['combined_name'] ?? '');
                    
                    // Filter: Sembunyikan Bimbingan Konseling / BK
                    if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') {
                        continue;
                    }

                    $cId = 'C_' . $mg['combined_id'];
                    if (!in_array($cId, $mapelDitemukan)) {
                        $daftarMapel[] = ['id' => $cId, 'nama_mapel' => $namaMapel];
                        $mapelDitemukan[] = $cId;
                    }
                }
            }

            // B. AMBIL MAPEL REGULER (YANG TIDAK DIGABUNG)
            $queryReguler = $db->table('class_schedules cs')
                          ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                          ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                          ->where('cs.version_id', $jadwalAktifId)
                          ->where("cs.{$kolomSubjectId} IS NOT NULL")
                          ->where("cs.{$kolomSubjectId} !=", 0);
                          
            // Filter sangat penting: Jangan ambil mapel asli jika record tersebut adalah jadwal mapel gabungan
            if ($kolomCombinedId) {
                $queryReguler->groupStart()
                             ->where("cs.{$kolomCombinedId} IS NULL")
                             ->orWhere("cs.{$kolomCombinedId}", 0)
                             ->groupEnd();
            }
            
            $mapelReguler = $queryReguler->groupBy("cs.{$kolomSubjectId}")->get()->getResultArray();
                          
            foreach ($mapelReguler as $m) {
                $namaMapel = trim($m['subject_name'] ?? '');
                
                // Filter: Sembunyikan Bimbingan Konseling / BK
                if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') {
                    continue;
                }

                $mId = 'S_' . $m['id'];
                if (!in_array($mId, $mapelDitemukan)) {
                    $daftarMapel[] = ['id' => $mId, 'nama_mapel' => $namaMapel];
                    $mapelDitemukan[] = $mId;
                }
            }
        }

        // Urutkan Mapel sesuai abjad A-Z agar tabel rapi
        usort($daftarMapel, function($a, $b) {
            return strcmp($a['nama_mapel'], $b['nama_mapel']);
        });

        // 5. Looping Hitung Rata-rata dari nilai_sumatif
        $rekapSumatif = [];
        $rataSumatifMapel = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            $rowNilai = ['rombel_name' => $rombel['rombel_name']];
            
            foreach ($daftarMapel as $mapel) {
                $mapel_id = $mapel['id']; 
                
                $sumatifQuery = $db->table('nilai_sumatif')
                                ->select('AVG(nilai_angka) as rata_nilai')
                                ->where('rombel_id', $rombel_id)
                                ->where('mapel_id', $mapel_id)
                                ->where('academic_year_id', $idTahunAjaran);
                                
                if (!empty($bulan_array)) {
                    $sumatifQuery->whereIn('bulan', $bulan_array);
                }
                
                $sumatifData = $sumatifQuery->get()->getRowArray();
                $nilaiAvg = round((float)($sumatifData['rata_nilai'] ?? 0), 2);
                
                $rowNilai['mapel_' . $mapel_id] = $nilaiAvg;
                
                if (!isset($rataSumatifMapel[$mapel_id])) {
                    $rataSumatifMapel[$mapel_id] = ['total' => 0, 'count' => 0];
                }
                if ($nilaiAvg > 0) {
                    $rataSumatifMapel[$mapel_id]['total'] += $nilaiAvg;
                    $rataSumatifMapel[$mapel_id]['count']++;
                }
            }
            $rekapSumatif[] = $rowNilai;
        }

        // =========================================================
        // 3I. LOGIKA REKAP ANEKDOT (Akumulasi Insiden / Catatan)
        // =========================================================
        $rekapAnekdot = [];
        $total_sekolah_anekdot = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Menggunakan tabel 'catatan_anekdot' dan kolom 'kejadian'
            $anekdot = $db->table('catatan_anekdot a')
                            ->join('class_rombel_students crs', 'crs.student_id = a.student_id', 'left')
                            ->select('
                                COUNT(a.id) as total_insiden,
                                GROUP_CONCAT(NULLIF(a.kejadian, "") SEPARATOR ", ") as keterangan
                            ')
                            ->where('crs.rombel_id', $rombel_id)
                            ->whereIn('MONTH(a.tanggal)', $bulan_array)
                            ->where('YEAR(a.tanggal)', $tahun)
                            ->get()->getRowArray();

            $tot = (int)($anekdot['total_insiden'] ?? 0);
            $ket = $anekdot['keterangan'] ?? '';

            $total_sekolah_anekdot += $tot;

            $rekapAnekdot[] = [
                'rombel_name' => $rombel['rombel_name'],
                'total'       => $tot,
                'keterangan'  => $ket
            ];
        }

        // =========================================================
        // 3J. LOGIKA REKAP PRESTASI
        // =========================================================
        $rekapPrestasi = [];
        $total_sekolah_prestasi = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];

            // Menggunakan tabel 'catatan_prestasi', kolom 'nama_prestasi', dan filter 'created_at'
            $prestasi = $db->table('catatan_prestasi p')
                            ->join('class_rombel_students crs', 'crs.student_id = p.student_id', 'left')
                            ->select('
                                COUNT(p.id) as total_prestasi,
                                GROUP_CONCAT(NULLIF(p.nama_prestasi, "") SEPARATOR ", ") as keterangan
                            ')
                            ->where('crs.rombel_id', $rombel_id)
                            ->whereIn('MONTH(p.created_at)', $bulan_array) // Filter menggunakan created_at karena tidak ada field tanggal
                            ->where('YEAR(p.created_at)', $tahun)
                            ->get()->getRowArray();

            $tot = (int)($prestasi['total_prestasi'] ?? 0);
            $ket = $prestasi['keterangan'] ?? '';

            $total_sekolah_prestasi += $tot;

            $rekapPrestasi[] = [
                'rombel_name' => $rombel['rombel_name'],
                'total'       => $tot,
                'keterangan'  => $ket
            ];
        }

        // =========================================================
        // 4. KIRIM SEMUA DATA KE VIEW
        // =========================================================
        $data = [
            'tipe_filter'     => $tipe_filter,
            'bulan'           => $bulan,
            'semester'        => $semester,
            'tahun'           => $tahun,
            
            // Variabel Absensi
            'hariEfektif'     => $hariEfektif,
            'hariEfektifList' => $hariEfektifList, 
            'rekapAbsensi'    => $rekapAbsensi,
            'tingkatCounts'   => $tingkatCounts,
            'rekapTingkat'    => $rekapTingkat,
            'avg_h'           => $totalTargetSekolah > 0 ? ($totalSemuaH / $totalTargetSekolah) * 100 : 0,
            'avg_s'           => $totalTargetSekolah > 0 ? ($totalSemuaS / $totalTargetSekolah) * 100 : 0,
            'avg_i'           => $totalTargetSekolah > 0 ? ($totalSemuaI / $totalTargetSekolah) * 100 : 0,
            'avg_a'           => $totalTargetSekolah > 0 ? ($totalSemuaA / $totalTargetSekolah) * 100 : 0,
            'avg_t'           => $totalTargetSekolah > 0 ? ($totalSemuaT / $totalTargetSekolah) * 100 : 0,

            // Variabel Kepatuhan
            'rekapKepatuhan'        => $rekapKepatuhan,
            'total_sekolah_seragam' => $total_sekolah_seragam,
            'total_sekolah_atribut' => $total_sekolah_atribut,
            'total_sekolah_bersih'  => $total_sekolah_bersih,
            'total_sekolah_lambat'  => $total_sekolah_lambat,
            'total_sekolah_aturan'  => $total_sekolah_aturan,
            'total_sekolah_masjid'  => $total_sekolah_masjid,
            'grand_total_kasus'     => $grand_total_kasus,
            
            // Variabel Yaumiyah
            'rekapYaumiyah' => $rekapYaumiyah,
            'rata_yaumiyah' => $rata_yaumiyah,

            // Variabel Quran
            'rekapQuran'         => $rekapQuran,
            'rata_quran_sekolah' => $rata_quran_sekolah,

            // Variabel Spiritual (Perbaikan)
            'rekapSpiritual'            => $rekapSpiritual,
            'total_sekolah_berdoa'      => $total_sekolah_berdoa,
            'total_sekolah_kalimat'     => $total_sekolah_kalimat,
            'total_sekolah_shalat'      => $total_sekolah_shalat,
            'total_sekolah_salam'       => $total_sekolah_salam,
            'total_sekolah_syukur'      => $total_sekolah_syukur,
            'total_sekolah_lingkungan'  => $total_sekolah_lingkungan,
            'total_sekolah_toleransi'   => $total_sekolah_toleransi,
            'grand_total_spiritual'     => $grand_total_spiritual,

            // Variabel Sosial (Revisi)
            'rekapSosial'                  => $rekapSosial,
            'total_sekolah_disiplin'       => $total_sekolah_disiplin,
            'total_sekolah_jujur'          => $total_sekolah_jujur,
            'total_sekolah_percaya_diri'   => $total_sekolah_percaya_diri,
            'total_sekolah_santun'         => $total_sekolah_santun,
            'total_sekolah_kerjasama'      => $total_sekolah_kerjasama,
            'total_sekolah_tanggung_jawab' => $total_sekolah_tj,
            'total_sekolah_adil'           => $total_sekolah_adil,
            'grand_total_sosial'           => $grand_total_sosial,

            // Variabel Peminatan & Pramuka
            'rekapPemPra' => $rekapPemPra,

            // Variabel Eskul
            'rekapEskul'  => $rekapEskul,

            // Variabel Nilai Sumatif
            'daftarMapel'      => $daftarMapel,
            'rekapSumatif'     => $rekapSumatif,
            'rataSumatifMapel' => $rataSumatifMapel,

            // Variabel Anekdot & Prestasi
            'rekapAnekdot'           => $rekapAnekdot,
            'total_sekolah_anekdot'  => $total_sekolah_anekdot,
            'rekapPrestasi'          => $rekapPrestasi,
            'total_sekolah_prestasi' => $total_sekolah_prestasi,
        ];

        return view('admin/rekap_sekolah/index', $data);
    }

    

    // =========================================================
    // 5. METHOD CRUD HARI EFEKTIF (Dipindah dari AbsensiController)[cite: 3]
    // =========================================================
    public function setHari()
    {
        $db = \Config\Database::connect();
        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');
        $hari  = $this->request->getPost('jumlah_hari');

        $builder = $db->table('hari_efektif');
        $cek = $builder->where(['bulan' => $bulan, 'tahun' => $tahun])->get()->getRow();
        
        if ($cek) {
            $builder->where('id', $cek->id)->update(['jumlah_hari' => $hari]);
        } else {
            $builder->insert(['bulan' => $bulan, 'tahun' => $tahun, 'jumlah_hari' => $hari]);
        }
        
        return redirect()->back()->with('success', 'Hari efektif berhasil disimpan.');
    }

    public function getHari()
    {
        $db = \Config\Database::connect();
        $bulan = $this->request->getGet('bulan');
        $tahun = $this->request->getGet('tahun');
        
        $cek = $db->table('hari_efektif')
                  ->where(['bulan' => $bulan, 'tahun' => $tahun])
                  ->get()->getRowArray();
                  
        return $this->response->setJSON([
            'jumlah_hari' => $cek ? $cek['jumlah_hari'] : ''
        ]);
    }
}