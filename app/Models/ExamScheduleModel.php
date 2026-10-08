<?php

namespace App\Models;
use CodeIgniter\Model;

class ExamScheduleModel extends Model
{
    protected $table            = 'exam_schedules';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    
    protected $allowedFields = [
        'academic_year_id', 'exam_category_id', 'rombel_id', 'subject_id', 
        'link_gform', 'waktu_mulai', 'waktu_selesai', 'durasi_menit', 'token', 'is_active'
    ];
    protected $useTimestamps    = true; 

    // Update query Join
    public function getJadwalAdminByTahun($academic_year_id)
    {
        return $this->select('exam_schedules.*, class_rombel.rombel_name, master_subjects.subject_name, exam_categories.nama_kategori')
                    ->join('class_rombel', 'class_rombel.id = exam_schedules.rombel_id', 'left')
                    ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                    ->join('exam_categories', 'exam_categories.id = exam_schedules.exam_category_id', 'left')
                    ->where('exam_schedules.academic_year_id', $academic_year_id)
                    ->orderBy('exam_schedules.waktu_mulai', 'DESC')
                    ->findAll();
    }
}