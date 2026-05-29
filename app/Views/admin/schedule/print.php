<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Jadwal - <?= esc($activeVersion['version_name']) ?></title>
    <style>
        @page { size: landscape; margin: 10mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; font-size: 11px; margin: 0; padding: 0; background: #fff; }
        
        .header { display: flex; align-items: center; justify-content: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header img { width: 90px; height: auto; position: absolute; left: 20px; }
        .header-text { text-align: center; line-height: 1.2; }
        .header-text h2 { margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 1px; color: #d84315; }
        .header-text h3 { margin: 2px 0; font-size: 16px; font-weight: bold; }
        .header-text h4 { margin: 0; font-size: 14px; font-weight: normal; }
        
        .table-container { width: 100%; margin-bottom: 20px; page-break-inside: avoid; }
        .day-title { background-color: #f0f0f0; font-weight: bold; text-align: left; padding: 5px 10px; font-size: 13px; border: 1px solid #000; border-bottom: none; margin-top: 15px; text-transform: uppercase;}
        
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        th { background-color: #f0f0f0; font-weight: bold; font-size: 12px; }
        
        /* Pewarnaan Khusus Kegiatan */
        .bg-kegiatan { background-color: #e8f5e9 !important; font-weight: bold; font-style: italic; }
        .bg-mapel { background-color: #ffffff !important; font-weight: bold; }
        .bg-kosong { background-color: #ffffff !important; color: #ccc; }

        .footer { width: 100%; margin-top: 30px; display: flex; justify-content: flex-end; page-break-inside: avoid; }
        .signature { text-align: center; width: 250px; }
        .signature p { margin: 2px 0; font-size: 12px; }
        .signature .name { font-weight: bold; text-decoration: underline; margin-top: 60px; }
        .signature .nip { font-size: 11px; }

        .version-code { position: absolute; top: 10px; right: 20px; font-size: 10px; font-weight: bold; }

        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .btn-print { display: none !important; }
        }
        
        .btn-print { position: fixed; bottom: 20px; right: 20px; background: #0d6efd; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; }
        .btn-print:hover { background: #0b5ed7; }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Cetak Sekarang (CTRL+P)</button>

    <div class="version-code">Kode: <?= esc($activeVersion['id']) ?>/<?= date('Y') ?></div>

    <div class="header">
        <img src="<?= base_url('assets/img/logo2.png') ?>" alt="Logo">
        <div class="header-text">
            <h2>MIMHa</h2>
            <h3>TSANAWIYAH INFORMATIKA</h3>
            <h4>JADWAL PELAJARAN TP. <?= esc($tahunAktif['academic_year']) ?></h4>
            <h4>SEMESTER <?= strtoupper(esc($tahunAktif['semester'])) ?></h4>
        </div>
    </div>

    <?php foreach($matrixDays as $day): 
        $slotsHariIni = array_filter($timeSlots, function($s) use ($day) { return $s['day_name'] == $day; });
        if(empty($slotsHariIni)) continue;
    ?>
    <div class="table-container">
        <div class="day-title"><?= strtoupper($day) ?></div>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th width="4%">JAM</th>
                    <th width="8%">WAKTU</th>
                    <?php foreach($rombels as $r): ?>
                        <th><?= esc($r['rombel_name']) ?></th> <!-- Menghilangkan kata Tingkat sesuai permintaan -->
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($slotsHariIni as $slot): ?>
                <tr>
                    <td><?= $slot['slot_number'] ?></td>
                    <td style="font-size: 10px;"><?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?></td>
                    
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

    <div class="footer">
        <div class="signature">
            <p>Bandung, <?= date('d F Y') ?></p>
            <p>Kepala Madrasah,</p>
            <p class="name">Rully Faizal, S.T.</p>
            <p class="nip">NUPTK. 1234567890123456</p>
        </div>
    </div>

    <!-- ALGORITMA MERGE CELL OTOMATIS (HORIZONTAL & VERTIKAL) -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tables = document.querySelectorAll('.schedule-table');
            
            tables.forEach(table => {
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                if(rows.length === 0) return;

                const colCount = rows[0].children.length;

                // 1. HORIZONTAL MERGE (COLSPAN) - Menggabungkan kolom kelas jika kegiatannya sama (Cth: Istirahat)
                rows.forEach(row => {
                    let prevCell = null;
                    let colspan = 1;
                    // Mulai dari index 2 karena index 0 (Jam) dan 1 (Waktu) tidak boleh di-merge secara horizontal
                    for (let i = 2; i < row.children.length; i++) {
                        let cell = row.children[i];
                        if (prevCell && cell.getAttribute('data-text') !== '' && cell.getAttribute('data-text') === prevCell.getAttribute('data-text')) {
                            colspan++;
                            prevCell.setAttribute('colspan', colspan);
                            cell.style.display = 'none'; // Sembunyikan sel yang sudah tergabung
                            cell.classList.add('merged-horizontal');
                        } else {
                            prevCell = cell;
                            colspan = 1;
                        }
                    }
                });

                // 2. VERTICAL MERGE (ROWSPAN) - Menggabungkan baris waktu jika mapelnya sama (Cth: MTK 2 Jam berurutan)
                for (let col = 2; col < colCount; col++) { // Cek tiap kolom kelas
                    let prevCell = null;
                    let rowspan = 1;
                    
                    for (let rowIdx = 0; rowIdx < rows.length; rowIdx++) {
                        let cell = rows[rowIdx].children[col];
                        
                        if (cell.classList.contains('merged-horizontal') || cell.style.display === 'none') {
                            prevCell = null; // Putuskan rantai vertikal jika menabrak colspan (kegiatan umum)
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