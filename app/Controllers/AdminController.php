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

        // Tarik semua data hubungan user dan grup dari database secara bersih
        $db = \Config\Database::connect();
        $userGrupRaw = $db->table('auth_groups_users')->get()->getResultArray();

        // Kelompokkan peran berdasarkan user_id agar mudah dibaca di View
        $peranUser = [];
        foreach ($userGrupRaw as $row) {
            $peranUser[$row['user_id']][] = $row['group'];
        }

        $data = [
            'users'      => $daftarUser,
            'roles'      => $daftarRole,
            'peranUser'  => $peranUser // Dikirim sebagai array siap pakai
        ];

        return view('admin/user_management', $data);
    }
}
