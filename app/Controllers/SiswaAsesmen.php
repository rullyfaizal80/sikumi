<?php

namespace App\Controllers;
use App\Models\ExamScheduleModel;
use App\Models\AcademicYearModel;

class SiswaAsesmen extends BaseController
{
    protected $examModel;
    protected $tahunModel;

    public function __construct()
    {
        $this->examModel = new ExamScheduleModel();
        $this->tahunModel = new AcademicYearModel();
    }

    public function index()
    {
        // 1. Ambil ID User Siswa yang sedang login (sesuaikan dengan session auth Anda)
       // Coba gunakan fungsi auth() dari CI4 Shield atau user_id() dari Myth:Auth
if (function_exists('auth')) {
    $student_id = auth()->id(); // Untuk CI4 Shield
} elseif (function_exists('user_id')) {
    $student_id = user_id(); // Untuk Myth:Auth
} else {
    // Jika custom session
    $student_id = session()->get('id') ?? session()->get('user_id'); 
}

// --- BARIS DEBUGGING (HAPUS/KOMENTARI JIKA SUDAH BERHASIL) ---
// dd('ID Siswa yang login: ' . $student_id); 
// -------------------------------------------------------------

if (!$student_id) {
    return redirect()->to('/')->with('error', 'Sesi login tidak valid.');
}

        // 2. Ambil Tahun Ajaran yang Aktif
        $activeYear = $this->tahunModel->getActiveYear();
        if (!$activeYear) {
            return view('siswa/asesmen/index', [
                'title' => 'Portal Asesmen',
                'jadwal_ujian' => [],
                'error_msg' => 'Tidak ada Tahun Ajaran aktif saat ini.'
            ]);
        }

        // 3. Cari rombel_id siswa berdasarkan kelas aktif di tahun ajaran aktif
        $db = \Config\Database::connect();
        $studentRombel = $db->table('class_rombel_students')
                            ->select('class_rombel_students.rombel_id, class_rombel.rombel_name')
                            ->join('class_rombel', 'class_rombel.id = class_rombel_students.rombel_id')
                            ->where('class_rombel_students.student_id', $student_id)
                            ->where('class_rombel.academic_year_id', $activeYear['id'])
                            ->get()
                            ->getRowArray();

        $jadwalUjian = [];
        $namaKelas = 'Belum Masuk Kelas';

        if ($studentRombel) {
            $namaKelas = $studentRombel['rombel_name'];
            
            // HANYA AMBIL JADWAL YANG IS_ACTIVE = 1
            $jadwalUjian = $this->examModel->select('exam_schedules.*, master_subjects.subject_name, exam_categories.nama_kategori')
                        ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                        ->join('exam_categories', 'exam_categories.id = exam_schedules.exam_category_id', 'left')
                        ->where('exam_schedules.academic_year_id', $activeYear['id'])
                        ->where('exam_schedules.rombel_id', $studentRombel['rombel_id'])
                        ->where('exam_schedules.is_active', 1) // Baris baru ini untuk memblokir jadwal non-aktif
                        ->orderBy('exam_schedules.waktu_mulai', 'ASC')
                        ->findAll();
        }

        // PASTIKAN ZONA WAKTU BENAR (Misal: WIB)
        date_default_timezone_set('Asia/Jakarta');

        $data = [
            'title'          => 'Portal Asesmen / Ujian Siswa',
            'tahun_aktif'    => $activeYear,
            'nama_kelas'     => $namaKelas,
            'jadwal_ujian'   => $jadwalUjian,
            'waktu_sekarang' => date('Y-m-d H:i:s')
        ];

        return view('siswa/asesmen/index', $data);
    }

    // 5. Fungsi Validasi Token yang dimasukkan siswa
    public function validate_token()
    {
        $exam_id = $this->request->getPost('exam_schedule_id');
        $input_token = strtoupper(trim($this->request->getPost('token')));

        $jadwal = $this->examModel->find($exam_id);

        if (!$jadwal) {
            return redirect()->back()->with('error', 'Jadwal ujian tidak ditemukan.');
        }

        // Cek kecocokan token
        if ($input_token !== $jadwal['token']) {
            return redirect()->back()->with('error', 'Token ujian salah! Silakan periksa kembali.');
        }

        // Jika token benar, arahkan ke halaman ujian terkunci (Fullscreen + iFrame GForm)
        return redirect()->to('/siswa/asesmen/ruang-ujian/' . $exam_id);
    }

    // --- FUNGSI RUANG UJIAN TERKUNCI ---
    public function ruang_ujian($id)
    {
        if (function_exists('auth')) { $student_id = auth()->id(); } 
        elseif (function_exists('user_id')) { $student_id = user_id(); } 
        else { $student_id = session()->get('id') ?? session()->get('user_id'); }

        if (!$student_id) return redirect()->to('/')->with('error', 'Sesi tidak valid.');

        // Ambil data ujian beserta nama mata pelajaran
        $jadwal = $this->examModel->select('exam_schedules.*, master_subjects.subject_name')
                                  ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                                  ->where('exam_schedules.id', $id)
                                  ->first();

        if (!$jadwal || $jadwal['is_active'] == 0) {
            return redirect()->to('/siswa/asesmen')->with('error', 'Ujian tidak ditemukan atau telah ditutup.');
        }

        // Catat ke tabel exam_logs jika belum ada
        $db = \Config\Database::connect();
        $logExist = $db->table('exam_logs')
                       ->where('exam_schedule_id', $id)
                       ->where('student_id', $student_id)
                       ->get()->getRowArray();

        if (!$logExist) {
            $db->table('exam_logs')->insert([
                'exam_schedule_id' => $id,
                'student_id'       => $student_id,
                'waktu_masuk'      => date('Y-m-d H:i:s'),
                'status'           => 'berlangsung'
            ]);
        }

        $data = [
            'title'  => 'Ujian: ' . $jadwal['subject_name'],
            'jadwal' => $jadwal
        ];

        return view('siswa/asesmen/ruang_ujian', $data);
    }
}