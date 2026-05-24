<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class DynamicPermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Validasi awal: Pengguna wajib login terlebih dahulu
        if (! auth()->loggedIn()) {
            return redirect()->to('login');
        }

        // Mendapatkan URL Path bersih saat ini (tanpa base_url / index.php)
        $segments = $request->getUri()->getSegments();
        $segments = array_values(array_filter($segments));
        $currentPath = implode('/', $segments);

        // Abaikan pengetatan jika mengakses root url '/' agar tidak terjadi infinite redirect loop
        if (empty($currentPath)) {
            return;
        }

        $db = \Config\Database::connect();

        // =========================================================================
        // LOGIKA POIN 1: Cari nilai url di kolom menu_link tabel custom_permissions
        // =========================================================================
        $permissions = $db->table('custom_permissions')
            ->where('is_active', 1)
            ->where('menu_link !=', '#')
            ->get()
            ->getResultArray();

        // Pengurutan string terpanjang ke terpendek (descending)
        // Diperlukan agar rute spesifik/sub-menu terdeteksi lebih dulu daripada rute induknya
        usort($permissions, function($a, $b) {
            return strlen($b['menu_link']) <=> strlen($a['menu_link']);
        });

        $matchedPermissionId = null;
        foreach ($permissions as $p) {
            $menuLink = trim($p['menu_link'], '/');
            
            // Mendukung kecocokan URL murni maupun rute aksi di bawahnya (misal: admin/users/edit/1)
            if ($currentPath === $menuLink || strpos($currentPath, $menuLink . '/') === 0) {
                $matchedPermissionId = $p['id']; // Simpan sementara nilai ID-nya
                break;
            }
        }

        // JIKALAU TIDAK ADA: Tampilkan 404 otomatis dari sistem
        if ($matchedPermissionId === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // =========================================================================
        // LOGIKA POIN 2: Ambil nilai id di tabel users untuk user yang sedang aktif
        // =========================================================================
        $currentUserId = auth()->id();

        // =========================================================================
        // LOGIKA POIN 3: Cari nilai id dari poin 2 ke tabel auth_groups_users kolom user_id
        // =========================================================================
        $userGroups = $db->table('auth_groups_users')
            ->where('user_id', $currentUserId)
            ->get()
            ->getResultArray();

        // JIKALAU TIDAK ADA nilai yang sama di kolom user_id, tampilkan 403
        if (empty($userGroups)) {
            return $this->reject();
        }

        // Ekstrak seluruh daftar nama group yang dimiliki user ke dalam bentuk array tunggal
        $groups = array_column($userGroups, 'group');

        // Flag penanda untuk status verifikasi hak akses
        $hasAccess = false;

        // =========================================================================
        // PERINTAH LOOPING PROSES (Untuk user dengan 1 group maupun > 2 group)
        // =========================================================================
        foreach ($groups as $groupName) {
            
            // POIN 4: Cari nilai group yang sama dengan nilai role_name dari tabel custom_roles
            $role = $db->table('custom_roles')
                ->where('role_name', $groupName)
                ->get()
                ->getRowArray();

            // Jika nama group ternyata tidak terdaftar di custom_roles, skip/lanjutkan ke group berikutnya
            if (!$role) {
                continue;
            }
            $roleId = $role['id']; // Ambil nilai id nya

            // POIN 5: Cari nilai id dari poin 4 dengan nilai role_id tabel custom_roles_permissions
            $rolePermissions = $db->table('custom_roles_permissions')
                ->where('role_id', $roleId)
                ->get()
                ->getResultArray();

            // Kumpulkan semua daftar permission_id yang diizinkan untuk role ini
            $allowedPermissionIds = array_column($rolePermissions, 'permission_id');

            // Samakan dengan nilai id dari poin 1
            if (in_array($matchedPermissionId, $allowedPermissionIds)) {
                $hasAccess = true; 
                break; // Ada yang sama! Akseskan ke URL tersebut dan HENTIKAN looping group.
            }
        }

        // Jika semua group selesai di-looping dan TIDAK ADA satu pun permission yang cocok, tampilkan 403
        if (!$hasAccess) {
            return $this->reject();
        }
        
        // Jika lolos (hasAccess = true), biarkan framework melanjutkan request ke Controller tujuan
    }

    // Fungsi utilitas untuk merender halaman error 403 Kustom murni secara bersih
    private function reject()
    {
        $response = service('response');
        $response->setStatusCode(403);
        return $response->setBody(view('403_kustom')); 
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}