<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AspekSosialController extends BaseController
{
    // =========================================================================
    // 1. HALAMAN DAFTAR KELAS
    // =========================================================================
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
            'title'        => 'Modul Aspek Sosial',
            'daftar_kelas' => $rombels
        ];

        return view('admin/aspek_sosial/index', $data);
    }

    // =========================================================================
    // 2. HALAMAN INPUT SOSIAL
    // =========================================================================
    public function input($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Ambil tanggal dari filter (default hari ini)[cite: 12]
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');

        // Detail Rombel[cite: 12]
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // Menggunakan tabel 'users' untuk data siswa seperti di SpiritualController[cite: 12]
        $students = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username as name, aso.disiplin, aso.jujur, aso.percaya_diri, aso.santun, aso.kerjasama, aso.tanggung_jawab, aso.adil, aso.keterangan')
            ->join('users u', 'u.id = crs.student_id')
            ->join('aspek_sosial aso', "aso.student_id = u.id AND aso.tanggal = '{$tanggal}'", 'left')
            ->where('crs.rombel_id', $rombel_id)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'     => 'Input Aspek Sosial: ' . $rombel['rombel_name'],
            'rombel_id' => $rombel_id,
            'rombel'    => $rombel,
            'tanggal'   => $tanggal,
            'students'  => $students
        ];

        return view('admin/aspek_sosial/input', $data);
    }

    // =========================================================================
    // 3. PROSES SIMPAN DATA (Array Input)
    // =========================================================================
    public function save()
    {
        $db = \Config\Database::connect();
        
        $rombel_id = $this->request->getPost('rombel_id');
        $tanggal   = $this->request->getPost('tanggal');
        $students  = $this->request->getPost('students'); // Menangkap data bentuk array seperti SpiritualController[cite: 12]
        
        $builder = $db->table('aspek_sosial');

        if (!empty($students)) {
            foreach ($students as $student_id => $data) {
                
                // Ambil nilai dari array form, jika dicentang nilainya 1, jika tidak 0
                $disiplin       = isset($data['disiplin']) ? 1 : 0;
                $jujur          = isset($data['jujur']) ? 1 : 0;
                $percaya_diri   = isset($data['percaya_diri']) ? 1 : 0;
                $santun         = isset($data['santun']) ? 1 : 0;
                $kerjasama      = isset($data['kerjasama']) ? 1 : 0;
                $tanggung_jawab = isset($data['tanggung_jawab']) ? 1 : 0;
                $adil           = isset($data['adil']) ? 1 : 0;
                $keterangan     = $data['keterangan'] ?? '';

                // Logika: simpan jika ada pelanggaran/catatan[cite: 12]
                if ($disiplin || $jujur || $percaya_diri || $santun || $kerjasama || $tanggung_jawab || $adil || !empty(trim($keterangan))) {
                    
                    $cekData = $builder->where(['student_id' => $student_id, 'tanggal' => $tanggal])->get()->getRow();
                    
                    $payload = [
                        'student_id'     => $student_id,
                        'rombel_id'      => $rombel_id,
                        'tanggal'        => $tanggal,
                        'disiplin'       => $disiplin,
                        'jujur'          => $jujur,
                        'percaya_diri'   => $percaya_diri,
                        'santun'         => $santun,
                        'kerjasama'      => $kerjasama,
                        'tanggung_jawab' => $tanggung_jawab,
                        'adil'           => $adil,
                        'keterangan'     => $keterangan
                    ];

                    if ($cekData) {
                        $builder->where('id', $cekData->id)->update($payload);
                    } else {
                        $builder->insert($payload);
                    }
                } else {
                    // Jika dihapus semua ceklisnya (bersih), hapus record[cite: 12]
                    $builder->where(['student_id' => $student_id, 'tanggal' => $tanggal])->delete();
                }
            }
        }

        return redirect()->back()->with('success', 'Data aspek sosial tanggal ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil disimpan!');
    }

    // =========================================================================
    // 4. HALAMAN REKAP KELAS (Matriks Semester)
    // =========================================================================
    public function rekap_kelas($rombel_id)
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Mengikuti logika Ganjil/Genap[cite: 12]
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

        // 1. Ambil daftar siswa menggunakan tabel users[cite: 12]
        $students = $db->table('class_rombel_students crs')
                       ->select('u.id as student_id, u.username as name')
                       ->join('users u', 'u.id = crs.student_id')
                       ->where('crs.rombel_id', $rombel_id)
                       ->orderBy('u.username', 'ASC')
                       ->get()->getResultArray();

        // 2. Ambil total rekapan
        $records = $db->table('aspek_sosial')
                      ->select('student_id, MONTH(tanggal) as bulan, 
                                SUM(disiplin) as sum_disiplin, 
                                SUM(jujur) as sum_jujur, 
                                SUM(percaya_diri) as sum_pd, 
                                SUM(santun) as sum_santun, 
                                SUM(kerjasama) as sum_kerjasama, 
                                SUM(tanggung_jawab) as sum_tj,
                                SUM(adil) as sum_adil,
                                GROUP_CONCAT(NULLIF(keterangan, "") SEPARATOR " | ") as gabungan_keterangan')
                      ->where('rombel_id', $rombel_id)
                      ->where('YEAR(tanggal)', $tahun)
                      ->whereIn('MONTH(tanggal)', $array_bulan)
                      ->groupBy('student_id, MONTH(tanggal)')
                      ->get()->getResultArray();

        // 3. Susun pivot data[cite: 12]
        $rekapData = [];
        foreach ($records as $r) {
            $rekapData[$r['student_id']][$r['bulan']] = [
                'disiplin'       => $r['sum_disiplin'],
                'jujur'          => $r['sum_jujur'],
                'percaya_diri'   => $r['sum_pd'],
                'santun'         => $r['sum_santun'],
                'kerjasama'      => $r['sum_kerjasama'],
                'tanggung_jawab' => $r['sum_tj'],
                'adil'           => $r['sum_adil'],
                'keterangan'     => $r['gabungan_keterangan']
            ];
        }

        $data = [
            'title'       => 'Rekap Aspek Sosial: ' . $rombel['rombel_name'],
            'rombel_id'   => $rombel_id,
            'rombel'      => $rombel,
            'tahun'       => $tahun,
            'semester'    => $semester,
            'array_bulan' => $array_bulan,
            'nama_bulan'  => $nama_bulan,
            'students'    => $students,
            'rekapData'   => $rekapData
        ];

        return view('admin/aspek_sosial/rekap_kelas', $data);
    }
}