<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class SpiritualController extends BaseController
{
    // =========================================================================
    // 1. HALAMAN DAFTAR KELAS (Meniru KepatuhanController::index)
    // =========================================================================
    public function index()
    {
        $db = \Config\Database::connect();
        
        // Ambil data rombel beserta jumlah siswanya (tanpa filter teacher_id)
        $rombels = $db->table('class_rombel cr')
                      ->select('cr.id, cr.rombel_name, COUNT(crs.student_id) as jumlah_siswa')
                      ->join('class_rombel_students crs', 'crs.rombel_id = cr.id', 'left')
                      ->groupBy('cr.id')
                      ->orderBy('cr.rombel_name', 'ASC')
                      ->get()->getResultArray();

        $data = [
            'title'   => 'Modul Aspek Spiritual',
            'rombels' => $rombels
        ];

        // Sesuaikan path view dengan struktur folder Anda (misal: admin/spiritual/index)
        return view('admin/spiritual/index', $data);
    }

    // =========================================================================
    // 2. HALAMAN INPUT SPIRITUAL (Meniru KepatuhanController::input)
    // =========================================================================
    public function input($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Ambil tanggal dari filter (default hari ini)
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');

        // Detail Rombel
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // Ambil daftar siswa beserta data spiritual di tanggal tersebut (jika sudah diinput)
        $siswaData = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username, as.berdoa, as.kalimat_thoyibah, as.shalat, as.salam, as.syukur, as.lingkungan, as.toleransi, as.keterangan')
            ->join('users u', 'u.id = crs.student_id')
            ->join('aspek_spiritual as', "as.student_id = u.id AND as.tanggal = '{$tanggal}'", 'left')
            ->where('crs.rombel_id', $rombel_id)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'     => 'Input Aspek Spiritual: ' . $rombel['rombel_name'],
            'rombel_id' => $rombel_id,
            'rombel'    => $rombel,
            'tanggal'   => $tanggal,
            'siswaData' => $siswaData
        ];

        return view('admin/spiritual/input', $data);
    }

    // =========================================================================
    // 3. PROSES SIMPAN DATA (Meniru KepatuhanController::save)
    // =========================================================================
    public function save()
    {
        $db = \Config\Database::connect();
        
        $rombel_id = $this->request->getPost('rombel_id');
        $tanggal   = $this->request->getPost('tanggal');
        $students  = $this->request->getPost('students'); // Menggunakan format array input dari form
        
        $builder = $db->table('aspek_spiritual');

        foreach ($students as $student_id => $data) {
            // Cek apakah ada pelanggaran (jika tidak ada ceklis sama sekali, anggap aman/0)
            $berdoa           = isset($data['berdoa']) ? 1 : 0;
            $kalimat_thoyibah = isset($data['kalimat_thoyibah']) ? 1 : 0;
            $shalat           = isset($data['shalat']) ? 1 : 0;
            $salam            = isset($data['salam']) ? 1 : 0;
            $syukur           = isset($data['syukur']) ? 1 : 0;
            $lingkungan       = isset($data['lingkungan']) ? 1 : 0;
            $toleransi        = isset($data['toleransi']) ? 1 : 0;
            $keterangan       = $data['keterangan'] ?? '';

            // Jika hari itu siswa punya minimal 1 pelanggaran/catatan, simpan/update
            if ($berdoa || $kalimat_thoyibah || $shalat || $salam || $syukur || $lingkungan || $toleransi || !empty(trim($keterangan))) {
                
                $cekData = $builder->where(['student_id' => $student_id, 'tanggal' => $tanggal])->get()->getRow();
                
                $payload = [
                    'student_id'       => $student_id,
                    'rombel_id'        => $rombel_id,
                    'tanggal'          => $tanggal,
                    'berdoa'           => $berdoa,
                    'kalimat_thoyibah' => $kalimat_thoyibah,
                    'shalat'           => $shalat,
                    'salam'            => $salam,
                    'syukur'           => $syukur,
                    'lingkungan'       => $lingkungan,
                    'toleransi'        => $toleransi,
                    'keterangan'       => $keterangan
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

        return redirect()->back()->with('success', 'Data aspek spiritual tanggal ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil disimpan!');
    }

    // =========================================================================
    // 4. HALAMAN REKAP KELAS (Meniru KepatuhanController::rekapKelas)
    // =========================================================================
    public function rekapKelas($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Filter Tahun dan Semester
        $tahun    = $request->getGet('tahun') ?? date('Y');
        $semester = $request->getGet('semester') ?? (date('n') >= 7 ? 'ganjil' : 'genap');

        if ($semester === 'ganjil') {
            $array_bulan = [7, 8, 9, 10, 11, 12];
            $nama_bulan  = [7=>'JULI', 8=>'AGUSTUS', 9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER'];
        } else {
            $array_bulan = [1, 2, 3, 4, 5, 6];
            $nama_bulan  = [1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL', 5=>'MEI', 6=>'JUNI'];
        }

        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // 1. Ambil daftar siswa di kelas tersebut
        $students = $db->table('class_rombel_students crs')
                       ->select('u.id as student_id, u.username')
                       ->join('users u', 'u.id = crs.student_id')
                       ->where('crs.rombel_id', $rombel_id)
                       ->orderBy('u.username', 'ASC')
                       ->get()->getResultArray();

       // 2. Ambil total pelanggaran per siswa & per bulan + Gabungkan Keterangan
        $records = $db->table('aspek_spiritual')
                      ->select('student_id, MONTH(tanggal) as bulan, 
                                SUM(berdoa) as sum_berdoa, 
                                SUM(kalimat_thoyibah) as sum_kalimat, 
                                SUM(shalat) as sum_shalat, 
                                SUM(salam) as sum_salam, 
                                SUM(syukur) as sum_syukur, 
                                SUM(lingkungan) as sum_lingkungan,
                                SUM(toleransi) as sum_toleransi,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR " | ") as gabungan_keterangan')
                      ->where('rombel_id', $rombel_id)
                      ->where('YEAR(tanggal)', $tahun)
                      ->whereIn('MONTH(tanggal)', $array_bulan)
                      ->groupBy('student_id, MONTH(tanggal)')
                      ->get()->getResultArray();

        // 3. Susun data menjadi Pivot
        $rekapData = [];
        foreach ($records as $r) {
            $rekapData[$r['student_id']][$r['bulan']] = [
                'berdoa'           => $r['sum_berdoa'],
                'kalimat_thoyibah' => $r['sum_kalimat'],
                'shalat'           => $r['sum_shalat'],
                'salam'            => $r['sum_salam'],
                'syukur'           => $r['sum_syukur'],
                'lingkungan'       => $r['sum_lingkungan'],
                'toleransi'        => $r['sum_toleransi'],
                'keterangan'       => $r['gabungan_keterangan']
            ];
        }

        $data = [
            'title'       => 'Rekap Aspek Spiritual: ' . $rombel['rombel_name'],
            'rombel_id'   => $rombel_id,
            'rombel'      => $rombel,
            'tahun'       => $tahun,
            'semester'    => $semester,
            'array_bulan' => $array_bulan,
            'nama_bulan'  => $nama_bulan,
            'students'    => $students,
            'rekapData'   => $rekapData
        ];

        return view('admin/spiritual/rekap_kelas', $data);
    }
}