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
    <div class="container-fluid" style="max-width: 1000px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-book-open mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Pilih kelompok untuk mengelola penilaian Tahsin, Tahfidz, dan Kitabah per Pekan.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-left" width="25%">Nama Kelompok</th>
                            <th width="15%">Jenis</th>
                            <th class="text-left" width="20%">Pembimbing</th>
                            <th width="35%">Kelola Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($daftarKelompok as $kelompok): ?>
                        <tr>
                            <td class="align-middle font-weight-bold"><?= $no++ ?></td>
                            <td class="text-left align-middle font-weight-bold text-success"><?= esc($kelompok['nama_kelompok']) ?></td>
                            <td class="align-middle">
                                <?php if($kelompok['jenis_kelompok'] == 'Reguler'): ?>
                                    <span class="badge" style="background-color: #007bff; color: white !important; font-weight: bold; padding: 5px 10px;">Reguler</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #ffc107; color: black !important; font-weight: bold; padding: 5px 10px;">Khusus</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-left align-middle"><?= esc($kelompok['pembimbing'] ?: 'Belum diset') ?></td>
                            <td class="align-middle">
    <div class="d-flex flex-column align-items-center">
        <!-- Baris 1 (Atas): Jurnal, Tahsin, Tahfidz -->
        <div class="d-flex justify-content-center mb-2" style="gap: 6px;">           
            <a href="<?= base_url('guru/quran/tahsin/'.$kelompok['id']) ?>" class="btn btn-info btn-sm font-weight-bold" title="Penilaian Tahsin">
                <i class="fas fa-book-reader"></i> Tahsin
            </a>
            <a href="<?= base_url('guru/quran/tahfidz/'.$kelompok['id']) ?>" class="btn btn-primary btn-sm font-weight-bold" title="Penilaian Tahfidz">
                <i class="fas fa-quran"></i> Tahfidz
            </a>
             <a href="<?= base_url('guru/quran/kitabah/'.$kelompok['id']) ?>" class="btn btn-warning btn-sm font-weight-bold text-dark" title="Penilaian Kitabah">
                <i class="fas fa-pen-nib"></i> Kitabah
            </a>
        </div>
        
        <!-- Baris 2 (Bawah): Kitabah, Rekap -->
        <div class="d-flex justify-content-center" style="gap: 6px;">           
            <a href="<?= base_url('guru/quran/rekap/'.$kelompok['id']) ?>" class="btn btn-secondary btn-sm font-weight-bold" title="Rekapitulasi">
                <i class="fas fa-chart-bar"></i> Rekap
            </a>
             <a href="<?= base_url('guru/quran/jurnal/'.$kelompok['id']) ?>" class="btn btn-success btn-sm font-weight-bold" title="Jurnal Pembelajaran">
                <i class="fas fa-book"></i> Jurnal
            </a>
        </div>
    </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($daftarKelompok)): ?>
                            <tr><td colspan="5" class="py-4 text-muted">Belum ada kelompok Al-Qur'an yang dibuat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>