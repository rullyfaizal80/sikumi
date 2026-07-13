<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-book-open mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Pilih kelas untuk mengelola penilaian Tahsin, Tahfidz, dan Kitabah per Pekan.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="10%">No</th>
                            <th class="text-left" width="30%">Nama Kelas</th>
                            <th width="60%">Kelola Penilaian (Berdasarkan Pekan)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($daftarRombel as $rombel): ?>
                        <tr>
                            <td class="align-middle font-weight-bold"><?= $no++ ?></td>
                            <td class="text-left align-middle font-weight-bold"><?= esc($rombel['rombel_name']) ?></td>
                            <td class="align-middle">
                                <!-- Tombol Tahsin -->
                                <a href="<?= base_url('guru/quran/tahsin/'.$rombel['id']) ?>" class="btn btn-info btn-sm font-weight-bold mx-1">
                                    <i class="fas fa-book-reader mr-1"></i> Tahsin
                                </a>
                                <!-- Tombol Tahfidz -->
                                <a href="<?= base_url('guru/quran/tahfidz/'.$rombel['id']) ?>" class="btn btn-primary btn-sm font-weight-bold mx-1">
                                    <i class="fas fa-quran mr-1"></i> Tahfidz
                                </a>
                                <!-- Tombol Kitabah -->
                                <a href="<?= base_url('guru/quran/kitabah/'.$rombel['id']) ?>" class="btn btn-warning btn-sm font-weight-bold mx-1">
                                    <i class="fas fa-pen-nib mr-1"></i> Kitabah
                                </a>
                                <!-- Tombol Rekap -->
                                <a href="<?= base_url('guru/quran/rekap/'.$rombel['id']) ?>" class="btn btn-secondary btn-sm font-weight-bold mx-1">
                                    <i class="fas fa-chart-bar mr-1"></i> Rekap
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($daftarRombel)): ?>
                            <tr><td colspan="3" class="py-4 text-muted">Belum ada data kelas untuk tahun ajaran aktif.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>