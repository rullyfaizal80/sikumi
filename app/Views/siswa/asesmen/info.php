<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container" style="max-width: 900px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;"><i class="fas fa-bullhorn mr-2"></i> Papan Informasi Asesmen</h3>
                <p class="text-muted small mb-0">Lihat Jadwal dan unduh Kisi-kisi berdasarkan kelas Anda.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

       <!-- Panel Jadwal Bergambar (Thumbnail) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white rounded text-center py-4">
                <h5 class="font-weight-bold mb-4 border-bottom pb-3">Jadwal Pelaksanaan Ujian</h5>
                
                <?php if(empty($info_asesmen['gambar_jadwal_tulis']) && empty($info_asesmen['gambar_jadwal_praktik'])): ?>
                    <p class="text-muted mb-0">Jadwal belum diunggah oleh Admin.</p>
                <?php else: ?>
                    <div class="row justify-content-center">
                        
                        <!-- Thumbnail Jadwal Tulis -->
                        <?php if(!empty($info_asesmen['gambar_jadwal_tulis'])): ?>
                        <div class="col-md-5 mb-4 mb-md-0">
                            <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-calendar-check mr-1"></i> Jadwal Ujian Tulis</h6>
                            
                            <!-- Area gambar yang bisa diklik (memicu modal) -->
                            <div class="d-inline-block" data-toggle="modal" data-target="#modalJadwalTulis" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                <img src="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_tulis']) ?>" alt="Jadwal Tulis" class="img-thumbnail shadow-sm rounded" style="max-height: 250px; object-fit: cover;">
                                <div class="mt-2 text-primary small font-weight-bold">
                                    <i class="fas fa-search-plus"></i> Klik gambar untuk memperbesar
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Thumbnail Jadwal Praktik -->
                        <?php if(!empty($info_asesmen['gambar_jadwal_praktik'])): ?>
                        <div class="col-md-5">
                            <h6 class="font-weight-bold text-success mb-3"><i class="fas fa-tools mr-1"></i> Jadwal Ujian Praktik</h6>
                            
                            <!-- Area gambar yang bisa diklik (memicu modal) -->
                            <div class="d-inline-block" data-toggle="modal" data-target="#modalJadwalPraktik" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                                <img src="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_praktik']) ?>" alt="Jadwal Praktik" class="img-thumbnail shadow-sm rounded" style="max-height: 250px; object-fit: cover;">
                                <div class="mt-2 text-success small font-weight-bold">
                                    <i class="fas fa-search-plus"></i> Klik gambar untuk memperbesar
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Card Kisi-kisi -->
        <div class="row">
            <!-- Kelas 7 -->
            <div class="col-md-4 mb-3">
                <div class="bg-white text-dark p-3 rounded shadow-sm h-100 border-top border-info border-3">
                    <h6 class="font-weight-bold text-center border-bottom pb-2 mb-3">KISI-KISI KELAS 7</h6>
                    <div class="d-flex flex-column">
                        <a href="<?= esc($info_asesmen['link_kisi_tulis_7'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2 <?= empty($info_asesmen['link_kisi_tulis_7']) ? 'disabled' : '' ?>"><i class="fas fa-file-alt mr-1"></i> Ujian Tulis</a>
                        <a href="<?= esc($info_asesmen['link_kisi_praktik_7'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-success <?= empty($info_asesmen['link_kisi_praktik_7']) ? 'disabled' : '' ?>"><i class="fas fa-flask mr-1"></i> Ujian Praktik</a>
                    </div>
                </div>
            </div>
            <!-- Kelas 8 -->
            <div class="col-md-4 mb-3">
                <div class="bg-white text-dark p-3 rounded shadow-sm h-100 border-top border-info border-3">
                    <h6 class="font-weight-bold text-center border-bottom pb-2 mb-3">KISI-KISI KELAS 8</h6>
                    <div class="d-flex flex-column">
                        <a href="<?= esc($info_asesmen['link_kisi_tulis_8'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2 <?= empty($info_asesmen['link_kisi_tulis_8']) ? 'disabled' : '' ?>"><i class="fas fa-file-alt mr-1"></i> Ujian Tulis</a>
                        <a href="<?= esc($info_asesmen['link_kisi_praktik_8'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-success <?= empty($info_asesmen['link_kisi_praktik_8']) ? 'disabled' : '' ?>"><i class="fas fa-flask mr-1"></i> Ujian Praktik</a>
                    </div>
                </div>
            </div>
            <!-- Kelas 9 -->
            <div class="col-md-4 mb-3">
                <div class="bg-white text-dark p-3 rounded shadow-sm h-100 border-top border-info border-3">
                    <h6 class="font-weight-bold text-center border-bottom pb-2 mb-3">KISI-KISI KELAS 9</h6>
                    <div class="d-flex flex-column">
                        <a href="<?= esc($info_asesmen['link_kisi_tulis_9'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-2 <?= empty($info_asesmen['link_kisi_tulis_9']) ? 'disabled' : '' ?>"><i class="fas fa-file-alt mr-1"></i> Ujian Tulis</a>
                        <a href="<?= esc($info_asesmen['link_kisi_praktik_9'] ?: '#') ?>" target="_blank" class="btn btn-sm btn-outline-success <?= empty($info_asesmen['link_kisi_praktik_9']) ? 'disabled' : '' ?>"><i class="fas fa-flask mr-1"></i> Ujian Praktik</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

   <!-- MODAL JADWAL TULIS -->
    <?php if(!empty($info_asesmen['gambar_jadwal_tulis'])): ?>
    <div class="modal fade" id="modalJadwalTulis" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-calendar-check mr-2"></i> Jadwal Ujian Tulis</h5>
                    <!-- Penambahan opacity: 1 dan text-shadow: none agar putih solid -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 15px; color: red; opacity: 1; text-shadow: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 text-center bg-dark">
                    <!-- Path diperbaiki: tanpa public/ -->
                    <img src="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_tulis']) ?>" alt="Jadwal Tulis" class="img-fluid w-100">
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <!-- Path diperbaiki: tanpa public/ -->
                    <a href="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_tulis']) ?>" download="Jadwal_Tulis.jpg" class="btn btn-success">
                        <i class="fas fa-download mr-1"></i> Unduh Jadwal
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL JADWAL PRAKTIK -->
    <?php if(!empty($info_asesmen['gambar_jadwal_praktik'])): ?>
    <div class="modal fade" id="modalJadwalPraktik" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-tools mr-2"></i> Jadwal Ujian Praktik</h5>
                    <!-- Penambahan opacity: 1 dan text-shadow: none agar putih solid -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 15px; color: red; opacity: 1; text-shadow: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 text-center bg-dark">
                    <!-- Path diperbaiki: tanpa public/ -->
                    <img src="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_praktik']) ?>" alt="Jadwal Praktik" class="img-fluid w-100">
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <!-- Path diperbaiki: tanpa public/ -->
                    <a href="<?= base_url('uploads/asesmen/' . $info_asesmen['gambar_jadwal_praktik']) ?>" download="Jadwal_Praktik.jpg" class="btn btn-success">
                        <i class="fas fa-download mr-1"></i> Unduh Jadwal
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>