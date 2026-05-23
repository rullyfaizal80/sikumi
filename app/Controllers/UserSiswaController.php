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

        // 3. Query khusus mencari user grup 'siswa' + Join Profile Statis
        $builder = $db->table('users u')
                      ->select('u.id, u.username, u.active, u.status, u.created_at, ai.secret as email, 
                                sp.id as student_profile_id, sp.nisn, sp.nis, sp.gender, sp.phone_ortu, sp.birth_place, sp.birth_date')
                      ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                      ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                      ->join('student_profiles sp', 'sp.user_id = u.id', 'left') // LEFT JOIN profil statis
                      ->where('u.deleted_at', null)
                      ->where('agu.group', 'siswa');

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('u.username', $keyword)
                    ->orLike('ai.secret', $keyword)
                    ->orLike('sp.nisn', $keyword)
                    ->orLike('sp.nis', $keyword)
                    ->groupEnd();
        }

        // Hitung total data sebelum limitasi halaman
        $totalBuilder = clone $builder;
        $totalData = $totalBuilder->countAllResults(false);

        // Ambil potongan data untuk halaman aktif
        $daftarSiswa = $builder->orderBy('u.username', 'ASC')
                               ->limit($limit, $offset)
                               ->get()
                               ->getResultArray();

        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        // 4. Ambil opsi data tahun akademik untuk form pendaftaran modal
        $tahunAkademik = $db->table('academic_years')->orderBy('academic_year', 'DESC')->orderBy('semester', 'DESC')->get()->getResultArray();

        // 5. Ambil data riwayat akademik per siswa untuk diletakkan di modal detail
        $riwayatSiswa = [];
        if (!empty($daftarSiswa)) {
            foreach ($daftarSiswa as $siswa) {
                if (!empty($siswa['student_profile_id'])) {
                    $riwayatSiswa[$siswa['id']] = $db->table('student_academic_history sah')
                                                     ->select('sah.id as history_id, sah.class_level, sah.class_room, sah.status, ay.academic_year, ay.semester')
                                                     ->join('academic_years ay', 'ay.id = sah.academic_year_id', 'left')
                                                     ->where('sah.student_profile_id', $siswa['student_profile_id'])
                                                     ->orderBy('ay.academic_year', 'DESC')
                                                     ->orderBy('ay.semester', 'DESC')
                                                     ->get()
                                                     ->getResultArray();
                } else {
                    $riwayatSiswa[$siswa['id']] = [];
                }
            }
        }

        // Send data ke view dengan key variabel asli milik view
        return view('admin/user_siswa_view', [
            'daftarSiswa'    => $daftarSiswa,
            'keyword'        => $keyword,
            'page'           => $page,
            'limit'          => $limit,
            'totalHalaman'   => $totalHalaman,
            'totalData'      => $totalData,
            'tahun_akademik' => $tahunAkademik,
            'riwayat_siswa'  => $riwayatSiswa
        ]);
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

    /**
     * Halaman Gudang Arsip Siswa Terhapus (Trash)
     */
    public function trashSiswa()
    {
        $db = \Config\Database::connect();

        // Mengambil data siswa yang memiliki deleted_at (tidak null)
        $daftarTrash = $db->table('users u')
                          ->select('u.id, u.username, u.deleted_at, ai.secret as email')
                          ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                          ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                          ->where('agu.group', 'siswa')
                          ->where('u.deleted_at !=', null)
                          ->orderBy('u.deleted_at', 'DESC')
                          ->get()
                          ->getResultArray();

        return view('admin/user_siswa_trash_view', ['daftarTrash' => $daftarTrash]);
    }

    /**
     * Mengembalikan akun siswa dari masa Soft Delete
     */
    public function restoreSiswa($id)
    {
        $db = \Config\Database::connect();
        
        $db->table('users')->where('id', $id)->update([
            'deleted_at' => null
        ]);

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Akun siswa berhasil dipulihkan dan aktif kembali!');
    }

/**
     * Memproses Perbaruan Data Profil & Akun Akun Siswa
     */
    public function updateSiswa($id)
    {
        $db = \Config\Database::connect();

        // 1. Ambil input data dari form modal edit
        $username   = $this->request->getPost('username');
        $email      = $this->request->getPost('email');
        $password   = $this->request->getPost('password');
        $nisn       = $this->request->getPost('nisn');
        $nis        = $this->request->getPost('nis');
        $gender     = $this->request->getPost('gender');
        $phone_ortu = $this->request->getPost('phone_ortu');
        $birth_place= $this->request->getPost('birth_place');
        $birth_date = $this->request->getPost('birth_date');

        $db->transStart();

        // 2. Update tabel dasar 'users'
        $db->table('users')->where('id', $id)->update([
            'username'   => $username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 3. Update tabel 'auth_identities' (untuk email)
        $db->table('auth_identities')
           ->where('user_id', $id)
           ->where('type', 'email_password')
           ->update([
               'secret' => $email
           ]);

        // 4. Jika kolom password diisi, lakukan hashing dan perbarui password
        if (!empty($password) && strlen($password) >= 8) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db->table('auth_identities')
               ->where('user_id', $id)
               ->where('type', 'email_password')
               ->update([
                   'secret2' => $hashedPassword
               ]);
        }

        // 5. Cek apakah profil statis siswa sudah ada di database atau belum
        $profileExists = $db->table('student_profiles')->where('user_id', $id)->get()->getRow();

        $profileData = [
            'nisn'        => !empty($nisn) ? $nisn : null,
            'nis'         => !empty($nis) ? $nis : null,
            'gender'      => $gender,
            'phone_ortu'  => !empty($phone_ortu) ? $phone_ortu : null,
            'birth_place' => !empty($birth_place) ? $birth_place : null,
            'birth_date'  => !empty($birth_date) ? $birth_date : null,
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        if ($profileExists) {
            // Jika sudah ada recordnya, lakukan update
            $db->table('student_profiles')->where('user_id', $id)->update($profileData);
        } else {
            // Antisipasi jika data profile belum terbuat sebelumnya, lakukan insert
            $profileData['user_id']    = $id;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            $db->table('student_profiles')->insert($profileData);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Gagal memperbarui data profil siswa.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Profil dan akun siswa berhasil diperbarui.');
    }

    /**
     * 1. Tambah Riwayat Akademik Baru (Plotting Kelas Baru)
     */
    public function addHistorySiswa($userId)
    {
        $db = \Config\Database::connect();
        
        // Pastikan record student_profiles sudah ada untuk mendapatkan student_profile_id
        $profile = $db->table('student_profiles')->where('user_id', $userId)->get()->getRow();
        if (!$profile) {
            $db->table('student_profiles')->insert([
                'user_id'    => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $studentProfileId = $db->insertID();
        } else {
            $studentProfileId = $profile->id;
        }

        $academicYearId = $this->request->getPost('academic_year_id');
        $classLevel     = $this->request->getPost('class_level');
        $classRoom      = $this->request->getPost('class_room');

        // Validasi: Cegah duplikasi plotting pada Tahun Ajaran & Semester yang sama
        $cekDuplikat = $db->table('student_academic_history')
                          ->where('student_profile_id', $studentProfileId)
                          ->where('academic_year_id', $academicYearId)
                          ->get()->getRow();

        if ($cekDuplikat) {
            return redirect()->back()->with('error', '❌ Siswa sudah terdaftar/diplotting pada Tahun Pelajaran tersebut!');
        }

        $db->table('student_academic_history')->insert([
            'student_profile_id' => $studentProfileId,
            'academic_year_id'   => $academicYearId,
            'class_level'        => $classLevel,
            'class_room'         => $classRoom,
            'status'             => 'aktif',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Berhasil melakukan plotting kelas baru untuk siswa.');
    }

    /**
     * 2. Perbarui Baris Riwayat Akademik Siswa (Inline Update)
     */
    public function updateHistorySiswa($historyId)
    {
        $db = \Config\Database::connect();

        $db->table('student_academic_history')->where('id', $historyId)->update([
            'class_level' => $this->request->getPost('class_level'),
            'class_room'  => $this->request->getPost('class_room'),
            'status'      => $this->request->getPost('status'),
            'updated_at'  => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Baris riwayat akademik berhasil diperbarui.');
    }

    /**
     * 3. Hapus Baris Riwayat Akademik Siswa
     */
    public function deleteHistorySiswa($historyId)
    {
        $db = \Config\Database::connect();
        $db->table('student_academic_history')->where('id', $historyId)->delete();

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '🗑️ Baris riwayat akademik berhasil dihapus.');
    }

    /**
     * Hapus Akun Siswa (Smart Delete: Hard vs Soft)
     */
    public function deleteSiswa($id)
    {
        $db = \Config\Database::connect();
        
        // 1. Cek apakah siswa memiliki profil statis
        $profile = $db->table('student_profiles')->where('user_id', $id)->get()->getRow();
        
        // 2. Deteksi keberadaan Riwayat Akademik (Plotting Kelas)
        $hasHistory = false;
        if ($profile) {
            $historyCount = $db->table('student_academic_history')
                               ->where('student_profile_id', $profile->id)
                               ->countAllResults();
            if ($historyCount > 0) {
                $hasHistory = true;
            }
        }

        $db->transStart();

        if ($hasHistory) {
            // EKSEKUSI SOFT DELETE (Karena punya rekam jejak)
            $db->table('users')->where('id', $id)->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'active'     => 0
            ]);
            $pesan = '🗑️ Akun dipindahkan ke Arsip (Soft Delete) karena siswa sudah memiliki rekam jejak kelas historis.';
        } else {
            // EKSEKUSI HARD DELETE (Bersihkan total karena dianggap salah buat/batal)
            if ($profile) {
                $db->table('student_profiles')->where('user_id', $id)->delete();
            }
            $db->table('auth_identities')->where('user_id', $id)->delete();
            $db->table('auth_groups_users')->where('user_id', $id)->delete();
            $db->table('users')->where('id', $id)->delete();
            
            $pesan = '💥 Akun dihapus permanen (Hard Delete) dari sistem karena tidak memiliki riwayat kelas.';
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Terjadi kesalahan saat memproses penghapusan akun.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', $pesan);
    }
}