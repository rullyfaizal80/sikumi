<!DOCTYPE html>
<html lang="id">
<head>
    <title>Laporan Per Siswa</title>
    <!-- Gunakan CSS yang sama dengan file sebelumnya -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4 w-100">
    <!-- Judul di sisi kiri -->
    <h3 class="mb-0">Laporan Individu Siswa</h3>
    
    <!-- Tombol terdorong ke sisi kanan -->
    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold ml-auto">
        <i class="fas fa-arrow-left mr-1"></i> Dashboard
    </a>
</div>

        <!-- PANEL FILTER -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="<?= base_url('admin/laporan-siswa') ?>" method="GET" class="row">
                    
                    <!-- Pilih Kelas -->
                    <div class="col-md-3 mb-2">
                        <label>Pilih Kelas</label>
                        <select name="rombel_id" id="kelas_select" class="form-control" onchange="loadSiswa()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach($daftarRombel as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $rombel_id == $r['id'] ? 'selected' : '' ?>>
                                    <?= esc($r['rombel_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pilih Siswa (Diisi otomatis oleh JS) -->
                    <div class="col-md-3 mb-2">
                        <label>Pilih Siswa</label>
                        <select name="student_id" id="siswa_select" class="form-control" required>
                            <option value="">-- Pilih Kelas Dahulu --</option>
                            <!-- Pilihan siswa akan masuk ke sini via AJAX -->
                        </select>
                    </div>

                    <!-- Pilih Bulan & Tahun -->
                    <div class="col-md-3 mb-2">
                        <label>Bulan</label>
                        <select name="bulan" class="form-control">
                            <?php 
                                $namaBulan = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agt','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
                                foreach ($namaBulan as $angka => $nama): 
                            ?>
                                <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Tahun</label>
                        <input type="number" name="tahun" class="form-control" value="<?= esc($tahun) ?>" required>
                    </div>

                    <div class="col-md-1 mb-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAMPILAN HASIL (Hanya muncul jika siswa sudah dipilih dan di-submit) -->
        <?php if (!empty($dataSiswa)): ?>
            <div class="card shadow-sm border-top-primary">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Data Aspek: Absensi</h5>
                    <small class="text-muted">
    Nama: <strong><?= esc($dataSiswa['name']) ?></strong> | 
    NIS: <strong><?= esc($dataSiswa['nis'] ?? '-') ?></strong> | 
    Hari Efektif: <strong><?= $hariEfektif ?> Hari</strong>
</small>
                </div>
                <div class="card-body">
                    <?php if ($hariEfektif == 0): ?>
                        <div class="alert alert-warning">Target hari efektif bulan ini belum diset.</div>
                    <?php else: ?>
                        <?php 
                            // Pastikan jika null menjadi 0
                            $h = (int)($rekapAbsen['total_h'] ?? 0);
                            $s = (int)($rekapAbsen['total_s'] ?? 0);
                            $i = (int)($rekapAbsen['total_i'] ?? 0);
                            $a = (int)($rekapAbsen['total_a'] ?? 0);
                            $t = (int)($rekapAbsen['total_t'] ?? 0);

                            // Hitung persentase kehadiran
                            $persenHadir = ($h / $hariEfektif) * 100;
                        ?>
                        <div class="row text-center">
                            <div class="col-md-2 border-right">
                                <h6>Hadir</h6><h3 class="text-success"><?= $h ?></h3>
                            </div>
                            <div class="col-md-2 border-right">
                                <h6>Sakit</h6><h3 class="text-info"><?= $s ?></h3>
                            </div>
                            <div class="col-md-2 border-right">
                                <h6>Ijin</h6><h3 class="text-warning"><?= $i ?></h3>
                            </div>
                            <div class="col-md-2 border-right">
                                <h6>Alpa</h6><h3 class="text-danger"><?= $a ?></h3>
                            </div>
                            <div class="col-md-2 border-right">
                                <h6>Terlambat</h6><h3 class="text-secondary"><?= $t ?></h3>
                            </div>
                            <div class="col-md-2">
                                <h6>Persentase</h6><h3 class="text-primary"><?= number_format($persenHadir, 1) ?>%</h3>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ... (TAMPILAN CARD ABSENSI SEBELUMNYA) ... -->

        <?php if (!empty($dataSiswa)): ?>
        <div class="row mt-4">
            <!-- ========================================== -->
            <!-- TAMPILAN KEPATUHAN (PELANGGARAN)           -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-danger h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list text-danger mr-2"></i>Catatan Kepatuhan</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                            $ks = $kepatuhanSiswa;
                            $totKasus = (int)($ks['seragam']??0) + (int)($ks['atribut']??0) + (int)($ks['bersih_diri']??0) + 
                                        (int)($ks['terlambat']??0) + (int)($ks['aturan_kelas']??0) + (int)($ks['masjid']??0);
                            
                            // Logika membersihkan duplikat keterangan (sama seperti yang Anda buat di rekap sekolah)
                            $ketRaw = isset($ks['keterangan']) ? $ks['keterangan'] : '';
                            $arrKet = array_filter(array_map('trim', explode(',', $ketRaw)));
                            $countKet = array_count_values($arrKet);
                            $hasilKet = [];
                            foreach($countKet as $teks => $jml) {
                                $hasilKet[] = $jml > 1 ? "$teks(<strong>$jml</strong>)" : $teks;
                            }
                            $teksKet = !empty($hasilKet) ? implode(', ', $hasilKet) : '-';
                        ?>
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" title="Seragam">Srgm</th>
                                    <th class="text-center" title="Atribut">Atrb</th>
                                    <th class="text-center" title="Bersih Diri">B.Diri</th>
                                    <th class="text-center" title="Terlambat">Lmbt</th>
                                    <th class="text-center" title="Aturan Kelas">Kls</th>
                                    <th class="text-center" title="Masjid">Msjd</th>
                                    <th class="text-center bg-danger text-white">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center font-weight-bold">
                                    <td><?= (int)($ks['seragam']??0) ?></td>
                                    <td><?= (int)($ks['atribut']??0) ?></td>
                                    <td><?= (int)($ks['bersih_diri']??0) ?></td>
                                    <td><?= (int)($ks['terlambat']??0) ?></td>
                                    <td><?= (int)($ks['aturan_kelas']??0) ?></td>
                                    <td><?= (int)($ks['masjid']??0) ?></td>
                                    <td class="bg-light text-danger" style="font-size: 1.2rem;"><?= $totKasus ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="small">
                            <strong>Keterangan Kasus:</strong><br>
                            <?= $teksKet ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAMPILAN JURNAL YAUMIYAH                   -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-success h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-pray text-success mr-2"></i>Capaian Yaumiyah</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0 text-center">
                            <tbody>
                                <tr>
                                    <td class="text-left pl-3">Shalat Dzuhur</td>
                                    <td><?= number_format($yaumiyahSiswa['p_dzuhur'], 1) ?>%</td>
                                    <td class="text-left pl-3">Tilawah</td>
                                    <td><?= number_format($yaumiyahSiswa['p_tilawah'], 1) ?>%</td>
                                </tr>
                                <tr>
                                    <td class="text-left pl-3">Shalat Ashar</td>
                                    <td><?= number_format($yaumiyahSiswa['p_ashar'], 1) ?>%</td>
                                    <td class="text-left pl-3">Infaq</td>
                                    <td><?= number_format($yaumiyahSiswa['p_infaq'], 1) ?>%</td>
                                </tr>
                                <tr>
                                    <td class="text-left pl-3">Ba'diah Dzuhur</td>
                                    <td><?= number_format($yaumiyahSiswa['p_bakdiah'], 1) ?>%</td>
                                    <td class="text-left pl-3">Shaum Sunnah</td>
                                    <td><?= number_format($yaumiyahSiswa['p_shaum'], 1) ?>%</td>
                                </tr>
                                <tr>
                                    <td class="text-left pl-3">Shalat Duha</td>
                                    <td><?= number_format($yaumiyahSiswa['p_duha'], 1) ?>%</td>
                                    <td class="text-left pl-3">Literasi</td>
                                    <td><?= number_format($yaumiyahSiswa['p_literasi'], 1) ?>%</td>
                                </tr>
                                <tr>
                                    <td class="text-left pl-3">Tahajud</td>
                                    <td><?= number_format($yaumiyahSiswa['p_tahajud'], 1) ?>%</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ... (TAMPILAN ROW KEPATUHAN & YAUMIYAH SEBELUMNYA) ... -->

        <?php if (!empty($dataSiswa)): ?>
        <div class="row mt-4">
            <!-- ========================================== -->
            <!-- TAMPILAN ASPEK SPIRITUAL                   -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-info h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-praying-hands text-info mr-2"></i>Catatan Spiritual</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                            $spi = $spiritualSiswa;
                            $totSpi = (int)($spi['berdoa']??0) + (int)($spi['kalimat_thoyibah']??0) + (int)($spi['shalat']??0) + 
                                      (int)($spi['salam']??0) + (int)($spi['syukur']??0) + (int)($spi['lingkungan']??0) + (int)($spi['toleransi']??0);
                            
                            $ketSpiRaw = isset($spi['keterangan']) ? $spi['keterangan'] : '';
                            $arrKetSpi = array_filter(array_map('trim', explode(',', $ketSpiRaw)));
                            $countKetSpi = array_count_values($arrKetSpi);
                            $hasilKetSpi = [];
                            foreach($countKetSpi as $teks => $jml) {
                                $hasilKetSpi[] = $jml > 1 ? "$teks(<strong>$jml</strong>)" : $teks;
                            }
                            $teksKetSpi = !empty($hasilKetSpi) ? implode(', ', $hasilKetSpi) : '-';
                        ?>
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" title="Berdoa">Doa</th>
                                    <th class="text-center" title="Kalimat Thoyibah">Thyb</th>
                                    <th class="text-center" title="Shalat">Shlt</th>
                                    <th class="text-center" title="Salam">Slm</th>
                                    <th class="text-center" title="Syukur">Sykr</th>
                                    <th class="text-center" title="Lingkungan">Lgkn</th>
                                    <th class="text-center" title="Toleransi">Tlrn</th>
                                    <th class="text-center bg-info text-white">TOT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center font-weight-bold">
                                    <td><?= (int)($spi['berdoa']??0) ?></td>
                                    <td><?= (int)($spi['kalimat_thoyibah']??0) ?></td>
                                    <td><?= (int)($spi['shalat']??0) ?></td>
                                    <td><?= (int)($spi['salam']??0) ?></td>
                                    <td><?= (int)($spi['syukur']??0) ?></td>
                                    <td><?= (int)($spi['lingkungan']??0) ?></td>
                                    <td><?= (int)($spi['toleransi']??0) ?></td>
                                    <td class="bg-light text-info" style="font-size: 1.1rem;"><?= $totSpi ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="small">
                            <strong>Keterangan Kasus:</strong><br>
                            <?= $teksKetSpi ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAMPILAN ASPEK SOSIAL                      -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-warning h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-users text-warning mr-2"></i>Catatan Sosial</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                            $sos = $sosialSiswa;
                            $totSos = (int)($sos['disiplin']??0) + (int)($sos['jujur']??0) + (int)($sos['percaya_diri']??0) + 
                                      (int)($sos['santun']??0) + (int)($sos['kerjasama']??0) + (int)($sos['tanggung_jawab']??0) + (int)($sos['adil']??0);
                            
                            $ketSosRaw = isset($sos['keterangan']) ? $sos['keterangan'] : '';
                            $arrKetSos = array_filter(array_map('trim', explode(',', $ketSosRaw)));
                            $countKetSos = array_count_values($arrKetSos);
                            $hasilKetSos = [];
                            foreach($countKetSos as $teks => $jml) {
                                $hasilKetSos[] = $jml > 1 ? "$teks(<strong>$jml</strong>)" : $teks;
                            }
                            $teksKetSos = !empty($hasilKetSos) ? implode(', ', $hasilKetSos) : '-';
                        ?>
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" title="Disiplin">Dspl</th>
                                    <th class="text-center" title="Jujur">Jjr</th>
                                    <th class="text-center" title="Percaya Diri">PD</th>
                                    <th class="text-center" title="Santun">Sntn</th>
                                    <th class="text-center" title="Kerjasama">Krjs</th>
                                    <th class="text-center" title="Tanggung Jawab">T.Jw</th>
                                    <th class="text-center" title="Adil">Adil</th>
                                    <th class="text-center bg-warning text-dark">TOT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-center font-weight-bold">
                                    <td><?= (int)($sos['disiplin']??0) ?></td>
                                    <td><?= (int)($sos['jujur']??0) ?></td>
                                    <td><?= (int)($sos['percaya_diri']??0) ?></td>
                                    <td><?= (int)($sos['santun']??0) ?></td>
                                    <td><?= (int)($sos['kerjasama']??0) ?></td>
                                    <td><?= (int)($sos['tanggung_jawab']??0) ?></td>
                                    <td><?= (int)($sos['adil']??0) ?></td>
                                    <td class="bg-light text-warning" style="font-size: 1.1rem;"><?= $totSos ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="small">
                            <strong>Keterangan Kasus:</strong><br>
                            <?= $teksKetSos ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ========================================== -->
            <!-- TAMPILAN AL-QUR'AN                         -->
            <!-- ========================================== -->
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm border-top-primary">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-book-open text-primary mr-2"></i>Penilaian Al-Qur'an</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 border-right">
                                <h6 class="text-muted">Tahsin</h6>
                                <h3 class="text-info"><?= !empty(trim($quranSiswa['tahsin_nilai']??'')) ? esc($quranSiswa['tahsin_nilai']) : '-' ?></h3>
                            </div>
                            <div class="col-md-4 border-right">
                                <h6 class="text-muted">Tahfidz</h6>
                                <h3 class="text-primary"><?= !empty(trim($quranSiswa['tahfidz_nilai']??'')) ? esc($quranSiswa['tahfidz_nilai']) : '-' ?></h3>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted">Kitabah</h6>
                                <h3 class="text-warning"><?= !empty(trim($quranSiswa['kitabah_nilai']??'')) ? esc($quranSiswa['kitabah_nilai']) : '-' ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- ... (TAMPILAN ROW AL-QUR'AN SEBELUMNYA) ... -->

        <?php if (!empty($dataSiswa)): ?>
        <div class="row mt-4">
            <!-- ========================================== -->
            <!-- TAMPILAN NILAI SUMATIF (AKADEMIK)          -->
            <!-- ========================================== -->
            <div class="col-md-8 mb-4">
                <div class="card shadow-sm border-top-primary h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-graduation-cap text-primary mr-2"></i>Nilai Sumatif (Akademik)</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">No</th>
                                    <th>Mata Pelajaran</th>
                                    <th class="text-center" style="width: 150px;">Nilai Angka</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    if (!empty($sumatifSiswa)): 
                                        $no = 1;
                                        foreach ($sumatifSiswa as $ns):
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= esc($ns['nama_mapel']) ?></td>
                                        <td class="text-center font-weight-bold <?= !empty($ns['nilai_angka']) ? 'text-primary' : 'text-muted' ?>">
                                            <?= !empty($ns['nilai_angka']) ? str_replace('.', ',', (float)$ns['nilai_angka']) : '-' ?>
                                        </td>
                                    </tr>
                                <?php 
                                        endforeach; 
                                    else:
                                ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Jadwal / Mata Pelajaran untuk tahun ajaran ini belum tersedia.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAMPILAN ESKUL, PRAMUKA & PEMINATAN        -->
            <!-- ========================================== -->
            <div class="col-md-4 mb-4">
                
                <!-- PRAMUKA & PEMINATAN -->
                <div class="card shadow-sm border-top-success mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-campground text-success mr-2"></i>Pramuka & Peminatan</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="pl-3">Nilai Pramuka</td>
                                    <td class="text-right pr-3 font-weight-bold"><?= !empty($pramukaSiswa['nilai']) ? esc($pramukaSiswa['nilai']) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="pl-3">Nilai Peminatan</td>
                                    <td class="text-right pr-3 font-weight-bold"><?= !empty($peminatanSiswa['nilai']) ? esc($peminatanSiswa['nilai']) : '-' ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- EKSTRAKURIKULER -->
                <div class="card shadow-sm border-top-warning">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 font-weight-bold"><i class="fas fa-futbol text-warning mr-2"></i>Ekstrakurikuler</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="pl-3">Kelompok</th>
                                    <th class="text-right pr-3">Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    if (!empty($eskulSiswa)): 
                                        foreach ($eskulSiswa as $es):
                                ?>
                                    <tr>
                                        <td class="pl-3"><?= esc($es['nama_kelompok']) ?></td>
                                        <td class="text-right pr-3 font-weight-bold"><?= esc($es['nilai']) ?></td>
                                    </tr>
                                <?php 
                                        endforeach; 
                                    else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-center py-3 text-muted small">Tidak mengikuti ekstrakurikuler.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- ... (TAMPILAN ROW NILAI SUMATIF & ESKUL SEBELUMNYA) ... -->

        <?php if (!empty($dataSiswa)): ?>
        <div class="row mt-4">
            <!-- ========================================== -->
            <!-- TAMPILAN CATATAN ANEKDOT                   -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-danger h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard text-danger mr-2"></i>Catatan Anekdot</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 100px;" class="pl-3">Tanggal</th>
                                    <th>Kejadian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($anekdotSiswa)): ?>
                                    <?php foreach ($anekdotSiswa as $an): ?>
                                        <tr>
                                            <!-- Format tanggal menjadi dd/mm/yyyy -->
                                            <td class="pl-3"><?= date('d/m/Y', strtotime($an['tanggal'])) ?></td>
                                            <td><?= esc($an['kejadian']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center py-3 text-muted small">Tidak ada catatan anekdot di bulan ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAMPILAN CATATAN PRESTASI                  -->
            <!-- ========================================== -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-top-info h-100">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-trophy text-info mr-2"></i>Catatan Prestasi</h5>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="pl-3">Nama Prestasi</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($prestasiSiswa)): ?>
                                    <?php foreach ($prestasiSiswa as $pr): ?>
                                        <tr>
                                            <td class="pl-3 font-weight-bold text-primary"><?= esc($pr['nama_prestasi']) ?></td>
                                            <td><?= esc($pr['keterangan']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center py-3 text-muted small">Belum ada catatan prestasi di bulan ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- SCRIPT AJAX UNTUK DROPDOWN DINAMIS -->
    <script>
        // URL endpoint ke function getSiswaByKelas()
        const urlGetSiswa = "<?= base_url('admin/laporan-siswa/get-siswa-by-kelas') ?>"; 

        // Fungsi yang dipanggil saat dropdown kelas berubah
        async function loadSiswa() {
            const rombelId = document.getElementById('kelas_select').value;
            const siswaSelect = document.getElementById('siswa_select');
            
            // Kosongkan opsi siswa saat ini
            siswaSelect.innerHTML = '<option value="">Memuat...</option>';

            if (!rombelId) {
                siswaSelect.innerHTML = '<option value="">-- Pilih Kelas Dahulu --</option>';
                return;
            }

            try {
                const response = await fetch(`${urlGetSiswa}?rombel_id=${rombelId}`);
                const data = await response.json();
                
                siswaSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';
                
                data.forEach(siswa => {
                    // Cek jika siswa ini sedang dipilih dari submit sebelumnya
                    const isSelected = (siswa.id == "<?= $student_id ?? '' ?>") ? 'selected' : '';
                    siswaSelect.innerHTML += `<option value="${siswa.id}" ${isSelected}>${siswa.name}</option>`;
                });
            } catch (error) {
                console.error("Gagal memuat data siswa", error);
                siswaSelect.innerHTML = '<option value="">Gagal memuat data</option>';
            }
        }

        // Panggil saat halaman pertama kali dimuat (berguna untuk mempertahankan pilihan setelah tombol Cari ditekan)
        document.addEventListener('DOMContentLoaded', function() {
            const kelasVal = document.getElementById('kelas_select').value;
            if(kelasVal !== "") {
                loadSiswa();
            }
        });
    </script>
</body>
</html>