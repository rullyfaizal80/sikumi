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
}