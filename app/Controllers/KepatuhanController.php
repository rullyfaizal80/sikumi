<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class KepatuhanController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // Ambil data rombel beserta jumlah siswanya
        $rombels = $db->table('class_rombel cr')
                      ->select('cr.id, cr.rombel_name, COUNT(crs.student_id) as jumlah_siswa')
                      ->join('class_rombel_students crs', 'crs.rombel_id = cr.id', 'left')
                      ->groupBy('cr.id')
                      ->orderBy('cr.rombel_name', 'ASC')
                      ->get()->getResultArray();

        $data = [
            'title'   => 'Modul Kepatuhan',
            'rombels' => $rombels
        ];

        return view('admin/kepatuhan/index', $data);
    }

    // Tambahkan di bawah fungsi index() yang sudah kita buat sebelumnya

    public function input($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Ambil tanggal dari filter (default hari ini)
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');

        // Detail Rombel
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // Ambil daftar siswa beserta data kepatuhan di tanggal tersebut (jika sudah diinput)
        $siswaData = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username, k.seragam, k.atribut, k.bersih_diri, k.terlambat, k.aturan_kelas, k.masjid, k.keterangan')
            ->join('users u', 'u.id = crs.student_id')
            ->join('kepatuhan k', "k.student_id = u.id AND k.tanggal = '{$tanggal}'", 'left')
            ->where('crs.rombel_id', $rombel_id)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'     => 'Input Kepatuhan: ' . $rombel['rombel_name'],
            'rombel_id' => $rombel_id,
            'rombel'    => $rombel,
            'tanggal'   => $tanggal,
            'siswaData' => $siswaData
        ];

        return view('admin/kepatuhan/input', $data);
    }

    public function save()
    {
        $db = \Config\Database::connect();
        
        $rombel_id = $this->request->getPost('rombel_id');
        $tanggal   = $this->request->getPost('tanggal');
        $students  = $this->request->getPost('students'); // Data array dari form
        
        $builder = $db->table('kepatuhan');

        foreach ($students as $student_id => $data) {
            // Cek apakah ada pelanggaran (jika tidak ada ceklis sama sekali, anggap aman/0)
            $seragam      = isset($data['seragam']) ? 1 : 0;
            $atribut      = isset($data['atribut']) ? 1 : 0;
            $bersih_diri  = isset($data['bersih_diri']) ? 1 : 0;
            $terlambat    = isset($data['terlambat']) ? 1 : 0;
            $aturan_kelas = isset($data['aturan_kelas']) ? 1 : 0;
            $masjid       = isset($data['masjid']) ? 1 : 0;
            $keterangan   = $data['keterangan'] ?? '';

            // Jika hari itu siswa punya minimal 1 pelanggaran, simpan/update
            if ($seragam || $atribut || $bersih_diri || $terlambat || $aturan_kelas || $masjid) {
                
                $cekData = $builder->where(['student_id' => $student_id, 'tanggal' => $tanggal])->get()->getRow();
                
                $payload = [
                    'student_id'   => $student_id,
                    'rombel_id'    => $rombel_id,
                    'tanggal'      => $tanggal,
                    'seragam'      => $seragam,
                    'atribut'      => $atribut,
                    'bersih_diri'  => $bersih_diri,
                    'terlambat'    => $terlambat,
                    'aturan_kelas' => $aturan_kelas,
                    'masjid'       => $masjid,
                    'keterangan'   => $keterangan
                ];

                if ($cekData) {
                    $builder->where('id', $cekData->id)->update($payload);
                } else {
                    $builder->insert($payload);
                }
            } else {
                // Jika tidak ada ceklis sama sekali (dibersihkan), hapus record jika sebelumnya ada
                $builder->where(['student_id' => $student_id, 'tanggal' => $tanggal])->delete();
            }
        }

        return redirect()->back()->with('success', 'Data kepatuhan tanggal ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil disimpan!');
    }

    public function rekapKelas($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Filter Tahun dan Semester
        $tahun    = $request->getGet('tahun') ?? date('Y');
        // Default ke ganjil jika bulan saat ini Juli-Desember, genap jika Jan-Juni
        $semester = $request->getGet('semester') ?? (date('n') >= 7 ? 'ganjil' : 'genap');

        if ($semester === 'ganjil') {
            $array_bulan = [7, 8, 9, 10, 11, 12];
            $nama_bulan  = [7=>'JULI', 8=>'AGUSTUS', 9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER'];
        } else {
            $array_bulan = [1, 2, 3, 4, 5, 6];
            $nama_bulan  = [1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL', 5=>'MEI', 6=>'JUNI'];
        }

        // Detail Rombel
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // 1. Ambil daftar siswa di kelas tersebut
        $students = $db->table('class_rombel_students crs')
                       ->select('u.id as student_id, u.username')
                       ->join('users u', 'u.id = crs.student_id')
                       ->where('crs.rombel_id', $rombel_id)
                       ->orderBy('u.username', 'ASC')
                       ->get()->getResultArray();

       // 2. Ambil total pelanggaran per siswa & per bulan + Gabungkan Keterangan
        $records = $db->table('kepatuhan')
                      ->select('student_id, MONTH(tanggal) as bulan, 
                                SUM(seragam) as sum_seragam, 
                                SUM(atribut) as sum_atribut, 
                                SUM(bersih_diri) as sum_bersih_diri, 
                                SUM(terlambat) as sum_terlambat, 
                                SUM(aturan_kelas) as sum_aturan_kelas, 
                                SUM(masjid) as sum_masjid,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR " | ") as gabungan_keterangan')
                      ->where('rombel_id', $rombel_id)
                      ->where('YEAR(tanggal)', $tahun)
                      ->whereIn('MONTH(tanggal)', $array_bulan)
                      ->groupBy('student_id, MONTH(tanggal)')
                      ->get()->getResultArray();

        // 3. Susun data menjadi Pivot Multi-Dimensi: $rekapData[ID_SISWA][BULAN] = Data
        $rekapData = [];
        foreach ($records as $r) {
            $rekapData[$r['student_id']][$r['bulan']] = [
                'seragam'      => $r['sum_seragam'],
                'atribut'      => $r['sum_atribut'],
                'bersih_diri'  => $r['sum_bersih_diri'],
                'terlambat'    => $r['sum_terlambat'],
                'aturan_kelas' => $r['sum_aturan_kelas'],
                'masjid'       => $r['sum_masjid'],
                'keterangan'   => $r['gabungan_keterangan'] // Masukkan keterangan gabungan
            ];
        }

        $data = [
            'title'       => 'Rekap Kepatuhan: ' . $rombel['rombel_name'],
            'rombel_id'   => $rombel_id,
            'rombel'      => $rombel,
            'tahun'       => $tahun,
            'semester'    => $semester,
            'array_bulan' => $array_bulan,
            'nama_bulan'  => $nama_bulan,
            'students'    => $students,
            'rekapData'   => $rekapData
        ];

        return view('admin/kepatuhan/rekap_kelas', $data);
    }
}