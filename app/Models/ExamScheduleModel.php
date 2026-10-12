<?php

namespace App\Models;
use CodeIgniter\Model;

class ExamScheduleModel extends Model
{
    protected $table            = 'exam_schedules';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    
    // PERBAIKAN: 'token' sudah dihapus dari array di bawah ini
    protected $allowedFields = [
        'academic_year_id', 'exam_category_id', 'rombel_id', 'subject_id', 
        'link_gform', 'waktu_mulai', 'waktu_selesai', 'durasi_menit', 'is_active'
    ];
    
    protected $useTimestamps    = true; 

    // Update query Join & Pengurutan Multi-Level
    public function getJadwalAdminByTahun($academic_year_id)
    {
        return $this->select('exam_schedules.*, class_rombel.rombel_name, master_subjects.subject_name, exam_categories.nama_kategori')
                    ->join('class_rombel', 'class_rombel.id = exam_schedules.rombel_id', 'left')
                    ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                    ->join('exam_categories', 'exam_categories.id = exam_schedules.exam_category_id', 'left')
                    ->where('exam_schedules.academic_year_id', $academic_year_id)
                    
                    // 1. Urutkan berdasarkan Kategori (Misal: PAS kumpul dengan PAS)
                    ->orderBy('exam_categories.nama_kategori', 'ASC')
                    
                    // 3. Terakhir, urutkan berdasarkan waktu mulai paling lama
                    ->orderBy('exam_schedules.waktu_mulai', 'ASC')

                    // 2. Urutkan berdasarkan Kelas (Terkecil ke Terbesar: 7, 7A, 7B, 8, 9A)
                    ->orderBy('class_rombel.rombel_name', 'ASC')
                    
                    ->findAll();
    }
}