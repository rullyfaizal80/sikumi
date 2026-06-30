<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ProfileController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // Menampilkan halaman pengaturan akun (Tunggal)
    public function index()
    {
        $userId = session()->get('id') ?? session()->get('user_id') ?? user_id();
        if (!$userId) {
            return redirect()->to('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Modifikasi: Ambil kolom nip dari tabel teacher_profiles
        $user = $this->db->table('users')
            ->select('users.*, auth_identities.secret as email, teacher_profiles.nip')
            ->join('auth_identities', 'auth_identities.user_id = users.id AND auth_identities.type = "email_password"', 'left')
            ->join('teacher_profiles', 'teacher_profiles.user_id = users.id', 'left')
            ->where('users.id', $userId)
            ->get()
            ->getRowArray();

        return view('profile/index', ['user' => $user]);
    }

    // Memproses Perubahan Profil, NPK, & API Key, serta Upload TTD
    public function updateProfile()
    {
        $userId = session()->get('id') ?? session()->get('user_id') ?? user_id();
        $username   = $this->request->getPost('username');
        $apiKeyInput = $this->request->getPost('api_key_ai');
        $npk         = $this->request->getPost('npk'); // Menangkap input NPK

        if (!empty($apiKeyInput) && !str_starts_with($apiKeyInput, 'gsk_')) {
            return redirect()->to('profile')->with('error', 'Format API Key tidak valid. Pastikan diawali dengan "gsk_"');
        }

        // 1. Update ke tabel users
        $this->db->table('users')
            ->where('id', $userId)
            ->update([
                'username'   => $username,
                'api_key_ai' => $apiKeyInput,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        // 2. Update atau Insert ke tabel teacher_profiles (untuk kolom nip)
        $profileExists = $this->db->table('teacher_profiles')->where('user_id', $userId)->countAllResults() > 0;
        if ($profileExists) {
            $this->db->table('teacher_profiles')
                ->where('user_id', $userId)
                ->update([
                    'nip'        => $npk,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } else {
            $this->db->table('teacher_profiles')
                ->insert([
                    'user_id'    => $userId,
                    'nip'        => $npk,
                    'gender'     => 'L', // Nilai default aman karena gender NOT NULL pada struktur SQL Anda
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }

        // 3. Proses Upload File TTD (Langsung ke direktori tanpa DB)
        $ttdFile = $this->request->getFile('ttd');
        if ($ttdFile && $ttdFile->isValid() && !$ttdFile->hasMoved()) {
            $validationRule = [
                'ttd' => [
                    'label' => 'Tanda Tangan',
                    'rules' => 'uploaded[ttd]|is_image[ttd]|mime_in[ttd,image/png,image/jpg,image/jpeg]',
                ],
            ];

            if ($this->validate($validationRule)) {
                $uploadPath = FCPATH . 'assets/img/';
                
                // Buat folder otomatis jika belum ada di public
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0775, true);
                }

                $fileName = 'ttd_' . $userId . '.png';

                // Menimpa file lama jika eksis
                if (file_exists($uploadPath . $fileName)) {
                    unlink($uploadPath . $fileName);
                }

                $ttdFile->move($uploadPath, $fileName);
            } else {
                return redirect()->to('profile')->with('error', 'Gagal upload TTD. Pastikan format file adalah gambar (PNG/JPG/JPEG).');
            }
        }

        return redirect()->to('profile')->with('success', 'Profil, NPK, dan Tanda Tangan Anda berhasil diperbarui!');
    }

    // Method Baru: Memproses Unduhan File TTD Langsung
    public function downloadTtd()
    {
        $userId = session()->get('id') ?? session()->get('user_id') ?? user_id();
        $path = FCPATH . 'assets/img/ttd_' . $userId . '.png';

        if (file_exists($path)) {
            return $this->response->download($path, null)->setFileName('ttd_' . $userId . '.png');
        }

        return redirect()->to('profile')->with('error', 'File tanda tangan Anda belum diunggah.');
    }

    // Memproses Perubahan Password (Shield Hash Standar)
    public function updatePassword()
    {
        $userId = session()->get('id') ?? session()->get('user_id') ?? user_id();
        $oldPassword     = $this->request->getPost('old_password');
        $newPassword     = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        if (strlen($newPassword) < 8) {
            return redirect()->to('profile')->with('error', 'Password baru minimal harus 8 karakter.');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->to('profile')->with('error', 'Konfirmasi password baru tidak cocok.');
        }

        $identity = $this->db->table('auth_identities')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get()
            ->getRowArray();

        if (!password_verify($oldPassword, $identity['secret2'])) {
            return redirect()->to('profile')->with('error', 'Password lama yang Anda masukkan salah.');
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->table('auth_identities')
            ->where('id', $identity['id'])
            ->update([
                'secret2'    => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return redirect()->to('profile')->with('success', 'Password Anda berhasil diubah!');
    }
}
