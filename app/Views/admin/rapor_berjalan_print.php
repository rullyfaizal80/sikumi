<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rapor - <?= esc($dataSiswa['name'] ?? 'Siswa') ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; }
        body { background-color: #525659; font-family: 'Open Sans', sans-serif; color: #333; margin: 0; padding: 20px 0; }
        
        /* DESAIN KERTAS (Tampil seperti halaman di layar) */
        .a4-paper { 
            width: 210mm; 
            min-height: 297mm; /* Tinggi minimal 1 halaman A4 */
            margin: 0 auto 30px auto; 
            background: #ffffff; 
            padding: 15mm; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.4); 
            border-top: 8px solid #1976d2; 
            border-radius: 8px; 
            position: relative;
        }

        /* Saat dicetak, hilangkan shadow dan margin luar */
        @page { size: A4 portrait; margin: 10mm; }
        @media print {
            body { background: #fff; padding: 0; }
            .a4-paper { width: 100%; min-height: auto; margin: 0; padding: 0 5mm; box-shadow: none; border-top: 8px solid #1976d2 !important; border-radius: 0; page-break-after: always; }
            .a4-paper:last-child { page-break-after: auto; }
            .print-actions-wrapper { display: none !important; }
        }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        /* TAMPILAN COVER (Ditengahkan Vertikal) */
        .cover-wrapper { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100%; 
            min-height: 260mm; /* Menjamin posisi selalu di tengah kertas */
            text-align: center; 
        }
        .cover-title { font-family: 'Merriweather', serif; font-size: 26px; color: #15202b; font-weight: bold; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 1px; }
        .cover-logo { margin-bottom: 50px; }
        .cover-logo img { width: 160px; height: auto; }
        .cover-student-box { background-color: #f4f9fd; border-left: 5px solid #0d47a1; border: right: 1px solid #ddd; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 20px; width: 85%; margin: 0 auto 40px auto; border-radius: 6px; }
        .cover-student-box h3 { margin: 0 0 10px 0; font-family: 'Merriweather', serif; color: #15202b; font-size: 22px; text-transform: uppercase; }
        .cover-student-box p { margin: 0; font-size: 16px; color: #535c5d; font-weight: 600; }
        .cover-footer { margin-top: auto; font-size: 16px; font-weight: bold; text-transform: uppercase; color: #15202b; line-height: 1.6; }

        /* HEADER ISI RAPOR */
        .header-sekolah { text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 15px; margin-bottom: 20px; }
        .header-sekolah h2 { font-family: 'Merriweather', serif; color: #15202b; margin: 0 0 5px 0; font-size: 22px; text-transform: uppercase; }
        .identitas-box { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; background-color: #f4f9fd; padding: 15px; border-radius: 6px; border-left: 4px solid #0d47a1; border: 1px solid #ddd; }
        .identitas-table td { padding: 4px 8px 4px 0; vertical-align: top; }
        .identitas-table td:first-child { font-weight: 700; width: 110px; color: #15202b; }
        .section-title { font-family: 'Merriweather', serif; font-size: 14px; background-color: #0d47a1; color: #ffffff; padding: 8px 12px; margin: 25px 0 10px 0; font-weight: bold; border-radius: 4px; }

        /* TABEL DATA */
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px; }
        table.data-table th, table.data-table td { border: 1px solid #222222; padding: 6px; vertical-align: middle; }
        table.data-table th { background-color: #eaf3fa !important; color: #15202b; text-align: center; font-weight: 700; border-bottom: 2px solid #1976d2; }
        .col-aspek { font-weight: 600; text-align: left; }
        .col-angka { text-align: center; }
        .col-rata { text-align: center; font-weight: bold; background-color: #e8f4fd !important; color: #0d47a1; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        .avoid-break { page-break-inside: avoid; }

        /* CATATAN BOX */
        .catatan-box-container { display: flex; gap: 15px; margin-bottom: 15px; }
        .catatan-box { flex: 1; border: 1px solid #888888; background-color: #ffffff; border-radius: 6px; padding: 15px; border-top: 4px solid #1976d2; }
        .catatan-box h4 { margin-top: 0; font-family: 'Merriweather', serif; font-size: 13px; color: #15202b; border-bottom: 1px solid #cccccc; padding-bottom: 8px; margin-bottom: 10px; }
        ul.list-catatan { margin: 0; padding-left: 20px; color: #212529; font-size: 12px; }

        /* TANDA TANGAN (Dikembalikan) */
        .signature-section { width: 100%; margin-top: 40px; font-size: 13px; page-break-inside: avoid; }
        .signature-table { width: 100%; text-align: center; border: none; margin-bottom: 15px; }
        .signature-table td { border: none; padding: 5px; width: 50%; vertical-align: top; }
        .signature-kamad { width: 100%; text-align: center; border: none; margin-top: 20px; }
        .signature-kamad td { border: none; }

        /* KONTROL MENGAMBANG */
        .print-actions-wrapper { position: fixed; top: 20px; right: 20px; z-index: 1000; display: flex; gap: 10px; }
        .btn-print { background: #1976d2; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-family: 'Open Sans', sans-serif; }
        .btn-close { background: #dc3545; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-family: 'Open Sans', sans-serif; }
    </style>
</head>
<body>

    <div class="print-actions-wrapper">
        <button class="btn-print" onclick="window.print()">🖨️ Cetak PDF</button>
        <button class="btn-close" onclick="window.close()">🆇 Tutup</button>
    </div>

    <?php 
        // 1. FORMAT TANGGAL TITI MANGSA
        $rawDate = $_GET['titi_mangsa'] ?? date('Y-m-d');
        $bulanIndoFull = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
        $parts = explode('-', $rawDate);
        if(count($parts) == 3) {
            $tglFormatted = $parts[2] . ' ' . $bulanIndoFull[$parts[1]] . ' ' . $parts[0];
            // Format Output: Bandung, 14 September 2026
            $titiMangsaStr = 'Bandung, ' . $tglFormatted; 
        } else {
            $titiMangsaStr = 'Bandung, ................................';
        }

        // 2. HELPER RAPOR
        $namaBulanIndo = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
        $semuaBulan = (strtolower($semester) === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
        $fmt = function($angka, $b) use ($bulanAktif) { 
            if (!in_array($b, $bulanAktif)) return ''; 
            return $angka !== null ? str_replace('.', ',', (float)$angka) : '-'; 
        };
    ?>

    <!-- KERTAS 1 : COVER RAPOR -->
    <div class="a4-paper">
        <div class="cover-wrapper">
            <br><br><br><br>
            <div class="cover-title">
                Laporan Perkembangan Murid<br>
            </div>
            <br><br><br><br><br><br>
            <div class="cover-logo">
                <img src="<?= base_url('assets/img/logo_kaldik2.png') ?>" alt="Logo Sekolah" onerror="this.style.display='none'">
            </div>
            <br><br><br><br><br><br><br><br>
            <div class="cover-student-box">
                <p style="font-size: 13px; text-transform: uppercase; margin-bottom: 5px;">Nama Peserta Didik</p>
                <h3><?= esc($dataSiswa['name']) ?></h3>
                <p>NIS/NISN : <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></p>
            </div>

            <div class="cover-footer">
                Kementerian Agama Republik Indonesia<br>
                MTsS Miftahul Huda<br>
                Tahun Ajaran <?= esc($tahun) ?>/<?= esc($tahun + 1) ?>
            </div>
        </div>
    </div>

    <!-- KERTAS 2+ : ISI RAPOR -->
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
        <div class="section-title">A. Perkembangan Akademik (Nilai Sumatif)</div>
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
        <div class="section-title avoid-break">B. Perkembangan Al-Qur'an</div>
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
        <div class="section-title avoid-break">C. Rekapitulasi Kehadiran</div>
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

        <!-- D. SIKAP & KEPATUHAN -->
        <div class="section-title avoid-break">D. Catatan Kepatuhan (Jumlah Kejadian/Pelanggaran)</div>
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
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 11px; padding: 6px 8px; line-height: 1.4; text-align: left;">
                        <?= $keteranganPelanggaran ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- E. PERKEMBANGAN SIKAP SPIRITUAL -->
        <div class="section-title avoid-break">E. Perkembangan Sikap Spiritual</div>
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
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 11px; padding: 6px 8px; line-height: 1.4; text-align: left;">
                        <?= $spiritual['keterangan_rincian'] ?? '-' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- F. PERKEMBANGAN SIKAP SOSIAL -->
        <div class="section-title avoid-break">F. Perkembangan Sikap Sosial</div>
        <table class="data-table">
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
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 11px; padding: 6px 8px; line-height: 1.4; text-align: left;">
                        <?= $sosial['keterangan_rincian'] ?? '-' ?>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div style="font-size: 11px; margin-bottom: 15px;">
            <strong>Keterangan Penilaian Karakter:</strong> A = Tidak pernah melanggar, B = 1-2 kali melanggar, C = 3-4 kali melanggar, D = > 4 kali melanggar
        </div>

        <!-- G. EKSTRAKURIKULER, PRAMUKA & PEMINATAN -->
        <div class="section-title avoid-break">G. Ekstrakurikuler, Pramuka & Peminatan</div>
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

        <!-- H. ANEKDOT & PRESTASI -->
        <div class="section-title avoid-break">H. Catatan Anekdot & Prestasi</div>
        <div class="catatan-box-container avoid-break">
            <div class="catatan-box">
                <h4>Prestasi / Penghargaan</h4>
                <?php if (!empty($prestasi)): ?>
                    <ul class="list-catatan">
                        <?php foreach ($prestasi as $p): ?>
                            <li><strong><?= esc($p['nama_prestasi']) ?></strong>: <?= esc($p['keterangan']) ?> <em>(<?= date('d/m/Y', strtotime($p['created_at'])) ?>)</em></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 11px; color: #666;">Belum ada catatan prestasi di semester ini.</p>
                <?php endif; ?>
            </div>
            <div class="catatan-box">
                <h4>Catatan Khusus (Anekdot)</h4>
                <?php if (!empty($anekdot)): ?>
                    <ul class="list-catatan">
                        <?php foreach ($anekdot as $a): ?>
                            <li><?= esc($a['kejadian']) ?> <em>(<?= date('d/m/Y', strtotime($a['tanggal'])) ?>)</em></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="font-size: 11px; color: #666;">Belum ada catatan khusus di semester ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- I. REKAPITULASI ASPEK YAUMIYAH -->
        <div class="section-title avoid-break">I. Rekapitulasi Aspek Yaumiyah</div>
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
                        <td class="col-aspek" style="font-weight: normal; padding-left: 10px;"><?= esc($label) ?></td>
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
        <div style="font-size: 11px; color: #555; margin-bottom: 20px;">
            <em>* Nilai yang ditampilkan adalah persentase capaian (%) dari target berdasarkan jumlah hari efektif sekolah per bulan.</em>
        </div>
        
        <!-- BLOK TANDA TANGAN -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>Mengetahui,<br>Orang Tua / Wali Murid</td>
                    <td><?= esc($titiMangsaStr) ?><br>Wali Kelas</td>
                </tr>
                <tr>
                    <td style="height: 80px; vertical-align: bottom;">
                        <b>( ........................................... )</b>
                    </td>
                    <td style="height: 80px; vertical-align: bottom;">
                        <b><?= esc($dataSiswa['wali_kelas'] ?? '...........................................') ?></b>
                    </td>
                </tr>
            </table>

            <table class="signature-kamad">
                <tr>
                    <td>Mengetahui,<br>Kepala Sekolah</td>
                </tr>
                <tr>
                    <td style="height: 80px; vertical-align: bottom;">
                        <b><?= esc($kepalaNama ?? '...........................................') ?></b><br>
                        <span style="font-size: 11px;">NIP/NPK. <?= esc($kepalaNpk ?? '................................') ?></span>
                    </td>
                </tr>
            </table>
        </div>

    </div>

</body>
</html>