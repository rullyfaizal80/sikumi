<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class KaldikController extends BaseController
{
    // Halaman Utama Pengelolaan & Penampilan Kalender Akademik
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil semua daftar Tahun Ajaran untuk opsi Dropdown di View
        $daftarTahun = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();

        // 2. Tangkap parameter pilihan Tahun Ajaran dari URL (?ta=...)
        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) {
            // Jika user memilih dari dropdown, gunakan tahun tersebut
            $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray();
        } 
        
        if (empty($tahunAktif)) {
            // Jika kosong (baru pertama buka), otomatis gunakan yang berstatus AKTIF
            $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            
            if (!$tahunAktif && !empty($daftarTahun)) {
                 // Fallback darurat jika belum ada yang diset aktif
                 $tahunAktif = $daftarTahun[0];
            }
        }

        // 3. Ambil parameter pilihan filter Kelas dan Jenjang (Default ke Kelas 7)
        $kelasTerpilih = $this->request->getGet('class_id') ?? 1;

        // 4. Tarik data master komponen untuk dropdown menu pilihan kelas & warna di View
        $daftarKelas = $db->table('master_classes')->get()->getResultArray();
        $daftarWarna = $db->table('master_categories')->get()->getResultArray();

        // 5. Ambil opsi hari kerja dari tabel settings (Fallback default ke 5 jika kosong)
        $hariKerjaSetting = 5;
        if ($db->tableExists('settings')) {
            $getSetting = $db->table('settings')->where('key', 'kaldik_hari_kerja')->get()->getRowArray();
            if ($getSetting) {
                $hariKerjaSetting = (int)$getSetting['value'];
            }
        }

        // 6. Tarik data agenda Kaldik berdasarkan tahun pelajaran & kelas yang sedang dilihat
        $agendaKaldik = [];
        if ($tahunAktif) {
            $agendaKaldik = $db->table('academic_calendars ac')
                   ->select('ac.*, mc.category_name, mc.color_hex, ac.category_id')
                   ->join('master_categories mc', 'mc.id = ac.category_id')
                   ->where('ac.academic_year_id', $tahunAktif['id'])
                   ->where('ac.class_id', $kelasTerpilih)
                   ->orderBy('ac.start_date', 'ASC')
                   ->get()
                   ->getResultArray();
        }

        $data = [
            'daftarTahun'      => $daftarTahun,     // SUNTIKKAN: Untuk Dropdown View
            'tahunAktif'       => $tahunAktif,      // SUNTIKKAN: Tahun yang sedang tampil
            'kelasTerpilih'    => $kelasTerpilih,
            'daftarKelas'      => $daftarKelas,
            'daftarWarna'      => $daftarWarna,
            'agendaKaldik'     => $agendaKaldik,
            'hariKerjaSetting' => $hariKerjaSetting 
        ];

        return view('admin/kaldik_manage', $data);
    }

    // Fungsi Pengolah Penyimpanan Agenda Kegiatan Baru dari Waka
    public function storeAgenda()
    {
        $db = \Config\Database::connect();

        $dataInsert = [
            'academic_year_id' => $this->request->getPost('academic_year_id'),
            'class_id'         => $this->request->getPost('class_id'),
            'category_id'      => $this->request->getPost('category_id'),
            'start_date'       => $this->request->getPost('start_date'),
            'end_date'         => $this->request->getPost('end_date'), // Mendukung rentang tanggal dokumen MIMHa
            'event_name'       => $this->request->getPost('event_name'),
            'created_at'       => date('Y-m-d H:i:s'),
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        $db->table('academic_calendars')->insert($dataInsert);

        return redirect()->to('admin/kaldik?class_id=' . $dataInsert['class_id'])->with('sukses', 'Agenda kegiatan baru berhasil diploting ke Kalender Akademik!');
    }

    // FITUR UNGGULAN: Opsi Salin (Copy) Seluruh Jadwal Agenda Ke Kelas Lain
    public function copyKaldik()
    {
        $db = \Config\Database::connect();

        $tahunId   = $this->request->getPost('academic_year_id');
        $dariKelas = $this->request->getPost('from_class_id');
        $keKelas   = $this->request->getPost('to_class_id'); // Target penyalinan (e.g., salin ke kelas 8)

        // 1. Tarik semua agenda dari kelas sumber
        $agendaSumber = $db->table('academic_calendars')
                           ->where('academic_year_id', $tahunId)
                           ->where('class_id', $dariKelas)
                           ->get()
                           ->getResultArray();

        if (empty($agendaSumber)) {
            return redirect()->to('admin/kaldik?class_id=' . $dariKelas)->with('gagal', 'Gagal menyalin! Kelas sumber tidak memiliki agenda apa pun.');
        }

        // 2. Susun data batch baru untuk dimasukkan ke kelas target
        $dataBatch = [];
        foreach ($agendaSumber as $row) {
            $dataBatch[] = [
                'academic_year_id' => $tahunId,
                'class_id'         => $keKelas, // Diubah ke ID kelas baru
                'category_id'      => $row['category_id'],
                'start_date'       => $row['start_date'],
                'end_date'         => $row['end_date'],
                'event_name'       => $row['event_name'],
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ];
        }

        // 3. Masukkan secara massal ke database
        $db->table('academic_calendars')->insertBatch($dataBatch);

        return redirect()->to('admin/kaldik?class_id=' . $keKelas)->with('sukses', 'Berhasil menduplikasi! Seluruh agenda kalender akademik sukses disalin.');
    }

        // Fungsi untuk Mengubah (Edit) Agenda Kaldik
    public function updateAgenda()
    {
        $db = \Config\Database::connect();
        $id = $this->request->getPost('agenda_id');
        $classId = $this->request->getPost('class_id');

        $dataUpdate = [
            'category_id' => $this->request->getPost('category_id'),
            'start_date'  => $this->request->getPost('start_date'),
            'end_date'    => $this->request->getPost('end_date'),
            'event_name'  => $this->request->getPost('event_name'),
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        $db->table('academic_calendars')->where('id', $id)->update($dataUpdate);

        return redirect()->to('admin/kaldik?class_id=' . $classId)->with('sukses', 'Agenda kegiatan berhasil diperbarui!');
    }

    // Fungsi untuk Menghapus Agenda Kaldik
    public function deleteAgenda($id)
    {
        $db = \Config\Database::connect();
        $classId = $this->request->getGet('class_id') ?? 1;

        $db->table('academic_calendars')->where('id', $id)->delete();

        return redirect()->to('admin/kaldik?class_id=' . $classId)->with('sukses', 'Agenda kegiatan berhasil dihapus dari kalender!');
    }

       // Fungsi khusus untuk merender Lembar Cetak Kalender 6 Bulan
    public function printKaldik()
    {
        $db = \Config\Database::connect();

        // SINKRONISASI: Tangkap URL dari tombol cetak agar mencetak semester yang tepat
        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) {
            $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray();
        } 
        if (empty($tahunAktif)) {
            $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        }

        $kelasTerpilih = $this->request->getGet('class_id') ?? 1;

        $daftarKelas = $db->table('master_classes')->get()->getResultArray();
        $daftarWarna = $db->table('master_categories')->get()->getResultArray();

        // MENCARI DATA PROFIL, KEPALA MADRASAH & TITI MANGSA DARI TABEL SETTING (JIKA ADA)
        $namaMadrasah  = null; 
        $titiMangsa    = null;
        $kepalaSekolah = null;
        $npkKepala     = null;

        if ($db->tableExists('settings')) {
            $namaMadrasah  = $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray(); 
            $titiMangsa    = $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray();
            $kepalaSekolah = $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray();
            $npkKepala     = $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray();
        }

        $agendaKaldik = [];
        if ($tahunAktif) {
            $agendaKaldik = $db->table('academic_calendars ac')
                               ->select('ac.*, mc.category_name, mc.color_hex, ac.category_id')
                               ->join('master_categories mc', 'mc.id = ac.category_id')
                               ->where('ac.academic_year_id', $tahunAktif['id'])
                               ->where('ac.class_id', $kelasTerpilih)
                               ->orderBy('ac.start_date', 'ASC')
                               ->get()
                               ->getResultArray();
        }

        $data = [
            'tahunAktif'    => $tahunAktif,
            'kelasTerpilih' => $kelasTerpilih,
            'daftarKelas'   => $daftarKelas,
            'daftarWarna'   => $daftarWarna,
            'agendaKaldik'  => $agendaKaldik,
            'namaMadrasah'  => $namaMadrasah ? $namaMadrasah['value'] : 'MIMHa Tsanawiyah Informatika', 
            'titiMangsa'    => $titiMangsa ? $titiMangsa['value'] : 'Bandung, 02 Januari 2026',
            'kepalaNama'    => $kepalaSekolah ? $kepalaSekolah['value'] : 'Yana Purnama, S.Pd',
            'kepalaNpk'     => $npkKepala ? $npkKepala['value'] : '3912390046098'
        ];

        return view('admin/kaldik_print_view', $data);
    }

    // =========================================================================
    // KHUSUS AKSES GURU (READ-ONLY)
    // =========================================================================
    public function guruIndex()
    {
        $db = \Config\Database::connect();

        $daftarTahun = $db->table('academic_years')->orderBy('id', 'DESC')->get()->getResultArray();
        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) {
            $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray();
        } 
        if (empty($tahunAktif)) {
            $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            if (!$tahunAktif && !empty($daftarTahun)) $tahunAktif = $daftarTahun[0];
        }

        $kelasTerpilih = $this->request->getGet('class_id') ?? 1;
        $daftarKelas = $db->table('master_classes')->get()->getResultArray();
        $daftarWarna = $db->table('master_categories')->get()->getResultArray();

        $hariKerjaSetting = 5;
        if ($db->tableExists('settings')) {
            $getSetting = $db->table('settings')->where('key', 'kaldik_hari_kerja')->get()->getRowArray();
            if ($getSetting) $hariKerjaSetting = (int)$getSetting['value'];
        }

        $agendaKaldik = [];
        if ($tahunAktif) {
            $agendaKaldik = $db->table('academic_calendars ac')
                   ->select('ac.*, mc.category_name, mc.color_hex, ac.category_id')
                   ->join('master_categories mc', 'mc.id = ac.category_id')
                   ->where('ac.academic_year_id', $tahunAktif['id'])
                   ->where('ac.class_id', $kelasTerpilih)
                   ->orderBy('ac.start_date', 'ASC')
                   ->get()
                   ->getResultArray();
        }

        $data = [
            'daftarTahun'      => $daftarTahun,
            'tahunAktif'       => $tahunAktif,
            'kelasTerpilih'    => $kelasTerpilih,
            'daftarKelas'      => $daftarKelas,
            'daftarWarna'      => $daftarWarna,
            'agendaKaldik'     => $agendaKaldik,
            'hariKerjaSetting' => $hariKerjaSetting 
        ];

        // MENGARAH KE FOLDER GURU
        return view('guru/kaldik_view', $data);
    }

    public function guruPrint()
    {
        $db = \Config\Database::connect();

        $selectedTaId = $this->request->getGet('ta');
        $tahunAktif = null;

        if (!empty($selectedTaId)) {
            $tahunAktif = $db->table('academic_years')->where('id', $selectedTaId)->get()->getRowArray();
        } 
        if (empty($tahunAktif)) {
            $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        }

        $kelasTerpilih = $this->request->getGet('class_id') ?? 1;
        $daftarKelas = $db->table('master_classes')->get()->getResultArray();
        $daftarWarna = $db->table('master_categories')->get()->getResultArray();

        $namaMadrasah = null; $titiMangsa = null; $kepalaSekolah = null; $npkKepala = null;
        if ($db->tableExists('settings')) {
            $namaMadrasah  = $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray(); 
            $titiMangsa    = $db->table('settings')->where('key', 'kaldik_titi_mangsa')->get()->getRowArray();
            $kepalaSekolah = $db->table('settings')->where('key', 'kaldik_kepala_nama')->get()->getRowArray();
            $npkKepala     = $db->table('settings')->where('key', 'kaldik_kepala_npk')->get()->getRowArray();
        }

        $agendaKaldik = [];
        if ($tahunAktif) {
            $agendaKaldik = $db->table('academic_calendars ac')
                               ->select('ac.*, mc.category_name, mc.color_hex, ac.category_id')
                               ->join('master_categories mc', 'mc.id = ac.category_id')
                               ->where('ac.academic_year_id', $tahunAktif['id'])
                               ->where('ac.class_id', $kelasTerpilih)
                               ->orderBy('ac.start_date', 'ASC')
                               ->get()
                               ->getResultArray();
        }

        $data = [
            'tahunAktif'    => $tahunAktif,
            'kelasTerpilih' => $kelasTerpilih,
            'daftarKelas'   => $daftarKelas,
            'daftarWarna'   => $daftarWarna,
            'agendaKaldik'  => $agendaKaldik,
            'namaMadrasah'  => $namaMadrasah ? $namaMadrasah['value'] : 'MIMHa Tsanawiyah Informatika', 
            'titiMangsa'    => $titiMangsa ? $titiMangsa['value'] : 'Bandung, 02 Januari 2026',
            'kepalaNama'    => $kepalaSekolah ? $kepalaSekolah['value'] : 'Yana Purnama, S.Pd',
            'kepalaNpk'     => $npkKepala ? $npkKepala['value'] : '3912390046098'
        ];

        // KITA BISA MENGGUNAKAN VIEW PRINT MILIK ADMIN KARENA ITU MURNI HANYA TAMPILAN CETAK
        return view('admin/kaldik_print_view', $data);
    }

}
