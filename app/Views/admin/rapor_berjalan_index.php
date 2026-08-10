<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Berjalan - Panel Admin</title>
    
    <!-- CSS & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        /* ======================== CSS FILTER FORM ======================== */
        body { background-color: #f4f7f6; font-family: 'Open Sans', sans-serif; color: #333; }
        .filter-container { margin-top: 5vh; margin-bottom: 4vh; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); }
        .card-header-custom { background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%); color: white; border-radius: 12px 12px 0 0 !important; padding: 20px; }
        .form-label { font-weight: 600; color: #2c3e50; font-size: 14px; }
        
        /* ======================== CSS LAPORAN/RAPOR ======================== */
        .rapor-wrapper { background-color: #e3f2fd; padding: 30px; border-radius: 10px; margin-bottom: 50px; }
        .rapor-container { max-width: 900px; margin: 0 auto; background: #ffffff; padding: 45px 50px; box-shadow: 0 10px 25px rgba(25, 118, 210, 0.15); border-top: 8px solid #1976d2; border-radius: 8px; }
        .header-sekolah { text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 20px; margin-bottom: 30px; }
        .header-sekolah h2 { font-family: 'Merriweather', serif; color: #15202b; margin: 0 0 8px 0; font-size: 26px; text-transform: uppercase; letter-spacing: 1px; }
        .header-sekolah p { margin: 0; font-size: 15px; color: #535c5d; }
        .identitas-box { display: flex; justify-content: space-between; margin-bottom: 35px; font-size: 14px; background-color: #f4f9fd; padding: 20px; border-radius: 6px; border-left: 4px solid #0d47a1; border-right: 1px solid #ddd; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .identitas-table td { padding: 5px 10px 5px 0; }
        .identitas-table td:first-child { font-weight: 700; width: 130px; color: #15202b; }
        .section-title { font-family: 'Merriweather', serif; font-size: 16px; background-color: #0d47a1; color: #ffffff; padding: 10px 15px; margin: 30px 0 15px 0; font-weight: bold; border-radius: 4px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        table.data-table th, table.data-table td { border: 1px solid #222222; padding: 10px 12px; vertical-align: middle; }
        table.data-table th { background-color: #eaf3fa; color: #15202b; text-align: center; font-weight: 700; border-bottom: 3px solid #1976d2; }
        .col-aspek { width: 35%; font-weight: 600; }
        .col-angka { text-align: center; width: 10%; }
        .col-rata { text-align: center; font-weight: bold; background-color: #e8f4fd; color: #0d47a1; width: 12%; }
        .catatan-box-container { display: flex; gap: 20px; }
        .catatan-box { flex: 1; border: 1px solid #888888; background-color: #ffffff; border-radius: 6px; padding: 20px; border-top: 4px solid #1976d2; }
        .catatan-box h4 { margin-top: 0; font-family: 'Merriweather', serif; font-size: 15px; color: #15202b; border-bottom: 1px solid #cccccc; padding-bottom: 10px; margin-bottom: 15px; }
        ul.list-catatan { margin: 0; padding-left: 20px; color: #212529; }
        ul.list-catatan li { margin-bottom: 8px; line-height: 1.5; }

        @media print {
            body { background: none; padding: 0; }
            .filter-container, .rapor-wrapper { background: none; padding: 0; margin: 0; }
            /* Menyembunyikan Form Filter dan Tombol Cetak saat halaman di-print */
            .filter-container, .aksi-admin { display: none !important; } 
            .rapor-container { box-shadow: none; border-top: 8px solid #1976d2 !important; padding: 0; }
            .section-title { background-color: #0d47a1 !important; color: #fff !important; }
            table.data-table th, table.data-table td { border: 1px solid #000000 !important; } 
            table.data-table th { background-color: #eaf3fa !important; border-bottom: 3px solid #1976d2 !important; }
        }
    </style>
</head>
<body>

<!-- ========================================================================= -->
<!-- 1. BAGIAN FORM FILTER PENCARIAN -->
<!-- ========================================================================= -->
<div class="container filter-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header card-header-custom d-flex align-items-center">
                    <i class="fas fa-book-open fs-4 me-3"></i>
                    <h5 class="mb-0 fw-bold">Pencarian Rapor Berjalan Siswa</h5>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <div class="mb-4 text-muted" style="font-size: 15px;">
                        Silakan tentukan tahun ajaran, semester, dan kelas terlebih dahulu untuk memunculkan daftar siswa.
                    </div>

                    <!-- PENTING: action kosong ("") agar form dikirim ke halaman itu sendiri -->
                    <form action="" method="GET">
                        <div class="row g-4">
                            <div class="col-md-6 col-lg-3">
                                <label for="tahun" class="form-label">Tahun Ajaran</label>
                                <input type="number" id="tahun" name="tahun" class="form-control" value="<?= esc($tahun ?? date('Y')) ?>" required>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select id="semester" name="semester" class="form-select" required>
                                    <option value="ganjil" <?= strtolower($semester ?? 'ganjil') == 'ganjil' ? 'selected' : '' ?>>Ganjil</option>
                                    <option value="genap" <?= strtolower($semester ?? '') == 'genap' ? 'selected' : '' ?>>Genap</option>
                                </select>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <label for="rombel_id" class="form-label">Kelas (Rombel)</label>
                                <!-- PENTING: pastikan 'name="rombel_id"' ada agar tersimpan saat form disubmit -->
                                <select id="rombel_id" name="rombel_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php if (!empty($daftarRombel)): ?>
                                        <?php foreach ($daftarRombel as $rombel): ?>
                                            <option value="<?= $rombel['id'] ?>" <?= (($selected_rombel ?? '') == $rombel['id']) ? 'selected' : '' ?>>
                                                <?= esc($rombel['rombel_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <label for="student_id" class="form-label">Nama Siswa</label>
                                <select name="student_id" id="student_id" class="form-select" required>
                                    <option value="">-- Tunggu Kelas --</option>
                                </select>
                            </div>
                        </div>

                        <hr class="mt-4 mb-4 text-muted">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('/') ?>" class="btn btn-light border text-secondary px-4"><i class="fas fa-arrow-left me-2"></i> Dashboard</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" style="background-color: #1976d2;"><i class="fas fa-search me-2"></i> Tampilkan Rapor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. BAGIAN HALAMAN RAPOR (Hanya tampil jika ada Data Siswa yang dicari) -->
<!-- ========================================================================= -->
<?php if (!empty($dataSiswa)): ?>
<div class="rapor-wrapper">
    <div class="rapor-container">
        
        <!-- Aksi Admin -->
        <div class="aksi-admin d-flex justify-content-end mb-4">
            <button onclick="window.print()" class="btn btn-success fw-bold"><i class="fas fa-print me-2"></i> Cetak Dokumen Rapor</button>
        </div>

        <div class="header-sekolah">
            <h2>Laporan Perkembangan Murid</h2>
            <p>Buku Catatan Akademik, Karakter, dan Kepatuhan - Terintegrasi</p>
        </div>

        <div class="identitas-box">
            <table class="identitas-table">
                <tr><td>Nama Lengkap</td><td>: <?= esc($dataSiswa['name']) ?></td></tr>
                <tr><td>NIS / NISN</td><td>: <?= esc($dataSiswa['nis'] ?: '-') ?> / <?= esc($dataSiswa['nisn'] ?: '-') ?></td></tr>
                <tr><td>Wali Kelas</td><td>: <?= esc($dataSiswa['wali_kelas'] ?? '-') ?></td></tr>
            </table>
            <table class="identitas-table">
                <tr><td>Kelas</td><td>: <?= esc($dataSiswa['kelas']) ?></td></tr>
                <tr><td>Semester</td><td>: <?= esc($semester) ?></td></tr>
                <tr><td>Tahun Ajaran</td><td>: <?= esc($tahun) ?>/<?= esc($tahun + 1) ?></td></tr>
            </table>
        </div>
        
        <?php 
            $fmt = function($angka) { return $angka != null ? str_replace('.', ',', (float)$angka) : '-'; };
        ?>

        <!-- A. PERKEMBANGAN AKADEMIK -->
        <div class="section-title">A. Perkembangan Akademik (Nilai Sumatif)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30%; vertical-align: middle;">Mata Pelajaran</th>
                    <th colspan="<?= count($bulanAktif) ?>" style="border-bottom: 1px solid #1976d2;">Bulan Penilaian</th>
                    <th rowspan="2" style="width: 10%; vertical-align: middle;">Rata-rata<br>Semester</th>
                </tr>
                <tr>
                    <?php foreach ($bulanAktif as $b): ?><th style="width: 10%;"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matrixSumatif)): ?>
                    <tr><td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">Belum ada data nilai mata pelajaran.</td></tr>
                <?php else: ?>
                    <?php foreach ($matrixSumatif as $mapel): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= esc($mapel['nama_mapel']) ?></td>
                            <?php foreach ($bulanAktif as $b): ?><td class="text-center"><?= $fmt($mapel['nilai'][$b]) ?></td><?php endforeach; ?>
                            <td class="col-rata"><?= $mapel['count'] > 0 ? $fmt(round($mapel['total'] / $mapel['count'], 2)) : '-' ?></td>
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
                    <th rowspan="2" style="width: 30%; vertical-align: middle;">Aspek Penilaian</th>
                    <th colspan="<?= count($bulanAktif) ?>" style="border-bottom: 1px solid #1976d2;">Bulan Penilaian</th>
                    <th rowspan="2" style="width: 10%; vertical-align: middle;">Rata-rata<br>Semester</th>
                </tr>
                <tr>
                    <?php foreach ($bulanAktif as $b): ?><th style="width: 10%;"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matrixQuran)): ?>
                    <tr><td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">Belum ada data nilai Al-Qur'an.</td></tr>
                <?php else: ?>
                    <?php foreach ($matrixQuran as $aspek => $dataQuran): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= esc($aspek) ?></td>
                            <?php foreach ($bulanAktif as $b): ?><td class="text-center"><?= $fmt($dataQuran['nilai'][$b]) ?></td><?php endforeach; ?>
                            <td class="col-rata"><?= $dataQuran['count'] > 0 ? $fmt(round($dataQuran['total'] / $dataQuran['count'], 2)) : '-' ?></td>
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
                    <th style="width: 30%;">Keterangan</th>
                    <?php foreach ($bulanAktif as $b): ?><th style="width: 10%;"><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
                    <th style="width: 10%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $labelAbsen = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa (Tanpa Keterangan)', 'T' => 'Frekuensi Keterlambatan', 'M' => 'Akumulasi Menit Terlambat'];
                    foreach ($labelAbsen as $kode => $label):
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= $label ?></td>
                    <?php foreach ($bulanAktif as $b): ?><td class="text-center"><?= esc($matrixAbsen[$kode][$b] ?? '0') ?></td><?php endforeach; ?>
                    <td class="col-rata"><?= esc($totalAbsen[$kode] ?? '0') ?></td>
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
                    <?php foreach ($bulanAktif as $b): ?><th><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
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
                    <?php foreach ($bulanAktif as $b): ?><td class="col-angka"><?= $kepatuhan['matrix'][$k][$b] ?></td><?php endforeach; ?>
                    <td class="col-rata"><?= $kepatuhan['totals'][$k] ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background-color: #f9f9f9;">
                    <td class="col-aspek" style="font-weight: bold; font-style: italic;">Rincian Pelanggaran:</td>
                    <td colspan="<?= count($bulanAktif) + 1 ?>" style="font-size: 0.9em; padding: 6px 12px; line-height: 1.5; color: #444; text-align: left;">
                        <?= $keteranganPelanggaran ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- E. PERKEMBANGAN SIKAP SPIRITUAL & SOSIAL -->
        <div class="section-title">E. Perkembangan Sikap Spiritual & Sosial (Jumlah Catatan Positif)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Sikap yang Diamati</th>
                    <?php foreach ($bulanAktif as $b): ?><th><?= $namaBulanIndo[$b] ?></th><?php endforeach; ?>
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
                    <?php foreach ($bulanAktif as $b): ?><td class="col-angka"><?= $spiritual['matrix'][$k][$b] ?: '-' ?></td><?php endforeach; ?>
                   <td class="col-rata"><?= $spiritual['totals_predikat'][$k] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>

                <tr><td colspan="<?= count($bulanAktif) + 2 ?>" style="background-color: #fdfefe; font-weight: bold; padding-left: 15px; color: #4a6375;">Sikap Sosial</td></tr>
                <?php
                    $labelSosial = ['disiplin' => 'Kedisiplinan', 'jujur' => 'Kejujuran', 'percaya_diri' => 'Kepercayaan Diri', 'santun' => 'Kesantunan', 'kerjasama' => 'Kerja Sama', 'tanggung_jawab' => 'Tanggung Jawab', 'adil' => 'Keadilan'];
                    foreach ($labelSosial as $k => $label):
                ?>
                <tr>
                    <td class="col-aspek" style="padding-left: 25px; font-weight: normal;">- <?= $label ?></td>
                    <?php foreach ($bulanAktif as $b): ?><td class="col-angka"><?= $sosial['matrix'][$k][$b] ?: '-' ?></td><?php endforeach; ?>
                    <td class="col-rata"><?= $sosial['totals_predikat'][$k] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="keterangan-predikat" style="margin-top: 15px; font-size: 14px;">
        <strong>Keterangan Penilaian Karakter:</strong><br>
        A = Tidak pernah melanggar ketentuan<br>
        B = 1 - 2 kali melanggar ketentuan<br>
        C = 3 - 4 kali melanggar ketentuan<br>
        D = > 4 kali melanggar ketentuan
        </div>

        <!-- F. EKSTRAKURIKULER, PRAMUKA & PEMINATAN -->
        <div class="section-title">F. Ekstrakurikuler, Pramuka & Peminatan</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Kegiatan / Ekstrakurikuler</th>
                    <?php foreach ($bulanAktif as $b): ?><th><?= $namaBulanIndo[$b] ?? $b ?></th><?php endforeach; ?>
                    <th class="col-rata">Predikat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matrixEskul)): ?>
                    <tr><td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">Belum ada data ekstrakurikuler.</td></tr>
                <?php else: ?>
                    <?php foreach ($matrixEskul as $key => $row): ?>
                    <tr>
                        <td class="col-aspek"><?= esc($row['label']) ?></td>
                        <?php foreach ($bulanAktif as $b): ?><td class="col-angka"><?= esc($row['bulan'][$b] ?? '-') ?></td><?php endforeach; ?>
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

        <!-- G. ANEKDOT & PRESTASI -->
        <div class="section-title">G. Catatan Anekdot & Prestasi</div>
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

        <!-- H. REKAPITULASI ASPEK YAUMIYAH -->
        <div class="section-title">H. Rekapitulasi Aspek Yaumiyah</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-aspek">Aspek Yaumiyah</th>
                    <?php foreach ($bulanAktif as $b): ?><th><?= esc($namaBulanIndo[$b] ?? $b) ?></th><?php endforeach; ?>
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
                        <td class="col-aspek" style="padding-left: 25px; font-weight: normal;">- <?= esc($label) ?></td>
                        <?php foreach ($bulanAktif as $b): 
                            $nilaiPersen = isset($matrixYaumiyah[$key][$b]) ? (float)$matrixYaumiyah[$key][$b] : 0;
                            if ($nilaiPersen > 0) {
                                $totalSatuBaris += $nilaiPersen;
                                $jumlahBulanAktif++;
                            }
                        ?>
                            <td class="col-angka text-center">
                                <?= $nilaiPersen > 0 ? number_format($nilaiPersen, 0) . '%' : '-' ?>
                            </td>
                        <?php endforeach; ?>
                        
                        <?php $rataRata = $jumlahBulanAktif > 0 ? ($totalSatuBaris / $jumlahBulanAktif) : 0; ?>
                        <td class="col-rata text-center">
                            <strong><?= $rataRata > 0 ? number_format($rataRata, 0) . '%' : '-' ?></strong>
                        </td>
                    </tr>
                <?php 
                    endforeach; 
                else:
                ?>
                    <tr>
                        <td colspan="<?= count($bulanAktif) + 2 ?>" class="text-center text-muted">
                            Belum ada data rekapitulasi yaumiyah di semester ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div style="margin-top: 5px; margin-bottom: 25px; font-size: 0.85em; color: #555;">
            <em>* Nilai yang ditampilkan adalah persentase capaian (%) dari target berdasarkan jumlah hari efektif per bulan.</em>
        </div>

    </div>
</div>
<?php endif; ?>

<!-- Script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    var selectedRombel = "<?= esc($selected_rombel ?? '') ?>";
    var selectedStudent = "<?= esc($selected_student ?? '') ?>";

    function loadSiswa(rombelId, selectedSiswaId = null) {
        var studentDropdown = $('#student_id');
        studentDropdown.html('<option value="">Mencari siswa...</option>');
        
        if(rombelId !== '') {
            $.ajax({
                // Menggunakan site_url() untuk keamanan route CodeIgniter 4
                url: '<?= site_url("admin/rapor-berjalan/get-siswa") ?>', 
                type: 'POST',
                data: { rombel_id: rombelId },
                dataType: 'json',
                success: function(response) {
                    studentDropdown.html('<option value="">-- Pilih Siswa --</option>');
                    if(response.length > 0) {
                        $.each(response, function(index, siswa) {
                            var isSelected = (siswa.id == selectedSiswaId) ? 'selected' : '';
                            studentDropdown.append('<option value="' + siswa.id + '" ' + isSelected + '>' + siswa.name + '</option>');
                        });
                    } else {
                        studentDropdown.html('<option value="">Siswa tidak ditemukan</option>');
                    }
                },
                error: function() {
                    studentDropdown.html('<option value="">Gagal memuat data</option>');
                }
            });
        } else {
            studentDropdown.html('<option value="">-- Tunggu Kelas --</option>');
        }
    }

    // Trigger saat form dropdown rombel diubah
    $('#rombel_id').change(function() {
        loadSiswa($(this).val());
    });

    // Jalankan otomatis saat halaman dimuat jika admin sudah pernah memilih kelas (rombel)
    if (selectedRombel !== '') {
        loadSiswa(selectedRombel, selectedStudent);
    }
});
</script>

</body>
</html>