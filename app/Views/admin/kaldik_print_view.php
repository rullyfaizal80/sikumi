<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kalender Pendidikan MIMHa</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        /* MARJIN PENGAMAN KERTAS LANDSCAPE */
        body { 
            background-color: #ffffff !important; 
            color: #000000; 
            font-family: 'Arial', sans-serif; 
            font-size: 11px; 
            padding: 20px 30px !important; 
        }
        
        .table-kaldik th, .month-box table tr th { 
            padding: 3px 2px !important; 
            font-size: 9.5px; 
            font-weight: 700; 
            border: 1px solid #000000 !important; 
        }
        .table-kaldik td, .month-box table tr td { 
            padding: 2px 1px !important; 
            font-size: 9px; 
            font-weight: 700; 
            border: 1px solid #000000 !important; 
            line-height: 1.1 !important;
        }
        
        /* Membuat tinggi kotak selalu rata */
        .month-box { border: 1px solid #000000; padding: 6px; background: #fff; display: flex; flex-direction: column; justify-content: space-between; height: 100%; }
        .month-title { background-color: #002060; color: #ffffff; text-align: center; font-weight: 800; padding: 3px; font-size: 11px; text-transform: uppercase; margin-bottom: 5px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .grid-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 9.5px; font-weight: 800; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 4px; }
        
        /* Memberikan min-height agar stabil mau 5/6 minggu */
        .grid-dates { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 3px; column-gap: 3px; text-align: center; min-height: 100px; align-content: start;}
        
        .date-cell { font-size: 10px; font-weight: 800; padding: 1px 0; color: #000; }
        .date-empty { visibility: hidden; }
        .date-weekend { color: #cc0000 !important; background-color: #f2f2f2; border-radius: 2px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* PENGATURAN PAGE BREAK KERTAS A4 LANDSCAPE */
        @page { size: A4 landscape; margin: 0.8cm; }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; margin: 0; }
            
            /* Memaksa pembagian kertas halaman baru */
            .page-break { page-break-before: always; break-before: page; clear: both; }
            
            /* Mengamankan row dari kerusakan flexbox browser */
            .row { display: flex !important; flex-wrap: wrap !important; }
            .col-4 { display: flex; flex: 0 0 33.333333% !important; max-width: 33.333333% !important; box-sizing: border-box !important; flex-direction: column; }
            
            .month-box { page-break-inside: avoid !important; break-inside: avoid !important; margin-bottom: 15px !important; }
            .legend-box, .table-kaldik, .signature-block { page-break-inside: avoid !important; break-inside: avoid !important; }
        }
    </style>
</head>
<body>

    <div class="no-print d-flex justify-content-between align-items-center alert alert-dark p-2 mb-3" style="border-radius: 6px;">
        <span class="small font-weight-bold text-white ps-2"><i class="bi bi-eye-fill me-1 text-warning"></i> Pratinjau Cetak Kalender Pendidikan</span>
        <div>
            <button onclick="window.print()" class="btn btn-warning btn-sm text-white font-weight-bold me-2 shadow-sm" style="background-color: #FF9F00; border: none;"><i class="bi bi-printer-fill me-1"></i> 🖨️ Cetak ke PDF</button>
            <button onclick="window.close()" class="btn btn-sm btn-secondary font-weight-bold px-3">Tutup</button>
        </div>
    </div>

    <div class="d-flex justify-content-center align-items-center border-bottom border-dark pb-2 mb-3 w-100">
        <div class="d-flex align-items-center justify-content-between" style="width: 85%;">
            <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan" style="height: 70px; width: auto; object-fit: contain;">
            <div class="text-center flex-grow-1 mx-4">
                <h5 class="my-0 font-weight-bold" style="font-weight: 800; color: #002060; font-size: 17px; letter-spacing: 0.5px;">
                    KALENDER PENDIDIKAN <?= strtoupper(esc($namaMadrasah)) ?>
                </h5>
                <h6 class="my-1 font-weight-bold" style="font-weight: 700; font-size: 13px;">TAHUN PELAJARAN <?= $tahunAktif ? $tahunAktif['academic_year'] : '-' ?></h6>
                <span class="badge font-weight-bold text-uppercase text-white px-3" style="font-size: 11px; background-color: #002060 !important; border-radius: 3px; padding: 2px 8px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">SEMESTER <?= $tahunAktif ? $tahunAktif['semester'] : '-' ?></span>
            </div>
            <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs" style="height: 70px; width: auto; object-fit: contain;">
        </div>
    </div>

    <?php
    $db = \Config\Database::connect();
    $hariKerjaSetting = 5; 
    if ($db->tableExists('settings')) {
        $getSetting = $db->table('settings')->where('key', 'kaldik_hari_kerja')->get()->getRowArray();
        if ($getSetting) $hariKerjaSetting = (int)$getSetting['value'];
    }

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
                        'jenis_matriks' => $ag['jenis_matriks'] ?? 'HEB'
                    ];
        }
    }

    $currentSemester = $tahunAktif ? $tahunAktif['semester'] : 'Ganjil';
    $rawYear = $tahunAktif ? $tahunAktif['academic_year'] : '2025/2026';
    $yearsArray = explode('/', $rawYear);

    if ($currentSemester === 'Ganjil') {
        $targetYear = $yearsArray[0];
        $bulanKaldik = [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    } else {
        $targetYear = $yearsArray[1];
        $bulanKaldik = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'];
    }

    $rekapBulanan = [];
    $totalSemesterHEB = 0; $totalSemesterHEF = 0; $totalSemesterHLCB = 0; $totalSemesterHari = 0;
    $mapDayIndex = [1 => 'sn', 2 => 'sl', 3 => 'rb', 4 => 'km', 5 => 'jm', 6 => 'sb', 0 => 'mn'];
    $listHariInisial = ['sn', 'sl', 'rb', 'km', 'jm', 'sb', 'mn'];
    ?>

    <div class="row g-2 mb-2">
        <?php foreach ($bulanKaldik as $numBulan => $namaBulan): ?>
            <div class="col-4" style="width: 33.333%;">
                <div class="month-box">
                    <div class="month-title"><?= $namaBulan ?> <?= $targetYear ?></div>
                    <div class="grid-days">
                        <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div>
                        <div class="<?= ($hariKerjaSetting == 5) ? 'text-danger' : '' ?>">Sab</div>
                        <div class="text-danger">Min</div>
                    </div>
                    <div class="grid-dates mb-2">
                        <?php
                        $wFirstDay = date('w', strtotime("$targetYear-$numBulan-01"));
                        $firstDayIndex = ($wFirstDay == 0) ? 6 : $wFirstDay - 1;
                        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);
                        $totalSemesterHari += $totalDaysInMonth;

                        foreach (['HEB', 'HEF', 'HLCB'] as $kat) {
                            foreach ($listHariInisial as $h) { $rekapBulanan[$numBulan][$kat][$h] = 0; }
                            $rekapBulanan[$numBulan][$kat]['jml'] = 0;
                        }

                        for ($i = 0; $i < $firstDayIndex; $i++) { echo '<div class="date-cell date-empty"></div>'; }

                        for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                            $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                            $dayOfWeek = (int)date('w', strtotime($fullDate));
                            $dayLabel = $mapDayIndex[$dayOfWeek];
                            $kategoriHari = 'HEB';

                            if (isset($mappedEvents[$fullDate])) {
                                $kategoriHari = $mappedEvents[$fullDate]['jenis_matriks'];
                            } else {
                                if ($dayOfWeek == 0 || ($dayOfWeek == 6 && $hariKerjaSetting == 5)) {
                                    $kategoriHari = 'HLCB';
                                }
                            }

                            $rekapBulanan[$numBulan][$kategoriHari][$dayLabel]++;
                            $rekapBulanan[$numBulan][$kategoriHari]['jml']++;

                            $styleCustom = '';
                            $classCell = 'date-cell';
                            if ($dayOfWeek == 0 || ($dayOfWeek == 6 && $hariKerjaSetting == 5)) { 
                                $classCell .= ' date-weekend'; 
                            }
                            if (isset($mappedEvents[$fullDate])) {
                                $styleCustom = 'background-color: ' . $mappedEvents[$fullDate]['color'] . '; border: 1px solid #777; -webkit-print-color-adjust: exact; print-color-adjust: exact;';
                            }

                            echo "<div class='$classCell' style='$styleCustom'>$tgl</div>";
                        }
                        
                        $totalSemesterHEB  += $rekapBulanan[$numBulan]['HEB']['jml'];
                        $totalSemesterHEF  += $rekapBulanan[$numBulan]['HEF']['jml'];
                        $totalSemesterHLCB += $rekapBulanan[$numBulan]['HLCB']['jml'];
                        ?>
                    </div>

                    <table class="table table-bordered text-center align-middle mt-auto" style="font-size: 8px; line-height: 1; font-weight: 700; border: 1px solid #000 !important; margin-bottom: 0 !important;">
                        <tr style="background-color: #d9e1f2; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            <td style="padding: 2px !important;">Hari</td><td style="padding: 2px !important;">Sn</td><td style="padding: 2px !important;">Sl</td><td style="padding: 2px !important;">Rb</td><td style="padding: 2px !important;">Km</td><td style="padding: 2px !important;">Jm</td>
                            <td style="padding: 2px !important; color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;">Sb</td>
                            <td style="padding: 2px !important; color:#c00000;">Mg</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px !important; background-color: #f2f2f2; text-align:left; padding-left:4px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">HEB</td><td><?= $rekapBulanan[$numBulan]['HEB']['sn'] ?></td><td><?= $rekapBulanan[$numBulan]['HEB']['sl'] ?></td><td><?= $rekapBulanan[$numBulan]['HEB']['rb'] ?></td><td><?= $rekapBulanan[$numBulan]['HEB']['km'] ?></td><td><?= $rekapBulanan[$numBulan]['HEB']['jm'] ?></td>
                            <td style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;"><?= $rekapBulanan[$numBulan]['HEB']['sb'] ?></td>
                            <td style="color:#c00000;"><?= $rekapBulanan[$numBulan]['HEB']['mn'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px !important; background-color: #f2f2f2; text-align:left; padding-left:4px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">HEF</td><td><?= $rekapBulanan[$numBulan]['HEF']['sn'] ?></td><td><?= $rekapBulanan[$numBulan]['HEF']['sl'] ?></td><td><?= $rekapBulanan[$numBulan]['HEF']['rb'] ?></td><td><?= $rekapBulanan[$numBulan]['HEF']['km'] ?></td><td><?= $rekapBulanan[$numBulan]['HEF']['jm'] ?></td>
                            <td style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;"><?= $rekapBulanan[$numBulan]['HEF']['sb'] ?></td>
                            <td style="color:#c00000;"><?= $rekapBulanan[$numBulan]['HEF']['mn'] ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px !important; background-color: #f2f2f2; text-align:left; padding-left:4px !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">HLCB</td><td><?= $rekapBulanan[$numBulan]['HLCB']['sn'] ?></td><td><?= $rekapBulanan[$numBulan]['HLCB']['sl'] ?></td><td><?= $rekapBulanan[$numBulan]['HLCB']['rb'] ?></td><td><?= $rekapBulanan[$numBulan]['HLCB']['km'] ?></td><td><?= $rekapBulanan[$numBulan]['HLCB']['jm'] ?></td>
                            <td style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;"><?= $rekapBulanan[$numBulan]['HLCB']['sb'] ?></td>
                            <td style="color:#c00000;"><?= $rekapBulanan[$numBulan]['HLCB']['mn'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <div class="page-break"></div>
    <div style="padding-top: 15px;"></div> 


    <?php if(!empty($agendaKaldik)): ?>
    <div class="legend-box border border-dark p-2 mb-4" style="background-color: #fffdf5; border-radius: 4px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
        <div class="font-weight-bold text-uppercase mb-2 pb-1" style="color: #002060; border-bottom: 1px dashed #ccc; font-size: 11px;">Keterangan Warna & Agenda Kegiatan:</div>
        <div class="row g-1">
            <?php 
            // Array untuk memetakan nama bulan dalam bahasa Indonesia
            $namaBulanIndo = [
                '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', 
                '05' => 'Mei', '06' => 'Jun', '07' => 'Juli', '08' => 'Agu', 
                '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
            ];

            foreach ($agendaKaldik as $ag): 
                $startDay = date('d', strtotime($ag['start_date']));
                $startMonth = date('m', strtotime($ag['start_date']));
                
                $endDay = date('d', strtotime($ag['end_date']));
                $endMonth = date('m', strtotime($ag['end_date']));
                
                if ($startMonth === $endMonth) {
                    if ($startDay === $endDay) {
                        $tglText = $startDay . ' ' . $namaBulanIndo[$startMonth];
                    } else {
                        $tglText = $startDay . '-' . $endDay . ' ' . $namaBulanIndo[$startMonth];
                    }
                } else {
                    $tglText = $startDay . ' ' . $namaBulanIndo[$startMonth] . ' - ' . $endDay . ' ' . $namaBulanIndo[$endMonth];
                }
            ?>
            <div class="col-4 mb-1">
                <table style="width: 100%; border: none; font-size: 10px; line-height: 1.2;">
                    <tr>
                        <td style="width: 12px; padding: 0; vertical-align: top; padding-top: 2px;">
                            <span style="display: block; width: 8px; height: 8px; border-radius: 2px; background-color: <?= $ag['color_hex'] ?>; border: 1px solid #777; -webkit-print-color-adjust: exact; print-color-adjust: exact;"></span>
                        </td>
                        <td style="width: 105px; min-width: 105px; max-width: 105px; padding: 0; vertical-align: top; white-space: nowrap; overflow: hidden;" class="font-weight-bold text-dark"><?= $tglText ?></td>
                        
                        <td style="width: 8px; padding: 0; vertical-align: top;" class="font-weight-bold text-center text-dark">:</td>
                        <td style="padding: 0; vertical-align: top; padding-left: 3px;" class="text-dark"><?= esc($ag['event_name']) ?></td>
                    </tr>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mb-2 p-1 text-center text-white text-uppercase" style="background-color: #002060 !important; border-radius: 4px; font-size: 13px; font-weight: 800; border: 1px solid #002060; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
        Rekapitulasi Analisis HEB, HEF dan HLCB
    </div>

    <div class="row g-2 mb-4">
        <?php foreach (['HEB' => ['Hari Efektif Belajar (HEB)', '#4f81bd', '#d9e1f2', true], 'HEF' => ['Hari Efektif Fakultatif (HEF)', '#ffc000', '#fff2cc', false], 'HLCB' => ['Hari Libur & Cuti Bersama (HLCB)', '#c00000', '#fce4d6', false]] as $keyKat => $meta): ?>
            <div class="col-4" style="width: 33.333%;">
                <table class="table table-bordered text-center mb-0 align-middle table-kaldik">
                    <thead class="text-white" style="background-color: <?= $meta[1] ?> !important; color: <?= $keyKat==='HEF'?'#000':'#fff' ?> !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <tr>
                            <th rowspan="2" class="text-center align-middle" style="width:34%; padding: 4px !important;"><?= $meta[0] ?></th>
                            <th colspan="7" class="py-0 align-middle">Sebaran Hari</th>
                            <th rowspan="2" class="align-middle">Jml</th>
                            <?php if ($meta[3]): ?> <th rowspan="2" class="align-middle">%</th> <?php endif; ?>
                        </tr>
                        <tr style="background-color: <?= $meta[2] ?>; color: #000; font-size: 8px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            <th>Sn</th><th>Sl</th><th>Rb</th><th>Km</th><th>Jm</th>
                            <th style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;">Sb</th>
                            <th style="color:#c00000;">Mg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $tSn=0;$tSl=0;$tRb=0;$tKm=0;$tJm=0;$tSb=0;$tMg=0;
                        foreach ($bulanKaldik as $nB => $nM): 
                            $hD = $rekapBulanan[$nB][$keyKat];
                            $tSn+=$hD['sn']; $tSl+=$hD['sl']; $tRb+=$hD['rb']; $tKm+=$hD['km']; $tJm+=$hD['jm']; $tSb+=$hD['sb']; $tMg+=$hD['mn'];
                            $pB = ($totalSemesterHEB > 0) ? round(($hD['jml'] / $totalSemesterHEB) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td class="text-start text-uppercase font-weight-bold text-dark align-middle" style="font-size: 8.5px; padding-left: 8px !important;"><?= strtoupper($nM) ?> <?= $targetYear ?></td>
                            <td><?= $hD['sn'] ?></td><td><?= $hD['sl'] ?></td><td><?= $hD['rb'] ?></td><td><?= $hD['km'] ?></td><td><?= $hD['jm'] ?></td>
                            <td style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;"><?= $hD['sb'] ?></td>
                            <td style="color:#c00000;"><?= $hD['mn'] ?></td>
                            <td class="bg-light-subtle font-weight-bold align-middle"><?= $hD['jml'] ?></td>
                            <?php if ($meta[3]): ?> <td class="text-muted align-middle"><?= $pB ?>%</td> <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: #e2e2e2; font-weight: 800; border-top: 1.5px solid #000; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                            <td class="text-center font-weight-bold align-middle">JUMLAH</td>
                            <td><?= $tSn ?></td><td><?= $tSl ?></td><td><?= $tRb ?></td><td><?= $tKm ?></td><td><?= $tJm ?></td>
                            <td style="color: <?= ($hariKerjaSetting == 5) ? '#c00000' : 'inherit' ?>;"><?= $tSb ?></td>
                            <td style="color:#c00000;"><?= $tMg ?></td>
                            <td class="text-dark align-middle" style="font-weight: 900; font-size: 10px;"><?= ${'totalSemester'.$keyKat} ?></td>
                            <?php if ($meta[3]): ?> <td class="text-success font-weight-bold align-middle">100%</td> <?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="d-flex justify-content-end text-end pe-4 signature-block" style="font-size: 11px; margin-top: 15px;">
        <div class="text-center" style="width: 250px;">
            <p class="mb-0"><?= esc($titiMangsa) ?></p>
            <p class="font-weight-bold" style="font-weight: 700; margin-bottom: 0; position: relative; z-index: 1;">Kepala Madrasah,</p>
            
            <img src="<?= base_url('assets/img/ttd_kamad.png') ?>" alt="TTD Kamad" style="height: 110px; width: auto; object-fit: contain; margin-top: -30px; margin-bottom: -28px; position: relative; z-index: 2; mix-blend-mode: multiply; transform: scale(0.85); left: -30px;">
            
            <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($kepalaNama) ?></p>
            <p class="text-muted small mb-0" style="font-size: 9px; position: relative; z-index: 3;">NPK. <?= esc($kepalaNpk) ?></p>
        </div>
    </div>

</body>
</html>
