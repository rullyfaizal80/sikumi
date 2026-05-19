<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class KaldikController extends BaseController
{
    // Halaman Utama Pengelolaan & Penampilan Kalender Akademik
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil Tahun Pelajaran & Semester yang berstatus AKTIF saat ini
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();

        // 2. Ambil parameter pilihan filter Kelas dan Jenjang (Default ke Kelas 7 jika belum dipilih)
        $kelasTerpilih = $this->request->getGet('class_id') ?? 1; // ID 1 = Kelas 7

        // 3. Tarik data master komponen untuk dropdown menu pilihan di View
        $daftarKelas = $db->table('master_classes')->get()->getResultArray();
        $daftarWarna = $db->table('master_categories')->get()->getResultArray();

        // 4. Tarik data agenda Kaldik yang sudah diploting berdasarkan tahun aktif dan kelas terpilih
        $agendaKaldik = [];
        if ($tahunAktif) {
            $agendaKaldik = $db->table('academic_calendars ac')
                   ->select('ac.*, mc.category_name, mc.color_hex, ac.category_id') // <-- TAMBAHKAN ac.category_id DI SINI
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
            'agendaKaldik'  => $agendaKaldik
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
}
