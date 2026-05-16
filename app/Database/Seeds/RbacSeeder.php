<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // 1. Mengisi Data Peran/Role Awal Sekolah
        $roles = [
            [
                'role_name'  => 'waka_kurikulum',
                'role_title' => 'Waka Kurikulum',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'role_name'  => 'guru_pengajar',
                'role_title' => 'Guru Pengajar',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];
        $db->table('custom_roles')->insertBatch($roles);

        // 2. Mengisi Data Hak Akses Menu/Permission Awal
        $permissions = [
            [
                'permission_name'        => 'kaldik.manage',
                'permission_description' => 'Mengatur Kalender Akademik Sekolah',
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ],
            [
                'permission_name'        => 'perangkat.create',
                'permission_description' => 'Membuat Modul dan Perangkat Ajar Guru',
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ],
            [
                'permission_name'        => 'kisi.validate',
                'permission_description' => 'Memvalidasi Kisi-Kisi Soal Asesmen (Waka)',
                'created_at'             => date('Y-m-d H:i:s'),
                'updated_at'             => date('Y-m-d H:i:s'),
            ],
        ];
        $db->table('custom_permissions')->insertBatch($permissions);

        // 3. Menghubungkan Peran dengan Hak Akses (Tabel Jembatan)
        // Waka Kurikulum (ID 1) berhak mengatur Kaldik dan Validasi Kisi-Kisi
        $wakaLinks = [
            ['role_id' => 1, 'permission_id' => 1], // kaldik.manage
            ['role_id' => 1, 'permission_id' => 3], // kisi.validate
        ];
        $db->table('custom_roles_permissions')->insertBatch($wakaLinks);

        // Guru Pengajar (ID 2) berhak membuat Perangkat Ajar
        $guruLinks = [
            ['role_id' => 2, 'permission_id' => 2], // perangkat.create
        ];
        $db->table('custom_roles_permissions')->insertBatch($guruLinks);
    }
}
