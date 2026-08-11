<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Viewport dikunci agar tampil utuh (seperti PDF) dan bisa di-zoom di HP -->
    <meta name="viewport" content="width=960, user-scalable=yes">
    <title>SiKuMi - Rekapitulasi Sekolah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Mengunci ukuran body agar tidak menyusut dan tata letak tidak hancur di HP */
        body { background-color: #f4f7f6; font-family: 'Open Sans', sans-serif; color: #333; min-width: 960px; overflow-x: auto; }
        
        /* Area Kontrol (Filter & Set Hari) - Sembunyikan saat dicetak */
        .control-panel { width: 900px; margin: 20px auto; }
        .card-custom { border: none; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); }
        .card-header-custom { background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; border-radius: 8px 8px 0 0; padding: 10px 15px; }
        
        /* Layout Kertas Rapor */
        .rapor-wrapper { background-color: #e3f2fd; padding: 30px; border-radius: 10px; width: 960px; margin: 0 auto 50px auto; }
        .rapor-container { max-width: 900px; margin: 0 auto; background: #ffffff; padding: 45px 50px; box-shadow: 0 10px 25px rgba(25, 118, 210, 0.15); border-top: 8px solid #1976d2; border-radius: 8px; }
        
        .header-sekolah { text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 20px; margin-bottom: 30px; }
        .header-sekolah h4, .header-sekolah h5 { font-family: 'Merriweather', serif; color: #15202b; margin: 0; letter-spacing: 1px; }
        .section-title { font-family: 'Merriweather', serif; font-size: 15px; background-color: #0d47a1; color: #ffffff; padding: 10px 15px; margin: 30px 0 15px 0; font-weight: bold; border-radius: 4px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        
        /* Desain Tabel Rapor */
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        table.data-table th, table.data-table td { border: 1px solid #222222; padding: 8px; vertical-align: middle; text-align: center; }
        table.data-table th { background-color: #eaf3fa; color: #15202b; font-weight: 700; border-bottom: 3px solid #1976d2; }
        
        /* Tema khusus tabel kepatuhan/spiritual agar beda warna headingnya */
        table.table-kepatuhan th { background-color: #f1daeb; border-bottom: 3px solid #d4abc9; }
        
        .col-kelas { font-weight: 600; text-align: left !important; background-color: #f8f9fa; }
        .col-kelas-kepatuhan { font-weight: 600; text-align: left !important; background-color: #fdf5f9; }
        .col-rata { background-color: #eaf3fa; font-weight: bold; text-align: left !important; }
        .col-rata-kepatuhan { background-color: #f1daeb; font-weight: bold; text-align: left !important; }
        
        .bg-tosca { background-color: #eaf3fa !important; color: #0d47a1; font-weight: bold; }
        .bg-pink { background-color: #fdf5f9 !important; color: #900c3f; font-weight: bold; }
        
        /* Box Legenda Keterangan */
        .legenda-box { background-color: #f4f9fd; border-left: 4px solid #1976d2; padding: 15px; border-radius: 4px; font-size: 13px; margin-bottom: 15px; border-top: 1px solid #ddd; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; }
        .legenda-box ul { padding-left: 20px; margin-bottom: 0; }
        .legenda-box li { margin-bottom: 4px; }

        @media print {
            body { background: none; padding: 0; min-width: auto; }
            .control-panel, .rapor-wrapper { background: none; padding: 0; margin: 0; width: 100%; }
            .control-panel { display: none !important; } /* Sembunyikan form filter saat print */
            .rapor-container { box-shadow: none; border-top: 8px solid #1976d2 !important; padding: 0; max-width: 100%; }
            .section-title { background-color: #0d47a1 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.data-table th, table.data-table td { border: 1px solid #000000 !important; } 
            table.data-table th { background-color: #eaf3fa !important; border-bottom: 3px solid #1976d2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            table.table-kepatuhan th { background-color: #f1daeb !important; border-bottom: 3px solid #d4abc9 !important; }
            .bg-tosca, .bg-pink { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <!-- ========================================================================= -->
    <!-- AREA KONTROL (FILTER & SET HARI) - AKAN HILANG SAAT DICETAK               -->
    <!-- ========================================================================= -->
    <div class="control-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 fw-bold" style="color: #2c3e50;">
                    <i class="fas fa-chart-line me-2" style="color: #FF9F00;"></i> Rekapitulasi Sekolah Terpadu
                </h4>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-primary btn-sm fw-bold">
                    <i class="fas fa-print me-1"></i> Cetak PDF
                </button>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-1"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4 g-3">
            <!-- Filter Laporan -->
            <div class="col-md-8">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom">
                        <h6 class="mb-0 fw-bold" style="font-size: 13px;"><i class="fas fa-filter me-1"></i> Filter Laporan</h6>
                    </div>
                    <div class="card-body p-3">
                        <form action="<?= base_url('admin/rekap-sekolah') ?>" method="GET" class="row align-items-end g-2">
                            <div class="col-md-3">
                                <label class="small fw-bold mb-1">Tipe Laporan</label>
                                <select name="tipe_filter" id="tipe_filter" class="form-select form-select-sm" onchange="toggleFilter()">
                                    <option value="bulan" <?= $tipe_filter == 'bulan' ? 'selected' : '' ?>>Bulanan</option>
                                    <option value="semester" <?= $tipe_filter == 'semester' ? 'selected' : '' ?>>Satu Semester</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3" id="box_bulan">
                                <label class="small fw-bold mb-1">Bulan</label>
                                <select name="bulan" class="form-select form-select-sm">
                                    <?php 
                                        $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                        foreach ($namaBulan as $angka => $nama): 
                                    ?>
                                        <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3" id="box_semester" style="display: none;">
                                <label class="small fw-bold mb-1">Semester</label>
                                <select name="semester" class="form-select form-select-sm">
                                    <option value="ganjil" <?= $semester == 'ganjil' ? 'selected' : '' ?>>Ganjil (Jul - Des)</option>
                                    <option value="genap" <?= $semester == 'genap' ? 'selected' : '' ?>>Genap (Jan - Jun)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="small fw-bold mb-1">Tahun</label>
                                <input type="number" name="tahun" class="form-control form-control-sm" value="<?= esc($tahun) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="fas fa-search me-1"></i> Tampilkan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Set Hari Efektif -->
            <div class="col-md-4">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom">
                        <h6 class="mb-0 fw-bold" style="font-size: 13px;"><i class="fas fa-calendar-check me-1"></i> Input Hari Efektif</h6>
                    </div>
                    <div class="card-body p-3">
                        <form id="form_set_hari" action="<?= base_url('admin/rekap-sekolah/set-hari') ?>" data-url="<?= base_url('admin/rekap-sekolah/get-hari') ?>" method="POST" class="row align-items-end g-2">
                            <div class="col-6">
                                <label class="small text-muted mb-1" style="font-size: 12px;">Bulan</label>
                                <select name="bulan" id="input_bulan" class="form-select form-select-sm" required onchange="updateHariInput()">
                                    <?php foreach ($namaBulan as $angka => $nama): ?>
                                        <option value="<?= $angka ?>" <?= $bulan === $angka ? 'selected' : '' ?>><?= $nama ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted mb-1" style="font-size: 12px;">Tahun</label>
                                <select name="tahun" id="input_tahun" class="form-select form-select-sm" required onchange="updateHariInput()">
                                    <?php 
                                        $tahunSekarang = date('Y');
                                        for ($y = $tahunSekarang - 2; $y <= $tahunSekarang + 2; $y++): 
                                    ?>
                                        <option value="<?= $y ?>" <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-7 mt-2">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="jumlah_hari" id="input_jumlah_hari" class="form-control" placeholder="Jml Hari" required>
                                    <span class="input-group-text">Hari</span>
                                </div>
                            </div>
                            <div class="col-5 mt-2">
                                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- KERTAS RAPOR REKAPITULASI                                                 -->
    <!-- ========================================================================= -->
    <div class="rapor-wrapper">
        <div class="rapor-container">

            <!-- Header Sekolah -->
            <div class="header-sekolah d-flex align-items-center justify-content-center gap-5">
                <div>
                    <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan" style="height: 85px; width: auto; object-fit: contain;">
                </div>
                <div class="text-center px-2">
                    <h4 class="mb-0 fw-bold">REKAPITULASI SEKOLAH TERPADU</h4>
                    <h5 class="mb-0 fw-bold">MIMHA TSANAWIYAH INFORMATIKA</h5>
                    <p class="mt-1 fw-bold text-secondary" style="font-size: 13px;">
                        Periode: <?= $tipe_filter == 'semester' ? "Semester " . ucfirst($semester) : "Bulan " . $namaBulan[$bulan] ?> Tahun <?= $tahun ?>
                    </p>
                </div>
                <div>
                    <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs" style="height: 85px; width: auto; object-fit: contain;">
                </div>
            </div>

            <!-- ============================================== -->
            <!-- BAGIAN 1: REKAPITULASI ABSENSI -->
            <!-- ============================================== -->
            <div class="section-title">1. Laporan Kehadiran (Persentase Absensi Kelas)</div>
            
            <?php if ($hariEfektif == 0): ?>
                <div class="alert alert-warning text-center p-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h6>Data Hari Efektif Kosong</h6>
                    <p class="small mb-0">Persentase Absensi tidak dapat dihitung. Silakan isi jumlah <strong>Hari Efektif</strong> melalui panel di atas terlebih dahulu.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
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
                                    <td rowspan="<?= $rowspan ?>" style="font-weight: bold; background-color: #f8f9fa;">
                                        <?= number_format($persenTingkat, 1, ',', '.') ?>%
                                    </td>
                                    <td rowspan="<?= $rowspan ?>" style="background-color: #f8f9fa;">
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
                            <td class="col-rata py-2">Rata-rata Sekolah</td>
                            <td class="bg-tosca"><?= number_format($avg_h ?? 0, 2, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($avg_s ?? 0, 2, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($avg_i ?? 0, 2, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($avg_a ?? 0, 2, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($avg_t ?? 0, 2, ',', '.') ?>%</td>
                            <td colspan="2" class="bg-tosca"><?= number_format($avg_h ?? 0, 1, ',', '.') ?>%</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="8" class="bg-secondary text-white font-weight-bold py-2" style="text-align: center; letter-spacing: 1px;">
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

            <!-- ============================================== -->
            <!-- BAGIAN 2: REKAPITULASI KEPATUHAN -->
            <!-- ============================================== -->
            <div class="section-title">2. Laporan Kepatuhan (Akumulasi Insiden / Pelanggaran)</div>
            
            <div class="legenda-box">
                <strong class="d-block mb-2">Keterangan Indikator Kolom:</strong>
                <div class="row">
                    <div class="col-4"><ul><li><strong>1</strong> = Tdk berseragam sesuai jadwal</li><li><strong>2</strong> = Tdk beratribut lengkap</li></ul></div>
                    <div class="col-4"><ul><li><strong>3</strong> = Tdk bersih diri (kuku, rambut)</li><li><strong>4</strong> = Terlambat hadir ke sekolah</li></ul></div>
                    <div class="col-4"><ul><li><strong>5</strong> = Melanggar peraturan kelas</li><li><strong>6</strong> = Melanggar tertib di masjid</li></ul></div>
                </div>
            </div>

            <table class="data-table table-kepatuhan">
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
                        <th style="min-width: 250px;">Rincian Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapKepatuhan)):
                        foreach ($rekapKepatuhan as $rk): 
                            $totalKasusKelas = $rk['seragam'] + $rk['atribut'] + $rk['bersih_diri'] + $rk['terlambat'] + $rk['aturan_kelas'] + $rk['masjid'];
                            
                            $keteranganRaw = isset($rk['keterangan']) ? $rk['keterangan'] : '';
                            $arrKet = array_filter(array_map('trim', explode(',', $keteranganRaw)));
                            $countKet = array_count_values($arrKet);
                            
                            $hasilKet = [];
                            foreach($countKet as $teks => $jml) {
                                if ($jml > 1) { $hasilKet[] = $teks . " (<strong>" . $jml . "</strong>)"; } 
                                else { $hasilKet[] = $teks; }
                            }
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
                            <td class="text-start" style="font-size: 12px; line-height: 1.4;"><?= $teksKeterangan ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="9" class="text-center py-4">Data kepatuhan belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapKepatuhan)): ?>
                    <tr>
                        <td class="col-rata-kepatuhan py-2">Total Kasus</td>
                        <td class="bg-pink"><?= $total_sekolah_seragam ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_atribut ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_bersih ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_lambat ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_aturan ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_masjid ?? 0 ?></td>
                        <td class="bg-pink" style="font-size: 15px;"><?= $grand_total_kasus ?? 0 ?></td>
                        <td class="bg-pink"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 3: REKAPITULASI SPIRITUAL -->
            <!-- ============================================== -->
            <div class="section-title">3. Laporan Aspek Spiritual (Akumulasi Insiden Peningkatan)</div>
            
            <div class="legenda-box">
                <strong class="d-block mb-2">Keterangan Indikator Kolom:</strong>
                <div class="row">
                    <div class="col-4"><ul><li><strong>1</strong> = Membiasakan Berdoa</li><li><strong>2</strong> = Kalimat Thoyibah</li><li><strong>3</strong> = Ibadah Shalat</li></ul></div>
                    <div class="col-4"><ul><li><strong>4</strong> = Membudayakan Salam</li><li><strong>5</strong> = Rasa Syukur</li></ul></div>
                    <div class="col-4"><ul><li><strong>6</strong> = Menjaga Lingkungan</li><li><strong>7</strong> = Toleransi Beragama</li></ul></div>
                </div>
            </div>

            <table class="data-table table-kepatuhan">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th title="Berdoa">1</th>
                        <th title="Kalimat Thoyibah">2</th>
                        <th title="Shalat">3</th>
                        <th title="Salam">4</th>
                        <th title="Rasa Syukur">5</th>
                        <th title="Lingkungan">6</th>
                        <th title="Toleransi">7</th>
                        <th style="background-color: #d4abc9; width: 100px;">Total Kasus</th>
                        <th style="min-width: 250px;">Rincian Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapSpiritual)):
                        foreach ($rekapSpiritual as $rs): 
                            $totalKasus = $rs['berdoa'] + $rs['kalimat_thoyibah'] + $rs['shalat'] + $rs['salam'] + $rs['syukur'] + $rs['lingkungan'] + $rs['toleransi'];
                            
                            $keteranganRaw = isset($rs['keterangan']) ? $rs['keterangan'] : '';
                            $arrKet = array_filter(array_map('trim', explode(',', $keteranganRaw)));
                            $countKet = array_count_values($arrKet);
                            
                            $hasilKet = [];
                            foreach($countKet as $teks => $jml) {
                                if ($jml > 1) { $hasilKet[] = $teks . " (<strong>" . $jml . "</strong>)"; } 
                                else { $hasilKet[] = $teks; }
                            }
                            $teksKeterangan = !empty($hasilKet) ? implode(', ', $hasilKet) : '-';
                    ?>
                        <tr>
                            <td class="col-kelas-kepatuhan"><?= esc($rs['rombel_name']) ?></td>
                            <td><?= $rs['berdoa'] ?></td>
                            <td><?= $rs['kalimat_thoyibah'] ?></td>
                            <td><?= $rs['shalat'] ?></td>
                            <td><?= $rs['salam'] ?></td>
                            <td><?= $rs['syukur'] ?></td>
                            <td><?= $rs['lingkungan'] ?></td>
                            <td><?= $rs['toleransi'] ?></td>
                            <td style="font-weight: bold; background-color: #fdf5f9;"><?= $totalKasus ?></td>
                            <td class="text-start" style="font-size: 12px; line-height: 1.4;"><?= $teksKeterangan ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="10" class="text-center py-4">Data spiritual belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapSpiritual)): ?>
                    <tr>
                        <td class="col-rata-kepatuhan py-2">Total Kasus</td>
                        <td class="bg-pink"><?= $total_sekolah_berdoa ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_kalimat ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_shalat ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_salam ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_syukur ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_lingkungan ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_toleransi ?? 0 ?></td>
                        <td class="bg-pink" style="font-size: 15px;"><?= $grand_total_spiritual ?? 0 ?></td>
                        <td class="bg-pink"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 4: REKAPITULASI SOSIAL -->
            <!-- ============================================== -->
            <div class="section-title">4. Laporan Aspek Sosial (Akumulasi Insiden Peningkatan)</div>
            
            <div class="legenda-box">
                <strong class="d-block mb-2">Keterangan Indikator Kolom:</strong>
                <div class="row">
                    <div class="col-4"><ul><li><strong>1</strong> = Disiplin</li><li><strong>2</strong> = Jujur</li><li><strong>3</strong> = Percaya Diri</li></ul></div>
                    <div class="col-4"><ul><li><strong>4</strong> = Santun</li><li><strong>5</strong> = Kerjasama</li></ul></div>
                    <div class="col-4"><ul><li><strong>6</strong> = Tanggung Jawab</li><li><strong>7</strong> = Adil</li></ul></div>
                </div>
            </div>

            <table class="data-table table-kepatuhan">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th title="Disiplin">1</th>
                        <th title="Jujur">2</th>
                        <th title="Percaya Diri">3</th>
                        <th title="Santun">4</th>
                        <th title="Kerjasama">5</th>
                        <th title="Tanggung Jawab">6</th>
                        <th title="Adil">7</th>
                        <th style="background-color: #d4abc9; width: 100px;">Total Kasus</th>
                        <th style="min-width: 250px;">Rincian Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapSosial)):
                        foreach ($rekapSosial as $rso): 
                            $totalKasusSos = $rso['disiplin'] + $rso['jujur'] + $rso['percaya_diri'] + $rso['santun'] + $rso['kerjasama'] + $rso['tanggung_jawab'] + $rso['adil'];
                            
                            $keteranganRawSos = isset($rso['keterangan']) ? $rso['keterangan'] : '';
                            $arrKetSos = array_filter(array_map('trim', explode(',', $keteranganRawSos)));
                            $countKetSos = array_count_values($arrKetSos);
                            
                            $hasilKetSos = [];
                            foreach($countKetSos as $teksSos => $jmlSos) {
                                if ($jmlSos > 1) { $hasilKetSos[] = $teksSos . " (<strong>" . $jmlSos . "</strong>)"; } 
                                else { $hasilKetSos[] = $teksSos; }
                            }
                            $teksKeteranganSos = !empty($hasilKetSos) ? implode(', ', $hasilKetSos) : '-';
                    ?>
                        <tr>
                            <td class="col-kelas-kepatuhan"><?= esc($rso['rombel_name']) ?></td>
                            <td><?= $rso['disiplin'] ?></td>
                            <td><?= $rso['jujur'] ?></td>
                            <td><?= $rso['percaya_diri'] ?></td>
                            <td><?= $rso['santun'] ?></td>
                            <td><?= $rso['kerjasama'] ?></td>
                            <td><?= $rso['tanggung_jawab'] ?></td>
                            <td><?= $rso['adil'] ?></td>
                            <td style="font-weight: bold; background-color: #fdf5f9;"><?= $totalKasusSos ?></td>
                            <td class="text-start" style="font-size: 12px; line-height: 1.4;"><?= $teksKeteranganSos ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="10" class="text-center py-4">Data sosial belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapSosial)): ?>
                    <tr>
                        <td class="col-rata-kepatuhan py-2">Total Kasus</td>
                        <td class="bg-pink"><?= $total_sekolah_disiplin ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_jujur ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_percaya_diri ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_santun ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_kerjasama ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_tanggung_jawab ?? 0 ?></td>
                        <td class="bg-pink"><?= $total_sekolah_adil ?? 0 ?></td>
                        <td class="bg-pink" style="font-size: 15px;"><?= $grand_total_sosial ?? 0 ?></td>
                        <td class="bg-pink"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 5: REKAPITULASI YAUMIYAH -->
            <!-- ============================================== -->
            <div class="section-title">5. Laporan Jurnal Yaumiyah (Persentase Capaian Kelas)</div>
            
            <?php if ($hariEfektif == 0): ?>
                <div class="alert alert-warning text-center p-3 small">
                    Isi data Hari Efektif terlebih dahulu untuk melihat persentase Yaumiyah.
                </div>
            <?php else: ?>
                <table class="data-table">
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
                                <td class="col-kelas"><?= esc($ry['rombel_name']) ?></td>
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

                        <?php if (!empty($rekapYaumiyah)): ?>
                        <tr>
                            <td class="col-rata py-2">Rata-rata Sekolah</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['dzuhur'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['ashar'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['bakdiah'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['duha'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['tahajud'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['tilawah'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['infaq'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['shaum'], 1, ',', '.') ?>%</td>
                            <td class="bg-tosca"><?= number_format($rata_yaumiyah['literasi'], 1, ',', '.') ?>%</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- ============================================== -->
            <!-- BAGIAN 6: REKAPITULASI AL-QUR'AN -->
            <!-- ============================================== -->
            <div class="section-title">6. Laporan Perkembangan Al-Qur'an (Rata-rata Nilai)</div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th>Rata-rata Tahsin</th>
                        <th>Rata-rata Tahfidz</th>
                        <th>Rata-rata Kitabah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapQuran)):
                        foreach ($rekapQuran as $rq): 
                    ?>
                        <tr>
                            <td class="col-kelas"><?= esc($rq['rombel_name']) ?></td>
                            <td class="fw-bold"><?= $rq['avg_tahsin'] > 0 ? number_format($rq['avg_tahsin'], 1, ',', '') : '-' ?></td>
                            <td class="fw-bold"><?= $rq['avg_tahfidz'] > 0 ? number_format($rq['avg_tahfidz'], 1, ',', '') : '-' ?></td>
                            <td class="fw-bold"><?= $rq['avg_kitabah'] > 0 ? number_format($rq['avg_kitabah'], 1, ',', '') : '-' ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="4" class="text-center py-4">Data penilaian Al-Qur'an belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapQuran)): ?>
                    <tr>
                        <td class="col-rata py-2">Rata-rata Sekolah</td>
                        <td class="bg-tosca" style="font-size: 14px;"><?= number_format($rata_quran_sekolah['tahsin'], 1, ',', '') ?></td>
                        <td class="bg-tosca" style="font-size: 14px;"><?= number_format($rata_quran_sekolah['tahfidz'], 1, ',', '') ?></td>
                        <td class="bg-tosca" style="font-size: 14px;"><?= number_format($rata_quran_sekolah['kitabah'], 1, ',', '') ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 7: REKAPITULASI PEMINATAN & PRAMUKA -->
            <!-- ============================================== -->
            <div class="section-title">7. Laporan Peminatan & Pramuka (Rata-rata Predikat)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th>Rata-rata Peminatan</th>
                        <th>Rata-rata Pramuka</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapPemPra)):
                        foreach ($rekapPemPra as $rp): 
                    ?>
                        <tr>
                            <td class="col-kelas"><?= esc($rp['rombel_name']) ?></td>
                            <td class="fw-bold"><?= $rp['peminatan'] ?></td>
                            <td class="fw-bold"><?= $rp['pramuka'] ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="3" class="text-center py-4">Data Peminatan & Pramuka belum tersedia</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 8: REKAPITULASI EKSTRAKURIKULER -->
            <!-- ============================================== -->
            <div class="section-title">8. Laporan Ekstrakurikuler (Rata-rata Predikat per Kelompok)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Kelompok Eskul</th>
                        <th style="width: 250px;">Rata-rata Nilai / Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapEskul)):
                        foreach ($rekapEskul as $re): 
                    ?>
                        <tr>
                            <td class="col-kelas" style="background-color: #f8f9fa;"><?= esc($re['nama_kelompok']) ?></td>
                            <td class="fw-bold text-center"><?= $re['nilai'] ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="2" class="text-center py-4">Data Kelompok Eskul belum tersedia</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 9: REKAPITULASI NILAI SUMATIF -->
            <!-- ============================================== -->
            <div class="section-title">9. Laporan Rata-rata Nilai Sumatif (Per Kelas)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <?php foreach ($daftarMapel as $mapel): ?>
                            <th><?= esc($mapel['nama_mapel']) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapSumatif)):
                        foreach ($rekapSumatif as $rs): 
                    ?>
                        <tr>
                            <td class="col-kelas"><?= esc($rs['rombel_name']) ?></td>
                            <?php foreach ($daftarMapel as $mapel): 
                                $nilai = $rs['mapel_' . $mapel['id']];
                            ?>
                                <td class="fw-bold <?= $nilai > 0 ? '' : 'text-muted' ?>">
                                    <?= $nilai > 0 ? number_format($nilai, 2, ',', '.') : '-' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="<?= count($daftarMapel) + 1 ?>" class="text-center py-4">Data nilai sumatif belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapSumatif) && !empty($daftarMapel)): ?>
                    <tr>
                        <td class="col-rata py-2">Rata-rata Sekolah</td>
                        <?php foreach ($daftarMapel as $mapel): 
                            $mapel_id = $mapel['id'];
                            $avgMapel = isset($rataSumatifMapel[$mapel_id]) && $rataSumatifMapel[$mapel_id]['count'] > 0 
                                ? ($rataSumatifMapel[$mapel_id]['total'] / $rataSumatifMapel[$mapel_id]['count']) 
                                : 0;
                        ?>
                            <td class="bg-tosca" style="font-size: 14px;">
                                <?= $avgMapel > 0 ? number_format($avgMapel, 2, ',', '.') : '-' ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 10: REKAPITULASI ANEKDOT -->
            <!-- ============================================== -->
            <div class="section-title">10. Laporan Catatan Khusus Anekdot (Akumulasi Insiden)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th style="width: 150px;">Total Catatan</th>
                        <th style="min-width: 250px;">Rincian Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapAnekdot)):
                        foreach ($rekapAnekdot as $ra): 
                            $ketRawAnekdot = isset($ra['keterangan']) ? $ra['keterangan'] : '';
                            $arrKetAnekdot = array_filter(array_map('trim', explode(',', $ketRawAnekdot)));
                            $countKetAnekdot = array_count_values($arrKetAnekdot);
                            
                            $hasilKetAnekdot = [];
                            foreach($countKetAnekdot as $teks => $jml) {
                                if ($jml > 1) { $hasilKetAnekdot[] = $teks . " (<strong>" . $jml . "</strong>)"; } 
                                else { $hasilKetAnekdot[] = $teks; }
                            }
                            $teksAnekdot = !empty($hasilKetAnekdot) ? implode(', ', $hasilKetAnekdot) : '-';
                    ?>
                        <tr>
                            <td class="col-kelas"><?= esc($ra['rombel_name']) ?></td>
                            <td class="fw-bold" style="font-size: 14px;"><?= $ra['total'] ?></td>
                            <td class="text-start" style="font-size: 12px; line-height: 1.4;"><?= $teksAnekdot ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="3" class="text-center py-4">Data anekdot belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapAnekdot)): ?>
                    <tr>
                        <td class="col-rata py-2">Total Kasus</td>
                        <td class="bg-tosca" style="font-size: 15px;"><?= $total_sekolah_anekdot ?? 0 ?></td>
                        <td class="bg-tosca"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- ============================================== -->
            <!-- BAGIAN 11: REKAPITULASI PRESTASI -->
            <!-- ============================================== -->
            <div class="section-title">11. Laporan Prestasi Siswa (Akumulasi Capaian)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Nama Kelas</th>
                        <th style="width: 150px;">Total Prestasi</th>
                        <th style="min-width: 250px;">Rincian Prestasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        if (!empty($rekapPrestasi)):
                        foreach ($rekapPrestasi as $rp): 
                            $ketRawPrestasi = isset($rp['keterangan']) ? $rp['keterangan'] : '';
                            $arrKetPrestasi = array_filter(array_map('trim', explode(',', $ketRawPrestasi)));
                            $countKetPrestasi = array_count_values($arrKetPrestasi);
                            
                            $hasilKetPrestasi = [];
                            foreach($countKetPrestasi as $teks => $jml) {
                                if ($jml > 1) { $hasilKetPrestasi[] = $teks . " (<strong>" . $jml . "</strong>)"; } 
                                else { $hasilKetPrestasi[] = $teks; }
                            }
                            $teksPrestasi = !empty($hasilKetPrestasi) ? implode(', ', $hasilKetPrestasi) : '-';
                    ?>
                        <tr>
                            <td class="col-kelas"><?= esc($rp['rombel_name']) ?></td>
                            <td class="fw-bold" style="font-size: 14px;"><?= $rp['total'] ?></td>
                            <td class="text-start" style="font-size: 12px; line-height: 1.4;"><?= $teksPrestasi ?></td>
                        </tr>
                    <?php 
                        endforeach; 
                        else:
                    ?>
                        <tr><td colspan="3" class="text-center py-4">Data prestasi belum tersedia</td></tr>
                    <?php endif; ?>

                    <?php if (!empty($rekapPrestasi)): ?>
                    <tr>
                        <td class="col-rata py-2">Total Capaian</td>
                        <td class="bg-tosca" style="font-size: 15px;"><?= $total_sekolah_prestasi ?? 0 ?></td>
                        <td class="bg-tosca"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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