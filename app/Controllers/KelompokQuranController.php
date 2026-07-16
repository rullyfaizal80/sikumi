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
    // 2. HALAMAN TAMBAH KELOMPOK & PILIH SISWA (LINTAS KELAS)
    // =========================================================================
    public function create()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data referensi
        $rombels = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        
        $pembimbing = $db->table('users u')
            ->select('u.id, u.username')
            ->join('teacher_profiles tp', 'tp.user_id = u.id')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();
            
        // AMBIL SEMUA SISWA BERSERTA KELASNYA SEKALIGUS
        $students = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username, cr.rombel_name, cr.id as rombel_id')
            ->join('users u', 'u.id = crs.student_id')
            ->join('class_rombel cr', 'cr.id = crs.rombel_id')
            ->orderBy('cr.rombel_name', 'ASC')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // Ambil ID siswa yang SUDAH masuk kelompok Reguler Al-Qur'an
        $terdaftar = $db->table('quran_group_students qgs')
            ->select('qgs.student_id')
            ->join('quran_groups qg', 'qg.id = qgs.group_id')
            ->where('qg.jenis_kelompok', 'Reguler')
            ->get()->getResultArray();

        $siswaRegulerTerdaftar = array_column($terdaftar, 'student_id');

        $data = [
            'title'                 => 'Tambah Kelompok Baru',
            'rombels'               => $rombels,
            'pembimbing'            => $pembimbing,
            'students'              => $students,
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

    // =========================================================================
    // 4. HALAMAN DETAIL KELOMPOK (LIHAT)
    // =========================================================================
    public function show($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');
        
        $db = \Config\Database::connect();
        
        // Ambil data detail kelompok
        $kelompok = $db->table('quran_groups qg')
            ->select('qg.*, u.username as pembimbing')
            ->join('users u', 'u.id = qg.pembimbing_id', 'left')
            ->where('qg.id', $id)
            ->get()->getRowArray();

        // PERBAIKAN: Diubah ke guru/quran_kelompok agar tidak 404
        if (!$kelompok) {
            return redirect()->to(base_url('guru/quran_kelompok'))->with('error', 'Kelompok tidak ditemukan.');
        }

        // Ambil daftar siswa di kelompok tersebut
        $anggota = $db->table('quran_group_students qgs')
            ->select('u.id as student_id, u.username as nama_siswa')
            ->join('users u', 'u.id = qgs.student_id')
            ->where('qgs.group_id', $id)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'    => 'Detail Kelompok',
            'kelompok' => $kelompok,
            'anggota'  => $anggota
        ];

        return view('guru/quran_kelompok/show', $data);
    }

    // =========================================================================
    // 5. HALAMAN EDIT KELOMPOK (Data Grup, Pembimbing, & Anggota - LINTAS KELAS)
    // =========================================================================
    public function edit($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        $kelompok = $db->table('quran_groups')->where('id', $id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to(base_url('guru/quran_kelompok'))->with('error', 'Kelompok tidak ditemukan.');

        // 1. Ambil daftar pembimbing dari relasi teacher_profiles
        $pembimbing = $db->table('users u')
            ->select('u.id, u.username')
            ->join('teacher_profiles tp', 'tp.user_id = u.id')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // 2. Ambil daftar semua Rombel untuk filter dropdown
        $rombels = $db->table('class_rombel')
                      ->orderBy('rombel_name', 'ASC')
                      ->get()->getResultArray();

        // 3. Ambil daftar siswa yang SAAT INI sudah terdaftar di kelompok ini
        $currentStudentsRaw = $db->table('quran_group_students')
            ->where('group_id', $id)
            ->get()->getResultArray();
        $currentStudentIds = array_column($currentStudentsRaw, 'student_id');

        // 4. AMBIL SEMUA SISWA BERSERTA KELASNYA SEKALIGUS
        $students = $db->table('class_rombel_students crs')
                       ->select('u.id as student_id, u.username, cr.rombel_name, cr.id as rombel_id')
                       ->join('users u', 'u.id = crs.student_id')
                       ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                       ->orderBy('cr.rombel_name', 'ASC')
                       ->orderBy('u.username', 'ASC')
                       ->get()->getResultArray();

        // 5. Cari siswa yang sudah terdaftar di kelompok Reguler LAIN (kecuali kelompok ini)
        $siswaRegulerLain = $db->table('quran_group_students qgs')
            ->join('quran_groups qg', 'qg.id = qgs.group_id')
            ->where('qg.jenis_kelompok', 'Reguler')
            ->where('qg.id !=', $id) // Kecualikan kelompok yang sedang di-edit ini
            ->get()->getResultArray();
        $siswaRegulerTerdaftar = array_column($siswaRegulerLain, 'student_id');

        $data = [
            'title'                 => 'Edit Kelompok',
            'kelompok'              => $kelompok,
            'pembimbing'            => $pembimbing,
            'rombels'               => $rombels,
            'students'              => $students,
            'currentStudentIds'     => $currentStudentIds,
            'siswaRegulerTerdaftar' => $siswaRegulerTerdaftar
        ];

        return view('guru/quran_kelompok/edit', $data);
    }

    // =========================================================================
    // 6. PROSES UPDATE DATA KELOMPOK DAN ANGGOTA (SINKRONISASI TOTAL)
    // =========================================================================
    public function update($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $nama_kelompok  = $post['nama_kelompok'];
        $jenis_kelompok = $post['jenis_kelompok'];
        $pembimbing_id  = $post['pembimbing_id'];
        
        // Ambil ID siswa dari seluruh checkbox yang dicentang lintas kelas
        $student_ids    = $post['students'] ?? [];

        // VALIDASI: Jika jenisnya Reguler, pastikan siswa baru tidak terdaftar di Reguler lain
        if ($jenis_kelompok === 'Reguler' && !empty($student_ids)) {
            $cekGanda = $db->table('quran_group_students qgs')
                ->join('quran_groups qg', 'qg.id = qgs.group_id')
                ->where('qg.jenis_kelompok', 'Reguler')
                ->where('qg.id !=', $id) // Abaikan kelompok ini sendiri
                ->whereIn('qgs.student_id', $student_ids)
                ->get()->getResultArray();

            if (count($cekGanda) > 0) {
                return redirect()->back()->withInput()->with('error', 'Gagal memperbarui! Ada siswa yang Anda pilih sudah terdaftar di Kelompok Reguler lain.');
            }
        }

        $db->transStart();

        // 1. Update Informasi Utama Kelompok
        $db->table('quran_groups')->where('id', $id)->update([
            'nama_kelompok'  => $nama_kelompok,
            'jenis_kelompok' => $jenis_kelompok,
            'pembimbing_id'  => $pembimbing_id,
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        // 2. HAPUS SEMUA ANGGOTA LAMA DI KELOMPOK INI
        $db->table('quran_group_students')->where('group_id', $id)->delete();

        // 3. MASUKKAN ULANG SEMUA ANGGOTA YANG DICENTANG (BATCH)
        if (!empty($student_ids)) {
            $dataAnggota = [];
            foreach ($student_ids as $sId) {
                $dataAnggota[] = [
                    'group_id'   => $id,
                    'student_id' => $sId
                ];
            }
            $db->table('quran_group_students')->insertBatch($dataAnggota);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui data kelompok.');
        }

        return redirect()->to(base_url('guru/quran_kelompok'))->with('success', 'Data kelompok dan anggota berhasil diperbarui!');
    }

    // =========================================================================
    // 7. HAPUS KELOMPOK
    // =========================================================================
    public function delete($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // VALIDASI: Cek apakah masih ada siswa yang terdaftar di kelompok ini
        $jumlahSiswa = $db->table('quran_group_students')->where('group_id', $id)->countAllResults();
        
        if ($jumlahSiswa > 0) {
            return redirect()->to(base_url('guru/quran_kelompok'))
                             ->with('error', 'Gagal menghapus! Kelompok ini masih memiliki ' . $jumlahSiswa . ' anggota siswa. Silakan hapus/kosongkan dulu anggotanya melalui menu Edit.');
        }

        // Jika kelompok sudah benar-benar kosong, proses hapus dijalankan
        $db->table('quran_groups')->where('id', $id)->delete();

        return redirect()->to(base_url('guru/quran_kelompok'))->with('success', 'Kelompok berhasil dihapus!');
    }
}