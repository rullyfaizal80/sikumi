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

// Fungsi untuk menampilkan halaman matriks centang menu
public function permissionMatrix()
{
    $db = \Config\Database::connect();
    
    // Ambil semua role kustom
    $roles = $db->table('custom_roles')->get()->getResultArray();
    
    // Ambil semua menu utama (parent_id adalah null) beserta sub-menunya
    $menusRaw = $db->table('custom_permissions')->get()->getResultArray();
    
    // Kelompokkan menu berdasarkan induknya (parent_id)
    $menus = [];
    foreach ($menusRaw as $m) {
        if ($m['parent_id'] === null) {
            $menus[$m['id']]['induk'] = $m;
        } else {
            $menus[$m['parent_id']]['anak'][] = $m;
        }
    }

    // Ambil semua data centangan yang sudah ada saat ini di tabel jembatan
    $matrixRaw = $db->table('custom_roles_permissions')->get()->getResultArray();
    $matrixActive = [];
    foreach ($matrixRaw as $row) {
        $matrixActive[$row['role_id']][] = $row['permission_id'];
    }

    $data = [
        'roles'        => $roles,
        'menus'        => $menus,
        'matrixActive' => $matrixActive
    ];

    return view('admin/permission_matrix', $data);
}

// Fungsi untuk mengeksekusi penyimpanan hasil centangan baru
public function saveMatrix()
{
    $db = \Config\Database::connect();
    $matrixInput = $this->request->getPost('matrix'); // Mengambil data array [role_id][permission_id]

    // 1. Kosongkan dulu semua hak akses lama demi menghindari data ganda
    $db->table('custom_roles_permissions')->truncate();

    // 2. Jika ada yang dicentang, masukkan secara massal
    if (!empty($matrixInput)) {
        $dataInsert = [];
        foreach ($matrixInput as $roleId => $permissionIds) {
            foreach ($permissionIds as $permId) {
                $dataInsert[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permId
                ];
            }
        }
        $db->table('custom_roles_permissions')->insertBatch($dataInsert);
    }

    return redirect()->to('admin/permission-matrix')->with('sukses', 'Matriks hak akses menu sekolah berhasil diperbarui secara dinamis!');
}

// Fungsi untuk menampilkan daftar Tahun Pelajaran & Semester
public function academicSetting()
{
    $db = \Config\Database::connect();
    
    // Ambil seluruh daftar tahun akademik
    $daftarAkademik = $db->table('academic_years')->orderBy('academic_year', 'ASC')->get()->getResultArray();

    $data = [
        'academic' => $daftarAkademik
    ];

    return view('admin/academic_setting', $data);
}

// Fungsi untuk mengubah status semester aktif secara massal
public function activateAcademic($id)
{
    $db = \Config\Database::connect();

    // 1. Matikan seluruh status aktif tahun pelajaran lama (set is_active = 0)
    $db->table('academic_years')->update(['is_active' => 0]);

    // 2. Aktifkan ID tahun pelajaran yang dipilih oleh pengguna (set is_active = 1)
    $db->table('academic_years')->where('id', $id)->update(['is_active' => 1]);

    return redirect()->to('admin/academic')->with('sukses', 'Tahun Pelajaran & Semester aktif berhasil diperbarui!');
}

public function storeUser()
{
    // 1. Tangkap data input dari form admin
    $username = $this->request->getPost('username');
    $email    = $this->request->getPost('email');
    $password = $this->request->getPost('password'); // Password acak awal dari admin

    // 2. Gunakan entitas User bawaan CodeIgniter Shield
    $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
    $user      = new \CodeIgniter\Shield\Entities\User([
        'username' => $username,
        'email'    => $email,
        'password' => $password,
    ]);

    // 3. Simpan ke database tabel users & auth_identities
    $userModel->save($user);

    // 4. Tarik ID user yang baru saja lahir
    $newUserId = $userModel->getInsertID();

    // 5. Otomatis berikan peran sebagai 'guru_pengajar' secara default
    $db = \Config\Database::connect();
    $db->table('auth_groups_users')->insert([
        'user_id'    => $newUserId,
        'group'      => 'guru_pengajar',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    return redirect()->to('admin/users')->with('sukses', 'Akun Guru baru bernama "' . $username . '" berhasil didaftarkan oleh sistem!');
}

}
