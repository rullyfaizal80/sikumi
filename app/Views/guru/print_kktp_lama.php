<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print KKTP - SiKuMi</title>
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
        .text-end { text-align: right !important; }
        .font-weight-bold { font-weight: bold !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 4px !important; }
        .mb-2 { margin-bottom: 8px !important; }
        .mb-3 { margin-bottom: 12px !important; }
        .mt-4 { margin-top: 16px !important; }
        .d-inline-block { display: inline-block !important; }
        .w-100 { width: 100% !important; }

        /* HEADER & METADATA */
        .main-title { font-size: 14pt; text-transform: uppercase; font-weight: bold; text-align: center; margin-bottom: 15px; }
        .meta-table { width: 100%; font-size: 10.5pt; margin-bottom: 12px; line-height: 1.2; }
        .meta-table td { padding: 2px 0; vertical-align: top; }

        /* BERUBAH: SEESUAI STRUKTUR TABEL KKTP */
        .table-print-kktp { width: 100%; border-collapse: collapse; margin-bottom: 20px; table-layout: fixed; }
        .table-print-kktp th { border: 1px solid #000; font-size: 10pt; padding: 5px; font-weight: bold; text-align: center; vertical-align: middle; background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .table-print-kktp td { border: 1px solid #000; font-size: 9.5pt; padding: 6px; vertical-align: top; line-height: 1.35; word-wrap: break-word; }

        /* LAYOUT KOLOM TTD */
        .ttd-container { width: 100%; margin-top: 25px; font-size: 11pt; page-break-inside: avoid; }
        .ttd-box { width: 280px; text-align: left; line-height: 1.3; }

        /* FLOATING ACTION ACTION */
        .print-actions-wrapper { position: fixed; bottom: 25px; right: 25px; z-index: 9999; display: flex; gap: 10px; background: rgba(0,0,0,0.7); padding: 10px 15px; border-radius: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .btn-print-action { border: none; padding: 8px 16px; font-size: 12px; font-weight: bold; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-blue { background-color: #007bff; color: white; }
        .btn-blue:hover { background-color: #0056b3; }
        .btn-grey { background-color: #e0e0e0; color: #333; }
        .btn-grey:hover { background-color: #cbd5e1; }
        .btn-tool-ttd { border: none; width: 28px; height: 28px; border-radius: 50%; font-weight: bold; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <button onclick="window.print();" class="btn-print-action btn-blue">🖨️ Cetak Sekarang</button>
        <button onclick="window.close();" class="btn-print-action btn-grey">❌ Tutup</button>
        <div style="display: flex; gap: 4px; align-items: center; margin-left: 5px; border-left: 1px solid #fff; padding-left: 10px;">
            <span style="color: #fff; font-size: 10px; font-weight: bold; margin-right: 2px;">Posisi TTD:</span>
            <button class="btn-tool-ttd" onclick="moveTtd(0, -4)" title="Atas">▲</button>
            <button class="btn-tool-ttd" onclick="moveTtd(0, 4)" title="Bawah">▼</button>
            <button class="btn-tool-ttd" onclick="moveTtd(-4, 0)" title="Kiri">◀</button>
            <button class="btn-tool-ttd" onclick="moveTtd(4, 0)" title="Kanan">▶</button>
        </div>
    </div>

    <div class="a4-paper">
        <div class="main-title">
            KRITERIA KETERCAPAIAN TUJUAN PEMBELAJARAN (KKTP)<br>
            TAHUN AJARAN <?= esc($tahunAktif['year_name'] ?? '-') ?>
        </div>

        <table class="meta-table">
            <tr>
                <td style="width: 140px;">Nama Madrasah</td>
                <td style="width: 15px;">:</td>
                <td class="font-weight-bold" style="width: 450px;"><?= esc($namaMadrasah) ?></td>
                <td style="width: 140px;">Kelas / Rombel</td>
                <td style="width: 15px;">:</td>
                <td class="font-weight-bold"><?= esc($namaRombelAktif) ?></td>
            </tr>
            <tr>
                <td>Mata Pelajaran</td>
                <td>:</td>
                <td class="font-weight-bold"><?= esc($namaMapelAktif) ?></td>
                <td>Fase</td>
                <td>:</td>
                <td class="font-weight-bold"><?= esc($faseAktif) ?></td>
            </tr>
            <tr>
                <td>Semester</td>
                <td>:</td>
                <td class="font-weight-bold" colspan="4">
                    <?= ($tahunAktif['semester'] ?? 1) == 1 ? '1 (Ganjil)' : '2 (Genap)' ?>
                </td>
            </tr>
        </table>

        <table class="table-print-kktp">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Tujuan Pembelajaran (TP)</th>
                    <th style="width: 22%;">Indikator Ketercapaian</th>
                    <th style="width: 12%;">Perlu Bimbingan<br>(0 - 60)</th>
                    <th style="width: 12%;">Cukup<br>(61 - 70)</th>
                    <th style="width: 12%;">Baik<br>(71 - 80)</th>
                    <th style="width: 12%;">Sangat Baik<br>(81 - 100)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($kktpData)): ?>
                    <?php $no = 1; foreach ($kktpData as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= esc($row['tujuan_pembelajaran'] ?? '') ?></td>
                            <td><?= esc($row['indikator'] ?? '-') ?></td>
                            <td><?= esc($row['skor_perlu_bimbingan'] ?? '-') ?></td>
                            <td><?= esc($row['skor_cukup'] ?? '-') ?></td>
                            <td><?= esc($row['skor_baik'] ?? '-') ?></td>
                            <td><?= esc($row['skor_sangat_baik'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 15px;">Belum ada data Rubrik KKTP yang di-generate atau di-copy untuk kelas ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="ttd-container d-flex justify-content-between">
            <div class="ttd-box">
                <p class="mb-1">Mengetahui,</p>
                <p class="font-weight-bold mb-0" style="font-weight: 700;">Kepala Madrasah</p>
                <div style="height: 75px;"></div>
                <p class="font-weight-bold mb-0" style="font-weight: 800;"><?= esc($kepalaSekolah) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; margin-top: 4px;">&nbsp;</p>
            </div>

            <div class="ttd-box text-left" style="padding-left: 50px;">
                <p class="mb-1"><?= esc($titiMangsa) ? esc($titiMangsa) : '..................., .............' ?></p>
                <p class="font-weight-bold mb-0" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Guru Mata Pelajaran,</p>
                <div style="width: 100%; position: relative; z-index: 2;">
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
        let ttdPosX = 0; let ttdPosY = 0;
        const imgTtdGuru = document.getElementById('ttd-guru');

        function moveTtd(x, y) {
            ttdPosX += x; ttdPosY += y;
            if(imgTtdGuru) {
                imgTtdGuru.style.left = ttdPosX + 'px';
                imgTtdGuru.style.top = ttdPosY + 'px';
            }
        }
    </script>
</body>
</html>