<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AntiBotFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $db = \Config\Database::connect();
        
        // Ambil IP Address dari pengunjung saat ini
        $ip = $request->getIPAddress();

        // Tentukan jendela waktu: 15 menit ke belakang dari sekarang
        $waktuMundur = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        // Hitung berapa kali IP ini GAGAL login dalam 15 menit terakhir
        $jumlahGagal = $db->table('auth_logins')
                          ->where('ip_address', $ip)
                          ->where('success', 0) // Kunci: Hanya hitung yang gagal
                          ->where('date >=', $waktuMundur)
                          ->countAllResults();

        // Jika mencapai 5 kali kegagalan, lakukan pemblokiran!
        if ($jumlahGagal >= 100) {
            // Mengembalikan status 429 (Too Many Requests) dan tampilan layar peringatan
            return \Config\Services::response()
                ->setStatusCode(429, 'Too Many Requests')
                ->setBody('
                    <div style="font-family:sans-serif; text-align:center; padding:40px; color:#842029; background-color:#f8d7da; border:2px solid #f5c2c7; border-radius:10px; margin:10% auto; max-width:600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <h1 style="margin-top:0; font-size:48px;">🛑</h1>
                        <h2 style="margin-top:0;">Akses Diblokir Sementara</h2>
                        <p>Sistem keamanan SiKuMi mendeteksi terlalu banyak percobaan login yang gagal dari perangkat/IP Anda (indikasi serangan <i>Brute-Force</i>).</p>
                        <p style="background:#fff; padding:10px; border-radius:5px; font-weight:bold; color:#000;">
                            Silakan coba kembali setelah 15 menit.
                        </p>
                    </div>
                ');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu aksi apapun setelah halaman dimuat
    }
}