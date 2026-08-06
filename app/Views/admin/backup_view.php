<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Backup Database</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1000px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #28a745; font-weight: 700;">💾 BACKUP DATABASE</h3>
                <p class="text-muted small mb-0">Unduh salinan seluruh data sistem SiKuMi Anda untuk keamanan dan pemulihan.</p>
            </div>
            <div>
                <!-- Sesuaikan link href di bawah ini dengan URL Dashboard Admin Bapak -->
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Content Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0" style="font-weight: 600;">Pencadangan Sistem (Export SQL)</h5>
            </div>
            
            <div class="card-body">
                <div class="alert alert-info border-0 shadow-sm mb-4" style="background-color: #e8f4fd; color: #0c5460;">
                    <h5 class="font-weight-bold"><i class="icon fas fa-info-circle"></i> Informasi Penting!</h5>
                    <ul class="mb-0 pl-4" style="font-size: 14px;">
                        <li class="mb-1">Proses ini akan mengekspor seluruh <b>struktur tabel</b> beserta <b>isi data</b> dari sistem SiKuMi Bapak saat ini.</li>
                        <li class="mb-1">File hasil unduhan akan berekstensi <strong>.sql</strong>.</li>
                        <li class="mb-1">Pastikan Anda menyimpan file cadangan ini di tempat yang aman (seperti Google Drive atau Flashdisk).</li>
                        <li>Sangat disarankan untuk melakukan <i>backup</i> secara berkala, terutama pada akhir semester atau setelah ada pembaruan data dalam jumlah besar.</li>
                    </ul>
                </div>

                <div class="text-center py-5">
                    <h5 class="text-dark font-weight-bold mb-2">Siap untuk mengamankan data Anda?</h5>
                    <p class="text-muted mb-4 small">Klik tombol di bawah ini untuk memproses dan mengunduh cadangan database SiKuMi.</p>
                    
                    <!-- Tombol Download Backup mengarah ke Route yang sudah dibuat sebelumnya -->
                    <a href="<?= base_url('backup-database') ?>" class="btn btn-success btn-lg font-weight-bold shadow-sm px-4 py-2" onclick="return confirm('Apakah Anda yakin ingin mengunduh backup database sekarang? Proses ini mungkin memerlukan waktu beberapa saat.')">
                        <i class="fas fa-download mr-2"></i> Mulai Unduh Backup
                    </a>
                </div>
            </div>
            
            <div class="card-footer bg-white text-center py-3">
                <small class="text-muted">SiKuMi - Kurikulum Berbasis Cinta</small>
            </div>
        </div>

    </div>
</body>
</html>