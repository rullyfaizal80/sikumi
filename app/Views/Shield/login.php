<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Masuk Aplikasi</title>
    <!-- Pemanggilan Seluruh Aset CSS Secara Lokal (Instant Tanpa Loading) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        /* Sentuhan Estetika Bersih Meniru Ekosistem Keuangan MIMHa */
        body { 
            background-color: #f4f6f9 !important; 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-box {
            width: 400px;
            max-width: 100%;
            padding: 20px;
        }
        .card-login {
            border-radius: 8px;
            border: 1px solid #dee2e6 !important;
            background-color: #ffffff;
        }
        .btn-warning-custom {
            background-color: #FF9F00 !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .btn-warning-custom:hover {
            background-color: #e68f00 !important;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="card card-login shadow-sm p-4">
        
        <!-- SISI ATAS: BRANDING LOGO & NAMA LEMBAGA MERDEKA -->
        <div class="text-center mb-4">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo MIMHa" class="mb-2" style="height: 65px; width: auto; object-fit: contain;">
            <h5 class="text-dark mb-1" style="font-weight: 700; letter-spacing: -0.5px;">Sistem Kurikulum MIMHa</h5>
            <span class="text-muted small">(SiKuMi)</span>
        </div>

        <!-- Notifikasi Pesan Kesalahan / Peringatan Sistem -->
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger p-2 small border-0 shadow-sm" role="alert">
                ❌ <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif ?>

        <!-- FORMULIR MASUK KONVENSIONAL (STANDARD SHIELD AUTH) -->
        <form action="<?= url_to('login') ?>" method="POST">
            <?= csrf_field() ?>

            <!-- Input Alamat Email -->
            <div class="mb-3">
                <label class="form-label small font-weight-bold text-muted mb-1">Alamat Email Guru</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control ps-2" placeholder="contoh: rully@mimha.sch.id" value="<?= old('email') ?>" required autofocus autocomplete="email">
                </div>
            </div>

            <!-- Input Kata Sandi -->
            <div class="mb-3">
                <label class="form-label small font-weight-bold text-muted mb-1">Kata Sandi (Password)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control ps-2" placeholder="Masukkan kata sandi Anda" required autocomplete="current-password">
                </div>
            </div>

            <!-- Fitur Ingat Saya (Remember Me) -->
            <div class="form-check mb-3 small">
                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" <?= old('remember') ? 'checked' : '' ?>>
                <label class="form-check-label text-muted" for="rememberMe">Ingat sesi masuk saya</label>
            </div>

            <!-- Tombol Submit Form Konvensional -->
            <button type="submit" class="btn btn-sm btn-warning-custom w-100 py-2 shadow-sm rounded mb-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sistem
            </button>
        </form>

        <!-- ======================================================== -->
        <!-- POSISI PRESISI TOMBOL SSO GOOGLE REKOMENDASI ANDA -->
        <!-- ======================================================== -->
<!--        
        <div class="mt-3 text-center">
            <div class="text-muted small mb-2" style="font-size: 11px; font-weight: 600;">- ATAU -</div>
            <a href="<?= base_url('auth/google') ?>" class="btn btn-sm btn-outline-dark w-100 d-flex align-items-center justify-content-center shadow-sm py-2" style="border-radius: 4px; font-size: 13px; font-weight: 600;">
                <i class="bi bi-google text-danger me-2" style="font-size: 14px;"></i> Masuk dengan Akun Google Sekolah
            </a>
        </div>

    </div>
</div>
-->
<!-- Pemanggilan Seluruh Aset JavaScript Secara Lokal -->
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

</body>
</html>
