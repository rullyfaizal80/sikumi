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
                                'name'  => $ag['event_name'],
                                'color' => $ag['color_hex']
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

                                <div class="row g-3">
                                    <?php foreach ($bulanKaldik as $numBulan => $namaBulan): ?>
                                        <div class="col-md-4">
                                            <div class="month-box">
                                                <div class="month-title"><?= $namaBulan ?> <?= $targetYear ?></div>
                                                
                                                <!-- HEADER HARI: SENIN DI AWAL, SABTU DAN MINGGU DI AKHIR SESUAI INSTRUKSI -->
                                                <div class="grid-days">
                                                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div class="text-danger">Sab</div><div class="text-danger">Min</div>
                                                </div>
                                                
                                                <div class="grid-dates">
                                                    <?php
                                                    // Ambil hari dalam seminggu untuk tanggal 1 (0 = Minggu, 1 = Senin, ..., 6 = Sabtu)
                                                    $wFirstDay = date('w', strtotime("$targetYear-$numBulan-01"));
                                                    
                                                    // Sesuaikan indeks agar Senin menjadi hari pertama (0 = Senin, 1 = Selasa, ..., 5 = Sabtu, 6 = Minggu)
                                                    $firstDayIndex = ($wFirstDay == 0) ? 6 : $wFirstDay - 1;
                                                    
                                                    $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $numBulan, $targetYear);

                                                    // 1. Cetak Kotak Kosong sebelum Tanggal 1
                                                    for ($i = 0; $i < $firstDayIndex; $i++) {
                                                        echo '<div class="date-cell date-empty"></div>';
                                                    }

                                                    // 2. Cetak Angka Tanggal Utama
                                                    for ($tgl = 1; $tgl <= $totalDaysInMonth; $tgl++) {
                                                        $fullDate = sprintf('%s-%02d-%02d', $targetYear, $numBulan, $tgl);
                                                        $dayOfWeek = date('w', strtotime($fullDate)); // 0 = Minggu, 6 = Sabtu
                                                        
                                                        $styleCustom = '';
                                                        $titleTooltip = '';
                                                        $classCell = 'date-cell';

                                                        // Deteksi apakah hari ini Sabtu (6) atau Minggu (0) untuk pewarnaan tulisan merah dasar
                                                        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                                                            $classCell .= ' date-weekend';
                                                        }

                                                        // JIKA ADA KEGIATAN KHUSUS DI DATABASE: TIMPA WARNA DASAR WEEKEND SESUAI WARNA AGENDA
                                                        if (isset($mappedEvents[$fullDate])) {
                                                            $styleCustom = 'background-color: ' . $mappedEvents[$fullDate]['color'] . '; color: #000000 !important; border: 1px solid #bbb;';
                                                            $titleTooltip = 'title="' . esc($mappedEvents[$fullDate]['name']) . '"';
                                                        }

                                                        echo "<div class='$classCell' style='$styleCustom' $titleTooltip>$tgl</div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
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

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
    <script>
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('end_date').value = this.value;
        });
    </script>
</body>
</html>
