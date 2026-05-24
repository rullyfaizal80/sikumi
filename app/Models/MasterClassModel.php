<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterClassModel extends Model
{
    protected $table            = 'master_classes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    // Kolom yang diizinkan untuk diisi/diubah melalui aplikasi
    protected $allowedFields    = [
        'class_name', 
        'level_type', 
        'curriculum_phase'
    ];

    // Karena di tabel aslinya tidak ada created_at/updated_at, kita matikan fiturnya
    protected $useTimestamps    = false; 
}