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

    // --- 1. FUNGSI PEMBANTU MENCARI ID SISWA ---
    private function get_student_id()
    {
        if (function_exists('auth')) { return auth()->id(); } 
        elseif (function_exists('user_id')) { return user_id(); } 
        else { return session()->get('id') ?? session()->get('user_id'); }
    }

    // --- 1. HALAMAN PORTAL UJIAN (INDEX) ---
    public function index()
    {
        $student_id = $this->get_student_id();
        if (!$student_id) return redirect()->to('/')->with('error', 'Sesi login tidak valid.');

        $activeYear = $this->tahunModel->getActiveYear();
        if (!$activeYear) {
            return view('siswa/asesmen/index', [
                'title' => 'Portal Asesmen',
                'jadwal_ujian' => [],
                'error_msg' => 'Tidak ada Tahun Ajaran aktif saat ini.'
            ]);
        }

        $db = \Config\Database::connect();
        $studentRombel = $db->table('class_rombel_students')
                            ->select('class_rombel_students.rombel_id, class_rombel.rombel_name')
                            ->join('class_rombel', 'class_rombel.id = class_rombel_students.rombel_id')
                            ->where('class_rombel_students.student_id', $student_id)
                            ->where('class_rombel.academic_year_id', $activeYear['id'])
                            ->get()->getRowArray();

        $jadwalUjian = [];
        $namaKelas = 'Belum Masuk Kelas';

        if ($studentRombel) {
            $namaKelas = $studentRombel['rombel_name'];
            $jadwalUjian = $this->examModel->select('exam_schedules.*, master_subjects.subject_name, exam_categories.nama_kategori')
                        ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                        ->join('exam_categories', 'exam_categories.id = exam_schedules.exam_category_id', 'left')
                        ->where('exam_schedules.academic_year_id', $activeYear['id'])
                        ->where('exam_schedules.rombel_id', $studentRombel['rombel_id'])
                        ->where('exam_schedules.is_active', 1) 
                        ->orderBy('exam_schedules.waktu_mulai', 'ASC')
                        ->findAll();
        }

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

    // --- 2. HALAMAN BARU: INFO & KISI-KISI ---
    public function info()
    {
        $student_id = $this->get_student_id();
        if (!$student_id) return redirect()->to('/')->with('error', 'Sesi login tidak valid.');

        $infoModel = new \App\Models\AsesmenInfoModel();
        
        $data = [
            'title'        => 'Informasi & Kisi-kisi Asesmen',
            'info_asesmen' => $infoModel->find(1)
        ];

        return view('siswa/asesmen/info', $data);
    }

    // --- 2. UPDATE FUNGSI VALIDASI TOKEN ---
    public function validate_token()
    {
        $exam_id = $this->request->getPost('exam_schedule_id');
        $input_token = strtoupper(trim($this->request->getPost('token')));

        $secretKey = "SIKUMI_EXAM";
        $timeBlock = floor(time() / 300); 
        $validToken = strtoupper(substr(md5($secretKey . $timeBlock), 0, 6));

        if ($input_token !== $validToken) {
            return redirect()->back()->with('error', 'Token salah atau sudah kadaluarsa (Token berubah otomatis)!');
        }

        $student_id = $this->get_student_id();
        
        $db = \Config\Database::connect();
        $logExist = $db->table('exam_logs')
                       ->where('exam_schedule_id', $exam_id)
                       ->where('student_id', $student_id)
                       ->get()->getRowArray();

        // UBAH DISINI: Cek status 'terkunci'
        if ($logExist && $logExist['status'] == 'terkunci') {
            $db->table('exam_logs')
               ->where('id', $logExist['id'])
               ->update(['status' => 'berlangsung']);
        }

        return redirect()->to('/siswa/asesmen/ruang-ujian/' . $exam_id);
    }

    // --- 3. UPDATE FUNGSI RUANG UJIAN ---
    public function ruang_ujian($id)
    {
        $student_id = $this->get_student_id();
        if (!$student_id) return redirect()->to('/')->with('error', 'Sesi tidak valid.');

        $jadwal = $this->examModel->select('exam_schedules.*, master_subjects.subject_name')
                                  ->join('master_subjects', 'master_subjects.id = exam_schedules.subject_id', 'left')
                                  ->where('exam_schedules.id', $id)->first();

        if (!$jadwal || $jadwal['is_active'] == 0) {
            return redirect()->to('/siswa/asesmen')->with('error', 'Ujian tidak aktif.');
        }

        $db = \Config\Database::connect();
        $logExist = $db->table('exam_logs')->where('exam_schedule_id', $id)->where('student_id', $student_id)->get()->getRowArray();

        $timeNow = \CodeIgniter\I18n\Time::now('Asia/Jakarta');

        if (!$logExist) {
            $waktu_masuk = $timeNow->format('Y-m-d H:i:s');
            
            $db->table('exam_logs')->insert([
                'exam_schedule_id' => $id,
                'student_id'       => $student_id,
                'waktu_masuk'      => $waktu_masuk,
                'status'           => 'berlangsung',
                'blokir_count'     => 0
            ]);
        } else {
            // UBAH DISINI: Cek status 'terkunci'
            if ($logExist['status'] == 'terkunci') {
                return redirect()->to('/siswa/asesmen')->with('error', 'Ujian Anda Terkunci! Masukkan Token terbaru dari pengawas.');
            }
            $waktu_masuk = $logExist['waktu_masuk'];
        }

        $waktu_masuk_obj = \CodeIgniter\I18n\Time::parse($waktu_masuk, 'Asia/Jakarta');
        $waktu_berakhir_obj = $waktu_masuk_obj->addMinutes((int)$jadwal['durasi_menit']);
        $sisa_waktu_detik = $waktu_berakhir_obj->getTimestamp() - $timeNow->getTimestamp();

        if ($sisa_waktu_detik < 0) {
            $sisa_waktu_detik = 0;
        }

        $data = [
            'title'            => 'Ujian: ' . $jadwal['subject_name'],
            'jadwal'           => $jadwal,
            'sisa_waktu_detik' => $sisa_waktu_detik
        ];

        return view('siswa/asesmen/ruang_ujian', $data);
    }

    // --- 4. FUNGSI EKSEKUSI BLOKIR ---
    public function eksekusi_blokir($exam_id)
    {
        $student_id = $this->get_student_id();

        if ($exam_id && $student_id) {
            $db = \Config\Database::connect();
            
            $log = $db->table('exam_logs')
                      ->where('exam_schedule_id', $exam_id)
                      ->where('student_id', $student_id)
                      ->get()
                      ->getRowArray();

            if ($log) {
                $newCount = (int)$log['blokir_count'] + 1;
                
                // UBAH DISINI: Simpan sebagai 'terkunci'
                $db->table('exam_logs')
                   ->where('id', $log['id'])
                   ->update([
                       'status'       => 'terkunci',
                       'blokir_count' => $newCount
                   ]);
            }
        }

        return redirect()->to('/siswa/asesmen')->with('error', 'Status ujian Anda telah dikunci karena pelanggaran layar. Silakan minta token baru ke Pengawas.');
    }

    // --- 5. FUNGSI SELESAI UJIAN (UPDATE STATUS MENJADI SELESAI) ---
    public function selesai_ujian($exam_id)
    {
        $student_id = $this->get_student_id();

        if ($exam_id && $student_id) {
            $db = \Config\Database::connect();
            
            // Ambil data log ujian
            $log = $db->table('exam_logs')
                      ->where('exam_schedule_id', $exam_id)
                      ->where('student_id', $student_id)
                      ->get()
                      ->getRowArray();

            // Hanya update jika statusnya masih 'berlangsung'
            if ($log && $log['status'] == 'berlangsung') {
                $db->table('exam_logs')
                   ->where('id', $log['id'])
                   ->update(['status' => 'selesai']);
            }
        }

        // Kembalikan ke dashboard dengan pesan sukses
        return redirect()->to('/siswa/asesmen')->with('success', 'Ujian telah selesai. Terima kasih.');
    }
}