<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Input Absensi Harian</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1200px;">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">📝 Form Absensi Harian</h3>
                <p class="text-muted small mb-0">
                    Kelas: <strong><?= esc($rombel['rombel_name'] ?? 'Tidak Diketahui') ?></strong> | 
                    Tahun Ajaran: <strong><?= esc($taAktif['academic_year'] ?? '') ?> - <?= esc($taAktif['semester'] ?? '') ?></strong>
                </p>
            </div>
            <div>
                <a href="<?= base_url('admin/absensi') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Kelas
                </a>
            </div>
        </div>

        <!-- Flash Message -->
        <?php if (session()->getFlashdata('sukses')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
                <?= session()->getFlashdata('sukses') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-3" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Form Induk -->
        <form action="<?= base_url('admin/absensi/store') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="rombel_id" value="<?= esc($rombel['id']) ?>">
            <input type="hidden" name="academic_year_id" value="<?= esc($taAktif['id']) ?>">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    
                    <!-- Kiri: Judul -->
                    <h5 class="mb-0" style="font-weight: 600;">Daftar Siswa</h5>

                    <!-- Kanan: Label, Input Tanggal, & Badge Status -->
                    <div class="d-flex align-items-center">
                        <!-- Label (anti-lipat 2 baris) -->
                        <label class="mb-0 font-weight-bold small text-muted" style="white-space: nowrap; margin-right: 10px;">
                            Tanggal Absensi:
                        </label>
                        
                        <!-- Input Tanggal (diperbesar sedikit) -->
                        <input type="date" name="tanggal" class="form-control form-control-sm" 
                               value="<?= esc($tanggal) ?>" required style="width: 150px; margin-right: 15px;" 
                               data-url="<?= base_url('admin/absensi/input/' . esc($rombel['id'])) ?>?tanggal="
                               onchange="window.location.href = this.dataset.url + this.value">

                        <!-- Badge Status -->
                        <?php if (!empty($absensiDetails)): ?>
                            <span class="badge" style="background-color: #28a745; color: white; font-size: 0.85rem; padding: 6px 12px; border-radius: 5px; white-space: nowrap;">
                                <i class="fas fa-check-circle"></i> Sudah Disimpan
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background-color: #6c757d; color: white; font-size: 0.85rem; padding: 6px 12px; border-radius: 5px; white-space: nowrap;">
                                <i class="fas fa-clock"></i> Belum Disimpan
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
                
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Nama Lengkap</th>
                                <th style="width: 250px;">Status Kehadiran</th>
                                <th style="width: 200px;">Keterlambatan (Hadir)</th>
                                <th>Keterangan Tambahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaKelas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada siswa yang dimasukkan ke kelas ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswaKelas as $siswa): ?>
                                    <?php 
                                        $studentId = esc($siswa['student_id']);
                                        $status = 'H';
                                        $terlambat = 0;
                                        $keterangan = '';
                                        
                                        if (isset($absensiDetails[$studentId])) {
                                            $detail = $absensiDetails[$studentId];
                                            $status = $detail['status'];
                                            $terlambat = (int) $detail['keterlambatan_menit'];
                                            $keterangan = $detail['keterangan'];
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                        <td><strong><?= esc($siswa['username']) ?></strong></td>
                                        
                                        <!-- Radio Status Kehadiran (PHP dipindah ke data-studentid) -->
                                        <td>
                                            <div class="d-flex gap-3">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input status-radio" type="radio" name="siswa[<?= $studentId ?>][status]" value="H" id="H_<?= $studentId ?>" data-studentid="<?= $studentId ?>" onchange="checkStatus(this)" <?= ($status === 'H') ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-success font-weight-bold" for="H_<?= $studentId ?>">H</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input status-radio" type="radio" name="siswa[<?= $studentId ?>][status]" value="S" id="S_<?= $studentId ?>" data-studentid="<?= $studentId ?>" onchange="checkStatus(this)" <?= ($status === 'S') ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-warning font-weight-bold" for="S_<?= $studentId ?>">S</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input status-radio" type="radio" name="siswa[<?= $studentId ?>][status]" value="I" id="I_<?= $studentId ?>" data-studentid="<?= $studentId ?>" onchange="checkStatus(this)" <?= ($status === 'I') ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-info font-weight-bold" for="I_<?= $studentId ?>">I</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input status-radio" type="radio" name="siswa[<?= $studentId ?>][status]" value="A" id="A_<?= $studentId ?>" data-studentid="<?= $studentId ?>" onchange="checkStatus(this)" <?= ($status === 'A') ? 'checked' : '' ?>>
                                                    <label class="form-check-label text-danger font-weight-bold" for="A_<?= $studentId ?>">A</label>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Ceklis Terlambat & Input Menit (PHP dipindah ke data-studentid) -->
                                        <td>
                                            <div class="form-check mb-1">
                                                <input class="form-check-input ceklis-terlambat" type="checkbox" id="ceklis_terlambat_<?= $studentId ?>" data-studentid="<?= $studentId ?>" onchange="toggleTerlambat(this)" <?= ($terlambat > 0) ? 'checked' : '' ?> <?= ($status !== 'H') ? 'disabled' : '' ?>>
                                                <label class="form-check-label small" for="ceklis_terlambat_<?= $studentId ?>">Siswa Terlambat?</label>
                                            </div>
                                            <div id="box_menit_<?= $studentId ?>" style="display: <?= ($terlambat > 0) ? 'block' : 'none' ?>;">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="siswa[<?= $studentId ?>][terlambat]" id="input_menit_<?= $studentId ?>" class="form-control" placeholder="0" min="1" value="<?= ($terlambat > 0) ? $terlambat : '' ?>" <?= ($terlambat > 0) ? 'required' : '' ?>>
                                                    <span class="input-group-text">Menit</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Input Keterangan -->
                                        <td>
                                            <input type="text" name="siswa[<?= $studentId ?>][keterangan]" class="form-control form-control-sm" placeholder="Opsional..." value="<?= esc($keterangan) ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white text-end py-3">
                    <!-- Tombol Hapus: Hanya muncul jika data absensi sudah ada -->
                    <?php if (!empty($absensiDetails)): ?>
                        <a href="<?= base_url('admin/absensi/delete/' . esc($rombel['id']) . '/' . esc($tanggal)) ?>" 
                           class="btn btn-danger font-weight-bold" 
                           style="margin-right: 10px;"
                           onclick="return confirm('⚠️ PERINGATAN!\n\nApakah Anda yakin ingin menghapus SELURUH data absensi kelas ini pada tanggal <?= esc($tanggal) ?>?\n\nData yang dihapus tidak dapat dikembalikan.');">
                            🗑️ Hapus Data Absensi
                        </a>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-warning text-white font-weight-bold" <?= empty($siswaKelas) ? 'disabled' : '' ?>>
                        💾 Simpan Data Absensi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        function toggleTerlambat(checkbox) {
            // Mengambil ID siswa dari atribut data-studentid (Bersih tanpa PHP)
            const studentId = checkbox.dataset.studentid; 
            const boxMenit = document.getElementById('box_menit_' + studentId);
            const inputMenit = document.getElementById('input_menit_' + studentId);

            if (checkbox.checked) {
                boxMenit.style.display = 'block';
                inputMenit.required = true;
            } else {
                boxMenit.style.display = 'none';
                inputMenit.value = '';
                inputMenit.required = false;
            }
        }

        function checkStatus(radio) {
            // Mengambil ID siswa dari atribut data-studentid (Bersih tanpa PHP)
            const studentId = radio.dataset.studentid;
            const ceklis = document.getElementById('ceklis_terlambat_' + studentId);
            const boxMenit = document.getElementById('box_menit_' + studentId);
            const inputMenit = document.getElementById('input_menit_' + studentId);

            if (radio.value !== 'H') {
                ceklis.checked = false;
                ceklis.disabled = true;
                boxMenit.style.display = 'none';
                inputMenit.value = '';
                inputMenit.required = false;
            } else {
                ceklis.disabled = false;
            }
        }
    </script>
</body>
</html>