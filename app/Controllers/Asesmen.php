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
        if (!$activeYear) die("Error: Tidak ada Tahun Ajaran aktif.");

        // RUMUS TOKEN GLOBAL (Berubah otomatis setiap 5 menit / 300 detik)
        $secretKey = "SIKUMI_EXAM"; // Jangan diubah agar token valid
        $timeBlock = floor(time() / 300); 
        $globalToken = strtoupper(substr(md5($secretKey . $timeBlock), 0, 6));

        $data = [
            'title'          => 'Manajemen Jadwal Ujian',
            'tahun_aktif'    => $activeYear,
            'jadwal_ujian'   => $this->examModel->getJadwalAdminByTahun($activeYear['id']),
            'kategori_ujian' => $this->categoryModel->findAll(),
            'global_token'   => $globalToken // Kirim ke View
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
            // FILTER KELAS HANYA UNTUK TAHUN AJARAN AKTIF
            'rombels'        => $rombelModel->where('academic_year_id', $activeYear['id'])->findAll(),
            'subjects'       => $subjectModel->findAll() // Catatan: Jika master_subjects bersifat global (berlaku semua tahun), boleh tetap findAll()
        ];

        return view('asesmen/create_jadwal', $data);
    }
    
    public function store_jadwal()
    {
        $activeYear = $this->tahunModel->getActiveYear();
        
        $this->examModel->save([
            'academic_year_id' => $activeYear['id'],
            'exam_category_id' => $this->request->getPost('exam_category_id'),
            'rombel_id'        => $this->request->getPost('rombel_id'),
            'subject_id'       => $this->request->getPost('subject_id'),
            'link_gform'       => $this->request->getPost('link_gform'),
            'waktu_mulai'      => $this->request->getPost('waktu_mulai'),
            'waktu_selesai'    => $this->request->getPost('waktu_selesai'),
            'durasi_menit'     => $this->request->getPost('durasi_menit'),
            // 'token' => $token,  <-- BARIS INI DIHAPUS
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0
        ]);

        session()->setFlashdata('success', 'Jadwal ujian berhasil dibuat!');
        return redirect()->to('/asesmen/admin');
    }

    // --- FUNGSI EDIT JADWAL ---
    public function edit_jadwal($id)
    {
        $jadwal = $this->examModel->find($id);
        if (!$jadwal) {
            return redirect()->to('/asesmen/admin')->with('error', 'Data jadwal tidak ditemukan.');
        }

        $rombelModel = new \App\Models\RombelModel();
        $subjectModel = new \App\Models\SubjectModel();
        $activeYear = $this->tahunModel->getActiveYear();

        $data = [
            'title'          => 'Edit Jadwal Ujian',
            'jadwal'         => $jadwal,
            'kategori_ujian' => $this->categoryModel->findAll(),
            // FILTER KELAS HANYA UNTUK TAHUN AJARAN AKTIF
            'rombels'        => $rombelModel->where('academic_year_id', $activeYear['id'])->findAll(),
            'subjects'       => $subjectModel->findAll()
        ];

        return view('asesmen/edit_jadwal', $data);
    }

   public function update_jadwal($id)
    {
        $dataUpdate = [
            'id'               => $id,
            'exam_category_id' => $this->request->getPost('exam_category_id'),
            'rombel_id'        => $this->request->getPost('rombel_id'),
            'subject_id'       => $this->request->getPost('subject_id'),
            'link_gform'       => $this->request->getPost('link_gform'),
            'waktu_mulai'      => $this->request->getPost('waktu_mulai'),
            'waktu_selesai'    => $this->request->getPost('waktu_selesai'),
            'durasi_menit'     => $this->request->getPost('durasi_menit'),
            'is_active'        => $this->request->getPost('is_active') ? 1 : 0
        ];

        // LOGIKA RESET TOKEN DIHAPUS TOTAL KARENA SUDAH GLOBAL

        $this->examModel->save($dataUpdate);

        session()->setFlashdata('success', 'Jadwal ujian berhasil diperbarui!');
        return redirect()->to('/asesmen/admin');
    }

    // --- FUNGSI HAPUS JADWAL ---
    public function hapus_jadwal($id)
    {
        $this->examModel->delete($id);
        session()->setFlashdata('success', 'Jadwal ujian berhasil dihapus.');
        return redirect()->to('/asesmen/admin');
    }
}