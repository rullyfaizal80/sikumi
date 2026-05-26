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

        // 4. Tempelkan Riwayat Rombel Otomatis ke masing-masing Siswa (Dari tabel baru)
        foreach ($daftarSiswa as &$siswa) {
            $siswa['history'] = $db->table('class_rombel_students crs')
                ->select('cr.rombel_name, mc.class_name as tingkat, ay.academic_year, ay.semester')
                ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                ->join('master_classes mc', 'mc.id = cr.master_class_id')
                ->join('academic_years ay', 'ay.id = cr.academic_year_id')
                ->where('crs.student_id', $siswa['id'])
                ->orderBy('ay.id', 'DESC')
                ->get()->getResultArray();
        }
        
        unset($siswa); // Putuskan referensi agar perulangan di View tidak bentrok

        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        // 5. Ambil opsi data tahun akademik untuk kebutuhan form/view
        $tahunAkademik = $db->table('academic_years')->orderBy('academic_year', 'DESC')->orderBy('semester', 'DESC')->get()->getResultArray();

        // ===================================================================
        // KUNCI PERBAIKAN: BLOK NOMOR 5 (PENCARIAN student_academic_history)
        // YANG SEBELUMNYA MEMBUAT ERROR 1146 SEKARANG SUDAH DIHAPUS TOTAL.
        // ===================================================================

        // Send data ke view dengan key variabel asli milik view
        return view('admin/user_siswa_view', [
            'daftarSiswa'    => $daftarSiswa,
            'keyword'        => $keyword,
            'page'           => $page,
            'limit'          => $limit,
            'totalHalaman'   => $totalHalaman,
            'totalData'      => $totalData,
            'tahun_akademik' => $tahunAkademik
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
        
        // 1. Ambil input dari form satu kesatuan (HANYA AKUN & BIODATA DASAR)
        $username    = $this->request->getPost('username');
        $email       = $this->request->getPost('email');
        $password    = $this->request->getPost('password'); // password login
        $nisn        = $this->request->getPost('nisn');
        $nis         = $this->request->getPost('nis');         // BARU
        $gender      = $this->request->getPost('gender');
        $phone_ortu  = $this->request->getPost('phone_ortu');  // BARU
        $birth_place = $this->request->getPost('birth_place'); // BARU
        $birth_date  = $this->request->getPost('birth_date');  // BARU
        
        // Variabel input Tahun Pelajaran dan Kelas SUDAH DIHAPUS dari sini

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
            'user_id'     => $newUserId,
            'nisn'        => !empty($nisn) ? $nisn : null,
            'nis'         => !empty($nis) ? $nis : null,
            'gender'      => $gender,
            'birth_place' => !empty($birth_place) ? $birth_place : null,
            'birth_date'  => !empty($birth_date) ? $birth_date : null,
            'phone_ortu'  => !empty($phone_ortu) ? $phone_ortu : null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ]);
        
        // BAGIAN E (student_academic_history) SEPENUHNYA DIHAPUS

        // 3. SELESAIKAN TRANSAKSI
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Gagal menambahkan akun siswa terjadi kesalahan sistem.');
        }

        // Pesan sukses disesuaikan
        return redirect()->back()->with('sukses', '✅ Akun siswa berhasil dibuat! Siswa berstatus "Siswa Bebas" (Belum masuk kelas).');
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
     * Hapus Akun Siswa (Smart Delete: Hard vs Soft)
     */
    public function deleteSiswa($id)
    {
        $db = \Config\Database::connect();
        
        // 1. Deteksi keberadaan Riwayat Akademik (Plotting Kelas Baru)
        // Cek langsung ke tabel class_rombel_students menggunakan student_id
        $historyCount = $db->table('class_rombel_students')
                           ->where('student_id', $id)
                           ->countAllResults();
                           
        $hasHistory = ($historyCount > 0);

        $db->transStart();

        if ($hasHistory) {
            // EKSEKUSI SOFT DELETE (Karena siswa sudah punya rekam jejak kelas)
            $db->table('users')->where('id', $id)->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'active'     => 0
            ]);
            $pesan = '🗑️ Akun dipindahkan ke Arsip (Soft Delete) karena siswa sudah memiliki rekam jejak rombel.';
        } else {
            // EKSEKUSI HARD DELETE (Bersihkan total karena statusnya Siswa Bebas/salah input)
            
            // Hapus profil statis (jika ada)
            $db->table('student_profiles')->where('user_id', $id)->delete();
            
            // Hapus data autentikasi dan role
            $db->table('auth_identities')->where('user_id', $id)->delete();
            $db->table('auth_groups_users')->where('user_id', $id)->delete();
            
            // Hapus akun utama
            $db->table('users')->where('id', $id)->delete();
            
            $pesan = '💥 Akun dihapus permanen (Hard Delete) dari sistem karena masih berstatus Siswa Bebas.';
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Terjadi kesalahan saat memproses penghapusan akun.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', $pesan);
    }

    /**
     * Halaman Gudang Arsip Siswa Terhapus (Trash)
     */
    public function trashSiswa()
    {
        $db = \Config\Database::connect();

        // Mengambil data siswa yang memiliki deleted_at (tidak null)
        $daftarTrash = $db->table('users u')
                          ->select('u.id, u.username, u.deleted_at, ai.secret as email, sp.nisn, sp.nis')
                          ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                          ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                          ->join('student_profiles sp', 'sp.user_id = u.id', 'left') // Join profile untuk NISN
                          ->where('agu.group', 'siswa')
                          ->where('u.deleted_at !=', null)
                          ->orderBy('u.deleted_at', 'DESC')
                          ->get()
                          ->getResultArray();

        return view('admin/user_siswa_trash_view', ['daftarTrash' => $daftarTrash]);
    }

    /**
     * Mengembalikan akun siswa dari masa Soft Delete (Restore)
     */
    public function restoreSiswa($id)
    {
        $db = \Config\Database::connect();
        
        $db->transStart();

        // Mengembalikan deleted_at menjadi NULL dan mengaktifkan kembali akun ('active' => 1)
        $db->table('users')->where('id', $id)->update([
            'deleted_at' => null,
            'active'     => 1
        ]);

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->to(base_url('admin/users/siswa-trash'))->with('error', '❌ Gagal memulihkan akun siswa.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Akun siswa berhasil dipulihkan! Akun tersebut kini aktif kembali di daftar utama.');
    }

}