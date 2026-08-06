<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Restore Database</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 800px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #dc3545; font-weight: 700;">⚠️ RESTORE DATABASE</h3>
                <p class="text-muted small mb-0">Pulihkan sistem dari file cadangan (.sql).</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Alert Notifikasi -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger font-weight-bold">
                <i class="fas fa-times-circle"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success font-weight-bold">
                <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <!-- Content Card -->
        <div class="card shadow-sm border-0 border-danger">
            <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="font-weight: 600;"><i class="fas fa-exclamation-triangle"></i> ZONA BERBAHAYA</h5>
                <span class="badge badge-light text-danger"><i class="fas fa-download"></i> Auto-Download Backup Aktif</span>
            </div>
            
            <div class="card-body">
                <div class="alert alert-warning border-0 shadow-sm mb-4" style="color: #856404; background-color: #fff3cd;">
                    <h5 class="font-weight-bold">PERINGATAN!</h5>
                    <p class="mb-2">Melakukan <i>restore</i> akan <b>MENGHAPUS SELURUH DATA</b> yang ada di sistem saat ini dan menggantinya dengan data dari file cadangan yang Anda unggah.</p>
                    <hr style="border-color: #ffe8a1;">
                    <p class="mb-0 small text-success font-weight-bold">
                        <i class="fas fa-info-circle"></i> Transparansi Keamanan: Sebagai tindakan pencegahan, sistem akan <b>mengunduh backup database Anda saat ini secara otomatis ke folder "Downloads" di komputer Anda</b> tepat sebelum proses restore berjalan.
                    </p>
                </div>

                <!-- KEMBALI MENGGUNAKAN 'test-restore' SEPERTI SEMULA -->
                <form id="formRestore" action="<?= base_url('test-restore') ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <div class="form-group mb-4">
                        <label class="font-weight-bold">1. Pilih File Backup (.sql)</label>
                        <input type="file" name="file_sql" class="form-control p-1" accept=".sql" required>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-danger">2. Masukkan Password Akun Anda</label>
                        <input type="password" name="password_konfirmasi" class="form-control" placeholder="Ketik password login Anda untuk verifikasi" required>
                    </div>

                    <div class="form-group mb-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="checkPaham" required>
                            <label class="custom-control-label font-weight-bold text-danger" for="checkPaham" style="cursor:pointer;">
                                Ya, saya sadar dan menyetujui proses penimpaan (restore) database ini.
                            </label>
                        </div>
                    </div>

                    <hr>
                    <div class="text-right">
                        <button type="submit" class="btn btn-danger font-weight-bold px-4">
                            <i class="fas fa-upload"></i> Eksekusi Restore
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    <!-- SCRIPT AKTIF: Fitur download otomatis dan upload berjalan -->
    <script>
        document.getElementById('formRestore').addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            if(confirm('Peringatan Terakhir: File backup saat ini akan diunduh, lalu data di sistem akan ditimpa. Lanjutkan?')) {
                
                // 1. Pancing download di "background / tab baru" agar tidak mengacaukan URL utama
                window.open('<?= base_url("backup-database") ?>', '_blank');
                
                // 2. Beri jeda 1 detik, lalu eksekusi form restore
                let formElement = this;
                setTimeout(function() {
                    formElement.submit();
                }, 1000);
            }
        });
    </script>
</body>
</html>