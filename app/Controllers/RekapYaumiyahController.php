<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class RekapYaumiyahController extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $taAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        if (!$taAktif) {
            return redirect()->to(base_url('home'))->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $daftarRombel = $db->table('class_rombel')
                           ->where('academic_year_id', $taAktif['id'])
                           ->orderBy('rombel_name', 'ASC')
                           ->get()->getResultArray();

        $data = [
            'title'        => 'Pilih Kelas Jurnal Yaumiyah',
            'daftarRombel' => $daftarRombel
        ];

        return view('guru/yaumiyah/index', $data);
    }

    public function rekapKelas($rombel_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();
        if (!$rombel) return redirect()->to('guru/yaumiyah')->with('error', 'Rombel tidak ditemukan.');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // 1. Ambil Hari Efektif dari database
        $cekHari = $db->table('hari_efektif')
                      ->where('bulan', sprintf('%02d', $bulan))
                      ->where('tahun', $tahun)
                      ->get()->getRowArray();
        
        // Jika belum disetting di database, beri default 20 hari
        $hariEfektif = $cekHari ? $cekHari['jumlah_hari'] : 20;

        // 2. Tentukan Target Dinamis
        $targetHarian = $hariEfektif;
        $targetMingguan = ceil($hariEfektif / 5); // 1x per 5 hari
        
        // --- LOGIKA BARU TARGET SHAUM ---
        if ($hariEfektif <= 15) {
            $targetShaum = 1; // Jika hari efektif 0 - 15 hari, target 1x
        } else {
            $targetShaum = 2; // Jika hari efektif 16 hari ke atas, target 2x
        }
        // --------------------------------

        $daftarSiswa = $db->table('class_rombel_students crs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = crs.student_id')
                          ->where('crs.rombel_id', $rombel_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        $rekapData = [];

        foreach ($daftarSiswa as $siswa) {
            $sId = $siswa['student_id'];
            
            // Note: Sabtu dan Minggu tidak dihitung karena WHERE DAYOFWEEK != 1 (Minggu) dan != 7 (Sabtu)
            $hitung = $db->table('yaumiyah')
                         ->select('
                            SUM(dzuhur) as total_dzuhur, SUM(ashar) as total_ashar,
                            SUM(bakdiah_dzuhur) as total_bakdiah, SUM(duha) as total_duha,
                            SUM(tahajud) as total_tahajud, SUM(tilawah) as total_tilawah,
                            SUM(infaq) as total_infaq, SUM(shaum) as total_shaum,
                            SUM(literasi) as total_literasi
                         ')
                         ->where('student_id', $sId)
                         ->where('MONTH(tanggal)', $bulan)
                         ->where('YEAR(tanggal)', $tahun)
                         ->where('DAYOFWEEK(tanggal) !=', 1) // Kecualikan Minggu
                         ->where('DAYOFWEEK(tanggal) !=', 7) // Kecualikan Sabtu
                         ->get()->getRowArray();

            // Fungsi helper untuk menghitung persentase maksimal 100%
            $calcPercent = function($total, $target) {
                if ($target == 0) return 0;
                $percent = ($total / $target) * 100;
                return $percent > 100 ? 100 : $percent; // Mentok di 100%
            };

            $rekapData[] = [
                'username'       => $siswa['username'],
                'p_dzuhur'       => $calcPercent($hitung['total_dzuhur'] ?? 0, $targetHarian),
                'p_ashar'        => $calcPercent($hitung['total_ashar'] ?? 0, $targetHarian),
                'p_bakdiah'      => $calcPercent($hitung['total_bakdiah'] ?? 0, $targetHarian),
                'p_duha'         => $calcPercent($hitung['total_duha'] ?? 0, $targetHarian),
                'p_tilawah'      => $calcPercent($hitung['total_tilawah'] ?? 0, $targetHarian),
                'p_literasi'     => $calcPercent($hitung['total_literasi'] ?? 0, $targetHarian),
                'p_tahajud'      => $calcPercent($hitung['total_tahajud'] ?? 0, $targetMingguan),
                'p_infaq'        => $calcPercent($hitung['total_infaq'] ?? 0, $targetMingguan),
                'p_shaum'        => $calcPercent($hitung['total_shaum'] ?? 0, $targetShaum),
            ];
        }

        $data = [
            'title'       => 'Rekap Jurnal Yaumiyah - Kelas ' . $rombel['rombel_name'],
            'rombel'      => $rombel,
            'bulan'       => $bulan,
            'tahun'       => $tahun,
            'hariEfektif' => $hariEfektif,
            'rekapData'   => $rekapData
        ];

        return view('guru/yaumiyah/rekap_kelas', $data);
    }

    // Fungsi Baru: Monitoring Bulanan (Senin - Jumat)
    public function monitoringBulanan($rombel_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();
        if (!$rombel) return redirect()->to('guru/yaumiyah')->with('error', 'Rombel tidak ditemukan.');

        // Filter Bulan & Tahun (Default bulan ini)
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $jumlahHari = date('t', strtotime("$tahun-$bulan-01"));

        // 1. Dapatkan semua Hari Efektif (Senin - Jumat) di bulan tersebut
        $hariAktif = [];
        for ($i = 1; $i <= $jumlahHari; $i++) {
            $tglFull = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
            $dayOfWeek = date('N', strtotime($tglFull)); // 1=Senin, 7=Minggu
            if ($dayOfWeek <= 5) {
                $hariAktif[] = $i;
            }
        }

        // 2. Tarik Daftar Siswa
        $daftarSiswa = $db->table('class_rombel_students crs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = crs.student_id')
                          ->where('crs.rombel_id', $rombel_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        // 3. Tarik Data Yaumiyah 1 Bulan Penuh Khusus Rombel Ini
        $siswaIds = array_column($daftarSiswa, 'student_id');
        $yaumiyahBulanIni = [];
        
        if (!empty($siswaIds)) {
            $yaumiyahBulanIni = $db->table('yaumiyah')
                                   ->whereIn('student_id', $siswaIds)
                                   ->where('MONTH(tanggal)', $bulan)
                                   ->where('YEAR(tanggal)', $tahun)
                                   ->get()->getResultArray();
        }

        // 4. Mapping Data Yaumiyah ke dalam Array berdasar Tanggal & Aspek
        $yaumiyahData = [];
        foreach ($yaumiyahBulanIni as $row) {
            $tgl = (int)date('j', strtotime($row['tanggal']));
            $yaumiyahData[$row['student_id']][$tgl] = [
                1 => $row['dzuhur'],
                2 => $row['ashar'],
                3 => $row['bakdiah_dzuhur'],
                4 => $row['duha'],
                5 => $row['tahajud'],
                6 => $row['tilawah'],
                7 => $row['infaq'],
                8 => $row['shaum'],
                9 => $row['literasi']
            ];
        }

        $data = [
            'title'        => 'Monitoring Yaumiyah - Kelas ' . $rombel['rombel_name'],
            'rombel'       => $rombel,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'hariAktif'    => $hariAktif,
            'daftarSiswa'  => $daftarSiswa,
            'yaumiyahData' => $yaumiyahData
        ];

        return view('guru/yaumiyah/monitoring', $data);
    }
}