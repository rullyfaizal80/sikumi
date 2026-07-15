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
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-users mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Kelola kelompok, pembimbing, dan jenis (Reguler/Khusus).</p>
            </div>
            <div>
                <a href="<?= base_url('guru/quran_kelompok/create') ?>" class="btn btn-success btn-sm font-weight-bold">
                    <i class="fas fa-plus mr-1"></i> Buat Kelompok Baru
                </a>
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold ml-2">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success shadow-sm">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-left" width="25%">Nama Kelompok</th>
                            <th width="20%">Jenis Kelompok</th>
                            <th class="text-left" width="25%">Pembimbing</th>
                            <th width="15%">Jml Siswa</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($kelompok as $k): ?>
                        <tr>
                            <td class="align-middle font-weight-bold"><?= $no++ ?></td>
                            <td class="text-left align-middle font-weight-bold text-success"><?= esc($k['nama_kelompok']) ?></td>
                            <td class="align-middle">
                                <?php if($k['jenis_kelompok'] == 'Reguler'): ?>
                                    <span class="badge badge-primary px-3 py-2">Reguler</span>
                                <?php else: ?>
                                    <span class="badge badge-warning px-3 py-2">Khusus</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-left align-middle"><?= esc($k['pembimbing'] ?: 'Belum diset') ?></td>
                            <td class="align-middle font-weight-bold"><?= $k['jumlah_siswa'] ?> Siswa</td>
                            <td class="align-middle">
                                <button class="btn btn-outline-info btn-sm"><i class="fas fa-eye"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($kelompok)): ?>
                            <tr><td colspan="6" class="py-4 text-muted">Belum ada kelompok yang dibuat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>