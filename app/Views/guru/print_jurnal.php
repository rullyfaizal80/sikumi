<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Jurnal Mengajar - SiKuMi</title>
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
        .btn-close { background: #dc3545; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .control-panel { background: #fff; padding: 12px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid #ddd; text-align: center; width: 140px; }
        .control-panel p { margin: 0 0 8px 0; font-size: 11px; font-weight: bold; color: #333; }
        .d-pad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; justify-items: center; }
        .btn-dpad { background: #e9ecef; border: 1px solid #ced4da; border-radius: 4px; width: 30px; height: 30px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; }
        .btn-dpad:hover { background: #dee2e6; }
        .btn-reset { margin-top: 8px; background: #6c757d; color: white; border: none; border-radius: 4px; font-size: 10px; padding: 4px; width: 100%; cursor: pointer; }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <div class="btn-group-top">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF</button>
            <button class="btn-close" onclick="window.close()">❌ Tutup</button>
        </div>
        <div class="control-panel">
            <p>Atur Posisi TTD Guru</p>
            <div class="d-pad">
                <div></div><button class="btn-dpad" onclick="moveTtd(0, -3)">⬆️</button><div></div>
                <button class="btn-dpad" onclick="moveTtd(-3, 0)">⬅️</button>
                <button class="btn-dpad" onclick="moveTtd(0, 3)">⬇️</button>
                <button class="btn-dpad" onclick="moveTtd(3, 0)">➡️</button>
            </div>
            <button class="btn-reset" onclick="resetTtd()">🔄 Reset Posisi</button>
        </div>
    </div>

    <div class="a4-paper">
        <div class="header-container">
            <div class="header-content">
                <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
                <div class="header-text">
                    <h5>JURNAL HARIAN MENGAJAR GURU</h5>
                    <h5 style="font-size: 18px; margin-top: 2px;"><?= strtoupper(esc($namaMadrasah ?? 'MTs MIFTAHUL HUDA (MIMHa)')) ?></h5>
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                    <span class="badge-semester">
                        SEMESTER <?= strtoupper(esc($tahunAktif['semester'] ?? '-')) ?>
                    </span>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <table class="info-table">
            <tr><td width="120">Mata Pelajaran</td><td width="10">:</td><td><?= esc($selectedMapelName ?? $namaMapelAktif ?? '-') ?></td></tr>
            <tr>
    <td>Bulan</td>
    <td>:</td>
    <td>
        <?php 
            // 1. Dapatkan teks tahun ajaran aktif (contoh: "2025/2026")
            $teksTahunAjaran = $tahunAktif['year_name'] ?? ''; 
            $tahunTampil = date('Y'); // default jika data tidak ada

            if (!empty($teksTahunAjaran)) {
                // Memecah "2025/2026" menjadi array ['2025', '2026']
                $partTahun = explode('/', $teksTahunAjaran);
                
                // 2. Cek bulan yang sedang dipilih/dicetak saat ini ($bulanPilih berasal dari Controller)
                // Jika bulan Juli (07) sampai Desember (12), pakai tahun depan/awal (index 0)
                // Jika bulan Januari (01) sampai Juni (06), pakai tahun belakang/akhir (index 1)
                if (isset($bulanPilih) && (int)$bulanPilih >= 7) {
                    $tahunTampil = $partTahun[0]; // Tahun awal semester ganjil
                } else if (isset($partTahun[1])) {
                    $tahunTampil = $partTahun[1]; // Tahun akhir semester genap
                } else {
                    $tahunTampil = $partTahun[0];
                }
            }
            
            // 3. Tampilkan Nama Bulan beserta Tahun yang sudah disesuaikan secara presisi
            echo esc(($namaRombelAktif ?? '-') . ' ' . $tahunTampil);
        ?>
    </td>
</tr>
            <tr><td>Guru Pengampu</td><td>:</td><td><?= esc($namaGuruCetak) ?></td></tr>
        </table>

        <table class="table table-bordered table-jurnal" border="1" cellspacing="0" cellpadding="5" style="width: 100%; border-collapse: collapse; font-size: 12px;">
    <thead>
        <tr style="background-color: #f2f2f2; text-align: center;">
            <th style="width: 4%;">No</th>
            <th style="width: 10%;">Hari / Tanggal</th>
            <th style="width: 7%;">Kelas</th>
            <th style="width: 23%;">Tujuan Pembelajaran (TP)</th>
            <th style="width: 23%;">Kegiatan Pembelajaran</th>
            <th style="width: 23%;">Refleksi</th>
            <th style="width: 10%;">Murid Tidak Hadir</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($jurnalList)): ?>
            <?php $no = 1; foreach ($jurnalList as $row): ?>
                <tr>
                    <td style="text-align: center;"><?= $no++ ?></td>
                    <td style="text-align: center;"><?= $row['hari_tanggal'] ?></td> 
                    <td style="text-align: center;"><?= esc($row['kelas']) ?></td>
                    <td><?= esc($row['tujuan_pembelajaran']) ?></td>
                    <td><?= esc($row['kegiatan']) ?></td>
                    <td><?= esc($row['refleksi']) ?></td>
                    <td style="text-align: center;"><?= esc($row['absen']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" style="text-align: center; color: #888; font-style: italic; padding: 20px;">
                    Tidak ada data jurnal pada bulan ini.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

        <div class="d-flex justify-content-between signature-section">
            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0">Mengetahui,</p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Kepala Madrasah,</p>
                <img src="<?= base_url('assets/img/ttd_kamad.png') ?>" alt="TTD Kamad" 
                     style="height: 90px; width: auto; object-fit: contain; margin-top: -8px; margin-bottom: -30px; position: relative; z-index: 2; mix-blend-mode: multiply; transform: scale(0.85); left: -25px;" 
                     onerror="this.style.opacity='0'">
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; position: relative; z-index: 3;"><?= esc($kepalaNama ?? '.............................................') ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; position: relative; z-index: 3; margin-top: 4px;">NPK. <?= esc($kepalaNpk ?? '.....................................') ?></p>
            </div>

            <div class="text-center" style="width: 250px; line-height: 1;">
                <p class="mb-0"><?= esc($titiMangsa ?? 'Bandung, ....................................') ?></p>
                <p class="font-weight-bold" style="font-weight: 700; margin-top: 3px; margin-bottom: -15px; position: relative; z-index: 1;">Guru Mata Pelajaran,</p>
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
            if(imgTtdGuru) { imgTtdGuru.style.left = ttdPosX + 'px'; imgTtdGuru.style.top = ttdPosY + 'px'; }
        }

        function resetTtd() {
            ttdPosX = 0; ttdPosY = 0;
            if(imgTtdGuru) { imgTtdGuru.style.left = ttdPosX + 'px'; imgTtdGuru.style.top = ttdPosY + 'px'; }
        }
    </script>
</body>
</html>