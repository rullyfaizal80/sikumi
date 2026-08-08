<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Monitoring Pengisian Laporan</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom { border-collapse: separate; border-spacing: 0; width: 100%; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; padding: 10px; }
        .table-custom thead th { background-color: #343a40; color: #fff; font-weight: 600; border-bottom: 2px solid #454d55; }
        
        .col-kelas { font-weight: bold; text-align: left !important; background-color: #f8f9fa; }
        .col-kelompok { font-weight: bold; text-align: left !important; background-color: #eef7fa; }
        .col-pembimbing { text-align: left !important; padding-left: 15px !important; }
        
        /* Modifikasi Progress Bar */
        .progress { height: 20px; background-color: #e9ecef; border-radius: 4px; margin-bottom: 0; box-shadow: inset 0 1px 2px rgba(0,0,0,.1); }
        .progress-bar { font-size: 11px; line-height: 20px; font-weight: bold; }
        
        /* Skala Warna Status */
        .bg-danger-custom { background-color: #dc3545 !important; color: #fff !important; }     /* < 50% */
        .bg-warning-custom { background-color: #ffc107 !important; color: #333 !important; }    /* 50% - 79% */
        .bg-info-custom { background-color: #17a2b8 !important; color: #fff !important; }       /* 80% - 99% */
        .bg-success-custom { background-color: #28a745 !important; color: #fff !important; }    /* 100% */
        
        /* Tabel Sumatif Horizontal Scrolling & Sticky */
        .table-wrapper-scroll { max-width: 100%; overflow-x: auto; }
        .sticky-col { position: sticky; left: 0; z-index: 2; background-color: #f8f9fa; border-right: 2px solid #dee2e6; }
        .sticky-head { position: sticky; left: 0; z-index: 3; background-color: #5a6268; border-right: 2px solid #dee2e6; }
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

        <!-- PANEL FILTER (Auto-Submit Tanpa Tombol) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <form id="formFilter" action="<?= base_url('admin/monitoring') ?>" method="GET" class="row align-items-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1">Bulan Laporan</label>
                        <select name="bulan" class="form-control form-control-sm font-weight-bold" onchange="document.getElementById('formFilter').submit()">
                            <?php 
                                $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                foreach ($namaBulan as $angka => $nama): 
                            ?>
                                <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-muted mb-1">Tahun</label>
                        <input type="number" name="tahun" class="form-control form-control-sm font-weight-bold" value="<?= esc($tahun) ?>" required onchange="document.getElementById('formFilter').submit()">
                    </div>
                    <div class="col-md-4 text-md-right text-muted small pt-3">
                        <i class="fas fa-info-circle mr-1"></i> Data akan otomatis memperbarui saat filter diubah.
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
            $getColor = function($val) {
                if ($val < 50) return 'bg-danger-custom';
                if ($val < 80) return 'bg-warning-custom';
                if ($val < 100) return 'bg-info-custom';
                return 'bg-success-custom';
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
                            <th style="width: 20%;">Peminatan</th>
                            <th style="width: 20%;">Pramuka</th>
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
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_yaumiyah']) ?>" style="width: <?= $row['persen_yaumiyah'] ?>%"><?= $row['persen_yaumiyah'] ?>%</div>
                                    </div>                                    
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_peminatan']) ?>" style="width: <?= $row['persen_peminatan'] ?>%"><?= $row['persen_peminatan'] ?>%</div>
                                    </div>
                                </td>

                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_pramuka']) ?>" style="width: <?= $row['persen_pramuka'] ?>%"><?= $row['persen_pramuka'] ?>%</div>
                                    </div>
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
        <!-- TABEL 2: MONITORING AL-QUR'AN -->
        <!-- ========================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-book-open mr-2"></i> Progress Penilaian Al-Qur'an (Kelompok Reguler)</h5>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-hover">
                    <thead>
                        <tr style="background-color: #17a2b8;">
                            <th rowspan="2" style="width: 22%; background-color: #117a8b; vertical-align: middle;">Nama Kelompok</th>
                            <th rowspan="2" style="width: 23%; background-color: #117a8b; vertical-align: middle;">Pembimbing</th>
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
                                <td class="col-pembimbing font-weight-bold text-dark"><?= esc($quran['pembimbing']) ?: '-' ?></td>
                                <td class="font-weight-bold text-muted"><?= $quran['jml_siswa'] ?></td>
                                
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($quran['persen_tahsin']) ?>" style="width: <?= $quran['persen_tahsin'] ?>%"><?= $quran['persen_tahsin'] ?>%</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($quran['persen_tahfidz']) ?>" style="width: <?= $quran['persen_tahfidz'] ?>%"><?= $quran['persen_tahfidz'] ?>%</div>
                                    </div>
                                </td>
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
        </div>

        <!-- ========================================== -->
        <!-- TABEL 3: MONITORING EKSTRAKURIKULER -->
        <!-- ========================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-futbol mr-2"></i> Progress Penilaian Ekstrakurikuler (Kelompok Reguler)</h5>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-hover">
                    <thead>
                        <tr>
                            <th style="width: 22%; background-color: #28a745; vertical-align: middle;">Nama Kelompok</th>
                            <th style="width: 23%; background-color: #28a745; vertical-align: middle;">Pembimbing</th>
                            <th style="width: 10%; background-color: #28a745; vertical-align: middle;">Siswa</th>
                            <th style="background-color: #28a745; vertical-align: middle;">Progress Input Nilai Bulanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($monitoringEskul)):
                            foreach ($monitoringEskul as $eskul): 
                        ?>
                            <tr>
                                <td class="col-kelompok text-dark"><?= esc($eskul['nama_kelompok']) ?></td>
                                <td class="col-pembimbing font-weight-bold text-dark"><?= esc($eskul['pembimbing']) ?: '-' ?></td>
                                <td class="font-weight-bold text-muted"><?= $eskul['jml_siswa'] ?></td>
                                
                                <td style="padding-left: 20px; padding-right: 20px;">
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($eskul['persen_nilai']) ?>" style="width: <?= $eskul['persen_nilai'] ?>%"><?= $eskul['persen_nilai'] ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada Kelompok Ekstrakurikuler Reguler.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TABEL 4: MONITORING NILAI SUMATIF -->
        <!-- ========================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-graduation-cap mr-2"></i> Progress Penilaian Sumatif (Per Mata Pelajaran)</h5>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0 table-wrapper-scroll">
                <table class="table-custom table-hover" style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th class="sticky-head" style="width: 160px;">Nama Kelas</th>
                            <?php foreach ($daftarMapel as $mapel): ?>
                                <th style="background-color: #5a6268; font-size: 11px; padding: 6px; width: 100px; line-height: 1.3;">
                                    <?= esc($mapel['nama_mapel']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($monitoringSumatif) && !empty($daftarMapel)):
                            foreach ($monitoringSumatif as $sumatif): 
                        ?>
                            <tr>
                                <td class="sticky-col text-dark"><?= esc($sumatif['rombel_name']) ?></td>
                                
                                <?php foreach ($daftarMapel as $mapel): 
                                    $val = $sumatif['mapel'][$mapel['id']];
                                ?>
                                    <td style="padding: 5px;">
                                        <?php if ($val === -1): ?>
                                            <div class="text-muted" style="background-color: #f1f3f5; border-radius: 3px; font-size: 12px; padding: 2px;">-</div>
                                        <?php else: ?>
                                            <div class="progress" style="height: 18px;">
                                                <div class="progress-bar <?= $getColor($val) ?>" style="width: <?= $val ?>%; font-size: 10px; line-height: 18px;"><?= $val ?>%</div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="<?= count($daftarMapel) + 1 ?>" class="text-center py-5 text-muted">Data jadwal mata pelajaran atau rombel belum tersedia.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

       <!-- LEGENDA WARNA PUSAT -->
        <div class="d-flex align-items-center flex-wrap mt-4 mb-5 pb-3" style="gap: 10px;">
            <span class="font-weight-bold text-muted small mr-2">Indikator Warna Status:</span>
            <span class="badge bg-danger-custom px-2 py-1 shadow-sm">Merah (< 50%)</span>
            <span class="badge bg-warning-custom px-2 py-1 shadow-sm">Kuning (50% - 79%)</span>
            <span class="badge bg-info-custom px-2 py-1 shadow-sm">Hijau Muda (80% - 99%)</span>
            <span class="badge bg-success-custom px-2 py-1 shadow-sm">Hijau Tua (100% Tuntas Sempurna)</span>
        </div>

    </div>
</body>
</html>