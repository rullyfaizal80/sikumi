<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class KktpController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id') ?? 3; // Contoh default admin

        // 1. INFO HEADER & SETTINGS
        $tahunAktif = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $namaMadrasah = $db->table('settings')->where('key', 'kaldik_lembaga_nama')->get()->getRowArray();
        
        // 2. DINAMISASI ROMBEL & MAPEL (Logika sama dengan ATP)
        $daftarRombel = $db->table('class_rombel cr')
                           ->select('cr.id, cr.rombel_name, mc.class_name, mc.id as master_class_id')
                           ->join('master_classes mc', 'mc.id = cr.master_class_id')
                           ->where('cr.academic_year_id', $tahunAktif['id'] ?? 0)
                           ->get()->getResultArray();

        $selectedRombelId = $this->request->getGet('rombel_id') ?? ($daftarRombel[0]['id'] ?? 0);
        $selectedMapelId = $this->request->getGet('mapel_id') ?? 'S_1';

        // 3. LOAD TP & DATA KKTP TERSIMPAN
        $dataKktp = [];
        if (!empty($selectedRombelId)) {
            $builder = $db->table('kurikulum_cp_details d')
                          ->select('d.id, d.tujuan_pembelajaran, d.urutan, k.indikator, k.skor_sangat_baik, k.skor_baik, k.skor_cukup, k.skor_perlu_bimbingan')
                          ->join('kurikulum_cp_headers h', 'h.id = d.header_id')
                          ->join('kurikulum_kktp k', "k.cp_detail_id = d.id AND k.rombel_id = $selectedRombelId", 'left')
                          ->where('h.mapel_id', $selectedMapelId)
                          ->orderBy('d.urutan', 'ASC');
            
            $dataKktp = $builder->get()->getResultArray();
        }

        $data = [
            'tahunAktif' => $tahunAktif,
            'namaMadrasah' => $namaMadrasah['value'] ?? 'MIMHa',
            'daftarRombel' => $daftarRombel,
            'selectedRombelId' => $selectedRombelId,
            'selectedMapelId' => $selectedMapelId,
            'dataKktp' => $dataKktp,
            'tingkatKelas' => '7' // Bisa didinamiskan seperti ATP
        ];

        return view('guru/kktp_manage', $data);
    }

    public function simpan()
    {
        $db = \Config\Database::connect();
        $request = $this->request->getPost();
        $dataRows = json_decode($request['data_kktp'], true);

        foreach ($dataRows as $row) {
            $db->table('kurikulum_kktp')->replace([
                'cp_detail_id' => $row['cp_id'],
                'rombel_id'    => $request['rombel_id'],
                'indikator'    => $row['indikator'],
                'skor_sangat_baik'     => $row['sangat_baik'],
                'skor_baik'            => $row['baik'],
                'skor_cukup'           => $row['cukup'],
                'skor_perlu_bimbingan' => $row['perlu_bimbingan'],
            ]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'KKTP Berhasil disimpan!']);
    }
}