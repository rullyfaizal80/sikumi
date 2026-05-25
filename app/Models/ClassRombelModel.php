<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassRombelModel extends Model
{
    protected $table            = 'class_rombel';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    protected $allowedFields    = [
        'academic_year_id', 
        'master_class_id', 
        'rombel_name', 
        'homeroom_teacher_id'
    ];

    protected $useTimestamps    = true;
}