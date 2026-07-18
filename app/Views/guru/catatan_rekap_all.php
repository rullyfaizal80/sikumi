<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Rekap Semua Kelas</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1200px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #007bff; font-weight: 700;">🌐 REKAPITULASI SELURUH KELAS</h3>
                <p class="text-muted small mb-0">Catatan Anekdot & Prestasi - Semester <strong class="text-uppercase"><?= esc($semesterAktif) ?></strong></p>
            </div>
            <div>
                <button onclick="window.history.back()" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </button>
            </div>
        </div>

        <!-- Tabel Catatan Anekdot -->
        <div class="mb-4 mt-5">
            <h5 class="font-weight-bold" style="color: #ffc107;"><i class="fas fa-edit mr-2"></i> Daftar Catatan Anekdot (Semua Kelas)</h5>
        </div>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-warning text-dark">
                        <tr>
                            <th width="12%" class="text-center">Tanggal</th>
                            <th width="15%" class="text-center">Kelas</th>
                            <th width="20%">Nama Siswa</th>
                            <th>Catatan Kejadian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($rekapAnekdot)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan anekdot di semester ini.</td></tr>
                        <?php else: foreach($rekapAnekdot as $a): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= date('d-m-Y', strtotime($a['tanggal'])) ?></td>
                                <td class="text-center"><span class="badge text-dark badge-secondary"><?= esc($a['kelas']) ?></span></td>
                                <td><strong><?= esc($a['name']) ?></strong></td>
                                <td><?= nl2br(esc($a['kejadian'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Catatan Prestasi -->
        <div class="mb-4">
            <h5 class="font-weight-bold text-success"><i class="fas fa-trophy mr-2"></i> Daftar Catatan Prestasi (Semua Kelas)</h5>
        </div>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="15%" class="text-center">Kelas</th>
                            <th width="20%">Nama Siswa</th>
                            <th width="30%">Catatan Prestasi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($rekapPrestasi)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan prestasi di semester ini.</td></tr>
                        <?php else: foreach($rekapPrestasi as $p): ?>
                            <tr>
                                <td class="text-center"><span class="badge text-dark badge-secondary"><?= esc($p['kelas']) ?></span></td>
                                <td><strong><?= esc($p['name']) ?></strong></td>
                                <td class="font-weight-bold text-success"><?= esc($p['nama_prestasi']) ?></td>
                                <td><?= nl2br(esc($p['keterangan'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>