<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perkembangan - <?= esc($dataSiswa['name']) ?></title>
    <!-- Menggunakan font eksternal untuk kesan formal dan elegan -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Open Sans', sans-serif;
        color: #1c2833; 
        background-color: #e3f2fd; /* Warna dasar diubah menjadi biru muda soft */
        margin: 0;
        padding: 20px;
    }
    .rapor-container {
        max-width: 900px;
        margin: 0 auto;
        background: #ffffff;
        padding: 45px 50px;
        box-shadow: 0 10px 25px rgba(25, 118, 210, 0.15); /* Bayangan disesuaikan ke arah biru */
        border-top: 8px solid #1976d2; /* Aksen Biru Utama */
        border-radius: 8px;
    }
    .header-sekolah {
        text-align: center;
        border-bottom: 2px solid #1976d2; /* Garis biru utama */
        padding-bottom: 20px;
        margin-bottom: 30px;
    }
    .header-sekolah h2 {
        font-family: 'Merriweather', serif;
        color: #15202b; 
        margin: 0 0 8px 0;
        font-size: 26px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .header-sekolah p { 
        margin: 0; 
        font-size: 15px; 
        color: #535c5d; 
    }
    
    .identitas-box {
        display: flex;
        justify-content: space-between;
        margin-bottom: 35px;
        font-size: 14px;
        background-color: #f4f9fd; /* Background identitas biru sangat pucat */
        padding: 20px;
        border-radius: 6px;
        border-left: 4px solid #0d47a1; /* Border kiri biru navy */
        border-right: 1px solid #ddd;
        border-top: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .identitas-table td { padding: 5px 10px 5px 0; }
    .identitas-table td:first-child { font-weight: 700; width: 130px; color: #15202b; }
    
    .section-title {
        font-family: 'Merriweather', serif;
        font-size: 16px;
        background-color: #0d47a1; /* Background Judul Biru Navy Pejal */
        color: #ffffff;           
        padding: 10px 15px;
        margin: 30px 0 15px 0;
        font-weight: bold;
        border-radius: 4px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 14px;
    }
    table.data-table th, table.data-table td {
        border: 1px solid #222222; /* Garis kisi tabel tetap hitam/abu pekat agar tegas */
        padding: 10px 12px;
        vertical-align: middle;
    }
    table.data-table th {
        background-color: #eaf3fa; /* Background header tabel biru super pucat */
        color: #15202b; 
        text-align: center;
        font-weight: 700;
        border-bottom: 3px solid #1976d2; /* Border bawah header biru tebal */
    }
    .col-aspek { width: 35%; font-weight: 600; }
    .col-angka { text-align: center; width: 10%; }
    .col-rata { 
        text-align: center; 
        font-weight: bold; 
        background-color: #e8f4fd; /* Sentuhan biru muda untuk kolom rata-rata */
        color: #0d47a1; /* Teks nilai rata-rata biru navy pekat */
        width: 12%; 
    }
    
    .text-center { text-align: center; }
    .text-muted { color: #707b7c; font-style: italic; }
    
    .catatan-box-container {
        display: flex;
        gap: 20px;
    }
    .catatan-box {
        flex: 1; 
        border: 1px solid #888888; 
        background-color: #ffffff;
        border-radius: 6px;
        padding: 20px;
        border-top: 4px solid #1976d2; /* Top border biru utama */
    }
    .catatan-box h4 {
        margin-top: 0; 
        font-family: 'Merriweather', serif; 
        font-size: 15px; 
        color: #15202b; 
        border-bottom: 1px solid #cccccc; 
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    ul.list-catatan { margin: 0; padding-left: 20px; color: #212529; }
    ul.list-catatan li { margin-bottom: 8px; line-height: 1.5; }

    @media print {
        body { background: none; padding: 0; }
        .rapor-container { box-shadow: none; border-top: 8px solid #1976d2 !important; padding: 0; }
        .section-title { background-color: #0d47a1 !important; color: #fff !important; }
        table.data-table th, table.data-table td { border: 1px solid #000000 !important; } 
        table.data-table th { background-color: #eaf3fa !important; border-bottom: 3px solid #1976d2 !important; }
    }
</style>
</head>
<body>

<div class="rapor-container">
    <!-- HEADER -->
    <div class="header-sekolah">
        <h2>Laporan Perkembangan Peserta Didik</h2>
        <p>Buku Catatan Akademik, Karakter, dan Kepatuhan - Terintegrasi</p>
    </div>

    <!-- IDENTITAS -->
    <div class="identitas-box">
        <table class="identitas-table">
            <tr><td>Nama Lengkap</td><td>: <?= esc($dataSiswa['name']) ?></td></tr>
            <tr><td>NIS / NISN</td><td>: <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></td></tr>
            <tr><td>Jenis Kelamin</td><td>: <?= ($dataSiswa['gender'] == 'L') ? 'Laki-laki' : (($dataSiswa['gender'] == 'P') ? 'Perempuan' : '-') ?></td></tr>
        </table>
        <table class="identitas-table">
            <tr><td>Kelas</td><td>: <?= esc($dataSiswa['kelas']) ?></td></tr>
            <tr><td>Semester</td><td>: <?= $semester ?></td></tr>
            <tr><td>Tahun Ajaran</td><td>: <?= $tahun ?>/<?= $tahun + 1 ?></td></tr>
        </table>
    </div>

    <?php 
        // Helper fungsi untuk format angka (menghilangkan .00 di belakang)
        $fmt = function($angka) {
            return $angka != null ? str_replace('.', ',', (float)$angka) : '-';
        };
    ?>

    <!-- 1. PERKEMBANGAN AKADEMIK (SUMATIF) -->
    <div class="section-title">A. Perkembangan Akademik (Nilai Sumatif)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-aspek">Mata Pelajaran</th>
                <th colspan="<?= count($bulanAktif) ?>">Bulan Penilaian</th>
                <th rowspan="2" class="col-rata">Rata-rata<br>Semester</th>
            </tr>
            <tr>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matrixSumatif)): ?>
                <tr><td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">Belum ada data nilai mata pelajaran.</td></tr>
            <?php else: ?>
                <?php foreach ($matrixSumatif as $mapel): ?>
                    <tr>
                        <td class="col-aspek"><?= esc($mapel['nama_mapel']) ?></td>
                        <?php foreach ($bulanAktif as $b): ?>
                            <td class="col-angka"><?= $fmt($mapel['nilai'][$b]) ?></td>
                        <?php endforeach; ?>
                        <td class="col-rata">
                            <?= $mapel['count'] > 0 ? $fmt(round($mapel['total'] / $mapel['count'], 2)) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- PERKEMBANGAN AL-QUR'AN -->
    <div class="section-title">B. Perkembangan Al-Qur'an</div>
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-aspek">Aspek Penilaian</th>
                <th colspan="<?= count($bulanAktif) ?>">Bulan Penilaian</th>
                <th rowspan="2" class="col-rata">Rata-rata<br>Semester</th>
            </tr>
            <tr>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matrixQuran)): ?>
                <tr><td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">Belum ada data nilai Al-Qur'an.</td></tr>
            <?php else: ?>
                <?php foreach ($matrixQuran as $aspek => $dataQuran): ?>
                    <tr>
                        <td class="col-aspek"><?= esc($aspek) ?></td>
                        <?php foreach ($bulanAktif as $b): ?>
                            <td class="col-angka"><?= $fmt($dataQuran['nilai'][$b]) ?></td>
                        <?php endforeach; ?>
                        <td class="col-rata">
                            <?= $dataQuran['count'] > 0 ? $fmt(round($dataQuran['total'] / $dataQuran['count'], 2)) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 2. KEHADIRAN (ABSENSI) -->
    <div class="section-title">B. Rekapitulasi Kehadiran</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-aspek">Keterangan</th>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
                <th class="col-rata">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $labelAbsen = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa (Tanpa Keterangan)', 'T' => 'Terlambat'];
                foreach ($labelAbsen as $kode => $label):
            ?>
            <tr>
                <td class="col-aspek"><?= $label ?></td>
                <?php foreach ($bulanAktif as $b): ?>
                    <td class="col-angka"><?= $matrixAbsen[$kode][$b] ?: '-' ?></td>
                <?php endforeach; ?>
                <td class="col-rata"><?= $totalAbsen[$kode] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 3. SIKAP & KEPATUHAN (PELANGGARAN) -->
    <div class="section-title">C. Catatan Kepatuhan (Jumlah Kejadian/Pelanggaran)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-aspek">Indikator Kepatuhan</th>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
                <th class="col-rata">Total Kasus</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $labelPatuh = [
                    'seragam' => 'Ketidaksesuaian Seragam', 'atribut' => 'Atribut Tidak Lengkap', 
                    'bersih_diri' => 'Kurang Menjaga Kebersihan Diri', 'terlambat' => 'Keterlambatan Hadir', 
                    'aturan_kelas' => 'Melanggar Peraturan Kelas', 'masjid' => 'Melanggar Ketertiban Masjid'
                ];
                foreach ($labelPatuh as $k => $label):
            ?>
            <tr>
                <td class="col-aspek"><?= $label ?></td>
                <?php foreach ($bulanAktif as $b): ?>
                    <td class="col-angka"><?= $kepatuhan['matrix'][$k][$b] ?: '-' ?></td>
                <?php endforeach; ?>
                <td class="col-rata"><?= $kepatuhan['totals'][$k] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 4. PERKEMBANGAN SIKAP SPIRITUAL & SOSIAL -->
    <div class="section-title">D. Perkembangan Sikap Spiritual & Sosial (Jumlah Catatan Positif)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-aspek">Sikap yang Diamati</th>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
                <th class="col-rata">Total Catatan</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="<?= count($bulanAktif) + 2 ?>" style="background-color: #fdfefe; font-weight: bold; padding-left: 15px; color: #4a6375;">Sikap Spiritual</td></tr>
            <?php
                $labelSpiritual = ['berdoa' => 'Membiasakan Berdoa', 'kalimat_thoyibah' => 'Mengucapkan Kalimat Thoyibah', 'shalat' => 'Menjalankan Ibadah Shalat', 'salam' => 'Membudayakan Salam', 'syukur' => 'Menunjukkan Rasa Syukur', 'lingkungan' => 'Menjaga Lingkungan', 'toleransi' => 'Toleransi Beragama'];
                foreach ($labelSpiritual as $k => $label):
            ?>
            <tr>
                <td class="col-aspek" style="padding-left: 25px; font-weight: normal;">- <?= $label ?></td>
                <?php foreach ($bulanAktif as $b): ?>
                    <td class="col-angka"><?= $spiritual['matrix'][$k][$b] ?: '-' ?></td>
                <?php endforeach; ?>
                <td class="col-rata"><?= $spiritual['totals'][$k] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>

            <tr><td colspan="<?= count($bulanAktif) + 2 ?>" style="background-color: #fdfefe; font-weight: bold; padding-left: 15px; color: #4a6375;">Sikap Sosial</td></tr>
            <?php
                $labelSosial = ['disiplin' => 'Kedisiplinan', 'jujur' => 'Kejujuran', 'percaya_diri' => 'Kepercayaan Diri', 'santun' => 'Kesantunan', 'kerjasama' => 'Kerja Sama', 'tanggung_jawab' => 'Tanggung Jawab', 'adil' => 'Keadilan'];
                foreach ($labelSosial as $k => $label):
            ?>
            <tr>
                <td class="col-aspek" style="padding-left: 25px; font-weight: normal;">- <?= $label ?></td>
                <?php foreach ($bulanAktif as $b): ?>
                    <td class="col-angka"><?= $sosial['matrix'][$k][$b] ?: '-' ?></td>
                <?php endforeach; ?>
                <td class="col-rata"><?= $sosial['totals'][$k] ?: '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- EKSTRAKURIKULER & PRAMUKA -->
    <div class="section-title">F. Kegiatan Ekstrakurikuler (Pramuka & Peminatan)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-aspek">Nama Kegiatan</th>
                <?php foreach ($bulanAktif as $b): ?>
                    <th><?= $namaBulanIndo[$b] ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($matrixEskul)): ?>
                <tr><td colspan="<?= count($bulanAktif) + 1 ?>" class="text-center text-muted">Belum ada data Ekstrakurikuler yang diikuti.</td></tr>
            <?php else: ?>
                <?php foreach ($matrixEskul as $namaEskul => $dataEskul): ?>
                    <tr>
                        <td class="col-aspek" style="font-weight: 600; color: #1a252f;">
                            <?= esc($namaEskul) ?>
                            <?php if (strtolower($namaEskul) == 'pramuka'): ?>
                                <br><small style="color: #d35400; font-weight: normal;">*Wajib</small>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($bulanAktif as $b): ?>
                            <!-- Predikat biasanya tidak dirata-rata, langsung ditampilkan nilai/hurufnya -->
                            <td class="col-angka" style="font-weight: bold;"><?= esc($dataEskul[$b]) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 5. ANEKDOT & PRESTASI -->
    <div class="section-title">E. Catatan Anekdot & Prestasi</div>
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

</div>

</body>
</html>