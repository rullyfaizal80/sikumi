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
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; padding: 12px; }
        .table-custom thead th { background-color: #343a40; color: #fff; font-weight: 600; }
        .col-kelas { font-weight: bold; text-align: left !important; background-color: #f8f9fa; }
        
        .progress { height: 18px; background-color: #e9ecef; border-radius: 4px; margin-bottom: 5px; box-shadow: inset 0 1px 2px rgba(0,0,0,.1); }
        .progress-bar { font-size: 11px; line-height: 18px; font-weight: bold; }
        .badge-insiden { font-size: 14px; padding: 6px 12px; }
        
        /* Pewarnaan Progress Bar Dinamis */
        .bg-danger-custom { background-color: #dc3545 !important; } /* < 50% */
        .bg-warning-custom { background-color: #ffc107 !important; color: #333 !important; } /* 50% - 80% */
        .bg-success-custom { background-color: #28a745 !important; } /* > 80% */
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-satellite-dish mr-2" style="color: #007bff;"></i> Monitoring Pengisian Kelas
                </h3>
                <p class="text-muted small mb-0 mt-1">Lacak persentase kelengkapan input data laporan oleh guru dan wali kelas.</p>
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
                <i class="fas fa-exclamation-triangle mr-2"></i> Perhatian: Hari efektif untuk bulan ini belum diatur. Beberapa persentase (seperti Absensi & Yaumiyah) akan bernilai 0%. Silakan atur di menu Rekapitulasi Sekolah.
            </div>
        <?php endif; ?>

        <!-- TABEL DASHBOARD MONITORING -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-hover">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Kelas (Rombel)</th>
                            <th style="width: 80px;">Siswa</th>
                            <th style="width: 18%;">Absensi Harian</th>
                            <th style="width: 18%;">Al-Qur'an (Penilaian)</th>
                            <th style="width: 18%;">Jurnal Yaumiyah</th>
                            <th style="width: 18%;">Nilai Sumatif</th>
                            <th title="Total input data Pelanggaran/Karakter/Anekdot. Semakin tinggi berarti guru aktif memantau.">Aktivitas Insidental <i class="fas fa-info-circle"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($monitoringData)):
                            foreach ($monitoringData as $row): 
                                // Fungsi penentu warna progress bar
                                $getColor = function($val) {
                                    if ($val < 50) return 'bg-danger-custom';
                                    if ($val < 80) return 'bg-warning-custom';
                                    return 'bg-success-custom';
                                };
                        ?>
                            <tr>
                                <td class="col-kelas text-dark"><?= esc($row['rombel_name']) ?></td>
                                <td class="font-weight-bold text-muted"><?= $row['jml_siswa'] ?></td>
                                
                                <!-- Bar Absensi -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_absen']) ?>" role="progressbar" style="width: <?= $row['persen_absen'] ?>%">
                                            <?= $row['persen_absen'] ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Target: <?= $hariEfektif ?> Hari</small>
                                </td>

                                <!-- Bar Al-Qur'an -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_quran']) ?>" role="progressbar" style="width: <?= $row['persen_quran'] ?>%">
                                            <?= $row['persen_quran'] ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Target: <?= $row['jml_siswa'] ?> Siswa</small>
                                </td>

                                <!-- Bar Yaumiyah -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_yaumiyah']) ?>" role="progressbar" style="width: <?= $row['persen_yaumiyah'] ?>%">
                                            <?= $row['persen_yaumiyah'] ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Target: <?= $row['jml_siswa'] * $hariEfektif ?> Record</small>
                                </td>

                                <!-- Bar Sumatif -->
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar <?= $getColor($row['persen_sumatif']) ?>" role="progressbar" style="width: <?= $row['persen_sumatif'] ?>%">
                                            <?= $row['persen_sumatif'] ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Capaian Siswa Dinilai</small>
                                </td>

                                <!-- Badge Insiden -->
                                <td>
                                    <?php if ($row['total_insiden'] > 0): ?>
                                        <span class="badge badge-info badge-insiden shadow-sm"><?= $row['total_insiden'] ?> Catatan</span>
                                    <?php else: ?>
                                        <span class="badge badge-light badge-insiden text-muted border">Kosong</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data Rombel yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top-0 text-muted small pb-3">
                <strong>Catatan:</strong> Warna <span class="text-danger">Merah (< 50%)</span>, <span class="text-warning">Kuning (50% - 80%)</span>, dan <span class="text-success">Hijau (> 80%)</span> merepresentasikan tingkat kedisiplinan guru/wali kelas dalam melengkapi data laporan di bulan tersebut. Kolom <em>Aktivitas Insidental</em> tidak memiliki persentase karena bersifat situasional (tidak ada target).
            </div>
        </div>

    </div>
</body>
</html>