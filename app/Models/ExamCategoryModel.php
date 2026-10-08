<?php

namespace App\Models;
use CodeIgniter\Model;

class ExamCategoryModel extends Model
{
    protected $table = 'exam_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['nama_kategori'];
    protected $useTimestamps = false; 
}