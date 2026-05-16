<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDynamicRbacTables extends Migration
{
    public function up()
    {
        // 1. Pembuatan Tabel custom_roles
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'role_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
        $this->forge->addUniqueKey('role_name');
        $this->forge->createTable('custom_roles');

        // 2. Pembuatan Tabel custom_permissions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'permission_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'permission_description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addUniqueKey('permission_name');
        $this->forge->createTable('custom_permissions');

        // 3. Pembuatan Tabel Jembatan custom_roles_permissions
        $this->forge->addField([
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey(['role_id', 'permission_id'], true);
        // Menambahkan Relasi Foreign Key demi menjaga keutuhan data sekolah
        $this->forge->addForeignKey('role_id', 'custom_roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'custom_permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('custom_roles_permissions');
    }

    public function down()
    {
        // Menghapus tabel dengan urutan terbalik untuk menghindari kendala kekuncian data (foreign key error)
        $this->forge->dropTable('custom_roles_permissions', true);
        $this->forge->dropTable('custom_permissions', true);
        $this->forge->dropTable('custom_roles', true);
    }
}
