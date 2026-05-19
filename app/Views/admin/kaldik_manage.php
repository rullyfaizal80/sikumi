<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Tampilan Kalender 6 Bulan Dinamis</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9 !important; font-family: 'Source Sans Pro', sans-serif; }
        .card-kaldik { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        .btn-warning-custom { background-color: #FF9F00 !important; border: none !important; color: #ffffff !important; font-weight: 600; }
        .btn-warning-custom:hover { background-color: #e68f00 !important; }
        
        /* BOX LAYOUT PER BULAN */
        .month-box { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); min-height: 230px; }
        .month-title { background-color: #212529; color: #ffffff; text-align: center; font-weight: 700; padding: 4px; border-radius: 4px; font-size: 13px; text-uppercase: true; margin-bottom: 8px; }
        
        /* HEADER HARI: SENIN DI AWAL MINGGU SESUAI INSTRUKSI */
        .grid-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-size: 11px; font-weight: 700; color: #495057; margin-bottom: 5px; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; }
        .grid-dates { display: grid; grid-template-columns: repeat(7, 1fr); row-gap: 4px; column-gap: 4px; text-align: center; }
        
        /* STYLE ANGKA TANGGAL */
        .date-cell { font-size: 11px; font-weight: 700; padding: 4px 0; border-radius: 4px; color: #212529; border: 1px solid transparent; }
        .date-empty { visibility: hidden; }
        
        /* WARNA MERAH UNTUK AKHIR MINGGU (SABTU & MINGGU) */
        .date-weekend { color: #dc3545 !important; background-color: #fff5f5; border-radius: 4px; }
        
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; }
        .legend-color { width: 16px; height: 16px; border-radius: 4px; border: 1px solid #ddd; }

        @media print {
            .app-header, .col-lg-3, .no-print, form, button, .alert { display: none !important; }
            .col-lg-9 { width: 100% !important; }
            body { background: white !important; padding: 0; }
            .card-kaldik { border: none !important; }
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg">
    <div class="app-wrapper">
        
        <!-- NAVBAR ATAS -->
        <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm py-2">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item d-flex align-items-center ps-2">
                        <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" class="me-2" style="height: 28px;">
                        <h4 class="navbar-text my-0 font-weight-bold text-dark" style="font-weight: 700; font-size: 17px;">
                            Kalender Pendidikan <span style="color: #FF9F00;">MIMHa Tsanawiyah</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <form action="<?= base_url('admin/kaldik') ?>" method="GET" class="d-flex align-items-center gap-2 bg-light p-1 rounded border">
                            <label class="small font-weight-bold text-muted mb-0 ps-2">Kelas:</label>
                            <select name="class_id" class="form-select form-select-sm border-0 font-weight-bold bg-light" onchange="this.form.submit()" style="width: 110px;">
                                <?php foreach ($daftarKelas as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $kelasTerpilih == $k['id'] ? 'selected' : '' ?>>
                                        Kelas <?= $k['class_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </li>
                    <li class="nav-item"><button type="button" class="btn btn-outline-dark btn-sm font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCopyKaldik"><i class="bi bi-copy me-1"></i> Copy Kaldik</button></li>
                    <li class="nav-item"><a href="<?= base_url('/') ?>" class="btn btn-sm btn-secondary font-weight-bold px-3">Dashboard</a></li>
                </ul>
            </div>
        </nav>

        <!-- AREA KONTEN KERJA -->
        <main class="app-main pt-4 pb-4">
            <div class="app-content">
                <div class="container-fluid px-4">
                    
                    <!-- INFO NOTIFIKASI -->
                    <?php if (session()->getFlashdata('sukses')): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
                            🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // 1. LOGIKA UTAMAKAN PEMETAAN TANGGAL KEGIATAN DARI DATABASE
                    $mappedEvents = [];
                    foreach ($agendaKaldik as $ag) {
                        $start = strtotime($ag['start_date']);
                        $end = strtotime($ag['end_date']);
                        for ($current = $start; $current <= $end; $current += 86400) {
                            $dateKey = date('Y-m-d', $current);
                            $mappedEvents[$dateKey] = [
                                'id'          => $ag['id'],
                                'name'  => $ag['event_name'],
                                'color' => $ag['color_hex'],
                                'category_id' => $ag['category_id']
                            ];
                        }
                    }

                    // 2. DETEKSI TAHUN & STRUKTUR BULAN SECARA DINAMIS BERDASARKAN SEMESTER AKTIF
                    $currentSemester = $tahunAktif ? $tahunAktif['semester'] : 'Ganjil';
                    $rawYear = $tahunAktif ? $tahunAktif['academic_year'] : '2025/2026';
                    $yearsArray = explode('/', $rawYear); // Memecah "2025/2026" menjadi ["2025", "2026"]

                    if ($currentSemester === 'Ganjil') {
                        // Jika Ganjil: Tampilkan Juli s/d Desember menggunakan Tahun Awal (contoh: 2025)
                        $targetYear = $yearsArray[0];
                        $bulanKaldik = [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                    } else {
                        // Jika Genap: Tampilkan Januari s/d Juni menggunakan Tahun Akhir (contoh: 2026)
                        $targetYear = $yearsArray[1] ?? $yearsArray[0];
                        $bulanKaldik = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni'];
                    }
                    ?>

                    <div class="row g-4">
                        <!-- GRID KALENDER 6 BULAN (SISI KIRI) -->
                        <div class="col-lg-9">
                            <div class="card card-kaldik shadow-sm border-top border-warning border-3 p-4">
                                
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                                    <div>
                                        <h4 class="text-dark my-0" style="font-weight: 800; font-size: 20px;">KALENDER PENDIDIKAN SEMESTER <?= strtoupper($currentSemester) ?></h4>
                                        <span class="text-muted small">Target Monitor: <strong>Kelas <?= $kelasTerpilih == 1 ? '7' : ($kelasTerpilled == 2 ? '8' : '9') ?> (MTs)</strong> | Tahun Pelajaran <?= $rawYear ?></span>
                                    </div>
                                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary font-weight-bold"><i class="bi bi-printer-fill me-1"></i> Cetak Kalender (PDF)</button>
                                </div>

                                                                <!-- ======================================================== -->
                                <!-- GRID LAYOUT 6 KOTAK BULANAN + RINCIAN AGENDA DI BAWAHNYA -->
                                <!-- ======================================================== -->
                                <div class="row g-3">
                                    <?php foreach ($bulanKaldik as $numBulan => $namaBulan): ?>
                                        <div class="col-md-4">
                                            <!-- month-box dibuat d-flex flex-column agar tinggi kotak seragam dan rapi -->
                                            <div class="month-box d-flex flex-column h-100">
                                                
                                                <!-- Kepala Judul Bulan -->
                                                <div class="month-title"><?= $namaBulan ?> <?= $targetYear ?></div>
                                                
                                                <!-- Header Nama Hari (Senin di Awal) -->
                                                <div class="grid-days">
                                                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div class="text-danger">Sab</div><div class="text-danger">Min</div>
                                                </div>
                                                
                                                <!-- Grid Angka Penanggalan -->
                                                <div class="grid-dates mb-3">
                                                    <?php
                                                    $wFirstDay = date('w', strtotime("$targetYear-$numBulan-01"));
                                                    $firstDayIndex = ($wFirstDay == 0) ? 6 : $wFirstDay - 1;
                                                    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);

                                                    // 1. Cetak Kotak Kosong sebelum Tanggal 1
                                                    for ($i = 0; $i < $firstDayIndex; $i++) {
                                                        echo '<div class="date-cell date-empty"></div>';
                                                    }

                                                    // 2. Cetak Angka Tanggal Utama
                                                    for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                                        $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                                        $dayOfWeek = date('w', strtotime($fullDate));
                                                        
                                                        $styleCustom = '';
                                                        $titleTooltip = '';
                                                        $classCell = 'date-cell';

                                                        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                                                            $classCell .= ' date-weekend';
                                                        }

                                                        // JIKA ADA KEGIATAN KHUSUS DI DATABASE
                                                        if (isset($mappedEvents[$fullDate])) {
                                                            $styleCustom = 'background-color: ' . $mappedEvents[$fullDate]['color'] . '; color: #000000 !important; border: 1px solid #bbb; cursor: pointer;';
                                                            $titleTooltip = 'title="' . esc($mappedEvents[$fullDate]['name']) . '"';
                                                            
                                                            // Klik memicu MODAL EDIT/HAPUS
                                                            echo "<div class='$classCell btn-tanggal-aktif' style='$styleCustom' $titleTooltip data-id='".$mappedEvents[$fullDate]['id']."' data-name='".esc($mappedEvents[$fullDate]['name'])."' data-start='$fullDate' data-end='$fullDate' data-cat='".$mappedEvents[$fullDate]['category_id']."'>$tgl</div>";
                                                        } else {
                                                            // Tanggal Polos memicu MODAL TAMBAH LANGSUNG
                                                            echo "<div class='$classCell btn-tanggal-polos' style='cursor: pointer;' data-date='$fullDate'>$tgl</div>";
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                                                                <!-- ======================================================== -->
                                                <!-- REVISI SENSOR LOGIKA MATRIKS BULANAN (AKURAT & PRESISI) -->
                                                <!-- ======================================================== -->
                                                <div class="mt-3 border-top pt-2">
                                                    <?php
                                                    // Inisialisasi ulang matriks sebaran hari bulanan (Sn=1, Sl=2, Rb=3, Km=4, Jm=5, Sb=6, Mg=0)
                                                    $matriksHari = [
                                                        'HEB'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0],
                                                        'HEF'  => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0],
                                                        'HLCB' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 0 => 0],
                                                    ];

                                                                                                        // Hitung klasifikasi hari dari tanggal 1 sampai akhir bulan berjalan
                                                    for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                                        $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                                        $dayOfWeek = (int)date('w', strtotime($fullDate)); // 0=Mg, 1=Sn, ..., 6=Sb

                                                        // A. JIKA TANGGAL TERSEBUT MEMILIKI AGENDA DI DATABASE
                                                        if (isset($mappedEvents[$fullDate])) {
                                                            // Tarik ID Kategori asli dari data tanggal terkait
                                                            $idKategori = (int)$mappedEvents[$fullDate]['category_id'];
                                                            
                                                            // ATURAN 1: MASUK HEF -> Kategori Asesmen/Ujian (4) & Kegiatan Sekolah (5)
                                                            if ($idKategori === 4 || $idKategori === 5) {
                                                                $matriksHari['HEF'][$dayOfWeek]++;
                                                            } 
                                                            // ATURAN 2: MASUK HLCB -> Kategori Libur Nasional (2) & Libur Khusus MIMHa (3)
                                                            elseif ($idKategori === 2 || $idKategori === 3) {
                                                                $matriksHari['HLCB'][$dayOfWeek]++;
                                                            } 
                                                            // ATURAN 3: MASUK HEB -> Hanya Kategori Hari Efektif Belajar (1)
                                                            else {
                                                                $matriksHari['HEB'][$dayOfWeek]++;
                                                            }
                                                        } 
                                                        // B. JIKA TANGGAL POLOS TANPA AGENDA KEGIATAN KUSTOM
                                                        else {
                                                            // Hari Sabtu (6) dan Minggu (0) otomatis masuk HLCB jika tidak ada kegiatan
                                                            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                                                                $matriksHari['HLCB'][$dayOfWeek]++;
                                                            } else {
                                                                $matriksHari['HEB'][$dayOfWeek]++;
                                                            }
                                                        }
                                                    }

                                                    ?>

                                                    <!-- CETAK STRUKTUR TABEL DESAIN PREMIUM MINIMALIS -->
                                                    <table class="table table-bordered text-center my-2 py-0 align-middle shadow-sm bg-white" style="font-size: 10px; line-height: 1.1; font-weight: 700; border: 1px solid #dee2e6;">
                                                        <thead>
                                                            <tr style="background-color: #9cc2e5; color: #000;">
                                                                <th style="padding: 4px; font-size: 11px; text-align: center;">Hari</th>
                                                                <th style="padding: 4px; width: 11%;">Sn</th>
                                                                <th style="padding: 4px; width: 11%;">Sl</th>
                                                                <th style="padding: 4px; width: 11%;">Rb</th>
                                                                <th style="padding: 4px; width: 11%;">Km</th>
                                                                <th style="padding: 4px; width: 11%;">Jm</th>
                                                                <th style="padding: 4px; width: 11%; color: #cc0000;">Sb</th>
                                                                <th style="padding: 4px; width: 11%; color: #cc0000;">Mg</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td style="padding: 4px; background-color: #f2f2f2; text-align: left; padding-left: 6px;">HEB</td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEB'][1] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEB'][2] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEB'][3] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEB'][4] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEB'][5] ?></td>
                                                                <td style="padding: 4px;" class="text-muted"><?= $matriksHari['HEB'][6] ?></td>
                                                                <td style="padding: 4px;" class="text-muted"><?= $matriksHari['HEB'][0] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding: 4px; background-color: #f2f2f2; text-align: left; padding-left: 6px;">HEF</td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEF'][1] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEF'][2] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEF'][3] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEF'][4] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HEF'][5] ?></td>
                                                                <td style="padding: 4px;" class="text-muted"><?= $matriksHari['HEF'][6] ?></td>
                                                                <td style="padding: 4px;" class="text-muted"><?= $matriksHari['HEF'][0] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding: 4px; background-color: #f2f2f2; text-align: left; padding-left: 6px;">HLCB</td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HLCB'][1] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HLCB'][2] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HLCB'][3] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HLCB'][4] ?></td>
                                                                <td style="padding: 4px;"><?= $matriksHari['HLCB'][5] ?></td>
                                                                <td style="padding: 4px;" class="text-danger"><?= $matriksHari['HLCB'][6] ?></td>
                                                                <td style="padding: 4px;" class="text-danger"><?= $matriksHari['HLCB'][0] ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>


                                                <!-- ======================================================== -->
                                                <!-- ORIGINAL: LIST DAFTAR AGENDA RINCIAN BULANAN (SISI BAWAH)-->
                                                <!-- ======================================================== -->
                                                <div class="mt-2 border-top pt-2" style="min-height: 60px; max-height: 120px; overflow-y: auto;">
                                                    <span class="d-block text-muted font-weight-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">📝 Agenda Kegiatan:</span>
                                                    <!-- Sisa perulangan foreach ($agendaKaldik as $ag) kemarin biarkan tetap utuh dibawah sini... -->

                                                <!-- ======================================================== -->
                                                <!-- BAGIAN BARU: LIST DAFTAR AGENDA RINCIAN BULANAN -->
                                                <!-- ======================================================== 
                                                <div class="mt-auto border-top pt-2" style="min-height: 80px; max-height: 150px; overflow-y: auto;">
                                                    <span class="d-block text-muted font-weight-bold mb-1" style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">📝 Agenda Kegiatan:</span>
                                                    -->
                                                    <?php 
                                                    $hasEvent = false;
                                                    foreach ($agendaKaldik as $ag): 
                                                        // Deteksi apakah rentang agenda ini bersinggungan dengan bulan yang sedang aktif di-loop
                                                        $startMonth = (int)date('m', strtotime($ag['start_date']));
                                                        $endMonth = (int)date('m', strtotime($ag['end_date']));
                                                        
                                                        if ($numBulan >= $startMonth && $numBulan <= $endMonth):
                                                            $hasEvent = true;
                                                    ?>
                                                        <div class="d-flex align-items-start mb-1" style="font-size: 11px; line-height: 1.3;">
                                                            <!-- Dot Penanda Warna Kategori Sesuai Database -->
                                                            <span class="me-2 mt-1 shadow-sm border" style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= $ag['color_hex'] ?>; flex-shrink: 0;"></span>
                                                            <div class="text-dark">
                                                                <!-- Format Rentang Tanggal Pendek -->
                                                                <span class="text-muted font-weight-bold" style="font-size: 10px;">
                                                                    <?php if ($ag['start_date'] === $ag['end_date']): ?>
                                                                        <?= date('d', strtotime($ag['start_date'])) ?>:
                                                                    <?php else: ?>
                                                                        <?= date('d', strtotime($ag['start_date'])) ?>-<?= date('d', strtotime($ag['end_date'])) ?>:
                                                                    <?php endif; ?>
                                                                </span>
                                                                <?= esc($ag['event_name']) ?>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    
                                                    if (!$hasEvent): 
                                                    ?>
                                                        <span class="text-muted italic small d-block pt-1" style="font-size: 11px; font-style: italic;">Tidak ada agenda kegiatan.</span>
                                                    <?php endif; ?>
                                                </div>

                                            </div> <!-- End Month Box -->
                                        </div>
                                    <?php endforeach; ?>
                                </div>


                                <!-- LEGEND WARNA KATEGORI -->
                                <div class="border-top mt-4 pt-3 no-print">
                                    <h6 class="font-weight-bold text-muted small mb-2">KETERANGAN INDIKATOR WARNA AGENDA:</h6>
                                    <div class="d-flex flex-wrap gap-4">
                                        <?php foreach ($daftarWarna as $dw): ?>
                                            <div class="legend-item">
                                                <div class="legend-color" style="background-color: <?= $dw['color_hex'] ?>;"></div>
                                                <span><?= $dw['category_name'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                            </div>
                        </div>

                        

                        <!-- FORM INPUT PLOTING AGENDA (SISI KANAN) -->
                        <div class="col-lg-3 no-print">
                            <div class="card card-kaldik shadow-sm border-top border-warning border-3 p-3">
                                <h5 class="mb-3" style="font-weight: 600; color: #333;">➕ Ploting Agenda</h5>
                                <form action="<?= base_url('admin/kaldik/store') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="academic_year_id" value="<?= $tahunAktif['id'] ?? 1 ?>">
                                    <input type="hidden" name="class_id" value="<?= $kelasTerpilih ?>">

                                    <div class="mb-2">
                                        <label class="form-label small font-weight-bold text-muted mb-1">Mulai:</label>
                                        <input type="date" name="start_date" class="form-control form-control-sm" required id="start_date">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small font-weight-bold text-muted mb-1">Selesai:</label>
                                        <input type="date" name="end_date" class="form-control form-control-sm" required id="end_date">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small font-weight-bold text-muted mb-1">Nama Kegiatan:</label>
                                        <input type="text" name="event_name" class="form-control form-control-sm" placeholder="contoh: Penilaian Akhir Jenjang" required autocomplete="off">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small font-weight-bold text-muted mb-1">Kategori:</label>
                                        <select name="category_id" class="form-select form-select-sm" required>
                                            <option value="" disabled selected>-- Pilih Kategori --</option>
                                            <?php foreach ($daftarWarna as $dw): ?>
                                                <option value="<?= $dw['id'] ?>"><?= $dw['category_name'] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-warning-custom w-100 py-2 rounded shadow-sm">
                                        💾 Ploting Ke Kalender
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
                                                                  <!-- ======================================================== -->
                                <!-- REVISI FINAL: TABEL MATRIKS ANALISIS HARI EFEKTIF BELAJAR RESMI MIMHA -->
                                <!-- ======================================================== -->
                                <div class="border-top mt-4 pt-4">
                                    <h5 class="text-dark mb-3" style="font-weight: 700; font-size: 16px;">📊 REKAPITULASI DAN ANALISIS HARI EFEKTIF BELAJAR PER SEMESTER</h5>
                                    <div class="table-responsive shadow-sm rounded">
                                        <table class="table table-bordered table-striped text-center mb-0 align-middle small" style="font-size: 11px;">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th rowspan="2" class="align-middle text-start ps-3" style="width: 15%;">BULAN</th>
                                                    <th colspan="7" class="bg-secondary text-white py-1">JUMLAH HARI PER MINGGU</th>
                                                    <th rowspan="2" class="align-middle bg-success text-white" style="width: 10%;">HEB<br>(EFEKTIF)</th>
                                                    <th rowspan="2" class="align-middle bg-warning text-dark" style="width: 10%;">HEF<br>(EVENT)</th>
                                                    <th rowspan="2" class="align-middle bg-danger text-white" style="width: 10%;">HLCB<br>(LIBUR)</th>
                                                    <th rowspan="2" class="align-middle bg-info text-white" style="width: 10%;">PROSENTASE<br>(%) HEB</th>
                                                </tr>
                                                <tr class="bg-dark text-white-50" style="font-size: 10px;">
                                                    <th style="width: 5%;">Sn</th>
                                                    <th style="width: 5%;">Sl</th>
                                                    <th style="width: 5%;">Rb</th>
                                                    <th style="width: 5%;">Km</th>
                                                    <th style="width: 5%;">Jm</th>
                                                    <th style="width: 5%; color: #ff8787;">Sb</th>
                                                    <th style="width: 5%; color: #ff8787;">Mn</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Variabel akumulasi akhir semester
                                                $totalSemesterHEB  = 0;
                                                $totalSemesterHEF  = 0;
                                                $totalSemesterHLCB = 0;
                                                $totalSemesterHari = 0;

                                                // Array pembantu mapping hari PHP (1=Senin, 2=Selasa, ..., 6=Sabtu, 0=Minggu)
                                                $mapDayIndex = [1 => 'sn', 2 => 'sl', 3 => 'rb', 4 => 'km', 5 => 'jm', 6 => 'sb', 0 => 'mn'];

                                                foreach ($bulanKaldik as $numBulan => $namaBulan):
                                                    // Reset hitungan per bulan
                                                    $countDays = ['sn' => 0, 'sl' => 0, 'rb' => 0, 'km' => 0, 'jm' => 0, 'sb' => 0, 'mn' => 0];
                                                    $hebBulan  = 0;
                                                    $hefBulan  = 0;
                                                    $hlcbBulan = 0;

                                                    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);
                                                    $totalSemesterHari += $totalDaysInMonth;

                                                    // Pemindaian otomatis 1 s/d 31 tanggal bulan berjalan
                                                    for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                                        $fullDate  = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                                        $dayOfWeek = date('w', strtotime($fullDate)); // 0=Minggu, 6=Sabtu, 1=Senin...
                                                        
                                                        // Tambah hitungan sebaran hari mingguan
                                                        $dayLabel = $mapDayIndex[$dayOfWeek];
                                                        $countDays[$dayLabel]++;

                                                        // FILTER LOGIKA PILIHAN KATEGORI AGENDA DATABASE
                                                        if (isset($mappedEvents[$fullDate])) {
                                                            $namaAgenda = strtolower($mappedEvents[$fullDate]['name']);
                                                            
                                                            if (str_contains($namaAgenda, 'ujian') || str_contains($namaAgenda, 'asesmen') || str_contains($namaAgenda, 'tka') || str_contains($namaAgenda, 'pas') || str_contains($namaAgenda, 'pts') || str_contains($namaAgenda, 'um')) {
                                                                $hefBulan++;
                                                            } elseif (str_contains($namaAgenda, 'libur') || str_contains($namaAgenda, 'cuti') || str_contains($namaAgenda, 'ramadhan')) {
                                                                $hlcbBulan++;
                                                            } else {
                                                                $hebBulan++;
                                                            }
                                                        } else {
                                                            // Aturan dasar weekend sekolah otomatis memotong libur
                                                            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                                                                $hlcbBulan++;
                                                            } else {
                                                                $hebBulan++;
                                                            }
                                                        }
                                                    }

                                                    // Hitung persentase HEB Bulanan (HEB / Total Hari Kalender * 100)
                                                    $persenHEB = ($totalDaysInMonth > 0) ? round(($hebBulan / $totalDaysInMonth) * 100, 1) : 0;

                                                    // Akumulasi total
                                                    $totalSemesterHEB  += $hebBulan;
                                                    $totalSemesterHEF  += $hefBulan;
                                                    $totalSemesterHLCB += $hlcbBulan;
                                                ?>
                                                <tr>
                                                    <td class="text-start ps-3 font-weight-bold text-dark"><?= $namaBulan ?> <?= $targetYear ?></td>
                                                    <td><?= $countDays['sn'] ?></td>
                                                    <td><?= $countDays['sl'] ?></td>
                                                    <td><?= $countDays['rb'] ?></td>
                                                    <td><?= $countDays['km'] ?></td>
                                                    <td><?= $countDays['jm'] ?></td>
                                                    <td class="text-danger bg-light-subtle"><?= $countDays['sb'] ?></td>
                                                    <td class="text-danger bg-light-subtle"><?= $countDays['mn'] ?></td>
                                                    <td class="font-weight-bold text-success" style="font-size: 12px;"><?= $hebBulan ?></td>
                                                    <td class="font-weight-bold text-warning" style="font-size: 12px;"><?= $hefBulan ?></td>
                                                    <td class="font-weight-bold text-danger" style="font-size: 12px;"><?= $hlcbBulan ?></td>
                                                    <td class="font-weight-bold text-info" style="font-size: 12px; background-color: #f0fafd;"><?= $persenHEB ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                                
                                                <!-- BARIS REKAPITULASI TOTAL SEMESTER AKHIR -->
                                                <?php 
                                                // Rata-rata persentase semester total
                                                $totalPersenSemester = ($totalSemesterHari > 0) ? round(($totalSemesterHEB / $totalSemesterHari) * 100, 1) : 0;
                                                ?>
                                                <tr class="table-secondary font-weight-bold text-dark" style="font-size: 12px;">
                                                    <td class="text-start ps-3">JUMLAH TOTAL</td>
                                                    <td colspan="7" class="text-muted small italic font-weight-normal text-center">Rekapitulasi Kumulatif Semester</td>
                                                    <td class="text-success" style="font-weight: 800;"><?= $totalSemesterHEB ?></td>
                                                    <td class="text-warning" style="font-weight: 800;"><?= $totalSemesterHEF ?></td>
                                                    <td class="text-danger" style="font-weight: 800;"><?= $totalSemesterHLCB ?></td>
                                                    <td class="text-info" style="font-weight: 800; background-color: #e3f7fc;"><?= $totalPersenSemester ?>%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>



    <!-- JENDELA MODAL COPY -->
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
                                <label class="small font-weight-bold text-muted">Dari Kelas:</label>
                                <select name="from_class_id" class="form-select form-select-sm" required>
                                    <?php foreach ($daftarKelas as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= $kelasTerpilih == $k['id'] ? 'selected' : '' ?>>Kelas <?= $k['class_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="small font-weight-bold text-muted">Ke Kelas:</label>
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

    <!-- JENDELA POP-UP MODAL EDIT & HAPUS AGENDA (AKSI KLIK TANGGAL) -->
<div class="modal fade" id="modalAksiAgenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius: 8px;">
            <div class="modal-header bg-dark text-white font-weight-bold">
                <h5 class="modal-title" id="judulModalAksi">⚙️ Kelola Agenda Tanggal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- FORM EDIT DATA -->
            <form action="<?= base_url('admin/kaldik/update') ?>" method="POST" id="formAksiKaldik">
                <?= csrf_field() ?>
                <input type="hidden" name="agenda_id" id="edit_agenda_id">
                <input type="hidden" name="class_id" value="<?= $kelasTerpilih ?>">

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
                    <!-- TOMBOL HAPUS (KIRI) -->
                    <button type="button" id="btnHapusAgenda" class="btn btn-sm btn-danger px-3 font-weight-bold"><i class="bi bi-trash3-fill"></i> Hapus Agenda</button>
                    <div>
                        <button type="button" class="btn btn-sm btn-secondary px-3 font-weight-bold me-1" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnSimpanAksi" class="btn btn-sm btn-warning-custom px-3 font-weight-bold">💾 Simpan</button>
                    </div>
                </div>
            </form>
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
</body>
</html>
