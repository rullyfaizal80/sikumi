<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Pengaturan Akun</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="p-4 bg-light">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">⚙️ Pengaturan Akun Saya</h3>
                <p class="text-muted small mb-0">Kelola informasi profil, integrasi API Llama 3.3, dan keamanan password Anda.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">🏠 Dashboard</a>
            </div>
        </div>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
                <h5><i class="icon fas fa-check-circle mr-2"></i> Sukses!</h5>
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
                <h5><i class="icon fas fa-ban mr-2"></i> Gagal!</h5>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            
            <div class="col-md-7">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="card-title font-weight-bold text-primary mb-0">
                            <i class="fas fa-id-card mr-2"></i> Profil & Integrasi Groq AI
                        </h4>
                    </div>
                    <form action="<?= base_url('profile/update') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label class="font-weight-bold">Alamat Email Akun</label>
                                <input type="email" class="form-control bg-light" value="<?= esc($user['email']) ?>" disabled>
                                <small class="text-muted italic small">Email terikat pada sistem utama autentikasi dan tidak dapat diubah.</small>
                            </div>

                            <div class="form-group">
                                <label for="username" class="font-weight-bold">Nama Lengkap & Gelar</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= esc($user['username']) ?>" required placeholder="Contoh: Nama Guru, S.Pd.">
                            </div>

                            <hr class="my-4">

                            <div class="p-3 mb-3 bg-light border-left border-warning rounded shadow-sm">
                                <h5 class="text-warning font-weight-bold mb-2">
                                    <i class="fas fa-lightbulb mr-2"></i> Pengaturan Token AI Mandiri
                                </h5>
                                <p class="small text-secondary mb-2">
                                    Untuk menggunakan fitur layanan SiKuMi AI, Anda wajib memasukkan token API Key milik sendiri yang didapatkan secara gratis dari platform Groq.
                                </p>
                                <a href="https://console.groq.com/keys" target="_blank" class="btn btn-xs btn-dark font-weight-bold">
                                    <i class="fas fa-external-link-alt mr-1"></i> Buka Groq Console (Gratis)
                                </a>
                            </div>

                            <div class="form-group">
                                <label for="api_key_ai" class="font-weight-bold">Groq API Key Anda</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_key_ai" name="api_key_ai" placeholder="gsk_xxxxxxxxxxxxxxxxxxxxxxxx" value="<?= esc($user['api_key_ai'] ?? '') ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                            <i class="fas fa-eye" id="eyeIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-danger font-weight-bold small">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Jika kolom ini dikosongkan, Anda tidak akan dapat menggunakan fitur layanan AI.
                                </small>
                            </div>

                        </div>
                        <div class="card-footer bg-white text-right">
                            <button type="submit" class="btn btn-primary font-weight-bold px-4">
                                <i class="fas fa-save mr-1"></i> Simpan Data Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card card-outline card-danger shadow-sm">
                    <div class="card-header bg-white">
                        <h4 class="card-title font-weight-bold text-danger mb-0">
                            <i class="fas fa-shield-alt mr-2"></i> Keamanan Sandi Akun
                        </h4>
                    </div>
                    <form action="<?= base_url('profile/update-password') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="card-body">
                            
                            <div class="form-group">
                                <label for="old_password" class="font-weight-bold">Password Saat Ini</label>
                                <input type="password" class="form-control" id="old_password" name="old_password" required placeholder="Sandi lama Anda">
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="font-weight-bold">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required placeholder="Minimal 8 karakter">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="font-weight-bold">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Ulangi password baru">
                            </div>

                        </div>
                        <div class="card-footer bg-white text-right">
                            <button type="submit" class="btn btn-danger font-weight-bold px-4">
                                <i class="fas fa-key mr-1"></i> Perbarui Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <script>
    // FIX TOMBOL CLOSE ALERT (X) MANUVAL VANILLA JS
    document.querySelectorAll('.alert .close, .alert .btn-close').forEach(function(button) {
        button.addEventListener('click', function() {
            const alertBox = this.closest('.alert');
            if (alertBox) {
                alertBox.remove(); 
            }
        });
    });

    // SWITCHER SHOW/HIDE API KEY
    document.getElementById('toggleApiKey').addEventListener('click', function() {
        const apiKeyInput = document.getElementById('api_key_ai');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (apiKeyInput.type === 'password') {
            apiKeyInput.type = 'text';
            eyeIcon.className = 'fas fa-eye-slash';
        } else {
            apiKeyInput.type = 'password';
            eyeIcon.className = 'fas fa-eye';
        }
    });
    </script>
</body>
</html>