<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Setelan Akademik</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper p-4">
        <div class="container-fluid ps-2 pe-2">
            
            <!-- HEADER NAVIGASI ATAS -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 style="color: #FF9F00; font-weight: 700;">⚙️ Setelan Tahun Akademik <span style="color: #FFC107;">SiKuMi</span></h3>
                <div>
                    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">⬅️ Ke Dashboard</a>
                </div>
            </div>

            <!-- Notifikasi Sukses Pembaruan Semester -->
            <?php if (session()->getFlashdata('sukses')): ?>
                <div class="alert alert-success shadow-sm mb-4" role="alert">
                    🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                </div>
            <?php endif; ?>

            <!-- TABEL DATA ACUAN WAKTU SEKOLAH -->
            <div class="card shadow-sm border-top border-warning border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0" style="font-weight: 600;">📋 Daftar Tahun Pelajaran & Semester</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-4" style="width: 15%;">ID</th>
                                    <th style="width: 30%;">Tahun Pelajaran</th>
                                    <th style="width: 25%;">Semester</th>
                                    <th style="width: 15%;">Status Operasional</th>
                                    <th class="text-center" style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($academic as $ac): ?>
                                <tr>
                                    <td class="ps-4"><?= $ac['id'] ?></td>
                                    <td><strong><?= $ac['academic_year'] ?></strong></td>
                                    <td><?= $ac['semester'] ?></td>
                                    <td>
                                        <?php if ($ac['is_active'] == 1): ?>
                                            <span class="badge bg-success px-3 py-1 font-weight-bold shadow-sm" style="font-size: 11px;">AKTIF BERJALAN</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-1 text-white-50" style="font-size: 11px;">Non-Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($ac['is_active'] == 0): ?>
                                            <form action="<?= base_url('admin/academic/activate/' . $ac['id']) ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-warning btn-sm font-weight-bold text-white shadow-sm" style="background-color: #FF9F00; border: none; font-size: 11px;">
                                                    ⚡ Aktifkan Semester
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-light btn-sm text-muted font-weight-bold" style="font-size: 11px;" disabled>
                                                🔒 Sedang Digunakan
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>
