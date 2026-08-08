<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Monitoring Pengisian Laporan</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom { border-collapse: collapse; width: 100%; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; padding: 10px; }
        .table-custom thead th { background-color: #343a40; color: #fff; font-weight: 600; }
        
        .col-kelas { font-weight: bold; text-align: left !important; background-color: #f8f9fa; }
        .col-kelompok { font-weight: bold; text-align: left !important; background-color: #eef7fa; }
        
        .progress { height: 20px; background-color: #e9ecef; border-radius: 4px; margin-bottom: 0; box-shadow: inset 0 1px 2px rgba(0,0,0,.1); }
        .progress-bar { font-size: 11px; line-height: 20px; font-weight: bold; }
        
        .badge-insiden { font-size: 13px; padding: 5px 10px; }
        
        /* 4 Skala Warna Pintar Berdasarkan Status Capaian */
        .bg-danger-custom { background-color: #dc3545 !important; color: #fff !important; }     /* < 50% (Merah) */
        .bg-warning-custom { background-color: #ffc107 !important; color: #333 !important; }    /* 50% - 79% (Kuning) */
        .bg-info-custom { background-color: #17a2b8 !important; color: #fff !important; }       /* 80% - 99% (Hijau Muda/Cyan) */
        .bg-success-custom { background-color: #28a745 !important; color: #fff !important; }    /* 100% Tuntas (Hijau Tua Spesial) */
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-satellite-dish mr-2" style="color: #007bff;"></i> Monitoring Pengisian Laporan
                </h3>
                <p class="text-muted small mb-0 mt-1">Lacak persentase kelengkapan input data laporan oleh guru dan pembimbing.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- PANEL FILTER -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="<?= base_url('admin/monitoring') ?>" method="GET" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Bulan Laporan</label>
                        <select name="bulan" class="form-control">
                            <?php 
                                $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                foreach ($namaBulan as $angka => $nama): 
                            ?>
                                <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="small font-weight-bold">Tahun</label>
                        <input type="number" name="tahun" class="form-control" value="<?= esc($tahun) ?>" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary font-weight-bold px-4">
                            <i class="fas fa-search mr-1"></i> Pantau Progress
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($hariEfektif == 0): ?>
            <div class="alert alert-warning shadow-sm font-weight-bold">
                <i class="fas fa-exclamation-triangle mr-2"></i> Perhatian: Hari efektif untuk bulan ini belum diatur. Persentase Absensi & Yaumiyah akan bernilai 0%.
            </div>
        <?php endif; ?>

        <?php 
            // Fungsi penentu 4 skala warna dinamis
            $getColor = function($val) {
                if ($val < 50) return 'bg-danger-custom';
                if ($val < 80) return 'bg-warning-custom';
                if ($val < 100) return 'bg-info-custom'; // Hijau Muda / Cyan untuk 80% - 99%
                return 'bg-success-custom';             // Hijau Tua Eksklusif untuk PAS 100%
            };
        ?>

        <!-- ========================================== -->
        <!-- TABEL 1: MONITORING PER KELAS / ROMBEL -->
        <!-- ========================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-4"><i class="fas fa-chalkboard-teacher mr-2"></i> Progress Laporan Per Kelas</h5>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-hover">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Kelas (Rombel)</th>
                            <th style="width: 70px;">Siswa</th>
                            <th style="width: 20%;">Absensi Harian</th>
                            <th style="width: 20%;">Jurnal Yaumiyah</th>
                            <th style="width: 20%;">Nilai Sumatif</th>
                            <th title="Total input data Pelanggaran/Karakter/Anekdot.">Insidental</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($monitoringKelas)):
                            foreach ($monitoringKelas as $row): 
                        ?>
                            <tr>
                                <td class="col-kelas text-dark"><?= esc($row['rombel_name']) ?></td>
                                <td class="font-weight-bold text-muted"><?= $row['jml_siswa'] ?></td>
                                
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_absen']) ?>" style="width: <?= $row['persen_absen'] ?>%"><?= $row['persen_absen'] ?>%</div>
                                    </div>
                                    <small class="text-muted">Target: <?= $hariEfektif ?> Hari</small>
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_yaumiyah']) ?>" style="width: <?= $row['persen_yaumiyah'] ?>%"><?= $row['persen_yaumiyah'] ?>%</div>
                                    </div>
                                    <small class="text-muted">Target: <?= $row['jml_siswa'] * $hariEfektif ?> Record</small>
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_sumatif']) ?>" style="width: <?= $row['persen_sumatif'] ?>%"><?= $row['persen_sumatif'] ?>%</div>
                                    </div>
                                    <small class="text-muted">Capaian Siswa</small>
                                </td>

                                <td>
                                    <?php if ($row['total_insiden'] > 0): ?>
                                        <span class="badge badge-info badge-insiden shadow-sm"><?= $row['total_insiden'] ?> Catatan</span>
                                    <?php else: ?>
                                        <span class="badge badge-light badge-insiden text-muted border">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data Rombel yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TABEL 2: MONITORING AL-QUR'AN (JUDUL 2 BARIS) -->
        <!-- ========================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-book-open mr-2"></i> Progress Penilaian Al-Qur'an (Kelompok Reguler)</h5>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-hover">
                    <thead>
                        <tr style="background-color: #17a2b8;">
                            <th rowspan="2" style="width: 20%; background-color: #117a8b; vertical-align: middle;">Nama Kelompok</th>
                            <th rowspan="2" style="width: 25%; background-color: #117a8b; vertical-align: middle;">Pembimbing</th>
                            <th rowspan="2" style="width: 10%; background-color: #117a8b; vertical-align: middle;">Siswa</th>
                            <th colspan="3" style="background-color: #117a8b; padding: 8px; font-size: 14px;">Progress Input Bulanan Al-Qur'an</th>
                        </tr>
                        <tr style="background-color: #138496;">
                            <th style="background-color: #138496; width: 15%; font-size: 12px; padding: 6px;">TAHSIN</th>
                            <th style="background-color: #138496; width: 15%; font-size: 12px; padding: 6px;">TAHFIDZ</th>
                            <th style="background-color: #138496; width: 15%; font-size: 12px; padding: 6px;">KITABAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($monitoringQuran)):
                            foreach ($monitoringQuran as $quran): 
                        ?>
                            <tr>
                                <td class="col-kelompok text-dark"><?= esc($quran['nama_kelompok']) ?></td>
                                <td class="font-weight-bold"><?= esc($quran['pembimbing']) ?: '-' ?></td>
                                <td class="font-weight-bold text-muted"><?= $quran['jml_siswa'] ?></td>
                                
                                <!-- Kolom Tahsin -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($quran['persen_tahsin']) ?>" style="width: <?= $quran['persen_tahsin'] ?>%"><?= $quran['persen_tahsin'] ?>%</div>
                                    </div>
                                </td>
                                <!-- Kolom Tahfidz -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($quran['persen_tahfidz']) ?>" style="width: <?= $quran['persen_tahfidz'] ?>%"><?= $quran['persen_tahfidz'] ?>%</div>
                                    </div>
                                </td>
                                <!-- Kolom Kitabah -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($quran['persen_kitabah']) ?>" style="width: <?= $quran['persen_kitabah'] ?>%"><?= $quran['persen_kitabah'] ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada Kelompok Al-Qur'an Reguler.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top-0 text-muted small pb-3">
                <strong>Indikator Warna Status:</strong> 
                <span class="badge bg-danger-custom px-2">Merah (<50%)</span> 
                <span class="badge bg-warning-custom px-2">Kuning (50%-79%)</span> 
                <span class="badge bg-info-custom px-2">Hijau Muda (80%-99%)</span> 
                <span class="badge bg-success-custom px-2">Hijau Tua (100% Tuntas Sempurna)</span>
            </div>
        </div>

    </div>
</body>
</html>