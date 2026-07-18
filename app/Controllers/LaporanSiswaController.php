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

            // ... (Kode Yaumiyah sebelumnya di sini) ...

            // =========================================================
            // F. Hitung Aspek Spiritual Siswa
            // =========================================================
            $spiritualSiswa = $db->table('aspek_spiritual')
                            ->select('
                                SUM(berdoa) as berdoa,
                                SUM(kalimat_thoyibah) as kalimat_thoyibah,
                                SUM(shalat) as shalat,
                                SUM(salam) as salam,
                                SUM(syukur) as syukur,
                                SUM(lingkungan) as lingkungan,
                                SUM(toleransi) as toleransi,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR ", ") as keterangan
                            ')
                            ->where('student_id', $student_id)
                            ->where('MONTH(tanggal)', $bulan)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            // =========================================================
            // G. Hitung Aspek Sosial Siswa
            // =========================================================
            $sosialSiswa = $db->table('aspek_sosial')
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
                            ->where('student_id', $student_id)
                            ->where('MONTH(tanggal)', $bulan)
                            ->where('YEAR(tanggal)', $tahun)
                            ->get()->getRowArray();

            // =========================================================
            // H. Ambil Nilai Al-Qur'an Siswa
            // =========================================================
            $quranSiswa = $db->table('quran_penilaian')
                            ->select('tahsin_nilai, tahfidz_nilai, kitabah_nilai')
                            ->where('student_id', $student_id)
                            ->where('bulan', $bulan)
                            ->where('tahun', $tahun)
                            ->get()->getRowArray();

        // ... (Kode Al-Qur'an sebelumnya di sini) ...

            // =========================================================
            // I. Ambil Nilai Pramuka & Peminatan Siswa
            // =========================================================
            $pramukaSiswa = $db->table('pramuka_grades')
                               ->select('nilai')
                               ->where('student_id', $student_id)
                               ->where('bulan', $bulan)
                               ->get()->getRowArray();

            $peminatanSiswa = $db->table('peminatan_grades')
                                 ->select('nilai')
                                 ->where('student_id', $student_id)
                                 ->where('bulan', $bulan)
                                 ->get()->getRowArray();

            // =========================================================
            // J. Ambil Nilai Ekstrakurikuler (Eskul) Siswa
            // =========================================================
            $eskulSiswa = [];
            if ($db->tableExists('eskul_grades') && $db->tableExists('eskul_groups')) {
                $eskulSiswa = $db->table('eskul_grades eg')
                                 ->join('eskul_groups grp', 'grp.id = eg.group_id', 'left')
                                 ->select('grp.nama_kelompok, eg.nilai')
                                 ->where('eg.student_id', $student_id)
                                 ->where('eg.bulan', $bulan)
                                 ->get()->getResultArray();
            }

            // =========================================================
            // K. Ambil Daftar Mapel & Nilai Sumatif (Cara Rekap Sekolah)
            // =========================================================
            
            // 1. Dapatkan ID Tahun Ajaran (Persis seperti rekap)
            $idTahunAjaran = 0;
            if ($db->tableExists('academic_years')) {
                $cekTahun = $db->table('academic_years')->where('academic_year', $tahun)->orWhere('id', $tahun)->get()->getRowArray();
                if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
                if ($idTahunAjaran == 0) {
                    $cekTahun = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
                    if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
                }
            }

            $daftarMapel = [];
            $mapelDitemukan = []; 

            // 2. Setup Tabel Master
            $tabelMapel = $db->tableExists('master_subjects') ? 'master_subjects' : ($db->tableExists('subjects') ? 'subjects' : 'mata_pelajaran');
            $mapelFields = $db->getFieldNames($tabelMapel);
            $kolomNamaMapel = in_array('subject_name', $mapelFields) ? 'subject_name' : (in_array('nama_mapel', $mapelFields) ? 'nama_mapel' : 'name');
            $hasCombinedTable = $db->tableExists('schedule_combined_subjects');

            // 3. Ambil Jadwal Aktif Sekolah
            $jadwalAktif = null;
            if ($db->tableExists('schedule_versions')) {
                $jadwalAktif = $db->table('schedule_versions')->where('academic_year_id', $idTahunAjaran)->where('is_active', 1)->get()->getRowArray();
                if (!$jadwalAktif) {
                    $jadwalAktif = $db->table('schedule_versions')->where('is_active', 1)->get()->getRowArray();
                }
            }

            // 4. Tarik Semua Mapel dari Jadwal Tingkat Sekolah
            if ($jadwalAktif) {
                $jadwalAktifId = $jadwalAktif['id'];
                $csFields = $db->getFieldNames('class_schedules');
                $kolomSubjectId = in_array('subject_id', $csFields) ? 'subject_id' : 'mapel_id';
                $kolomCombinedId = in_array('combined_subject_id', $csFields) ? 'combined_subject_id' : null;

                // A. AMBIL MAPEL GABUNGAN
                if ($kolomCombinedId && $hasCombinedTable) {
                    $mapelGabungan = $db->table('class_schedules cs')
                                 ->select("cs.{$kolomCombinedId} as combined_id, c.combined_name")  
                                 ->join("schedule_combined_subjects c", "c.id = cs.{$kolomCombinedId}", 'left') 
                                 ->where('cs.version_id', $jadwalAktifId)
                                 ->where("cs.{$kolomCombinedId} IS NOT NULL")
                                 ->where("cs.{$kolomCombinedId} !=", 0)
                                 ->groupBy("cs.{$kolomCombinedId}")
                                 ->get()->getResultArray();
                                 
                    foreach ($mapelGabungan as $mg) {
                        $namaMapel = trim($mg['combined_name'] ?? '');
                        if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') continue;

                        $cId = 'C_' . $mg['combined_id'];
                        if (!in_array($cId, $mapelDitemukan)) {
                            $daftarMapel[] = ['id' => $cId, 'nama_mapel' => $namaMapel];
                            $mapelDitemukan[] = $cId;
                        }
                    }
                }

                // B. AMBIL MAPEL REGULER
                $queryReguler = $db->table('class_schedules cs')
                              ->select("cs.{$kolomSubjectId} as id, s.{$kolomNamaMapel} as subject_name")
                              ->join("{$tabelMapel} s", "s.id = cs.{$kolomSubjectId}", 'left')
                              ->where('cs.version_id', $jadwalAktifId)
                              ->where("cs.{$kolomSubjectId} IS NOT NULL")
                              ->where("cs.{$kolomSubjectId} !=", 0);
                              
                if ($kolomCombinedId) {
                    $queryReguler->groupStart()
                                 ->where("cs.{$kolomCombinedId} IS NULL")
                                 ->orWhere("cs.{$kolomCombinedId}", 0)
                                 ->groupEnd();
                }
                
                $mapelReguler = $queryReguler->groupBy("cs.{$kolomSubjectId}")->get()->getResultArray();
                              
                foreach ($mapelReguler as $m) {
                    $namaMapel = trim($m['subject_name'] ?? '');
                    if (empty($namaMapel) || stripos($namaMapel, 'Bimbingan Konseling') !== false || strtoupper($namaMapel) === 'BK') continue;

                    $mId = 'S_' . $m['id'];
                    if (!in_array($mId, $mapelDitemukan)) {
                        $daftarMapel[] = ['id' => $mId, 'nama_mapel' => $namaMapel];
                        $mapelDitemukan[] = $mId;
                    }
                }
            }

            // Urutkan Mapel A-Z
            usort($daftarMapel, function($a, $b) {
                return strcmp($a['nama_mapel'], $b['nama_mapel']);
            });

            // 5. Tarik Nilai Sumatif per Mapel untuk Siswa ini
            $sumatifSiswa = [];
            foreach ($daftarMapel as $mapel) {
                $nilaiQuery = $db->table('nilai_sumatif')
                                 ->select('nilai_angka')
                                 ->where('student_id', $student_id)
                                 ->where('mapel_id', $mapel['id']); // Mencocokkan 'S_1' atau 'C_2'
                                 
                if (!empty($bulan)) {
                    $nilaiQuery->where('bulan', $bulan);
                }
                
                $nilaiData = $nilaiQuery->get()->getRowArray();
                
                $sumatifSiswa[] = [
                    'nama_mapel'  => $mapel['nama_mapel'],
                    'nilai_angka' => $nilaiData ? $nilaiData['nilai_angka'] : null
                ];
            }

            // ... (Kode Nilai Sumatif sebelumnya di sini) ...

            // =========================================================
            // L. Ambil Catatan Anekdot & Prestasi Siswa[cite: 5]
            // =========================================================
            // Ambil Anekdot berdasarkan kolom 'tanggal'[cite: 5]
            $anekdotSiswa = $db->table('catatan_anekdot')
                               ->select('tanggal, kejadian')
                               ->where('student_id', $student_id)
                               ->where('MONTH(tanggal)', $bulan)
                               ->where('YEAR(tanggal)', $tahun)
                               ->orderBy('tanggal', 'ASC')
                               ->get()->getResultArray();

            // Ambil Prestasi berdasarkan kolom 'created_at' karena tidak ada field tanggal khusus[cite: 5]
            $prestasiSiswa = $db->table('catatan_prestasi')
                                ->select('nama_prestasi, keterangan, created_at')
                                ->where('student_id', $student_id)
                                ->where('MONTH(created_at)', $bulan)
                                ->where('YEAR(created_at)', $tahun)
                                ->orderBy('created_at', 'ASC')
                                ->get()->getResultArray();

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
            'spiritualSiswa' => $spiritualSiswa, // <-- TAMBAHKAN INI
            'sosialSiswa'    => $sosialSiswa,    // <-- TAMBAHKAN INI
            'quranSiswa'     => $quranSiswa,     // <-- TAMBAHKAN INI
            'pramukaSiswa'   => $pramukaSiswa,   // <-- TAMBAHKAN INI
            'peminatanSiswa' => $peminatanSiswa, // <-- TAMBAHKAN INI
            'eskulSiswa'     => $eskulSiswa,     // <-- TAMBAHKAN INI
            'sumatifSiswa'   => $sumatifSiswa,   // <-- TAMBAHKAN INI
            'anekdotSiswa'  => $anekdotSiswa,   // <-- TAMBAHKAN INI
            'prestasiSiswa' => $prestasiSiswa,  // <-- TAMBAHKAN INI
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