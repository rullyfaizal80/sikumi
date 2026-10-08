<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <!-- CSS AdminLTE & FontAwesome -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1200px;">
        
        <!-- Flash Message -->
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;">📝 <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Tahun Ajaran: <strong><?= esc($tahun_aktif['academic_year']) ?> - Semester <?= esc($tahun_aktif['semester']) ?></strong></p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="mb-3">
           <a href="<?= base_url('asesmen/admin/create') ?>" class="btn btn-primary font-weight-bold shadow-sm">
                 <i class="fas fa-plus mr-1"></i> Tambah Jadwal Ujian
            </a>
            <button type="button" class="btn btn-info font-weight-bold shadow-sm" data-toggle="modal" data-target="#modalKategori">
                <i class="fas fa-tags mr-1"></i> Kelola Kategori
            </button>
        </div>

        <!-- Daftar Jadwal Ujian -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0" style="font-weight: 600;">Jadwal Ujian Aktif</h5>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kategori</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Waktu Pelaksanaan</th>
                                <th class="text-center">Token</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jadwal_ujian)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada jadwal ujian di semester ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <!-- Ganti bagian foreach di dalam tbody menjadi seperti ini: -->

<?php foreach ($jadwal_ujian as $jadwal): ?>
<tr>
    <td class="text-center font-weight-bold"><?= $no++ ?></td>
    
    <!-- 1. Perbaikan Kategori: Menggunakan teks warna biru tua (primary) -->
    <td>
        <strong class="text-primary"><?= esc($jadwal['nama_kategori'] ?? 'Tanpa Kategori') ?></strong>
    </td>
    
    <td><?= esc($jadwal['rombel_name'] ?? 'N/A') ?></td>
    <td><?= esc($jadwal['subject_name'] ?? 'N/A') ?></td>
    <td>
        <small>
            <i class="fas fa-play text-success mr-1"></i> <?= esc($jadwal['waktu_mulai']) ?><br>
            <i class="fas fa-stop text-danger mr-1"></i> <?= esc($jadwal['waktu_selesai']) ?>
        </small>
    </td>
    
    <!-- 2. Perbaikan Token: Menggunakan warna merah terang agar mencolok -->
    <td class="text-center">
        <span class="font-weight-bold" style="color: #d81b60; font-size: 15px; letter-spacing: 2px;">
            <?= esc($jadwal['token']) ?>
        </span>
    </td>
    
    <!-- 3. Perbaikan Status: Menggunakan teks hijau/abu-abu dengan Icon -->
    <td class="text-center">
        <?php if($jadwal['is_active']): ?>
            <strong class="text-success"><i class="fas fa-check-circle mr-1"></i>Aktif</strong>
        <?php else: ?>
            <strong class="text-secondary"><i class="fas fa-ban mr-1"></i>Nonaktif</strong>
        <?php endif; ?>
    </td>
    
    <td class="text-center">
        <a href="<?= base_url('asesmen/admin/edit/' . $jadwal['id']) ?>" class="btn btn-sm btn-primary" title="Edit Jadwal">
    <i class="fas fa-edit"></i>
</a>
<a href="<?= base_url('asesmen/admin/hapus/' . $jadwal['id']) ?>" class="btn btn-sm btn-danger" title="Hapus Jadwal" onclick="return confirm('Apakah Anda yakin ingin menghapus jadwal ujian ini secara permanen? Semua riwayat ujian siswa pada jadwal ini akan ikut terhapus!');">
    <i class="fas fa-trash"></i>
</a>
    </td>
</tr>
<?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- MODAL KELOLA KATEGORI -->
    <div class="modal fade" id="modalKategori" tabindex="-1" aria-labelledby="modalKategoriLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold" id="modalKategoriLabel"><i class="fas fa-tags mr-2"></i>Kelola Kategori Asesmen</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form Tambah Kategori -->
                    <form action="<?= base_url('asesmen/admin/simpan-kategori') ?>" method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Kategori baru (Misal: SAS, PAS)" required>
                            <div class="input-group-append">
                                <button class="btn btn-success font-weight-bold" type="submit"><i class="fas fa-save mr-1"></i> Simpan</button>
                            </div>
                        </div>
                    </form>

                    <!-- Tabel Kategori -->
                    <table class="table table-sm table-bordered table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" width="50">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-center" width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($kategori_ujian)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Belum ada data kategori</td>
                                </tr>
                            <?php else: ?>
                                <?php $nk = 1; foreach($kategori_ujian as $kategori): ?>
                                <tr>
                                    <td class="text-center"><?= $nk++ ?></td>
                                    <td><?= esc($kategori['nama_kategori']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= base_url('asesmen/admin/hapus-kategori/' . $kategori['id']) ?>" class="btn btn-xs btn-danger" onclick="return confirm('Yakin ingin menghapus kategori ini? Jadwal ujian yang menggunakan kategori ini akan ikut terhapus!');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Script jQuery & Bootstrap JS (Dibutuhkan untuk Modal & Alert) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>