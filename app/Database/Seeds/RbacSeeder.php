<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Mengisi Kembali Data Peran/Role Sekolah (Sama seperti kemarin)
        $roles = [
            [
                'id'         => 1,
                'role_name'  => 'waka_kurikulum',
                'role_title' => 'Waka Kurikulum',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'         => 2,
                'role_name'  => 'guru_pengajar',
                'role_title' => 'Guru Pengajar',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'         => 3,
                'role_name'  => 'wali_kelas',
                'role_title' => 'Wali Kelas',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
    'id'         => 4,
    'role_name'  => 'admin',
    'role_title' => 'Super Admin',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
],

        ];
        $db->table('custom_roles')->insertBatch($roles);

        // 2. Mengisi Data Menu Berjenjang (Urutan ID Harus Presisi)
        $permissions = [
            // ==========================================
            // MENU INDUK / UTAMA (parent_id = null)
            // ==========================================
            [
                'id'                     => 1,
                'parent_id'              => null,
                'permission_name'        => 'menu.kaldik',
                'menu_link'              => '#',
                'icon'                   => 'bi bi-calendar3',
                'permission_description' => 'Kalender Akademik',
                'is_active'              => 1,
            ],
            [
                'id'                     => 2,
                'parent_id'              => null,
                'permission_name'        => 'menu.perangkat',
                'menu_link'              => '#',
                'icon'                   => 'bi bi-journal-text',
                'permission_description' => 'Perangkat Ajar',
                'is_active'              => 1,
            ],
            [
                'id'                     => 3,
                'parent_id'              => null,
                'permission_name'        => 'menu.asesmen',
                'menu_link'              => '#',
                'icon'                   => 'bi bi-pencil-square',
                'permission_description' => 'Asesmen & Ujian',
                'is_active'              => 1,
            ],

            // ==========================================
            // SUB-MENU KALDIK (parent_id = 1)
            // ==========================================
            [
                'id'                     => 4,
                'parent_id'              => 1,
                'permission_name'        => 'kaldik.view',
                'menu_link'              => 'kaldik/view',
                'icon'                   => 'bi bi-eye',
                'permission_description' => 'Lihat Kaldik Resmi',
                'is_active'              => 0, // 0 = Masih dikembangkan (akan muncul alert)
            ],
            [
                'id'                     => 5,
                'parent_id'              => 1,
                'permission_name'        => 'kaldik.manage',
                'menu_link'              => 'kaldik/manage',
                'icon'                   => 'bi bi-gear',
                'permission_description' => 'Kelola Kaldik Sekolah',
                'is_active'              => 0,
            ],

            // ==========================================
            // SUB-MENU PERANGKAT AJAR (parent_id = 2)
            // ==========================================
            [
                'id'                     => 6,
                'parent_id'              => 2,
                'permission_name'        => 'perangkat.cp',
                'menu_link'              => 'perangkat/cp',
                'icon'                   => 'bi bi-file-earmark-text',
                'permission_description' => 'Capaian Pembelajaran (CP)',
                'is_active'              => 0,
            ],
            [
                'id'                     => 7,
                'parent_id'              => 2,
                'permission_name'        => 'perangkat.atp',
                'menu_link'              => 'perangkat/atp',
                'icon'                   => 'bi bi-bezier2',
                'permission_description' => 'Alur Tujuan Pembelajaran (ATP)',
                'is_active'              => 0,
            ],
            [
                'id'                     => 8,
                'parent_id'              => 2,
                'permission_name'        => 'perangkat.modul',
                'menu_link'              => 'perangkat/modul',
                'icon'                   => 'bi bi-robot', // Ikon bernuansa AI
                'permission_description' => 'Modul Ajar (Asisten AI)',
                'is_active'              => 0,
            ],

            // ==========================================
            // SUB-MENU ASESMEN & CBT (parent_id = 3)
            // ==========================================
            [
                'id'                     => 9,
                'parent_id'              => 3,
                'permission_name'        => 'kisi.validate',
                'menu_link'              => 'kisi/validate',
                'icon'                   => 'bi bi-shield-check',
                'permission_description' => 'Validasi Kisi-Kisi AI',
                'is_active'              => 0,
            ],
            [
                'id'                     => 10,
                'parent_id'              => 3,
                'permission_name'        => 'cbt.test',
                'menu_link'              => 'cbt/test',
                'icon'                   => 'bi bi-laptop',
                'permission_description' => 'Ujian Online CBT',
                'is_active'              => 0,
            ],
            // Tambahkan ini di baris paling akhir array $permissions di dalam RbacSeeder.php
[
    'id'                     => 11,
    'parent_id'              => null,
    'permission_name'        => 'menu.admin',
    'menu_link'              => '#',
    'icon'                   => 'bi bi-gear-fill',
    'permission_description' => 'Menu Admin',
    'is_active'              => 1,
],
[
    'id'                     => 12,
    'parent_id'              => 11,
    'permission_name'        => 'admin.users',
    'menu_link'              => 'admin/users',
    'icon'                   => 'bi bi-people-fill',
    'permission_description' => 'Manajemen Pengguna',
    'is_active'              => 1, // Kita buat 1 karena fiturnya sudah selesai dibuat
],
[
    'id'                     => 13,
    'parent_id'              => 11,
    'permission_name'        => 'admin.matrix',
    'menu_link'              => 'admin/permission-matrix',
    'icon'                   => 'bi bi-grid-3x3-gap-fill',
    'permission_description' => 'Matriks Hak Akses',
    'is_active'              => 1, // Kita buat 1 karena fiturnya sudah selesai dibuat
],

        ];
        $db->table('custom_permissions')->insertBatch($permissions);

        // 3. Menghubungkan Peran dengan Banyak Menu Utama & Sub-Menunya
        // Waka Kurikulum (ID 1) punya akses ke Kaldik & Validasi Kisi-Kisi
        $wakaLinks = [
            ['role_id' => 1, 'permission_id' => 1], // Induk Kaldik
            ['role_id' => 1, 'permission_id' => 4], // Lihat Kaldik
            ['role_id' => 1, 'permission_id' => 5], // Kelola Kaldik
            ['role_id' => 1, 'permission_id' => 3], // Induk Asesmen
            ['role_id' => 1, 'permission_id' => 9], // Validasi Kisi-Kisi
        ];
        $db->table('custom_roles_permissions')->insertBatch($wakaLinks);

        // Guru Pengajar (ID 2) punya akses ke Perangkat Ajar & Lihat Kaldik
        $guruLinks = [
            ['role_id' => 2, 'permission_id' => 1], // Induk Kaldik
            ['role_id' => 2, 'permission_id' => 4], // Lihat Kaldik
            ['role_id' => 2, 'permission_id' => 2], // Induk Perangkat
            ['role_id' => 2, 'permission_id' => 6], // CP
            ['role_id' => 2, 'permission_id' => 7], // ATP
            ['role_id' => 2, 'permission_id' => 8], // Modul Ajar
        ];
        $db->table('custom_roles_permissions')->insertBatch($guruLinks);

        // Tambahkan ini di baris paling bawah fungsi run() sebelum penutup
$adminLinks = [
    ['role_id' => 4, 'permission_id' => 11], // Induk Menu Admin
    ['role_id' => 4, 'permission_id' => 12], // Sub Manajemen Pengguna
    ['role_id' => 4, 'permission_id' => 13], // Sub Matriks Hak Akses
];
$db->table('custom_roles_permissions')->insertBatch($adminLinks);

    }
}
