<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Penilaian Ekstrakurikuler</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1200px;">
        
        <!-- Header Utama -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 font-weight-bold" style="color: #17a2b8;"><i class="fas fa-medal mr-2"></i> MODUL EKSTRAKURIKULER</h3>
                <p class="text-muted small mb-0">Silakan pilih kelas atau kelompok untuk menginput/melihat rekap nilai ekstrakurikuler.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Alert Notifikasi -->
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success shadow-sm alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

       <!-- ========================================== -->
        <!-- BAGIAN 1: PENILAIAN BERBASIS KELAS/ROMBEL  -->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0" style="font-weight: 600; color: #343a40;">
                    <i class="fas fa-chalkboard-teacher mr-2 text-warning"></i> Daftar Kelas (Pramuka & Peminatan)
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th class="text-left">Nama Kelas / Rombel</th>
                            <th style="width: 250px;">Pramuka</th>
                            <th style="width: 250px;">Peminatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kelas)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Data kelas belum tersedia.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($kelas as $k): ?>
                            <tr>
                                <td class="font-weight-bold"><?= $no++ ?></td>
                                <td class="text-left">
                                    <strong><?= esc($k['rombel_name'] ?? 'Nama Kelas') ?></strong>
                                </td>
                                <td>
    <!-- Aksi Pramuka -->
    <div class="d-flex justify-content-center">
        <a href="<?= base_url('guru/pramuka/input/' . esc($k['id'])) ?>" class="btn btn-sm btn-warning text-dark font-weight-bold" title="Input Nilai Pramuka">
            📝 Input Nilai
        </a>
    </div>
</td>
<td>
    <!-- Aksi Peminatan -->
    <div class="d-flex justify-content-center">
        <a href="<?= base_url('guru/peminatan/input/' . esc($k['id'])) ?>" class="btn btn-sm btn-info text-white font-weight-bold" title="Input Nilai Peminatan">
            📝 Input Nilai
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

        <!-- ========================================== -->
        <!-- BAGIAN 2: PENILAIAN BERBASIS KELOMPOK ESKUL-->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="font-weight: 600; color: #343a40;">
                    <i class="fas fa-users mr-2 text-success"></i> Kelompok Ekstrakurikuler
                </h5>
                <a href="<?= base_url('guru/ekstrakurikuler/kelompok/create') ?>" class="btn btn-success btn-sm font-weight-bold shadow-sm">
                    <i class="fas fa-plus mr-1"></i> Buat Kelompok Baru
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-left" width="20%">Nama Kelompok</th>
                            <th width="15%">Jenis</th>
                            <th class="text-left" width="20%">Pembina/Pembimbing</th>
                            <th width="15%">Jml Siswa</th>
                            <th width="25%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kelompok)): ?>
                            <tr><td colspan="6" class="py-4 text-muted">Belum ada kelompok eskul yang dibuat.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($kelompok as $klp): ?>
                            <tr>
                                <td class="align-middle font-weight-bold"><?= $no++ ?></td>
                                <td class="text-left align-middle font-weight-bold text-success"><?= esc($klp['nama_kelompok']) ?></td>
                                <td class="align-middle">
                                    <?php if(isset($klp['jenis_kelompok']) && $klp['jenis_kelompok'] == 'Reguler'): ?>
                                        <span class="badge badge-primary" style="color: black !important;">Reguler</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning" style="color: black !important;">Khusus</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-left align-middle"><?= esc($klp['pembimbing'] ?? 'Belum diset') ?></td>
                                <td class="align-middle font-weight-bold"><?= $klp['jumlah_siswa'] ?? 0 ?> Siswa</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <!-- Tombol Nilai -->
                                        <a href="<?= base_url('guru/ekstrakurikuler/kelompok/input/'.$klp['id']) ?>" class="btn btn-success btn-sm mr-1 font-weight-bold" title="Input Nilai"><i class="fas fa-edit mr-1"></i> Input Nilai</a>
                                        
                                        <!-- Tombol Manajemen -->
                                        <div class="border-left pl-2 ml-1 d-flex">
                                            <a href="<?= base_url('guru/ekstrakurikuler/kelompok/show/'.$klp['id']) ?>" class="btn btn-info btn-sm mr-1 text-white" title="Lihat Anggota"><i class="fas fa-eye"></i></a>
                                            <a href="<?= base_url('guru/ekstrakurikuler/kelompok/edit/'.$klp['id']) ?>" class="btn btn-warning btn-sm mr-1 text-dark" title="Edit Kelompok"><i class="fas fa-edit"></i></a>
                                            <a href="<?= base_url('guru/ekstrakurikuler/kelompok/delete/'.$klp['id']) ?>" class="btn btn-danger btn-sm" title="Hapus Kelompok" onclick="return confirm('Yakin ingin menghapus kelompok ini? Kelompok hanya bisa dihapus jika sudah tidak memiliki anggota siswa.')"><i class="fas fa-trash"></i></a>
                                        </div>
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
</body>
</html>