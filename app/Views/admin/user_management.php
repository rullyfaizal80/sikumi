<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Admin Panel</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper p-4">
        <div class="container-fluid">
            
            <!-- Header Halaman -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 style="color: #FF9F00;">⚙️ Ruang Kontrol Utama <span style="color: #FFC107;">SiKuMi</span></h3>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">⬅️ Kembali ke Dashboard</a>
            </div>

            <div class="row">
                <!-- KIRI: Tabel Daftar Guru & Jabatannya -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-top border-warning">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 font-weight-bold">👤 Manajemen Akun Guru & Staf</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Jabatan Aktif</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                   <?php foreach ($users as $user): ?>
<tr>
    <td><?= $user->id ?></td>
    <td><strong><?= $user->username ?></strong></td>
    <td><?= $user->email ?></td>
    <td>
        <!-- KODE BARU: Membaca array siap pakai dari Controller, bebas dari query DB -->
        <?php if (isset($peranUser[$user->id])): ?>
            <strong><?= implode(', ', $peranUser[$user->id]) ?></strong>
        <?php else: ?>
            <span class="badge bg-secondary">Belum Diatur</span>
        <?php endif; ?>
    </td>
    <td>
        <button class="btn btn-primary btn-xs">✏️ Ubah Jabatan</button>
    </td>
</tr>
<?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- KANAN: Panel Ringkas Tambah Peran Baru (Database Dinamis) -->
                <div class="col-md-4">
                    <div class="card shadow-sm border-top border-warning mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 font-weight-bold">➕ Tambah Peran (Role) Baru</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?= current_url() ?>" method="POST">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Kode Peran (Huruf kecil & tanpa spasi)</label>
                                    <input type="text" class="form-control form-control-sm" placeholder="contoh: wali_kelas" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted">Nama Jabatan Resmi</label>
                                    <input type="text" class="form-control form-control-sm" placeholder="contoh: Wali Kelas" required>
                                </div>
                                <button type="button" class="btn btn-warning btn-sm text-white w-100">💾 Simpan Peran Baru</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
