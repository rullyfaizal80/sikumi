<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rapor - <?= esc($dataSiswa['name'] ?? 'Siswa') ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Tambahkan Library html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { background-color: #525659; font-family: 'Open Sans', sans-serif; color: #333; padding: 20px 0; }
        
        /* DESAIN KERTAS A4 DI PREVIEW LAYAR */
        .a4-paper { 
            width: 210mm; 
            height: 297mm; 
            margin: 0 auto 30px auto; 
            background: #ffffff; 
            padding: 12mm 15mm; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            border-top: 8px solid #1976d2; 
            border-radius: 8px; 
            position: relative;
            overflow: hidden;
            page-break-after: always; 
            break-after: page;
        }

        /* MENCEGAH HALAMAN KOSONG DI AKHIR PDF */
        #area-pdf .a4-paper:last-child {
            page-break-after: auto !important;
            break-after: auto !important;
        }

        /* WATERMARK DI LAYAR */
        .a4-paper::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 75%; 
            height: 75%;
            background-image: url('<?= base_url('assets/img/logo_kaldik2.png') ?>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.1; /* <--- Atur Transparansi di sini (misal 0.05 untuk lebih tipis) */
            z-index: 0;
            pointer-events: none;
        }

        .a4-paper > * {
            position: relative;
            z-index: 1;
        }

        /* CSS KHUSUS SAAT PROSES DIRECT DOWNLOAD PDF (html2pdf) */
        .mode-download .a4-paper {
            margin: 0 !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            border-top: 8px solid #1976d2 !important;
            height: 296.5mm !important; 
            max-height: 296.5mm !important;
        }

        /* ==========================================================
           METODE CETAK BAWAAN PRINTER (PRINT STYLES) 
           ========================================================== */
        @page { size: A4 portrait; margin: 0; }
        
        @media print {
            html, body { 
                background: #fff !important; 
                padding: 0 !important; 
                margin: 0 !important; 
            }
            
            .a4-paper { 
                width: 210mm !important; 
                height: 297mm !important; 
                max-height: 297mm !important;
                margin: 0 !important; 
                padding: 10mm 12mm !important; /* Ruang lebih lega untuk printer */
                box-shadow: none !important; 
                border-top: 8px solid #1976d2 !important; 
                border-radius: 0 !important; 
                
                page-break-after: always !important; 
                break-after: page !important; 
                page-break-inside: avoid !important; 
                break-inside: avoid !important; 
                overflow: hidden !important; 
            }
            
            /* KUNCI WATERMARK KE TITIK TENGAH A4 FISIK */
            /* Memastikan logo tidak turun meskipun tabel sedikit memaksa melar */
            .a4-paper::before {
                top: 148.5mm !important; /* 148.5mm adalah angka mati setengah tinggi A4 */
                left: 105mm !important;  /* 105mm adalah angka mati setengah lebar A4 */
                transform: translate(-50%, -50%) !important;
            }

            /* PADATKAN KONTEN AGAR MUAT 1 HALAMAN TANPA MERUSAK LAYOUT */
            table.data-table { font-size: 9.5px !important; margin-bottom: 6px !important; }
            table.data-table th, table.data-table td { padding: 4px 4px !important; }
            
            .section-title { font-size: 10.5px !important; margin: 8px 0 4px 0 !important; padding: 4px 6px !important; }
            .identitas-box { margin-bottom: 8px !important; padding: 8px 10px !important; font-size: 10.5px !important; }
            .header-sekolah { margin-bottom: 10px !important; padding-bottom: 5px !important; }
            .header-sekolah h2 { font-size: 16px !important; margin-bottom: 2px !important; }
            
            .catatan-box { padding: 6px 8px !important; min-height: 50px !important; }
            .catatan-box h4 { font-size: 10.5px !important; margin-bottom: 4px !important; padding-bottom: 2px !important; }
            ul.list-catatan { font-size: 9.5px !important; }
            .signature-section { margin-top: 10px !important; }
            
            #area-pdf .a4-paper:last-child { page-break-after: auto !important; break-after: auto !important; }
            .print-actions-wrapper { display: none !important; }
        }

        /* MEMAKSA BACKGROUND WARNA & WATERMARK TERCETAK */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        /* TAMPILAN KONTEN DEFAULT (LAYAR) */
       /* UPDATE CSS UNTUK COVER */
        .cover-wrapper { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            /* KUNCI KESEIMBANGAN: Jarak kosong dibagi merata ke tengah */
            justify-content: space-between; 
            height: 100%; 
            text-align: center; 
            /* Beri bantalan agar konten tidak menempel ke garis tepi kertas */
            padding: 30px 0 20px 0; 
        }
        .cover-title { font-family: 'Merriweather', serif; font-size: 26px; color: #15202b; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-top: 15px; }
        .cover-logo { margin: 0; }
        .cover-logo img { width: 130px; height: auto; }
        .cover-student-box { 
            background-color: #f4f9fd; 
            border-left: 5px solid #0d47a1; 
            border-right: 1px solid #ddd; 
            border-top: 1px solid #ddd; 
            border-bottom: 1px solid #ddd; 
            padding: 18px; 
            width: 100%; /* Diubah menjadi 100% agar pas dengan lebar cover-bottom */
            margin: 0 auto 55px auto; /* Angka 55px menentukan jarak/gap ke teks footer di bawahnya */
            border-radius: 6px; 
        }
        .cover-student-box h3 { margin: 0 0 8px 0; font-family: 'Merriweather', serif; color: #15202b; font-size: 20px; text-transform: uppercase; }
        .cover-student-box p { margin: 0; font-size: 15px; color: #535c5d; font-weight: 600; }
        .cover-footer { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #15202b; line-height: 1.5; }

        .header-sekolah { text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 8px; margin-bottom: 12px; }
        .header-sekolah h2 { font-family: 'Merriweather', serif; color: #15202b; margin: 0 0 4px 0; font-size: 18px; text-transform: uppercase; }
        .identitas-box { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 11.5px; background-color: #f4f9fd; padding: 10px 12px; border-radius: 6px; border-left: 4px solid #0d47a1; border: 1px solid #ddd; }
        .identitas-table td { padding: 2px 5px 2px 0; vertical-align: top; }
        .identitas-table td:first-child { font-weight: 700; width: 105px; color: #15202b; }
        .section-title { font-family: 'Merriweather', serif; font-size: 11.5px; background-color: #0d47a1; color: #ffffff; padding: 5px 8px; margin: 12px 0 6px 0; font-weight: bold; border-radius: 4px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10.5px; background: transparent; }
        table.data-table th, table.data-table td { border: 1px solid #222222; padding: 5px 5px; vertical-align: middle; }
        table.data-table th { background-color: #eaf3fa !important; color: #15202b; text-align: center; font-weight: 700; border-bottom: 2px solid #1976d2; }
        .col-aspek { font-weight: 600; text-align: left; }
        .col-angka { text-align: center; }
        .col-rata { text-align: center; font-weight: bold; background-color: #e8f4fd !important; color: #0d47a1; }
        tr { page-break-inside: avoid; page-break-after: auto; }

        .catatan-box-container { display: flex; gap: 12px; margin-bottom: 10px; }
        .catatan-box { flex: 1; border: 1px solid #888888; background-color: transparent; border-radius: 6px; padding: 10px; border-top: 3px solid #1976d2; min-height: 70px; }
        .catatan-box h4 { margin-top: 0; font-family: 'Merriweather', serif; font-size: 11.5px; color: #15202b; border-bottom: 1px solid #cccccc; padding-bottom: 4px; margin-bottom: 6px; }
        ul.list-catatan { margin: 0; padding-left: 16px; color: #212529; font-size: 10.5px; }

        .signature-section { width: 100%; margin-top: 15px; font-size: 11px; page-break-inside: avoid; }
        .signature-table { width: 100%; text-align: center; border: none; margin-bottom: 6px; }
        .signature-table td { border: none; padding: 2px; width: 50%; vertical-align: top; }
        .signature-kamad { width: 100%; text-align: center; border: none; margin-top: 8px; }
        .signature-kamad td { border: none; padding: 2px; }

        /* KONTROL MENGAMBANG */
        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .btn-action { padding: 10px 18px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-family: 'Open Sans', sans-serif; transition: 0.3s; color: #fff; display: flex; align-items: center; gap: 6px; }
        .btn-print { background: #1976d2; } .btn-print:hover { background: #1565c0; }
        .btn-download { background: #28a745; } .btn-download:hover { background: #218838; }
        .btn-close { background: #dc3545; } .btn-close:hover { background: #c82333; }
        .btn-action:disabled { background: #6c757d; cursor: not-allowed; }
    </style>
</head>
<body>

    <!-- TOMBOL AKSI -->
    <div class="print-actions-wrapper">
        <button class="btn-action btn-download" id="btnDownloadPdf" onclick="unduhPDF()">⬇️ Download PDF</button>
        <button class="btn-action btn-print" onclick="window.print()">🖨️ Cetak PDF</button>
        <button class="btn-action btn-close" onclick="window.close()">🆇 Tutup</button>
    </div>

    <?php 
        $rawDate = $_GET['titi_mangsa'] ?? date('Y-m-d');
        $bulanIndoFull = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
        $parts = explode('-', $rawDate);
        if(count($parts) == 3) {
            $tglFormatted = $parts[2] . ' ' . $bulanIndoFull[$parts[1]] . ' ' . $parts[0];
            $titiMangsaStr = 'Bandung, ' . $tglFormatted; 
        } else {
            $titiMangsaStr = 'Bandung, ................................';
        }

        $namaBulanIndo = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
        $semuaBulan = (strtolower($semester) === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        $fmt = function($angka, $b) use ($bulanAktif) { 
            if (!in_array($b, $bulanAktif)) return ''; 
            return $angka !== null ? str_replace('.', ',', (float)$angka) : '-'; 
        };
    ?>

    <!-- BUNGKUS SELURUH KERTAS UNTUK html2pdf -->
    <div id="area-pdf">

        <!-- ================= HALAMAN 1 : COVER ================= -->
        <div class="a4-paper">
            <div class="cover-wrapper">
                
                <!-- KELOMPOK 1: ATAS -->
                <div class="cover-top" style="width: 100%;">
                    <div>
                        <img src="<?= base_url('assets/img/logo_kemenag.png') ?>" alt="Logo Kemenag" style="width: 300px; height: auto;">
                    </div>
                    <div class="cover-title" style="margin-top: 50px;">
                        Laporan Perkembangan Murid
                    </div>
                </div>

                <!-- KELOMPOK 2: TENGAH -->
                <div class="cover-logo">
                    <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo Sekolah" onerror="this.style.display='none'">
                </div>

                <!-- KELOMPOK 3: BAWAH (Didorong ke dasar kertas) -->
                <!-- Gunakan display: flex dan gap untuk jarak pasti yang anti-gagal -->
                <div class="cover-bottom" style="width: 75%; margin-bottom: 40px; display: flex; flex-direction: column; gap: 70px;">
                    
                    <!-- Kotak Identitas Siswa -->
                    <div class="cover-student-box" style="margin: 0; width: 100%;">
                        <p style="font-size: 12px; text-transform: uppercase; margin-bottom: 4px;">Nama Murid</p>
                        <h3><?= esc($dataSiswa['name']) ?></h3>
                        <p>NIS/NISN : <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></p>
                    </div>
                    
                    <!-- Teks Yayasan Bawah -->
                    <div class="cover-footer" style="margin: 0;">
                        MTSS MIFTAHUL HUDA<br>
                        KOTA BANDUNG<br>
                        PROVINSI JAWA BARAT
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= HALAMAN 2 : AKADEMIK, QUR'AN, KEHADIRAN ================= -->
        <div class="a4-paper">
            <div class="header-sekolah">
                <h2>Laporan Perkembangan Murid</h2>
            </div>

            <div class="identitas-box">
                <table class="identitas-table" style="width: 50%;">
                    <tr><td>Nama Lengkap</td><td>: <?= esc($dataSiswa['name']) ?></td></tr>
                    <tr><td>NIS / NISN</td><td>: <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></td></tr>
                    <tr><td>Wali Kelas</td><td>: <?= esc($dataSiswa['wali_kelas'] ?? '-') ?></td></tr>
                </table>
                <table class="identitas-table" style="width: 45%;">
                    <tr><td>Kelas</td><td>: <?= esc($dataSiswa['kelas']) ?></td></tr>
                    <tr><td>Semester</td><td>: <?= esc(ucfirst($semester)) ?></td></tr>
                    <tr><td>Tahun Ajaran</td><td>: <?= esc($tahun) ?>/<?= esc($tahun + 1) ?></td></tr>
                </table>
            </div>

            <!-- A. PERKEMBANGAN AKADEMIK -->
            <div class="section-title" style="margin-top: 25px;">A. Perkembangan Akademik (Nilai Sumatif)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Mata Pelajaran</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                        <th width="14%">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matrixSumatif)): ?>
                        <tr><td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">Belum ada data nilai mata pelajaran.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matrixSumatif as $mapel): ?>
                            <tr>
                                <td class="col-aspek"><?= esc($mapel['nama_mapel']) ?></td>
                                <?php foreach ($semuaBulan as $b): ?>
                                    <td class="col-angka"><?= $fmt($mapel['nilai'][$b] ?? null, $b) ?></td>
                                <?php endforeach; ?>
                                <td class="col-rata"><?= $mapel['count'] > 0 ? str_replace('.', ',', round($mapel['total'] / $mapel['count'], 2)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- B. PERKEMBANGAN AL-QUR'AN -->
            <div class="section-title" style="margin-top: 25px;">B. Perkembangan Al-Qur'an</div>
            <table class="data-table">
                <thead> 
                    <tr> 
                        <th width="32%">Aspek Penilaian</th> 
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?> 
                        <th width="14%">Rata-rata</th> 
                    </tr> 
                </thead>
                <tbody>
                    <?php if (empty($matrixQuran)): ?>
                        <tr><td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">Belum ada data nilai Al-Qur'an.</td></tr>
                    <?php else: ?>
                        <?php 
                            $kelasSiswa = $dataSiswa['kelas'] ?? '';
                            $isKelas7 = preg_match('/(7|VII)/i', $kelasSiswa);
                        ?>
                        <?php foreach ($matrixQuran as $aspek => $dataQuran): ?>
                            <?php if ($isKelas7 && strtolower($aspek) === 'tahfidz') continue; ?>
                            <tr>
                                <td class="col-aspek"><?= esc($aspek) ?></td>
                                <?php foreach ($semuaBulan as $b): ?>
                                    <td class="col-angka"><?= $fmt($dataQuran['nilai'][$b] ?? null, $b) ?></td>
                                <?php endforeach; ?>
                                <td class="col-rata"><?= $dataQuran['count'] > 0 ? str_replace('.', ',', round($dataQuran['total'] / $dataQuran['count'], 2)) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- C. KEHADIRAN (ABSENSI) -->
            <div class="section-title" style="margin-top: 25px;">C. Rekapitulasi Kehadiran</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Keterangan</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                        <th width="14%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $labelAbsen = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa (Tanpa Keterangan)', 'T' => 'Frekuensi Keterlambatan', 'M' => 'Akumulasi Menit Terlambat'];
                        foreach ($labelAbsen as $kode => $label):
                    ?>
                    <tr>
                        <td class="col-aspek"><?= $label ?></td>
                        <?php foreach ($semuaBulan as $b): ?>
                            <td class="col-angka">
                                <?php 
                                    if (!in_array($b, $bulanAktif)) echo ''; 
                                    else {
                                        $val = $matrixAbsen[$kode][$b] ?? '-';
                                        echo ($kode === 'M' && ($val === '-' || $val === '' || $val === null)) ? '0' : esc($val);
                                    }
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="col-rata">
                            <?php 
                                $valTotal = $totalAbsen[$kode] ?? '-';
                                echo ($kode === 'M' && ($valTotal === '-' || $valTotal === '' || $valTotal === null)) ? '0' : esc($valTotal);
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= HALAMAN 3 : KEPATUHAN, SPIRITUAL, SOSIAL ================= -->
        <div class="a4-paper">
            <!-- D. SIKAP & KEPATUHAN -->
            <div class="section-title" style="margin-top: 0;">D. Catatan Kepatuhan (Jumlah Kejadian/Pelanggaran)</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Indikator Kepatuhan</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                        <th width="14%">Total Kasus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $labelPatuh = ['seragam' => 'Ketidaksesuaian Seragam', 'atribut' => 'Atribut Tidak Lengkap', 'bersih_diri' => 'Kurang Menjaga Kebersihan Diri', 'terlambat' => 'Keterlambatan Hadir', 'aturan_kelas' => 'Melanggar Peraturan Kelas', 'masjid' => 'Melanggar Ketertiban Masjid'];
                        foreach ($labelPatuh as $k => $label):
                    ?>
                    <tr>
                        <td class="col-aspek"><?= $label ?></td>
                        <?php foreach ($semuaBulan as $b): ?>
                            <td class="col-angka"><?= in_array($b, $bulanAktif) ? ($kepatuhan['matrix'][$k][$b] ?? 0) : '' ?></td>
                        <?php endforeach; ?>
                        <td class="col-rata"><?= $kepatuhan['totals'][$k] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f9f9f9;">
                        <td class="col-aspek" style="font-style: italic;">Rincian Pelanggaran:</td>
                        <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 10px; padding: 4px 6px; line-height: 1.3; text-align: left;">
                            <?= $keteranganPelanggaran ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- E. PERKEMBANGAN SIKAP SPIRITUAL -->
            <div class="section-title" style="margin-top: 25px;">E. Perkembangan Sikap Spiritual</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Sikap Spiritual yang Diamati</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                        <th width="14%">Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $labelSpiritual = ['berdoa' => 'Membiasakan Berdoa', 'kalimat_thoyibah' => 'Mengucapkan Kalimat Thoyibah', 'shalat' => 'Menjalankan Ibadah Shalat', 'salam' => 'Membudayakan Salam', 'syukur' => 'Menunjukkan Rasa Syukur', 'lingkungan' => 'Menjaga Lingkungan', 'toleransi' => 'Toleransi Beragama'];
                        foreach ($labelSpiritual as $k => $label):
                    ?>
                    <tr>
                        <td class="col-aspek" style="font-weight: normal;"><?= $label ?></td>
                        <?php foreach ($semuaBulan as $b): ?>
                            <td class="col-angka"><?= in_array($b, $bulanAktif) ? ($spiritual['matrix'][$k][$b] ?? '-') : '' ?></td>
                        <?php endforeach; ?>
                       <td class="col-rata"><?= $spiritual['totals_predikat'][$k] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f9f9f9;">
                        <td class="col-aspek" style="font-style: italic;">Rincian Catatan Spiritual:</td>
                        <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 10px; padding: 4px 6px; line-height: 1.3; text-align: left;">
                            <?= $spiritual['keterangan_rincian'] ?? '-' ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- F. PERKEMBANGAN SIKAP SOSIAL -->
            <div class="section-title" style="margin-top: 25px;">F. Perkembangan Sikap Sosial</div>
            <table class="data-table" style="margin-bottom: 5px;">
                <thead>
                    <tr>
                        <th width="32%">Sikap Sosial yang Diamati</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                        <th width="14%">Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $labelSosial = ['disiplin' => 'Kedisiplinan', 'jujur' => 'Kejujuran', 'percaya_diri' => 'Kepercayaan Diri', 'santun' => 'Kesantunan', 'kerjasama' => 'Kerja Sama', 'tanggung_jawab' => 'Tanggung Jawab', 'adil' => 'Keadilan'];
                        foreach ($labelSosial as $k => $label):
                    ?>
                    <tr>
                        <td class="col-aspek" style="font-weight: normal;"><?= $label ?></td>
                        <?php foreach ($semuaBulan as $b): ?>
                            <td class="col-angka"><?= in_array($b, $bulanAktif) ? ($sosial['matrix'][$k][$b] ?? '-') : '' ?></td>
                        <?php endforeach; ?>
                        <td class="col-rata"><?= $sosial['totals_predikat'][$k] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background-color: #f9f9f9;">
                        <td class="col-aspek" style="font-style: italic;">Rincian Catatan Sosial:</td>
                        <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 10px; padding: 4px 6px; line-height: 1.3; text-align: left;">
                            <?= $sosial['keterangan_rincian'] ?? '-' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size: 10px; color: #444; margin-top: 15px;">
                <strong>Keterangan Penilaian Karakter:</strong><br>
        A = Tidak pernah melanggar ketentuan<br>
        B = 1 - 2 kali melanggar ketentuan<br>
        C = 3 - 4 kali melanggar ketentuan<br>
        D = > 4 kali melanggar ketentuan
            </div>
        </div>

        <!-- ================= HALAMAN 4 : ESKUL, YAUMIYAH, TANDA TANGAN ================= -->
        <div class="a4-paper">
            <!-- G. EKSTRAKURIKULER, PRAMUKA & PEMINATAN -->
            <div class="section-title" style="margin-top: 0;">G. Ekstrakurikuler, Pramuka & Peminatan</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Kegiatan / Ekstrakurikuler</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= $namaBulanIndo[$b] ?? $b ?></th><?php endforeach; ?>
                        <th width="14%">Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matrixEskul)): ?>
                        <tr><td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">Belum ada data ekstrakurikuler.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matrixEskul as $key => $row): ?>
                        <tr>
                            <td class="col-aspek"><?= esc($row['label']) ?></td>
                            <?php foreach ($semuaBulan as $b): ?>
                                <td class="col-angka"><?= in_array($b, $bulanAktif) ? esc($row['bulan'][$b] ?? '-') : '' ?></td>
                            <?php endforeach; ?>
                            <td class="col-rata"><strong><?= esc($row['predikat_akhir'] ?? '-') ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
             <div style="font-size: 10px; color: #444; margin-top: 15px;">
                <strong>Keterangan Predikat Nilai:</strong><br>
            A = Sangat Baik (90 - 100)<br>
            B = Baik (80 - 89)<br>
            C = Cukup (70 - 79)<br>
            D = Kurang (&lt; 69)
            </div>
        
            <!-- H. ANEKDOT & PRESTASI -->
            <div class="section-title" style="margin-top: 25px;">H. Catatan Anekdot & Prestasi</div>
            <div class="catatan-box-container">
                <div class="catatan-box">
                    <h4>Prestasi / Penghargaan</h4>
                    <?php if (!empty($prestasi)): ?>
                        <ul class="list-catatan">
                            <?php foreach ($prestasi as $p): ?>
                                <li><strong><?= esc($p['nama_prestasi']) ?></strong>: <?= esc($p['keterangan']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="font-size: 10px; color: #666; margin:0;">Belum ada catatan prestasi.</p>
                    <?php endif; ?>
                </div>
                <div class="catatan-box">
                    <h4>Catatan Khusus (Anekdot)</h4>
                    <?php if (!empty($anekdot)): ?>
                        <ul class="list-catatan">
                            <?php foreach ($anekdot as $a): ?>
                                <li><?= esc($a['kejadian']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="font-size: 10px; color: #666; margin:0;">Belum ada catatan khusus.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- I. REKAPITULASI ASPEK YAUMIYAH -->
            <div class="section-title" style="margin-top: 25px;">I. Rekapitulasi Aspek Yaumiyah</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="32%">Aspek Yaumiyah</th>
                        <?php foreach ($semuaBulan as $b): ?><th width="9%"><?= esc($namaBulanIndo[$b] ?? $b) ?></th><?php endforeach; ?>
                        <th width="14%">Rata-rata</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $labelYaumiyah = [
                        'p_dzuhur'   => 'Shalat Dzuhur di Sekolah',
                        'p_ashar'    => 'Shalat Ashar di Sekolah',
                        'p_bakdiah'  => 'Ba\'diah Dzuhur di Sekolah',
                        'p_duha'     => 'Shalat Duha di Sekolah',
                        'p_tahajud'  => 'Shalat Tahajud (1x/minggu)',
                        'p_tilawah'  => 'Tilawah (1 halaman/hari)',
                        'p_infaq'    => 'Infaq (1x/minggu)',
                        'p_shaum'    => 'Puasa Sunah (2x/bulan)',
                        'p_literasi' => 'Literasi (1 halaman/hari)'
                    ];
                    
                    if (isset($matrixYaumiyah) && !empty($matrixYaumiyah)):
                        foreach ($labelYaumiyah as $key => $label): 
                            $totalSatuBaris = 0; $jumlahBulanAktif = 0;
                    ?>
                        <tr>
                            <td class="col-aspek" style="font-weight: normal; padding-left: 8px;"><?= esc($label) ?></td>
                            <?php foreach ($semuaBulan as $b): 
                                if (!in_array($b, $bulanAktif)) echo '<td class="col-angka"></td>'; 
                                else {
                                    $nilaiPersen = isset($matrixYaumiyah[$key][$b]) ? (float)$matrixYaumiyah[$key][$b] : 0;
                                    if ($nilaiPersen > 0) { $totalSatuBaris += $nilaiPersen; $jumlahBulanAktif++; }
                                    echo '<td class="col-angka">' . ($nilaiPersen > 0 ? number_format($nilaiPersen, 0) . '%' : '0%') . '</td>';
                                }
                            endforeach; ?>
                            <td class="col-rata">
                                <strong><?= $jumlahBulanAktif > 0 ? number_format($totalSatuBaris / $jumlahBulanAktif, 0) . '%' : '0%' ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">Belum ada data rekapitulasi yaumiyah.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <div style="font-size: 10px; color: #444;">
                * Nilai yang ditampilkan adalah persentase capaian (%) dari target berdasarkan jumlah hari efektif sekolah per bulan.
            </div>
            
            <!-- BLOK TANDA TANGAN (Di Halaman 4) -->
            <div style="margin-top: 35px;" class="signature-section">
                <table class="signature-table">
                    <tr>
                        <td>Mengetahui,<br>Orang Tua / Wali Murid</td>
                        <td><?= esc($titiMangsaStr) ?><br>Wali Kelas</td>
                    </tr>
                    <tr>
                        <td style="height: 70px; vertical-align: bottom;">
                            <b>( ........................................... )</b>
                        </td>
                        <td style="height: 70px; vertical-align: bottom;">
                            <b><?= esc($dataSiswa['wali_kelas'] ?? '...........................................') ?></b>
                        </td>
                    </tr>
                </table>

                <table class="signature-kamad" style="margin-top: 10px;">
                    <tr>
                        <td>Mengetahui,<br>Kepala Madrasah</td>
                    </tr>
                    <tr>
                        <td style="height: 70px; vertical-align: bottom;">
                            <b>Yana Purnama, S.Pd.</b><br>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
    </div> <!-- END BUNGKUS AREA PDF -->

    <!-- SCRIPT UNTUK PROSES DIRECT DOWNLOAD PDF -->
    <script>
        function unduhPDF() {
            // Ubah tombol jadi status loading
            const btn = document.getElementById('btnDownloadPdf');
            const teksAsli = btn.innerHTML;
            btn.innerHTML = '⏳ Menyimpan PDF...';
            btn.disabled = true;

            // Targetkan area pembungkus
            const elemen = document.getElementById('area-pdf');
            
            // Tambahkan class khusus saat render PDF
            elemen.classList.add('mode-download');

            // Scroll manual ke atas agar tidak ada area terpotong akibat user yang scroll mouse
            window.scrollTo(0, 0);

            // Konfigurasi PDF
            const namaSiswa = "<?= esc(str_replace(' ', '_', $dataSiswa['name'] ?? 'Siswa')) ?>";
            const opt = {
                margin:       0, 
                filename:     'Rapor_Perkembangan_' + namaSiswa + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true,
                    scrollY: 0
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: 'css' }
            };

            // Proses Generate PDF
            html2pdf().set(opt).from(elemen).save().then(function() {
                // Kembalikan tombol dan layout ke semula
                elemen.classList.remove('mode-download');
                btn.innerHTML = teksAsli;
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>