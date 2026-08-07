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

        // 3. Ambil Daftar Rombel
        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        $monitoringData = [];

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            
            // Hitung total siswa di kelas ini
            $jmlSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();

            if ($jmlSiswa == 0) {
                continue; // Lewati jika kelas kosong
            }

            // A. PERSENTASE ABSENSI (Hari Diinput vs Hari Efektif)
            $cekAbsen = $db->table('absensi')
                           ->select('COUNT(DISTINCT tanggal) as hari_diinput')
                           ->where('rombel_id', $rombel_id)
                           ->where('MONTH(tanggal)', $bulan)
                           ->where('YEAR(tanggal)', $tahun)
                           ->get()->getRowArray();
            $hariDiinput = (int)($cekAbsen['hari_diinput'] ?? 0);
            $persenAbsen = $hariEfektif > 0 ? min(100, ($hariDiinput / $hariEfektif) * 100) : 0;

            // B. PERSENTASE AL-QUR'AN (Siswa Dinilai vs Total Siswa)
            $cekQuran = $db->table('quran_penilaian qp')
                           ->join('class_rombel_students crs', 'crs.student_id = qp.student_id')
                           ->where('crs.rombel_id', $rombel_id)
                           ->where('qp.bulan', $bulan)
                           ->where('qp.tahun', $tahun)
                           ->select('COUNT(DISTINCT qp.student_id) as siswa_dinilai')
                           ->get()->getRowArray();
            $siswaDinilaiQ = (int)($cekQuran['siswa_dinilai'] ?? 0);
            $persenQuran = min(100, ($siswaDinilaiQ / $jmlSiswa) * 100);

            // C. PERSENTASE YAUMIYAH (Record Diinput vs Target Harian)
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

            // D. PERSENTASE SUMATIF (Siswa dengan minimal 1 nilai)
            $cekSumatif = $db->table('nilai_sumatif')
                             ->where('rombel_id', $rombel_id)
                             ->where('academic_year_id', $idTahunAjaran)
                             ->where('bulan', $bulan)
                             ->select('COUNT(DISTINCT student_id) as siswa_dinilai')
                             ->get()->getRowArray();
            $siswaDinilaiS = (int)($cekSumatif['siswa_dinilai'] ?? 0);
            $persenSumatif = min(100, ($siswaDinilaiS / $jmlSiswa) * 100);

            // E. AKTIVITAS JURNAL INSIDENTAL (Kepatuhan, Spiritual, Sosial, Anekdot)
            $kepatuhanCount = $db->table('kepatuhan')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            $spiritualCount = $db->table('aspek_spiritual')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            $sosialCount = $db->table('aspek_sosial')->where('rombel_id', $rombel_id)->where('MONTH(tanggal)', $bulan)->where('YEAR(tanggal)', $tahun)->countAllResults();
            
            $catatanAnekdotCount = $db->table('catatan_anekdot a')
                                      ->join('class_rombel_students crs', 'crs.student_id = a.student_id')
                                      ->where('crs.rombel_id', $rombel_id)->where('MONTH(a.tanggal)', $bulan)->where('YEAR(a.tanggal)', $tahun)->countAllResults();

            $totalInsiden = $kepatuhanCount + $spiritualCount + $sosialCount + $catatanAnekdotCount;

            $monitoringData[] = [
                'rombel_name'    => $rombel['rombel_name'],
                'jml_siswa'      => $jmlSiswa,
                'persen_absen'   => round($persenAbsen),
                'persen_quran'   => round($persenQuran),
                'persen_yaumiyah'=> round($persenYaumiyah),
                'persen_sumatif' => round($persenSumatif),
                'total_insiden'  => $totalInsiden
            ];
        }

        $data = [
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'hariEfektif'    => $hariEfektif,
            'monitoringData' => $monitoringData
        ];

        return view('admin/monitoring/index', $data);
    }
}