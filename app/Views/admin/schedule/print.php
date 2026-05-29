<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Jadwal Pelajaran - <?= esc($activeVersion['version_name']) ?></title>
    <style>
        /* Pengaturan Kertas Portrait A4 */
        @page { size: A4 portrait; margin: 10mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 10px; margin: 0; padding: 0; background: #fff; }
        
        /* HEADER STYLE (Sesuai Kaldik) */
        .header-container { display: flex; justify-content: center; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; width: 100%; }
        .header-content { display: flex; align-items: center; justify-content: space-between; width: 85%; }
        .header-content img { height: 70px; width: auto; object-fit: contain; }
        .header-text { text-align: center; flex-grow: 1; margin: 0 15px; }
        .header-text h5 { margin: 0; font-weight: 800; color: #002060; font-size: 17px; letter-spacing: 0.5px; }
        .header-text h6 { margin: 4px 0; font-weight: 700; font-size: 13px; }
        
        .badge-semester {
            font-weight: bold; text-transform: uppercase; color: #fff; font-size: 11px;
            background-color: #002060 !important; border-radius: 3px; padding: 3px 10px;
            display: inline-block; -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }

        /* TABLE & DAY STYLE */
        .day-section { margin-bottom: 15px; page-break-inside: avoid; }
        .day-title {
            background-color: #002060 !important; color: #fff !important; font-weight: bold; 
            text-align: left; padding: 3px 8px; font-size: 11px; margin-bottom: 0; 
            -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000; border-bottom: none;
        }
        
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { border: 1px solid #000; padding: 3px; vertical-align: middle; }
        th { background-color: #f4f4f4 !important; font-weight: bold; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* Pewarnaan Mapel & Kegiatan */
        .bg-kegiatan { background-color: #e8f5e9 !important; font-weight: bold; font-style: italic; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-mapel { background-color: #ffffff !important; font-weight: bold; }
        .bg-kosong { background-color: #ffffff !important; color: #ccc; }

        /* FOOTER & TANDA TANGAN */
        .footer { width: 100%; margin-top: 20px; display: flex; justify-content: flex-end; page-break-inside: avoid; }
        .signature { text-align: center; width: 230px; }
        .signature p { margin: 2px 0; font-size: 11px; }
        .signature .name { font-weight: bold; text-decoration: underline; margin-top: 50px; }
        
        .version-top { position: absolute; top: 10px; right: 15px; font-size: 9px; font-weight: bold; }
        .version-bottom { font-size: 9px; font-weight: bold; margin-top: 15px; text-align: right; }

        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .btn-print { display: none !important; }
        }
        
        .btn-print { position: fixed; bottom: 20px; right: 20px; background: #002060; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; font-weight: bold; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-print:hover { background: #001540; }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF (CTRL+P)</button>

    <div class="version-top">Kode : <?= esc($activeVersion['version_name']) ?></div>

    <!-- HEADER SESUAI KALDIK -->
    <div class="header-container">
        <div class="header-content">
            <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
            
            <div class="header-text">
                <h5>JADWAL PELAJARAN MTs MIFTAHUL HUDA</h5>
                <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                <span class="badge-semester">SEMESTER <?= $tahunAktif ? strtoupper(esc($tahunAktif['semester'])) : '-' ?></span>
            </div>
            
            <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
        </div>
    </div>

    <!-- TABEL JADWAL PER HARI -->
    <?php foreach($matrixDays as $day): 
        $slotsHariIni = array_filter($timeSlots, function($s) use ($day) { return $s['day_name'] == $day; });
        if(empty($slotsHariIni)) continue;
        $slotsHariIni = array_values($slotsHariIni); // Reset Index Array
    ?>
    <div class="day-section">
        <div class="day-title"><?= strtoupper($day) ?></div>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th width="5%">JAM</th>
                    <th width="10%">WAKTU</th>
                    <?php foreach($rombels as $r): ?>
                        <th><?= esc($r['rombel_name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($slotsHariIni as $slot): ?>
                <tr>
                    <td><?= $slot['slot_number'] ?></td>
                    <td><?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?></td>
                    
                    <?php foreach($rombels as $r): 
                        $data = $classSchedules[$slot['id']][$r['id']] ?? ['text' => '-', 'type' => 'empty'];
                        $bgColor = ($data['type'] == 'kegiatan') ? 'bg-kegiatan' : (($data['type'] == 'mapel') ? 'bg-mapel' : 'bg-kosong');
                        $textVal = ($data['text'] == '-') ? '' : esc($data['text']);
                    ?>
                    <td class="<?= $bgColor ?>" data-text="<?= $textVal ?>"><?= $textVal ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- TANDA TANGAN -->
    <div class="footer">
        <div class="signature">
            <p>Bandung, <?= date('d F Y') ?></p>
            <p>Kepala Madrasah,</p>
            <p class="name">Rully Faizal, S.T.</p>
        </div>
    </div>
    
    <div class="version-bottom">Kode : <?= esc($activeVersion['version_name']) ?></div>

    <!-- SCRIPT MERGE CELL (VERTICAL & HORIZONTAL) -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tables = document.querySelectorAll('.schedule-table');
            
            tables.forEach(table => {
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                if(rows.length === 0) return;

                const colCount = rows[0].children.length;

                // 1. HORIZONTAL MERGE: Hanya untuk Kegiatan Umum (Mulai Kolom Kelas, Index 2)
                rows.forEach(row => {
                    let prevCell = null;
                    let colspan = 1;
                    for (let i = 2; i < row.children.length; i++) {
                        let cell = row.children[i];
                        if (cell.classList.contains('bg-kegiatan') && prevCell && cell.getAttribute('data-text') !== '' && cell.getAttribute('data-text') === prevCell.getAttribute('data-text')) {
                            colspan++;
                            prevCell.setAttribute('colspan', colspan);
                            cell.style.display = 'none';
                            cell.classList.add('merged-horizontal');
                        } else {
                            prevCell = cell;
                            colspan = 1;
                        }
                    }
                });

                // 2. VERTICAL MERGE: Untuk Mapel yang berurutan ke bawah (Index 2 s.d Terakhir)
                for (let col = 2; col < colCount; col++) {
                    let prevCell = null;
                    let rowspan = 1;
                    for (let r = 0; r < rows.length; r++) {
                        let cell = rows[r].children[col];
                        
                        if (cell.style.display === 'none' || cell.classList.contains('merged-horizontal')) {
                            prevCell = null; 
                            continue;
                        }

                        if (prevCell && cell.getAttribute('data-text') !== '' && cell.getAttribute('data-text') === prevCell.getAttribute('data-text')) {
                            rowspan++;
                            prevCell.setAttribute('rowspan', rowspan);
                            cell.style.display = 'none';
                        } else {
                            prevCell = cell;
                            rowspan = 1;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>