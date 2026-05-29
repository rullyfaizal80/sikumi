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

        /* Pengaturan Cetak yang Rapi untuk Grid 2 Kolom */
        .page-break-safe { page-break-inside: avoid; }
        
        @media print {
            body { background-color: #fff; }
            .no-print { display: none !important; }
            .print-area { width: 100%; margin: 0; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
            .table-dark-header th { background-color: var(--mimha-dark) !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-accent-mimha { background-color: var(--mimha-accent) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            
            /* Memaksa format 2 kolom berdampingan saat dicetak di kertas */
            .print-row { display: flex !important; flex-wrap: wrap !important; }
            .print-col-6 { width: 50% !important; flex: 0 0 50% !important; max-width: 50% !important; padding-right: 15px; padding-left: 15px; }
        }
    </style>
</head>
<body class="bg-body-tertiary">

    <nav class="navbar navbar-expand-lg bg-dark navbar-dark mb-4 shadow-sm no-print">
        <div class="container-fluid px-4">
            <a class="navbar-brand font-weight-bold" href="#">
                <span class="text-primary-mimha fw-bold" style="font-size: 22px;">SmartKurikulum</span> 
                <span class="text-accent-mimha fw-bold" style="font-size: 22px;">MIMHa</span>
            </a>
            <span class="navbar-text text-white badge bg-secondary ms-auto">
                Modul Analisis HEB (Akses: <?= esc($displayRole) ?>)
            </span>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">
        
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
                        <label class="form-label fw-bold small text-muted">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select border-secondary shadow-sm" onchange="document.getElementById('filterForm').submit()">
                            <?php if(empty($subjectOptions)): ?><option value="">- Belum ada mapel di jadwal -</option><?php endif; ?>
                            <?php foreach($subjectOptions as $id => $opt): ?>
                                <option value="<?= $id ?>" <?= $selectedSubjectId == $id ? 'selected' : '' ?>><?= esc($opt['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-auto ms-auto d-flex gap-2">
                        <a href="<?= base_url($isGuru ? 'guru' : 'admin') ?>" class="btn btn-secondary shadow-sm px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        
                        <?php if(!empty($allAnalysisData)): ?>
                        <button type="button" onclick="window.print()" class="btn btn-primary-mimha shadow-sm px-4">
                            <i class="bi bi-printer-fill me-1"></i> Cetak PDF
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if(!empty($allAnalysisData)): ?>
            <div class="card shadow-sm border-0 print-area">
                <div class="card-body bg-white p-4">
                    
                    <div class="row print-row">
                        
                        <?php foreach($allAnalysisData as $dataKelas): ?>
                        <div class="col-md-6 print-col-6 mb-5 page-break-safe">
                            
                            <div class="mb-3 p-3 bg-light rounded border" style="font-family: Arial, sans-serif;">
                                <h5 class="fw-bold mb-2 text-center" style="font-size: 15px; text-decoration: underline;">ANALISIS HEB</h5>
                                <table class="fw-bold text-dark" style="font-size: 13px; line-height: 1.5; width: 100%;">
                                    <tr><td width="30%">TP</td><td>: <?= esc($tahunAktif['academic_year']) ?></td></tr>
                                    <tr><td>MAPEL</td><td>: <?= strtoupper(esc($subjectOptions[$selectedSubjectId]['subject_name'] ?? '-')) ?></td></tr>
                                    <tr><td>KELAS</td><td class="text-primary-mimha" style="font-size: 15px;">: <?= strtoupper(esc($dataKelas['rombel_name'])) ?></td></tr>
                                    <tr><td>HARI</td><td>: <?= esc($dataKelas['hari_mengajar']) ?></td></tr>
                                </table>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered border-dark text-center align-middle" style="font-family: Arial, sans-serif; font-size: 13px;">
                                    <thead class="table-dark-header">
                                        <tr>
                                            <th width="20%" class="border-dark py-2">BULAN</th>
                                            <th width="15%" class="border-dark py-2">HARI</th>
                                            <th width="15%" class="border-dark py-2">HEB</th>
                                            <th width="15%" class="border-dark py-2">JP</th>
                                            <th width="15%" class="border-dark py-2">JML</th>
                                            <th width="20%" class="border-dark py-2">TOTAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($dataKelas['analysisData'] as $bulan): ?>
                                            <?php foreach($bulan['detail'] as $index => $hari): 
                                                if ($hari['hari'] == 'Jumat') continue; 
                                            ?>
                                            <tr>
                                                <?php if($index == 0): ?>
                                                    <td rowspan="4" class="border-dark align-middle fw-bold"><?= esc($bulan['nama_bulan']) ?></td>
                                                <?php endif; ?>
                                                
                                                <td class="border-dark text-start ps-2 fw-semibold"><?= $hari['hari'] ?></td>
                                                <td class="border-dark"><?= $hari['heb'] ?></td>
                                                <td class="border-dark"><?= $hari['jp'] ?></td>
                                                <td class="border-dark"><?= $hari['jumlah'] ?></td>
                                                
                                                <?php if($index == 0): ?>
                                                    <td rowspan="4" class="border-dark align-middle fw-bold" style="font-size: 14px;">
                                                        <?= $bulan['total_jp_bulan'] ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-accent-mimha fw-bold" style="font-size: 14px;">
                                        <tr>
                                            <td colspan="5" class="border-dark text-center py-2">TOTAL (JP)</td>
                                            <td class="border-dark py-2 fs-6"><?= $dataKelas['grandTotalJp'] ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                        </div>
                        <?php endforeach; ?>

                    </div> </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary border-0 shadow-sm text-center py-5 no-print">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-dark fw-bold">Data Tidak Ditemukan / Belum Ada Jadwal</h5>
                <p class="text-muted">Silakan pilih Guru dan Mata Pelajaran yang memiliki jam mengajar di Jadwal Aktif.</p>
            </div>
        <?php endif; ?>

    </div>
    
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>