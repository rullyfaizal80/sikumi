<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Rekap Absensi Seluruh Kelas</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom { border-collapse: collapse; width: 100%; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; padding: 12px; font-size: 14px; }
        .table-custom thead th { background-color: #a5c8cc; color: #333; font-weight: 700; border-bottom: 2px solid #8baeb2; }
        .col-kelas { background-color: #e9f2f3; font-weight: bold; text-align: left !important; padding-left: 20px !important; }
        .col-rata { background-color: #a5c8cc; font-weight: bold; }
        .bg-tosca { background-color: #a5c8cc; }
        .card-header-custom { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-chart-bar mr-2" style="color: #FF9F00;"></i> Rekapitulasi Absensi Sekolah
                </h3>
                <p class="text-muted small mb-0 mt-1">Laporan persentase kehadiran per tingkat dan kelas.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <!-- PANEL KONTROL: Filter & Input Hari -->
        <div class="row mb-4">
            <!-- Filter Laporan -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header card-header-custom py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter Laporan</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url('admin/absensi/rekap-sekolah') ?>" method="GET" class="row align-items-end">
                            <div class="col-md-3 mb-2">
                                <label class="small font-weight-bold">Tipe Laporan</label>
                                <select name="tipe_filter" id="tipe_filter" class="form-control form-control-sm" onchange="toggleFilter()">
                                    <option value="bulan" <?= $tipe_filter == 'bulan' ? 'selected' : '' ?>>Bulanan</option>
                                    <option value="semester" <?= $tipe_filter == 'semester' ? 'selected' : '' ?>>Satu Semester</option>
                                </select>
                            </div>
                            
                            <!-- Opsi Bulan -->
                            <div class="col-md-3 mb-2" id="box_bulan">
                                <label class="small font-weight-bold">Pilih Bulan</label>
                                <select name="bulan" class="form-control form-control-sm">
                                    <?php 
                                        $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                        foreach ($namaBulan as $angka => $nama): 
                                    ?>
                                        <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Opsi Semester -->
                            <div class="col-md-3 mb-2" id="box_semester" style="display: none;">
                                <label class="small font-weight-bold">Pilih Semester</label>
                                <select name="semester" class="form-control form-control-sm">
                                    <option value="ganjil" <?= $semester == 'ganjil' ? 'selected' : '' ?>>Ganjil (Jul - Des)</option>
                                    <option value="genap" <?= $semester == 'genap' ? 'selected' : '' ?>>Genap (Jan - Jun)</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-2">
                                <label class="small font-weight-bold">Tahun</label>
                                <input type="number" name="tahun" class="form-control form-control-sm" value="<?= esc($tahun) ?>" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold">
                                    Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Set Hari Efektif -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header card-header-custom py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-calendar-check mr-1"></i> Input Hari Efektif (Per Bulan)</h6>
                    </div>
                    <div class="card-body bg-white">
                        <!-- Tambahkan id dan data-url pada form -->
                        <form id="form_set_hari" action="<?= base_url('admin/absensi/rekap-sekolah/set-hari') ?>" data-url="<?= base_url('admin/absensi/rekap-sekolah/get-hari') ?>" method="POST" class="row align-items-end">
                            <div class="col-6 mb-2">
                                <label class="small text-muted mb-1">Bulan</label>
                                <select name="bulan" id="input_bulan" class="form-control form-control-sm" required onchange="updateHariInput()">
                                    <?php foreach ($namaBulan as $angka => $nama): ?>
                                        <option value="<?= $angka ?>" <?= $bulan === $angka ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="small text-muted mb-1">Tahun</label>
                                <select name="tahun" id="input_tahun" class="form-control form-control-sm" required onchange="updateHariInput()">
                                    <?php 
                                        $tahunSekarang = date('Y');
                                        for ($y = $tahunSekarang - 2; $y <= $tahunSekarang + 2; $y++): 
                                    ?>
                                        <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-7 mb-0">
                                <div class="input-group input-group-sm">
                                    <!-- Hapus value bawaan, biarkan JS yang mengisi -->
                                    <input type="number" name="jumlah_hari" id="input_jumlah_hari" class="form-control" placeholder="Jml Hari" required>
                                    <div class="input-group-append"><span class="input-group-text">Hari</span></div>
                                </div>
                            </div>
                            <div class="col-5 mb-0">
                                <button type="submit" class="btn btn-success btn-sm btn-block font-weight-bold">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                
                <?php if ($hariEfektif == 0): ?>
                    <div class="p-5 text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Data Hari Efektif Kosong</h5>
                        <p class="text-muted">Persentase tidak dapat dihitung. Silakan isi jumlah <strong>Hari Efektif</strong> melalui panel di atas terlebih dahulu.</p>
                    </div>
                <?php else: ?>
                    <table class="table-custom table-hover">
                        <thead>
                            <tr>
                                <th style="width: 180px;"></th>
                                <th>Hadir</th>
                                <th>Sakit</th>
                                <th>Ijin</th>
                                <th>Alpa</th>
                                <th>Terlambat</th>
                                <th colspan="2">Kehadiran / Tingkat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $currentTingkat = '';
                                foreach ($rekapSekolah as $rs): 
                            ?>
                                <tr>
                                    <td class="col-kelas"><?= esc($rs['rombel_name']) ?></td>
                                    <td><?= number_format($rs['persen_h'], 2, ',', '.') ?>%</td>
                                    <td><?= number_format($rs['persen_s'], 2, ',', '.') ?>%</td>
                                    <td><?= number_format($rs['persen_i'], 2, ',', '.') ?>%</td>
                                    <td><?= number_format($rs['persen_a'], 2, ',', '.') ?>%</td>
                                    <td><?= number_format($rs['persen_t'], 2, ',', '.') ?>%</td>
                                    
                                    <?php if ($rs['tingkat'] !== $currentTingkat): ?>
                                        <?php 
                                            $rowspan = $tingkatCounts[$rs['tingkat']];
                                            $targetTingkat = $rekapTingkat[$rs['tingkat']]['target'];
                                            $persenTingkat = $targetTingkat > 0 ? ($rekapTingkat[$rs['tingkat']]['h'] / $targetTingkat) * 100 : 0;
                                        ?>
                                        <td rowspan="<?= $rowspan ?>" style="font-weight: bold; background-color: #fafafa;">
                                            <?= number_format($persenTingkat, 1, ',', '.') ?>%
                                        </td>
                                        <td rowspan="<?= $rowspan ?>" style="background-color: #fafafa;">
                                            Kelas <?= esc($rs['tingkat']) ?>
                                        </td>
                                        <?php $currentTingkat = $rs['tingkat']; ?>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td class="col-rata py-3">Rata-rata</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_h, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_s, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_i, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_a, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_t, 2, ',', '.') ?>%</td>
                                <td colspan="2" class="bg-tosca font-weight-bold"><?= number_format($avg_h, 1, ',', '.') ?>%</td>
                            </tr>
                            <tr>
                                <td colspan="8" class="bg-secondary text-white font-weight-bold" style="text-align: center; padding: 12px; letter-spacing: 1px;">
                                    <?php 
                                        if ($tipe_filter == 'semester') {
                                            echo "TOTAL HARI EFEKTIF SEMESTER " . strtoupper($semester) . ": " . $hariEfektif . " HARI";
                                        } else {
                                            echo "HARI EFEKTIF BULAN " . strtoupper($namaBulan[$bulan]) . " " . $tahun . ": " . $hariEfektif . " HARI";
                                        }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Script untuk Toggle Dropdown Filter -->
    <script>
        function toggleFilter() {
            var tipe = document.getElementById('tipe_filter').value;
            if (tipe === 'semester') {
                document.getElementById('box_bulan').style.display = 'none';
                document.getElementById('box_semester').style.display = 'block';
            } else {
                document.getElementById('box_bulan').style.display = 'block';
                document.getElementById('box_semester').style.display = 'none';
            }
        }
        
        // Panggil saat halaman dimuat
        document.addEventListener("DOMContentLoaded", function() {
            toggleFilter();
        });
    </script>

<!-- Script untuk Otomatisasi Input Hari Efektif (AJAX Fetch) -->
    <script>
        async function updateHariInput() {
            const selectBulan = document.getElementById('input_bulan').value;
            const selectTahun = document.getElementById('input_tahun').value;
            const inputHari = document.getElementById('input_jumlah_hari');
            const urlApi = document.getElementById('form_set_hari').getAttribute('data-url');
            
            // Berikan efek loading saat data sedang ditarik dari database
            inputHari.placeholder = "...";
            inputHari.value = "";
            
            try {
                // Tembak URL API
                const response = await fetch(`${urlApi}?bulan=${selectBulan}&tahun=${selectTahun}`);
                const data = await response.json();
                
                // Isi input dengan hasil dari database
                inputHari.value = data.jumlah_hari;
                inputHari.placeholder = "Jml Hari";
            } catch (error) {
                console.error('Gagal mengambil data:', error);
                inputHari.placeholder = "Jml Hari";
            }
        }

        // Panggil fungsi sekali saat halaman pertama kali dimuat agar kotak langsung terisi
        document.addEventListener("DOMContentLoaded", function() {
            updateHariInput();
        });
    </script>
    
</body>
</html>