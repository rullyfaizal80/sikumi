<?php

namespace App\Controllers;
use App\Models\ExamScheduleModel;
use App\Models\AcademicYearModel;
use App\Models\ExamCategoryModel; // Tambahkan ini

class Asesmen extends BaseController
{
    protected $examModel;
    protected $tahunModel;
    protected $categoryModel;

    public function __construct()
    {
        $this->examModel = new ExamScheduleModel();
        $this->tahunModel = new AcademicYearModel();
        $this->categoryModel = new ExamCategoryModel();
    }

    public function admin_index()
    {
        $activeYear = $this->tahunModel->getActiveYear();
        if (!$activeYear) {
            die("Error: Tidak ada Tahun Ajaran yang aktif di database.");
        }

        $data = [
            'title'          => 'Manajemen Jadwal Ujian',
            'tahun_aktif'    => $activeYear,
            'jadwal_ujian'   => $this->examModel->getJadwalAdminByTahun($activeYear['id']),
            'kategori_ujian' => $this->categoryModel->findAll() // Mengambil data kategori
        ];

        return view('asesmen/admin_index', $data);
    }

    // --- FUNGSI KELOLA KATEGORI ---
    public function simpan_kategori()
    {
        $this->categoryModel->save([
            'nama_kategori' => $this->request->getPost('nama_kategori')
        ]);
        
        session()->setFlashdata('success', 'Kategori asesmen berhasil ditambahkan!');
        return redirect()->to('/asesmen/admin');
    }

    public function hapus_kategori($id)
    {
        $this->categoryModel->delete($id);
        
        session()->setFlashdata('success', 'Kategori asesmen berhasil dihapus!');
        return redirect()->to('/asesmen/admin');
    }

    // --- FUNGSI TAMBAH JADWAL UJIAN ---
    public function create_jadwal()
    {
        $rombelModel = new \App\Models\RombelModel();
        $subjectModel = new \App\Models\SubjectModel();

        $activeYear = $this->tahunModel->getActiveYear();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada Tahun Ajaran aktif.');
        }

        $data = [
            'title'          => 'Tambah Jadwal Ujian Baru',
            'kategori_ujian' => $this->categoryModel->findAll(),
            'rombels'        => $rombelModel->findAll(),
            'subjects'       => $subjectModel->findAll()
        ];

        return view('asesmen/create_jadwal', $data);
    }

    public function store_jadwal()
    {
        $activeYear = $this->tahunModel->getActiveYear();
        
        // Generate Token Acak 6 Karakter (Huruf Kapital dan Angka)
        $token = strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6));

        $this->examModel->save([
            'academic_year_id' => $activeYear['id'],
            'exam_category_id' => $this->request->getPost('exam_category_id'),
            'rombel_id'        => $this->request->getPost('rombel_id'),
            'subject_id'       => $this->request->getPost('subject_id'),
            'link_gform'       => $this->request->getPost('link_gform'),
            'waktu_mulai'      => $this->request->getPost('waktu_mulai'),
            'waktu_selesai'    => $this->request->getPost('waktu_selesai'),
            'durasi_menit'     => $this->request->getPost('durasi_menit'),
            'token'            => $token,
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0
        ]);

        session()->setFlashdata('success', 'Jadwal berhasil dibuat! Token otomatis digenerate: ' . $token);
        return redirect()->to('/asesmen/admin');
    }
}