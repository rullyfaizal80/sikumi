<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Master Data</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        
        <nav class="app-header navbar navbar-expand bg-body shadow-sm">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <h4 class="navbar-text my-0 ps-2" style="color: #FF9F00; font-weight: 700;">
                            🗂️ Ruang Master Data <span style="color: #FFC107;">SiKuMi</span>
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ps-2">
                        <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">⬅️ Dashboard</a>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="app-main pt-4">
            <div class="container-fluid">
                
                <?php if (session()->getFlashdata('sukses')): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <?= session()->getFlashdata('sukses') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    
                    <div class="col-lg-5 col-12 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="card-title mb-0 font-weight-bold text-dark" style="font-weight: 600;">🏫 Master Tingkat Kelas</h5>
                                <button type="button" class="btn btn-sm btn-warning font-weight-bold px-3 ms-auto" data-bs-toggle="modal" data-bs-target="#modalTambahKelas">
                                    ➕ Tambah Tingkat
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th style="width: 60px;">No</th>
                                                <th>Tingkat</th>
                                                <th>Jenjang</th>
                                                <th>Fase</th>
                                                <th style="width: 120px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            <?php if(empty($classes)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-muted py-4">Belum ada data tingkat kelas.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php $noC = 1; foreach($classes as $c): ?>
                                                    <tr>
                                                        <td><?= $noC++ ?></td>
                                                        <td><span class="badge bg-dark px-2.5"><?= esc($c['class_name']) ?></span></td>
                                                        <td><span class="badge bg-secondary"><?= esc($c['level_type']) ?></span></td>
                                                        <td><em class="text-secondary"><?= esc($c['curriculum_phase']) ?></em></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-light btn-sm border-secondary-subtle px-2" data-bs-toggle="modal" data-bs-target="#modalEditKelas<?= $c['id'] ?>" title="Edit Kelas">📝</button>
                                                                <a href="<?= base_url('admin/master-data/class-delete/' . $c['id']) ?>" class="btn btn-light btn-sm border-secondary-subtle px-2" onclick="return confirm('Hapus master tingkat kelas ini? Agenda kalender akademik yang terikat akan ikut terpengaruh.')" title="Hapus Kelas">🗑️</a>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <div class="modal fade" id="modalEditKelas<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header bg-warning">
                                                                    <h5 class="modal-title font-weight-bold text-dark">📝 Ubah Master Tingkat</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="<?= base_url('admin/master-data/class-update/' . $c['id']) ?>" method="POST">
                                                                    <?= csrf_field() ?>
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Nama Tingkat (Angka)</label>
                                                                            <input type="text" name="class_name" class="form-control form-control-sm" value="<?= esc($c['class_name']) ?>" required placeholder="Misal: 7, 8, 9">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Jenjang Madrasah</label>
                                                                            <select name="level_type" class="form-select form-select-sm" required>
                                                                                <option value="MI" <?= $c['level_type'] == 'MI' ? 'selected' : '' ?>>MI (Madrasah Ibtidaiyah)</option>
                                                                                <option value="MTs" <?= $c['level_type'] == 'MTs' ? 'selected' : '' ?>>MTs (Madrasah Tsanawiyah)</option>
                                                                                <option value="MA" <?= $c['level_type'] == 'MA' ? 'selected' : '' ?>>MA (Madrasah Aliyah)</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Fase Kurikulum</label>
                                                                            <input type="text" name="curriculum_phase" class="form-control form-control-sm" value="<?= esc($c['curriculum_phase']) ?>" required placeholder="Misal: Fase D, Fase E">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer bg-light">
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-warning btn-sm font-weight-bold text-dark">💾 Simpan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 col-12 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-white d-flex align-items-center py-3">
    <h5 class="card-title mb-0 font-weight-bold text-dark" style="font-weight: 600;">📚 Master Mata Pelajaran</h5>
    
    <button type="button" class="btn btn-sm btn-info text-white font-weight-bold px-3 ms-auto" data-bs-toggle="modal" data-bs-target="#modalTambahMapel">
        ➕ Tambah Mapel
    </button>
</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small text-center">
                                            <tr>
                                                <th style="width: 60px;">No</th>
                                                <th style="width: 100px;">Kode</th>
                                                <th>Nama Mata Pelajaran</th>
                                                <th>Kelompok Kelompok</th>
                                                <th style="width: 120px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            <?php if(empty($subjects)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data mata pelajaran.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php $noS = 1; foreach($subjects as $s): ?>
                                                    <tr>
                                                        <td class="text-center"><?= $noS++ ?></td>
                                                        <td class="text-center"><code><?= esc($s['subject_code']) ?></code></td>
                                                        <td class="font-weight-bold text-dark"><?= esc($s['subject_name']) ?></td>
                                                        <td><span class="badge bg-light text-dark border border-secondary-subtle"><?= esc($s['subject_group'] ?? 'Umum') ?></span></td>
                                                        <td class="text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-light btn-sm border-secondary-subtle px-2" data-bs-toggle="modal" data-bs-target="#modalEditMapel<?= $s['id'] ?>" title="Edit Mapel">📝</button>
                                                                <a href="<?= base_url('admin/master-data/subject-delete/' . $s['id']) ?>" class="btn btn-light btn-sm border-secondary-subtle px-2" onclick="return confirm('Hapus mata pelajaran ini?')" title="Hapus Mapel">🗑️</a>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <div class="modal fade" id="modalEditMapel<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-info text-white">
                                                                    <h5 class="modal-title font-weight-bold">📝 Ubah Mata Pelajaran</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="<?= base_url('admin/master-data/subject-update/' . $s['id']) ?>" method="POST">
                                                                    <?= csrf_field() ?>
                                                                    <div class="modal-body text-start">
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Kode Mapel</label>
                                                                            <input type="text" name="subject_code" class="form-control form-control-sm" value="<?= esc($s['subject_code']) ?>" required placeholder="Misal: INF, MTK, PAI">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Nama Lengkap Mata Pelajaran</label>
                                                                            <input type="text" name="subject_name" class="form-control form-control-sm" value="<?= esc($s['subject_name']) ?>" required placeholder="Misal: Informatika, Matematika">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="small font-weight-bold mb-1">Kelompok/Kategori</label>
                                                                            <input type="text" name="subject_group" class="form-control form-control-sm" value="<?= esc($s['subject_group']) ?>" placeholder="Misal: Kelompok A, Mulok, Pilihan">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer bg-light">
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-info btn-sm font-weight-bold text-white">💾 Simpan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalTambahKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold">➕ Tambah Master Tingkat Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/master-data/class-store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Nama Tingkat (Angka)</label>
                            <input type="text" name="class_name" class="form-control form-control-sm" required placeholder="Misal: 7, 8, 10">
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Jenjang Madrasah</label>
                            <select name="level_type" class="form-select form-select-sm" required>
                                <option value="MI">MI (Madrasah Ibtidaiyah)</option>
                                <option value="MTs" selected>MTs (Madrasah Tsanawiyah)</option>
                                <option value="MA">MA (Madrasah Aliyah)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Fase Kurikulum</label>
                            <input type="text" name="curriculum_phase" class="form-control form-control-sm" required placeholder="Misal: Fase D, Fase E">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning btn-sm font-weight-bold text-dark">💾 Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahMapel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold">➕ Tambah Mata Pelajaran</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/master-data/subject-store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Kode Mapel</label>
                            <input type="text" name="subject_code" class="form-control form-control-sm" required placeholder="Misal: INF, MTK, PAI">
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Nama Lengkap Mata Pelajaran</label>
                            <input type="text" name="subject_name" class="form-control form-control-sm" required placeholder="Misal: Informatika, Matematika">
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Kelompok/Kategori</label>
                            <input type="text" name="subject_group" class="form-control form-control-sm" placeholder="Misal: Kelompok A, Mulok, Pilihan">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info btn-sm font-weight-bold text-white">💾 Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

</body>
</html>