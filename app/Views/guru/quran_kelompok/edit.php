<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .sticky-header th { position: sticky; top: 0; background: #e2e3e5; z-index: 10; }
    </style>
</head>
<body class="p-4 bg-light">
    
    <div class="container-fluid" style="max-width: 1000px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-warning font-weight-bold"><i class="fas fa-edit mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Edit detail kelompok Al-Qur'an dan kelola keanggotaan lintas kelas secara realtime.</p>
            </div>
            <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>

        <!-- Alert Error (Warna Kontras Tinggi) -->
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm alert-dismissible fade show mb-4 text-dark font-weight-bold" style="background-color: #f8d7da; border-color: #f5c6cb;">
                <i class="fas fa-exclamation-triangle mr-1 text-danger"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="close text-dark" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- STEP 1: DROPDOWN FILTER KELAS (Dinamis Tanpa Reload) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white p-4">
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-secondary mb-2"><i class="fas fa-filter mr-1 text-primary"></i> Langkah 1: Filter Kelas (Opsional)</label>
                    <select class="form-control form-control-lg text-dark font-weight-bold border-primary" id="filter_rombel">
                        <option value="all">-- Tampilkan Semua Kelas --</option>
                        <?php foreach($rombels as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= esc($r['rombel_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted mt-2 d-block">Gunakan filter kelas ini untuk mempermudah pencarian siswa tanpa khawatir kehilangan centangan Anda di kelas lainnya.</small>
                </div>
            </div>
        </div>

        <!-- STEP 2: DETAIL DAN TABEL ANGGOTA -->
        <div class="card shadow-sm border-0">
            <form action="<?= base_url('guru/quran_kelompok/update/'.$kelompok['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="card-body p-4 bg-white">
                    
                    <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2"><i class="fas fa-file-alt mr-1 text-warning"></i> Langkah 2: Sesuaikan Kelompok & Anggota</h5>

                    <div class="row mb-4">
                        <!-- Nama Kelompok -->
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-2">Nama Kelompok</label>
                            <input type="text" name="nama_kelompok" class="form-control text-dark font-weight-bold" required value="<?= esc(old('nama_kelompok', $kelompok['nama_kelompok'])) ?>">
                        </div>
                        
                        <!-- Pembimbing -->
                        <div class="col-md-6">
                            <label class="font-weight-bold text-secondary mb-2">Guru Pembimbing / Pengampu</label>
                            <select name="pembimbing_id" class="form-control text-dark" required>
                                <?php foreach($pembimbing as $guru): ?>
                                    <option value="<?= $guru['id'] ?>" <?= ($guru['id'] == old('pembimbing_id', $kelompok['pembimbing_id'])) ? 'selected' : '' ?>>
                                        <?= esc($guru['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Jenis Kelompok -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="font-weight-bold text-secondary mb-2">Jenis Kelompok</label>
                            <select name="jenis_kelompok" id="jenis_kelompok" class="form-control text-dark font-weight-bold" required>
                                <option value="Reguler" <?= (old('jenis_kelompok', $kelompok['jenis_kelompok']) == 'Reguler') ? 'selected' : '' ?>>Reguler (Siswa maksimal 1 kelompok)</option>
                                <option value="Khusus" <?= (old('jenis_kelompok', $kelompok['jenis_kelompok']) == 'Khusus') ? 'selected' : '' ?>>Khusus (Siswa bebas masuk kelompok tambahan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabel Anggota Siswa Lintas Kelas -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-end mb-3">
                                <label class="font-weight-bold text-secondary mb-0 d-block">Centang Anggota Siswa (Bisa Lintas Kelas)</label>
                                <span class="badge badge-warning text-dark py-2 px-3 font-weight-bold shadow-sm" style="font-size: 14px; background-color: #ffeba5;">
                                    Total Terpilih: <span id="selected_count">0</span> Siswa
                                </span>
                            </div>
                            
                            <?php if(empty($students)): ?>
                                <div class="text-center text-muted py-3 border bg-light">Belum ada data siswa di dalam sistem.</div>
                            <?php else: ?>
                                <div class="table-responsive border rounded bg-light" style="max-height: 450px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0 bg-white">
                                        <thead class="sticky-header text-dark">
                                            <tr>
                                                <th width="8%" class="text-center">Pilih</th>
                                                <th>Nama Siswa</th>
                                                <th width="15%">Kelas</th>
                                                <th width="40%">Status Keanggotaan / Validasi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="student_table_body">
                                            <?php foreach($students as $s): ?>
                                                <?php 
                                                    $isMember = in_array($s['student_id'], $currentStudentIds);
                                                    $sudahRegulerLain = in_array($s['student_id'], $siswaRegulerTerdaftar);
                                                ?>
                                                <tr class="student-row" data-rombel="<?= $s['rombel_id'] ?>">
                                                    <td class="text-center align-middle">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" name="students[]" value="<?= $s['student_id'] ?>" 
                                                                   class="custom-control-input student-cb" 
                                                                   id="student_<?= $s['student_id'] ?>" 
                                                                   data-is-reguler="<?= $sudahRegulerLain ? 'true' : 'false' ?>"
                                                                   data-is-member="<?= $isMember ? 'true' : 'false' ?>"
                                                                   <?= $isMember ? 'checked' : '' ?>>
                                                            <label class="custom-control-label" for="student_<?= $s['student_id'] ?>" style="cursor: pointer;"></label>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle student-name font-weight-bold text-dark">
                                                        <?= esc($s['username']) ?>
                                                    </td>
                                                    <td class="align-middle font-weight-bold text-primary">
                                                        <?= esc($s['rombel_name']) ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <!-- Keterangan Status Berwarna Kontras -->
                                                        <span class="badge badge-success text-dark status-member py-1 px-2" style="display: none; font-size: 85%; background-color: #d4edda; color: #155724 !important;">
                                                            <i class="fas fa-check-double mr-1"></i> Anggota Kelompok Ini
                                                        </span>
                                                        <span class="badge badge-warning text-dark status-locked py-1 px-2" style="display: none; font-size: 85%; background-color: #ffe0b2; color: #e65100 !important;">
                                                            <i class="fas fa-ban mr-1"></i> Terdaftar di Reguler Lain
                                                        </span>
                                                        <span class="badge badge-secondary text-dark status-ready py-1 px-2" style="display: none; font-size: 85%; background-color: #f5f5f5;">
                                                            <i class="fas fa-plus mr-1"></i> Siap Ditambahkan
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                
                <div class="card-footer bg-light py-3 d-flex justify-content-end">
                    <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-secondary font-weight-bold px-3 mr-2">Batal</a>
                    <button type="submit" class="btn btn-warning text-dark font-weight-bold px-4 shadow-sm" style="background-color: #ffc107; border-color: #ffc107;">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- SCRIPT INTERAKTIF (Filter & Validasi Reguler/Khusus Lintas Kelas) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectJenis  = document.getElementById('jenis_kelompok');
            const checkboxes   = document.querySelectorAll('.student-cb');
            const filterDropdown = document.getElementById('filter_rombel');
            const rows         = document.querySelectorAll('.student-row');
            const counterText  = document.getElementById('selected_count');

            // 1. Fungsi Update Status Reguler / Khusus & Validasi
            function updateCheckboxes() {
                if (!selectJenis) return;
                const isReguler = selectJenis.value === 'Reguler';

                checkboxes.forEach(function(cb) {
                    const isAlreadyRegulerLain = cb.getAttribute('data-is-reguler') === 'true';
                    const isMember = cb.getAttribute('data-is-member') === 'true';
                    const tr = cb.closest('tr');
                    const nameCell = tr.querySelector('.student-name');
                    
                    const badgeMember = tr.querySelector('.status-member');
                    const badgeLocked = tr.querySelector('.status-locked');
                    const badgeReady  = tr.querySelector('.status-ready');

                    if (isReguler && isAlreadyRegulerLain) {
                        // Jika grup REGULER, kunci siswa yang sudah terdaftar di kelompok reguler LAIN (kecuali dia memang anggota di sini)
                        if (isMember) {
                            cb.disabled = false;
                            nameCell.classList.remove('text-muted');
                            nameCell.style.textDecoration = 'none';
                            badgeMember.style.display = 'inline-block';
                            badgeLocked.style.display = 'none';
                            badgeReady.style.display  = 'none';
                        } else {
                            cb.disabled = true;
                            cb.checked = false; // Batalkan centangan jika sebelumnya tercentang paksa
                            nameCell.classList.add('text-muted');
                            nameCell.style.textDecoration = 'line-through';
                            badgeMember.style.display = 'none';
                            badgeLocked.style.display = 'inline-block';
                            badgeReady.style.display  = 'none';
                        }
                    } else {
                        // Jika grup KHUSUS atau siswa bebas / siap gabung
                        cb.disabled = false;
                        nameCell.classList.remove('text-muted');
                        nameCell.style.textDecoration = 'none';
                        badgeLocked.style.display = 'none';

                        if (isMember) {
                            badgeMember.style.display = 'inline-block';
                            badgeReady.style.display  = 'none';
                        } else {
                            badgeMember.style.display = 'none';
                            badgeReady.style.display  = 'inline-block';
                        }
                    }
                });
                countSelected(); // Hitung ulang total yang dicentang
            }

            // 2. Fungsi Hitung Jumlah Siswa Terpilih (Dicentang)
            function countSelected() {
                const checkedBoxes = document.querySelectorAll('.student-cb:checked');
                counterText.innerText = checkedBoxes.length;
            }

            // 3. Fungsi Filter Kelas (Sembunyikan baris, pertahankan memori centang)
            function applyFilter() {
                const selectedRombel = filterDropdown.value;
                rows.forEach(function(row) {
                    const rowRombel = row.getAttribute('data-rombel');
                    if (selectedRombel === 'all' || rowRombel === selectedRombel) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // Event Listeners
            if (selectJenis) selectJenis.addEventListener('change', updateCheckboxes);
            
            checkboxes.forEach(cb => {
                cb.addEventListener('change', countSelected);
            });

            if (filterDropdown) filterDropdown.addEventListener('change', applyFilter);

            // Eksekusi fungsi pertama kali saat halaman dimuat
            updateCheckboxes();
        });
    </script>
</body>
</html>