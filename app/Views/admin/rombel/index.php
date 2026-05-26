<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Manajemen Rombel & Plotting</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
            color: #212529;
            box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        
        <nav class="app-header navbar navbar-expand bg-white shadow-sm">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <h4 class="navbar-text my-0 ps-2" style="color: #0d6efd; font-weight: 700;">
                            <i class="bi bi-building"></i> Manajemen Rombel & Plotting Mapel
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ps-2 pe-3">
                        <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-secondary font-weight-bold">
                            <i class="bi bi-house-door"></i> Kembali ke Dashboard
                        </a>
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

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3">
                        
                        <form action="<?= base_url('admin/rombel') ?>" method="GET" class="d-flex align-items-center gap-2">
                            <label class="font-weight-bold text-nowrap mb-0"><i class="bi bi-calendar3"></i> Tahun Ajaran:</label>
                            <select name="ta" class="form-select form-select-sm" style="min-width: 250px;" onchange="this.form.submit()">
                                <?php if(empty($tahunAjaran)): ?>
                                    <option value="">-- Belum ada Tahun Ajaran --</option>
                                <?php else: ?>
                                    <?php foreach($tahunAjaran as $ta): ?>
                                        <option value="<?= $ta['id'] ?>" <?= ($ta['id'] == $selectedTaId) ? 'selected' : '' ?>>
                                            Semester <?= esc($ta['semester']) ?> - <?= esc($ta['academic_year']) ?> 
                                            <?= $ta['is_active'] ? '(Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </form>

                        <div class="d-flex gap-2">
                            <?php if(empty($rombels) && !empty($tahunAjaran)): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalCopyRombel">
                                    <i class="bi bi-magic"></i> Salin dari Semester Lalu
                                </button>
                            <?php endif; ?>

                            <?php if(!empty($tahunAjaran)): ?>
                                <button type="button" class="btn btn-sm btn-primary font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahRombel">
                                    <i class="bi bi-plus-lg"></i> Tambah Rombel Baru
                                </button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                <?php if(empty($tahunAjaran)): ?>
                    <div class="alert alert-warning text-center shadow-sm">
                        ⚠️ Data Tahun Ajaran kosong. Silakan atur Kalender Akademik/Tahun Ajaran terlebih dahulu.
                    </div>
                <?php elseif(empty($rombels)): ?>
                    <div class="alert alert-info text-center shadow-sm py-5 bg-white border-0">
                        <h5 class="text-muted mb-3"><i class="bi bi-inbox fs-1"></i></h5>
                        <strong>Belum ada Rombel di semester ini.</strong><br>
                        Silakan buat rombel baru atau gunakan fitur "Salin dari Semester Lalu".
                    </div>
                <?php else: ?>
                    <div class="accordion shadow-sm" id="accordionRombel">
                        <?php foreach($rombels as $r): ?>
                            <div class="accordion-item border-0 border-bottom">
                                <h2 class="accordion-header" id="heading<?= $r['id'] ?>">
                                    <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $r['id'] ?>" aria-expanded="false" aria-controls="collapse<?= $r['id'] ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-center pe-3">
                                            <div>
                                                <span class="badge bg-primary fs-6 me-2"><?= esc($r['rombel_name']) ?></span>
                                                <span class="text-muted small">Tingkat <?= esc($r['tingkat']) ?> (<?= esc($r['level_type']) ?>)</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="small text-muted d-block" style="font-size: 0.75rem;">Wali Kelas:</span>
                                                <span class="font-weight-bold"><i class="bi bi-person-badge"></i> <?= esc($r['nama_walas'] ?? 'Belum Diatur') ?></span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse<?= $r['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $r['id'] ?>" data-bs-parent="#accordionRombel">
                                    <div class="accordion-body bg-light pt-2 pb-4">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 font-weight-bold text-secondary"><i class="bi bi-book"></i> Guru Mata Pelajaran</h6>
                                            <div>
                                                <button class="btn btn-sm btn-outline-secondary me-1" title="Edit Wali Kelas/Nama Rombel" data-bs-toggle="modal" data-bs-target="#modalEditRombel<?= $r['id'] ?>"><i class="bi bi-pencil-square"></i> Edit</button>

                                                <a href="<?= base_url('admin/rombel/siswa/' . $r['id']) ?>" class="btn btn-sm btn-info text-white shadow-sm me-1" title="Kelola Anggota Rombel">
    <i class="bi bi-people-fill"></i> Kelola Siswa
</a>
                                                
                                                <a href="<?= base_url('admin/rombel/delete/' . $r['id']) ?>" class="btn btn-sm btn-outline-danger me-2" onclick="return confirm('Apakah Anda yakin ingin menghapus Rombel ini? Pastikan tidak ada mapel yang tersisa.')" title="Hapus Kelas">
                                                    <i class="bi bi-trash"></i> Hapus Kelas
                                                </a>

                                                <button class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMapel<?= $r['id'] ?>"><i class="bi bi-plus-circle"></i> Tambah Mapel</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive bg-white rounded border">
                                            <table class="table table-hover table-sm mb-0 align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3" style="width: 50px;">No</th>
                                                        <th>Mata Pelajaran</th>
                                                        <th>Kelompok</th>
                                                        <th>Guru Pengampu</th>
                                                        <th class="text-center" style="width: 80px;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(empty($plottingMapel[$r['id']])): ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center text-muted py-3">Belum ada guru mata pelajaran yang di-plot di kelas ini.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php $no = 1; foreach($plottingMapel[$r['id']] as $plot): ?>
                                                            <tr>
                                                                <td class="ps-3 text-muted"><?= $no++ ?></td>
                                                                <td class="font-weight-bold"><?= esc($plot['subject_name']) ?></td>
                                                                <td><span class="badge bg-secondary opacity-75"><?= esc($plot['subject_group']) ?></span></td>
                                                                <td><?= esc($plot['nama_guru']) ?></td>
                                                                <td class="text-center">
                                                                    <a href="<?= base_url('admin/rombel/plot-delete/' . $plot['id']) ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Hapus plotting mapel ini?')" title="Hapus Plotting">
                                                                        <i class="bi bi-trash"></i>
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

                            <div class="modal fade" id="modalEditRombel<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-secondary text-white">
                                            <h6 class="modal-title font-weight-bold">📝 Edit Rombel & Wali Kelas</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="<?= base_url('admin/rombel/update/' . $r['id']) ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="small font-weight-bold mb-1">Nama Rombel</label>
                                                    <input type="text" name="rombel_name" class="form-control" value="<?= esc($r['rombel_name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small font-weight-bold mb-1">Wali Kelas (Opsional)</label>
                                                    <select name="homeroom_teacher_id" class="form-select">
                                                        <option value="">-- Belum Ditentukan --</option>
                                                        <?php foreach($walasList as $walas): ?>
                                                            <option value="<?= $walas['id'] ?>" <?= ($r['homeroom_teacher_id'] == $walas['id']) ? 'selected' : '' ?>><?= esc($walas['username']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm">💾 Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalTambahMapel<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h6 class="modal-title font-weight-bold">➕ Plotting Guru Mapel (<?= esc($r['rombel_name']) ?>)</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="<?= base_url('admin/rombel/plot-store') ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="rombel_id" value="<?= $r['id'] ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="small font-weight-bold mb-1">Mata Pelajaran</label>
                                                    <select name="master_subject_id" class="form-select" required>
                                                        <option value="">-- Pilih Mata Pelajaran --</option>
                                                        <?php foreach($masterSubjects as $ms): ?>
                                                            <option value="<?= $ms['id'] ?>"><?= esc($ms['subject_name']) ?> (<?= esc($ms['subject_group']) ?>)</option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small font-weight-bold mb-1">Guru Pengampu</label>
                                                    <select name="teacher_id" class="form-select" required>
                                                        <option value="">-- Pilih Guru --</option>
                                                        <?php foreach($guruList as $guru): ?>
                                                            <option value="<?= $guru['id'] ?>"><?= esc($guru['username']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success btn-sm">💾 Simpan Plotting</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <div class="modal fade" id="modalTambahRombel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title font-weight-bold">➕ Tambah Rombel Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/rombel/store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="academic_year_id" value="<?= esc($selectedTaId ?? '') ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Tingkat Kelas (Master)</label>
                            <select name="master_class_id" class="form-select" required>
                                <option value="">-- Pilih Tingkat --</option>
                                <?php foreach($masterClasses as $mc): ?>
                                    <option value="<?= $mc['id'] ?>">Tingkat <?= esc($mc['class_name']) ?> (<?= esc($mc['level_type']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Nama Spesifik Rombel</label>
                            <input type="text" name="rombel_name" class="form-control" required placeholder="Misal: 7A, 7-Al Farabi, X MIPA 1">
                        </div>
                        <div class="mb-3">
                            <label class="small font-weight-bold mb-1">Wali Kelas (Opsional)</label>
                            <select name="homeroom_teacher_id" class="form-select">
                                <option value="">-- Belum Ditentukan --</option>
                                <?php foreach($walasList as $walas): ?>
                                    <option value="<?= $walas['id'] ?>"><?= esc($walas['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">💾 Simpan Rombel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCopyRombel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title font-weight-bold"><i class="bi bi-magic"></i> Salin Struktur Rombel & Mapel</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/rombel/copy') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_academic_year_id" value="<?= esc($selectedTaId ?? '') ?>">
                    
                    <div class="modal-body">
                        <p class="mb-3">Apakah Anda yakin ingin menyalin seluruh data Rombongan Belajar (Kelas) beserta Plotting Guru Mata Pelajaran dari <strong>Semester Sebelumnya</strong> ke Semester ini?</p>
                        <div class="alert alert-info small mb-0 py-2 border-0 shadow-sm">
                            <i class="bi bi-info-circle-fill"></i> <strong>Sistem Otomatis:</strong> Sesuai instruksi kebijakan, data Wali Kelas pada semester baru ini akan otomatis dikosongkan terlebih dahulu agar Anda dapat memetakan penugasan barunya secara mandiri.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold">🪄 Ya, Salin Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

</body>
</html>