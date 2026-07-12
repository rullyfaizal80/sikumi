<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Rekapitulasi Sekolah</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom { border-collapse: collapse; width: 100%; }
        .table-custom th, .table-custom td { border: 1px solid #dee2e6; text-align: center; vertical-align: middle; padding: 12px; font-size: 14px; }
        .table-custom thead th { background-color: #a5c8cc; color: #333; font-weight: 700; border-bottom: 2px solid #8baeb2; }
        
        /* Tema khusus tabel kepatuhan agar bisa dibedakan secara visual */
        .table-kepatuhan thead th { background-color: #f1daeb; color: #333; font-weight: 700; border-bottom: 2px solid #d4abc9; }
        
        .col-kelas { background-color: #e9f2f3; font-weight: bold; text-align: left !important; padding-left: 20px !important; }
        .col-kelas-kepatuhan { background-color: #fdf5f9; font-weight: bold; text-align: left !important; padding-left: 20px !important; }
        .col-rata { background-color: #a5c8cc; font-weight: bold; }
        .col-rata-kepatuhan { background-color: #f1daeb; font-weight: bold; }
        .bg-tosca { background-color: #a5c8cc; }
        .bg-pink { background-color: #f1daeb; }
        .card-header-custom { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-chart-line mr-2" style="color: #FF9F00;"></i> Rekapitulasi Sekolah Terpadu
                </h3>
                <p class="text-muted small mb-0 mt-1">Laporan persentase kehadiran dan akumulasi kepatuhan seluruh kelas.</p>
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
            <!-- Filter Laporan (Berlaku untuk Absensi & Kepatuhan) -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header card-header-custom py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter Laporan</h6>
                    </div>
                    <div class="card-body">
                        <!-- Sesuaikan action form dengan rute Controller Rekap Sekolah Anda -->
                        <form action="<?= base_url('admin/rekap-sekolah') ?>" method="GET" class="row align-items-end">
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
                                    <i class="fas fa-search mr-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Set Hari Efektif (Hanya untuk keperluan Kalkulasi Absensi) -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header card-header-custom py-2">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-calendar-check mr-1"></i> Input Hari Efektif (Absensi)</h6>
                    </div>
                    <div class="card-body bg-white">
                        <form id="form_set_hari" action="<?= base_url('admin/rekap-sekolah/set-hari') ?>" data-url="<?= base_url('admin/rekap-sekolah/get-hari') ?>" method="POST" class="row align-items-end">
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

        <!-- ============================================== -->
        <!-- BAGIAN 1: REKAPITULASI ABSENSI -->
        <!-- ============================================== -->
        <h5 class="font-weight-bold text-secondary mb-3"><i class="fas fa-user-check mr-2"></i> Laporan Kehadiran (Persentase)</h5>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <?php if ($hariEfektif == 0): ?>
                    <div class="p-5 text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>Data Hari Efektif Kosong</h5>
                        <p class="text-muted">Persentase Absensi tidak dapat dihitung. Silakan isi jumlah <strong>Hari Efektif</strong> melalui panel di atas terlebih dahulu.</p>
                    </div>
                <?php else: ?>
                    <!-- Menggunakan styling bawaan rekap absensi sebelumnya -->
                    <table class="table-custom table-hover">
                        <thead>
                            <tr>
                                <th style="width: 180px;">Nama Kelas</th>
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
                                if (!empty($rekapAbsensi)):
                                foreach ($rekapAbsensi as $rs): 
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
                            <?php 
                                endforeach; 
                                else: 
                            ?>
                                <tr><td colspan="8" class="text-center py-4">Data absensi belum tersedia</td></tr>
                            <?php endif; ?>
                            
                            <!-- Rata-rata Total Absensi -->
                            <?php if (!empty($rekapAbsensi)): ?>
                            <tr>
                                <td class="col-rata py-3">Rata-rata Sekolah</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_h ?? 0, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_s ?? 0, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_i ?? 0, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_a ?? 0, 2, ',', '.') ?>%</td>
                                <td class="bg-tosca font-weight-bold"><?= number_format($avg_t ?? 0, 2, ',', '.') ?>%</td>
                                <td colspan="2" class="bg-tosca font-weight-bold"><?= number_format($avg_h ?? 0, 1, ',', '.') ?>%</td>
                            </tr>
                            <?php endif; ?>
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

       <!-- ============================================== -->
        <!-- BAGIAN 2: REKAPITULASI KEPATUHAN / PELANGGARAN -->
        <!-- ============================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-clipboard-list mr-2"></i> Laporan Kepatuhan (Akumulasi Insiden)</h5>
        
        <!-- Legenda Aspek -->
        <div class="alert alert-secondary shadow-sm mb-3 py-2" role="alert">
            <h6 class="font-weight-bold small mb-2"><i class="fas fa-info-circle mr-1"></i> Keterangan Indikator (Angka 1-6):</h6>
            <div class="row small">
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>1</strong> = Tdk berseragam sesuai jadwal</li>
                        <li><strong>2</strong> = Tdk beratribut lengkap</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>3</strong> = Tdk bersih diri (kuku, rambut)</li>
                        <li><strong>4</strong> = Terlambat hadir ke sekolah</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <ul class="mb-0 pl-3">
                        <li><strong>5</strong> = Melanggar peraturan kelas</li>
                        <li><strong>6</strong> = Melanggar tertib di masjid</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-0 table-responsive">
                <table class="table-custom table-kepatuhan table-hover">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Nama Kelas</th>
                            <th title="Seragam">1</th>
                            <th title="Atribut">2</th>
                            <th title="Bersih Diri">3</th>
                            <th title="Terlambat">4</th>
                            <th title="Aturan Kelas">5</th>
                            <th title="Masjid">6</th>
                            <th style="background-color: #d4abc9; width: 100px;">Total Kasus</th>
                            <th style="min-width: 280px;">Rincian Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            if (!empty($rekapKepatuhan)):
                            foreach ($rekapKepatuhan as $rk): 
                                $totalKasusKelas = $rk['seragam'] + $rk['atribut'] + $rk['bersih_diri'] + $rk['terlambat'] + $rk['aturan_kelas'] + $rk['masjid'];
                                
                                // LOGIKA PENGGABUNGAN & PENGHITUNGAN KETERANGAN
                                $keteranganRaw = isset($rk['keterangan']) ? $rk['keterangan'] : '';
                                // 1. Pecah teks berdasarkan koma, bersihkan spasi, & buang yang kosong
                                $arrKet = array_filter(array_map('trim', explode(',', $keteranganRaw)));
                                // 2. Hitung jumlah kemunculan teks yang persis sama
                                $countKet = array_count_values($arrKet);
                                
                                $hasilKet = [];
                                foreach($countKet as $teks => $jml) {
                                    if ($jml > 1) {
                                        $hasilKet[] = $teks . " (<strong>" . $jml . "</strong>)";
                                    } else {
                                        $hasilKet[] = $teks;
                                    }
                                }
                                // 3. Gabungkan kembali dengan koma
                                $teksKeterangan = !empty($hasilKet) ? implode(', ', $hasilKet) : '-';
                        ?>
                            <tr>
                                <td class="col-kelas-kepatuhan"><?= esc($rk['rombel_name']) ?></td>
                                <td><?= $rk['seragam'] ?></td>
                                <td><?= $rk['atribut'] ?></td>
                                <td><?= $rk['bersih_diri'] ?></td>
                                <td><?= $rk['terlambat'] ?></td>
                                <td><?= $rk['aturan_kelas'] ?></td>
                                <td><?= $rk['masjid'] ?></td>
                                <td style="font-weight: bold; background-color: #fdf5f9;"><?= $totalKasusKelas ?></td>
                                <td class="text-left" style="font-size: 13px; line-height: 1.4;"><?= $teksKeterangan ?></td>
                            </tr>
                        <?php 
                            endforeach; 
                            else:
                        ?>
                            <tr><td colspan="9" class="text-center py-4">Data kepatuhan belum tersedia</td></tr>
                        <?php endif; ?>

                        <!-- Total Seluruh Sekolah untuk Kepatuhan -->
                        <?php if (!empty($rekapKepatuhan)): ?>
                        <tr>
                            <td class="col-rata-kepatuhan py-3">Total Kasus</td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_seragam ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_atribut ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_bersih ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_lambat ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_aturan ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold"><?= $total_sekolah_masjid ?? 0 ?></td>
                            <td class="bg-pink font-weight-bold" style="font-size: 16px;"><?= $grand_total_kasus ?? 0 ?></td>
                            <td class="bg-pink"></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- ============================================== -->
        <!-- BAGIAN 3: REKAPITULASI YAUMIYAH -->
        <!-- ============================================== -->
        <h5 class="font-weight-bold text-secondary mb-3 mt-5"><i class="fas fa-pray mr-2"></i> Laporan Jurnal Yaumiyah (Persentase Capaian)</h5>
        
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <?php if ($hariEfektif == 0): ?>
                    <div class="p-4 text-center">
                        <p class="text-muted mb-0">Isi data Hari Efektif terlebih dahulu untuk melihat persentase.</p>
                    </div>
                <?php else: ?>
                    <table class="table-custom table-yaumiyah table-hover">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Nama Kelas</th>
                                <th>Dzuhur</th>
                                <th>Ashar</th>
                                <th>Ba'diah Dz</th>
                                <th>Dhuha</th>
                                <th>Tahajud</th>
                                <th>Tilawah</th>
                                <th>Infaq</th>
                                <th>Shaum</th>
                                <th>Literasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                if (!empty($rekapYaumiyah)):
                                foreach ($rekapYaumiyah as $ry): 
                            ?>
                                <tr>
                                    <td class="col-kelas-yaumiyah"><?= esc($ry['rombel_name']) ?></td>
                                    <td><?= number_format($ry['p_dzuhur'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_ashar'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_bakdiah'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_duha'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_tahajud'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_tilawah'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_infaq'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_shaum'], 1, ',', '.') ?>%</td>
                                    <td><?= number_format($ry['p_literasi'], 1, ',', '.') ?>%</td>
                                </tr>
                            <?php 
                                endforeach; 
                                else:
                            ?>
                                <tr><td colspan="10" class="text-center py-4">Data yaumiyah belum tersedia</td></tr>
                            <?php endif; ?>

                            <!-- Rata-rata Total Yaumiyah -->
                            <?php if (!empty($rekapYaumiyah)): ?>
                            <tr>
                                <td class="col-rata-yaumiyah py-3">Rata-rata Sekolah</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['dzuhur'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['ashar'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['bakdiah'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['duha'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['tahajud'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['tilawah'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['infaq'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['shaum'], 1, ',', '.') ?>%</td>
                                <td class="col-rata-yaumiyah"><?= number_format($rata_yaumiyah['literasi'], 1, ',', '.') ?>%</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Script untuk Toggle Dropdown Filter (Sama dengan sebelumnya) -->
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
        
        document.addEventListener("DOMContentLoaded", function() {
            toggleFilter();
        });
    </script>

    <!-- Script untuk Otomatisasi Input Hari Efektif (Sama dengan sebelumnya) -->
    <script>
        async function updateHariInput() {
            const selectBulan = document.getElementById('input_bulan').value;
            const selectTahun = document.getElementById('input_tahun').value;
            const inputHari = document.getElementById('input_jumlah_hari');
            const urlApi = document.getElementById('form_set_hari').getAttribute('data-url');
            
            inputHari.placeholder = "...";
            inputHari.value = "";
            
            try {
                const response = await fetch(`${urlApi}?bulan=${selectBulan}&tahun=${selectTahun}`);
                const data = await response.json();
                inputHari.value = data.jumlah_hari;
                inputHari.placeholder = "Jml Hari";
            } catch (error) {
                console.error('Gagal mengambil data:', error);
                inputHari.placeholder = "Jml Hari";
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateHariInput();
        });
    </script>
</body>
</html>