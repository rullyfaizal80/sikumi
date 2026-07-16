<?php

namespace App\Controllers;

class EkstrakurikulerController extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');
        
        $db = \Config\Database::connect();
        
        $currentYear = date('Y');
        $tahunAkademik = [];
        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
            $tahunAkademik[] = $i . '/' . ($i + 1);
        }

        $kelas = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        
        $kelompok = [];
        if ($db->tableExists('eskul_groups')) {
            $kelompok = $db->table('eskul_groups eg')
                ->select('eg.id, eg.nama_kelompok, eg.jenis_kelompok, u.username as pembimbing, COUNT(egs.student_id) as jumlah_siswa')
                ->join('users u', 'u.id = eg.pembimbing_id', 'left')
                ->join('eskul_group_students egs', 'egs.group_id = eg.id', 'left')
                ->groupBy('eg.id')
                ->orderBy('eg.nama_kelompok', 'ASC')
                ->get()->getResultArray();
        }

        $data = [
            'title'          => 'Penilaian Ekstrakurikuler',
            'tahunAkademik'  => $tahunAkademik,
            'kelas'          => $kelas,
            'kelompok'       => $kelompok,
            'semester_aktif' => (date('n') >= 7 ? 'Ganjil' : 'Genap')
        ];

        return view('guru/ekstrakurikuler/index', $data);
    }

    public function kelompokCreate()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil data Master
        $rombels = $db->table('class_rombel')->orderBy('rombel_name', 'ASC')->get()->getResultArray();
        $pembimbing = $db->table('users u')
            ->select('u.id, u.username')
            ->join('teacher_profiles tp', 'tp.user_id = u.id')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();
            
        // AMBIL SEMUA SISWA BERSERTA KELASNYA SEKALIGUS
        $students = $db->table('class_rombel_students crs')
            ->select('u.id as student_id, u.username, cr.rombel_name, cr.id as rombel_id')
            ->join('users u', 'u.id = crs.student_id')
            ->join('class_rombel cr', 'cr.id = crs.rombel_id')
            ->orderBy('cr.rombel_name', 'ASC')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // Ambil daftar ID siswa yang SUDAH memiliki kelompok REGULER
        $terdaftar = $db->table('eskul_group_students egs')
            ->select('egs.student_id')
            ->join('eskul_groups eg', 'eg.id = egs.group_id')
            ->where('eg.jenis_kelompok', 'Reguler')
            ->get()->getResultArray();

        $siswaRegulerTerdaftar = array_column($terdaftar, 'student_id');

        $data = [
            'title'                 => 'Buat Kelompok Eskul Baru',
            'rombels'               => $rombels,
            'pembimbing'            => $pembimbing,
            'students'              => $students,
            'siswaRegulerTerdaftar' => $siswaRegulerTerdaftar
        ];

        return view('guru/ekstrakurikuler/kelompok_create', $data);
    }

    public function kelompokStore()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $nama_kelompok  = $post['nama_kelompok'];
        $jenis_kelompok = $post['jenis_kelompok'];
        $pembimbing_id  = $post['pembimbing_id'];
        $student_ids    = $post['students'] ?? [];

        // VALIDASI HANYA BERLAKU JIKA MEMBUAT KELOMPOK REGULER
        if ($jenis_kelompok === 'Reguler' && !empty($student_ids)) {
            $cekGanda = $db->table('eskul_group_students egs')
                ->join('eskul_groups eg', 'eg.id = egs.group_id')
                ->where('eg.jenis_kelompok', 'Reguler')
                ->whereIn('egs.student_id', $student_ids)
                ->get()->getResultArray();

            if (count($cekGanda) > 0) {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan! Salah satu atau beberapa siswa pilihan Anda sudah terdaftar di Kelompok Reguler lainnya.');
            }
        }

        $db->transStart();

        $db->table('eskul_groups')->insert([
            'nama_kelompok'  => $nama_kelompok,
            'jenis_kelompok' => $jenis_kelompok,
            'pembimbing_id'  => $pembimbing_id
        ]);
        $group_id = $db->insertID();

        if (!empty($student_ids)) {
            $dataAnggota = [];
            foreach ($student_ids as $sId) {
                $dataAnggota[] = [
                    'group_id'   => $group_id,
                    'student_id' => $sId
                ];
            }
            $db->table('eskul_group_students')->insertBatch($dataAnggota);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat menyimpan data.');
        }

        return redirect()->to(base_url('guru/ekstrakurikuler'))->with('success', 'Kelompok Eskul berhasil dibuat!');
    }

    // =========================================================================
    // HALAMAN DETAIL KELOMPOK ESKUL (LIHAT ANGGOTA)
    // =========================================================================
    public function kelompokShow($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();

        // 1. Ambil detail kelompok eskul beserta nama pembimbingnya
        $kelompok = $db->table('eskul_groups eg')
            ->select('eg.*, u.username as pembimbing')
            ->join('users u', 'u.id = eg.pembimbing_id', 'left')
            ->where('eg.id', $id)
            ->get()->getRowArray();

        if (!$kelompok) {
            return redirect()->to(base_url('guru/ekstrakurikuler'))->with('error', 'Kelompok eskul tidak ditemukan.');
        }

        // 2. Ambil daftar siswa yang menjadi anggota kelompok ini beserta rombelnya
        $anggota = $db->table('eskul_group_students egs')
            ->select('u.id as student_id, u.username as nama_siswa, cr.rombel_name')
            ->join('users u', 'u.id = egs.student_id')
            ->join('class_rombel_students crs', 'crs.student_id = u.id', 'left')
            ->join('class_rombel cr', 'cr.id = crs.rombel_id', 'left')
            ->where('egs.group_id', $id)
            ->orderBy('cr.rombel_name', 'ASC')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title'    => 'Detail Kelompok Eskul',
            'kelompok' => $kelompok,
            'anggota'  => $anggota
        ];

        return view('guru/ekstrakurikuler/kelompok_show', $data);
    }

    // =========================================================================
    // HALAMAN EDIT KELOMPOK ESKUL (Data Grup & Anggota - LINTAS KELAS)
    // =========================================================================
    public function kelompokEdit($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        $kelompok = $db->table('eskul_groups')->where('id', $id)->get()->getRowArray();
        if (!$kelompok) return redirect()->to(base_url('guru/ekstrakurikuler'))->with('error', 'Kelompok eskul tidak ditemukan.');

        // 1. Ambil daftar pembimbing
        $pembimbing = $db->table('users u')
            ->select('u.id, u.username')
            ->join('teacher_profiles tp', 'tp.user_id = u.id')
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        // 2. Ambil daftar semua Rombel untuk filter dropdown
        $rombels = $db->table('class_rombel')
                      ->orderBy('rombel_name', 'ASC')
                      ->get()->getResultArray();

        // 3. Ambil ID siswa yang SAAT INI sudah terdaftar di kelompok ini
        $currentStudentsRaw = $db->table('eskul_group_students')
            ->where('group_id', $id)
            ->get()->getResultArray();
        $currentStudentIds = array_column($currentStudentsRaw, 'student_id');

        // 4. AMBIL SEMUA SISWA BERSERTA KELASNYA SEKALIGUS
        $students = $db->table('class_rombel_students crs')
                       ->select('u.id as student_id, u.username, cr.rombel_name, cr.id as rombel_id')
                       ->join('users u', 'u.id = crs.student_id')
                       ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                       ->orderBy('cr.rombel_name', 'ASC')
                       ->orderBy('u.username', 'ASC')
                       ->get()->getResultArray();

        // 5. Cari siswa yang sudah terdaftar di kelompok eskul Reguler LAIN
        $siswaRegulerLain = $db->table('eskul_group_students egs')
            ->join('eskul_groups eg', 'eg.id = egs.group_id')
            ->where('eg.jenis_kelompok', 'Reguler')
            ->where('eg.id !=', $id) 
            ->get()->getResultArray();
        $siswaRegulerTerdaftar = array_column($siswaRegulerLain, 'student_id');

        $data = [
            'title'                 => 'Edit Kelompok Eskul',
            'kelompok'              => $kelompok,
            'pembimbing'            => $pembimbing,
            'rombels'               => $rombels,
            'students'              => $students,
            'currentStudentIds'     => $currentStudentIds,
            'siswaRegulerTerdaftar' => $siswaRegulerTerdaftar
        ];

        return view('guru/ekstrakurikuler/kelompok_edit', $data);
    }

    // =========================================================================
    // PROSES UPDATE DATA KELOMPOK ESKUL (SINKRONISASI TOTAL)
    // =========================================================================
    public function kelompokUpdate($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $nama_kelompok  = $post['nama_kelompok'];
        $jenis_kelompok = $post['jenis_kelompok'];
        $pembimbing_id  = $post['pembimbing_id'];
        
        $student_ids    = $post['students'] ?? [];

        // VALIDASI GANDA: Jika grup Reguler, pastikan siswa baru tidak ada di Reguler lain
        if ($jenis_kelompok === 'Reguler' && !empty($student_ids)) {
            $cekGanda = $db->table('eskul_group_students egs')
                ->join('eskul_groups eg', 'eg.id = egs.group_id')
                ->where('eg.jenis_kelompok', 'Reguler')
                ->where('eg.id !=', $id)
                ->whereIn('egs.student_id', $student_ids)
                ->get()->getResultArray();

            if (count($cekGanda) > 0) {
                return redirect()->back()->withInput()->with('error', 'Gagal menyimpan! Ada siswa yang Anda pilih sudah terdaftar di Kelompok Eskul Reguler lain.');
            }
        }

        $db->transStart();

        // 1. Update Info Kelompok
        $db->table('eskul_groups')->where('id', $id)->update([
            'nama_kelompok'  => $nama_kelompok,
            'jenis_kelompok' => $jenis_kelompok,
            'pembimbing_id'  => $pembimbing_id,
            'updated_at'     => date('Y-m-d H:i:s')
        ]);

        // 2. Kosongkan semua anggota lama
        $db->table('eskul_group_students')->where('group_id', $id)->delete();

        // 3. Masukkan ulang anggota yang dicentang
        if (!empty($student_ids)) {
            $dataAnggota = [];
            foreach ($student_ids as $sId) {
                $dataAnggota[] = [
                    'group_id'   => $id,
                    'student_id' => $sId
                ];
            }
            $db->table('eskul_group_students')->insertBatch($dataAnggota);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem saat memperbarui data kelompok.');
        }

        return redirect()->to(base_url('guru/ekstrakurikuler'))->with('success', 'Data kelompok eskul berhasil diperbarui!');
    }

    // =========================================================================
    // HAPUS KELOMPOK ESKUL
    // =========================================================================
    public function kelompokDelete($id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Proteksi: Jangan izinkan hapus jika masih ada siswa di dalamnya
        $jumlahSiswa = $db->table('eskul_group_students')->where('group_id', $id)->countAllResults();
        
        if ($jumlahSiswa > 0) {
            return redirect()->to(base_url('guru/ekstrakurikuler'))
                             ->with('error', 'Gagal menghapus! Kelompok ini masih memiliki ' . $jumlahSiswa . ' anggota. Kosongkan dulu anggotanya melalui menu Edit.');
        }

        $db->table('eskul_groups')->where('id', $id)->delete();

        return redirect()->to(base_url('guru/ekstrakurikuler'))->with('success', 'Kelompok eskul berhasil dihapus!');
    }
}