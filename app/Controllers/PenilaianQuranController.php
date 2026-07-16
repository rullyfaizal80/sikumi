<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class PenilaianQuranController extends BaseController
{
    // Halaman Utama: Pilih Kelompok untuk Penilaian Qur'an
    public function index()
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('login');
        }

        $db = \Config\Database::connect();

        // Ambil daftar kelompok beserta nama pembimbingnya
        $daftarKelompok = $db->table('quran_groups qg')
            ->select('qg.id, qg.nama_kelompok, qg.jenis_kelompok, u.username as pembimbing')
            ->join('users u', 'u.id = qg.pembimbing_id', 'left')
            ->orderBy('qg.nama_kelompok', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'          => 'Penilaian Al-Qur\'an',
            'daftarKelompok' => $daftarKelompok
        ];

        return view('guru/quran/index', $data);
    }

    // =======================================================
    // HALAMAN INPUT TAHSIN
    // =======================================================
    public function tahsin($group_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data kelompok (menggantikan rombel)
        $kelompok = $db->table('quran_groups')->where('id', $group_id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to('guru/quran')->with('error', 'Kelompok tidak ditemukan.');

        // Filter Parameter
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $pekan = $this->request->getGet('pekan') ?? 1;

        // Ambil Daftar Siswa berdasarkan anggota kelompok tersebut
        $daftarSiswa = $db->table('quran_group_students qgs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = qgs.student_id')
                          ->where('qgs.group_id', $group_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        // Ambil Data Nilai (jika sudah ada)
        $studentIds = array_column($daftarSiswa, 'student_id');
        $nilaiData = [];
        
        if (!empty($studentIds)) {
            $existing = $db->table('quran_penilaian')
                           ->whereIn('student_id', $studentIds)
                           ->where('bulan', sprintf('%02d', $bulan))
                           ->where('tahun', $tahun)
                           ->where('pekan', $pekan)
                           ->get()->getResultArray();
                           
            foreach ($existing as $row) {
                $nilaiData[$row['student_id']] = $row;
            }
        }

        $data = [
            'title'       => 'Input Tahsin - Kelompok ' . $kelompok['nama_kelompok'],
            'kelompok'    => $kelompok,
            'bulan'       => sprintf('%02d', $bulan),
            'tahun'       => $tahun,
            'pekan'       => $pekan,
            'daftarSiswa' => $daftarSiswa,
            'nilaiData'   => $nilaiData
        ];

        return view('guru/quran/tahsin', $data);
    }

    // =======================================================
    // PROSES SIMPAN TAHSIN
    // =======================================================
    public function saveTahsin()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();
        
        // Tangkap group_id (menggantikan rombel_id)
        $group_id  = $post['group_id'];
        $bulan     = sprintf('%02d', $post['bulan']);
        $tahun     = $post['tahun'];
        $pekan     = $post['pekan'];
        $dataSiswa = $post['data'] ?? [];

        foreach ($dataSiswa as $student_id => $val) {
            // Cek apakah data pekan tersebut sudah ada di database
            $cek = $db->table('quran_penilaian')
                      ->where(['student_id' => $student_id, 'bulan' => $bulan, 'tahun' => $tahun, 'pekan' => $pekan])
                      ->get()->getRowArray();

            $updateData = [
                'tahsin_talqin'   => $val['talqin'] ?? null,
                'tahsin_riyadhah' => $val['riyadhah'] ?? null,
                'tahsin_nilai'    => $val['nilai'] ?? null,
                'tahsin_catatan'  => $val['catatan'] ?? null,
            ];

            if ($cek) {
                // Jika sudah ada, cukup update khusus kolom Tahsin saja
                $db->table('quran_penilaian')->where('id', $cek['id'])->update($updateData);
            } else {
                // Jika belum ada, buat record baru dengan menggunakan group_id
                $insertData = array_merge($updateData, [
                    'student_id' => $student_id,
                    'group_id'   => $group_id, 
                    'bulan'      => $bulan,
                    'tahun'      => $tahun,
                    'pekan'      => $pekan
                ]);
                $db->table('quran_penilaian')->insert($insertData);
            }
        }

        return redirect()->to("guru/quran/tahsin/{$group_id}?bulan={$bulan}&tahun={$tahun}&pekan={$pekan}")
                         ->with('success', "Data Tahsin Pekan ke-{$pekan} berhasil disimpan!");
    }

    // =======================================================
    // HALAMAN INPUT TAHFIDZ
    // =======================================================
    public function tahfidz($group_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data kelompok
        $kelompok = $db->table('quran_groups')->where('id', $group_id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to('guru/quran')->with('error', 'Kelompok tidak ditemukan.');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $pekan = $this->request->getGet('pekan') ?? 1;

        // Ambil daftar siswa berdasarkan keanggotaan kelompok
        $daftarSiswa = $db->table('quran_group_students qgs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = qgs.student_id')
                          ->where('qgs.group_id', $group_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        $studentIds = array_column($daftarSiswa, 'student_id');
        $nilaiData = [];
        
        if (!empty($studentIds)) {
            $existing = $db->table('quran_penilaian')
                           ->whereIn('student_id', $studentIds)
                           ->where('bulan', sprintf('%02d', $bulan))
                           ->where('tahun', $tahun)
                           ->where('pekan', $pekan)
                           ->get()->getResultArray();
                           
            foreach ($existing as $row) {
                $nilaiData[$row['student_id']] = $row;
            }
        }

        $data = [
            'title'       => 'Input Tahfidz - Kelompok ' . $kelompok['nama_kelompok'],
            'kelompok'    => $kelompok,
            'bulan'       => sprintf('%02d', $bulan),
            'tahun'       => $tahun,
            'pekan'       => $pekan,
            'daftarSiswa' => $daftarSiswa,
            'nilaiData'   => $nilaiData
        ];

        return view('guru/quran/tahfidz', $data);
    }

    // =======================================================
    // PROSES SIMPAN TAHFIDZ
    // =======================================================
    public function saveTahfidz()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();
        
        // Gunakan group_id
        $group_id  = $post['group_id'];
        $bulan     = sprintf('%02d', $post['bulan']);
        $tahun     = $post['tahun'];
        $pekan     = $post['pekan'];
        $dataSiswa = $post['data'] ?? [];

        foreach ($dataSiswa as $student_id => $val) {
            $cek = $db->table('quran_penilaian')
                      ->where(['student_id' => $student_id, 'bulan' => $bulan, 'tahun' => $tahun, 'pekan' => $pekan])
                      ->get()->getRowArray();

            $updateData = [
                'tahfidz_sabqi'   => $val['sabqi'] ?? null,
                'tahfidz_sabaq'   => $val['sabaq'] ?? null,
                'tahfidz_nilai'   => $val['nilai'] ?? null,
                'tahfidz_catatan' => $val['catatan'] ?? null,
            ];

            if ($cek) {
                // Update khusus kolom Tahfidz saja
                $db->table('quran_penilaian')->where('id', $cek['id'])->update($updateData);
            } else {
                // Insert baru jika belum ada, simpan group_id
                $insertData = array_merge($updateData, [
                    'student_id' => $student_id,
                    'group_id'   => $group_id,
                    'bulan'      => $bulan,
                    'tahun'      => $tahun,
                    'pekan'      => $pekan
                ]);
                $db->table('quran_penilaian')->insert($insertData);
            }
        }

        return redirect()->to("guru/quran/tahfidz/{$group_id}?bulan={$bulan}&tahun={$tahun}&pekan={$pekan}")
                         ->with('success', "Data Tahfidz Pekan ke-{$pekan} berhasil disimpan!");
    }

    // =======================================================
    // HALAMAN INPUT KITABAH
    // =======================================================
    public function kitabah($group_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data kelompok
        $kelompok = $db->table('quran_groups')->where('id', $group_id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to('guru/quran')->with('error', 'Kelompok tidak ditemukan.');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');
        $pekan = $this->request->getGet('pekan') ?? 1;

        // Ambil daftar siswa berdasarkan keanggotaan kelompok
        $daftarSiswa = $db->table('quran_group_students qgs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = qgs.student_id')
                          ->where('qgs.group_id', $group_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        $studentIds = array_column($daftarSiswa, 'student_id');
        $nilaiData = [];
        
        if (!empty($studentIds)) {
            $existing = $db->table('quran_penilaian')
                           ->whereIn('student_id', $studentIds)
                           ->where('bulan', sprintf('%02d', $bulan))
                           ->where('tahun', $tahun)
                           ->where('pekan', $pekan)
                           ->get()->getResultArray();
                           
            foreach ($existing as $row) {
                $nilaiData[$row['student_id']] = $row;
            }
        }

        $data = [
            'title'       => 'Input Kitabah - Kelompok ' . $kelompok['nama_kelompok'],
            'kelompok'    => $kelompok,
            'bulan'       => sprintf('%02d', $bulan),
            'tahun'       => $tahun,
            'pekan'       => $pekan,
            'daftarSiswa' => $daftarSiswa,
            'nilaiData'   => $nilaiData
        ];

        return view('guru/quran/kitabah', $data);
    }

    // =======================================================
    // PROSES SIMPAN KITABAH
    // =======================================================
    public function saveKitabah()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();
        
        // Gunakan group_id
        $group_id  = $post['group_id'];
        $bulan     = sprintf('%02d', $post['bulan']);
        $tahun     = $post['tahun'];
        $pekan     = $post['pekan'];
        $dataSiswa = $post['data'] ?? [];

        foreach ($dataSiswa as $student_id => $val) {
            $cek = $db->table('quran_penilaian')
                      ->where(['student_id' => $student_id, 'bulan' => $bulan, 'tahun' => $tahun, 'pekan' => $pekan])
                      ->get()->getRowArray();

            $updateData = [
                'kitabah_surat'   => $val['surat'] ?? null,
                'kitabah_nilai'   => $val['nilai'] ?? null,
                'kitabah_catatan' => $val['catatan'] ?? null,
            ];

            if ($cek) {
                // Update khusus kolom Kitabah saja
                $db->table('quran_penilaian')->where('id', $cek['id'])->update($updateData);
            } else {
                // Insert baru jika belum ada, simpan group_id
                $insertData = array_merge($updateData, [
                    'student_id' => $student_id,
                    'group_id'   => $group_id,
                    'bulan'      => $bulan,
                    'tahun'      => $tahun,
                    'pekan'      => $pekan
                ]);
                $db->table('quran_penilaian')->insert($insertData);
            }
        }

        return redirect()->to("guru/quran/kitabah/{$group_id}?bulan={$bulan}&tahun={$tahun}&pekan={$pekan}")
                         ->with('success', "Data Kitabah Pekan ke-{$pekan} berhasil disimpan!");
    }

    // =======================================================
    // HALAMAN REKAP BULANAN
    // =======================================================
    public function rekap($group_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data kelompok
        $kelompok = $db->table('quran_groups')->where('id', $group_id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to('guru/quran')->with('error', 'Kelompok tidak ditemukan.');

        // Filter default adalah bulan dan tahun saat ini
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // Ambil daftar siswa berdasarkan keanggotaan kelompok
        $daftarSiswa = $db->table('quran_group_students qgs')
                          ->select('u.id as student_id, u.username')
                          ->join('users u', 'u.id = qgs.student_id')
                          ->where('qgs.group_id', $group_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        $studentIds = array_column($daftarSiswa, 'student_id');
        $records = [];
        
        // Ambil semua data pada bulan & tahun tersebut (Pekan 1 sampai 5)
        if (!empty($studentIds)) {
            $records = $db->table('quran_penilaian')
                          ->whereIn('student_id', $studentIds)
                          ->where('bulan', sprintf('%02d', $bulan))
                          ->where('tahun', $tahun)
                          ->orderBy('pekan', 'ASC')
                          ->get()->getResultArray();
        }

        // Wadah sementara untuk mengumpulkan data per siswa
        $rawData = [];
        foreach ($studentIds as $sId) {
            $rawData[$sId] = [
                't_talqin' => [], 't_riyadhah' => [], 't_nilai' => [], 't_catatan' => [],
                'h_sabqi'  => [], 'h_sabaq'    => [], 'h_nilai' => [], 'h_catatan' => [],
                'k_surat'  => [], 'k_nilai'    => [], 'k_catatan' => []
            ];
        }

        // Proses pengelompokan data
        foreach ($records as $row) {
            $sId = $row['student_id'];
            
            // Hindari error offset jika ada sisa data lama yang muridnya sudah keluar kelompok
            if (!isset($rawData[$sId])) continue;
            
            // Kategori Tahsin
            if (!empty(trim($row['tahsin_talqin'])))   $rawData[$sId]['t_talqin'][] = trim($row['tahsin_talqin']);
            if (!empty(trim($row['tahsin_riyadhah']))) $rawData[$sId]['t_riyadhah'][] = trim($row['tahsin_riyadhah']);
            if (!empty(trim($row['tahsin_nilai'])))    $rawData[$sId]['t_nilai'][] = (float)str_replace(',', '.', $row['tahsin_nilai']);
            if (!empty(trim($row['tahsin_catatan'])))  $rawData[$sId]['t_catatan'][] = trim($row['tahsin_catatan']);

            // Kategori Tahfidz
            if (!empty(trim($row['tahfidz_sabqi'])))   $rawData[$sId]['h_sabqi'][] = trim($row['tahfidz_sabqi']);
            if (!empty(trim($row['tahfidz_sabaq'])))   $rawData[$sId]['h_sabaq'][] = trim($row['tahfidz_sabaq']);
            if (!empty(trim($row['tahfidz_nilai'])))   $rawData[$sId]['h_nilai'][] = (float)str_replace(',', '.', $row['tahfidz_nilai']);
            if (!empty(trim($row['tahfidz_catatan']))) $rawData[$sId]['h_catatan'][] = trim($row['tahfidz_catatan']);

            // Kategori Kitabah
            if (!empty(trim($row['kitabah_surat'])))   $rawData[$sId]['k_surat'][] = trim($row['kitabah_surat']);
            if (!empty(trim($row['kitabah_nilai'])))   $rawData[$sId]['k_nilai'][] = (float)str_replace(',', '.', $row['kitabah_nilai']);
            if (!empty(trim($row['kitabah_catatan']))) $rawData[$sId]['k_catatan'][] = trim($row['kitabah_catatan']);
        }

        // Hitung rata-rata dan gabungkan data final
        $rekapFinal = [];
        foreach ($daftarSiswa as $siswa) {
            $sId = $siswa['student_id'];
            $d = $rawData[$sId];

            // Fungsi rata-rata
            $avg = function($arr) {
                if (count($arr) == 0) return '-';
                return number_format(array_sum($arr) / count($arr), 1, ',', '');
            };

            $rekapFinal[$sId] = [
                'tahsin_talqin'   => implode(', ', array_unique($d['t_talqin'])),
                'tahsin_riyadhah' => implode(', ', array_unique($d['t_riyadhah'])),
                'tahsin_nilai'    => $avg($d['t_nilai']),
                'tahsin_catatan'  => implode('<br>', $d['t_catatan']),

                'tahfidz_sabqi'   => implode(', ', array_unique($d['h_sabqi'])),
                'tahfidz_sabaq'   => implode(', ', array_unique($d['h_sabaq'])),
                'tahfidz_nilai'   => $avg($d['h_nilai']),
                'tahfidz_catatan' => implode('<br>', $d['h_catatan']),

                'kitabah_surat'   => implode(', ', array_unique($d['k_surat'])),
                'kitabah_nilai'   => $avg($d['k_nilai']),
                'kitabah_catatan' => implode('<br>', $d['k_catatan']),
            ];
        }

        $data = [
            'title'       => 'Rekap Bulanan Al-Qur\'an - Kelompok ' . $kelompok['nama_kelompok'],
            'kelompok'    => $kelompok,
            'bulan'       => sprintf('%02d', $bulan),
            'tahun'       => $tahun,
            'daftarSiswa' => $daftarSiswa,
            'rekapFinal'  => $rekapFinal
        ];

        return view('guru/quran/rekap', $data);
    }

    // =======================================================
    // HALAMAN JURNAL MENGAJAR
    // =======================================================
    public function jurnal($group_id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        $kelompok = $db->table('quran_groups')->where('id', $group_id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to('guru/quran')->with('error', 'Kelompok tidak ditemukan.');

        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // Ambil daftar anggota kelompok untuk dropdown ketidakhadiran
        $daftarSiswa = $db->table('quran_group_students qgs')
                          ->select('u.username')
                          ->join('users u', 'u.id = qgs.student_id')
                          ->where('qgs.group_id', $group_id)
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        // Mengambil data jurnal berdasarkan kelompok
        $jurnalList = $db->table('quran_journals')
                         ->where('group_id', $group_id)
                         ->where('MONTH(tanggal)', $bulan)
                         ->where('YEAR(tanggal)', $tahun)
                         ->orderBy('tanggal', 'ASC')
                         ->get()->getResultArray();

        $data = [
            'title'       => 'Jurnal Mengajar - Kelompok ' . $kelompok['nama_kelompok'],
            'kelompok'    => $kelompok,
            'bulan'       => sprintf('%02d', $bulan),
            'tahun'       => $tahun,
            'jurnalList'  => $jurnalList,
            'daftarSiswa' => $daftarSiswa // <-- Variabel baru untuk View
        ];

        return view('guru/quran/jurnal', $data);
    }

    // =======================================================
    // PROSES SIMPAN / UPDATE JURNAL (VIA MODAL)
    // =======================================================
    public function saveJurnal()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $group_id = $post['group_id'];
        $tanggal  = $post['tanggal'];
        
        $data = [
            'group_id'          => $group_id,
            'tanggal'           => $tanggal,
            'kegiatan'          => $post['kegiatan'] ?? '',
            'kendala'           => $post['kendala'] ?? '',
            'tindak_lanjut'     => $post['tindak_lanjut'] ?? '',
            'murid_tidak_hadir' => $post['murid_tidak_hadir'] ?? '' // Langsung simpan string dari textarea
        ];

        $existing = $db->table('quran_journals')
                       ->where(['group_id' => $group_id, 'tanggal' => $tanggal])
                       ->get()->getRowArray();

        if ($existing) {
            $db->table('quran_journals')->where('id', $existing['id'])->update($data);
            $msg = "Jurnal tanggal " . date('d/m/Y', strtotime($tanggal)) . " berhasil diperbarui.";
        } else {
            $db->table('quran_journals')->insert($data);
            $msg = "Jurnal tanggal " . date('d/m/Y', strtotime($tanggal)) . " berhasil ditambahkan.";
        }

        $bulan = date('m', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));

        return redirect()->to("guru/quran/jurnal/{$group_id}?bulan={$bulan}&tahun={$tahun}")
                         ->with('success', $msg);
    }

    // =======================================================
    // PROSES HAPUS JURNAL
    // =======================================================
    public function deleteJurnal($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Cari data jurnal berdasarkan ID
        $jurnal = $db->table('quran_journals')->where('id', $id)->get()->getRowArray();
        
        if (!$jurnal) {
            return redirect()->back()->with('error', 'Data jurnal tidak ditemukan.');
        }

        $group_id = $jurnal['group_id'];
        $bulan    = date('m', strtotime($jurnal['tanggal']));
        $tahun    = date('Y', strtotime($jurnal['tanggal']));
        $tanggal  = date('d/m/Y', strtotime($jurnal['tanggal']));

        // Eksekusi hapus
        $db->table('quran_journals')->where('id', $id)->delete();

        return redirect()->to("guru/quran/jurnal/{$group_id}?bulan={$bulan}&tahun={$tahun}")
                         ->with('success', "Jurnal tanggal {$tanggal} berhasil dihapus.");
    }

}