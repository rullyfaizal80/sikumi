<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>
        // Fungsi untuk reload halaman saat ganti Rombel
        function gantiRombel() {
            let rombelId = document.getElementById('filter_rombel').value;
            let url = "<?= base_url('guru/quran_kelompok/create') ?>";
            if (rombelId) {
                url += "?rombel_id=" + rombelId;
            }
            window.location.href = url;
        }
    </script>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-plus-circle mr-2"></i> <?= esc($title) ?></h3>
            </div>
            <div>
                <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="<?= base_url('guru/quran_kelompok/store') ?>" method="POST">
                    
                    <div class="row">
                        <!-- Informasi Kelompok -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-secondary border-bottom pb-2 mb-3">Informasi Kelompok</h6>
                            
                            <div class="form-group">
                                <label>Nama Kelompok (Contoh: Kelompok A - Ust. Fulan)</label>
                                <input type="text" name="nama_kelompok" class="form-control" required value="<?= old('nama_kelompok') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Jenis Kelompok</label>
                                <select name="jenis_kelompok" class="form-control" required>
                                    <option value="Reguler" <?= old('jenis_kelompok') == 'Reguler' ? 'selected' : '' ?>>Reguler (Siswa hanya boleh 1 kelompok)</option>
                                    <option value="Khusus" <?= old('jenis_kelompok') == 'Khusus' ? 'selected' : '' ?>>Khusus (Siswa bebas masuk)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Pilih Pembimbing</label>
                                <select name="pembimbing_id" class="form-control" required>
                                    <option value="">-- Pilih Guru / Pembimbing --</option>
                                    <?php foreach($pembimbing as $guru): ?>
                                        <option value="<?= $guru['id'] ?>"><?= esc($guru['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Pilihan Siswa -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-secondary border-bottom pb-2 mb-3">Pilih Anggota Siswa</h6>
                            
                            <div class="form-group">
                                <label class="text-primary"><i class="fas fa-filter"></i> Filter Kelas (Rombel) Terlebih Dahulu:</label>
                                <select id="filter_rombel" class="form-control border-primary" onchange="gantiRombel()">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach($rombels as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= ($r['id'] == $rombel_id) ? 'selected' : '' ?>><?= esc($r['rombel_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if($rombel_id): ?>
                                <div class="bg-light border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                    <p class="small font-weight-bold text-muted mb-2">Pilih siswa yang akan dimasukkan ke kelompok ini:</p>
                                    
                                    <?php if(empty($students)): ?>
                                        <span class="text-danger small">Tidak ada siswa di kelas ini.</span>
                                    <?php endif; ?>

                                    <?php foreach($students as $s): 
                                        $sudahReguler = in_array($s['student_id'], $siswaRegulerTerdaftar);
                                    ?>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <!-- Peringatan Visual Jika Siswa Sudah Ada Di Reguler -->
                                            <input type="checkbox" class="custom-control-input" name="students[]" value="<?= $s['student_id'] ?>" id="siswa_<?= $s['student_id'] ?>">
                                            <label class="custom-control-label" for="siswa_<?= $s['student_id'] ?>" style="<?= $sudahReguler ? 'color: #dc3545;' : '' ?>">
                                                <?= esc($s['username']) ?>
                                                <?php if($sudahReguler): ?>
                                                    <span class="badge badge-danger ml-1" style="font-size: 10px;">Sudah Punya Kelompok Reguler</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info py-2 small">
                                    Silakan pilih kelas pada dropdown di atas untuk memunculkan nama-nama siswa.
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <hr>
                    <div class="text-right">
                        <button type="submit" class="btn btn-success font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Kelompok
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>