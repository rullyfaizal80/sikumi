<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Rekap Absensi Bulanan</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-rekap th, .table-rekap td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        /* Perbaikan CSS: Gunakan Class spesifik, bukan :nth-child agar tanggal 1 & 2 tidak ikut tertumpuk */
        .sticky-col-1 {
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 2;
        }
        .sticky-col-2 {
            position: sticky;
            left: 50px; /* Jarak sebesar lebar kolom No */
            background-color: #fff;
            z-index: 2;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .table-rekap thead .sticky-col-1, .table-rekap thead .sticky-col-2 {
            background-color: #343a40;
            color: #fff;
            z-index: 3;
        }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 100%; overflow-x: hidden;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">📊 Rekap Absensi Bulanan</h3>
                <p class="text-muted small mb-0">Lihat laporan kehadiran dan rekap keterlambatan siswa.</p>
            </div>
            <div>
                <a href="<?= base_url('admin/absensi') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white py-3">
                <form action="<?= base_url('admin/absensi/rekap') ?>" method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="font-weight-bold small">Pilih Kelas / Rombel</label>
                        <select name="rombel_id" class="form-control form-control-sm" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($daftarRombel as $rmb): ?>
                                <option value="<?= esc($rmb['id']) ?>" <?= ($rombel_id == $rmb['id']) ? 'selected' : '' ?>>
                                    <?= esc($rmb['rombel_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small">Bulan</label>
                        <select name="bulan" class="form-control form-control-sm">
                            <?php 
                                $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                foreach ($namaBulan as $angka => $nama): 
                            ?>
                                <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="font-weight-bold small">Tahun</label>
                        <input type="number" name="tahun" class="form-control form-control-sm" value="<?= esc($tahun) ?>" min="2020" max="2099">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">
                            <i class="fas fa-search mr-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Rekap Matrix -->
        <?php if (!empty($rombel_id)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0 table-responsive" style="max-height: 600px;">
                    <table class="table table-bordered table-hover table-rekap mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="sticky-col-1" style="width: 50px;">No</th>
                                <th rowspan="2" class="sticky-col-2" style="min-width: 200px; text-align: left;">Nama Siswa</th>
                                <th colspan="<?= $jumlahHari ?>">Tanggal (Bulan <?= $namaBulan[$bulan] ?>)</th>
                                <th colspan="6">Total Rekapitulasi</th>
                            </tr>
                            <tr>
                                <!-- Generate Kolom Tanggal -->
                                <?php for ($i = 1; $i <= $jumlahHari; $i++): ?>
                                    <th style="min-width: 40px; padding: 5px; font-size: 0.85rem;"><?= $i ?></th>
                                <?php endfor; ?>
                                
                                <!-- Kolom Total (Ditambah T dan Menit) -->
                                <th style="width: 40px; background-color: #28a745;" title="Total Hadir">H</th>
                                <th style="width: 40px; background-color: #ffc107; color: black;" title="Total Sakit">S</th>
                                <th style="width: 40px; background-color: #17a2b8;" title="Total Izin">I</th>
                                <th style="width: 40px; background-color: #dc3545;" title="Total Alpa">A</th>
                                <th style="width: 45px; background-color: #6c757d;" title="Sering Terlambat (Berapa Kali)">T</th>
                                <th style="width: 60px; background-color: #343a40;" title="Total Akumulasi Menit Terlambat">Menit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaKelas)): ?>
                                <tr>
                                    <td colspan="<?= $jumlahHari + 8 ?>" class="text-center py-4 text-muted">Belum ada siswa di kelas ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswaKelas as $siswa): ?>
                                    <?php 
                                        $studentId = esc($siswa['student_id']);
                                        $totalH = 0; $totalS = 0; $totalI = 0; $totalA = 0;
                                        $totalKaliTerlambat = 0;
                                        $totalMenitTerlambat = 0;
                                    ?>
                                    <tr>
                                        <td class="sticky-col-1"><?= $no++ ?></td>
                                        <td class="sticky-col-2" style="text-align: left;"><strong><?= esc($siswa['username']) ?></strong></td>
                                        
                                        <!-- Looping Tanggal -->
                                        <?php for ($i = 1; $i <= $jumlahHari; $i++): ?>
                                            <?php 
                                                $dataAbsen = $rekapData[$studentId][$i] ?? null;
                                                $status = '-';
                                                $menit = 0;
                                                $colorClass = 'text-muted';

                                                if ($dataAbsen) {
                                                    $status = $dataAbsen['status'];
                                                    $menit  = (int) $dataAbsen['menit'];

                                                    // Hitung Total Kehadiran
                                                    if ($status === 'H') { $totalH++; $colorClass = 'text-success font-weight-bold'; }
                                                    if ($status === 'S') { $totalS++; $colorClass = 'text-warning font-weight-bold'; }
                                                    if ($status === 'I') { $totalI++; $colorClass = 'text-info font-weight-bold'; }
                                                    if ($status === 'A') { $totalA++; $colorClass = 'text-danger font-weight-bold'; }
                                                    
                                                    // Hitung Keterlambatan
                                                    if ($menit > 0) {
                                                        $totalKaliTerlambat++;
                                                        $totalMenitTerlambat += $menit;
                                                    }
                                                }
                                            ?>
                                            <td style="padding: 5px;">
                                                <span class="<?= $colorClass ?>"><?= $status ?></span>
                                                <?php if ($menit > 0): ?>
                                                    <!-- Jika telat, tampilkan teks menit kecil di bawah huruf H -->
                                                    <br><small class="text-danger font-weight-bold" style="font-size: 0.7rem;"><?= $menit ?>m</small>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>

                                        <!-- Menampilkan Total per Siswa -->
                                        <td class="font-weight-bold bg-light"><?= $totalH ?></td>
                                        <td class="font-weight-bold bg-light"><?= $totalS ?></td>
                                        <td class="font-weight-bold bg-light"><?= $totalI ?></td>
                                        <td class="font-weight-bold bg-light"><?= $totalA ?></td>
                                        <td class="font-weight-bold text-danger bg-light"><?= $totalKaliTerlambat ?></td>
                                        <td class="font-weight-bold text-danger bg-light"><?= $totalMenitTerlambat ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>