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
        
        $tahunAjaran = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();
        
        $selectedTaId = $this->request->getGet('ta');
        if (empty($selectedTaId)) {
            $activeYear = $db->table('academic_years')->where('is_active', 1)->get()->getRow();
            if ($activeYear) {
                $selectedTaId = $activeYear->id;
            } else {
                $selectedTaId = !empty($tahunAjaran) ? $tahunAjaran[0]['id'] : null;
            }
        }

        $classModel = new MasterClassModel();
        $subjectModel = new MasterSubjectModel();
        
        $guruList = $db->table('users u')->select('u.id, u.username')->join('auth_groups_users agu', 'agu.user_id = u.id')->where('agu.group', 'guru')->get()->getResultArray();
        $walasList = $db->table('users u')->select('u.id, u.username')->join('auth_groups_users agu', 'agu.user_id = u.id')->where('agu.group', 'walas')->get()->getResultArray();

        $rombelModel = new ClassRombelModel();
        $rombels = $rombelModel->select('class_rombel.*, mc.class_name as tingkat, mc.level_type, u.username as nama_walas')
                               ->join('master_classes mc', 'mc.id = class_rombel.master_class_id')
                               ->join('users u', 'u.id = class_rombel.homeroom_teacher_id', 'left')
                               ->where('class_rombel.academic_year_id', $selectedTaId)
                               ->orderBy('mc.id', 'ASC')
                               ->orderBy('class_rombel.rombel_name', 'ASC')
                               ->findAll();

        foreach ($rombels as &$rombel) {
            $rombel['jumlah_siswa'] = $db->table('class_rombel_students')->where('rombel_id', $rombel['id'])->countAllResults();
            $rombel['jumlah_mapel'] = $db->table('class_subject_teachers')->where('rombel_id', $rombel['id'])->countAllResults();
        }
        unset($rombel);

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

    public function store()
    {
        $rombelModel = new ClassRombelModel();
        $rombelModel->insert([
            'academic_year_id'    => $this->request->getPost('academic_year_id'),
            'master_class_id'     => $this->request->getPost('master_class_id'),
            'rombel_name'         => $this->request->getPost('rombel_name'),
            'homeroom_teacher_id' => $this->request->getPost('homeroom_teacher_id') ?: null,
        ]);
        return redirect()->back()->with('sukses', '✔️ Rombongan Belajar baru berhasil diaktifkan.');
    }

    public function update($id)
    {
        $rombelModel = new ClassRombelModel();
        $rombelModel->update($id, [
            'rombel_name'         => $this->request->getPost('rombel_name'),
            'homeroom_teacher_id' => $this->request->getPost('homeroom_teacher_id') ?: null,
        ]);
        return redirect()->back()->with('sukses', '📝 Data Rombel & Wali Kelas berhasil diperbarui.');
    }

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
    // FUNGSI BARU: COPY MAPEL & GURU ANTAR KELAS
    // ==========================================
    public function copyPlottingToOtherClass()
    {
        $sourceRombelId = $this->request->getPost('source_rombel_id');
        $targetRombelId = $this->request->getPost('target_rombel_id');

        if ($sourceRombelId == $targetRombelId) return redirect()->back()->with('error', '❌ Kelas sumber dan target tidak boleh sama.');

        $plottingModel = new ClassSubjectTeacherModel();
        
        // 1. Ambil semua mapel dari kelas sumber
        $sourcePlots = $plottingModel->where('rombel_id', $sourceRombelId)->findAll();

        if (empty($sourcePlots)) return redirect()->back()->with('error', '❌ Kelas sumber belum memiliki data guru & mapel untuk disalin.');

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($sourcePlots as $plot) {
            // Cek apakah mapel ini sudah ada di kelas target
            $exists = $plottingModel->where([
                'rombel_id'         => $targetRombelId,
                'master_subject_id' => $plot['master_subject_id']
            ])->first();

            if (!$exists) {
                // Jika belum ada, tambahkan baru
                $plottingModel->insert([
                    'rombel_id'         => $targetRombelId,
                    'master_subject_id' => $plot['master_subject_id'],
                    'teacher_id'        => $plot['teacher_id']
                ]);
            } else {
                // Jika sudah ada, update/timpa nama gurunya saja agar sama
                $plottingModel->update($exists['id'], [
                    'teacher_id' => $plot['teacher_id']
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) return redirect()->back()->with('error', '❌ Terjadi kesalahan sistem saat menyalin plotting.');

        return redirect()->back()->with('sukses', '♻️ Berhasil! Seluruh Mata Pelajaran beserta Gurunya sukses disalin ke kelas target.');
    }

    // ==========================================
    // FUNGSI LAINNYA (TETAP SAMA)
    // ==========================================
    public function copySemester()
    {
        $db = \Config\Database::connect();
        $targetTaId = $this->request->getPost('target_academic_year_id');
        if (!$targetTaId) return redirect()->back()->with('error', '❌ Tahun ajaran target tidak valid.');

        $prevSemester = $db->table('academic_years')->where('id <', $targetTaId)->orderBy('id', 'DESC')->get()->getRowArray();
        if (!$prevSemester) return redirect()->back()->with('error', '❌ Sistem tidak menemukan data semester sebelumnya untuk disalin.');

        $sourceTaId = $prevSemester['id'];
        $rombelModel = new ClassRombelModel();
        $plottingModel = new ClassSubjectTeacherModel();

        $oldRombels = $rombelModel->where('academic_year_id', $sourceTaId)->findAll();
        if (empty($oldRombels)) return redirect()->back()->with('error', '❌ Pembatalan: Semester sebelumnya ternyata belum memiliki data Rombel.');

        $db->transStart();
        foreach ($oldRombels as $oldRombel) {
            $newRombelId = $rombelModel->insert([
                'academic_year_id'    => $targetTaId,
                'master_class_id'     => $oldRombel['master_class_id'],
                'rombel_name'         => $oldRombel['rombel_name'],
                'homeroom_teacher_id' => null 
            ]);

            $oldPlots = $plottingModel->where('rombel_id', $oldRombel['id'])->findAll();
            foreach ($oldPlots as $oldPlot) {
                $plottingModel->insert([
                    'rombel_id'         => $newRombelId,
                    'master_subject_id' => $oldPlot['master_subject_id'],
                    'teacher_id'        => $oldPlot['teacher_id']
                ]);
            }
        }
        $db->transComplete();

        if ($db->transStatus() === false) return redirect()->back()->with('error', '❌ Terjadi kegagalan sistem saat menyalin data.');

        return redirect()->to(base_url('admin/rombel?ta=' . $targetTaId))->with('sukses', '🪄 Alhamdulillah! Seluruh struktur kelas & beban mengajar guru dari semester lalu berhasil disalin.');
    }

    public function delete($id)
    {
        $rombelModel = new ClassRombelModel();
        $plottingModel = new ClassSubjectTeacherModel();
        $cekMapel = $plottingModel->where('rombel_id', $id)->countAllResults();
        if ($cekMapel > 0) return redirect()->back()->with('error', '❌ Gagal menghapus kelas! Rombel ini masih memiliki data Guru Mata Pelajaran. Silakan hapus seluruh mapel di kelas ini terlebih dahulu.');
        $rombelModel->delete($id);
        return redirect()->back()->with('sukses', '🗑️ Rombel berhasil dihapus.');
    }

    public function manageStudents($rombelId)
    {
        $db = \Config\Database::connect();
        $assignedStudents = $db->table('class_student_members csm')->select('u.id, u.username')->join('users u', 'u.id = csm.student_id')->where('csm.rombel_id', $rombelId)->get()->getResultArray();
        $availableStudents = $db->table('users u')->join('auth_groups_users agu', 'agu.user_id = u.id')->where('agu.group', 'siswa')->whereNotIn('u.id', $db->table('class_student_members')->select('student_id'))->get()->getResultArray();

        return view('admin/rombel/manage_students', [
            'rombelId' => $rombelId,
            'assigned' => $assignedStudents,
            'available' => $availableStudents
        ]);
    }

    public function storeStudents()
    {
        $rombelId = $this->request->getPost('rombel_id');
        $studentIds = $this->request->getPost('student_ids'); 
        if (!empty($studentIds)) {
            $db = \Config\Database::connect();
            foreach ($studentIds as $sId) {
                try { $db->table('class_student_members')->insert(['rombel_id' => $rombelId, 'student_id' => $sId]); } 
                catch (\Exception $e) { continue; }
            }
        }
        return redirect()->back()->with('sukses', '✔️ Siswa berhasil ditambahkan ke kelas.');
    }
}