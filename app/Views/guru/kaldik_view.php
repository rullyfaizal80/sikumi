<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Kalender Akademik (Guru)</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9 !important; font-family: 'Source Sans Pro', sans-serif; }
        .card-kaldik { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        .month-box { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); min-height: 240px; }
        .month-title { background-color: #212529; color: #ffffff; text-align: center; font-weight: 700; padding: 6px; border-radius: 4px; font-size: 14px; text-uppercase: true; margin-bottom: 10px; }
        .grid-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 12px; font-weight: 700; color: #495057; margin-bottom: 6px; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; }
        .grid-dates { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 5px; column-gap: 5px; text-align: center; }
        .date-cell { font-size: 12px; font-weight: 700; padding: 5px 0; border-radius: 4px; color: #212529; border: 1px solid transparent; }
        .date-empty { visibility: hidden; }
        .date-weekend { color: #dc3545 !important; background-color: #fff5f5; border-radius: 4px; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
        .legend-color { width: 16px; height: 16px; border-radius: 4px; border: 1px solid #ddd; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg">
    <div class="app-wrapper">
        
        <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm py-2">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item d-flex align-items-center ps-2">
                        <i class="bi bi-calendar3 text-primary me-2 fs-4"></i>
                        <h4 class="navbar-text my-0 font-weight-bold text-dark" style="font-weight: 700; font-size: 18px;">
                            Kalender Pendidikan <span class="badge bg-primary fs-6 ms-2">Akses Guru</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <form action="<?= base_url('guru/kaldik') ?>" method="GET" class="d-flex align-items-center gap-2 bg-light p-1 rounded border shadow-sm">
                            <select name="ta" class="form-select form-select-sm border-0 font-weight-bold bg-white text-dark" onchange="this.form.submit()" style="width: auto; min-width: 170px;">
                                <?php foreach ($daftarTahun as $ta) : ?>
                                    <option value="<?= $ta['id'] ?>" <?= ($tahunAktif && $tahunAktif['id'] == $ta['id']) ? 'selected' : '' ?>>
                                        <?= $ta['academic_year'] ?> - <?= $ta['semester'] ?> <?= $ta['is_active'] == 1 ? '🌟 (Aktif)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="class_id" class="form-select form-select-sm border-0 font-weight-bold bg-white text-dark" onchange="this.form.submit()" style="width: 110px;">
                                <?php foreach ($daftarKelas as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $kelasTerpilih == $k['id'] ? 'selected' : '' ?>>
                                        Kelas <?= $k['class_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </li>
                    <li class="nav-item"><a href="<?= base_url('/') ?>" class="btn btn-sm btn-secondary font-weight-bold px-3">🏠 Dashboard</a></li>
                </ul>
            </div>
        </nav>

        <main class="app-main pt-4 pb-4">
            <div class="app-content">
                <div class="container-fluid px-4">
                    
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
                        'jenis_matriks' => $ag['jenis_matriks'], // <--- TAMBAHKAN BARIS INI
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
                        <div class="col-lg-12">
                            <div class="card card-kaldik shadow-sm border-top border-primary border-3 p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                    <div>
                                        <h4 class="text-dark my-0" style="font-weight: 800; font-size: 22px;">KALENDER PENDIDIKAN SEMESTER <?= strtoupper($currentSemester) ?></h4>
                                        <span class="text-muted small">Target Monitor: <strong>Kelas <?= $kelasTerpilih == 1 ? '7' : ($kelasTerpilih == 2 ? '8' : '9') ?> (MTs)</strong> | Tahun Pelajaran <?= $rawYear ?></span>
                                    </div>
                                    <a href="<?= base_url('guru/kaldik/print?ta=' . ($tahunAktif ? $tahunAktif['id'] : '') . '&class_id=' . $kelasTerpilih) ?>" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold shadow-sm">
                                        <i class="bi bi-printer-fill me-1"></i>🖨️ Cetak Kalender (PDF)
                                    </a>
                                </div>

                                <div class="row g-3">
                                    <?php foreach ($bulanKaldik as $numBulan => $namaBulan): ?>
                                        <div class="col-md-4">
                                            <div class="month-box d-flex flex-column h-100">
                                                <div class="month-title bg-primary"><?= $namaBulan ?> <?= $targetYear ?></div>
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

                                                        if ($dayOfWeek == 0) {
                                                            $classCell .= ' date-weekend';
                                                        } elseif ($dayOfWeek == 6 && $hariKerjaSetting == 5) {
                                                            $classCell .= ' date-weekend';
                                                        }

                                                        if (isset($mappedEvents[$fullDate])) {
                                                            $styleCustom = 'background-color: ' . $mappedEvents[$fullDate]['color'] . '; color: #000000 !important; border: 1px solid #bbb;';
                                                            $titleTooltip = 'title="' . esc($mappedEvents[$fullDate]['name']) . '"';
                                                            
                                                            echo "<div class='$classCell' style='$styleCustom' data-bs-toggle='tooltip' $titleTooltip>$tgl</div>";
                                                        } else {
                                                            echo "<div class='$classCell'>$tgl</div>";
                                                        }
                                                    }
                                                    ?>
                                                </div>

                                                <!-- MATRIKS BULANAN DIKEMBALIKAN -->
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
                                <div class="border-top mt-4 pt-3 no-print">
                                    <div class="d-flex flex-wrap gap-4">
                                        <?php foreach ($daftarWarna as $dw): ?>
                                            <div class="legend-item"><div class="legend-color" style="background-color: <?= $dw['color_hex'] ?>;"></div><span><?= $dw['category_name'] ?></span></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- ======================================================== -->
                                <!-- TABEL REKAPITULASI SEMESTER DIKEMBALIKAN (GURU VIEW) -->
                                <!-- ======================================================== -->
                                <div class="border-top mt-5 pt-4">
                                    <h5 class="text-dark mb-4 text-center" style="font-weight: 800; font-size: 16px; letter-spacing: 0.5px;">
                                        ANALISIS HARI EFEKTIF BELAJAR MIMHa TSANAWIYAH INFORMATIKA SEMESTER <?= strtoupper($currentSemester) ?> TP. <?= $rawYear ?>
                                    </h5>
                                    
                                    <?php
                                    $rekapBulanan = [];
                                    $grandTotalHari = 0;
                                    $totalSemesterHEB = 0;
                                    $totalSemesterHEF = 0;
                                    $totalSemesterHLCB = 0;

                                    $mapDayIndex = [1 => 'sn', 2 => 'sl', 3 => 'rb', 4 => 'km', 5 => 'jm', 6 => 'sb', 0 => 'mn'];
                                    $listHariInisial = ['sn', 'sl', 'rb', 'km', 'jm', 'sb', 'mn'];

                                    foreach ($bulanKaldik as $numBulan => $namaBulan) {
                                        foreach (['HEB', 'HEF', 'HLCB'] as $kat) {
                                            foreach ($listHariInisial as $h) {
                                                $rekapBulanan[$numBulan][$kat][$h] = 0;
                                            }
                                            $rekapBulanan[$numBulan][$kat]['jml'] = 0;
                                        }

                                        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);
                                        $grandTotalHari += $totalDaysInMonth;

                                        for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                            $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                            $dayOfWeek = (int)date('w', strtotime($fullDate));
                                            
                                            $dayLabel = $mapDayIndex[$dayOfWeek];
                                            $kategoriHari = 'HEB';

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

                                            $rekapBulanan[$numBulan][$kategoriHari][$dayLabel]++;
                                            $rekapBulanan[$numBulan][$kategoriHari]['jml']++;
                                        }

                                        $totalSemesterHEB  += $rekapBulanan[$numBulan]['HEB']['jml'];
                                        $totalSemesterHEF  += $rekapBulanan[$numBulan]['HEF']['jml'];
                                        $totalSemesterHLCB += $rekapBulanan[$numBulan]['HLCB']['jml'];
                                    }
                                    ?>

                                    <div class="row g-3">
                                        <!-- TABEL 1: HEB -->
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

                                        <!-- TABEL 2: HEF -->
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

                                        <!-- TABEL 3: HLCB -->
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
</body>
</html>