<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\CustomRoleModel;

class AdminController extends BaseController
{
    public function index()
{
    $userModel = new UserModel();
    $daftarUser = $userModel->findAll();

    $roleModel = new CustomRoleModel();
    $daftarRole = $roleModel->findAll();

    // KODE BARU: Melakukan JOIN agar mendapatkan role_title (Nama Resmi) dari database
    $db = \Config\Database::connect();
    $builder = $db->table('auth_groups_users agu');
    $builder->select('agu.user_id, cr.role_title');
    $builder->join('custom_roles cr', 'cr.role_name = agu.group');
    $userGrupRaw = $builder->get()->getResultArray();

    // Kelompokkan Nama Resmi Peran berdasarkan ID Pengguna
    $peranUser = [];
    foreach ($userGrupRaw as $row) {
        $peranUser[$row['user_id']][] = $row['role_title']; // Menggunakan role_title, bukan group
    }

    $data = [
        'users'      => $daftarUser,
        'roles'      => $daftarRole, // Mengirimkan seluruh daftar peran yang ada di sistem
        'peranUser'  => $peranUser
    ];

    return view('admin/user_management', $data);
}

    // Tambahkan fungsi ini di dalam file app/Controllers/AdminController.php
public function storeRole()
{
    // 1. Tangkap data input dari form visual
    $roleName  = $this->request->getPost('role_name');
    $roleTitle = $this->request->getPost('role_title');

    // 2. Masukkan data ke dalam tabel custom_roles menggunakan model kustom kita
    $roleModel = new \App\Models\CustomRoleModel();
    $roleModel->insert([
        'role_name'  => $roleName,
        'role_title' => $roleTitle,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // 3. Kembalikan ke halaman admin sambil membawa pesan sukses
    return redirect()->to('admin/users')->with('sukses', 'Peran jabatan baru "' . $roleTitle . '" berhasil disimpan ke database kustom!');
}

// Tambahkan fungsi pengelola multi-jabatan ini ke dalam app/Controllers/AdminController.php
public function updateUserRoles()
{
    $userId = $this->request->getPost('user_id');
    $roles  = $this->request->getPost('roles'); // Menangkap array banyak centangan jabatan

    $db = \Config\Database::connect();
    
    // 1. Bersihkan semua jabatan lama guru tersebut di tabel jembatan Shield
    $db->table('auth_groups_users')->where('user_id', $userId)->delete();

    // 2. Jika ada jabatan baru yang dicentang, masukkan semuanya secara massal (Batch)
    if (!empty($roles)) {
        $dataInsert = [];
        foreach ($roles as $role) {
            $dataInsert[] = [
                'user_id'    => $userId,
                'group'      => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        $db->table('auth_groups_users')->insertBatch($dataInsert);
    }

    // 3. Kembalikan ke halaman admin dengan membawa pesan sukses
    return redirect()->to('admin/users')->with('sukses', 'Daftar jabatan aktif guru berhasil diperbarui secara dinamis!');
}


}
