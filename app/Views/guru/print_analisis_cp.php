<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Analisis CP - SiKuMi</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 20px 0; background-color: #525659; }
        .a4-paper { width: 297mm; min-height: 210mm; margin: 0 auto; background: #fff; padding: 10mm 12mm; box-shadow: 0 0 15px rgba(0,0,0,0.4); position: relative; }

        @page { size: A4 landscape; margin: 10mm; }
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
        .section-title { font-weight: bold; font-size: 13px; margin-top: 15px; margin-bottom: 5px; color: #000; }

        /* HEADER */
        .header-container { display: flex; justify-content: center; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 15px; width: 100%; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; width: 100%; }
        .header-content img { height: 70px; width: auto; object-fit: contain; }
        .header-text { text-align: center; margin: 0; line-height: 1.2; }
        .header-text h5 { margin: 0 0 2px 0; font-weight: 800; color: #002060; font-size: 16px; letter-spacing: 0.5px; }
        .header-text h6 { margin: 2px 0; font-weight: 700; font-size: 12px; }
        .badge-semester { font-weight: bold; text-transform: uppercase; color: #fff; font-size: 10px; background-color: #002060 !important; border-radius: 3px; padding: 2px 8px; display: inline-block; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* INFO GURU & MAPEL */
        .info-table { width: auto; margin-bottom: 10px; font-weight: bold; }
        .info-table td { border: none !important; padding: 2px 8px 2px 0 !important; text-align: left !important; font-size: 12px !important; }

        /* TABLE STYLE */
        .table-container { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; text-align: left; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; font-size: 11px; line-height: 1.3; }
        th { background-color: #f4f4f4 !important; font-weight: bold; text-align: center; vertical-align: middle; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* TANDA TANGAN SECTION */
        .signature-section { font-size: 12px; margin-top: 30px; padding: 0 30px; page-break-inside: avoid; }

        /* PANEL KONTROL */
        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .btn-group-top { display: flex; gap: 10px; }
        .btn-print { background: #002060; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-word { background: #2b579a; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-close { background: #dc3545; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .control-panel { background: #fff; padding: 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid #ddd; text-align: center; width: auto; max-width: 320px; }
        .control-panel p { margin: 0 0 8px 0; font-size: 11px; font-weight: bold; color: #333; }
        .control-panels-wrapper { display: flex; gap: 15px; margin-bottom: 8px; }
        .d-pad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; justify-items: center; }
        .btn-dpad { background: #e9ecef; border: 1px solid #ced4da; border-radius: 4px; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; }
        .btn-dpad:hover { background: #dee2e6; }
        .btn-reset { margin-top: 8px; background: #6c757d; color: white; border: none; border-radius: 4px; font-size: 10px; padding: 4px; width: 100%; cursor: pointer; }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <div class="btn-group-top">
            <button class="btn-word" onclick="exportToWord('Analisis_CP.doc')">📝 Download Word</button>
            <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF</button>
            <button class="btn-close" onclick="window.close()">🆇 Tutup</button>
        </div>
        
        <div class="control-panel">
            <div class="control-panels-wrapper">
                <!-- Panel TTD Kepala -->
                <div>
                    <p>TTD Kepala</p>
                    <div class="d-pad">
                        <div></div><button class="btn-dpad" onclick="moveKamad(0, -3)">⬆️</button><div></div>
                        <button class="btn-dpad" onclick="moveKamad(-3, 0)">⬅️</button>
                        <button class="btn-dpad" onclick="moveKamad(0, 3)">⬇️</button>
                        <button class="btn-dpad" onclick="moveKamad(3, 0)">➡️</button>
                    </div>
                    <div style="display: flex; gap: 4px; justify-content: center; margin-top: 5px;">
                        <button class="btn-dpad" style="width: 100%; height: 26px; font-weight: bold;" onclick="zoomKamad(0.05)">➕</button>
                        <button class="btn-dpad" style="width: 100%; height: 26px; font-weight: bold;" onclick="zoomKamad(-0.05)">➖</button>
                    </div>
                </div>

                <!-- Panel TTD Guru -->
                <div>
                    <p>TTD Guru</p>
                    <div class="d-pad">
                        <div></div><button class="btn-dpad" onclick="moveTtd(0, -3)">⬆️</button><div></div>
                        <button class="btn-dpad" onclick="moveTtd(-3, 0)">⬅️</button>
                        <button class="btn-dpad" onclick="moveTtd(0, 3)">⬇️</button>
                        <button class="btn-dpad" onclick="moveTtd(3, 0)">➡️</button>
                    </div>
                    <div style="display: flex; gap: 4px; justify-content: center; margin-top: 5px;">
                        <button class="btn-dpad" style="width: 100%; height: 26px; font-weight: bold;" onclick="zoomTtd(0.05)">➕</button>
                        <button class="btn-dpad" style="width: 100%; height: 26px; font-weight: bold;" onclick="zoomTtd(-0.05)">➖</button>
                    </div>
                </div>
            </div>
            <button class="btn-reset" onclick="resetTtd()">🔄 Reset Semua</button>
        </div>
    </div>

    <div class="a4-paper" id="exportContent">
        <!-- HEADER KOP SURAT -->
        <div class="header-container">
            <div class="header-content">
                <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
                <div class="header-text">
                    <h5>ANALISIS CAPAIAN PEMBELAJARAN</h5>
                    <h5 style="font-size: 18px; margin-top: 2px;"><?= strtoupper(esc($namaMadrasah ?? 'MTs MIFTAHUL HUDA (MIMHa)')) ?></h5>
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                    <span class="badge-semester">
                        SEMESTER <?= strtoupper(esc($tahunAktif['semester'] ?? '-')) ?>
                    </span>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <!-- INFORMASI MAPEL -->
        <table class="info-table">
            <tr><td width="120">Mata Pelajaran</td><td width="10">:</td><td><?= esc($namaMapelAktif ?? '-') ?></td></tr>
            <tr><td>Kelas / Fase</td><td>:</td><td><?= esc($namaKelasAktif ?? '-') ?></td></tr>
            <tr><td>Guru Pengampu</td><td>:</td><td><?= esc($namaGuruCetak) ?></td></tr>
        </table>

        <!-- BAGIAN A -->
        <div class="section-title">A. ELEMEN & DESKRIPSI CAPAIAN PEMBELAJARAN (CP)</div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="15%">Elemen CP</th>
                        <th width="80%">Deskripsi Capaian Pembelajaran</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($draftElemen)): ?>
                        <tr><td colspan="3" class="text-center">Belum ada deskripsi elemen CP.</td></tr>
                    <?php else: ?>
                        <?php foreach($draftElemen as $no => $d): ?>
                        <tr>
                            <td class="text-center"><?= $no+1 ?></td>
                            <td class="font-weight-bold" dir="auto"><?= esc($d['nama_elemen']) ?></td>
                            <td dir="auto" style="text-align: justify;"><?= nl2br(esc($d['deskripsi_cp'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- BAGIAN B -->
        <div class="section-title">B. HASIL ANALISIS CAPAIAN PEMBELAJARAN (CP)</div>
        <div class="table-container">
            <table class="table table-bordered" style="table-layout: fixed; word-wrap: break-word;">
                <thead>
                    <tr>
                        <th width="4%">No</th>
                        <th width="15%">Elemen CP</th>
                        <th width="20%">Tujuan Pembelajaran (TP)</th>
                        <th width="16%">Lingkup Materi</th>
                        <th width="20%">Kriteria Ketercapaian TP (KKTP)</th>
                        <th width="20%">Aktivitas Pembelajaran</th>
                        <th width="5%">JP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalJp = 0;
                    if(empty($analisisData)): 
                    ?>
                        <tr><td colspan="7" class="text-center">Belum ada data analisis CP.</td></tr>
                    <?php else: ?>
                        <?php foreach($analisisData as $no => $dt): 
                            $totalJp += (int)$dt['estimasi_jp'];
                        ?>
                        <tr>
                            <td class="text-center"><?= $no+1 ?></td>
                            <td class="font-weight-bold" dir="auto"><?= esc($dt['elemen_cp']) ?></td>
                            <td dir="auto"><?= esc($dt['tujuan_pembelajaran']) ?></td>
                            <td dir="auto"><?= esc($dt['lingkup_materi']) ?></td>
                            <td dir="auto"><?= nl2br(esc($dt['kktp'])) ?></td>
                            <td dir="auto"><?= esc($dt['aktivitas_tarl']) ?></td>
                            <td class="text-center font-weight-bold"><?= esc($dt['estimasi_jp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tbody>
                    <tr style="background-color: #f4f4f4; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                        <td colspan="6" class="text-right" style="font-weight: bold; padding-right: 15px; vertical-align: middle;">TOTAL ALOKASI JP:</td>
                        <td class="text-center font-weight-bold" style="font-size: 13px;"><?= $totalJp ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TANDA TANGAN -->
        <div class="d-flex justify-content-between signature-section">
            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0">Mengetahui,</p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Kepala Madrasah,</p>
                <!-- TAMBAHAN ID ttd-kamad -->
                <img id="ttd-kamad" src="<?= base_url('assets/img/ttd_kamad.png') ?>" alt="TTD Kamad" 
                     style="height: 90px; width: auto; object-fit: contain; margin-top: -8px; margin-bottom: -30px; position: relative; z-index: 2; mix-blend-mode: multiply; transform: scale(0.85); left: -25px;" 
                     onerror="this.style.opacity='0'">
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($kepalaNama ?? '.............................................') ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; position: relative; z-index: 3; margin-top: 4px;">NPK. <?= esc($kepalaNpk ?? '.....................................') ?></p>
            </div>

            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0"><?= esc($titiMangsa ?? 'Bandung, ....................................') ?></p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Guru Mata Pelajaran,</p>
                <div style="width: 100%; position: relative; z-index: 2;">
                    <!-- ID ttd-guru -->
                    <img id="ttd-guru" src="<?= base_url('assets/img/ttd_' . esc($userId) . '.png') ?>" alt="TTD Guru" 
                         style="height: 78px; width: auto; object-fit: contain; top: 0px; margin-top: 3px; margin-bottom: -28px; position: relative; mix-blend-mode: multiply; transform: scale(0.85); left: 0px;" 
                         onerror="this.style.opacity='0'">
                </div>
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($namaGuruCetak) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; position: relative; z-index: 3; margin-top: 4px;">NPK. <?= esc($guruNpk) ?></p>
            </div>
        </div>

    </div>

    <script>
        // State Guru
        let ttdPosX = 0; let ttdPosY = 0; let ttdScale = 0.85; 
        const imgTtdGuru = document.getElementById('ttd-guru');

        // State Kepala (left awal diset ke -25px berdasarkan html asli Anda)
        let kamadPosX = -25; let kamadPosY = 0; let kamadScale = 0.85;
        const imgTtdKamad = document.getElementById('ttd-kamad');

        // Fungsi TTD Guru
        function moveTtd(x, y) {
            ttdPosX += x; ttdPosY += y;
            if(imgTtdGuru) { imgTtdGuru.style.left = ttdPosX + 'px'; imgTtdGuru.style.top = ttdPosY + 'px'; }
        }
        function zoomTtd(factor) {
            ttdScale += factor;
            if(imgTtdGuru) { imgTtdGuru.style.transform = 'scale(' + ttdScale + ')'; }
        }

        // Fungsi TTD Kepala
        function moveKamad(x, y) {
            kamadPosX += x; kamadPosY += y;
            if(imgTtdKamad) { imgTtdKamad.style.left = kamadPosX + 'px'; imgTtdKamad.style.top = kamadPosY + 'px'; }
        }
        function zoomKamad(factor) {
            kamadScale += factor;
            if(imgTtdKamad) { imgTtdKamad.style.transform = 'scale(' + kamadScale + ')'; }
        }

        // Reset Keduanya
        function resetTtd() {
            ttdPosX = 0; ttdPosY = 0; ttdScale = 0.85; 
            kamadPosX = -25; kamadPosY = 0; kamadScale = 0.85; 
            
            if(imgTtdGuru) { 
                imgTtdGuru.style.left = ttdPosX + 'px'; 
                imgTtdGuru.style.top = ttdPosY + 'px'; 
                imgTtdGuru.style.transform = 'scale(' + ttdScale + ')'; 
            }
            if(imgTtdKamad) { 
                imgTtdKamad.style.left = kamadPosX + 'px'; 
                imgTtdKamad.style.top = kamadPosY + 'px'; 
                imgTtdKamad.style.transform = 'scale(' + kamadScale + ')'; 
            }
        }

        // FUNGSI EXPORT WORD 
        function exportToWord(filename = 'Analisis_CP.doc') {
            var exportSource = document.getElementById('exportContent');
            var cloneDiv = exportSource.cloneNode(true);
            
            // Hapus spasi berlebih
            var textElements = cloneDiv.querySelectorAll('h5, h6, p');
            for (var i = 0; i < textElements.length; i++) {
                textElements[i].style.margin = '0px';
                textElements[i].style.padding = '0px';
                textElements[i].style.lineHeight = '1';
            }

            // Garis tabel
            var tables = cloneDiv.getElementsByTagName('table');
            for (var i = 0; i < tables.length; i++) {
                tables[i].setAttribute('border', '1');
                tables[i].setAttribute('cellpadding', '4');
                tables[i].setAttribute('cellspacing', '0');
                tables[i].style.borderCollapse = 'collapse';
                tables[i].style.marginBottom = '10px';
            }
            
            // Tabel Info
            var infoTables = cloneDiv.getElementsByClassName('info-table');
            for (var i = 0; i < infoTables.length; i++) {
                infoTables[i].setAttribute('border', '0');
                infoTables[i].style.marginBottom = '5px';
                var tds = infoTables[i].getElementsByTagName('td');
                for(var j = 0; j < tds.length; j++){
                    tds[j].style.padding = '1px';
                    tds[j].style.border = 'none';
                }
            }

            // Kunci ukuran zoom gambar TTD (termasuk Kamad) ke piksel
            var originalImgs = exportSource.getElementsByTagName('img');
            var cloneImgs = cloneDiv.getElementsByTagName('img');
            for (var i = 0; i < originalImgs.length; i++) {
                var rect = originalImgs[i].getBoundingClientRect();
                cloneImgs[i].style.transform = 'none'; 
                cloneImgs[i].style.width = Math.round(rect.width) + 'px';
                cloneImgs[i].style.height = Math.round(rect.height) + 'px';
                cloneImgs[i].setAttribute('width', Math.round(rect.width));
                cloneImgs[i].setAttribute('height', Math.round(rect.height));
                cloneImgs[i].style.marginTop = '0px';
                cloneImgs[i].style.marginBottom = '0px';
            }

            // Layout Kop Surat
            var cloneHeader = cloneDiv.querySelector('.header-container');
            if (cloneHeader) {
                var headerImgs = cloneHeader.getElementsByTagName('img');
                var img1 = headerImgs[0] ? headerImgs[0].outerHTML : '';
                var img2 = headerImgs[1] ? headerImgs[1].outerHTML : '';
                var hText = cloneHeader.querySelector('.header-text').innerHTML;
                
                var headerTable = '<table style="width:100%; border-bottom: 3px double #000; margin-bottom: 10px; text-align:center;" border="0" cellpadding="0" cellspacing="0">' +
                                  '<tr><td style="width:15%; border:none; vertical-align:middle; padding:0;">' + img1 + '</td>' +
                                  '<td style="width:70%; border:none; vertical-align:middle; padding:0;">' + hText + '</td>' +
                                  '<td style="width:15%; border:none; vertical-align:middle; padding:0;">' + img2 + '</td></tr></table>';
                cloneHeader.outerHTML = headerTable;
            }

            // Layout Tanda Tangan
            var cloneSig = cloneDiv.querySelector('.signature-section');
            if (cloneSig) {
                var leftContent = cloneSig.children[0].innerHTML;
                var rightContent = cloneSig.children[1].innerHTML;
                var sigTable = '<table style="width:100%; border:none; text-align:center; margin-top:10px;" border="0" cellpadding="0" cellspacing="0">' +
                               '<tr><td style="width:50%; border:none; vertical-align:bottom; padding:0;">' + leftContent + '</td>' +
                               '<td style="width:50%; border:none; vertical-align:bottom; padding:0;">' + rightContent + '</td></tr></table>';
                cloneSig.outerHTML = sigTable;
            }

            // Style Ms. Word (Landscape & Narrow Margin)
            var msoStyles = "<style>" +
                            "@page WordSection1 { size: 841.9pt 595.3pt; mso-page-orientation: landscape; margin: 36.0pt 36.0pt 36.0pt 36.0pt; mso-header-margin: 36.0pt; mso-footer-margin: 36.0pt; mso-paper-source: 0; }" +
                            "div.WordSection1 { page: WordSection1; }" +
                            "table { font-size: 11px; font-family: 'Times New Roman', Times, serif; }" +
                            "</style>";

            // Format Akhir
            var preHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Analisis CP</title>" + msoStyles + "</head><body><div class='WordSection1'>";
            var postHtml = "</div></body></html>";
            var html = preHtml + cloneDiv.innerHTML + postHtml;

            // Eksekusi Unduhan
            var blob = new Blob(['\ufeff', html], { type: 'application/msword' });
            var downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            
            if (navigator.msSaveOrOpenBlob) {
                navigator.msSaveOrOpenBlob(blob, filename);
            } else {
                var url = URL.createObjectURL(blob);
                downloadLink.href = url;
                downloadLink.download = filename;
                downloadLink.click();
                URL.revokeObjectURL(url);
            }
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>