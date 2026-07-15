<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 text-info font-weight-bold"><i class="fas fa-eye mr-2"></i> Detail Kelompok</h3>
            <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
</a>
        </div>

        <div class="row">
            <!-- Info Card -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white font-weight-bold">Informasi</div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><th width="40%" class="text-muted">Nama Kelompok</th><td>: <?= esc($kelompok['nama_kelompok']) ?></td></tr>
                            <tr><th class="text-muted">Pembimbing</th><td>: <span class="font-weight-bold"><?= esc($kelompok['pembimbing']) ?></span></td></tr>
                            <tr>
    <th class="text-muted">Jenis</th>
    <td>: 
        <?php 
            // Ambil nilai jenis kelompok, hilangkan spasi, dan ubah ke huruf kecil untuk perbandingan aman
            $jenis = isset($kelompok['jenis_kelompok']) ? trim($kelompok['jenis_kelompok']) : '';
            $jenisLower = strtolower($jenis);
        ?>
        
        <?php if ($jenisLower === 'reguler'): ?>
            <!-- Paksa warna background biru dan teks putih secara inline -->
            <span class="badge" style="color: #ffffff !important; background-color: #007bff !important; padding: 5px 10px; font-weight: bold; display: inline-block;">
                Reguler
            </span>
        <?php elseif ($jenisLower === 'khusus'): ?>
            <!-- Paksa warna background kuning dan teks hitam secara inline -->
            <span class="badge" style="color: #000000 !important; background-color: #ffc107 !important; padding: 5px 10px; font-weight: bold; display: inline-block;">
                Khusus
            </span>
        <?php else: ?>
            <!-- Jika kosong atau berisi teks lain, tampilkan kotak merah berisi nilai aslinya untuk pelacakan -->
            <span style="color: #ffffff !important; background-color: #dc3545 !important; padding: 5px 10px; font-weight: bold; border-radius: 4px; display: inline-block;">
                Gagal Membaca DB (Nilai: "<?= esc($jenis === '' ? 'KOSONG/NULL' : $jenis) ?>")
            </span>
        <?php endif; ?>
    </td>
</tr>
                            <tr><th class="text-muted">Jumlah Siswa</th><td>: <?= count($anggota) ?> Orang</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- List Anggota -->
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white font-weight-bold text-secondary">
                        <i class="fas fa-users mr-1"></i> Daftar Anggota (Siswa)
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th width="10%" class="text-center">No</th>
                                    <th>Nama Siswa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($anggota as $siswa): ?>
                                    <tr>
                                        <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                        <td><?= esc($siswa['nama_siswa']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($anggota)): ?>
                                    <tr><td colspan="2" class="text-center py-4 text-muted">Belum ada siswa di kelompok ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>