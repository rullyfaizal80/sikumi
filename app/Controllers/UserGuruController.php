<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UserGuruController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil Parameter Pencarian & Halaman Aktif dari URL
        $keyword = $this->request->getGet('search') ?? '';
        $page    = (int) ($this->request->getGet('page_guru') ?? 1);
        if ($page < 1) $page = 1;

        // 2. TENTUKAN LIMIT DATA PER HALAMAN (Ubah dari 5 menjadi 10)
        $limit = 10; 
        $offset = ($page - 1) * $limit;

        // 3. Buat Query Builder Utama (Sama seperti kemarin, menggunakan LEFT JOIN)
        $builder = $db->table('users u')
                      ->select('u.id, u.username, u.active, u.status, u.created_at, ai.secret as email')
                      ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                      ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
                      ->where('u.deleted_at', null)
                      ->where('(agu.group != "siswa" OR agu.group IS NULL)');

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('u.username', $keyword)
                    ->orLike('ai.secret', $keyword)
                    ->groupEnd();
        }

        // 4. HITUNG TOTAL DATA SEBELUM DIPOTONG (Untuk dasar hitungan tombol halaman)
        $totalBuilder = clone $builder;
        $totalData = $totalBuilder->groupBy('u.id')->countAllResults();

        // 5. AMBIL DATA YANG SUDAH DIPOTONG LIMIT & OFFSET
        $daftarGuru = $builder->groupBy('u.id')
                              ->orderBy('u.username', 'ASC')
                              ->limit($limit, $offset)
                              ->get()
                              ->getResultArray();

        // Hitung total lembar halaman yang tersedia
        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        // 6. Ambil data lencana jabatan (tetap sama)
        $peranUser = [];
        if (!empty($daftarGuru)) {
            $userIds = array_column($daftarGuru, 'id');
            $grupRaw = $db->table('auth_groups_users agu')
                          ->select('agu.user_id, cr.role_title')
                          ->join('custom_roles cr', 'cr.role_name = agu.group')
                          ->whereIn('agu.user_id', $userIds)
                          ->get()
                          ->getResultArray();
            
            foreach ($grupRaw as $row) {
                $peranUser[$row['user_id']][] = $row['role_title'];
            }
        }

        // 7. Kirim semua data perhitungan ke View
        $data = [
            'daftarGuru'   => $daftarGuru,
            'peranUser'    => $peranUser,
            'keyword'      => $keyword,
            'page'         => $page,
            'limit'        => $limit,
            'totalData'    => $totalData,
            'totalHalaman' => $totalHalaman
        ];

        return view('admin/user_guru_view', $data);
    }

    /**
     * FUNGSI SAKLAR: Mengubah status login Guru (Banned / Izinkan)
     */
    public function toggleStatus($id)
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil data status terkini dari user tersebut
        $user = $db->table('users')->where('id', $id)->get()->getRow();
        
        if ($user) {
            // 2. Logika bolak-balik (Jika sudah banned -> kosongkan, jika belum -> set banned)
            $statusBaru = (strtolower($user->status ?? '') === 'banned') ? null : 'banned';
            
            $db->table('users')
               ->where('id', $id)
               ->update([
                   'status'     => $statusBaru,
                   'updated_at' => date('Y-m-d H:i:s')
               ]);
               
            $pesan = ($statusBaru === 'banned') ? '🔒 Akses login guru berhasil dibekukan!' : '✅ Akses login guru telah diizinkan kembali.';
            return redirect()->back()->with('sukses', $pesan);
        }

        return redirect()->back()->with('error', '❌ Pengguna tidak ditemukan.');
    }

    /**
     * FUNGSI PROSES: Menyimpan Akun Login + Profil + Riwayat Tugas Guru (Satu Kesatuan)
     */
    public function storeGuru()
    {
        $db = \Config\Database::connect();

        // 1. Tangkap seluruh kiriman data dari form modal
        $username          = $this->request->getPost('username');
        $email             = $this->request->getPost('email');
        $password          = $this->request->getPost('password');
        $nip               = $this->request->getPost('nip') ?: null;
        $nuptk             = $this->request->getPost('nuptk') ?: null;
        $gender            = $this->request->getPost('gender');
        $academic_year_id  = $this->request->getPost('academic_year_id');
        $assignment_role   = $this->request->getPost('assignment_role');
        $assignment_detail = $this->request->getPost('assignment_detail') ?: null;

        // 2. JALANKAN DATABASE TRANSACTION (Anti-Gagal Setengah-Setengah)
        $db->transStart();

        // A. Simpan ke tabel 'users' (Shield Auth)
        $db->table('users')->insert([
            'username'   => $username,
            'active'     => 1, // Otomatis aktif siap pakai
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $newUserId = $db->insertID(); // Tangkap ID User yang baru terbentuk

        // B. Simpan Kredensial Login ke 'auth_identities'
        $db->table('auth_identities')->insert([
            'user_id'    => $newUserId,
            'type'       => 'email_password',
            'secret'     => $email,
            'secret2'    => password_hash($password, PASSWORD_DEFAULT), // Enkripsi hash aman
            'force_reset'=> 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // C. Kaitkan grup keamanan di 'auth_groups_users' (Bawaan Shield)
        $db->table('auth_groups_users')->insert([
            'user_id'    => $newUserId,
            'group'      => $assignment_role,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // D. Simpan Properti Utama Permanen ke 'teacher_profiles'
        $db->table('teacher_profiles')->insert([
            'user_id'    => $newUserId,
            'nip'        => $nip,
            'nuptk'      => $nuptk,
            'gender'     => $gender,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $newProfileId = $db->insertID(); // Tangkap ID Profil Guru

        // E. Simpan Riwayat Tugas Pertama ke 'teacher_academic_history'
        $db->table('teacher_academic_history')->insert([
            'teacher_profile_id' => $newProfileId,
            'academic_year_id'   => $academic_year_id,
            'assignment_role'    => $assignment_role,
            'assignment_detail'  => $assignment_detail,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ]);

        // 3. TUTUP & VALIDASI TRANSAKSI
        $db->transComplete();

        // Jika ada salah satu query SQL yang gagal/patah, batalkan semuanya otomatis
        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Terjadi kegagalan sistem. Pembuatan akun guru dibatalkan.');
        }

        return redirect()->back()->with('sukses', '✅ Sukses! Akun login, profil, dan rekam jejak tugas pertama ' . $username . ' berhasil diterbitkan.');
    }
}