<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - SiKuMi MIMHa</title>
    
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    
    <style>
        body { background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Source Sans Pro', sans-serif; }
        .error-card { max-width: 500px; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; text-align: center; border: 1px solid #dee2e6; }
        .error-code { font-size: 72px; font-weight: 800; color: #dc3545; line-height: 1; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="error-card">
        <div class="error-code">403</div>
        <h4 class="fw-bold text-dark mb-3">Waduh! Akses Dilarang</h4>
        <p class="text-muted mb-4">Direktori atau file ini bersifat rahasia dan tidak diizinkan untuk diakses secara manual demi keamanan data madrasah.</p>
        
        <a href="<?= base_url('/') ?>" class="btn btn-warning font-weight-bold px-4 text-white" style="background-color: #FF9F00; border: none;">
            <i class="bi bi-speedometer2 me-1"></i> Kembali ke Dashboard
        </a>
    </div>

</body>
</html>