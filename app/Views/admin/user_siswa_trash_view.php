<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SiKuMi - Arsip Siswa Non-Aktif</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-light p-4">

    <div class="container-fluid">
        
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card card-outline card-danger shadow-sm">
            
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column">
                        <h5 class="card-title text-danger font-weight-bold m-0">
                            <i class="fas fa-trash-alt mr-2"></i> Gudang Arsip Akun Siswa (Trash)
                        </h5>
                        <p class="text-muted small m-0 mt-1">
                            Daftar akun siswa lama yang dinonaktifkan (Soft Delete) karena lulus, pindah, atau keluar.
                        </p>
                    </div>
                    
                    <a href="<?= base_url('admin/users/siswa-tes') ?>" class="btn btn-sm btn-secondary font-weight-bold shadow-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Kelola Siswa
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered m-0 small">
                        <thead class="bg-dark text-white text-center">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Nama Akun / Siswa</th>
                                <th style="width: 15%;">NISN / NIS</th>
                                <th>Email Login</th>
                                <th style="width: 20%;">Tanggal Dihapus</th>
                                <th style="width: 15%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftarTrash)): ?>
                                <?php $no = 1; foreach ($daftarTrash as $row): ?>
                                    <tr>
                                        <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                        <td><strong class="text-danger"><?= esc($row['username']) ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?= esc($row['nisn'] ?? '-') ?></span>
                                            <div class="text-muted text-xs mt-1"><?= esc($row['nis'] ?? '-') ?></div>
                                        </td>
                                        <td><?= esc($row['email'] ?? '-') ?></td>
                                        <td class="text-center text-muted">
                                            <i class="far fa-clock mr-1"></i> <?= date('d M Y - H:i', strtotime($row['deleted_at'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('admin/users/siswa-restore/' . $row['id']) ?>" 
                                               class="btn btn-xs btn-success font-weight-bold text-white shadow-sm px-2" 
                                               style="font-size: 0.8rem;"
                                               onclick="return confirm('Pulihkan kembali akun siswa ini agar aktif di madrasah?')">
                                                <i class="fas fa-undo mr-1"></i> Restore Akun
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?> 
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open fa-2x d-block mb-2 text-gray"></i>
                                        <span class="font-style-italic font-weight-bold">Gudang arsip kosong. Tidak ada data akun siswa di dalam Trash.</span>
                                    </td>
                                </tr> 
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>