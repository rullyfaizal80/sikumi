<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ClassRombelModel;
use App\Models\ClassSubjectTeacherModel;
use App\Models\MasterClassModel;
use App\Models\MasterSubjectModel;

class RombelController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil daftar Tahun Ajaran dari database
        $tahunAjaran = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();
        
        // 2. Tangkap parameter Tahun Ajaran atau gunakan yang AKTIF
        $selectedTaId = $this->request->getGet('ta');
        if (empty($selectedTaId)) {
            $activeYear = $db->table('academic_years')->where('is_active', 1)->get()->getRow();
            if ($activeYear) {
                $selectedTaId = $activeYear->id;
            } else {
                $selectedTaId = !empty($tahunAjaran) ? $tahunAjaran[0]['id'] : null;
            }
        }

        // 3. Ambil data Master
        $classModel = new MasterClassModel();
        $subjectModel = new MasterSubjectModel();
        
        // 4. Ambil daftar Guru & Walas untuk Dropdown
        $guruList = $db->table('users u')
                       ->select('u.id, u.username') 
                       ->join('auth_groups_users agu', 'agu.user_id = u.id')
                       ->where('agu.group', 'guru') 
                       ->get()->getResultArray();

        $walasList = $db->table('users u')
                       ->select('u.id, u.username') 
                       ->join('auth_groups_users agu', 'agu.user_id = u.id')
                       ->where('agu.group', 'walas') 
                       ->get()->getResultArray();

        // 5. Ambil data Rombel berdasarkan Tahun Ajaran yang terpilih
        $rombelModel = new ClassRombelModel();
        $rombels = $rombelModel->select('class_rombel.*, mc.class_name as tingkat, mc.level_type, u.username as nama_walas')
                               ->join('master_classes mc', 'mc.id = class_rombel.master_class_id')
                               ->join('users u', 'u.id = class_rombel.homeroom_teacher_id', 'left')
                               ->where('class_rombel.academic_year_id', $selectedTaId)
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('class_rombel.rombel_name', 'ASC')
                               ->findAll();

        // 🌟 PROSES BARU: Hitung jumlah siswa yang masuk ke masing-masing rombel
        foreach ($rombels as &$rombel) {
            $rombel['jumlah_siswa'] = $db->table('class_rombel_students')
                                         ->where('rombel_id', $rombel['id'])
                                         ->countAllResults();
        }
        unset($rombel); // Putuskan referensi penunjuk link perulangan

        // 6. Ambil data Plotting Guru Mapel untuk setiap Rombel
        $plottingMapel = [];
        if (!empty($rombels)) {
            $rombelIds = array_column($rombels, 'id');
            $plottingModel = new ClassSubjectTeacherModel();
            
            $plotData = $plottingModel->select('class_subject_teachers.*, ms.subject_name, ms.subject_group, u.username as nama_guru')
                                      ->join('master_subjects ms', 'ms.id = class_subject_teachers.master_subject_id')
                                      ->join('users u', 'u.id = class_subject_teachers.teacher_id')
                                      ->whereIn('class_subject_teachers.rombel_id', $rombelIds)
                                      ->findAll();
            
            foreach ($plotData as $plot) {
                $plottingMapel[$plot['rombel_id']][] = $plot;
            }
        }

        $data = [
            'tahunAjaran'   => $tahunAjaran,
            'selectedTaId'  => $selectedTaId,
            'masterClasses' => $classModel->findAll(),
            'masterSubjects'=> $subjectModel->findAll(),
            'guruList'      => $guruList,
            'walasList'     => $walasList,
            'rombels'       => $rombels,
            'plottingMapel' => $plottingMapel
        ];

        return view('admin/rombel/index', $data);
    }

    // ==========================================
    // PROSES CRUD ROMBEL
    // ==========================================

    public function store()
    {
        $rombelModel = new ClassRombelModel();
        
        $rombelModel->insert([
            'academic_year_id'    => $this->request->getPost('academic_year_id'),
            'master_class_id'     => $this->request->getPost('master_class_id'),
            'rombel_name'         => $this->request->getPost('rombel_name'),
            'homeroom_teacher_id' => $this->request->getPost('homeroom_teacher_id') ?: null, // opsional bisa null
        ]);

        return redirect()->back()->with('sukses', '✔️ Rombongan Belajar baru berhasil diaktifkan.');
    }

    public function update($id)
    {
        $rombelModel = new ClassRombelModel();
        
        $rombelModel->update($id, [
            'rombel_name'         => $this->request->getPost('rombel_name'),
            'homeroom_teacher_id' => $this->request->getPost('homeroom_teacher_id') ?: null, // opsional bisa null
        ]);

        return redirect()->back()->with('sukses', '📝 Data Rombel & Wali Kelas berhasil diperbarui.');
    }

    // ==========================================
    // PROSES PLOTTING GURU MAPEL
    // ==========================================

    public function plotStore()
    {
        $plottingModel = new ClassSubjectTeacherModel();
        
        $plottingModel->insert([
            'rombel_id'         => $this->request->getPost('rombel_id'),
            'master_subject_id' => $this->request->getPost('master_subject_id'),
            'teacher_id'        => $this->request->getPost('teacher_id'),
        ]);

        return redirect()->back()->with('sukses', '✔️ Plotting Guru Mata Pelajaran berhasil disimpan.');
    }

    public function plotDelete($id)
    {
        $plottingModel = new ClassSubjectTeacherModel();
        $plottingModel->delete($id);

        return redirect()->back()->with('sukses', '🗑️ Plotting Mapel berhasil dihapus dari kelas.');
    }

    // ==========================================
    // FITUR UTAMA: SALIN DATA SEMESTER LALU
    // ==========================================

    public function copySemester()
    {
        $db = \Config\Database::connect();
        $targetTaId = $this->request->getPost('target_academic_year_id');

        if (!$targetTaId) {
            return redirect()->back()->with('error', '❌ Tahun ajaran target tidak valid.');
        }

        // Cari ID Semester Sebelumnya yang lebih kecil dari semester saat ini
        $prevSemester = $db->table('academic_years')
                           ->where('id <', $targetTaId)
                           ->orderBy('id', 'DESC')
                           ->get()->getRowArray();

        if (!$prevSemester) {
            return redirect()->back()->with('error', '❌ Sistem tidak menemukan data semester sebelumnya untuk disalin.');
        }

        $sourceTaId = $prevSemester['id'];

        $rombelModel = new ClassRombelModel();
        $plottingModel = new ClassSubjectTeacherModel();

        // Ambil rombel dari semester lama
        $oldRombels = $rombelModel->where('academic_year_id', $sourceTaId)->findAll();

        if (empty($oldRombels)) {
            return redirect()->back()->with('error', '❌ Pembatalan: Semester sebelumnya ternyata belum memiliki data Rombel.');
        }

        // Jalankan Transaction DB (Gagal satu, batalkan semua)
        $db->transStart();

        foreach ($oldRombels as $oldRombel) {
            // Duplikasi Rombel ke Semester Baru (Wali Kelas dikosongkan dahulu sesuai instruksi)
            $newRombelId = $rombelModel->insert([
                'academic_year_id'    => $targetTaId,
                'master_class_id'     => $oldRombel['master_class_id'],
                'rombel_name'         => $oldRombel['rombel_name'],
                'homeroom_teacher_id' => null 
            ]);

            // Ambil seluruh mapel yang nempel di rombel lama tersebut
            $oldPlots = $plottingModel->where('rombel_id', $oldRombel['id'])->findAll();

            foreach ($oldPlots as $oldPlot) {
                // Masukkan plotting mapel dengan mengikatkan ID Rombel yang baru dibuat
                $plottingModel->insert([
                    'rombel_id'         => $newRombelId,
                    'master_subject_id' => $oldPlot['master_subject_id'],
                    'teacher_id'        => $oldPlot['teacher_id']
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', '❌ Terjadi kegagalan sistem saat menyalin data.');
        }

        return redirect()->to(base_url('admin/rombel?ta=' . $targetTaId))->with('sukses', '🪄 Alhamdulillah! Seluruh struktur kelas & beban mengajar guru dari semester lalu berhasil disalin.');
    }

    public function delete($id)
    {
        $rombelModel = new ClassRombelModel();
        $plottingModel = new ClassSubjectTeacherModel();

        // Aturan: Cek apakah rombel ini masih memiliki plot mata pelajaran
        $cekMapel = $plottingModel->where('rombel_id', $id)->countAllResults();

        if ($cekMapel > 0) {
            return redirect()->back()->with('error', '❌ Gagal menghapus kelas! Rombel ini masih memiliki data Guru Mata Pelajaran. Silakan hapus seluruh mapel di kelas ini terlebih dahulu.');
        }

        // Jika tidak ada mapel (aman), hapus rombel
        $rombelModel->delete($id);

        return redirect()->back()->with('sukses', '🗑️ Rombel berhasil dihapus.');
    }

    public function manageStudents($rombelId)
{
    $db = \Config\Database::connect();
    
    // 1. Siswa yang sudah di kelas ini
    $assignedStudents = $db->table('class_student_members csm')
                           ->select('u.id, u.username')
                           ->join('users u', 'u.id = csm.student_id')
                           ->where('csm.rombel_id', $rombelId)
                           ->get()->getResultArray();

    // 2. Siswa yang belum masuk kelas manapun (Siswa Bebas)
    // Query: Ambil user 'siswa' yang ID-nya TIDAK ADA di tabel class_student_members
    $availableStudents = $db->table('users u')
                            ->join('auth_groups_users agu', 'agu.user_id = u.id')
                            ->where('agu.group', 'siswa')
                            ->whereNotIn('u.id', $db->table('class_student_members')->select('student_id'))
                            ->get()->getResultArray();

    return view('admin/rombel/manage_students', [
        'rombelId' => $rombelId,
        'assigned' => $assignedStudents,
        'available' => $availableStudents
    ]);
}

public function storeStudents()
{
    $rombelId = $this->request->getPost('rombel_id');
    $studentIds = $this->request->getPost('student_ids'); // Array dari select box

    if (!empty($studentIds)) {
        $db = \Config\Database::connect();
        foreach ($studentIds as $sId) {
            try {
                $db->table('class_student_members')->insert([
                    'rombel_id'  => $rombelId,
                    'student_id' => $sId
                ]);
            } catch (\Exception $e) {
                // Jika error (karena duplicate unique constraint), abaikan
                continue;
            }
        }
    }
    return redirect()->back()->with('sukses', '✔️ Siswa berhasil ditambahkan ke kelas.');
}

}