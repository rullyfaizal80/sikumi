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
    <div class="container-fluid" style="max-width: 800px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;"><i class="fas fa-edit mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Perbarui informasi jadwal ujian atau reset token jika terjadi kebocoran.</p>
            </div>
            <div>
                <a href="<?= base_url('asesmen/admin') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Form Edit Jadwal -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="<?= base_url('asesmen/admin/update/' . $jadwal['id']) ?>" method="POST">
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Kategori Ujian</label>
                        <select name="exam_category_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori_ujian as $kategori): ?>
                                <option value="<?= $kategori['id'] ?>" <?= ($jadwal['exam_category_id'] == $kategori['id']) ? 'selected' : '' ?>>
                                    <?= esc($kategori['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Target Kelas / Rombel</label>
                            <select name="rombel_id" class="form-control" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach($rombels as $rombel): ?>
                                    <option value="<?= $rombel['id'] ?>" <?= ($jadwal['rombel_id'] == $rombel['id']) ? 'selected' : '' ?>>
                                        <?= esc($rombel['rombel_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Mata Pelajaran</label>
                            <select name="subject_id" class="form-control" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" <?= ($jadwal['subject_id'] == $subject['id']) ? 'selected' : '' ?>>
                                        <?= esc($subject['subject_name']) ?> (<?= esc($subject['subject_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Link URL Google Form</label>
                        <input type="url" name="link_gform" class="form-control" value="<?= esc($jadwal['link_gform']) ?>" required>
                    </div>

                    <div class="row">
                        <!-- Format waktu untuk input type="datetime-local" adalah YYYY-MM-DDThh:mm -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Waktu Mulai</label>
                            <input type="datetime-local" name="waktu_mulai" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($jadwal['waktu_mulai'])) ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Waktu Selesai</label>
                            <input type="datetime-local" name="waktu_selesai" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($jadwal['waktu_selesai'])) ?>" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Durasi (Menit)</label>
                            <input type="number" name="durasi_menit" class="form-control" value="<?= esc($jadwal['durasi_menit']) ?>" required>
                        </div>
                    </div>

                    <hr>

                    <!-- Alert Info Token Saat Ini -->
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-key mr-2"></i> Token Ujian saat ini: <strong><?= esc($jadwal['token']) ?></strong>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="isActiveSwitch" name="is_active" value="1" <?= $jadwal['is_active'] ? 'checked' : '' ?>>
                                <label class="custom-control-label font-weight-bold text-success" for="isActiveSwitch">Aktifkan Ujian?</label>
                            </div>
                        </div>
                        <div class="col-md-6 border-left">
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="resetTokenSwitch" name="reset_token" value="1">
                                <label class="custom-control-label font-weight-bold text-danger" for="resetTokenSwitch">Generate Ulang Token Baru?</label>
                                <small class="d-block text-muted">Centang jika token lama bocor ke siswa yang belum waktunya ujian.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Update Data Ujian
                    </button>
                </form>
            </div>
        </div>

    </div>
</body>
</html>