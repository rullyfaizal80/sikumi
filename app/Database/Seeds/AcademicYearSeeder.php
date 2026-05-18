<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Bersihkan tabel terlebih dahulu agar tidak duplikat saat diuji coba ulang
        $db->table('academic_years')->truncate();

        $data = [
            [
                'academic_year' => '2025/2026',
                'semester'      => 'Ganjil',
                'is_active'     => 0, // Masa lalu / arsip
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year' => '2025/2026',
                'semester'      => 'Genap',
                'is_active'     => 1, // Semester yang sedang berjalan saat ini
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'academic_year' => '2026/2027',
                'semester'      => 'Ganjil',
                'is_active'     => 0, // Masa depan / belum berjalan
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];

        // Suntikkan data secara massal ke dalam database
        $db->table('academic_years')->insertBatch($data);
    }
}
