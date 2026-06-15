<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Modul Ajar - SiKuMi</title>
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

        /* IDENTITAS AWAL (Kop Atas) */
        .info-table { width: 100%; margin-bottom: 15px; font-weight: bold; font-size: 13px; }
        .info-table td { border: none !important; padding: 3px 8px 3px 0 !important; text-align: left !important; vertical-align: top; }

        /* TABEL KONTEN (Tabel Utama Bergaris) */
        .content-table { width: 100%; border-collapse: collapse; text-align: left; table-layout: auto; margin-bottom: 15px; }
        .content-table th, .content-table td { border: 1px solid #000; padding: 8px; vertical-align: top; font-size: 13px; line-height: 1.5; }
        .content-table td.label { font-weight: bold; width: 28%; background-color: #f4f4f4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* GRID UNTUK CHECKBOX DPL & PANCA CINTA */
        .grid-3-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; }
        .grid-2-col { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .check-item { display: flex; align-items: flex-start; font-size: 12px; }
        .check-box { font-size: 16px; line-height: 1; margin-right: 6px; font-family: Arial, sans-serif; }

        /* TABEL KEGIATAN */
        .kegiatan-table th { background-color: #f4f4f4 !important; text-align: center; font-size: 13px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .signature-section { font-size: 12px; margin-top: 40px; padding: 0 30px; page-break-inside: avoid; }

        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
        .btn-print { background: #002060; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        .btn-close { background: #dc3545; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <div class="btn-group-top">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF</button>
            <button class="btn-close" onclick="window.close()">❌ Tutup</button>
        </div>
    </div>

    <div class="a4-paper">
        <div class="header-container">
            <div class="header-content">
                <img src="<?= base_url('assets/img/logo_kaldik1.png') ?>" alt="Logo Yayasan">
                <div class="header-text">
                    <h5>MODUL AJAR KURIKULUM BERBASIS CINTA</h5>
                    <h5 style="font-size: 18px; margin-top: 2px;"><?= strtoupper(esc($namaMadrasah)) ?></h5>
                    <h6>TAHUN PELAJARAN <?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?></h6>
                </div>
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo MTs">
            </div>
        </div>

        <?php $kegiatan = json_decode($modulData['kegiatan_pembelajaran'] ?? '{}', true); ?>

        <table class="info-table">
            <tr>
                <td width="150">Satuan Pendidikan</td><td width="10">:</td><td width="250"><?= esc($namaMadrasah) ?></td>
                <td width="130">Alokasi Waktu</td><td width="10">:</td><td><?= esc($modulData['alokasi_jp'] ?? 0) ?> JP (<?= esc($modulData['alokasi_jp'] ?? 0) ?> x <?= esc($modulData['menit_per_jp'] ?? 0) ?> Menit)</td>
            </tr>
            <tr>
                <td>Mata Pelajaran</td><td>:</td><td><?= esc($namaMapelAktif) ?></td>
                <td>Pertemuan Ke-</td><td>:</td><td><?= esc($modulData['pertemuan_ke'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>Fase / Kelas</td><td>:</td><td>D / <?= esc($namaRombel) ?></td>
                <td>Semester</td><td>:</td><td><?= strtoupper(esc($tahunAktif['semester'] ?? '-')) ?></td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="label">Peserta Didik</td>
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
                <td class="label">Identifikasi Lima Pilar Panca Cinta</td>
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
                <td class="label">Integrasi Kurikulum Berbasis Cinta (KBC)</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['insersi_kbc'] ?? '-')) ?></td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="label">Capaian Pembelajaran</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['capaian_pembelajaran'] ?? '-')) ?></td>
            </tr>
            <tr>
                <td class="label">Tujuan Pembelajaran</td>
                <td style="text-align: justify;"><?= nl2br(esc($tujuanPembelajaranTeks)) ?></td>
            </tr>
            <tr>
                <td class="label">Lintas Disiplin Ilmu</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['lintas_disiplin'] ?? '-')) ?></td>
            </tr>
            <tr>
                <td class="label">Praktik Pedagogis & Pemanfaatan Digital</td>
                <td style="text-align: justify;">
                    <b>Pedagogis:</b> <?= nl2br(esc($modulData['praktik_pedagogis'] ?? '-')) ?><br><br>
                    <b>Digital:</b> <?= nl2br(esc($modulData['pemanfaatan_digital'] ?? '-')) ?>
                </td>
            </tr>
            <tr>
                <td class="label">Kemitraan & Lingkungan</td>
                <td style="text-align: justify;">
                    <b>Kemitraan:</b> <?= nl2br(esc($modulData['kemitraan_pembelajaran'] ?? '-')) ?><br><br>
                    <b>Lingkungan:</b> <?= nl2br(esc($modulData['lingkungan_pembelajaran'] ?? '-')) ?>
                </td>
            </tr>
        </table>

        <table class="content-table kegiatan-table">
            <tr>
                <th width="20%">Tahap Kegiatan</th>
                <th width="65%">Deskripsi Skenario Pembelajaran</th>
                <th width="15%">Waktu</th>
            </tr>
            <tr>
                <td class="font-weight-bold">Kegiatan Awal</td>
                <td style="text-align: justify;"><?= nl2br(esc($kegiatan['awal']['isi'] ?? '-')) ?></td>
                <td class="text-center font-weight-bold"><?= esc($kegiatan['awal']['menit'] ?? 0) ?> Menit</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Kegiatan Inti<br><br><span style="font-weight:normal; font-style:italic;">Pendekatan Deep Learning</span></td>
                <td style="text-align: justify;">
                    <b>a. Memahami (Meaningful Learning):</b><br>
                    <?= nl2br(esc($kegiatan['inti']['memahami'] ?? '-')) ?><br><br>
                    
                    <b>b. Mengaplikasikan (Joyful Learning):</b><br>
                    <?= nl2br(esc($kegiatan['inti']['mengaplikasikan'] ?? '-')) ?><br><br>

                    <b>c. Merefleksi (Mindful Learning):</b><br>
                    <?= nl2br(esc($kegiatan['inti']['merefleksi'] ?? '-')) ?>
                </td>
                <td class="text-center font-weight-bold"><?= esc($kegiatan['inti']['menit'] ?? 0) ?> Menit</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Kegiatan Penutup</td>
                <td style="text-align: justify;"><?= nl2br(esc($kegiatan['penutup']['isi'] ?? '-')) ?></td>
                <td class="text-center font-weight-bold"><?= esc($kegiatan['penutup']['menit'] ?? 0) ?> Menit</td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="label">Asesmen Awal (Diagnostik)</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_awal'] ?? '-')) ?></td>
            </tr>
            <tr>
                <td class="label">Asesmen Proses (Formatif)</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_proses'] ?? '-')) ?></td>
            </tr>
            <tr>
                <td class="label">Asesmen Akhir (Sumatif)</td>
                <td style="text-align: justify;"><?= nl2br(esc($modulData['asesmen_akhir'] ?? '-')) ?></td>
            </tr>
        </table>

        <table class="content-table">
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
            <div class="text-center" style="width: 250px; line-height: 1.2;">
                <p>Mengetahui,<br>Kepala Madrasah,</p>
                <br><br><br>
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; border-bottom: 1px solid #000; padding-bottom: 2px;"><?= esc($kepalaNama) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; margin-top: 4px;">NPK. <?= esc($kepalaNpk) ?></p>
            </div>

            <div class="text-center" style="width: 250px; line-height: 1.2;">
                <p><?= esc($titiMangsa) ?><br>Guru Mata Pelajaran,</p>
                <br><br><br>
                <p class="font-weight-bold mb-0 d-inline-block" style="font-weight: 800; border-bottom: 1px solid #000; padding-bottom: 2px;"><?= esc($namaGuruCetak) ?></p>
                <p class="text-muted small mb-0" style="font-size: 11px; margin-top: 4px;">NPK. <?= esc($guruNpk) ?></p>
            </div>
        </div>

    </div>

</body>
</html>
