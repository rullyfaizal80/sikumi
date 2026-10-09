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
        
        <!-- Flash Message -->
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Header Portal -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;"><i class="fas fa-graduation-cap mr-2"></i> Portal Asesmen Siswa</h3>
                <p class="text-muted small mb-0">Kelas: <strong><?= esc($nama_kelas) ?></strong> | Tahun Ajaran: <strong><?= esc($tahun_aktif['academic_year'] ?? '-') ?> (<?= esc($tahun_aktif['semester'] ?? '-') ?>)</strong></p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Daftar Card Ujian -->
        <div class="row">
            <?php if (empty($jadwal_ujian)): ?>
                <div class="col-12">
                    <div class="card shadow-sm border-0 text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada jadwal ujian untuk kelas Anda saat ini.</h5>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($jadwal_ujian as $ujian): ?>
                    <?php 
                        // Ubah string waktu menjadi timestamp angka agar komparasinya akurat
                        $waktu_sekarang_time = strtotime($waktu_sekarang);
                        $waktu_mulai_time = strtotime($ujian['waktu_mulai']);
                        $waktu_selesai_time = strtotime($ujian['waktu_selesai']);

                        $status_badge = '';
                        $status_class = '';
                        $is_clickable = false;

                        if ($waktu_sekarang_time < $waktu_mulai_time) {
                            $status_badge = 'Belum Dimulai';
                            $status_class = 'badge-warning text-dark'; // Teks hitam agar tidak hilang di background kuning
                        } elseif ($waktu_sekarang_time >= $waktu_mulai_time && $waktu_sekarang_time <= $waktu_selesai_time) {
                            $status_badge = 'Aktif / Siap Diikuti';
                            $status_class = 'badge-success text-white'; 
                            $is_clickable = true;
                        } else {
                            $status_badge = 'Kadaluarsa / Selesai';
                            $status_class = 'badge-danger text-white'; 
                        }
                    ?>

                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100" style="border-top: 4px solid <?= $waktu_sekarang_time < $waktu_mulai_time ? '#ffc107' : ($is_clickable ? '#28a745' : '#dc3545') ?>;">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <!-- Perbaikan Kategori agar berwarna biru teks (tidak hilang) -->
                                        <strong class="text-primary" style="font-size: 14px;">
                                            <i class="fas fa-tags mr-1"></i> <?= esc($ujian['nama_kategori'] ?? 'Ujian') ?>
                                        </strong>
                                        <span class="badge <?= $status_class ?> px-2 py-1" style="font-size: 13px;"><?= $status_badge ?></span>
                                    </div>
                                    
                                    <h5 class="card-title font-weight-bold text-dark mb-1"><?= esc($ujian['subject_name']) ?></h5>
                                    <p class="text-muted small mb-3"><i class="far fa-clock mr-1"></i> Durasi: <strong><?= esc($ujian['durasi_menit']) ?> Menit</strong></p>
                                    
                                    <hr class="my-2">
                                    
                                    <p class="mb-1 small"><strong>Mulai:</strong> <?= date('d M Y, H:i', strtotime($ujian['waktu_mulai'])) ?></p>
                                    <p class="mb-3 small"><strong>Selesai:</strong> <?= date('d M Y, H:i', strtotime($ujian['waktu_selesai'])) ?></p>
                                </div>

                                <!-- Tombol Aksi Masuk Ujian -->
                                <div>
                                    <?php if ($is_clickable): ?>
                                        <button type="button" class="btn btn-success btn-block font-weight-bold btn-mulai" data-toggle="modal" data-target="#tokenModal" data-id="<?= $ujian['id'] ?>" data-mapel="<?= esc($ujian['subject_name']) ?>">
                                            <i class="fas fa-key mr-1"></i> Masukkan Token & Mulai
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary btn-block font-weight-bold" disabled>
                                            <i class="fas lock mr-1"></i> Ujian Terkunci
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- MODAL INPUT TOKEN -->
    <div class="modal fade" id="tokenModal" tabindex="-1" aria-labelledby="tokenModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('siswa/asesmen/validate-token') ?>" method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold" id="tokenModalLabel"><i class="fas fa-lock mr-2"></i>Validasi Token Ujian</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="text-muted mb-3">Mata Pelajaran: <strong id="modalMapel" class="text-dark"></strong></p>
                        
                        <input type="hidden" name="exam_schedule_id" id="modalExamId">
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Masukkan 6 Karakter Token</label>
                            <input type="text" name="token" class="form-control text-center text-uppercase font-weight-bold" style="font-size: 24px; letter-spacing: 5px;" maxlength="6" placeholder="______" required autocomplete="off">
                            <small class="text-muted mt-2 d-block">Token diberikan oleh Pengawas / Guru Pengampu.</small>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary font-weight-bold px-4" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success font-weight-bold px-4"><i class="fas fa-sign-in-alt mr-1"></i> Mulai Ujian (Fullscreen)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script untuk menangkap ID Ujian dan Nama Mapel saat tombol diklik
        $('#tokenModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var examId = button.data('id');
            var mapel = button.data('mapel');
            
            var modal = $(this);
            modal.find('#modalExamId').val(examId);
            modal.find('#modalMapeltext').text(mapel);
            modal.find('#modalMapel').text(mapel);
        });
    </script>
</body>
</html>