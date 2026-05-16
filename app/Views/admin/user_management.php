<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Admin Panel</title>
    <!-- Hubungkan ke file CSS AdminLTE 4 lokal Mac Anda -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        
        <!-- HEADER / NAVBAR ATAS -->
        <nav class="app-header navbar navbar-expand bg-body shadow-sm">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <h4 class="navbar-text my-0 ps-2" style="color: #FF9F00; font-weight: 700;">
                            ⚙️ Ruang Kontrol Utama <span style="color: #FFC107;">SiKuMi</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ps-2">
                        <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary">
                            ⬅️ Kembali ke Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- KONTEN UTAMA (Satu Layar Penuh) -->
        <main class="app-main pt-4">
            <div class="app-content">
                <div class="container-fluid">

                    <!-- Notifikasi Sukses / Gagal Berhasil Ditambahkan -->
                    <?php if (session()->getFlashdata('sukses')): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <!-- KIRI: Tabel Daftar Guru -->
                        <div class="col-lg-8">
                            <div class="card shadow-sm h-100 border-top border-warning border-3">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title mb-0" style="font-weight: 600;">👤 Manajemen Akun Guru & Staf</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0 align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th class="ps-3">ID</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Jabatan Aktif</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td class="ps-3"><?= $user->id ?></td>
                                                    <td><strong><?= $user->username ?></strong></td>
                                                    <td><?= $user->email ?></td>
                                                    <td>
                                                        <?php if (isset($peranUser[$user->id])): ?>
                                                            <span class="badge bg-warning text-dark px-2 py-1">
                                                                <?= implode(', ', $peranUser[$user->id]) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary px-2 py-1">Belum Diatur</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" 
        class="btn btn-primary btn-sm px-3 shadow-sm btn-ubah-jabatan" 
        data-bs-toggle="modal" 
        data-bs-target="#modalUbahJabatan"
        data-id="<?= $user->id ?>"
        data-username="<?= $user->username ?>"
        data-roles='<?= isset($peranUser[$user->id]) ? json_encode($peranUser[$user->id]) : json_encode([]) ?>'>
    ✏️ Ubah Jabatan
</button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KANAN: Form Tambah Peran Baru -->
                        <div class="col-lg-4">
                            <div class="card shadow-sm border-top border-warning border-3">
                                <div class="card-header bg-white py-3">
                                    <h5 class="card-title mb-0" style="font-weight: 600;">➕ Tambah Peran (Role) Baru</h5>
                                </div>
                                <div class="card-body">
                                    <!-- Form diarahkan ke fungsi simpan di Controller -->
                                    <form action="<?= base_url('admin/roles/store') ?>" method="POST">
                                        <?= csrf_field() ?> <!-- Fitur Pengaman Token CSRF -->
                                        
                                        <div class="mb-3">
                                            <label class="form-label text-muted small" style="font-weight: 600;">Kode Peran (Huruf kecil & tanpa spasi)</label>
                                            <input type="text" name="role_name" class="form-control" placeholder="contoh: wali_kelas" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small" style="font-weight: 600;">Nama Jabatan Resmi</label>
                                            <input type="text" name="role_title" class="form-control" placeholder="contoh: Wali Kelas" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning w-100 text-white shadow-sm" style="font-weight: 600; background-color: #FF9F00; border: none;">
                                            💾 Simpan Peran Baru
                                        </button>
                                    </form>
                                    <!-- Tambahkan ini tepat di bawah penutup </form> pada sisi kanan -->
<hr class="my-4">
<h6 class="mb-3" style="font-weight: 600; color: #FF9F00;">📋 Daftar Peran di Sistem Saat Ini:</h6>
<div class="list-group shadow-sm">
    <?php foreach ($roles as $r): ?>
        <div class="list-group-item d-flex justify-content-between align-items-center bg-light-subtle">
            <div>
                <strong class="text-dark d-block small"><?= $r['role_title'] ?></strong>
                <span class="text-muted" style="font-size: 11px;">Kode: <code><?= $r['role_name'] ?></code></span>
            </div>
            <span class="badge bg-secondary rounded-pill small" style="font-size: 10px;">Aktif</span>
        </div>
    <?php endforeach; ?>
</div>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- JENDELA POP-UP (MODAL) UBAH JABATAN GURU -->
<div class="modal fade" id="modalUbahJabatan" tabindex="-1" aria-labelledby="modalUbahJabatanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="modalUbahJabatanLabel">✏️ Atur Jabatan Guru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form diarahkan ke fungsi update di Controller -->
            <form action="<?= base_url('admin/users/update-roles') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="text-muted small">Tentukan peran dan hak akses untuk akun guru berikut di bawah ini:</p>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold mb-0">Nama Pengguna (Username):</label>
                        <h6 id="modal-username-text" class="text-warning font-weight-bold"></h6>
                        <!-- Input tersembunyi untuk mengirimkan ID User ke backend -->
                        <input type="hidden" name="user_id" id="modal-user-id">
                    </div>
                    
                    <label class="form-label font-weight-bold mb-2">Pilih Jabatan Sekolah (Bisa Centang Lebih dari 1):</label>
                    <div class="p-3 bg-light rounded border">
                        <?php foreach ($roles as $r): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input checkbox-role" 
                                       type="checkbox" 
                                       name="roles[]" 
                                       value="<?= $r['role_name'] ?>" 
                                       id="role_<?= $r['id'] ?>"
                                       data-title="<?= $r['role_title'] ?>">
                                <label class="form-check-label font-weight-bold" for="role_<?= $r['id'] ?>">
                                    <?= $r['role_title'] ?> <span class="text-muted small">(<code><?= $r['role_name'] ?></code>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm text-white font-weight-bold" style="background-color: #FF9F00; border: none;">
                        💾 Simpan Perubahan Jabatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

 <!-- ============================================================= -->
    <!-- URUTAN PEMANGGILAN SCRIPT WAJIB SEPERTI INI AGAR POP-UP AKTIF -->
    <!-- ============================================================= -->
    
    <!-- 1. Pustaka Utama Bootstrap JS Lokal (Wajib Paling Atas) -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>

    <!-- 2. Pustaka Pendukung Desain AdminLTE 4 Lokal -->
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <!-- 3. Skrip Otomatis Penyuntik Data Guru ke Dalam Pop-Up Modal -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tombolUbah = document.querySelectorAll('.btn-ubah-jabatan');
            
            tombolUbah.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    const userCurrentRoles = JSON.parse(this.getAttribute('data-roles'));

                    document.getElementById('modal-user-id').value = userId;
                    document.getElementById('modal-username-text').textContent = username;

                    const checkboxes = document.querySelectorAll('.checkbox-role');
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        const roleTitle = cb.getAttribute('data-title');
                        if (userCurrentRoles.includes(roleTitle)) {
                            cb.checked = true;
                        }
                    });
                });
            });
        });
    </script>

</body>
</html>
