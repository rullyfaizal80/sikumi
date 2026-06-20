<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Kalender Akademik Full Screen</title>
    <!-- Pemanggilan Seluruh Aset CSS Secara Lokal (Instant Tanpa Loading) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9 !important; font-family: 'Source Sans Pro', sans-serif; }
        .card-kaldik { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        .btn-warning-custom { background-color: #FF9F00 !important; border: none !important; color: #ffffff !important; font-weight: 600; }
        .btn-warning-custom:hover { background-color: #e68f00 !important; }
        
        /* LAYOUT BOX BULANAN JAUH LEBIH LUAS */
        .month-box { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); min-height: 240px; }
        .month-title { background-color: #212529; color: #ffffff; text-align: center; font-weight: 700; padding: 6px; border-radius: 4px; font-size: 14px; text-uppercase: true; margin-bottom: 10px; }
        
        /* TATA LETAK GRID HARI & ANGKA */
        .grid-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 12px; font-weight: 700; color: #495057; margin-bottom: 6px; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
        .grid-dates { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 5px; column-gap: 5px; text-align: center; }
        
        /* STYLE MASING-MASING SEL TANGGAL */
        .date-cell { font-size: 12px; font-weight: 700; padding: 5px 0; border-radius: 4px; color: #212529; border: 1px solid transparent; }
        .date-empty { visibility: hidden; }
        
        /* AKHIR MINGGU MERAH */
        .date-weekend { color: #dc3545 !important; background-color: #fff5f5; border-radius: 4px; }
        
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
        .legend-color { width: 16px; height: 16px; border-radius: 4px; border: 1px solid #ddd; }

        @media print {
            .app-header, .no-print, form, button, .alert { display: none !important; }
            body { background: white !important; padding: 0; }
            .card-kaldik { border: none !important; }
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg">
    <div class="app-wrapper">
        
        <!-- HEADER / NAVBAR ATAS -->
        <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm py-2">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item d-flex align-items-center ps-2">
                        <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" class="me-2" style="height: 30px;">
                        <h4 class="navbar-text my-0 font-weight-bold text-dark" style="font-weight: 700; font-size: 18px;">
                            Kalender Pendidikan <span style="color: #FF9F00;">MIMHa Tsanawiyah</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <form action="<?= base_url('admin/kaldik') ?>" method="GET" class="d-flex align-items-center gap-2 bg-light p-1 rounded border shadow-sm">
    
    <label class="small font-weight-bold text-muted mb-0 ps-2 d-none d-md-block">Filter:</label>
    
    <!-- 1. DROPDOWN TAHUN PELAJARAN (OTOMATIS SUBMIT & ADA PENANDA AKTIF) -->
    <select name="ta" class="form-select form-select-sm border-0 font-weight-bold bg-white text-dark" onchange="this.form.submit()" style="width: auto; min-width: 170px;">
        <?php foreach ($daftarTahun as $ta) : ?>
            <option value="<?= $ta['id'] ?>" <?= ($tahunAktif && $tahunAktif['id'] == $ta['id']) ? 'selected' : '' ?>>
                <?= $ta['academic_year'] ?> - <?= $ta['semester'] ?> <?= $ta['is_active'] == 1 ? '🌟 (Aktif)' : '' ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- 2. DROPDOWN KELAS (OTOMATIS SUBMIT) -->
    <select name="class_id" class="form-select form-select-sm border-0 font-weight-bold bg-white text-dark" onchange="this.form.submit()" style="width: 110px;">
        <?php foreach ($daftarKelas as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $kelasTerpilih == $k['id'] ? 'selected' : '' ?>>
                Kelas <?= $k['class_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

</form>
                    </li>
                    <li class="nav-item"><button type="button" class="btn btn-outline-dark btn-sm font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCopyKaldik">📄 Copy Kaldik</button></li>
                    <li class="nav-item">
                        <form action="<?= base_url('admin/kaldik/clear') ?>" method="POST" onsubmit="return confirm('⚠️ PERINGATAN PERMANEN:\n\nSemua agenda kegiatan pada kelas dan tahun pelajaran ini akan DIHAPUS SEKALIGUS.\n\nTindakan ini tidak bisa dibatalkan! Apakah Anda yakin?')" style="display: inline;">
                            <?= csrf_field() ?> <input type="hidden" name="academic_year_id" value="<?= !empty($tahunAktif) ? $tahunAktif['id'] : '' ?>">
                            <input type="hidden" name="class_id" value="<?= $kelasTerpilih ?>">
                            <button type="submit" class="btn btn-sm btn-danger font-weight-bold shadow-sm">
                                🗑️ Hapus Isi Kaldik
                            </button>
                        </form>
                    </li>
                    <li class="nav-item"><a href="<?= base_url('/') ?>" class="btn btn-sm btn-secondary font-weight-bold px-3">🏠 Dashboard</a></li>
                </ul>
            </div>
        </nav>

        <!-- AREA KONTEN KERJA LUAS SATU LAYAR FULL -->
        <main class="app-main pt-4 pb-4">
            <div class="app-content">
                <div class="container-fluid px-4">
                    
                    <!-- NOTIFIKASI SYSTEM -->
                    <?php if (session()->getFlashdata('sukses')): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
                            🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $mappedEvents = [];
                    foreach ($agendaKaldik as $ag) {
                        $start = strtotime($ag['start_date']);
                        $end = strtotime($ag['end_date']);
                        for ($current = $start; $current <= $end; $current += 86400) {
                            $dateKey = date('Y-m-d', $current);
                            $mappedEvents[$dateKey] = [
                        'id'          => $ag['id'],
                        'name'        => $ag['event_name'],
                        'color'       => $ag['color_hex'],
                        'category_id' => $ag['category_id'],
                        'jenis_matriks' => $ag['jenis_matriks'],
                        'start_date'  => $ag['start_date'],
                        'end_date'    => $ag['end_date']
                    ];
                        }
                    }

                    $currentSemester = $tahunAktif ? $tahunAktif['semester'] : 'Genap';
                    $rawYear = $tahunAktif ? $tahunAktif['academic_year'] : '2025/2026';
                    $yearsArray = explode('/', $rawYear);

                    if ($currentSemester === 'Ganjil') {
                        $targetYear = (int)$yearsArray[0];
                        $bulanKaldik = [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    } else {
                        $targetYear = (int)$yearsArray[1];
                        $bulanKaldik = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'];
                    }
                    ?>

                    <div class="row">
                        <!-- SATU KOLOM UTUH MELEBAR MAKSIMAL (col-lg-12) -->
                        <div class="col-lg-12">
                            <div class="card card-kaldik shadow-sm border-top border-warning border-3 p-4">
                                
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                    <div>
                                        <h4 class="text-dark my-0" style="font-weight: 800; font-size: 22px; letter-spacing: -0.5px;">KALENDER PENDIDIKAN SEMESTER <?= strtoupper($currentSemester) ?></h4>
                                        <span class="text-muted small">Target Monitor: <strong>Kelas <?= $kelasTerpilih == 1 ? '7' : ($kelasTerpilih == 2 ? '8' : '9') ?> (MTs)</strong> | Tahun Pelajaran <?= $rawYear ?></span>
                                    </div>
                                      <!-- REVISI TOMBOL CETAK AGAR MEMBAWA PARAMETER TAHUN DAN KELAS -->
<a href="<?= base_url('admin/kaldik/print?ta=' . ($tahunAktif ? $tahunAktif['id'] : '') . '&class_id=' . $kelasTerpilih) ?>" target="_blank" class="btn btn-sm btn-outline-secondary font-weight-bold shadow-sm">
    🖨️ Cetak Kalender (PDF)
</a>
                                    </div>

                                <!-- GRID 6 KOTAK BULANAN HORIZONTAL -->
                                <div class="row g-3">
                                    <?php foreach ($bulanKaldik as $numBulan => $namaBulan): ?>
                                        <div class="col-md-4">
                                            <div class="month-box d-flex flex-column h-100">
                                                <div class="month-title"><?= $namaBulan ?> <?= $targetYear ?></div>
                                                <div class="grid-days">
    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div>
    <div class="<?= ($hariKerjaSetting == 5) ? 'text-danger' : '' ?>">Sab</div>
    <div class="text-danger">Min</div>
</div>
                                                <div class="grid-dates mb-3">
                                                    <?php
                                                    $wFirstDay = date('w', strtotime("$targetYear-$numBulan-01"));
                                                    $firstDayIndex = ($wFirstDay == 0) ? 6 : $wFirstDay - 1;
                                                    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);

                                                    for ($i = 0; $i < $firstDayIndex; $i++) {
                                                        echo '<div class="date-cell date-empty"></div>';
                                                    }

                                                    for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
    $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
    $dayOfWeek = date('w', strtotime($fullDate));
    
    $styleCustom = '';
    $titleTooltip = '';
    $classCell = 'date-cell';

    // REVISI LOGIKA AKHIR PEKAN DINAMIS
    if ($dayOfWeek == 0) {
        // Hari Minggu selalu libur merah
        $classCell .= ' date-weekend';
    } elseif ($dayOfWeek == 6 && $hariKerjaSetting == 5) {
        // Hari Sabtu hanya ikut libur merah jika settingannya adalah 5 hari kerja
        $classCell .= ' date-weekend';
    }

    if (isset($mappedEvents[$fullDate])) {
        $styleCustom = 'background-color: ' . $mappedEvents[$fullDate]['color'] . '; color: #000000 !important; border: 1px solid #bbb; cursor: pointer;';
        $titleTooltip = 'title="' . esc($mappedEvents[$fullDate]['name']) . '"';
        
        echo "<div class='$classCell btn-tanggal-aktif' style='$styleCustom' $titleTooltip data-id='".$mappedEvents[$fullDate]['id']."' data-name='".esc($mappedEvents[$fullDate]['name'])."' data-start='".$mappedEvents[$fullDate]['start_date']."' data-end='".$mappedEvents[$fullDate]['end_date']."' data-cat='".$mappedEvents[$fullDate]['category_id']."'>$tgl</div>";
    } else {
        echo "<div class='$classCell btn-tanggal-polos' style='cursor: pointer;' data-date='$fullDate'>$tgl</div>";
    }
}
                                                    ?>
                                                </div>

                                                <!-- LIST MATRIKS SEBARAN HARI BULANAN (HEB, HEF, HLCB) -->
                                                <div class="mt-auto border-top pt-2">
                                                    <?php
                                                    $matriksHari = ['HEB' => [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,0=>0], 'HEF' => [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,0=>0], 'HLCB' => [1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,0=>0]];
                                                    for ($d = 1; $d <= $totalDaysInMonth; $d++) {
                                                        $cDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $d);
                                                        $cDay = (int)date('w', strtotime($cDate));
                                                        if (isset($mappedEvents[$cDate])) {
                                                            // Ambil langsung jenis matriks dari database
                                                            $kategoriCat = $mappedEvents[$cDate]['jenis_matriks'];
                                                            $matriksHari[$kategoriCat][$cDay]++;
                                                        } else {
        // REVISI LOGIKA HLCB POLOS BULANAN:
        if ($cDay == 0 || ($cDay == 6 && $hariKerjaSetting == 5)) { 
            $matriksHari['HLCB'][$cDay]++; 
        } else { 
            $matriksHari['HEB'][$cDay]++; 
        }
    }
}
                                                    ?>
                                                    <table class="table table-bordered text-center my-1 py-0 align-middle shadow-sm bg-white" style="font-size: 10px; line-height: 1.1; font-weight: 700; border: 1px solid #dee2e6;">
                                                        <thead>
                                                            <tr style="background-color: #9cc2e5; color: #000;">
                                                                <th style="padding: 3px; font-size: 10px;">Hari</th>
                                                                <th style="padding: 3px; width: 11%;">Sn</th><th style="padding: 3px; width: 11%;">Sl</th><th style="padding: 3px; width: 11%;">Rb</th><th style="padding: 3px; width: 11%;">Km</th><th style="padding: 3px; width: 11%;">Jm</th><th style="padding: 3px; width: 11%; color: #cc0000;">Sb</th><th style="padding: 3px; width: 11%; color: #cc0000;">Mg</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr><td style="padding: 3px; background-color:#f2f2f2; text-align:left; padding-left:4px;">HEB</td><td><?= $matriksHari['HEB'][1] ?></td><td><?= $matriksHari['HEB'][2] ?></td><td><?= $matriksHari['HEB'][3] ?></td><td><?= $matriksHari['HEB'][4] ?></td><td><?= $matriksHari['HEB'][5] ?></td><td class="text-muted"><?= $matriksHari['HEB'][6] ?></td><td class="text-muted"><?= $matriksHari['HEB'][0] ?></td></tr>
                                                            <tr><td style="padding: 3px; background-color:#f2f2f2; text-align:left; padding-left:4px;">HEF</td><td><?= $matriksHari['HEF'][1] ?></td><td><?= $matriksHari['HEF'][2] ?></td><td><?= $matriksHari['HEF'][3] ?></td><td><?= $matriksHari['HEF'][4] ?></td><td><?= $matriksHari['HEF'][5] ?></td><td class="text-muted"><?= $matriksHari['HEF'][6] ?></td><td class="text-muted"><?= $matriksHari['HEF'][0] ?></td></tr>
                                                            <tr><td style="padding: 3px; background-color:#f2f2f2; text-align:left; padding-left:4px;">HLCB</td><td><?= $matriksHari['HLCB'][1] ?></td><td><?= $matriksHari['HLCB'][2] ?></td><td><?= $matriksHari['HLCB'][3] ?></td><td><?= $matriksHari['HLCB'][4] ?></td><td><?= $matriksHari['HLCB'][5] ?></td><td class="text-danger"><?= $matriksHari['HLCB'][6] ?></td><td class="text-danger"><?= $matriksHari['HLCB'][0] ?></td></tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- RINCIAN TEKS AGENDA -->
                                                <div class="mt-2 border-top pt-2" style="min-height: 50px; max-height: 100px; overflow-y: auto;">
                                                    <?php 
                                                    $hasEvent = false;
                                                    $uniquePrinted = [];
                                                    foreach ($agendaKaldik as $ag): 
                                                        $startMonth = (int)date('m', strtotime($ag['start_date']));
                                                        $endMonth = (int)date('m', strtotime($ag['end_date']));
                                                        if ($numBulan >= $startMonth && $numBulan <= $endMonth):
                                                            if (in_array($ag['id'], $uniquePrinted)) continue;
                                                            $uniquePrinted[] = $ag['id'];
                                                            $hasEvent = true;
                                                    ?>
                                                        <div class="d-flex align-items-start mb-1" style="font-size: 11px; line-height: 1.2;">
                                                            <span class="me-2 mt-1 border" style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background-color: <?= $ag['color_hex'] ?>; flex-shrink: 0;"></span>
                                                            <div class="text-dark">
                                                                <span class="text-muted font-weight-bold" style="font-size: 10px;">
                                                                    <?= ($ag['start_date'] === $ag['end_date']) ? date('d', strtotime($ag['start_date'])) : date('d', strtotime($ag['start_date'])).'-'.date('d', strtotime($ag['end_date'])) ?>:
                                                                </span>
                                                                <?= esc($ag['event_name']) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; endforeach; if (!$hasEvent): ?>
                                                        <span class="text-muted italic small d-block pt-1" style="font-size: 11px; font-style: italic;">Tidak ada agenda.</span>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <!-- LEGEND WARNA NOTIFIKASI KATEGORI -->
                                <div class="border-top mt-4 pt-3 no-print">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="font-weight-bold mb-0 text-muted">🎨 Legenda Kategori Agenda</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMasterKategori">
                                            ⚙️ Kelola Master Kategori
                                        </button>
                                    </div>
                                    <div class="d-flex flex-wrap gap-4">
                                        <?php foreach ($daftarWarna as $dw): ?>
                                            <div class="legend-item"><div class="legend-color" style="background-color: <?= $dw['color_hex'] ?>;"></div><span><?= $dw['category_name'] ?></span></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <!-- ======================================================== -->
                                <!-- REVISI FINAL SIKUMI: 3 BLOK TABEL MATRIKS REKAP DENGAN HARI MINGGU -->
                                <!-- ======================================================== -->
                                <div class="border-top mt-5 pt-4">
                                    <h5 class="text-dark mb-4 text-center" style="font-weight: 800; font-size: 16px; letter-spacing: 0.5px;">
                                        ANALISIS HARI EFEKTIF BELAJAR MIMHa TSANAWIYAH INFORMATIKA SEMESTER <?= strtoupper($currentSemester) ?> TP. <?= $rawYear ?>
                                    </h5>
                                    
                                    <?php
                                    // 1. PROSES KALKULASI UTAMA DATA ALGORITMA MATRIKS (KONSISTEN DENGAN HARI MINGGU)
                                    $rekapBulanan = [];
                                    $grandTotalHari = 0;
                                    $totalSemesterHEB = 0;
                                    $totalSemesterHEF = 0;
                                    $totalSemesterHLCB = 0;

                                    $mapDayIndex = [1 => 'sn', 2 => 'sl', 3 => 'rb', 4 => 'km', 5 => 'jm', 6 => 'sb', 0 => 'mn'];
                                    $listHariInisial = ['sn', 'sl', 'rb', 'km', 'jm', 'sb', 'mn']; // REVISI: Memasukkan 'mn' (Minggu) ke dalam sebaran kolom mingguan

                                    foreach ($bulanKaldik as $numBulan => $namaBulan) {
                                        // Inisialisasi counter per bulan
                                        foreach (['HEB', 'HEF', 'HLCB'] as $kat) {
                                            foreach ($listHariInisial as $h) {
                                                $rekapBulanan[$numBulan][$kat][$h] = 0;
                                            }
                                            $rekapBulanan[$numBulan][$kat]['jml'] = 0;
                                        }

                                        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);
                                        $grandTotalHari += $totalDaysInMonth;

                                        // Scan Tanggal 1 s/d Akhir Bulan
                                        for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                            $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                            $dayOfWeek = (int)date('w', strtotime($fullDate));
                                            
                                            $dayLabel = $mapDayIndex[$dayOfWeek];
                                            $kategoriHari = 'HEB'; // Default awal

                                            // Filter Sensor Utama Kategori Database (Tanpa Hardcode)
                                            if (isset($mappedEvents[$fullDate])) {
                                                // Langsung ambil tujuannya dari database!
                                                $kategoriHari = $mappedEvents[$fullDate]['jenis_matriks'];
                                            } else {
                                                // Logika HLCB polos jika kalender kosong
                                                if ($dayOfWeek == 0 || ($dayOfWeek == 6 && $hariKerjaSetting == 5)) {
                                                    $kategoriHari = 'HLCB';
                                                }
                                            }

                                            // Akumulasikan ke dalam matriks hari bulanan secara presisi
                                            $rekapBulanan[$numBulan][$kategoriHari][$dayLabel]++;
                                            $rekapBulanan[$numBulan][$kategoriHari]['jml']++;
                                        }

                                        // Kumpulkan total kumulatif semester
                                        $totalSemesterHEB  += $rekapBulanan[$numBulan]['HEB']['jml'];
                                        $totalSemesterHEF  += $rekapBulanan[$numBulan]['HEF']['jml'];
                                        $totalSemesterHLCB += $rekapBulanan[$numBulan]['HLCB']['jml'];
                                    }
                                    ?>

                                    <!-- ROW UTAMA 3 GRIDS TABEL (DIREVISI SUPAYA SINKRON DAN LUAS) -->
                                    <div class="row g-3">
                                        
                                        <!-- ========================================== -->
                                        <!-- TABEL 1: HARI EFEKTIF BELAJAR (HEB) -->
                                        <!-- ========================================== -->
                                        <div class="col-xl-4 col-lg-12">
                                            <div class="table-responsive shadow-sm rounded border">
                                                <table class="table table-bordered table-striped text-center mb-0 align-middle shadow-sm bg-white" style="font-size: 11px; font-weight: 700; border: 1px solid #dee2e6;">
                                                    <thead class="text-white" style="background-color: #4f81bd !important;">
                                                        <tr>
                                                            <th rowspan="2" class="align-middle text-start ps-2" style="width: 25%;">Bulan</th>
                                                            <th colspan="7" class="py-1" style="font-size: 10px;">Hari Efektif Belajar (HEB)</th>
                                                            <th rowspan="2" class="align-middle" style="width: 10%;">Jml</th>
                                                            <th rowspan="2" class="align-middle" style="width: 13%;">% HEB</th>
                                                        </tr>
                                                        <tr style="font-size: 10px; background-color: #d9e1f2; color: #000;">
                                                            <th>Sn</th><th>Sl</th><th>Rb</th><th>Km</th><th>Jm</th><th style="color: #cc0000;">Sb</th><th style="color: #cc0000;">Mg</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $totalSn = 0; $totalSl = 0; $totalRb = 0; $totalKm = 0; $totalJm = 0; $totalSb = 0; $totalMg = 0;
                                                        foreach ($bulanKaldik as $numBulan => $namaBulan): 
                                                            $hData = $rekapBulanan[$numBulan]['HEB'];
                                                            $totalSn += $hData['sn']; $totalSl += $hData['sl']; $totalRb += $hData['rb'];
                                                            $totalKm += $hData['km']; $totalJm += $hData['jm']; $totalSb += $hData['sb']; $totalMg += $hData['mn'];
                                                            
                                                            $persenBulanHEB = ($totalSemesterHEB > 0) ? round(($hData['jml'] / $totalSemesterHEB) * 100, 2) : 0;
                                                        ?>
                                                        <tr>
                                                            <td class="text-start ps-2 text-dark" style="font-size: 10px;"><?= strtoupper($namaBulan) ?></td>
                                                            <td><?= $hData['sn'] ?></td><td><?= $hData['sl'] ?></td><td><?= $hData['rb'] ?></td>
                                                            <td><?= $hData['km'] ?></td><td><?= $hData['jm'] ?></td><td class="text-muted"><?= $hData['sb'] ?></td><td class="text-muted"><?= $hData['mn'] ?></td>
                                                            <td class="bg-light-subtle font-weight-bold"><?= $hData['jml'] ?></td>
                                                            <td class="text-secondary"><?= $persenBulanHEB ?>%</td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <tr style="background-color: #f2f2f2; font-weight: 800; border-top: 2px solid #000;">
                                                            <td class="text-start ps-2">JUMLAH</td>
                                                            <td><?= $totalSn ?></td><td><?= $totalSl ?></td><td><?= $totalRb ?></td>
                                                            <td><?= $totalKm ?></td><td><?= $totalJm ?></td><td><?= $totalSb ?></td><td><?= $totalMg ?></td>
                                                            <td style="color: #198754;"><?= $totalSemesterHEB ?></td>
                                                            <td class="text-success">100.00%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- ========================================== -->
                                        <!-- TABEL 2: HARI EFEKTIF FAKULTATIF (HEF) -->
                                        <!-- ========================================== -->
                                        <div class="col-xl-4 col-lg-12">
                                            <div class="table-responsive shadow-sm rounded border">
                                                <table class="table table-bordered table-striped text-center mb-0 align-middle shadow-sm bg-white" style="font-size: 11px; font-weight: 700; border: 1px solid #dee2e6;">
                                                    <thead class="text-dark" style="background-color: #ffc000 !important; color: #000 !important;">
                                                        <tr>
                                                            <th rowspan="2" class="align-middle text-start ps-2" style="width: 30%;">Hari Efektif Fakultatif (HEF)</th>
                                                            <th colspan="7" class="py-1" style="font-size: 10px; color: #000;">Sebaran Hari Event / Ujian</th>
                                                            <th rowspan="2" class="align-middle" style="width: 12%;">Jml</th>
                                                        </tr>
                                                        <tr style="font-size: 10px; background-color: #fff2cc; color: #000;">
                                                            <th>Sn</th><th>Sl</th><th>Rb</th><th>Km</th><th>Jm</th><th style="color: #cc0000;">Sb</th><th style="color: #cc0000;">Mg</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $totalSn = 0; $totalSl = 0; $totalRb = 0; $totalKm = 0; $totalJm = 0; $totalSb = 0; $totalMg = 0;
                                                        foreach ($bulanKaldik as $numBulan => $namaBulan): 
                                                            $hData = $rekapBulanan[$numBulan]['HEF'];
                                                            $totalSn += $hData['sn']; $totalSl += $hData['sl']; $totalRb += $hData['rb'];
                                                            $totalKm += $hData['km']; $totalJm += $hData['jm']; $totalSb += $hData['sb']; $totalMg += $hData['mn'];
                                                        ?>
                                                        <tr>
                                                            <td class="text-start ps-2 text-muted" style="font-size: 10px;"><?= strtoupper($namaBulan) ?></td>
                                                            <td><?= $hData['sn'] ?></td><td><?= $hData['sl'] ?></td><td><?= $hData['rb'] ?></td>
                                                            <td><?= $hData['km'] ?></td><td><?= $hData['jm'] ?></td><td><?= $hData['sb'] ?></td><td><?= $hData['mn'] ?></td>
                                                            <td class="bg-light-subtle font-weight-bold"><?= $hData['jml'] ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <tr style="background-color: #f2f2f2; font-weight: 800; border-top: 2px solid #000;">
                                                            <td class="text-start ps-2">JUMLAH</td>
                                                            <td><?= $totalSn ?></td><td><?= $totalSl ?></td><td><?= $totalRb ?></td>
                                                            <td><?= $totalKm ?></td><td><?= $totalJm ?></td><td><?= $totalSb ?></td><td><?= $totalMg ?></td>
                                                            <td style="color: #ffc107;"><?= $totalSemesterHEF ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- ========================================== -->
                                        <!-- TABEL 3: HARI LIBUR & CUTI BERSAMA (HLCB) -->
                                        <!-- ========================================== -->
                                        <div class="col-xl-4 col-lg-12">
                                            <div class="table-responsive shadow-sm rounded border">
                                                <table class="table table-bordered table-striped text-center mb-0 align-middle shadow-sm bg-white" style="font-size: 11px; font-weight: 700; border: 1px solid #dee2e6;">
                                                    <thead class="text-white" style="background-color: #c00000 !important;">
                                                        <tr>
                                                            <th rowspan="2" class="align-middle text-start ps-2" style="width: 30%;">Hari Libur & Cuti Bersama (HLCB)</th>
                                                            <th colspan="7" class="py-1" style="font-size: 10px;">Sebaran Hari Libur Sekolah</th>
                                                            <th rowspan="2" class="align-middle" style="width: 12%;">Jml</th>
                                                        </tr>
                                                        <tr style="font-size: 10px; background-color: #fce4d6; color: #000;">
                                                            <th>Sn</th><th>Sl</th><th>Rb</th><th>Km</th><th>Jm</th><th style="color: #cc0000;">Sb</th><th style="color: #cc0000;">Mg</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $totalSn = 0; $totalSl = 0; $totalRb = 0; $totalKm = 0; $totalJm = 0; $totalSb = 0; $totalMg = 0;
                                                        foreach ($bulanKaldik as $numBulan => $namaBulan): 
                                                            $hData = $rekapBulanan[$numBulan]['HLCB'];
                                                            $totalSn += $hData['sn']; $totalSl += $hData['sl']; $totalRb += $hData['rb'];
                                                            $totalKm += $hData['km']; $totalJm += $hData['jm']; $totalSb += $hData['sb']; $totalMg += $hData['mn'];
                                                        ?>
                                                        <tr>
                                                            <td class="text-start ps-2 text-muted" style="font-size: 10px;"><?= strtoupper($namaBulan) ?></td>
                                                            <td><?= $hData['sn'] ?></td><td><?= $hData['sl'] ?></td><td><?= $hData['rb'] ?></td>
                                                            <td><?= $hData['km'] ?></td><td><?= $hData['jm'] ?></td><td class="text-danger"><?= $hData['sb'] ?></td><td class="text-danger"><?= $hData['mn'] ?></td>
                                                            <td class="bg-light-subtle font-weight-bold"><?= $hData['jml'] ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                        <tr style="background-color: #f2f2f2; font-weight: 800; border-top: 2px solid #000;">
                                                            <td class="text-start ps-2">JUMLAH</td>
                                                            <td><?= $totalSn ?></td><td><?= $totalSl ?></td><td><?= $totalRb ?></td>
                                                            <td><?= $totalKm ?></td><td><?= $totalJm ?></td><td><?= $totalSb ?></td><td><?= $totalMg ?></td>
                                                            <td style="color: #dc3545;"><?= $totalSemesterHLCB ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div> <!-- END ROW GRIDS -->
                                </div>                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JENDELA POP-UP MODAL EDIT, HAPUS, & QUICK ADD AGENDA -->
    <div class="modal fade" id="modalAksiAgenda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0" style="border-radius: 8px变量;">
                <div class="modal-header bg-dark text-white font-weight-bold">
                    <h5 class="modal-title" id="judulModalAksi">⚙️ Kelola Agenda Tanggal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/kaldik/update') ?>" method="POST" id="formAksiKaldik">
                    <?= csrf_field() ?>
                    <input type="hidden" name="agenda_id" id="edit_agenda_id">
                    <input type="hidden" name="class_id" value="<?= $kelasTerpilih ?>">
                    <!-- BARIS BARU PENYELAMAT DATA: Menyuntikkan ID Tahun Pelajaran Aktif -->
                    <input type="hidden" name="academic_year_id" value="<?= $tahunAktif ? $tahunAktif['id'] : 1 ?>">

                    <div class="modal-body py-3">
                        <div class="mb-2">
                            <label class="small font-weight-bold text-muted mb-1">Nama Agenda / Kegiatan:</label>
                            <input type="text" name="event_name" id="edit_event_name" class="form-control form-control-sm" required autocomplete="off">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small font-weight-bold text-muted mb-1">Tanggal Mulai:</label>
                                <input type="date" name="start_date" id="edit_start_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="small font-weight-bold text-muted mb-1">Tanggal Selesai:</label>
                                <input type="date" name="end_date" id="edit_end_date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="small font-weight-bold text-muted mb-1">Kategori Warna:</label>
                            <select name="category_id" id="edit_category_id" class="form-select form-select-sm" required>
                                <?php foreach ($daftarWarna as $dw): ?>
                                    <option value="<?= $dw['id'] ?>"><?= $dw['category_name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-2 d-flex justify-content-between">
                        <button type="button" id="btnHapusAgenda" class="btn btn-sm btn-danger px-3 font-weight-bold"><i class="bi bi-trash3-fill"></i> Hapus</button>
                        <div>
                            <button type="button" class="btn btn-sm btn-secondary px-3 font-weight-bold me-1" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-sm btn-warning-custom px-3 font-weight-bold">💾 Simpan Agenda</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL COPY KALDIK -->
    <div class="modal fade" id="modalCopyKaldik" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0" style="border-radius: 8px;">
                <div class="modal-header bg-dark text-white font-weight-bold">
                    <h5 class="modal-title" style="font-weight: 700;"><i class="bi bi-copy me-2"></i> Kloning Kalender</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/kaldik/copy') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="academic_year_id" value="<?= $tahunAktif['id'] ?? 1 ?>">
                    <div class="modal-body py-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="small font-weight-bold text-muted">Dari:</label>
                                <select name="from_class_id" class="form-select form-select-sm" required>
                                    <?php foreach ($daftarKelas as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= $kelasTerpilih == $k['id'] ? 'selected' : '' ?>>Kelas <?= $k['class_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small font-weight-bold text-muted">Ke:</label>
                                <select name="to_class_id" class="form-select form-select-sm" required>
                                    <option value="" disabled selected>-- Pilih Target --</option>
                                    <?php foreach ($daftarKelas as $k): ?>
                                        <option value="<?= $k['id'] ?>">Kelas <?= $k['class_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-sm btn-secondary font-weight-bold px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-warning-custom font-weight-bold px-3 shadow-sm">📋 Salin Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMasterKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title font-weight-bold">⚙️ Kelola Master Kategori Kaldik</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body bg-light border-bottom">
                    <form action="<?= base_url('admin/kaldik/category/store') ?>" method="POST" id="formKategori">
                        <?= csrf_field() ?>
                        
                        <input type="hidden" name="id" id="kat_id" />
                        
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="small font-weight-bold">Nama Kategori</label>
                                <input type="text" name="category_name" id="kat_name" class="form-control form-control-sm" required placeholder="Cth: Rapat Guru" />
                            </div>
                            <div class="col-md-3">
                                <label class="small font-weight-bold">Warna Tema</label>
                                <input type="color" name="color_hex" id="kat_color" class="form-control form-control-sm p-1" required value="#0dcaf0" style="height: 31px;" />
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Target Matriks</label>
                                <select name="jenis_matriks" id="kat_jenis" class="form-select form-select-sm" required>
                                    <option value="HEB">HEB (Tetap Dihitung Efektif)</option>
                                    <option value="HEF">HEF (Hari Efektif Fakultatif)</option>
                                    <option value="HLCB">HLCB (Libur & Cuti Bersama)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary btn-sm font-weight-bold" id="btnBatalEditKat" style="display:none;" onclick="resetFormKat()">Batal Edit</button>
                            <button type="submit" class="btn btn-success btn-sm font-weight-bold shadow-sm" id="btnSimpanKat">➕ Tambah Kategori</button>
                        </div>
                    </form>
                </div>

                <div class="modal-body p-0">
                    <table class="table table-hover table-bordered m-0 small text-center align-middle">
                        <thead class="bg-white">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Warna</th>
                                <th width="40%" class="text-start">Nama Kategori</th>
                                <th width="20%">Matriks</th>
                                <th width="25%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftarWarna as $idx => $dw): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div style="width: 20px; height: 20px; background-color: <?= esc($dw['color_hex']) ?>; margin: 0 auto; border-radius: 4px; border: 1px solid #ccc;"></div>
                                </td>
                                <td class="text-start font-weight-bold"><?= esc($dw['category_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= esc($dw['jenis_matriks'] ?? 'HEB') ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm py-0 px-2 text-white" 
                                            data-id="<?= esc($dw['id']) ?>"
                                            data-name="<?= esc($dw['category_name']) ?>"
                                            data-color="<?= esc($dw['color_hex']) ?>"
                                            data-jenis="<?= esc($dw['jenis_matriks'] ?? 'HEB') ?>"
                                            onclick="editKat(this.dataset.id, this.dataset.name, this.dataset.color, this.dataset.jenis)">
                                        ✏️
                                    </button>
                                    
                                    <a href="<?= base_url('admin/kaldik/category/delete/' . $dw['id']) ?>" class="btn btn-danger btn-sm py-0 px-2" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                        🗑️
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

       <!-- ======================================================== -->
    <!-- SUSUNAN UTUH SCRIPT PENUTUP BERKAS KALDIK_MANAGE.PHP -->
    <!-- ======================================================== -->

    <!-- 1. Pemanggilan Seluruh Aset JavaScript Secara Lokal -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <!-- 2. KODE LAMA ANDA: Otomatisasi Input Tanggal Form Samping Sisi Kanan (Tetap Dipertahankan) -->
    <script>
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').value = this.value;
        });
    </script>

    <!-- 3. KODE BARU: Logika Pemicu Jendela Modal Pop-Up Saat Angka Tanggal Kalender Diklik -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const modalAksi = new bootstrap.Modal(document.getElementById('modalAksiAgenda'));
            
            // A. LOGIKA JIKA TANGGAL BERWARNA (ADA AGENDA) DIKLIK OLEH WAKA
            document.querySelectorAll('.btn-tanggal-aktif').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('judulModalAksi').innerHTML = '<i class="bi bi-pencil-square text-warning me-1"></i> Ubah / Hapus Agenda';
                    document.getElementById('formAksiKaldik').action = "<?= base_url('admin/kaldik/update') ?>";
                    
                    const id = this.getAttribute('data-id');
                    document.getElementById('edit_agenda_id').value = id;
                    document.getElementById('edit_event_name').value = this.getAttribute('data-name');
                    document.getElementById('edit_start_date').value = this.getAttribute('data-start');
                    document.getElementById('edit_end_date').value = this.getAttribute('data-end');
                    document.getElementById('edit_category_id').value = this.getAttribute('data-cat');
                    
                    // Munculkan tombol hapus warna merah di sisi kiri bawah modal
                    document.getElementById('btnHapusAgenda').style.display = 'block';
                    document.getElementById('btnHapusAgenda').onclick = function() {
                        if (confirm('Apakah Anda yakin ingin menghapus agenda kegiatan ini secara permanen dari kalender sekolah?')) {
                            const formHapus = document.createElement('form');
                            formHapus.method = 'POST';
                            formHapus.action = "<?= base_url('admin/kaldik/delete') ?>/" + id + "?class_id=<?= $kelasTerpilih ?>";
                            
                            // Amankan dengan CSRF Token bawaan framework
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '<?= csrf_token() ?>';
                            csrfInput.value = '<?= csrf_hash() ?>';
                            
                            formHapus.appendChild(csrfInput);
                            document.body.appendChild(formHapus);
                            formHapus.submit();
                        }
                    };
                    modalAksi.show();
                });
            });

            // B. LOGIKA JIKA TANGGAL POLOS (KOSONG) DIKLIK -> FITUR INSTANT QUICK ADD
            document.querySelectorAll('.btn-tanggal-polos').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('judulModalAksi').innerHTML = '<i class="bi bi-calendar-plus text-success me-1"></i> Ploting Agenda Baru';
                    document.getElementById('formAksiKaldik').action = "<?= base_url('admin/kaldik/store') ?>";
                    
                    const tglDipilih = this.getAttribute('data-date');
                    document.getElementById('edit_agenda_id').value = '';
                    document.getElementById('edit_event_name').value = '';
                    document.getElementById('edit_start_date').value = tglDipilih;
                    document.getElementById('edit_end_date').value = tglDipilih;
                    document.getElementById('edit_category_id').value = '';
                    
                    // Sembunyikan tombol hapus karena ini murni pembuatan data baru
                    document.getElementById('btnHapusAgenda').style.display = 'none';
                    modalAksi.show();
                });
            });
        });
    </script>
    <!-- SCRIPT UNTUK HANDLE EDIT KATEGORI -->
    <script>
        function editKat(id, name, color, jenis) {
            document.getElementById('formKategori').action = "<?= base_url('admin/kaldik/category/update') ?>";
            document.getElementById('kat_id').value = id;
            document.getElementById('kat_name').value = name;
            document.getElementById('kat_color').value = color;
            document.getElementById('kat_jenis').value = jenis;
            
            document.getElementById('btnSimpanKat').innerHTML = "💾 Update Kategori";
            document.getElementById('btnSimpanKat').className = "btn btn-warning btn-sm font-weight-bold shadow-sm text-white";
            document.getElementById('btnBatalEditKat').style.display = "inline-block";
        }

        function resetFormKat() {
            document.getElementById('formKategori').action = "<?= base_url('admin/kaldik/category/store') ?>";
            document.getElementById('kat_id').value = "";
            document.getElementById('kat_name').value = "";
            document.getElementById('kat_color').value = "#0dcaf0";
            document.getElementById('kat_jenis').value = "HEB";
            
            document.getElementById('btnSimpanKat').innerHTML = "➕ Tambah Kategori";
            document.getElementById('btnSimpanKat').className = "btn btn-success btn-sm font-weight-bold shadow-sm";
            document.getElementById('btnBatalEditKat').style.display = "none";
        }
    </script>
</body>
</html>
