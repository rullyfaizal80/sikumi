<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class MonitoringController extends BaseController
{
    public function index()
    {
        $db      = Database::connect();
        $request = \Config\Services::request();

        // 1. Ambil Filter
        $bulan = $request->getGet('bulan') ?? date('m');
        $tahun = $request->getGet('tahun') ?? date('Y');

        // 2. Ambil Hari Efektif & Tahun Ajaran Aktif
        $cekHari = $db->table('hari_efektif')->where(['bulan' => $bulan, 'tahun' => $tahun])->get()->getRowArray();
        $hariEfektif = $cekHari ? (int) $cekHari['jumlah_hari'] : 0;

        $idTahunAjaran = 0;
        if ($db->tableExists('academic_years')) {
            $cekTahun = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            if ($cekTahun) $idTahunAjaran = $cekTahun['id'];
        }

        // ====================================================================
        // BAGIAN A: MONITORING PER KELAS / ROMBEL
        // ====================================================================
        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        $monitoringKelas = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            $jmlSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();

            if ($jmlSiswa == 0) continue; 

            // Persentase Absensi
            $cekAbsen = $db->table('absensi')
                           ->select('COUNT(DISTINCT tanggal) as hari_diinput')
                           ->where('rombel_id', $rombel_id)
                           ->where('MONTH(tanggal)', $bulan)
                           ->where('YEAR(tanggal)', $tahun)
                           ->get()->getRowArray();
            $hariDiinput = (int)($cekAbsen['hari_diinput'] ?? 0);
            $persenAbsen = $hariEfektif > 0 ? min(100, ($hariDiinput / $hariEfektif) * 100) : 0;

            // Persentase Yaumiyah
            $targetYaumiyah = $jmlSiswa * $hariEfektif;
            $cekYaumiyah = $db->table('yaumiyah y')
                              ->join('class_rombel_students crs', 'crs.student_id = y.student_id')
                              ->where('crs.rombel_id', $rombel_id)
                              ->where('MONTH(y.tanggal)', $bulan)
                              ->where('YEAR(y.tanggal)', $tahun)
                              ->select('COUNT(y.id) as record_diinput')
                              ->get()->getRowArray();
            $recordYaumiyah = (int)($cekYaumiyah['record_diinput'] ?? 0);
            $persenYaumiyah = $targetYaumiyah > 0 ? min(100, ($recordYaumiyah / $targetYaumiyah) * 100) : 0;

            // Persentase Sumatif
            $cekSumatif = $db->table('nilai_sumatif')
                             ->where('rombel_id', $rombel_id)
                             ->where('academic_year_id', $idTahunAjaran)
                             ->where('bulan', $bulan)
                             ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                             ->get()->getRowArray();
            $siswaDinilaiS = (int)($cekSumatif['siswa_dinilai'] ?? 0);
            $persenSumatif = min(100, ($siswaDinilaiS / $jmlSiswa) * 100);

            // Aktivitas Jurnal Insidental
            $kepatuhanCount = $db->table('kepatuhan')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            $spiritualCount = $db->table('aspek_spiritual')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            $sosialCount = $db->table('aspek_sosial')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            $catatanAnekdotCount = $db->table('catatan_anekdot a')
                                      ->join('class_rombel_students crs', 'crs.student_id = a.student_id')
                                      ->where('crs.rombel_id', $rombel_id)->where('MONTH(a.tanggal)', $bulan)->where('YEAR(a.tanggal)', $tahun)->countAllResults();

            $totalInsiden = $kepatuhanCount + $spiritualCount + $sosialCount + $catatanAnekdotCount;

            $monitoringKelas[] = [
                'rombel_name'    => $rombel['rombel_name'],
                'jml_siswa'      => $jmlSiswa,
                'persen_absen'   => round($persenAbsen),
                'persen_yaumiyah'=> round($persenYaumiyah),
                'persen_sumatif' => round($persenSumatif),
                'total_insiden'  => $totalInsiden
            ];
        }

        // ====================================================================
        // BAGIAN B: MONITORING PER KELOMPOK AL-QUR'AN (KHUSUS REGULER)
        // ====================================================================
        $quranGroups = $db->table('quran_groups qg')
                          ->select('qg.id, qg.nama_kelompok, u.username as pembimbing')
                          ->join('users u', 'u.id = qg.pembimbing_id', 'left')
                          ->where('qg.jenis_kelompok', 'Reguler')
                          ->orderBy('qg.nama_kelompok', 'ASC')
                          ->get()->getResultArray();

        $monitoringQuran = [];

        foreach ($quranGroups as $qg) {
            $groupId = $qg['id'];
            $jmlSiswaQuran = $db->table('quran_group_students')->where('group_id', $groupId)->countAllResults();
            
            if ($jmlSiswaQuran == 0) continue;

            $penilaianQuran = $db->table('quran_penilaian qp')
                                 ->join('quran_group_students qgs', 'qgs.student_id = qp.student_id')
                                 ->where('qgs.group_id', $groupId)
                                 ->where('qp.bulan', sprintf('%02d', $bulan))
                                 ->where('qp.tahun', $tahun)
                                 ->select('qp.student_id, qp.tahsin_nilai, qp.tahfidz_nilai, qp.kitabah_nilai')
                                 ->get()->getResultArray();

            $siswaTahsin = []; $siswaTahfidz = []; $siswaKitabah = [];
            
            foreach ($penilaianQuran as $pq) {
                $sId = $pq['student_id'];
                if (!empty(trim($pq['tahsin_nilai']))) $siswaTahsin[$sId] = true;
                if (!empty(trim($pq['tahfidz_nilai']))) $siswaTahfidz[$sId] = true;
                if (!empty(trim($pq['kitabah_nilai']))) $siswaKitabah[$sId] = true;
            }

            $monitoringQuran[] = [
                'nama_kelompok'  => $qg['nama_kelompok'],
                'pembimbing'     => $qg['pembimbing'],
                'jml_siswa'      => $jmlSiswaQuran,
                'persen_tahsin'  => min(100, round((count($siswaTahsin) / $jmlSiswaQuran) * 100)),
                'persen_tahfidz' => min(100, round((count($siswaTahfidz) / $jmlSiswaQuran) * 100)),
                'persen_kitabah' => min(100, round((count($siswaKitabah) / $jmlSiswaQuran) * 100)),
            ];
        }

        $data = [
            'bulan'           => $bulan,
            'tahun'           => $tahun,
            'hariEfektif'     => $hariEfektif,
            'monitoringKelas' => $monitoringKelas,
            'monitoringQuran' => $monitoringQuran
        ];

        return view('admin/monitoring/index', $data);
    }
}