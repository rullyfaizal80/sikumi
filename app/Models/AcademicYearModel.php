<?php

namespace App\Models;
use CodeIgniter\Model;

class AcademicYearModel extends Model
{
    protected $table = 'academic_years';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['academic_year', 'semester', 'is_active'];

    // Ambil tahun ajaran yang sedang aktif (is_active = 1)
    public function getActiveYear()
    {
        return $this->where('is_active', 1)->first();
    }
}