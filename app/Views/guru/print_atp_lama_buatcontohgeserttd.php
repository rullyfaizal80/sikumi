<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print ATP - SiKuMi</title>
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
        .font-weight-bold { font-weight: bold !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .mt-2 { margin-top: 0.5rem !important; }
        .mb-3 { margin-bottom: 1rem !important; }
        .text-muted { color: #6c757d !important; }
        .small { font-size: 12px; }

        /* HEADER IDENTITAS */
        .doc-title { font-size: 16px; margin-bottom: 15px; letter-spacing: 1px; }
        .identity-table { width: 100%; font-size: 13px; margin-bottom: 15px; }
        .identity-table td { padding: 2px 5px; vertical-align: top; }
        .identity-table td:first-child { width: 120px; font-weight: bold; }
        .identity-table td:nth-child(2) { width: 10px; }

        /* TABEL DATA */
        .data-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        .data-table th { background-color: #f2f2f2; text-align: center; font-weight: bold; vertical-align: middle; }
        .data-table td { line-height: 1.4; }

        /* PANEL KONTROL MELAYANG */
        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; width: 300px; background: #fff; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; overflow: hidden; font-family: Arial, sans-serif; }
        .print-actions-header { background: #002060; color: #fff; padding: 10px; text-align: center; font-weight: bold; font-size: 14px; }
        .print-actions-body { padding: 15px; }
        .btn { display: inline-block; padding: 8px 12px; font-size: 13px; font-weight: bold; text-align: center; text-decoration: none; border-radius: 4px; cursor: pointer; border: none; width: 100%; box-sizing: border-box; }
        .btn-primary { background-color: #0d6efd; color: #fff; }
        .btn-secondary { background-color: #6c757d; color: #fff; margin-top: 10px; }
        .control-group { border: 1px solid #eee; padding: 10px; border-radius: 6px; margin-top: 15px; background: #f9f9f9; }
        .control-title { font-size: 12px; font-weight: bold; margin-bottom: 8px; text-align: center; color: #333; }
        .d-pad { display: grid; grid-template-columns: 30px 30px 30px; grid-template-rows: 30px 30px 30px; gap: 4px; justify-content: center; }
        .d-btn { background: #e0e0e0; border: 1px solid #bbb; border-radius: 4px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .d-btn:active { background: #ccc; }
        .d-up { grid-column: 2; grid-row: 1; }
        .d-left { grid-column: 1; grid-row: 2; }
        .d-right { grid-column: 3; grid-row: 2; }
        .d-down { grid-column: 2; grid-row: 3; }
        .zoom-controls { display: flex; justify-content: center; gap: 10px; margin-top: 8px; }
        .zoom-btn { background: #17a2b8; color: #fff; border: none; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 12px; }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <div class="print-actions-header">⚙️ Pengaturan Cetak</div>
        <div class="print-actions-body">
            <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Cetak Dokumen</button>
            <a href="javascript:history.back()" class="btn btn-secondary">⬅️ Kembali</a>

            <div class="control-group">
                <div class="control-title">Geser TTD Kepala Madrasah</div>
                <div class="d-pad">
                    <button class="d-btn d-up" onclick="moveKepalaTtd(0, -5)">▲</button>
                    <button class="d-btn d-left" onclick="moveKepalaTtd(-5, 0)">◀</button>
                    <button class="d-btn d-right" onclick="moveKepalaTtd(5, 0)">▶</button>
                    <button class="d-btn d-down" onclick="moveKepalaTtd(0, 5)">▼</button>
                </div>
                <div class="zoom-controls">
                    <button class="zoom-btn" onclick="zoomKepalaTtd(-0.05)">➖ Perkecil</button>
                    <button class="zoom-btn" onclick="zoomKepalaTtd(0.05)">➕ Perbesar</button>
                </div>
            </div>

            <div class="control-group">
                <div class="control-title">Geser TTD Guru</div>
                <div class="d-pad">
                    <button class="d-btn d-up" onclick="moveTtd(0, -5)">▲</button>
                    <button class="d-btn d-left" onclick="moveTtd(-5, 0)">◀</button>
                    <button class="d-btn d-right" onclick="moveTtd(5, 0)">▶</button>
                    <button class="d-btn d-down" onclick="moveTtd(0, 5)">▼</button>
                </div>
                <div class="zoom-controls">
                    <button class="zoom-btn" onclick="zoomTtd(-0.05)">➖ Perkecil</button>
                    <button class="zoom-btn" onclick="zoomTtd(0.05)">➕ Perbesar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="a4-paper">
        <h5 class="text-center font-weight-bold doc-title">ALUR TUJUAN PEMBELAJARAN (ATP)</h5>

        <table class="identity-table">
            <tr>
                <td>Nama Guru</td><td>:</td><td><?= esc($namaGuruCetak ?? '-') ?></td>
                <td style="width: 15%;"></td>
                <td>Mata Pelajaran</td><td>:</td><td><?= esc($selectedMapelName ?? '-') ?></td>
            </tr>
            <tr>
                <td>Tahun Pelajaran</td><td>:</td><td><?= esc($tahunAktif['academic_year'] ?? '-') ?></td>
                <td></td>
                <td>Fase / Kelas</td><td>:</td><td><?= esc($namaRombelAktif ?? '-') ?></td>
            </tr>
            <tr>
                <td>Semester</td><td>:</td><td><?= esc(ucfirst($tahunAktif['semester'] ?? '-')) ?></td>
                <td></td>
                <td></td><td></td><td></td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 22%;">Tujuan Pembelajaran</th>
                    <th style="width: 15%;">Lingkup Materi</th>
                    <th style="width: 15%;">Aktivitas Kognitif</th>
                    <th style="width: 13%;">Profil Lulusan (DPL)</th>
                    <th style="width: 10%;">Panca Cinta</th>
                    <th style="width: 5%;">JP</th>
                    <th style="width: 15%;">Tanggal Pelaksanaan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($dataAtp)): ?>
                    <tr><td colspan="8" class="text-center py-4">Belum ada data ATP untuk kelas ini.</td></tr>
                <?php else: ?>
                    <?php foreach($dataAtp as $idx => $row): ?>
                    <tr>
                        <td class="text-center font-weight-bold"><?= esc($tingkatKelas) . '.' . ($idx + 1) ?></td>
                        <td><?= esc($row['tujuan_pembelajaran'] ?? $row['tp'] ?? '-') ?></td>
                        <td><?= esc($row['lingkup_materi'] ?? $row['lingkup'] ?? '-') ?></td>
                        <td><?= esc($row['aktivitas_tarl'] ?? $row['aktivitas_kognitif'] ?? '-') ?></td>
                        
                        <td style="font-size: 11px;">
                            <?php 
                                $dpl = $row['dpl_terpilih'] ?? [];
                                if (is_string($dpl)) $dpl = explode(',', $dpl);
                                echo !empty($dpl) ? esc(implode(', ', $dpl)) : '-';
                            ?>
                        </td>
                        
                        <td class="text-center" style="font-size: 11px;">
                            <?php 
                                $pc = $row['panca_cinta_terpilih'] ?? [];
                                if (is_string($pc)) $pc = explode(',', $pc);
                                echo !empty($pc) ? esc(implode(', ', $pc)) : '-';
                            ?>
                        </td>
                        
                        <td class="text-center"><?= esc($row['estimasi_jp'] ?? $row['jp'] ?? 0) ?></td>
                        <td class="text-center font-weight-bold">
                            <?php 
                                $tgl = $row['tanggal'] ?? 'Jadwal Habis';
                                echo esc($tgl);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="d-flex justify-content-between" style="margin-top: 40px; padding: 0 40px;">
            <div class="text-center" style="width: 250px;">
                <p class="mb-0">Mengetahui,</p>
                <p class="font-weight-bold" style="min-height: 19px; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Kepala Madrasah,</p>
                <div style="width: 100%; position: relative; z-index: 2;">
                    <img id="ttd-kepala" src="<?= base_url('assets/img/ttd_kepala.png') ?>" alt="TTD Kepala" 
                         style="height: 78px; width: auto; object-fit: contain; top: 0px; margin-top: 3px; margin-bottom: -28px; position: relative; mix-blend-mode: multiply; transform: scale(0.85); left: 0px;" 
                         onerror="this.style.opacity='0'">
                </div>
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3; text-decoration: underline;"><?= esc($kepalaNama) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; position: relative; z-index: 3; margin-top: 4px;">NPK. <?= esc($kepalaNpk) ?></p>
            </div>

            <div class="text-center" style="width: 250px;">
                <p class="mb-0"><?= esc($titiMangsa) ?></p>
                <p class="font-weight-bold" style="min-height: 19px; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Guru Mata Pelajaran,</p>
                <div style="width: 100%; position: relative; z-index: 2;">
                    <img id="ttd-guru" src="<?= base_url('assets/img/ttd_' . esc($userId) . '.png') ?>" alt="TTD Guru" 
                         style="height: 78px; width: auto; object-fit: contain; top: 0px; margin-top: 3px; margin-bottom: -28px; position: relative; mix-blend-mode: multiply; transform: scale(0.85); left: 0px;" 
                         onerror="this.style.opacity='0'">
                </div>
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3; text-decoration: underline;"><?= esc($namaGuruCetak) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; position: relative; z-index: 3; margin-top: 4px;">NPK. <?= esc($guruNpk) ?></p>
            </div>
        </div>

    </div>

    <script>
        let ttdPosX = 0; let ttdPosY = 0;
        let kepalaPosX = 0; let kepalaPosY = 0;
        let ttdScale = 0.85; let kepalaScale = 0.85;

        const imgTtdGuru = document.getElementById('ttd-guru');
        const imgTtdKepala = document.getElementById('ttd-kepala');

        function moveTtd(x, y) {
            ttdPosX += x; ttdPosY += y;
            imgTtdGuru.style.left = ttdPosX + 'px';
            imgTtdGuru.style.top = ttdPosY + 'px';
        }
        function zoomTtd(scaleChange) {
            ttdScale += scaleChange;
            if(ttdScale < 0.3) ttdScale = 0.3; 
            imgTtdGuru.style.transform = `scale(${ttdScale})`;
        }
        function moveKepalaTtd(x, y) {
            kepalaPosX += x; kepalaPosY += y;
            imgTtdKepala.style.left = kepalaPosX + 'px';
            imgTtdKepala.style.top = kepalaPosY + 'px';
        }
        function zoomKepalaTtd(scaleChange) {
            kepalaScale += scaleChange;
            if(kepalaScale < 0.3) kepalaScale = 0.3;
            imgTtdKepala.style.transform = `scale(${kepalaScale})`;
        }
    </script>
</body>
</html>