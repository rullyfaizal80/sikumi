<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis HEB - SmartKurikulum MIMHa</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        :root {
            --mimha-primary: #FF9F00; 
            --mimha-accent: #FFC107;  
            --mimha-dark: #212529;    
            --mimha-bg: #F8F9FA;      
        }
        body { background-color: var(--mimha-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .text-primary-mimha { color: var(--mimha-primary) !important; }
        .text-accent-mimha { color: var(--mimha-accent) !important; }
        .bg-accent-mimha { background-color: var(--mimha-accent) !important; color: #000; }
        .border-primary-mimha { border-color: var(--mimha-primary) !important; }
        .btn-primary-mimha { background-color: var(--mimha-primary); border-color: var(--mimha-primary); color: #fff; font-weight: bold; }
        .btn-primary-mimha:hover { background-color: #e68f00; border-color: #e68f00; color: #fff; }
        .table-dark-header th { background-color: var(--mimha-dark) !important; color: #ffffff !important; border-color: #373b3e; text-transform: uppercase; }

        @media print {
            body { background-color: #fff; }
            .no-print { display: none !important; }
            .print-area { width: 100%; margin: 0; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
            .table-dark-header th { background-color: var(--mimha-dark) !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-accent-mimha { background-color: var(--mimha-accent) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-body-tertiary">

    <nav class="app-header navbar navbar-expand bg-body shadow-sm no-print">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <h4 class="navbar-text my-0 ps-2" style="color: #FF9F00; font-weight: 700;">
                            📊 Analisis HEB <span style="color: #FFC107;">SiKuMi</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ps-2">
                        <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary font-weight-bold">
                            🏠 Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    <div class="container-fluid px-4 pb-5">
        <br>
        <div class="card shadow-sm border-primary-mimha border-2 mb-4 no-print">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-funnel-fill text-accent-mimha me-2"></i> Filter Analisis Hari Efektif Belajar</h6>
            </div>
            <div class="card-body bg-white">
                <form method="GET" action="" class="row g-3 align-items-end" id="filterForm">
                    
                    <?php if(!$isGuru): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Pilih Guru</label>
                        <select name="teacher_id" class="form-select border-secondary shadow-sm" onchange="document.getElementById('filterForm').submit()">
                            <?php if(empty($teachers)): ?><option value="">- Belum ada guru di jadwal -</option><?php endif; ?>
                            <?php foreach($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $selectedTeacherId == $t['id'] ? 'selected' : '' ?>><?= esc($t['nama_guru']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Pilih Kelas Rombel</label>
                        <select name="rombel_id" class="form-select border-secondary shadow-sm" onchange="document.getElementById('filterForm').submit()">
                            <?php if(empty($rombelOptions)): ?><option value="">- Belum Ada Jadwal -</option><?php endif; ?>
                            <?php foreach($rombelOptions as $id => $opt): ?>
                                <option value="<?= $id ?>" <?= $selectedRombelId == $id ? 'selected' : '' ?>><?= esc($opt['rombel_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select border-secondary shadow-sm" onchange="document.getElementById('filterForm').submit()">
                            <?php if(empty($subjectOptions)): ?><option value="">- Pilih Kelas Dulu -</option><?php endif; ?>
                            <?php foreach($subjectOptions as $id => $opt): ?>
                                <option value="<?= $id ?>" <?= $selectedSubjectId == $id ? 'selected' : '' ?>><?= esc($opt['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if(!empty($analysisData)): ?>
        <div class="card shadow-sm border-0 print-area">
            <div class="card-body bg-white p-4 p-md-5">
                
                <div class="mb-4" style="font-family: Arial, sans-serif;">
                    <h5 class="fw-bold mb-3" style="font-size: 18px; text-decoration: underline;">PERHITUNGAN HARI EFEKTIF BELAJAR</h5>
                    <table class="fw-bold text-dark" style="font-size: 14px; line-height: 1.8; width: 100%; max-width: 600px;">
                        <tr><td width="30%">TP</td><td>: <?= esc($tahunAktif['academic_year']) ?></td></tr>
                        <tr><td>MATA PELAJARAN</td><td>: <?= strtoupper(esc($subjectOptions[$selectedSubjectId]['subject_name'] ?? '-')) ?></td></tr>
                        <tr><td>KELAS</td><td>: <?= strtoupper(esc($rombelOptions[$selectedRombelId]['rombel_name'] ?? '-')) ?></td></tr>
                        <tr><td>HARI MENGAJAR</td><td>: <?= esc($hariMengajarText) ?></td></tr>
                    </table>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered border-dark text-center align-middle" style="font-family: Arial, sans-serif; font-size: 14px;">
                        <thead class="table-dark-header">
                            <tr>
                                <th width="15%" class="border-dark py-3">BULAN</th>
                                <th width="15%" class="border-dark py-3">HARI</th>
                                <th width="15%" class="border-dark py-3">HEB</th>
                                <th width="15%" class="border-dark py-3">JP</th>
                                <th width="15%" class="border-dark py-3">JUMLAH</th>
                                <th width="25%" class="border-dark py-3">TOTAL (JP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($analysisData as $bulan): ?>
                                <?php foreach($bulan['detail'] as $index => $hari): 
                                    if ($hari['hari'] == 'Jumat') continue; 
                                ?>
                                <tr>
                                    <?php if($index == 0): ?>
                                        <td rowspan="4" class="border-dark align-middle fw-bold"><?= esc($bulan['nama_bulan']) ?></td>
                                    <?php endif; ?>
                                    
                                    <td class="border-dark text-start ps-3 fw-semibold"><?= $hari['hari'] ?></td>
                                    <td class="border-dark"><?= $hari['heb'] ?></td>
                                    <td class="border-dark"><?= $hari['jp'] ?></td>
                                    <td class="border-dark"><?= $hari['jumlah'] ?></td>
                                    
                                    <?php if($index == 0): ?>
                                        <td rowspan="4" class="border-dark align-middle fw-bold" style="font-size: 16px;">
                                            <?= $bulan['total_jp_bulan'] ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-accent-mimha fw-bold" style="font-size: 15px;">
                            <tr>
                                <td colspan="5" class="border-dark text-center py-3">TOTAL KESELURUHAN (JP)</td>
                                <td class="border-dark py-3 fs-5"><?= $grandTotalJp ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="text-end mt-4 no-print">
                    <button onclick="window.print()" class="btn btn-primary-mimha px-4 py-2 shadow">
                        <i class="bi bi-printer-fill me-2"></i> Cetak Analisis HEB
                    </button>
                </div>

            </div>
        </div>
        <?php else: ?>
            <div class="alert alert-secondary border-0 shadow-sm text-center py-5 no-print">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-dark fw-bold">Data Tidak Ditemukan / Belum Ada Jadwal</h5>
                <p class="text-muted">Silakan pilih Guru, Kelas, dan Mata Pelajaran yang memiliki jam mengajar di Jadwal Aktif.</p>
            </div>
        <?php endif; ?>

    </div>
     <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>