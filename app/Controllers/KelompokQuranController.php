<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class KelompokQuranController extends BaseController
{
    // =========================================================================
    // 1. HALAMAN DAFTAR KELOMPOK
    // =========================================================================
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');
        
        $db = \Config\Database::connect();
        
        // Ambil daftar kelompok beserta nama pembimbing dan jumlah anggotanya
        $kelompok = $db->table('quran_groups qg')
            ->select('qg.id, qg.nama_kelompok, qg.jenis_kelompok, u.username as pembimbing, COUNT(qgs.student_id) as jumlah_siswa')
            ->join('users u', 'u.id = qg.pembimbing_id', 'left')
            ->join('quran_group_students qgs', 'qgs.group_id = qg.id', 'left')
            ->groupBy('qg.id')
            ->orderBy('qg.nama_kelompok', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'    => 'Manajemen Kelompok Al-Qur\'an',
            'kelompok' => $kelompok
        ];

        return view('guru/quran_kelompok/index', $data);
    }

    // =========================================================================
    // 2. HALAMAN TAMBAH KELOMPOK & PILIH SISWA
    // =========================================================================
    public function create()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        
        $rombel_id = $request->getGet('rombel_id');

        // Ambil data referensi
        $rombels = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        // Ambil data guru dengan men-join tabel users ke teacher_profiles
$pembimbing = $db->table('users u')
    ->select('u.id, u.username')
    ->join('teacher_profiles tp', 'tp.user_id = u.id')
    ->orderBy('u.username', 'ASC')
    ->get()->getResultArray();
        $students = [];
        $siswaRegulerTerdaftar = [];

        // Jika user sudah memilih Rombel, tampilkan daftar siswanya
        if ($rombel_id) {
            $students = $db->table('class_rombel_students crs')
                ->select('u.id as student_id, u.username')
                ->join('users u', 'u.id = crs.student_id')
                ->where('crs.rombel_id', $rombel_id)
                ->orderBy('u.username', 'ASC')
                ->get()->getResultArray();

            // Ambil ID siswa di kelas ini yang SUDAH masuk kelompok Reguler
            $terdaftar = $db->table('quran_group_students qgs')
                ->select('qgs.student_id')
                ->join('quran_groups qg', 'qg.id = qgs.group_id')
                ->where('qg.jenis_kelompok', 'Reguler')
                ->get()->getResultArray();

            $siswaRegulerTerdaftar = array_column($terdaftar, 'student_id');
        }

        $data = [
            'title'                 => 'Tambah Kelompok Baru',
            'rombels'               => $rombels,
            'pembimbing'            => $pembimbing,
            'students'              => $students,
            'rombel_id'             => $rombel_id,
            'siswaRegulerTerdaftar' => $siswaRegulerTerdaftar
        ];

        return view('guru/quran_kelompok/create', $data);
    }

    // =========================================================================
    // 3. PROSES SIMPAN KELOMPOK BARU
    // =========================================================================
    public function store()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $nama_kelompok  = $post['nama_kelompok'];
        $jenis_kelompok = $post['jenis_kelompok'];
        $pembimbing_id  = $post['pembimbing_id'];
        $student_ids    = $post['students'] ?? [];

        // VALIDASI: Jika Reguler, pastikan siswa tidak terdaftar di Reguler lain
        if ($jenis_kelompok === 'Reguler' && !empty($student_ids)) {
            $cekGanda = $db->table('quran_group_students qgs')
                ->join('quran_groups qg', 'qg.id = qgs.group_id')
                ->where('qg.jenis_kelompok', 'Reguler')
                ->whereIn('qgs.student_id', $student_ids)
                ->get()->getResultArray();

            if (count($cekGanda) > 0) {
                return redirect()->back()->withInput()->with('error', 'Gagal! Ada siswa yang Anda pilih sudah terdaftar di Kelompok Reguler lain.');
            }
        }

        $db->transStart();

        // 1. Simpan Header Kelompok
        $db->table('quran_groups')->insert([
            'nama_kelompok'  => $nama_kelompok,
            'jenis_kelompok' => $jenis_kelompok,
            'pembimbing_id'  => $pembimbing_id
        ]);
        $group_id = $db->insertID();

        // 2. Simpan Anggota (Siswa)
        if (!empty($student_ids)) {
            $dataAnggota = [];
            foreach ($student_ids as $sId) {
                $dataAnggota[] = [
                    'group_id'   => $group_id,
                    'student_id' => $sId
                ];
            }
            $db->table('quran_group_students')->insertBatch($dataAnggota);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }

        return redirect()->to(base_url('guru/quran_kelompok'))->with('success', 'Kelompok berhasil dibuat!');
    }
}