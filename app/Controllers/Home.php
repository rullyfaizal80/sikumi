<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // 1. Amankan halaman. Jika belum login, paksa seret ke halaman login Shield
        if (!auth()->loggedIn()) {
            return redirect()->to('login');
        }

        $db = \Config\Database::connect();
        $user = auth()->user();

        // 2. Ambil semua kode peran/grup yang dimiliki oleh user ini (Multi-Role Support)
        $userGrupRaw = $db->table('auth_groups_users')
                           ->where('user_id', $user->id)
                           ->get()
                           ->getResultArray();
        
        $myRoles = array_column($userGrupRaw, 'group');

        $menusAllowed = [];
        
        if (!empty($myRoles)) {
            // 3. Cari ID Peran kustom yang cocok dengan nama grup user tersebut
            $roleData = $db->table('custom_roles')
                           ->whereIn('role_name', $myRoles)
                           ->get()
                           ->getResultArray();
            $myRoleIds = array_column($roleData, 'id');

            if (!empty($myRoleIds)) {
                // 4. Tarik semua ID Permission (Menu) yang telah dicentang oleh Admin di halaman matriks
                $permissionData = $db->table('custom_roles_permissions')
                                     ->whereIn('role_id', $myRoleIds)
                                     ->get()
                                     ->getResultArray();
                $myPermissionIds = array_unique(array_column($permissionData, 'permission_id'));

                if (!empty($myPermissionIds)) {
                    // 5. Ambil data menu utuh berdasarkan ID yang diizinkan
                    $menusAllowed = $db->table('custom_permissions')
                                      ->whereIn('id', $myPermissionIds)
                                      ->get()
                                      ->getResultArray();
                }
            }
        }

        // 6. Kelompokkan data menjadi struktur Menu Utama (Parent) dan Sub-Menu (Child)
        $sidebarMenu = [];
        foreach ($menusAllowed as $m) {
            if ($m['parent_id'] === null) {
                // Inisialisasi menu induk
                $sidebarMenu[$m['id']]['induk'] = $m;
                $sidebarMenu[$m['id']]['anak']  = [];
            }
        }

        // Masukkan sub-menu ke induknya masing-masing
        foreach ($menusAllowed as $m) {
            if ($m['parent_id'] !== null && isset($sidebarMenu[$m['parent_id']])) {
                $sidebarMenu[$m['parent_id']]['anak'][] = $m;
            }
        }

        $data = [
            'username'    => $user->username,
            'myRoles'     => $myRoles,
            'sidebarMenu' => $sidebarMenu
        ];

        return view('dashboard_test', $data);
    }
}
