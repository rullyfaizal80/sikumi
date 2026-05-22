<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class UserSiswaController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil Parameter Pencarian & Halaman Aktif dari URL
        $keyword = $this->request->getGet('search') ?? '';
        $page    = (int) ($this->request->getGet('page_siswa') ?? 1);
        if ($page < 1) $page = 1;

        // 2. Batasi 10 baris per halaman agar jendela padat
        $limit = 10; 
        $offset = ($page - 1) * $limit;

        // 3. Query khusus mencari user yang tergabung dalam grup 'siswa'
        $builder = $db->table('users u')
                      ->select('u.id, u.username, u.active, u.status, u.created_at, ai.secret as email')
                      ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                      ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner') // Inner join karena siswa wajib punya grup 'siswa'
                      ->where('u.deleted_at', null)
                      ->where('agu.group', 'siswa'); // KUNCI: Hanya grup siswa

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('u.username', $keyword)
                    ->orLike('ai.secret', $keyword)
                    ->groupEnd();
        }

        // 4. Hitung Total Data Siswa sebelum dipotong limit
        $totalBuilder = clone $builder;
        $totalData = $totalBuilder->countAllResults();

        // 5. Ambil potongan data untuk halaman aktif
        $daftarSiswa = $builder->orderBy('u.username', 'ASC')
                               ->limit($limit, $offset)
                               ->get()
                               ->getResultArray();

        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        $data = [
            'daftarSiswa'  => $daftarSiswa,
            'keyword'      => $keyword,
            'page'         => $page,
            'limit'        => $limit,
            'totalData'    => $totalData,
            'totalHalaman' => $totalHalaman
        ];

        return view('admin/user_siswa_view', $data);
    }

    /**
     * FUNGSI SAKLAR: Mengubah status login Siswa (Banned / Izinkan)
     */
    public function toggleStatus($id)
    {
        $db = \Config\Database::connect();
        
        $user = $db->table('users')->where('id', $id)->get()->getRow();
        
        if ($user) {
            $statusBaru = (strtolower($user->status ?? '') === 'banned') ? null : 'banned';
            
            $db->table('users')
               ->where('id', $id)
               ->update([
                   'status'     => $statusBaru,
                   'updated_at' => date('Y-m-d H:i:s')
               ]);
               
            $pesan = ($statusBaru === 'banned') ? '🔒 Akses login siswa berhasil dibekukan!' : '✅ Akses login siswa telah diizinkan kembali.';
            return redirect()->back()->with('sukses', $pesan);
        }

        return redirect()->back()->with('error', '❌ Siswa tidak ditemukan.');
    }
}