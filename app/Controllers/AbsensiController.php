<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class AbsensiController extends BaseController
{

    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Cari Tahun Ajaran yang sedang aktif
        $taAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        
        if (!$taAktif) {
            // Jika belum ada tahun ajaran aktif, kembalikan ke dashboard dengan pesan error
            return redirect()->to(base_url('/'))->with('error', 'Tidak ada tahun ajaran aktif. Silakan seting terlebih dahulu.');
        }

        // 2. Ambil daftar rombel HANYA pada tahun ajaran yang aktif tersebut
        $daftarRombel = $db->table('class_rombel')
                           ->where('academic_year_id', $taAktif['id'])
                           ->orderBy('rombel_name', 'ASC')
                           ->get()->getResultArray();

        $data = [
            'daftarRombel' => $daftarRombel
        ];

        return view('admin/absensi/index', $data);
    }

    public function input($rombel_id)
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        
        // 1. Ambil tanggal dari URL (jika user ganti tanggal), default hari ini
        $tanggal = $request->getGet('tanggal') ?? date('Y-m-d');

        // Ambil Tahun Ajaran Aktif
        $taAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$taAktif) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        // Ambil Data Rombel
        $rombel = $db->table('class_rombel')->where('id', $rombel_id)->get()->getRowArray();

        // Ambil Daftar Siswa
        $siswaKelas = $db->table('class_rombel_students crs')
                         ->select('u.id as student_id, u.username')
                         ->join('users u', 'u.id = crs.student_id')
                         ->where('crs.rombel_id', $rombel_id)
                         ->orderBy('u.username', 'ASC')
                         ->get()->getResultArray();

        // 2. CEK RIWAYAT ABSENSI DI TANGGAL TERSEBUT
        $cekAbsensi = $db->table('absensi')
                         ->where('rombel_id', $rombel_id)
                         ->where('tanggal', $tanggal)
                         ->get()->getRowArray();

        $absensiDetails = [];
        if ($cekAbsensi) {
            $details = $db->table('absensi_details')->where('absensi_id', $cekAbsensi['id'])->get()->getResultArray();
            // Ubah format array agar mudah dibaca di view (key = student_id)
            foreach ($details as $d) {
                $absensiDetails[$d['student_id']] = $d;
            }
        }

        $data = [
            'taAktif'        => $taAktif,
            'rombel'         => $rombel,
            'siswaKelas'     => $siswaKelas,
            'tanggal'        => $tanggal,
            'absensiDetails' => $absensiDetails // Kirim riwayat ke View
        ];

        return view('admin/absensi/input', $data);
    }

    public function store()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        $rombel_id        = $request->getPost('rombel_id');
        $academic_year_id = $request->getPost('academic_year_id');
        $tanggal          = $request->getPost('tanggal');
        $pencatat_id      = session()->get('user_id') ?? session()->get('id') ?? 1; 
        $siswaData        = $request->getPost('siswa'); 

        if (empty($siswaData)) {
             return redirect()->back()->with('error', 'Gagal: Data absensi kosong.');
        }

        try {
            $db->transStart();

            // 1. Cek apakah absensi sudah ada
            $cekAbsensi = $db->table('absensi')
                             ->where('rombel_id', $rombel_id)
                             ->where('tanggal', $tanggal)
                             ->get()->getRowArray();

            if ($cekAbsensi) {
                // JIKA SUDAH ADA: Update parent dan hapus detail lama
                $absensi_id = $cekAbsensi['id'];
                
                $db->table('absensi')->where('id', $absensi_id)->update([
                    'pencatat_id' => $pencatat_id,
                    'updated_at'  => date('Y-m-d H:i:s')
                ]);
                
                $db->table('absensi_details')->where('absensi_id', $absensi_id)->delete();
                $pesanSukses = 'Data absensi harian berhasil diperbarui (Revisi).';
            } else {
                // JIKA BELUM ADA: Insert baru
                $db->table('absensi')->insert([
                    'academic_year_id' => $academic_year_id,
                    'rombel_id'        => $rombel_id,
                    'tanggal'          => $tanggal,
                    'pencatat_id'      => $pencatat_id,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s')
                ]);
                $absensi_id = $db->insertID();
                $pesanSukses = 'Data absensi harian berhasil disimpan.';
            }

            // 2. Siapkan Data Detail Baru (Untuk Insert/Update)
            $batchDetails = [];
            foreach ($siswaData as $student_id => $dataAbsen) {
                $batchDetails[] = [
                    'absensi_id'          => $absensi_id,
                    'student_id'          => $student_id,
                    'status'              => $dataAbsen['status'],
                    'keterlambatan_menit' => empty($dataAbsen['terlambat']) ? 0 : $dataAbsen['terlambat'],
                    'keterangan'          => empty($dataAbsen['keterangan']) ? null : $dataAbsen['keterangan'],
                ];
            }

            // 3. Simpan Massal ke Tabel Detail
            if (!empty($batchDetails)) {
                $db->table('absensi_details')->insertBatch($batchDetails);
            }

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                return redirect()->back()->with('error', 'Transaksi dibatalkan oleh database (Rollback).');
            }

            // Redirect kembali ke form di tanggal yang sama
            return redirect()->to(base_url("admin/absensi/input/{$rombel_id}?tanggal={$tanggal}"))->with('sukses', $pesanSukses);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'PESAN ERROR ASLI: ' . $e->getMessage());
        }
    }

    public function rekap()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // Ambil filter dari URL (jika ada), default ke bulan & tahun sekarang
        $rombel_id = $request->getGet('rombel_id');
        $bulan     = $request->getGet('bulan') ?? date('m');
        $tahun     = $request->getGet('tahun') ?? date('Y');

        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();

        $siswaKelas = [];
        $rekapData  = [];
        $jumlahHari = date('t', strtotime("$tahun-$bulan-01")); 

        if (!empty($rombel_id)) {
            // 1. Ambil Daftar Siswa di kelas tersebut
            $siswaKelas = $db->table('class_rombel_students crs')
                             ->select('u.id as student_id, u.username')
                             ->join('users u', 'u.id = crs.student_id')
                             ->where('crs.rombel_id', $rombel_id)
                             ->orderBy('u.username', 'ASC')
                             ->get()->getResultArray();

            // 2. Ambil data absensi beserta menit keterlambatan
            $absensiBulanan = $db->table('absensi a')
                                 ->select('a.tanggal, ad.student_id, ad.status, ad.keterlambatan_menit')
                                 ->join('absensi_details ad', 'ad.absensi_id = a.id')
                                 ->where('a.rombel_id', $rombel_id)
                                 ->where('MONTH(a.tanggal)', $bulan)
                                 ->where('YEAR(a.tanggal)', $tahun)
                                 ->get()->getResultArray();

            // 3. Susun ulang data menjadi array multi-dimensi (Status & Menit)
            foreach ($absensiBulanan as $row) {
                $tgl = (int) date('j', strtotime($row['tanggal'])); 
                $rekapData[$row['student_id']][$tgl] = [
                    'status' => $row['status'],
                    'menit'  => $row['keterlambatan_menit']
                ];
            }
        }

        $data = [
            'daftarRombel' => $daftarRombel,
            'rombel_id'    => $rombel_id,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'jumlahHari'   => $jumlahHari,
            'siswaKelas'   => $siswaKelas,
            'rekapData'    => $rekapData
        ];

        return view('admin/absensi/rekap', $data);
    }

    // --- TIMPA FUNGSI INI ---
    public function rekapSekolah()
    {
        $db      = \Config\Database::connect();
        $request = \Config\Services::request();

        // Ambil Filter
        $tipe_filter = $request->getGet('tipe_filter') ?? 'bulan';
        $bulan       = $request->getGet('bulan') ?? date('m');
        $semester    = $request->getGet('semester') ?? 'ganjil';
        $tahun       = $request->getGet('tahun') ?? date('Y');

        // Tentukan Array Bulan berdasarkan filter
        if ($tipe_filter === 'semester') {
            if ($semester === 'ganjil') {
                $bulan_array = ['07', '08', '09', '10', '11', '12'];
            } else {
                $bulan_array = ['01', '02', '03', '04', '05', '06'];
            }
            // Jumlahkan total hari efektif di semester tersebut
            $cekHari = $db->table('hari_efektif')
                          ->whereIn('bulan', $bulan_array)
                          ->where('tahun', $tahun)
                          ->selectSum('jumlah_hari')
                          ->get()->getRowArray();
            $hariEfektif = $cekHari['jumlah_hari'] ? (int) $cekHari['jumlah_hari'] : 0;
        } else {
            $bulan_array = [$bulan];
            $cekHari = $db->table('hari_efektif')
                          ->where('bulan', $bulan)
                          ->where('tahun', $tahun)
                          ->get()->getRowArray();
            $hariEfektif = $cekHari ? (int) $cekHari['jumlah_hari'] : 0;
        }

        $daftarRombel = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();

        $rekapSekolah = [];
        $tingkatCounts = [];
        $rekapTingkat  = [];
        
        $totalTargetSekolah = 0;
        $totalSemuaH = 0; $totalSemuaS = 0; $totalSemuaI = 0; $totalSemuaA = 0; $totalSemuaT = 0;

        foreach ($daftarRombel as $rombel) {
            $rombel_id = $rombel['id'];
            
            preg_match('/\d+/', $rombel['rombel_name'], $matches);
            $tingkat = $matches[0] ?? 'Lainnya';
            
            $jumlahSiswa = $db->table('class_rombel_students')->where('rombel_id', $rombel_id)->countAllResults();
            $targetKehadiran = $jumlahSiswa * $hariEfektif;
            $totalTargetSekolah += $targetKehadiran;

            // Hitung Absen berdasarkan Array Bulan (Bisa 1 bulan atau 6 bulan)
            $absen = $db->table('absensi a')
                        ->join('absensi_details ad', 'a.id = ad.absensi_id')
                        ->select('
                            SUM(CASE WHEN ad.status = "H" THEN 1 ELSE 0 END) as total_h,
                            SUM(CASE WHEN ad.status = "S" THEN 1 ELSE 0 END) as total_s,
                            SUM(CASE WHEN ad.status = "I" THEN 1 ELSE 0 END) as total_i,
                            SUM(CASE WHEN ad.status = "A" THEN 1 ELSE 0 END) as total_a,
                            SUM(CASE WHEN ad.keterlambatan_menit > 0 THEN 1 ELSE 0 END) as total_t
                        ')
                        ->where('a.rombel_id', $rombel_id)
                        ->whereIn('MONTH(a.tanggal)', $bulan_array)
                        ->where('YEAR(a.tanggal)', $tahun)
                        ->get()->getRowArray();

            $h = (int)($absen['total_h'] ?? 0);
            $s = (int)($absen['total_s'] ?? 0);
            $i = (int)($absen['total_i'] ?? 0);
            $a = (int)($absen['total_a'] ?? 0);
            $t = (int)($absen['total_t'] ?? 0);

            $totalSemuaH += $h; $totalSemuaS += $s; $totalSemuaI += $i; $totalSemuaA += $a; $totalSemuaT += $t;

            $rekapSekolah[] = [
                'rombel_name' => $rombel['rombel_name'],
                'tingkat'     => $tingkat,
                'persen_h'    => $targetKehadiran > 0 ? ($h / $targetKehadiran) * 100 : 0,
                'persen_s'    => $targetKehadiran > 0 ? ($s / $targetKehadiran) * 100 : 0,
                'persen_i'    => $targetKehadiran > 0 ? ($i / $targetKehadiran) * 100 : 0,
                'persen_a'    => $targetKehadiran > 0 ? ($a / $targetKehadiran) * 100 : 0,
                'persen_t'    => $targetKehadiran > 0 ? ($t / $targetKehadiran) * 100 : 0,
            ];

            if (!isset($tingkatCounts[$tingkat])) {
                $tingkatCounts[$tingkat] = 0;
                $rekapTingkat[$tingkat] = ['h' => 0, 'target' => 0];
            }
            $tingkatCounts[$tingkat]++;
            $rekapTingkat[$tingkat]['h'] += $h;
            $rekapTingkat[$tingkat]['target'] += $targetKehadiran;
        }

        // Ambil data spesifik untuk form berdasarkan bulan filter aktif
        $cekHariForm = $db->table('hari_efektif')->where(['bulan' => $bulan, 'tahun' => $tahun])->get()->getRowArray();
        $hariEfektifForm = $cekHariForm ? (int) $cekHariForm['jumlah_hari'] : '';

        // --- TAMBAHAN BARU: Ambil SEMUA data hari efektif di tahun tersebut untuk JavaScript ---
        $allHariEfektif = $db->table('hari_efektif')->where('tahun', $tahun)->get()->getResultArray();
        $hariEfektifList = [];
        foreach ($allHariEfektif as $h) {
            $hariEfektifList[$h['bulan']] = $h['jumlah_hari'];
        }

        $data = [
            'tipe_filter'     => $tipe_filter,
            'bulan'           => $bulan,
            'semester'        => $semester,
            'tahun'           => $tahun,
            'hariEfektif'     => $hariEfektif,
            'hariEfektifForm' => $hariEfektifForm,
            'hariEfektifList' => $hariEfektifList, // <- Tambahkan variabel list ini
            'rekapSekolah'    => $rekapSekolah,
            'tingkatCounts'   => $tingkatCounts,
            'rekapTingkat'    => $rekapTingkat,
            'avg_h' => $totalTargetSekolah > 0 ? ($totalSemuaH / $totalTargetSekolah) * 100 : 0,
            'avg_s' => $totalTargetSekolah > 0 ? ($totalSemuaS / $totalTargetSekolah) * 100 : 0,
            'avg_i' => $totalTargetSekolah > 0 ? ($totalSemuaI / $totalTargetSekolah) * 100 : 0,
            'avg_a' => $totalTargetSekolah > 0 ? ($totalSemuaA / $totalTargetSekolah) * 100 : 0,
            'avg_t' => $totalTargetSekolah > 0 ? ($totalSemuaT / $totalTargetSekolah) * 100 : 0,
        ];

        return view('admin/absensi/rekap_sekolah', $data);
    }

    // --- TAMBAHKAN FUNGSI BARU INI DI BAWAHNYA ---
    public function setHariEfektif()
    {
        $db = \Config\Database::connect();
        $bulan = $this->request->getPost('bulan');
        $tahun = $this->request->getPost('tahun');
        $hari  = $this->request->getPost('jumlah_hari');

        $builder = $db->table('hari_efektif');
        $cek = $builder->where(['bulan' => $bulan, 'tahun' => $tahun])->get()->getRow();
        
        if ($cek) {
            $builder->where('id', $cek->id)->update(['jumlah_hari' => $hari]);
        } else {
            $builder->insert(['bulan' => $bulan, 'tahun' => $tahun, 'jumlah_hari' => $hari]);
        }
        
        return redirect()->back()->with('success', 'Hari efektif berhasil disimpan.');
    }

    public function getHariEfektif()
    {
        $db = \Config\Database::connect();
        $bulan = $this->request->getGet('bulan');
        $tahun = $this->request->getGet('tahun');
        
        $cek = $db->table('hari_efektif')
                  ->where(['bulan' => $bulan, 'tahun' => $tahun])
                  ->get()->getRowArray();
                  
        return $this->response->setJSON([
            'jumlah_hari' => $cek ? $cek['jumlah_hari'] : ''
        ]);
    }
}

