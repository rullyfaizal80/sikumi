<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Analisis HEB - SmartKurikulum MIMHa</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 20px 0; background-color: #525659; }
        .a4-paper { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 8mm 8mm; box-shadow: 0 0 15px rgba(0,0,0,0.4); position: relative; }

        @page { size: A4 portrait; margin: 8mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .a4-paper { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-actions-wrapper { display: none !important; }
        }

        /* UTILITY CLASS */
        .d-flex { display: flex !important; }
        .justify-content-between { justify-content: space-between !important; }
        .justify-content-center { justify-content: center !important; }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .font-weight-bold { font-weight: bold !important; }
        .text-muted { color: #6c757d !important; }
        .d-inline-block { display: inline-block !important; }

        /* HEADER */
        .header-container { display: flex; justify-content: center; align-items: center; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 8px; width: 100%; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 30px; width: 100%; }
        .header-content img { height: 65px; width: auto; object-fit: contain; }
        .header-text { text-align: center; margin: 0; line-height: 1.1; }
        .header-text h5 { margin: 0 0 2px 0; font-weight: 800; color: #002060; font-size: 13px; letter-spacing: 0.5px; }
        .header-text h6 { margin: 2px 0; font-weight: 700; font-size: 10px; }
        .badge-semester { font-weight: bold; text-transform: uppercase; color: #fff; font-size: 8.5px; background-color: #002060 !important; border-radius: 3px; padding: 2px 8px; display: inline-block; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* INFO GURU & MAPEL RATA KIRI */
        .info-table { width: auto; margin-bottom: 8px; font-weight: bold; }
        .info-table td { border: none !important; padding: 1px 6px 1px 0 !important; text-align: left !important; font-size: 10px !important; }

        /* TABLE STYLE */
        .table-container { margin-bottom: 12px; page-break-inside: avoid; }
        .class-title { background-color: #002060 !important; color: #fff !important; font-weight: bold; text-align: center; padding: 2px 4px; font-size: 9px; margin-bottom: 0; border: 1px solid #000; border-bottom: none; letter-spacing: 0.5px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        table { width: 100%; border-collapse: collapse; text-align: center; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 2px; vertical-align: middle; font-size: 9px; line-height: 1.1; }
        th { background-color: #f4f4f4 !important; font-weight: bold; padding: 3px 2px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        tfoot td { font-size: 10px !important; padding: 2px; }
        
        /* TOMBOL AKSI */
        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .btn-print { background: #002060; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-close { background: #6c757d; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        
        /* GRID 2 KOLOM */
        .print-row { display: flex; flex-wrap: wrap; margin: 0 -5px; }
        .print-col-6 { width: 50%; padding: 0 5px; margin-bottom: 0; }
        
        /* TANDA TANGAN SECTION */
        .signature-section { font-size: 11px; margin-top: 15px; padding: 0 20px; page-break-inside: avoid; }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF (CTRL+P)</button>
        <button class="btn-close" onclick="window.close()">❌ Tutup Tab</button>
    </div>

    <div class="a4-paper">
        <!-- HEADER -->
        <div class="header-container">
            <div class="header-content">
                <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
                <div class="header-text">
                    <h5>ANALISIS HARI EFEKTIF BELAJAR (HEB)</h5>
                    <h5 style="font-size: 14px; margin-top: 0;"><?= strtoupper(esc($namaMadrasah ?? 'MTs MIFTAHUL HUDA (MIMHa)')) ?></h5>
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                    <span class="badge-semester">
                        SEMESTER <?= strtoupper(esc($tahunAktif['semester'] ?? '-')) ?>
                    </span>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <?php 
        $namaGuruCetak = '.....................................';
        foreach($teachers as $t) {
            if($t['id'] == $selectedTeacherId) {
                $namaGuruCetak = $t['nama_guru'];
                break;
            }
        }
        ?>

        <!-- INFO GURU & MAPEL -->
        <table class="info-table">
            <tr>
                <td width="90">Mata Pelajaran</td>
                <td width="10">:</td>
                <td><?= (esc($subjectOptions[$selectedSubjectId]['subject_name'] ?? '-')) ?></td>
            </tr>
            <tr>
                <td>Guru Pengampu</td>
                <td>:</td>
                <td><?= (esc($namaGuruCetak)) ?></td>
            </tr>
        </table>

        <!-- LOOPING TABEL KELAS -->
        <div class="print-row">
            <?php foreach($allAnalysisData as $dataKelas): ?>
            <div class="print-col-6">
                <div class="table-container">
                    <div class="class-title">KELAS <?= strtoupper(esc($dataKelas['rombel_name'])) ?></div>
                    <table>
                        <thead>
                            <tr>
                                <th width="20%">BULAN</th>
                                <th width="15%">HARI</th>
                                <th width="15%">HEB</th>
                                <th width="15%">JP</th>
                                <th width="15%">JML</th>
                                <th width="20%">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($dataKelas['analysisData'] as $bulan): ?>
                                <?php foreach($bulan['detail'] as $index => $hari): 
                                    if ($hari['hari'] == 'Jumat') continue; 
                                ?>
                                <tr>
                                    <?php if($index == 0): ?>
                                        <td rowspan="4" style="font-weight: bold;"><?= esc($bulan['nama_bulan']) ?></td>
                                    <?php endif; ?>
                                    
                                    <td style="text-align: left; font-weight: bold; padding-left: 4px;"><?= $hari['hari'] ?></td>
                                    <td><?= $hari['heb'] ?></td>
                                    <td><?= $hari['jp'] ?></td>
                                    <td><?= $hari['jumlah'] ?></td>
                                    
                                    <?php if($index == 0): ?>
                                        <td rowspan="4" style="font-weight: bold; font-size: 10px;">
                                            <?= $bulan['total_jp_bulan'] ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f4f4f4; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                                <!-- PERBAIKAN: Tulisan total membentang 4 kolom, value jatuh pas di kolom JML -->
                                <td colspan="5" class="text-right" style="font-weight: bold; padding-right: 10px;">TOTAL (JP)</td>
                                <td style="font-weight: bold; font-size: 10.5px;"><?= $dataKelas['grandTotalJp'] ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

       <!-- PENGESAHAN MENGGUNAKAN METODE MARGIN NEGATIF BERTUMPUK -->
        <div class="d-flex justify-content-between signature-section">
            
            <!-- KIRI: KEPALA MADRASAH -->
            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0">Mengetahui,</p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Kepala Madrasah,</p>
                
                <!-- Gambar Kamad -->
                <img src="<?= base_url('assets/img/ttd_kamad.png') ?>" alt="TTD Kamad" 
                     style="height: 90px; width: auto; object-fit: contain; margin-top: -8px; margin-bottom: -30px; position: relative; z-index: 2; mix-blend-mode: multiply; transform: scale(0.85); left: -25px;" 
                     onerror="this.style.opacity='0'">
                
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($kepalaNama ?? '.............................................') ?></p>
                <p class="text-muted small mb-0" style="font-size: 9px; position: relative; z-index: 3; margin-top: 2px;">NPK. <?= esc($kepalaNpk ?? '.....................................') ?></p>
            </div>

            <div class="text-center" style="width: 250px; line-height: 1;">
    <p class="mb-0"><?= esc($titiMangsa ?? 'Bandung, ....................................') ?></p>
    <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Guru Mata Pelajaran,</p>
    
    <div style="width: 100%; position: relative; z-index: 2;">
        <img src="<?= base_url('assets/img/ttd_' . esc($selectedTeacherId) . '.png') ?>" alt="TTD Guru" 
             style="height: 78px; width: auto; object-fit: contain; margin-top: 3px; margin-bottom: -30px; position: relative; mix-blend-mode: multiply; transform: scale(0.85); left: 0px;" 
             onerror="this.style.opacity='0'">
    </div>
    
    <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($namaGuruCetak) ?></p>
    <p class="text-muted small mb-0" style="font-size: 9px; position: relative; z-index: 3; margin-top: 2px;">NPK. <?= esc($guruNpk) ?></p>
</div>

        </div>

    </div>
</body>
</html>