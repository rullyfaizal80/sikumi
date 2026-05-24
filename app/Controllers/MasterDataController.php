<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MasterClassModel;
use App\Models\MasterSubjectModel;

class MasterDataController extends BaseController
{
    public function index()
    {
        $classModel = new MasterClassModel();
        $subjectModel = new MasterSubjectModel();

        // Mengambil semua data kelas dan mapel untuk dikirim ke View
        $data = [
            'classes'  => $classModel->findAll(),
            'subjects' => $subjectModel->findAll()
        ];

        return view('admin/master_data/index', $data);
    }

    // ==========================================
    // FUNGSI CRUD MASTER KELAS (BARU)
    // ==========================================

    public function storeClass()
    {
        $classModel = new MasterClassModel();
        
        $classModel->insert([
            'class_name'       => $this->request->getPost('class_name'),
            'level_type'       => $this->request->getPost('level_type'), // Enum: MI, MTs, MA
            'curriculum_phase' => $this->request->getPost('curriculum_phase'), // Misal: Fase D
        ]);

        return redirect()->back()->with('sukses', '✔️ Master Kelas baru berhasil ditambahkan.');
    }

    public function updateClass($id)
    {
        $classModel = new MasterClassModel();
        
        $classModel->update($id, [
            'class_name'       => $this->request->getPost('class_name'),
            'level_type'       => $this->request->getPost('level_type'),
            'curriculum_phase' => $this->request->getPost('curriculum_phase'),
        ]);

        return redirect()->back()->with('sukses', '📝 Data Master Kelas berhasil diperbarui.');
    }

    public function deleteClass($id)
    {
        $classModel = new MasterClassModel();
        $db = \Config\Database::connect();

        // Proteksi Awal: Cek apakah ID kelas ini sudah terikat di tabel Kalender Akademik atau transaksi lain
        $cekKaldik = $db->table('academic_calendars')->where('class_id', $id)->get()->getRow();
        
        if ($cekKaldik) {
            return redirect()->back()->with('error', '❌ Gagal dihapus! Master Kelas ini sudah digunakan dan terikat dengan data agenda di Kalender Akademik.');
        }

        $classModel->delete($id);

        return redirect()->back()->with('sukses', '🗑️ Master Kelas berhasil dihapus.');
    }

    // ==========================================
    // FUNGSI CRUD MATA PELAJARAN
    // ==========================================

    public function storeSubject()
    {
        $subjectModel = new MasterSubjectModel();
        
        $subjectModel->insert([
            'subject_code'  => $this->request->getPost('subject_code'),
            'subject_name'  => $this->request->getPost('subject_name'),
            'subject_group' => $this->request->getPost('subject_group'),
        ]);

        return redirect()->back()->with('sukses', '✔️ Mata Pelajaran baru berhasil ditambahkan.');
    }

    public function updateSubject($id)
    {
        $subjectModel = new MasterSubjectModel();
        
        $subjectModel->update($id, [
            'subject_code'  => $this->request->getPost('subject_code'),
            'subject_name'  => $this->request->getPost('subject_name'),
            'subject_group' => $this->request->getPost('subject_group'),
        ]);

        return redirect()->back()->with('sukses', '📝 Data Mata Pelajaran berhasil diperbarui.');
    }

    public function deleteSubject($id)
    {
        $subjectModel = new MasterSubjectModel();
        $subjectModel->delete($id);

        return redirect()->back()->with('sukses', '🗑️ Mata Pelajaran berhasil dihapus.');
    }
}