<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassSubjectTeacherModel extends Model
{
    protected $table            = 'class_subject_teachers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    protected $allowedFields    = [
        'rombel_id', 
        'master_subject_id', 
        'teacher_id'
    ];

    protected $useTimestamps    = true;
}