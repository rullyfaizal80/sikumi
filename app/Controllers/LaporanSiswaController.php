<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class LaporanSiswaController extends BaseController
{
    public function index()
    {
        $db      = Database::connect();
        $request = \Config\Services::request();

        // 1. Ambil Parameter dari URL
        $rombel_id  = $request->getGet('rombel_id');
        $student_id = $request->getGet('student_id');
        $bulan      = $request->getGet('bulan') ?? date('m');
        $tahun      = $request->getGet('tahun') ?? date('Y');

        // 2. Ambil Daftar Kelas untuk Dropdown
        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();

        // Variabel penampung hasil
        $dataSiswa   = null;
        $rekapAbsen  = null;
        $hariEfektif = 0;

        // 3. JIKA SISWA SUDAH DIPILIH, PROSES DATANYA
        if (!empty($student_id)) {
            
            // A. Ambil Info Profil Siswa (KODE BARU)
            // Menggabungkan tabel users[cite: 3] dan student_profiles
            $dataSiswa = $db->table('users u')
                            ->join('student_profiles sp', 'sp.user_id = u.id', 'left')
                            ->select('u.id, u.username as name, sp.nisn, sp.nis, sp.gender')
                            ->where('u.id', $student_id)
                            ->get()->getRowArray();

            // B. Ambil Target Hari Efektif di bulan tersebut
            $cekHari = $db->table('hari_efektif')
                          ->where('bulan', $bulan)
                          ->where('tahun', $tahun)
                          ->get()->getRowArray();
            $hariEfektif = $cekHari ? (int) $cekHari['jumlah_hari'] : 0;

            // C. Hitung Absensi KHUSUS Siswa Tersebut
            $rekapAbsen = $db->table('absensi a')
                        ->join('absensi_details ad', 'a.id = ad.absensi_id')
                        ->select('
                            SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                            SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                            SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                            SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                            SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t
                        ')
                        ->where('ad.student_id', $student_id)
                        ->where('MONTH(a.tanggal)', $bulan)
                        ->where('YEAR(a.tanggal)', $tahun)
                        ->get()->getRowArray();
        }

        // ... (Kode Absensi yang sebelumnya sudah ada di sini) ...

            // =========================================================
            // D. Hitung Kepatuhan (Kasus/Pelanggaran) Siswa
            // =========================================================
            $kepatuhanSiswa = $db->table('kepatuhan')
                            ->select('
                                SUM(seragam) as seragam,
                                SUM(atribut) as atribut,
                                SUM(bersih_diri) as bersih_diri,
                                SUM(terlambat) as terlambat,
                                SUM(aturan_kelas) as aturan_kelas,
                                SUM(masjid) as masjid,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR ", ") as keterangan
                            ')
                            ->where('student_id', $student_id) // Fokus ke siswa ini
                            ->where('MONTH(tanggal)', $bulan)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            // =========================================================
            // E. Hitung Jurnal Yaumiyah Siswa
            // =========================================================
            $targetHarian = $hariEfektif;
            $targetMingguan = ceil($hariEfektif / 5);
            $targetShaum = ($hariEfektif <= 15) ? 1 : 2; // Sesuai logika asli Anda

            $yaumiyahRaw = $db->table('yaumiyah')
                        ->select('
                            SUM(dzuhur) as t_dz, SUM(ashar) as t_as, SUM(bakdiah_dzuhur) as t_bd,
                            SUM(duha) as t_dh, SUM(tahajud) as t_th, SUM(tilawah) as t_tl,
                            SUM(infaq) as t_if, SUM(shaum) as t_sh, SUM(literasi) as t_lt
                        ')
                        ->where('student_id', $student_id)
                        ->where('MONTH(tanggal)', $bulan)
                        ->where('YEAR(tanggal)', $tahun)
                        ->where('DAYOFWEEK(tanggal) !=', 1)
                        ->where('DAYOFWEEK(tanggal) !=', 7) // Abaikan Sabtu & Minggu
                        ->get()->getRowArray();

            // Fungsi pembantu untuk hitung persentase yaumiyah (maks 100%)
            $calcP = function($total, $target) {
                if ($target == 0) return 0;
                $p = ($total / $target) * 100;
                return $p > 100 ? 100 : $p;
            };

            $yaumiyahSiswa = [
                'p_dzuhur'   => $calcP((int)($yaumiyahRaw['t_dz'] ?? 0), $targetHarian),
                'p_ashar'    => $calcP((int)($yaumiyahRaw['t_as'] ?? 0), $targetHarian),
                'p_bakdiah'  => $calcP((int)($yaumiyahRaw['t_bd'] ?? 0), $targetHarian),
                'p_duha'     => $calcP((int)($yaumiyahRaw['t_dh'] ?? 0), $targetHarian),
                'p_tahajud'  => $calcP((int)($yaumiyahRaw['t_th'] ?? 0), $targetMingguan),
                'p_tilawah'  => $calcP((int)($yaumiyahRaw['t_tl'] ?? 0), $targetHarian),
                'p_infaq'    => $calcP((int)($yaumiyahRaw['t_if'] ?? 0), $targetMingguan),
                'p_shaum'    => $calcP((int)($yaumiyahRaw['t_sh'] ?? 0), $targetShaum),
                'p_literasi' => $calcP((int)($yaumiyahRaw['t_lt'] ?? 0), $targetHarian),
            ];

        $data = [
            'daftarRombel' => $daftarRombel,
            'rombel_id'    => $rombel_id,
            'student_id'   => $student_id,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'dataSiswa'    => $dataSiswa,
            'hariEfektif'  => $hariEfektif,
            'rekapAbsen'   => $rekapAbsen,
            'kepatuhanSiswa' => $kepatuhanSiswa, // <-- TAMBAHKAN INI
            'yaumiyahSiswa'  => $yaumiyahSiswa,  // <-- TAMBAHKAN INI
        ];

        return view('admin/laporan_siswa/index', $data);
    }

    public function getSiswaByKelas()
    {
        $db = Database::connect();
        $rombel_id = $this->request->getGet('rombel_id');
        
        // Kita join ke tabel 'users' karena nama ada di kolom 'username'
        // Asumsi: 'student_id' di tabel 'class_rombel_students' merujuk ke 'users.id'
        $siswa = $db->table('class_rombel_students crs')
                    ->join('users u', 'u.id = crs.student_id')
                    ->select('u.id, u.username as name') // Alias name agar JS tidak perlu diubah
                    ->where('crs.rombel_id', $rombel_id)
                    ->orderBy('u.username', 'ASC')
                    ->get()->getResultArray();

        return $this->response->setJSON($siswa);
    }
}