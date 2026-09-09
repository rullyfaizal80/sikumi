<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Tampilan Layar Maintenance
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pemeliharaan Sistem - SiKuMi</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <style>
                body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
                .card-maintenance { max-width: 550px; border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
            </style>
        </head>
        <body>
            <div class="card card-maintenance p-4 p-md-5 text-center bg-white">
                <div class="fs-1 mb-2">🚀</div>
                <h3 class="fw-bold text-dark mb-2">Website Dalam Pemeliharaan</h3>
                <span class="badge bg-warning text-dark px-3 py-2 fs-6 mb-3 mx-auto" style="width:fit-content;">Proses Migrasi Hosting</span>
                <p class="text-secondary small mb-4">
                    Aplikasi <strong>SiKuMi</strong> saat ini sedang dalam proses migrasi ke server hosting baru untuk performa yang lebih optimal.
                </p>
                <div class="alert alert-light border small text-muted mb-0">
                    Mohon tunggu beberapa saat. Terima kasih atas kesabaran Anda.
                </div>
            </div>
        </body>
        </html>
        ';

        return \Config\Services::response()->setStatusCode(503)->setBody($html);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak diperlukan
    }
}