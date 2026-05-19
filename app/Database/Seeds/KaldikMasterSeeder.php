<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KaldikMasterSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // A. Suntik Data Master Kelas MTs (7, 8, 9 - Fase D)
        $db->table('master_classes')->truncate();
        $db->table('master_classes')->insertBatch([
            ['class_name' => '7', 'level_type' => 'MTs', 'curriculum_phase' => 'Fase D'],
            ['class_name' => '8', 'level_type' => 'MTs', 'curriculum_phase' => 'Fase D'],
            ['class_name' => '9', 'level_type' => 'MTs', 'curriculum_phase' => 'Fase D'],
        ]);

        // B. Suntik Data Sistem Kerja Efektif Sekolah (Contoh: 5 Hari Kerja)
        $db->table('master_workdays')->truncate();
        $db->table('master_workdays')->insertBatch([
            ['day_name' => 'Senin',  'is_workday' => 1],
            ['day_name' => 'Selasa', 'is_workday' => 1],
            ['day_name' => 'Rabu',   'is_workday' => 1],
            ['day_name' => 'Kamis',  'is_workday' => 1],
            ['day_name' => 'Jumat',  'is_workday' => 1],
            ['day_name' => 'Sabtu',  'is_workday' => 0], // 0 = Otomatis Libur Mingguan
            ['day_name' => 'Minggu', 'is_workday' => 0], // 0 = Otomatis Libur Mingguan
        ]);

        // C. Suntik Data Master Kategori Kriteria Waktu Kurikulum
        $db->table('master_categories')->truncate();
        $db->table('master_categories')->insertBatch([
            ['category_name' => 'Hari Efektif Belajar', 'color_hex' => '#ffffff', 'count_as_effective' => 1],
            ['category_name' => 'Libur Nasional',       'color_hex' => '#dc3545', 'count_as_effective' => 0], // Merah
            ['category_name' => 'Libur Khusus MIMHa',   'color_hex' => '#198754', 'count_as_effective' => 0], // Hijau
            ['category_name' => 'Asesmen/Ujian',        'color_hex' => '#ffc107', 'count_as_effective' => 0], // Kuning
            ['category_name' => 'Kegiatan Sekolah',     'color_hex' => '#0dcaf0', 'count_as_effective' => 1], // Biru Muda
        ]);
    }
}
