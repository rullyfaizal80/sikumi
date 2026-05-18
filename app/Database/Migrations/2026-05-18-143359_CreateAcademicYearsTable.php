<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicYearsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'academic_year' => [
                'type'       => 'VARCHAR',
                'constraint' => 9, // Format contoh: "2025/2026"
            ],
            'semester' => [
                'type'       => 'ENUM',
                'constraint' => ['Ganjil', 'Genap'],
                'default'    => 'Ganjil',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0, // 1 = Aktif saat ini, 0 = Tidak aktif (arsip/masa lalu)
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->createTable('academic_years');
    }

    public function down()
    {
        $this->forge->dropTable('academic_years', true);
    }
}
