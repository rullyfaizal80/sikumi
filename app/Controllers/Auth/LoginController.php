<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class LoginController extends BaseController
{
    /**
     * 1. MENAMPILKAN HALAMAN FORM LOGIN
     */
    public function loginView()
    {
        // Jika user sudah login, langsung lempar ke halaman utama (rute bernama 'dashboard')
        if (auth()->loggedIn()) {
            return redirect()->to(route_to('dashboard'));
        }

        // Menampilkan view login yang Anda atur di Config/Auth.php
        return view('Shield/login');
    }

    /**
     * 2. MEMPROSES DATA SAAT USER KLIK TOMBOL LOGIN
     */
    public function loginAction()
    {
        // Jika ada session tersisa, paksa logout dulu agar tidak error LogicException
        if (auth()->loggedIn()) {
            auth()->logout();
        }

        // Ambil data input email/username dan password dari form login
        $credentials = $this->request->getPost(setting('Auth.validFields'));
        $credentials = array_filter($credentials);
        $credentials['password'] = $this->request->getPost('password');

        // Jalankan proses validasi dan autentikasi login dari Shield
        $attempt = auth()->attempt($credentials);

        // Jika login gagal, kembalikan ke halaman login dengan pesan error
        if (! $attempt->isOK()) {
            return redirect()->back()->with('error', $attempt->reason());
        }

        // Jika login sukses, arahkan ke halaman utama menggunakan nama rutenya
        return redirect()->to(route_to('dashboard'));
    }
}
