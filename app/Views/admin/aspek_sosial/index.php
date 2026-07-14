<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Pilih Kelas Sosial</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1000px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;">🤝 ASPEK SOSIAL (Pilih Rombel / Kelas)</h3>
                <p class="text-muted small mb-0">Silakan pilih kelas untuk menginput atau melihat rekap aspek sosial siswa.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

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
                            <th class="text-center" style="width: 250px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_kelas)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Data kelas belum tersedia.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($daftar_kelas as $kelas): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                <td>
                                    <strong><?= esc($kelas['rombel_name'] ?? 'Nama Kelas') ?></strong>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <!-- Tombol Menuju Form Input -->
                                        <a href="<?= base_url('admin/aspek-sosial/input/' . esc($kelas['id'])) ?>" class="btn btn-sm btn-warning text-white font-weight-bold" style="margin-right: 5px;">
                                            📝 Input
                                        </a>
                                        
                                        <!-- Tombol Menuju Rekap -->
                                        <a href="<?= base_url('admin/aspek-sosial/rekap-kelas/' . esc($kelas['id'])) ?>" class="btn btn-sm btn-info text-white font-weight-bold">
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
</body>
</html>