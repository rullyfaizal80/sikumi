<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicCalendarsTable extends Migration
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
            'academic_year_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'class_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Mengunci ke kelas (7, 8, atau 9) sesuai PDF Anda
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Hubungan ke warna (Libur Nasional, Ujian, dll)
            ],
            // REVISI: MENGGUNAKAN TANGGAL MULAI & SELESAI UNTUK RENTANG AGENDA MIMHA
            'start_date' => [
                'type' => 'DATE',
            ],
            'end_date' => [
                'type' => 'DATE', // Jika agenda hanya 1 hari, samakan dengan start_date
            ],
            'event_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
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
        
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('class_id', 'master_classes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'master_categories', 'id', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('academic_calendars');
    }

    public function down()
    {
        $this->forge->dropTable('academic_calendars', true);
    }
}
