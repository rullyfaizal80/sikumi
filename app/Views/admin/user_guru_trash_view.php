<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SiKuMi - Arsip Guru Non-Aktif</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-light p-4">

    <div class="container-fluid">
        <div class="card card-outline card-danger shadow-sm">
            
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
    <div class="d-flex flex-column">
        <h5 class="card-title text-danger font-weight-bold m-0">
            <i class="fas fa-trash-alt mr-2"></i> Gudang Arsip Akun Guru (Trash)
        </h5>
        <p class="text-muted small m-0 mt-1">
            Daftar akun guru lama yang sudah dinonaktifkan atau keluar dari madrasah.
        </p>
    </div>
    
    <a href="<?= base_url('admin/users/guru-tes') ?>" class="btn btn-sm btn-secondary font-weight-bold shadow-sm">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Kelola Guru
    </a>
</div>
            </div>

            <div class="card-body">
                
                <?php if (session()->getFlashdata('error')) : ?>
                    <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                        <i class="icon fas fa-ban mr-2"></i> <?= session()->getFlashdata('error') ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover table-sm align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Nama Lengkap Guru</th>
                                <th>Email Resmi</th>
                                <th style="width: 180px;">NIP</th>
                                <th style="width: 200px;">Tanggal Dihapus</th>
                                <th style="width: 160px;">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($daftarTrash)): ?>
                                <?php $no = 1; foreach ($daftarTrash as $row): ?>
                                    <tr>
                                        <td class="text-center font-weight-bold text-muted"><?= $no++ ?></td>
                                        <td><strong class="text-secondary"><?= esc($row['username']) ?></strong></td>
                                        <td><?= esc($row['email']) ?></td>
                                        <td class="text-center font-monospace"><?= esc($row['nip'] ?? '-') ?></td>
                                        <td class="text-center text-danger small font-weight-bold">
                                            <i class="far fa-calendar-alt mr-1"></i> <?= date('d-m-Y (H:i)', strtotime($row['deleted_at'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= base_url('admin/users/guru-restore/' . $row['id']) ?>" 
                                               class="btn btn-xs btn-success font-weight-bold text-white shadow-sm px-2" 
                                               style="font-size: 0.8rem;"
                                               onclick="return confirm('Pulihkan akun guru ini agar bisa aktif kembali di madrasah?')">
                                                <i class="fas fa-undo mr-1"></i> Restore Akun
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?> <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open fa-2x d-block mb-2 text-gray"></i>
                                        <span class="font-style-italic font-weight-bold">Gudang arsip kosong. Tidak ada data akun guru yang dinonaktifkan.</span>
                                    </td>
                                </tr> <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>