<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Berjalan - Portal Siswa</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Open Sans', sans-serif; color: #333; }
        .top-nav-container { max-width: 900px; margin: 20px auto 0 auto; padding: 0 10px; }
        .rapor-wrapper { background-color: #e3f2fd; padding: 30px; border-radius: 10px; margin: 20px auto 50px auto; max-width: 960px; }
        .rapor-container { max-width: 900px; margin: 0 auto; background: #ffffff; padding: 45px 50px; box-shadow: 0 10px 25px rgba(25, 118, 210, 0.15); border-top: 8px solid #1976d2; border-radius: 8px; }
        .header-sekolah { text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 20px; margin-bottom: 30px; }
        .header-sekolah h2 { font-family: 'Merriweather', serif; color: #15202b; margin: 0 0 8px 0; font-size: 26px; text-transform: uppercase; letter-spacing: 1px; }
        .header-sekolah p { margin: 0; font-size: 15px; color: #535c5d; }
        .identitas-box { display: flex; justify-content: space-between; margin-bottom: 35px; font-size: 14px; background-color: #f4f9fd; padding: 20px; border-radius: 6px; border-left: 4px solid #0d47a1; border-right: 1px solid #ddd; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .identitas-table td { padding: 5px 10px 5px 0; }
        .identitas-table td:first-child { font-weight: 700; width: 130px; color: #15202b; }
        .section-title { font-family: 'Merriweather', serif; font-size: 16px; background-color: #0d47a1; color: #ffffff; padding: 10px 15px; margin: 30px 0 15px 0; font-weight: bold; border-radius: 4px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        table.data-table th, table.data-table td { border: 1px solid #222222; padding: 10px 8px; vertical-align: middle; }
        table.data-table th { background-color: #eaf3fa; color: #15202b; text-align: center; font-weight: 700; border-bottom: 3px solid #1976d2; }
        
        .col-aspek { width: 35%; font-weight: 600; text-align: left; }
        .col-angka { text-align: center; width: 8%; }
        .col-rata { text-align: center; font-weight: bold; background-color: #e8f4fd; color: #0d47a1; width: 12%; }

        .catatan-box-container { display: flex; gap: 20px; }
        .catatan-box { flex: 1; border: 1px solid #888888; background-color: #ffffff; border-radius: 6px; padding: 20px; border-top: 4px solid #1976d2; }
        .catatan-box h4 { margin-top: 0; font-family: 'Merriweather', serif; font-size: 15px; color: #15202b; border-bottom: 1px solid #cccccc; padding-bottom: 10px; margin-bottom: 15px; }
        ul.list-catatan { margin: 0; padding-left: 20px; color: #212529; }
        ul.list-catatan li { margin-bottom: 8px; line-height: 1.5; }

        @media print {
            body { background: none; padding: 0; }
            .top-nav-container, .rapor-wrapper { background: none; padding: 0; margin: 0; }
            .top-nav-container { display: none !important; } 
            .rapor-container { box-shadow: none; border-top: 8px solid #1976d2 !important; padding: 0; }
            .section-title { background-color: #0d47a1 !important; color: #fff !important; }
            table.data-table th, table.data-table td { border: 1px solid #000000 !important; } 
        }
    </style>
</head>
<body>

<?php 
    $fmt = function($angka, $b) use ($bulanAktif) { 
        if (!in_array($b, $bulanAktif)) return ''; 
        return $angka !== null ? str_replace('.', ',', (float)$angka) : '-'; 
    };
    
    $semuaBulan = (strtolower($semester) === 'ganjil') ? ['07', '08', '09', '10', '11', '12'] : ['01', '02', '03', '04', '05', '06'];
?>

<!-- NAVIGASI ATAS KHUSUS SISWA -->
<div class="top-nav-container d-flex justify-content-between align-items-center">
    <h5 class="fw-bold text-primary mb-0"><i class="fas id-card me-2"></i> Portal Rapor Pribadi Siswa</h5>
    <div>
        <a href="<?= base_url('/') ?>" class="btn btn-primary btn-sm me-2 shadow-sm">
            <i class="fas fa-home me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="rapor-wrapper">
    <div class="rapor-container">

        <div class="header-sekolah">
            <h2>Laporan Perkembangan Murid</h2>
        </div>

        <div class="identitas-box">
            <table class="identitas-table">
                <tr><td>Nama Lengkap</td><td>: <?= esc($dataSiswa['name']) ?></td></tr>
                <tr><td>NIS / NISN</td><td>: <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></td></tr>
                <tr><td>Wali Kelas</td><td>: <?= esc($dataSiswa['wali_kelas'] ?? '-') ?></td></tr>
            </table>
            <table class="identitas-table">
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
                    <th class="col-aspek" style="vertical-align: middle;">Mata Pelajaran</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata" style="vertical-align: middle;">Rata-rata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matrixSumatif)): ?>
                    <tr><td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">Belum ada data nilai mata pelajaran.</td></tr>
                <?php else: ?>
                    <?php foreach ($matrixSumatif as $mapel): ?>
                        <tr>
                            <td class="col-aspek" style="font-weight: 600;"><?= esc($mapel['nama_mapel']) ?></td>
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
        <div class="section-title">B. Perkembangan Al-Qur'an</div>
        <table class="data-table">
            <thead> 
                <tr> 
                    <th class="col-aspek" style="vertical-align: middle;">Aspek Penilaian</th> 
                    <?php foreach ($semuaBulan as $b): ?> 
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th> 
                    <?php endforeach; ?> 
                    <th class="col-rata" style="vertical-align: middle;">Rata-rata</th> 
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
                        <?php 
                            if ($isKelas7 && strtolower($aspek) === 'tahfidz') {
                                continue;
                            }
                        ?>
                        <tr>
                            <td class="col-aspek" style="font-weight: 600;"><?= esc($aspek) ?></td>
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
        <div class="section-title">C. Rekapitulasi Kehadiran</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Keterangan</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $labelAbsen = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa (Tanpa Keterangan)', 'T' => 'Frekuensi Keterlambatan', 'M' => 'Akumulasi Menit Terlambat'];
                    foreach ($labelAbsen as $kode => $label):
                ?>
                <tr>
                    <td class="col-aspek" style="font-weight: 600;"><?= $label ?></td>
                    <?php foreach ($semuaBulan as $b): ?>
                        <td class="col-angka">
                            <?php 
                                if (!in_array($b, $bulanAktif)) {
                                    echo '';
                                } else {
                                    $val = $matrixAbsen[$kode][$b] ?? '-';
                                    if ($kode === 'M' && ($val === '-' || $val === '' || $val === null)) {
                                        echo '0';
                                    } else {
                                        echo esc($val);
                                    }
                                }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="col-rata">
                        <?php 
                            $valTotal = $totalAbsen[$kode] ?? '-';
                            if ($kode === 'M' && ($valTotal === '-' || $valTotal === '' || $valTotal === null)) {
                                echo '0';
                            } else {
                                echo esc($valTotal);
                            }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- D. SIKAP & KEPATUHAN -->
        <div class="section-title">D. Catatan Kepatuhan (Jumlah Kejadian/Pelanggaran)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Indikator Kepatuhan</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Total Kasus</th>
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
                    <td class="col-aspek" style="font-weight: bold; font-style: italic;">Rincian Pelanggaran:</td>
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 0.9em; padding: 6px 12px; line-height: 1.5; color: #444; text-align: left;">
                        <?= $keteranganPelanggaran ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- E. PERKEMBANGAN SIKAP SPIRITUAL -->
        <div class="section-title">E. Perkembangan Sikap Spiritual</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Sikap Spiritual yang Diamati</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Predikat</th>
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
                    <td class="col-aspek" style="font-weight: bold; font-style: italic;">Rincian Catatan Spiritual:</td>
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 0.9em; padding: 6px 12px; line-height: 1.5; color: #444; text-align: left;">
                        <?= $spiritual['keterangan_rincian'] ?? '-' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- F. PERKEMBANGAN SIKAP SOSIAL -->
        <div class="section-title">F. Perkembangan Sikap Sosial</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Sikap Sosial yang Diamati</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Predikat</th>
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
                    <td class="col-aspek" style="font-weight: bold; font-style: italic;">Rincian Catatan Sosial:</td>
                    <td colspan="<?= count($semuaBulan) + 1 ?>" style="font-size: 0.9em; padding: 6px 12px; line-height: 1.5; color: #444; text-align: left;">
                        <?= $sosial['keterangan_rincian'] ?? '-' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="keterangan-predikat" style="margin-top: 15px; font-size: 14px;">
        <strong>Keterangan Penilaian Karakter:</strong><br>
        A = Tidak pernah melanggar ketentuan<br>
        B = 1 - 2 kali melanggar ketentuan<br>
        C = 3 - 4 kali melanggar ketentuan<br>
        D = > 4 kali melanggar ketentuan
        </div>

        <!-- G. EKSTRAKURIKULER, PRAMUKA & PEMINATAN -->
        <div class="section-title">G. Ekstrakurikuler, Pramuka & Peminatan</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Kegiatan / Ekstrakurikuler</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= $namaBulanIndo[$b] ?? $b ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Predikat</th>
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
        <div class="keterangan-predikat" style="margin-top: 10px; font-size: 0.85em; line-height: 1.5; color: #333;">
            <strong>Keterangan Predikat Nilai:</strong><br>
            A = Sangat Baik (90 - 100)<br>
            B = Baik (80 - 89)<br>
            C = Cukup (70 - 79)<br>
            D = Kurang (&lt; 69)
        </div>

        <!-- H. ANEKDOT & PRESTASI -->
        <div class="section-title">H. Catatan Anekdot & Prestasi</div>
        <div class="catatan-box-container">
            <div class="catatan-box">
                <h4>Prestasi / Penghargaan</h4>
                <?php if (!empty($prestasi)): ?>
                    <ul class="list-catatan">
                        <?php foreach ($prestasi as $p): ?>
                            <li><strong><?= esc($p['nama_prestasi']) ?></strong>: <?= esc($p['keterangan']) ?> <em>(<?= date('d/m/Y', strtotime($p['created_at'])) ?>)</em></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small">Belum ada catatan prestasi di semester ini.</p>
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
                    <p class="text-muted small">Belum ada catatan khusus di semester ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- I. REKAPITULASI ASPEK YAUMIYAH -->
        <div class="section-title">I. Rekapitulasi Aspek Yaumiyah</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Aspek Yaumiyah</th>
                    <?php foreach ($semuaBulan as $b): ?>
                        <th class="col-angka"><?= esc($namaBulanIndo[$b] ?? $b) ?></th>
                    <?php endforeach; ?>
                    <th class="col-rata">Rata-rata</th>
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
                        $totalSatuBaris = 0;
                        $jumlahBulanAktif = 0;
                ?>
                    <tr>
                        <td class="col-aspek" style="padding-left: 20px; font-weight: normal;"><?= esc($label) ?></td>
                        <?php foreach ($semuaBulan as $b): 
                            if (!in_array($b, $bulanAktif)) {
                                echo '<td class="col-angka"></td>'; 
                            } else {
                                $nilaiPersen = isset($matrixYaumiyah[$key][$b]) ? (float)$matrixYaumiyah[$key][$b] : 0;
                                if ($nilaiPersen > 0) {
                                    $totalSatuBaris += $nilaiPersen;
                                    $jumlahBulanAktif++;
                                }
                                $tampilanNilai = $nilaiPersen > 0 ? number_format($nilaiPersen, 0) . '%' : '0%';
                                echo '<td class="col-angka">' . $tampilanNilai . '</td>';
                            }
                        ?>
                        <?php endforeach; ?>
                        
                        <?php $rataRata = $jumlahBulanAktif > 0 ? ($totalSatuBaris / $jumlahBulanAktif) : 0; ?>
                        <td class="col-rata">
                            <strong><?= $rataRata > 0 ? number_format($rataRata, 0) . '%' : '0%' ?></strong>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                else:
                ?>
                    <tr>
                        <td colspan="<?= count($semuaBulan) + 2 ?>" class="text-center text-muted">
                            Belum ada data rekapitulasi yaumiyah di semester ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div style="margin-top: 5px; margin-bottom: 25px; font-size: 0.85em; color: #555;">
            <em>* Nilai yang ditampilkan adalah persentase capaian (%) dari target berdasarkan jumlah hari efektif sekolah per bulan.</em>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>