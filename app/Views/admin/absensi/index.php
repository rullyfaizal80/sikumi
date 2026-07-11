<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Pilih Kelas Absensi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1000px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">🏫 Pilih Rombel / Kelas</h3>
                <p class="text-muted small mb-0">Silakan pilih kelas untuk menginput atau melihat rekap absensi.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Flash Message Error (Jika Tahun Ajaran Kosong) -->
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Daftar Kelas -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0" style="font-weight: 600;">Daftar Kelas Tersedia</h5>
            </div>
            
            <div class="card-body p-0">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Kelas / Rombel</th>
                            <th class="text-center" style="width: 300px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarRombel)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Data kelas belum tersedia di Tahun Ajaran ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($daftarRombel as $rombel): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                <td>
                                    <strong><?= esc($rombel['rombel_name'] ?? 'Nama Kelas') ?></strong>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <!-- Tombol Menuju Form Input Absen -->
                                        <a href="<?= base_url('admin/absensi/input/' . esc($rombel['id'])) ?>" class="btn btn-sm btn-warning text-white font-weight-bold" style="margin-right: 5px;">
                                            📝 Input
                                        </a>
                                        
                                        <!-- Tombol Menuju Rekap Absensi (Otomatis membawa ID Kelas) -->
                                        <a href="<?= base_url('admin/absensi/rekap?rombel_id=' . esc($rombel['id'])) ?>" class="btn btn-sm btn-info text-white font-weight-bold">
                                            📊 Rekap
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>