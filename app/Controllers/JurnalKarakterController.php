<?php

namespace App\Controllers;

class JurnalKarakterController extends BaseController
{
    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        
        // Ambil filter periode (default bulan & tahun sekarang)
        $bulan = $this->request->getGet('bulan') ?? date('m');
        $tahun = $this->request->getGet('tahun') ?? date('Y');

        // 1. Ambil data Jurnal Keputraan
        $jurnalKeputraan = $db->table('jurnal_keputraan')
                             ->where('MONTH(tanggal)', $bulan)
                             ->where('YEAR(tanggal)', $tahun)
                             ->orderBy('tanggal', 'ASC')
                             ->get()->getResultArray();

        // 2. Ambil data Jurnal Keputrian
        $jurnalKeputrian = $db->table('jurnal_keputrian')
                             ->where('MONTH(tanggal)', $bulan)
                             ->where('YEAR(tanggal)', $tahun)
                             ->orderBy('tanggal', 'ASC')
                             ->get()->getResultArray();

        // 3. Ambil daftar user yang HANYA berprofesi sebagai GURU[cite: 1, 2]
        $daftarGuru = $db->table('users u')
                          ->select('u.username')
                          ->join('teacher_profiles tp', 'tp.user_id = u.id')
                          ->orderBy('u.username', 'ASC')
                          ->get()->getResultArray();

        $data = [
            'title'           => 'Jurnal Keputraan & Keputrian',
            'bulan'           => sprintf('%02d', $bulan),
            'tahun'           => $tahun,
            'jurnalKeputraan' => $jurnalKeputraan,
            'jurnalKeputrian' => $jurnalKeputrian,
            'daftarGuru'      => $daftarGuru
        ];

        return view('guru/karakter/jurnal', $data);
    }

    public function saveJurnal()
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $post = $this->request->getPost();

        $jenis   = $post['jenis']; // 'keputraan' atau 'keputrian'
        $tabel   = ($jenis == 'keputrian') ? 'jurnal_keputrian' : 'jurnal_keputraan';
        $tanggal = $post['tanggal'];

        $data = [
            'tanggal'       => $tanggal,
            'waktu'         => $post['waktu'] ?? '',
            'tempat'        => $post['tempat'] ?? '',
            'materi'        => $post['materi'] ?? '',
            'pemateri'      => $post['pemateri'] ?? '',
            'kendala'       => $post['kendala'] ?? '',
            'tindak_lanjut' => $post['tindak_lanjut'] ?? ''
        ];

        if (!empty($post['id'])) {
            $db->table($tabel)->where('id', $post['id'])->update($data);
            $msg = "Jurnal " . ucfirst($jenis) . " berhasil diperbarui.";
        } else {
            $db->table($tabel)->insert($data);
            $msg = "Jurnal " . ucfirst($jenis) . " berhasil ditambahkan.";
        }

        $bulan = date('m', strtotime($tanggal));
        $tahun = date('Y', strtotime($tanggal));

        return redirect()->to("guru/jurnal-karakter?bulan={$bulan}&tahun={$tahun}")->with('success', $msg);
    }

    public function deleteJurnal($jenis, $id)
    {
        if (!auth()->loggedIn()) return redirect()->to('login');

        $db = \Config\Database::connect();
        $tabel = ($jenis == 'keputrian') ? 'jurnal_keputrian' : 'jurnal_keputraan';
        
        $jurnal = $db->table($tabel)->where('id', $id)->get()->getRowArray();
        
        if ($jurnal) {
            $bulan = date('m', strtotime($jurnal['tanggal']));
            $tahun = date('Y', strtotime($jurnal['tanggal']));
            
            $db->table($tabel)->where('id', $id)->delete();
            return redirect()->to("guru/jurnal-karakter?bulan={$bulan}&tahun={$tahun}")
                             ->with('success', 'Data jurnal berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }
}