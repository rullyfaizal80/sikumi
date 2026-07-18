<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class CatatanSiswaController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil Tahun Ajaran Aktif & Semester
        $tahunAktif = $db->tableExists('academic_years') ? $db->table('academic_years')->where('is_active', 1)->get()->getRowArray() : null;
        $tahunAktifId = $tahunAktif['id'] ?? 0;
        
        // Ambil string Semester Aktif untuk kebutuhan judul/header halaman[cite: 5]
        $semesterAktif = $tahunAktif['semester'] ?? 'Ganjil'; 

        // 2. Ambil Daftar Kelas (Rombel)
        $daftarRombel = [];
        if ($tahunAktifId > 0 && $db->tableExists('class_rombel')) {
            $daftarRombel = $db->table('class_rombel')->where('academic_year_id', $tahunAktifId)->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        }
        
        $selectedRombelId = $this->request->getGet('rombel_id') ?? (!empty($daftarRombel) ? $daftarRombel[0]['id'] : null);

        // Cari nama rombel aktif untuk ditampilkan di view/modal
        $namaRombelTerpilih = '';
        foreach ($daftarRombel as $r) {
            if ($r['id'] == $selectedRombelId) {
                $namaRombelTerpilih = $r['rombel_name'];
                break;
            }
        }

        // 3. Ambil Data Siswa di Rombel Terpilih
        $siswaData = [];
        if ($selectedRombelId && $db->tableExists('class_rombel_students')) {
            $siswaData = $db->table('class_rombel_students crs')
                            ->select('u.id as student_id, u.username as name')
                            ->join('users u', 'u.id = crs.student_id')
                            ->where('crs.rombel_id', $selectedRombelId)
                            ->orderBy('u.username', 'ASC')
                            ->get()->getResultArray();
                            
            foreach ($siswaData as &$siswa) {
                $siswa['jml_anekdot'] = $db->table('catatan_anekdot')->where('student_id', $siswa['student_id'])->where('academic_year_id', $tahunAktifId)->countAllResults();
                $siswa['jml_prestasi'] = $db->table('catatan_prestasi')->where('student_id', $siswa['student_id'])->where('academic_year_id', $tahunAktifId)->countAllResults();
            }
        }

        $data = [
            'tahunAktifId'       => $tahunAktifId,
            'semesterAktif'      => $semesterAktif, 
            'daftarRombel'       => $daftarRombel,
            'selectedRombelId'   => $selectedRombelId,
            'namaRombelTerpilih' => $namaRombelTerpilih,
            'siswaData'          => $siswaData,
        ];

        return view('guru/catatan_index', $data);
    }

    // Proses Simpan Anekdot via AJAX
    public function simpanAnekdot()
    {
        $db = \Config\Database::connect();
        $db->table('catatan_anekdot')->insert([
            'student_id'       => $this->request->getPost('student_id'),
            'rombel_id'        => $this->request->getPost('rombel_id'),
            'academic_year_id' => $this->request->getPost('academic_year_id'),
            'tanggal'          => $this->request->getPost('tanggal'),
            'kejadian'         => $this->request->getPost('kejadian'),
            'created_at'       => date('Y-m-d H:i:s')
        ]);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Anekdot berhasil disimpan.']);
    }

    // Proses Simpan Prestasi via AJAX
    public function simpanPrestasi()
    {
        $db = \Config\Database::connect();
        $db->table('catatan_prestasi')->insert([
            'student_id'       => $this->request->getPost('student_id'),
            'rombel_id'        => $this->request->getPost('rombel_id'),
            'academic_year_id' => $this->request->getPost('academic_year_id'),
            'nama_prestasi'    => $this->request->getPost('nama_prestasi'),
            'keterangan'       => $this->request->getPost('keterangan'),
            'created_at'       => date('Y-m-d H:i:s')
        ]);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Prestasi berhasil disimpan.']);
    }

    // Halaman Rekapitulasi per Kelas
    public function rekap()
    {
        $db = \Config\Database::connect();
        $rombelId = $this->request->getGet('rombel_id');
        $tahunAktifId = $this->request->getGet('academic_year_id');

        $rekapAnekdot = $db->table('catatan_anekdot ca')
                           ->select('ca.*, u.username as name')
                           ->join('users u', 'u.id = ca.student_id')
                           ->where('ca.rombel_id', $rombelId)
                           ->where('ca.academic_year_id', $tahunAktifId)
                           ->orderBy('ca.tanggal', 'DESC')->get()->getResultArray();

        $rekapPrestasi = $db->table('catatan_prestasi cp')
                            ->select('cp.*, u.username as name')
                            ->join('users u', 'u.id = cp.student_id')
                            ->where('cp.rombel_id', $rombelId)
                            ->where('cp.academic_year_id', $tahunAktifId)
                            ->orderBy('cp.created_at', 'DESC')->get()->getResultArray();

        $data = [
            'rekapAnekdot'  => $rekapAnekdot,
            'rekapPrestasi' => $rekapPrestasi
        ];

        return view('guru/catatan_rekap', $data);
    }

    // Halaman Rekapitulasi SELURUH KELAS (1 Semester Aktif)
    public function rekapAll()
    {
        $db = \Config\Database::connect();
        $tahunAktifId = $this->request->getGet('academic_year_id');
        $semesterAktif = $this->request->getGet('semester');

        // Tarik semua anekdot digabung dengan nama kelas (Hanya filter berdasarkan academic_year_id)
        $rekapAnekdot = $db->table('catatan_anekdot ca')
                           ->select('ca.*, u.username as name, r.rombel_name as kelas')
                           ->join('users u', 'u.id = ca.student_id')
                           ->join('class_rombel r', 'r.id = ca.rombel_id', 'left')
                           ->where('ca.academic_year_id', $tahunAktifId)
                           ->orderBy('ca.tanggal', 'DESC')->get()->getResultArray();

        // Tarik semua prestasi digabung dengan nama kelas (Hanya filter berdasarkan academic_year_id)
        $rekapPrestasi = $db->table('catatan_prestasi cp')
                            ->select('cp.*, u.username as name, r.rombel_name as kelas')
                            ->join('users u', 'u.id = cp.student_id')
                            ->join('class_rombel r', 'r.id = cp.rombel_id', 'left')
                            ->where('cp.academic_year_id', $tahunAktifId)
                            ->orderBy('cp.created_at', 'DESC')->get()->getResultArray();

        $data = [
            'rekapAnekdot'  => $rekapAnekdot,
            'rekapPrestasi' => $rekapPrestasi,
            'semesterAktif' => $semesterAktif
        ];

        return view('guru/catatan_rekap_all', $data);
    }

    // ==========================================
    // FUNGSI UPDATE & HAPUS ANEKDOT
    // ==========================================
    public function updateAnekdot()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $db->table('catatan_anekdot')->where('id', $id)->update([
            'tanggal'  => $this->request->getPost('tanggal'),
            'kejadian' => $this->request->getPost('kejadian')
        ]);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Anekdot berhasil diperbarui.']);
    }

    public function hapusAnekdot()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $db->table('catatan_anekdot')->where('id', $id)->delete();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Anekdot berhasil dihapus.']);
    }

    // ==========================================
    // FUNGSI UPDATE & HAPUS PRESTASI
    // ==========================================
    public function updatePrestasi()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $db->table('catatan_prestasi')->where('id', $id)->update([
            'nama_prestasi' => $this->request->getPost('nama_prestasi'),
            'keterangan'    => $this->request->getPost('keterangan')
        ]);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Prestasi berhasil diperbarui.']);
    }

    public function hapusPrestasi()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('id');
        $db->table('catatan_prestasi')->where('id', $id)->delete();
        return $this->response->setJSON(['status' => 'success', 'message' => 'Catatan Prestasi berhasil dihapus.']);
    }
}