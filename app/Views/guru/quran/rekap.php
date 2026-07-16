<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-rekap th, .table-rekap td { vertical-align: middle !important; font-size: 13px; }
        .table-rekap th { text-align: center; }
        .bg-tahsin { background-color: #d1ecf1; } /* Biru Muda */
        .bg-tahfidz { background-color: #cce5ff; } /* Biru */
        .bg-kitabah { background-color: #fff3cd; } /* Kuning */
        .note-cell { font-size: 11px; text-align: left !important; line-height: 1.4; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-secondary font-weight-bold"><i class="fas fa-chart-bar mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Capaian Surat/Halaman digabung, Nilai dirata-ratakan, Catatan diurutkan berdasarkan pekan.</p>
            </div>
            <div>
                <a href="<?= base_url('guru/quran') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Menu
                </a>
            </div>
        </div>

        <!-- FILTER BULAN & TAHUN -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('guru/quran/rekap/'.$kelompok['id']) ?>" method="GET" class="d-flex align-items-center">
                    <label class="font-weight-bold mr-2 mb-0">Bulan:</label>
                    <select name="bulan" class="form-control form-control-sm mr-3" style="width: 130px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($angka == $bulan) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="font-weight-bold mr-2 mb-0">Tahun:</label>
                    <select name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;">
                        <?php for($t = date('Y') - 2; $t <= date('Y') + 1; $t++): ?>
                            <option value="<?= $t ?>" <?= ($t == $tahun) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-secondary btn-sm font-weight-bold px-3">
                        <i class="fas fa-filter mr-1"></i> Tampilkan Rekap
                    </button>
                </form>
            </div>
        </div>

        <!-- TABEL REKAPITULASI -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-rekap table-hover mb-0 text-center">
                    <thead>
                        <tr>
                            <th width="3%" rowspan="2" class="bg-light">No</th>
                            <th width="12%" rowspan="2" class="bg-light text-left">Nama Siswa</th>
                            <th colspan="4" class="bg-tahsin">TAHSIN</th>
                            <th colspan="4" class="bg-tahfidz">TAHFIDZ</th>
                            <th colspan="3" class="bg-kitabah">KITABAH</th>
                        </tr>
                        <tr>
                            <!-- TAHSIN -->
                            <th class="bg-tahsin" width="8%">TALQIN</th>
                            <th class="bg-tahsin" width="8%">RIYADHAH</th>
                            <th class="bg-tahsin" width="4%">NILAI</th>
                            <th class="bg-tahsin" width="10%">CATATAN</th>
                            <!-- TAHFIDZ -->
                            <th class="bg-tahfidz" width="8%">SABQI</th>
                            <th class="bg-tahfidz" width="8%">SABAQ</th>
                            <th class="bg-tahfidz" width="4%">NILAI</th>
                            <th class="bg-tahfidz" width="10%">CATATAN</th>
                            <!-- KITABAH -->
                            <th class="bg-kitabah" width="8%">SURAT</th>
                            <th class="bg-kitabah" width="4%">NILAI</th>
                            <th class="bg-kitabah" width="10%">CATATAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($daftarSiswa as $siswa): 
                            $rf = $rekapFinal[$siswa['student_id']];
                        ?>
                        <tr>
                            <td class="font-weight-bold"><?= $no++ ?></td>
                            <td class="text-left font-weight-bold"><?= esc($siswa['username']) ?></td>
                            
                            <!-- DATA TAHSIN -->
                            <td><?= $rf['tahsin_talqin'] ?: '-' ?></td>
                            <td><?= $rf['tahsin_riyadhah'] ?: '-' ?></td>
                            <td class="font-weight-bold text-info"><?= $rf['tahsin_nilai'] ?></td>
                            <td class="note-cell"><?= $rf['tahsin_catatan'] ?: '-' ?></td>

                            <!-- DATA TAHFIDZ -->
                            <td><?= $rf['tahfidz_sabqi'] ?: '-' ?></td>
                            <td><?= $rf['tahfidz_sabaq'] ?: '-' ?></td>
                            <td class="font-weight-bold text-primary"><?= $rf['tahfidz_nilai'] ?></td>
                            <td class="note-cell"><?= $rf['tahfidz_catatan'] ?: '-' ?></td>

                            <!-- DATA KITABAH -->
                            <td><?= $rf['kitabah_surat'] ?: '-' ?></td>
                            <td class="font-weight-bold text-warning"><?= $rf['kitabah_nilai'] ?></td>
                            <td class="note-cell"><?= $rf['kitabah_catatan'] ?: '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($daftarSiswa)): ?>
                            <tr><td colspan="13" class="py-4 text-muted">Belum ada data siswa di kelompok ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>