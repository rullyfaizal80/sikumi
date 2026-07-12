<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom th, .table-custom td { vertical-align: middle; text-align: center; }
        .table-custom th { font-size: 13px; background-color: #f4f6f9; color: #333; font-weight: 700; }
        .table-custom td.text-left { text-align: left; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-chart-bar mr-2" style="color: #9b59b6;"></i> <?= esc($title) ?>
                </h3>
                <p class="text-muted small mb-0 mt-1">Laporan persentase capaian ibadah dan amalan yaumiyah siswa dalam satu bulan.</p>
            </div>
           <div>
    <a href="<?= base_url('guru/yaumiyah') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Pilih Kelas
    </a>
</div>
        </div>

        <!-- Filter Bulan & Tahun -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('guru/yaumiyah/rekap/'.$rombel['id']) ?>" method="GET" class="d-flex align-items-center">
                    <label class="font-weight-bold mr-3 mb-0" style="white-space: nowrap;">
                        <i class="fas fa-calendar-alt mr-1"></i> Pilih Bulan Rekap:   
                    </label>
                    <select name="bulan" class="form-control form-control-sm mr-2" style="width: 150px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;" value="<?= esc($tahun) ?>">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">
                        <i class="fas fa-search mr-1"></i> Tampilkan
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Kelas -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th width="3%">No</th>
                            <th width="15%" class="text-left">Nama Siswa</th>
                            <th>Dzuhur</th>
                            <th>Ashar</th>
                            <th>Ba'diyah<br>Dzuhur</th>
                            <th>Duha</th>
                            <th>Tahajud</th>
                            <th>Tilawah</th>
                            <th>Infaq</th>
                            <th>Shaum</th>
                            <th>Literasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rekapData)): ?>
                            <?php $no = 1; foreach ($rekapData as $rd): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="text-left font-weight-bold"><?= esc($rd['username']) ?></td>
                                <td><?= number_format($rd['p_dzuhur'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_ashar'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_bakdiah'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_duha'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_tahajud'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_tilawah'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_infaq'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_shaum'], 1, ',', '.') ?>%</td>
                                <td><?= number_format($rd['p_literasi'], 1, ',', '.') ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="py-4 text-center text-muted">Belum ada data siswa terdaftar di kelas ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>