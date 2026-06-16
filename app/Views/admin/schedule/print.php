<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Jadwal Pelajaran - <?= esc($activeVersion['version_name']) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 30px 0; background-color: #525659; }
        .a4-paper { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 12mm 10mm; box-shadow: 0 0 15px rgba(0,0,0,0.4); position: relative; }

        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .a4-paper { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .btn-print { display: none !important; }
            .btn-close { display: none !important; }
        }

        /* UTILITY CLASS */
        .d-flex { display: flex !important; }
        .justify-content-center { justify-content: center !important; }
        .justify-content-end { justify-content: flex-end !important; }
        .align-items-center { align-items: center !important; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .pe-4 { padding-right: 1.5rem !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .font-weight-bold { font-weight: bold !important; }
        .position-relative { position: relative !important; }
        .z-index-1 { z-index: 1 !important; }
        .z-index-2 { z-index: 2 !important; }
        .z-index-3 { z-index: 3 !important; }
        .d-inline-block { display: inline-block !important; }
        .text-muted { color: #6c757d !important; }
        .small { font-size: 0.875em !important; }

        /* HEADER STYLE */
        .header-container { display: flex; justify-content: center; align-items: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 15px; width: 100%; }
        .header-content { display: flex; align-items: center; justify-content: space-between; width: 85%; }
        .header-content img { height: 70px; width: auto; object-fit: contain; }
        .header-text { text-align: center; flex-grow: 1; margin: 0 20px; }
        .header-text h5 { margin: 0; font-weight: 800; color: #002060; font-size: 17px; letter-spacing: 0.5px; }
        .header-text h6 { margin: 6px 0; font-weight: 700; font-size: 13px; }
        .badge-semester { font-weight: bold; text-transform: uppercase; color: #fff; font-size: 11px; background-color: #002060 !important; border-radius: 3px; padding: 3px 12px; display: inline-block; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* TABLE STYLE */
        .day-section { margin-bottom: 35px; page-break-inside: avoid; }
        .day-title { background-color: #002060 !important; color: #fff !important; font-weight: bold; text-align: center; padding: 3px 8px; font-size: 11px; margin-bottom: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; border: 1px solid #000; border-bottom: none; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; text-align: center; table-layout: fixed; word-wrap: break-word; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: middle; font-size: 10px; overflow: hidden; }
        th { background-color: #f4f4f4 !important; font-weight: bold; font-size: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        .bg-kegiatan { background-color: #e8f5e9 !important; font-weight: bold; font-style: italic; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-mapel { background-color: #ffffff !important; font-weight: bold; }
        .bg-kosong { background-color: #ffffff !important; color: #ccc; }

        .btn-print:hover { background: #001540; }

        .print-actions-wrapper{position:fixed;top:20px;right:20px;z-index:1000;display:flex;gap:10px}
        .btn-print{background:#002060;color:#fff;padding:10px 20px;border:none;border-radius:5px;font-size:14px;cursor:pointer;font-weight:bold;box-shadow:0 4px 6px rgba(0,0,0,0.3)}
        .btn-close{background:#6c757d;color:#fff;padding:10px 20px;border:none;border-radius:5px;font-size:14px;cursor:pointer;font-weight:bold;box-shadow:0 4px 6px rgba(0,0,0,0.3)}

    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF (CTRL+P)</button>
        <button class="btn-print" onclick="warnaiJadwal()" style="background-color: #28a745; margin-right: 5px;">🎨 Warnai Jadwal</button>
        <button class="btn-close" onclick="window.close()">❌ Tutup</button>
    </div>

    <div class="a4-paper">
        <!-- HEADER -->
        <div class="header-container">
            <div class="header-content">
                <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
                <div class="header-text">
                    <!-- JUDUL DIBUAT DUA BARIS -->
                    <h5 style="margin-bottom: 2px;">JADWAL PELAJARAN</h5>
                    <h5 style="font-size: 18px; margin-top: 0;"><?= strtoupper(esc($namaMadrasah)) ?></h5>
                    
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                    
                    <span class="badge-semester">
                        <?= !empty($activeVersion['schedule_title']) ? strtoupper(esc($activeVersion['schedule_title'])) : 'SEMESTER ' . strtoupper(esc($tahunAktif['semester'] ?? '-')) ?>
                    </span>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <?php 
        $isFirstDayRendered = false; 
        
        foreach($matrixDays as $day): 
            $slotsHariIni = array_filter($timeSlots, function($s) use ($day) { return $s['day_name'] == $day; });
            if(empty($slotsHariIni)) continue;
            $slotsHariIni = array_values($slotsHariIni); 

            // KODE ATAS KIRI
            if (!$isFirstDayRendered) {
                echo '<div style="text-align: left; font-size: 8px; font-weight: bold; margin-bottom: 2px;">Kode : ' . esc($activeVersion['version_name']) . '</div>';
                $isFirstDayRendered = true;
            }
        ?>
        <div class="day-section">
            <div class="day-title"><?= strtoupper($day) ?></div>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">JAM</th>
                        <th style="width: 14%;">WAKTU</th>
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

        <!-- KODE BAWAH KANAN (Ditarik naik agar mepet ke tabel) -->
        <div style="text-align: right; font-size: 8px; font-weight: bold; margin-top: -33px; margin-bottom: 10px;">Kode : <?= esc($activeVersion['version_name']) ?></div>

        <!-- PENGESAHAN TTD KAMAD (Spasi dirapatkan, Garis Bawah Dihapus) -->
        <div class="d-flex justify-content-end text-end pe-4" style="font-size: 11px; margin-top: 10px;">
            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0"><?= esc($titiMangsa) ?></p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15; position: relative; z-index: 1;">Kepala Madrasah,</p>
                
                <!-- Margin negatif diperbesar untuk merapatkan gambar ke teks atas & bawah -->
                <img src="<?= base_url('assets/img/ttd_kamad.png') ?>" alt="TTD Kamad" style="height: 100px; width: auto; object-fit: contain; margin-top: -35px; margin-bottom: -35px; position: relative; z-index: 2; mix-blend-mode: multiply; transform: scale(0.85); left: -30px;">
                
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($kepalaNama) ?></p>
                <p class="text-muted small mb-0" style="font-size: 9px; position: relative; z-index: 3; margin-top: 2px;">NPK. <?= esc($kepalaNpk) ?></p>
            </div>
        </div>

    </div>

    <!-- SCRIPT MERGE CELL -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tables = document.querySelectorAll('.schedule-table');
            
            tables.forEach(table => {
                const tbody = table.querySelector('tbody');
                const rows = tbody.querySelectorAll('tr');
                if(rows.length === 0) return;
                const colCount = rows[0].children.length;

                // 1. HORIZONTAL MERGE
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

                // 2. VERTICAL MERGE 
                for (let col = 2; col < colCount; col++) {
                    let prevCell = null;
                    let rowspan = 1;
                    for (let r = 0; r < rows.length; r++) {
                        let cell = rows[r].children[col];
                        
                        if (cell.style.display === 'none' || cell.classList.contains('merged-horizontal')) {
                            prevCell = null; 
                            continue;
                        }

                        let currentText = cell.getAttribute('data-text');
                        let currentColspan = cell.getAttribute('colspan') || "1";

                        if (prevCell) {
                            let prevText = prevCell.getAttribute('data-text');
                            let prevColspan = prevCell.getAttribute('colspan') || "1";

                            if (currentText !== '' && currentText === prevText && currentColspan === prevColspan) {
                                rowspan++;
                                prevCell.setAttribute('rowspan', rowspan);
                                cell.style.display = 'none';
                                continue; 
                            }
                        }
                        
                        prevCell = cell;
                        rowspan = 1;
                    }
                }
            });
        });

       // ==============================================================
        // FITUR MEWARNAI JADWAL (WARNA PASTEL LEBIH BERAGAM)
        // ==============================================================
        let isColored = false;

        // Fungsi mengubah teks menjadi warna Pastel yang unik, beragam, dan tetap konsisten
        function stringToSoftColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
                hash = hash & hash; // Konversi ke 32bit integer
            }
            
            // Ubah jadi angka positif
            hash = Math.abs(hash);
            
            // HUE: Spektrum warna 0 - 360 derajat (Memutar seluruh warna)
            const h = hash % 360;
            
            // SATURATION: Bervariasi antara 55% - 85% (Agar ada warna yg kalem & ada yg tajam)
            const s = 55 + (hash % 30);
            
            // LIGHTNESS: Bervariasi antara 75% - 90% (Mencegah warna terlalu gelap)
            // Digeser (>> 4) agar kombinasinya tidak seragam dengan Saturation
            const l = 75 + ((hash >> 4) % 15);
            
            return `hsl(${h}, ${s}%, ${l}%)`; 
        }

        function warnaiJadwal() {
            isColored = !isColored; // Toggle nyala/mati
            
            let cells = document.querySelectorAll('td[data-text]');
            
            cells.forEach(cell => {
                let text = cell.getAttribute('data-text');
                
                // Abaikan jika kosong atau strip
                if (!text || text.trim() === '' || text.trim() === '-') {
                    return;
                }

                if (isColored) {
                    let softColor = stringToSoftColor(text.trim());
                    cell.style.setProperty('background-color', softColor, 'important');
                    cell.style.setProperty('-webkit-print-color-adjust', 'exact', 'important');
                    cell.style.setProperty('print-color-adjust', 'exact', 'important');
                } else {
                    cell.style.removeProperty('background-color');
                }
            });
        }
    </script>
</body>
</html>