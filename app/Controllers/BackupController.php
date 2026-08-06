<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class BackupController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // Ambil semua nama tabel di dalam database
        $tables = $db->listTables();
        
        $sql = "-- =====================================================\n";
        $sql .= "-- Backup Database SiKuMi\n";
        $sql .= "-- Waktu Pembuatan: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- =====================================================\n\n";

        // Nonaktifkan foreign key checks agar saat restore tidak error
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // 1. Ambil struktur tabel (CREATE TABLE)
            $query = $db->query("SHOW CREATE TABLE `$table`")->getRowArray();
            $sql .= "-- Struktur untuk tabel `$table`\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $query['Create Table'] . ";\n\n";

            // 2. Ambil isi data dari tabel (INSERT INTO)
            $rows = $db->query("SELECT * FROM `$table`")->getResultArray();
            
            if (!empty($rows)) {
                $sql .= "-- Data untuk tabel `$table`\n";
                foreach ($rows as $row) {
                    $sql .= "INSERT INTO `$table` VALUES (";
                    $values = [];
                    
                    foreach ($row as $val) {
                        if (is_null($val)) {
                            $values[] = "NULL";
                        } else {
                            // Escape data agar aman dari karakter khusus
                            $values[] = $db->escape($val);
                        }
                    }
                    $sql .= implode(", ", $values) . ");\n";
                }
                $sql .= "\n\n";
            }
        }

        // Kembalikan foreign key checks
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        // 3. Proses Download File SQL
        $filename = 'backup_sikumi_' . date('Y-m-d_H-i-s') . '.sql';
        
        return $this->response->download($filename, $sql);
    }
}