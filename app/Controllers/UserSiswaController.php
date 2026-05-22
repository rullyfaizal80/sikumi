<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UserSiswaController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil Parameter Pencarian & Halaman Aktif dari URL
        $keyword = $this->request->getGet('search') ?? '';
        $page    = (int) ($this->request->getGet('page_siswa') ?? 1);
        if ($page < 1) $page = 1;

        // 2. Batasi 10 baris per halaman agar jendela padat
        $limit = 10; 
        $offset = ($page - 1) * $limit;

        // 3. Query khusus mencari user yang tergabung dalam grup 'siswa'
        $builder = $db->table('users u')
                      ->select('u.id, u.username, u.active, u.status, u.created_at, ai.secret as email')
                      ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                      ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner') // Inner join karena siswa wajib punya grup 'siswa'
                      ->where('u.deleted_at', null)
                      ->where('agu.group', 'siswa'); // KUNCI: Hanya grup siswa

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('u.username', $keyword)
                    ->orLike('ai.secret', $keyword)
                    ->groupEnd();
        }

        // 4. Hitung Total Data Siswa sebelum dipotong limit
        $totalBuilder = clone $builder;
        $totalData = $totalBuilder->countAllResults();

        // 5. Ambil potongan data untuk halaman aktif
        $daftarSiswa = $builder->orderBy('u.username', 'ASC')
                               ->limit($limit, $offset)
                               ->get()
                               ->getResultArray();

        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        $data = [
            'daftarSiswa'  => $daftarSiswa,
            'keyword'      => $keyword,
            'page'         => $page,
            'limit'        => $limit,
            'totalData'    => $totalData,
            'totalHalaman' => $totalHalaman
        ];

        return view('admin/user_siswa_view', $data);
    }

    /**
     * FUNGSI SAKLAR: Mengubah status login Siswa (Banned / Izinkan)
     */
    public function toggleStatus($id)
    {
        $db = \Config\Database::connect();
        
        $user = $db->table('users')->where('id', $id)->get()->getRow();
        
        if ($user) {
            $statusBaru = (strtolower($user->status ?? '') === 'banned') ? null : 'banned';
            
            $db->table('users')
               ->where('id', $id)
               ->update([
                   'status'     => $statusBaru,
                   'updated_at' => date('Y-m-d H:i:s')
               ]);
               
            $pesan = ($statusBaru === 'banned') ? '🔒 Akses login siswa berhasil dibekukan!' : '✅ Akses login siswa telah diizinkan kembali.';
            return redirect()->back()->with('sukses', $pesan);
        }

        return redirect()->back()->with('error', '❌ Siswa tidak ditemukan.');
    }

    public function storeSiswa()
{
    $db = \Config\Database::connect();
    
    // 1. Ambil input dari form satu kesatuan
    $username   = $this->request->getPost('username');
    $email      = $this->request->getPost('email');
    $password   = $this->request->getPost('password'); // password login
    $nisn       = $this->request->getPost('nisn');
    $gender     = $this->request->getPost('gender');
    
    // Data Dinamis Semester/Kelas Saat Ini
    $academic_year_id = $this->request->getPost('academic_year_id'); // Tahun Pelajaran Aktif saat input
    $class_level      = $this->request->getPost('class_level');      // Kelas 7
    $class_room       = $this->request->getPost('class_room');       // Ruang A

    // 2. MULAI PROSES TRANSAKSI SATU KESATUAN
    $db->transStart();

    // A. Masukkan ke tabel 'users' (Shield Auth)
    $db->table('users')->insert([
        'username'   => $username,
        'active'     => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    $newUserId = $db->insertID(); // Ambil ID User yang baru saja tercipta

    // B. Masukkan Kredensial Email & Password ke 'auth_identities'
    $db->table('auth_identities')->insert([
        'user_id'    => $newUserId,
        'type'       => 'email_password',
        'secret'     => $email,
        'secret2'    => password_hash($password, PASSWORD_DEFAULT), // Enkripsi aman
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // C. Ikat hak akses sebagai 'siswa' di 'auth_groups_users'
    $db->table('auth_groups_users')->insert([
        'user_id'    => $newUserId,
        'group'      => 'siswa',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // D. Masukkan Properti Induk Statis ke 'student_profiles'
    $db->table('student_profiles')->insert([
        'user_id'    => $newUserId,
        'nisn'       => $nisn,
        'gender'     => $gender,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    $newProfileId = $db->insertID();

    // E. Masukkan Properti Dinamis (Tahun Akademik & Kelas Pertama Siswa)
    $db->table('student_academic_history')->insert([
        'student_profile_id' => $newProfileId,
        'academic_year_id'   => $academic_year_id,
        'class_level'        => $class_level,
        'class_room'         => $class_room,
        'status'             => 'aktif',
        'created_at'         => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // 3. SELESAIKAN TRANSAKSI
    $db->transComplete();

    if ($db->transStatus() === FALSE) {
        return redirect()->back()->with('error', '❌ Gagal menambahkan akun siswa terjadi kesalahan sistem.');
    }

    return redirect()->back()->with('sukses', '✅ Akun siswa dan data riwayat kelas berhasil diterbitkan!');
}
}