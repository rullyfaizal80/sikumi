<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Kelola Anggota Kelas</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        .scrollable-table { max-height: 400px; overflow-y: auto; }
        /* Kustomisasi scrollbar agar cantik */
        .scrollable-table::-webkit-scrollbar { width: 6px; }
        .scrollable-table::-webkit-scrollbar-thumb { background: #adb5bd; border-radius: 10px; }
    </style>
</head>
<body class="layout-fixed bg-body-tertiary">
    <div class="app-wrapper">
        
        <nav class="app-header navbar navbar-expand bg-white shadow-sm">
            <div class="container-fluid">
                <a href="<?= base_url('admin/rombel') ?>" class="btn btn-sm btn-outline-secondary font-weight-bold">
                    <i class="bi bi-arrow-left"></i> Kembali ke Rombel
                </a>
                <h5 class="navbar-text my-0 mx-auto font-weight-bold text-primary">
                    <i class="bi bi-people-fill"></i> Kelola Siswa: Kelas <?= esc($rombel['rombel_name']) ?> (Tingkat <?= esc($rombel['tingkat']) ?>)
                </h5>
            </div>
        </nav>

        <main class="app-main pt-4">
            <div class="container-fluid px-4">
                
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

                <div class="row g-4 mt-1">
                    
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 border-top border-warning border-3 h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold"><i class="bi bi-person-exclamation text-warning"></i> Siswa Belum Dapat Kelas</h6>
                                <span class="badge bg-warning text-dark"><?= count($siswaBebas) ?> Siswa</span>
                            </div>
                            <form action="<?= base_url('admin/rombel/siswa/add') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="rombel_id" value="<?= $rombel['id'] ?>">
                                
                                <div class="card-body p-0 scrollable-table">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead class="table-light sticky-top shadow-sm">
                                            <tr>
                                                <th class="ps-3" style="width: 40px;">
                                                    <input type="checkbox" class="form-check-input border-secondary" id="checkAllKiri" onclick="toggleChecks('checkAllKiri', 'student_ids[]')">
                                                </th>
                                                <th>Nama Siswa (Pilih)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($siswaBebas)): ?>
                                                <tr><td colspan="2" class="text-center text-muted py-4">Semua siswa di angkatan ini sudah masuk kelas.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($siswaBebas as $sb): ?>
                                                    <tr>
                                                        <td class="ps-3"><input type="checkbox" name="student_ids[]" value="<?= $sb['id'] ?>" class="form-check-input border-secondary"></td>
                                                        <td class="font-weight-bold text-secondary"><?= esc($sb['username']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer bg-light text-end">
                                    <button type="submit" class="btn btn-primary font-weight-bold shadow-sm" <?= empty($siswaBebas) ? 'disabled' : '' ?>>
                                        Pindahkan ke Kelas Kanan <i class="bi bi-arrow-right-circle-fill"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 border-top border-success border-3 h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 font-weight-bold"><i class="bi bi-person-check-fill text-success"></i> Anggota Kelas <?= esc($rombel['rombel_name']) ?></h6>
                                <span class="badge bg-success"><?= count($siswaTerdaftar) ?> Siswa</span>
                            </div>
                            <form action="<?= base_url('admin/rombel/siswa/remove') ?>" method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="rombel_id" value="<?= $rombel['id'] ?>">

                                <div class="card-body p-0 scrollable-table">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light sticky-top shadow-sm">
                                            <tr>
                                                <th class="ps-3" style="width: 40px;">
                                                    <input type="checkbox" class="form-check-input border-secondary" id="checkAllKanan" onclick="toggleChecks('checkAllKanan', 'plot_ids[]')">
                                                </th>
                                                <th>Nama Siswa Terdaftar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(empty($siswaTerdaftar)): ?>
                                                <tr><td colspan="2" class="text-center text-muted py-4">Belum ada siswa di kelas ini.</td></tr>
                                            <?php else: ?>
                                                <?php foreach($siswaTerdaftar as $st): ?>
                                                    <tr>
                                                        <td class="ps-3"><input type="checkbox" name="plot_ids[]" value="<?= $st['plot_id'] ?>" class="form-check-input border-danger"></td>
                                                        <td class="font-weight-bold"><?= esc($st['username']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer bg-light text-start">
                                    <button type="submit" class="btn btn-outline-danger font-weight-bold" <?= empty($siswaTerdaftar) ? 'disabled' : '' ?> onclick="return confirm('Siswa yang dikeluarkan akan kembali ke daftar Siswa Bebas. Lanjutkan?')">
                                        <i class="bi bi-arrow-left-circle-fill"></i> Keluarkan Siswa (Kiri)
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </main>
        
        <script>
            function toggleChecks(sourceId, name) {
                let checkboxes = document.getElementsByName(name);
                let source = document.getElementById(sourceId);
                for(var i=0, n=checkboxes.length;i<n;i++) {
                    checkboxes[i].checked = source.checked;
                }
            }
        </script>

    </body>
    </html>