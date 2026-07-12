<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-rekap { border-collapse: collapse; min-width: 100%; }
        .table-rekap th, .table-rekap td {
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
            white-space: nowrap;
        }
        
        /* Sticky Column seperti di Modul Absensi */
        .sticky-col-1 {
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 2;
        }
        .sticky-col-2 {
            position: sticky;
            left: 50px;
            background-color: #fff;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .table-rekap thead .sticky-col-1, .table-rekap thead .sticky-col-2 {
            background-color: #343a40;
            color: #fff;
            z-index: 3;
        }

        /* Lebar spesifik untuk kolom Keterangan agar tidak terlalu menyusut */
        .col-ket { min-width: 250px; text-align: left !important; white-space: normal !important; font-size: 12px; }
        
        /* Pewarnaan seling per bulan */
        .bg-bulan-ganjil { background-color: #f8f9fa; } 
        .bg-bulan-genap { background-color: #e9ecef; }
        .header-ganjil { background-color: #e3c1d9; color: #333; }
        .header-genap { background-color: #b4c5e4; color: #333; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 100%; overflow-x: hidden;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2ecc71; font-weight: 700;">
                    <i class="fas fa-clipboard-list mr-2"></i> <?= esc($title) ?>
                </h3>
                <p class="text-muted small mb-0 mt-1">Akumulasi pelanggaran harian yang dijumlahkan per bulan beserta keterangannya.</p>
            </div>
            <div>
                <a href="<?= base_url('admin/kepatuhan') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white py-3">
                <form action="<?= base_url('admin/kepatuhan/rekap-kelas/'.$rombel_id) ?>" method="GET" class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small">Semester</label>
                        <select name="semester" class="form-control form-control-sm">
                            <option value="ganjil" <?= $semester == 'ganjil' ? 'selected' : '' ?>>Semester Ganjil (Jul - Des)</option>
                            <option value="genap" <?= $semester == 'genap' ? 'selected' : '' ?>>Semester Genap (Jan - Jun)</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="font-weight-bold small">Tahun</label>
                        <select name="tahun" class="form-control form-control-sm">
                            <?php 
                                $thnSkrg = date('Y');
                                for ($y = $thnSkrg - 2; $y <= $thnSkrg + 2; $y++): 
                            ?>
                                <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">
                            <i class="fas fa-search mr-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PINDAHKAN KETERANGAN / LEGENDA KE SINI -->
        <div class="alert alert-secondary shadow-sm mb-3" role="alert">
            <h6 class="font-weight-bold mb-2"><i class="fas fa-info-circle mr-1"></i> Keterangan Indikator Pelanggaran (Angka 1-6):</h6>
            <div class="row small">
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>1</strong> = Tidak berseragam sesuai jadwal</li>
                        <li><strong>2</strong> = Tidak beratribut lengkap</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>3</strong> = Tidak bersih diri (kuku, rambut)</li>
                        <li><strong>4</strong> = Terlambat hadir ke sekolah</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>5</strong> = Melanggar peraturan kelas</li>
                        <li><strong>6</strong> = Melanggar prosedur tata tertib di masjid</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- BATAS KETERANGAN -->

        <!-- Tabel Rekap Matrix -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-0 table-responsive" style="max-height: 600px;">
                <table class="table table-bordered table-hover table-rekap mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" class="sticky-col-1" style="width: 50px;">No</th>
                            <th rowspan="2" class="sticky-col-2" style="min-width: 200px; text-align: left;">Nama Siswa</th>
                            
                            <!-- Header Nama Bulan (Hanya nama bulan) -->
                            <?php 
                                $idxBulan = 0;
                                foreach ($array_bulan as $b): 
                                $idxBulan++;
                                $bgHeader = ($idxBulan % 2 !== 0) ? 'header-ganjil' : 'header-genap';
                            ?>
                                <th colspan="7" class="<?= $bgHeader ?>">
                                    <?= esc($nama_bulan[$b]) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <!-- Header Angka 1-6 dan Keterangan -->
                            <?php 
                                $idxBulan = 0;
                                foreach ($array_bulan as $b): 
                                $idxBulan++;
                                $bgSub = ($idxBulan % 2 !== 0) ? '#f1daeb' : '#dbe4f0';
                            ?>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">1</th>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">2</th>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">3</th>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">4</th>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">5</th>
                                <th style="background-color: <?= $bgSub ?>; width: 40px;">6</th>
                                <th style="background-color: <?= $bgSub ?>;" class="col-ket">Rekap Keterangan</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="44" class="text-center py-4 text-muted">Belum ada siswa di kelas ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($students as $siswa): ?>
                            <?php $sId = $siswa['student_id']; ?>
                            <tr>
                                <td class="sticky-col-1"><?= $no++ ?></td>
                                <td class="sticky-col-2" style="text-align: left;"><strong><?= esc($siswa['username']) ?></strong></td>
                                
                                <!-- Looping Data Bulanan Siswa -->
                                <?php 
                                    $idxBulan = 0;
                                    foreach ($array_bulan as $b): 
                                    $idxBulan++;
                                    $bgCell = ($idxBulan % 2 !== 0) ? 'bg-bulan-ganjil' : 'bg-bulan-genap';
                                    
                                    // Panggil dari Array Pivot
                                    $valSeragam = $rekapData[$sId][$b]['seragam'] ?? 0;
                                    $valAtribut = $rekapData[$sId][$b]['atribut'] ?? 0;
                                    $valBersih  = $rekapData[$sId][$b]['bersih_diri'] ?? 0;
                                    $valLambat  = $rekapData[$sId][$b]['terlambat'] ?? 0;
                                    $valAturan  = $rekapData[$sId][$b]['aturan_kelas'] ?? 0;
                                    $valMasjid  = $rekapData[$sId][$b]['masjid'] ?? 0;
                                    $valKet     = $rekapData[$sId][$b]['keterangan'] ?? '-';
                                ?>
                                    <td class="<?= $bgCell ?>"><?= $valSeragam ?></td>
                                    <td class="<?= $bgCell ?>"><?= $valAtribut ?></td>
                                    <td class="<?= $bgCell ?>"><?= $valBersih ?></td>
                                    <td class="<?= $bgCell ?>"><?= $valLambat ?></td>
                                    <td class="<?= $bgCell ?>"><?= $valAturan ?></td>
                                    <td class="<?= $bgCell ?>"><?= $valMasjid ?></td>
                                    <td class="<?= $bgCell ?> col-ket text-muted">
                                        <em><?= esc($valKet) ?></em>
                                    </td>
                                <?php endforeach; ?>
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