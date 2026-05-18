<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Google\Client as GoogleClient;
use CodeIgniter\Shield\Models\UserModel;

class GoogleAuthController extends BaseController
{
    // Pastikan dua baris kunci asli Anda dari Google Cloud Console terpasang di sini
    private string $clientId     = '194621511921-8644a22nf7vfglrqpmpnpb3au5ki9i7j.apps.googleusercontent.com';
    private string $clientSecret = 'CGOCSPX-jK9h2FHD8sEz0Z-YjfHHUpTO9aje';

    public function redirectToGoogle()
    {
        $client = new GoogleClient();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri(base_url('auth/google/callback'));
        
        $client->addScope('email');
        $client->addScope('profile');

        return redirect()->to($client->createAuthUrl());
    }

    public function handleCallback()
    {
        $client = new GoogleClient();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri(base_url('auth/google/callback'));

        // 1. PASTIKAN GOOGLE MENGIRIMKAN KODE Otentikasi KEMBALI KEMARI
        if ($this->request->getGet('code')) {
            try {
                $emailGoogle = null;

                // =====================================================================
                // A. JALUR LOKAL MAC / LOCALHOST (Bypass Hambatan Enkripsi Non-SSL)
                // =====================================================================
                if (str_contains(base_url(), 'localhost')) {
                    // PENTING: Silakan ketik alamat email asli sekolah yang sudah Anda masukkan
                    // ke dalam tabel user database kemarin untuk disimulasikan secara ketat.
                    $emailGoogle = 'waka@mimha.sch.id'; 
                } 
                // =====================================================================
                // B. JALUR ONLINE HOSTING PRODUCTION (Otomatis Aktif Pakai Sensor Google Asli)
                // =====================================================================
                else {
                    $token = $client->fetchAccessTokenWithAuthCode($this->request->getGet('code'));
                    if (isset($token['id_token'])) {
                        $payload = $client->verifyIdToken($token['id_token']);
                        if ($payload) {
                            $emailGoogle = $payload['email'] ?? null;
                        }
                    }
                    if (empty($emailGoogle) && isset($token['access_token'])) {
                        $client->setAccessToken($token);
                        $googleService = new \Google\Service\Oauth2($client);
                        $userInfo = $googleService->userinfo->get();
                        $emailGoogle = $userInfo->email;
                    }
                }

                // =====================================================================
                // SENSOR UTAMA SIKUMI: SENSOR VALIDASI DATABASE MUTLAK (KUNCI GERBANG)
                // =====================================================================
                if (!empty($emailGoogle)) {
                    // Paksa konversi ke huruf kecil semua untuk menghindari salah ketik kapital
                    $emailValid = strtolower(trim($emailGoogle));

                    $userModel = model(UserModel::class);
                    // Cari secara presisi ke tabel user database kustom Anda
                    $user = $userModel->findByCredentials(['email' => $emailValid]);

                    if ($user) {
                        // JIKA COCOK & TERDAFTAR DI DATABASE ADMIN: Loloskan masuk dashboard
                        auth()->login($user);
                        return redirect()->to('/');
                    } else {
                        // JIKA TIDAK TERDAFTAR: Tolak mentah-mentah
                        return redirect()->to('login')->with('error', 'Akses Ditolak! Alamat email Google Sekolah "' . $emailValid . '" belum didaftarkan oleh Admin SiKuMi.');
                    }
                } else {
                    return redirect()->to('login')->with('error', 'Gagal membaca profil email dari server Google local. Silakan coba kembali.');
                }

            } catch (\Exception $e) {
                return redirect()->to('login')->with('error', 'Gagal memproses otentikasi keamanan Google: ' . $e->getMessage());
            }
        }

        return redirect()->to('login');
    }


}

    // Jangan dihapus Token untuk login SSO Google
    // private string $clientId     = '194621511921-8644a22nf7vfglrqpmpnpb3au5ki9i7j.apps.googleusercontent.com';
    // private string $clientSecret = 'CGOCSPX-jK9h2FHD8sEz0Z-YjfHHUpTO9aje';
