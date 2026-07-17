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
        // 3B. LOGIKA REKAP YAUMIYAH (Rata-rata Persentase Kelas)
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

        $tot_dz = 0; $tot_as = 0; $tot_bd = 0; $tot_dh = 0; $tot_th = 0; 
        $tot_tl = 0; $tot_if = 0; $tot_sh = 0; $tot_lt = 0;
        
        $tgt_harian_sek = 0; $tgt_mingguan_sek = 0; $tgt_shaum_sek = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            $jumlahSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();
            
            // Target per kelas
            $tgtHarianKls = $jumlahSiswa * $targetHarian;
            $tgtMingguanKls = $jumlahSiswa * $targetMingguan;
            $tgtShaumKls = $jumlahSiswa * $targetShaum;

            $tgt_harian_sek += $tgtHarianKls;
            $tgt_mingguan_sek += $tgtMingguanKls;
            $tgt_shaum_sek += $tgtShaumKls;

            // Ambil total amalan kelas ini (kecuali Sabtu & Minggu)
            $yData = $db->table('yaumiyah y')
                        ->join('class_rombel_students crs', 'crs.student_id = y.student_id')
                        ->select('
                            SUM(y.dzuhur) as t_dz, SUM(y.ashar) as t_as, SUM(y.bakdiah_dzuhur) as t_bd,
                            SUM(y.duha) as t_dh, SUM(y.tahajud) as t_th, SUM(y.tilawah) as t_tl,
                            SUM(y.infaq) as t_if, SUM(y.shaum) as t_sh, SUM(y.literasi) as t_lt
                        ')
                        ->where('crs.rombel_id', $rombel_id)
                        ->whereIn('MONTH(y.tanggal)', $bulan_array)
                        ->where('YEAR(y.tanggal)', $tahun)
                        ->where('DAYOFWEEK(y.tanggal) !=', 1)
                        ->where('DAYOFWEEK(y.tanggal) !=', 7)
                        ->get()->getRowArray();

            $dz = (int)($yData['t_dz'] ?? 0); $as = (int)($yData['t_as'] ?? 0); $bd = (int)($yData['t_bd'] ?? 0);
            $dh = (int)($yData['t_dh'] ?? 0); $th = (int)($yData['t_th'] ?? 0); $tl = (int)($yData['t_tl'] ?? 0);
            $if = (int)($yData['t_if'] ?? 0); $sh = (int)($yData['t_sh'] ?? 0); $lt = (int)($yData['t_lt'] ?? 0);

            // Akumulasi total sekolah
            $tot_dz += $dz; $tot_as += $as; $tot_bd += $bd; $tot_dh += $dh; $tot_th += $th; 
            $tot_tl += $tl; $tot_if += $if; $tot_sh += $sh; $tot_lt += $lt;

            $calcP = function($total, $target) {
                if ($target == 0) return 0;
                $p = ($total / $target) * 100;
                return $p > 100 ? 100 : $p;
            };

            $rekapYaumiyah[] = [
                'rombel_name' => $rombel['rombel_name'],
                'p_dzuhur'    => $calcP($dz, $tgtHarianKls),
                'p_ashar'     => $calcP($as, $tgtHarianKls),
                'p_bakdiah'   => $calcP($bd, $tgtHarianKls),
                'p_duha'      => $calcP($dh, $tgtHarianKls),
                'p_tahajud'   => $calcP($th, $tgtMingguanKls),
                'p_tilawah'   => $calcP($tl, $tgtHarianKls),
                'p_infaq'     => $calcP($if, $tgtMingguanKls),
                'p_shaum'     => $calcP($sh, $tgtShaumKls),
                'p_literasi'  => $calcP($lt, $tgtHarianKls),
            ];
        }

        $rata_yaumiyah = [
            'dzuhur'   => $tgt_harian_sek > 0 ? min(100, ($tot_dz / $tgt_harian_sek) * 100) : 0,
            'ashar'    => $tgt_harian_sek > 0 ? min(100, ($tot_as / $tgt_harian_sek) * 100) : 0,
            'bakdiah'  => $tgt_harian_sek > 0 ? min(100, ($tot_bd / $tgt_harian_sek) * 100) : 0,
            'duha'     => $tgt_harian_sek > 0 ? min(100, ($tot_dh / $tgt_harian_sek) * 100) : 0,
            'tahajud'  => $tgt_mingguan_sek > 0 ? min(100, ($tot_th / $tgt_mingguan_sek) * 100) : 0,
            'tilawah'  => $tgt_harian_sek > 0 ? min(100, ($tot_tl / $tgt_harian_sek) * 100) : 0,
            'infaq'    => $tgt_mingguan_sek > 0 ? min(100, ($tot_if / $tgt_mingguan_sek) * 100) : 0,
            'shaum'    => $tgt_shaum_sek > 0 ? min(100, ($tot_sh / $tgt_shaum_sek) * 100) : 0,
            'literasi' => $tgt_harian_sek > 0 ? min(100, ($tot_lt / $tgt_harian_sek) * 100) : 0,
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