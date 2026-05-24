<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterSubjectModel extends Model
{
    protected $table            = 'master_subjects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    // Kolom yang diizinkan untuk diisi/diubah melalui aplikasi
    protected $allowedFields    = [
        'subject_code', 
        'subject_name', 
        'subject_group'
    ];

    // Fitur otomatis mengisi kolom created_at dan updated_at
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}