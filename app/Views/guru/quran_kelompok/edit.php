<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>
        // Fungsi reload halaman saat ganti Rombel khusus halaman edit
        function gantiRombel() {
            let rombelId = document.getElementById('filter_rombel').value;
            
            let nama = document.querySelector('input[name="nama_kelompok"]').value;
            let jenis = document.querySelector('select[name="jenis_kelompok"]').value;
            let pembimbing = document.querySelector('select[name="pembimbing_id"]').value;

            let url = "<?= base_url('guru/quran_kelompok/edit/' . $kelompok['id']) ?>";
            
            if (rombelId) {
                url += "?rombel_id=" + rombelId;
                url += "&nama=" + encodeURIComponent(nama);
                url += "&jenis=" + encodeURIComponent(jenis);
                url += "&pembimbing=" + encodeURIComponent(pembimbing);
            }
            
            window.location.href = url;
        }
    </script>
</head>
<body class="p-4 bg-light">
    
    <?php 
        // Pertahankan inputan dari URL GET (jika ada reload filter) atau dari database asli kelompok
        $valNama = isset($_GET['nama']) ? $_GET['nama'] : $kelompok['nama_kelompok'];
        $valJenis = isset($_GET['jenis']) ? $_GET['jenis'] : $kelompok['jenis_kelompok'];
        $valPembimbing = isset($_GET['pembimbing']) ? $_GET['pembimbing'] : $kelompok['pembimbing_id'];
    ?>

    <div class="container-fluid" style="max-width: 900px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 text-warning font-weight-bold"><i class="fas fa-edit mr-2"></i> Edit Kelompok</h3>
            <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="<?= base_url('guru/quran_kelompok/update/'.$kelompok['id']) ?>" method="POST">
                    
                    <div class="row">
                        <!-- SISI KIRI: Informasi Kelompok -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-secondary border-bottom pb-2 mb-3">Informasi Kelompok</h6>
                            
                            <div class="form-group">
                                <label>Nama Kelompok</label>
                                <input type="text" name="nama_kelompok" class="form-control" required value="<?= esc($valNama) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Jenis Kelompok</label>
                                <select name="jenis_kelompok" class="form-control" required>
                                    <option value="Reguler" <?= ($valJenis == 'Reguler') ? 'selected' : '' ?>>Reguler</option>
                                    <option value="Khusus" <?= ($valJenis == 'Khusus') ? 'selected' : '' ?>>Khusus</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label>Pembimbing</label>
                                <select name="pembimbing_id" class="form-control" required>
                                    <?php foreach($pembimbing as $guru): ?>
                                        <option value="<?= $guru['id'] ?>" <?= ($guru['id'] == $valPembimbing) ? 'selected' : '' ?>>
                                            <?= esc($guru['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- SISI KANAN: Manajemen Anggota (Tambah/Kurang) -->
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-secondary border-bottom pb-2 mb-3">Kelola Anggota Siswa</h6>
                            
                            <div class="form-group">
                                <label class="text-primary"><i class="fas fa-filter"></i> Pilih Rombel / Kelas Siswa:</label>
                                <select id="filter_rombel" class="form-control border-primary" onchange="gantiRombel()">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach($rombels as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= ($r['id'] == $rombel_id) ? 'selected' : '' ?>><?= esc($r['rombel_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if($rombel_id): ?>
                                <div class="bg-light border rounded p-3" style="max-height: 280px; overflow-y: auto;">
                                    <p class="small font-weight-bold text-muted mb-2">Beri centang untuk menambahkan, lepas centang untuk mengeluarkan:</p>
                                    
                                    <?php if(empty($students)): ?>
                                        <span class="text-danger small">Tidak ada siswa di kelas ini.</span>
                                    <?php endif; ?>

                                    <?php 
                                        $filteredStudentIds = [];
                                        foreach($students as $s): 
                                            $filteredStudentIds[] = $s['student_id'];
                                            $isMember = in_array($s['student_id'], $currentStudentIds);
                                            $sudahRegulerLain = in_array($s['student_id'], $siswaRegulerTerdaftar);
                                    ?>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <!-- Checkbox Anggota -->
                                            <input type="checkbox" class="custom-control-input" name="students[]" value="<?= $s['student_id'] ?>" id="siswa_<?= $s['student_id'] ?>" <?= $isMember ? 'checked' : '' ?>>
                                            
                                            <label class="custom-control-label" for="siswa_<?= $s['student_id'] ?>" style="<?= ($sudahRegulerLain && !$isMember) ? 'color: #dc3545;' : '' ?>">
                                                <?= esc($s['username']) ?>
                                                
                                                <?php if($isMember): ?>
                                                    <span class="badge badge-success ml-1" style="font-size: 9px;">Anggota Kelompok Ini</span>
                                                <?php endif; ?>

                                                <?php if($sudahRegulerLain && !$isMember): ?>
                                                    <span class="badge badge-danger ml-1" style="font-size: 9px;">Sudah di Reguler Lain</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Token penanda daftar ID siswa yang sedang diubah di kelas ini -->
                                    <input type="hidden" name="current_filtered_student_ids" value="<?= implode(',', $filteredStudentIds) ?>">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info py-2 small">
                                    Silakan pilih kelas pada dropdown di atas untuk mengelola keanggotaan siswa kelas tersebut.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-right border-top pt-3 mt-3">
                        <button type="submit" class="btn btn-warning font-weight-bold text-white px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>