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

        $user = $this->db->table('users')
            ->select('users.*, auth_identities.secret as email')
            ->join('auth_identities', 'auth_identities.user_id = users.id AND auth_identities.type = "email_password"', 'left')
            ->where('users.id', $userId)
            ->get()
            ->getRowArray();

        return view('profile/index', ['user' => $user]);
    }

    // Memproses Perubahan Profil & API Key
    public function updateProfile()
    {
        $userId = session()->get('id') ?? session()->get('user_id') ?? user_id();
        $username   = $this->request->getPost('username');
        $apiKeyInput = $this->request->getPost('api_key_ai');

        if (!empty($apiKeyInput) && !str_starts_with($apiKeyInput, 'gsk_')) {
            return redirect()->to('profile')->with('error', 'Format API Key tidak valid. Pastikan diawali dengan "gsk_"');
        }

        $this->db->table('users')
            ->where('id', $userId)
            ->update([
                'username'   => $username,
                'api_key_ai' => $apiKeyInput,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return redirect()->to('profile')->with('success', 'Profil dan API Key Anda berhasil diperbarui!');
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
