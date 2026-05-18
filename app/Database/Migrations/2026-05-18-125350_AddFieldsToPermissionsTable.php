<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToPermissionsTable extends Migration
{
    public function up()
    {
        $fields = [
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null, // NULL berarti ini Menu Utama / Induk
                'after'      => 'id'
            ],
            'menu_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '#',
                'after'      => 'permission_name'
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'bi bi-circle',
                'after'      => 'menu_link'
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0, // 0 = Masih dikembangkan, 1 = Sudah aktif
                'after'      => 'permission_description'
            ],
        ];
        
        // Suntikkan kolom secara aman ke tabel custom_permissions yang sudah ada isinya
        $this->forge->addColumn('custom_permissions', $fields);
    }

    public function down()
    {
        // Fungsi pemulihan jika tombol rollback ditekan kembali
        $this->forge->dropColumn('custom_permissions', ['parent_id', 'menu_link', 'icon', 'is_active']);
    }
}
