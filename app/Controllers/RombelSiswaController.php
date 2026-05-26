<?php

namespace App\Controllers;

use App\Models\ClassRombelModel;
use App\Models\ClassRombelStudentModel;

class RombelSiswaController extends BaseController
{
    public function manage($rombel_id)
    {
        $db = \Config\Database::connect();
        $rombelModel = new ClassRombelModel();
        
        // 1. Ambil data Rombel yang sedang dibuka
        $rombel = $rombelModel->select('class_rombel.*, mc.class_name as tingkat')
                              ->join('master_classes mc', 'mc.id = class_rombel.master_class_id')
                              ->find($rombel_id);

        if (!$rombel) {
            return redirect()->to(base_url('admin/rombel'))->with('error', '❌ Data Rombel tidak ditemukan.');
        }

        $ta_id = $rombel['academic_year_id'];

        // 2. Ambil Siswa yang SUDAH TERDAFTAR di kelas ini (Kolom Kanan)
        $siswaTerdaftar = $db->table('class_rombel_students crs')
                             ->select('crs.id as plot_id, u.id as student_id, u.username')
                             ->join('users u', 'u.id = crs.student_id')
                             ->where('crs.rombel_id', $rombel_id)
                             ->orderBy('u.username', 'ASC')
                             ->get()->getResultArray();

        // 3. Kueri Rahasia "Anti Rombel Ganda": Cari siswa yang BEBAS di tahun ajaran ini
        // Kita cari ID Siswa yang sudah masuk rombel di semester aktif
        $subQuery = $db->table('class_rombel_students crs')
                       ->select('crs.student_id')
                       ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                       ->where('cr.academic_year_id', $ta_id)
                       ->getCompiledSelect();

        // Ambil data users dengan role 'siswa' yang TIDAK ADA di subQuery atas (Kolom Kiri)
        $siswaBebas = $db->table('users u')
                         ->select('u.id, u.username')
                         ->join('auth_groups_users agu', 'agu.user_id = u.id')
                         ->where('agu.group', 'siswa')
                         ->where("u.id NOT IN ($subQuery)", null, false)
                         ->orderBy('u.username', 'ASC')
                         ->get()->getResultArray();

        $data = [
            'rombel'         => $rombel,
            'siswaTerdaftar' => $siswaTerdaftar,
            'siswaBebas'     => $siswaBebas
        ];

        return view('admin/rombel_siswa/index', $data);
    }

    // Fungsi Memasukkan Siswa ke Kelas (Massal)
    public function add()
    {
        $rombel_id = $this->request->getPost('rombel_id');
        $student_ids = $this->request->getPost('student_ids');

        if (empty($student_ids)) {
            return redirect()->back()->with('error', '⚠️ Pilih minimal satu siswa untuk ditambahkan!');
        }

        $model = new ClassRombelStudentModel();
        foreach ($student_ids as $sid) {
            $model->insert([
                'rombel_id'  => $rombel_id,
                'student_id' => $sid
            ]);
        }

        return redirect()->back()->with('sukses', '✅ ' . count($student_ids) . ' Siswa berhasil dimasukkan ke kelas.');
    }

    // Fungsi Mengeluarkan Siswa dari Kelas (Massal)
    public function remove()
    {
        $plot_ids = $this->request->getPost('plot_ids');

        if (empty($plot_ids)) {
            return redirect()->back()->with('error', '⚠️ Pilih minimal satu siswa untuk dikeluarkan!');
        }

        $model = new ClassRombelStudentModel();
        foreach ($plot_ids as $pid) {
            $model->delete($pid);
        }

        return redirect()->back()->with('sukses', '🗑️ ' . count($plot_ids) . ' Siswa berhasil dikeluarkan dari kelas (Mereka kembali menjadi siswa bebas).');
    }
}