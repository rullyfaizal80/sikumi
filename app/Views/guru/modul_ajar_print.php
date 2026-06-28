<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak RPP - SiKuMi</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 0; padding: 20px 0; background-color: #525659; }
        .a4-paper { width: 297mm; min-height: 210mm; margin: 0 auto; background: #fff; padding: 12mm 15mm; box-shadow: 0 0 15px rgba(0,0,0,0.4); position: relative; }

        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .a4-paper { width: 100%; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
            .print-actions-wrapper { display: none !important; }
        }

        .d-flex { display: flex !important; }
        .justify-content-between { justify-content: space-between !important; }
        .text-center { text-align: center !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .font-weight-bold { font-weight: bold !important; }
        .text-muted { color: #6c757d !important; }
        .d-inline-block { display: inline-block !important; }

        /* HEADER */
        .header-container { display: flex; justify-content: center; align-items: center; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 15px; width: 100%; }
        .header-content { display: flex; align-items: center; justify-content: center; gap: 20px; width: 100%; }
        .header-content img { height: 70px; width: auto; object-fit: contain; }
        .header-text { text-align: center; margin: 0; line-height: 1.2; }
        .header-text h5 { margin: 0 0 2px 0; font-weight: 800; color: #002060; font-size: 16px; letter-spacing: 0.5px; }
        .header-text h6 { margin: 2px 0; font-weight: 700; font-size: 12px; }
        .badge-semester { font-weight: bold; text-transform: uppercase; color: #fff; font-size: 10px; background-color: #002060 !important; border-radius: 3px; padding: 2px 8px; display: inline-block; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* IDENTITAS AWAL (Kop Atas) */
        .info-table { width: 100%; margin-bottom: 15px; font-weight: bold; font-size: 13px; }
        .info-table td { border: none !important; padding: 3px 8px 3px 0 !important; text-align: left !important; vertical-align: top; }

        /* TABEL KONTEN (Tabel Utama Bergaris) */
        .content-table { width: 100%; border-collapse: collapse; text-align: left; table-layout: auto; margin-bottom: 15px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; vertical-align: top; font-size: 13px; line-height: 1.5; }
        .content-table td.label { font-weight: bold; width: 28%; background-color: #f4f4f4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* GRID UNTUK CHECKBOX DPL & PANCA CINTA */
        
        .grid-3-col {display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 6px; }
        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .check-item { display: flex; align-items: flex-start; font-size: 12px; }
        .check-box { font-size: 16px; line-height: 1; margin-right: 6px; font-family: Arial, sans-serif; }

        /* TABEL KEGIATAN */
        .kegiatan-table th { background-color: #f4f4f4 !important; text-align: center; font-size: 13px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

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
                    <h5>RENCANA PELAKSANAAN PEMBELAJARAN KBC</h5>
                    <h5 style="font-size: 18px; margin-top: 2px;"><?= strtoupper(esc($namaMadrasah ?? 'MTs MIFTAHUL HUDA (MIMHa)')) ?></h5>
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                    <span class="badge-semester">
                        SEMESTER <?= strtoupper(esc($tahunAktif['semester'] ?? '-')) ?>
                    </span>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <?php $kegiatan = json_decode($modulData['kegiatan_pembelajaran'] ?? '{}', true); ?>

         <table class="info-table">
            <tr><td width="120">Mata Pelajaran</td><td width="10">:</td><td><?= esc($namaMapelAktif) ?></td></tr>
            <tr><td>Fase / Kelas</td><td>:</td><td>D / <?= esc($namaRombel) ?></td></tr>            
            <tr><td>Pertemuan Ke-</td><td>:</td><td><?= esc($modulData['pertemuan_ke'] ?? '-') ?></td></tr>
            <tr><td>Alokasi Waktu</td><td>:</td><td><?= esc($modulData['alokasi_jp'] ?? 0) ?> JP (<?= esc($modulData['alokasi_jp'] ?? 0) ?> x <?= esc($modulData['menit_per_jp'] ?? 0) ?> Menit)</td></tr>
            <tr><td>Guru Pengampu</td><td>:</td><td><?= esc($namaGuruCetak) ?></td></tr>
        </table>

        <table class="content-table" style="width: 100%; table-layout: fixed;">
    <colgroup>
        <col style="width: 25%;">
        <col style="width: 75%;">
    </colgroup>

    <tr>
        <td colspan="2" style="background-color: #004080; color: #ffffff; text-align: center; vertical-align: middle; font-weight: bold; padding: 6px;">
            IDENTIFIKASI
        </td>
    </tr>
    
    <tr>
        <td class="label">Kesiapan Murid</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['kesiapan_murid'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Materi Pelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['topik_pembelajaran'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Dimensi Profil Lulusan</td>
        <td>
            <div class="grid-3-col">
                <?php 
                $semuaDpl = [
                    'DPL1' => 'Keimanan dan Ketakwaan', 'DPL4' => 'Kreativitas', 'DPL7' => 'Kesehatan',
                    'DPL2' => 'Kewargaan', 'DPL5' => 'Kolaborasi', 'DPL8' => 'Komunikasi',
                    'DPL3' => 'Penalaran Kritis', 'DPL6' => 'Kemandirian', '' => ''
                ];
                foreach($semuaDpl as $k => $v) {
                    if($k === '') { echo "<div></div>"; continue; }
                    $isChecked = in_array($k, $dplArray) ? '☑' : '☐';
                    echo "<div class='check-item'><span class='check-box'>{$isChecked}</span> <span><b>{$k}</b> {$v}</span></div>";
                }
                ?>
            </div>
        </td>
    </tr>
    <tr>
        <td class="label">Topik Panca Cinta</td>
        <td>
            <div class="grid-2-col">
                <?php 
                $semuaPilar = [
                    'P1' => 'Cinta kepada Allah dan Rasul-Nya', 'P4' => 'Cinta kepada Lingkungan',
                    'P2' => 'Cinta kepada Ilmu', 'P5' => 'Cinta kepada Tanah Air',
                    'P3' => 'Cinta kepada Diri Sendiri dan Sesama', '' => ''
                ];
                foreach($semuaPilar as $k => $v) {
                    if($k === '') continue;
                    $isChecked = in_array($k, $pancaCintaArray) ? '☑' : '☐';
                    echo "<div class='check-item'><span class='check-box'>{$isChecked}</span> <span>{$v}</span></div>";
                }
                ?>
            </div>
        </td>
    </tr>
    <tr>
        <td class="label">Materi Integrasi KBC</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['insersi_kbc'] ?? '-')) ?></td>
    </tr>
</table>

       <table class="content-table" style="width: 100%; table-layout: fixed;">
    <colgroup>
        <col style="width: 25%;"> <col style="width: 75%;"> </colgroup>

    <tr>
        <td colspan="2" style="background-color: #004080; color: #ffffff; text-align: center; vertical-align: middle; font-weight: bold; padding: 6px;">
            DESAIN PEMBELAJARAN
        </td>
    </tr>
    
    <tr>
        <td class="label">Capaian Pembelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['capaian_pembelajaran'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Tujuan Pembelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($tujuanPembelajaranTeks)) ?></td>
    </tr>
    <tr>
        <td class="label">Topik Pembelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($topikPembelajaranTeks)) ?></td>
    </tr>
    <tr>
        <td class="label">Lintas Disiplin Ilmu</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['lintas_disiplin'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Praktik Pedagogis</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['praktik_pedagogis'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Kemitraan Pembelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['kemitraan_pembelajaran'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Lingkungan Pembelajaran</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['lingkungan_pembelajaran'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Pemanfaatan Digital</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['pemanfaatan_digital'] ?? '-')) ?></td>
    </tr>
</table>

        <table class="content-table kegiatan-table" style="width: 100%; table-layout: fixed;">
    <colgroup>
        <col style="width: 20%;">
        <col style="width: 68%;">
        <col style="width: 12%;">
    </colgroup>

    <tr>
        <td colspan="3" style="background-color: #004080; color: #ffffff; text-align: center; vertical-align: middle; font-weight: bold; padding: 6px;">
            PENGALAMAN BELAJAR
        </td>
    </tr>

    <tr>
        <th style="text-align: center; font-weight: bold; padding: 6px;">Tahap Kegiatan</th>
        <th style="text-align: center; font-weight: bold; padding: 6px;">Langkah-langkah Pembelajaran</th>
        <th style="text-align: center; font-weight: bold; padding: 6px;">Waktu</th>
    </tr>

    <tr>
        <td class="font-weight-bold" style="vertical-align: top; padding: 6px;">Kegiatan Awal</td>
        <td style="text-align: justify; padding: 6px;"><?= nl2br(esc($kegiatan['awal']['isi'] ?? '-')) ?></td>
        <td class="text-center font-weight-bold" style="vertical-align: middle; padding: 6px;"><?= esc($kegiatan['awal']['menit'] ?? 0) ?> Menit</td>
    </tr>

    <tr>
        <td class="font-weight-bold" style="vertical-align: top; padding: 6px;">
            Kegiatan Inti<br><br>
            <span style="font-weight:normal; font-style:italic; font-size: 11px;">Pendekatan Pembelajaran Mendalam</span>
        </td>
        <td style="text-align: justify; padding: 6px;">
            <b>A. Memahami :</b><br>
            <?= nl2br(esc($kegiatan['inti']['memahami'] ?? '-')) ?><br><br>
            
            <b>B. Mengaplikasikan :</b><br>
            <?= nl2br(esc($kegiatan['inti']['mengaplikasikan'] ?? '-')) ?><br><br>

            <b>C. Merefleksi :</b><br>
            <?= nl2br(esc($kegiatan['inti']['merefleksi'] ?? '-')) ?>
        </td>
        <td class="text-center font-weight-bold" style="vertical-align: middle; padding: 6px;"><?= esc($kegiatan['inti']['menit'] ?? 0) ?> Menit</td>
    </tr>

    <tr>
        <td class="font-weight-bold" style="vertical-align: top; padding: 6px;">Kegiatan Penutup</td>
        <td style="text-align: justify; padding: 6px;"><?= nl2br(esc($kegiatan['penutup']['isi'] ?? '-')) ?></td>
        <td class="text-center font-weight-bold" style="vertical-align: middle; padding: 6px;"><?= esc($kegiatan['penutup']['menit'] ?? 0) ?> Menit</td>
    </tr>
</table>

        <table class="content-table" style="width: 100%; table-layout: fixed;">
    <colgroup>
        <col style="width: 25%;">
        <col style="width: 75%;">
    </colgroup>

    <tr>
        <td colspan="2" style="background-color: #004080; color: #ffffff; text-align: center; vertical-align: middle; font-weight: bold; padding: 6px;">
            ASESMEN PEMBELAJARAN
        </td>
    </tr>
    
    <tr>
        <td class="label">Asesmen pada Awal Pembelajaran (Diagnostik)</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_awal'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Asesmen pada Proses Pembelajaran (Formatif)</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_proses'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Asesmen pada Akhir Pembelajaran (Sumatif)</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_akhir'] ?? '-')) ?></td>
    </tr>
</table>

        <table class="content-table" style="width: 100%; table-layout: fixed;">
    <colgroup>
        <col style="width: 25%;">
        <col style="width: 75%;">
    </colgroup>

    <tr>
        <td colspan="2" style="background-color: #004080; color: #ffffff; text-align: center; vertical-align: middle; font-weight: bold; padding: 6px;">
            LAMPIRAN
        </td>
    </tr>
    
    <tr>
        <td class="label">Bahan Bacaan / Materi</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['lampiran_materi'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">LKM (Lembar Kerja Murid)</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['lampiran_lkm'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Rubrik Penilaian</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['lampiran_rubrik'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Sumber Belajar Utama</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['sumber_belajar'] ?? '-')) ?></td>
    </tr>
    <tr>
        <td class="label">Contoh Produk (Output)</td>
        <td style="text-align: justify;"><?= nl2br(esc($modulData['contoh_produk'] ?? '-')) ?></td>
    </tr>
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
