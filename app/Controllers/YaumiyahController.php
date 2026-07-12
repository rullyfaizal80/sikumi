<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class YaumiyahController extends BaseController
{
    public function index()
    {
        // Pastikan user sudah login[cite: 4]
        if (!auth()->loggedIn()) {
            return redirect()->to('login');
        }

        $db      = \Config\Database::connect();
        $user    = auth()->user(); // Ambil data user aktif[cite: 4]
        $student_id = $user->id;   // Ambil ID user[cite: 4]

        // Filter Bulan dan Tahun (Default bulan ini)
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $jumlahHari = date('t', strtotime("$tahun-$bulan-01"));

        // Ambil data yaumiyah siswa ini di bulan terpilih
        $yaumiyahRaw = $db->table('yaumiyah')
                          ->where('student_id', $student_id)
                          ->where('MONTH(tanggal)', $bulan)
                          ->where('YEAR(tanggal)', $tahun)
                          ->get()->getResultArray();

        // Susun data agar mudah diakses di view berdasarkan tanggal
        $yaumiyahData = [];
        foreach ($yaumiyahRaw as $row) {
            $tgl = date('Y-m-d', strtotime($row['tanggal']));
            $yaumiyahData[$tgl] = $row;
        }

        $data = [
            'title'        => 'Jurnal Yaumiyah',
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'jumlahHari'   => $jumlahHari,
            'yaumiyahData' => $yaumiyahData
        ];

        return view('siswa/yaumiyah/index', $data);
    }

    public function save()
    {
        $db = \Config\Database::connect();
        $user = auth()->user(); //[cite: 4]
        $student_id = $user->id; //[cite: 4]
        
        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');
        $yaumiyahPost = $this->request->getPost('yaumiyah'); // Array data dari form

        $jumlahHari = date('t', strtotime("$tahun-$bulan-01"));
        $batchData = [];

        // Looping seluruh hari di bulan tersebut untuk mendeteksi checkbox
        for ($i = 1; $i <= $jumlahHari; $i++) {
            $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
            
            // Cek apakah di tanggal ini ada minimal 1 aktivitas yang diceklis
            if (isset($yaumiyahPost[$tgl])) {
                $harian = $yaumiyahPost[$tgl];
                
                $batchData[] = [
                    'student_id'     => $student_id,
                    'tanggal'        => $tgl,
                    'dzuhur'         => isset($harian['dzuhur']) ? 1 : 0,
                    'ashar'          => isset($harian['ashar']) ? 1 : 0,
                    'bakdiah_dzuhur' => isset($harian['bakdiah_dzuhur']) ? 1 : 0,
                    'duha'           => isset($harian['duha']) ? 1 : 0,
                    'tahajud'        => isset($harian['tahajud']) ? 1 : 0,
                    'tilawah'        => isset($harian['tilawah']) ? 1 : 0,
                    'infaq'          => isset($harian['infaq']) ? 1 : 0,
                    'shaum'          => isset($harian['shaum']) ? 1 : 0,
                    'literasi'       => isset($harian['literasi']) ? 1 : 0,
                ];
            }
        }

        $db->transStart();
        
        // 1. Hapus semua data bulan ini (untuk me-reset yang di-uncheck)
        $db->table('yaumiyah')
           ->where('student_id', $student_id)
           ->where('MONTH(tanggal)', $bulan)
           ->where('YEAR(tanggal)', $tahun)
           ->delete();

        // 2. Insert data baru jika ada yang diceklis
        if (!empty($batchData)) {
            $db->table('yaumiyah')->insertBatch($batchData);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Gagal menyimpan data yaumiyah.');
        }

        return redirect()->back()->with('success', 'Jurnal Yaumiyah bulan ini berhasil disimpan.');
    }
}