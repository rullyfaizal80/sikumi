<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 text-info font-weight-bold"><i class="fas fa-bullhorn mr-2"></i> <?= esc($title) ?></h3>
            <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
        </div>

        <form action="<?= base_url('asesmen/admin/update-info') ?>" method="POST" enctype="multipart/form-data">
            
            <!-- PANEL 1: JADWAL UJIAN (GAMBAR) -->
            <div class="card shadow-sm mb-4 border-top-primary">
                <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold">1. Upload Jadwal Ujian (Gambar)</h5></div>
                <div class="card-body row">
                    <div class="col-md-6 border-right">
                        <label class="font-weight-bold text-primary">Jadwal Ujian Tulis</label>
                        <?php if(!empty($info['gambar_jadwal_tulis'])): ?>
                            <div class="mb-2"><img src="<?= base_url('uploads/asesmen/' . $info['gambar_jadwal_tulis']) ?>" class="img-thumbnail" style="max-height: 150px;"></div>
                        <?php endif; ?>
                        <input type="file" name="gambar_jadwal_tulis" class="form-control-file" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold text-success">Jadwal Ujian Praktik</label>
                        <?php if(!empty($info['gambar_jadwal_praktik'])): ?>
                            <div class="mb-2"><img src="<?= base_url('uploads/asesmen/' . $info['gambar_jadwal_praktik']) ?>" class="img-thumbnail" style="max-height: 150px;"></div>
                        <?php endif; ?>
                        <input type="file" name="gambar_jadwal_praktik" class="form-control-file" accept="image/*">
                    </div>
                </div>
            </div>

            <!-- PANEL 2: KISI-KISI UJIAN (LINK GDRIVE) -->
            <div class="card shadow-sm mb-4 border-top-warning">
                <div class="card-header bg-white"><h5 class="mb-0 font-weight-bold">2. Link Folder Kisi-kisi (Google Drive)</h5></div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kelas 7 -->
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-center border-bottom pb-2">KELAS 7</h6>
                                <label class="small mt-2">Link Kisi-kisi Tulis</label>
                                <input type="url" name="link_kisi_tulis_7" class="form-control form-control-sm mb-2" value="<?= esc($info['link_kisi_tulis_7'] ?? '') ?>">
                                <label class="small">Link Kisi-kisi Praktik</label>
                                <input type="url" name="link_kisi_praktik_7" class="form-control form-control-sm" value="<?= esc($info['link_kisi_praktik_7'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Kelas 8 -->
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-center border-bottom pb-2">KELAS 8</h6>
                                <label class="small mt-2">Link Kisi-kisi Tulis</label>
                                <input type="url" name="link_kisi_tulis_8" class="form-control form-control-sm mb-2" value="<?= esc($info['link_kisi_tulis_8'] ?? '') ?>">
                                <label class="small">Link Kisi-kisi Praktik</label>
                                <input type="url" name="link_kisi_praktik_8" class="form-control form-control-sm" value="<?= esc($info['link_kisi_praktik_8'] ?? '') ?>">
                            </div>
                        </div>
                        <!-- Kelas 9 -->
                        <div class="col-md-4 mb-3">
                            <div class="p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-center border-bottom pb-2">KELAS 9</h6>
                                <label class="small mt-2">Link Kisi-kisi Tulis</label>
                                <input type="url" name="link_kisi_tulis_9" class="form-control form-control-sm mb-2" value="<?= esc($info['link_kisi_tulis_9'] ?? '') ?>">
                                <label class="small">Link Kisi-kisi Praktik</label>
                                <input type="url" name="link_kisi_praktik_9" class="form-control form-control-sm" value="<?= esc($info['link_kisi_praktik_9'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold shadow"><i class="fas fa-save mr-2"></i> Simpan Semua Perubahan</button>
        </form>
    </div>
</body>
</html>