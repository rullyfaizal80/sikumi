<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class RestoreController extends BaseController
{
    public function index()
    {
        return view('admin/restore_view');
    }

    public function process()
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();

        // 1. Ambil Input File dan Password
        $file = $request->getFile('file_sql');
        $passwordInput = $request->getPost('password_konfirmasi');

        // 2. OTORISASI: Cek Password User Aktif (CodeIgniter Shield)
        if (!auth()->loggedIn()) {
            return redirect()->back()->with('error', 'Sesi tidak valid. Harap login kembali.');
        }

        $userId = auth()->id();
        
        $identity = $db->table('auth_identities')
                       ->where('user_id', $userId)
                       ->where('type', 'email_password')
                       ->get()
                       ->getRowArray();

        // UBAH 'secret' MENJADI 'secret2'
        if (!$identity || !password_verify($passwordInput, $identity['secret2'])) {
            return redirect()->back()->with('error', 'Password Anda salah! Proses Restore dibatalkan demi keamanan.');
        }

        // 3. VALIDASI FILE
        if (!$file->isValid() || $file->getClientExtension() !== 'sql') {
            return redirect()->back()->with('error', 'File tidak valid. Harap unggah file berekstensi .sql.');
        }

        // 4. PROSES BACA DAN EKSEKUSI FILE .SQL (RESTORE)
        $filepath = $file->getTempName();
        $lines = file($filepath);
        $query = '';

        $db->transStart();
        
        // Matikan pengecekan Foreign Key sementara agar tidak error saat Drop/Insert
        $db->query("SET FOREIGN_KEY_CHECKS = 0;");

       foreach ($lines as $line) {
            // Lewati baris komentar dan baris kosong
            if (substr($line, 0, 2) == '--' || $line == '') {
                continue;
            }

            $query .= $line;

            // Jika menemukan titik koma (;) di akhir baris, eksekusi query tersebut
            if (substr(trim($line), -1, 1) == ';') {
                try {
                    // Jalankan query
                    $run = $db->query($query);
                    
                    // JIKA QUERY GAGAL (Tapi tidak melempar Exception)
                    if (!$run) {
                        $dbError = $db->error();
                        $db->transRollback();
                        return redirect()->back()->with('error', 'Gagal memulihkan database. Error DB: ' . $dbError['message']);
                    }
                } catch (\Exception $e) {
                    $db->transRollback();
                    return redirect()->back()->with('error', 'Gagal memulihkan database. Kesalahan SQL: ' . $e->getMessage());
                }
                $query = ''; // Kosongkan query untuk baris selanjutnya
            }
        }

        // Nyalakan kembali pengecekan Foreign Key
        $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Sistem gagal menyelesaikan proses restore.');
        }

        return redirect()->back()->with('success', 'Sistem SiKuMi berhasil dipulihkan secara penuh dari file cadangan Anda!');
    }
}