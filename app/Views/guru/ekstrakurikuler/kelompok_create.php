<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Agar tabel rapi */
        .sticky-header th { position: sticky; top: 0; background: #e2e3e5; z-index: 10; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-users-cog mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Buat kelompok eskul baru, lintas kelas dengan mudah tanpa kehilangan centangan.</p>
            </div>
            <div>
                <a href="<?= base_url('guru/ekstrakurikuler') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm alert-dismissible fade show mb-4 text-dark font-weight-bold" style="background-color: #f8d7da; border-color: #f5c6cb;">
                <i class="fas fa-exclamation-triangle mr-1 text-danger"></i> <?= session()->getFlashdata('error') ?>
                <button type="button" class="close text-dark" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- STEP 1: DROPDOWN FILTER KELAS (Javascript Murni) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white p-4">
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-secondary mb-2"><i class="fas fa-filter mr-1 text-primary"></i> Langkah 1: Filter Kelas (Opsional)</label>
                    <select class="form-control form-control-lg text-dark font-weight-bold" id="filter_rombel">
                        <option value="all">-- Tampilkan Semua Kelas --</option>
                        <?php foreach($rombels as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= esc($r['rombel_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted mt-2 d-block">Mencari siswa dengan filter kelas tidak akan me-reset/menghapus centangan Anda.</small>
                </div>
            </div>
        </div>

        <!-- STEP 2 & 3: FORM UTAMA -->
        <div class="card shadow-sm border-0">
            <form action="<?= base_url('guru/ekstrakurikuler/kelompok/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="card-body p-4 bg-white">
                    
                    <h5 class="font-weight-bold text-dark mb-4 border-bottom pb-2"><i class="fas fa-file-alt mr-1 text-success"></i> Langkah 2: Detail Kelompok & Anggota</h5>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="font-weight-bold text-secondary mb-2">Nama Kelompok Eskul</label>
                            <input type="text" name="nama_kelompok" class="form-control text-dark" placeholder="Misal: Tim Futsal A / Klub Sains" value="<?= old('nama_kelompok') ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="font-weight-bold text-secondary mb-2">Guru Pembimbing / Pembina</label>
                            <select name="pembimbing_id" class="form-control text-dark" required>
                                <option value="">-- Pilih Pembimbing --</option>
                                <?php foreach($pembimbing as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= old('pembimbing_id') == $p['id'] ? 'selected' : '' ?>><?= esc($p['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="font-weight-bold text-secondary mb-2 d-block">Jenis Kelompok</label>
                            <div class="custom-control custom-radio custom-control-inline mr-4">
                                <input type="radio" id="radio_reguler" name="jenis_kelompok" value="Reguler" class="custom-control-input" <?= old('jenis_kelompok', 'Reguler') == 'Reguler' ? 'checked' : '' ?>>
                                <label class="custom-control-label font-weight-bold text-primary" for="radio_reguler" style="cursor:pointer;">Reguler (Maks 1 Eskul / Siswa)</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="radio_khusus" name="jenis_kelompok" value="Khusus" class="custom-control-input" <?= old('jenis_kelompok') == 'Khusus' ? 'checked' : '' ?>>
                                <label class="custom-control-label font-weight-bold text-warning" for="radio_khusus" style="cursor:pointer;">Khusus (Boleh Dobel / Remedial)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Murid (Lintas Kelas) -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-end mb-3">
                                <label class="font-weight-bold text-secondary mb-0 d-block">Pilih Anggota Siswa (Bisa Lintas Kelas)</label>
                                <span class="badge badge-primary py-2 px-3" style="font-size: 14px;">Total Terpilih: <span id="selected_count">0</span> Siswa</span>
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
                                                <th width="35%">Status Validasi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="student_table_body">
                                            <?php foreach($students as $s): ?>
                                                <?php $isTerdaftar = in_array($s['student_id'], $siswaRegulerTerdaftar); ?>
                                                <tr class="student-row" data-rombel="<?= $s['rombel_id'] ?>">
                                                    <td class="text-center align-middle">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" name="students[]" value="<?= $s['student_id'] ?>" 
                                                                   class="custom-control-input student-cb" 
                                                                   id="student_<?= $s['student_id'] ?>" 
                                                                   data-is-reguler="<?= $isTerdaftar ? 'true' : 'false' ?>">
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
                                                        <span class="badge badge-warning text-dark status-locked py-1 px-2" style="display: none; font-size: 85%;">
                                                            <i class="fas fa-ban mr-1"></i> Di Reguler Lain
                                                        </span>
                                                        <span class="badge badge-success text-white status-ready py-1 px-2" style="display: none; font-size: 85%;">
                                                            <i class="fas fa-check mr-1"></i> Siap Gabung
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
                    <a href="<?= base_url('guru/ekstrakurikuler') ?>" class="btn btn-secondary font-weight-bold px-3 mr-2">Batal</a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm" id="btn_simpan">
                        <i class="fas fa-save mr-1"></i> Simpan Kelompok Eskul
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- SCRIPT INTERAKTIF (Filter & Validasi Reguler/Khusus) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioReguler = document.getElementById('radio_reguler');
            const radioKhusus  = document.getElementById('radio_khusus');
            const checkboxes   = document.querySelectorAll('.student-cb');
            const filterDropdown = document.getElementById('filter_rombel');
            const rows         = document.querySelectorAll('.student-row');
            const counterText  = document.getElementById('selected_count');

            // 1. Fungsi Update Status Reguler / Khusus
            function updateCheckboxes() {
                if (!radioReguler || !radioKhusus) return;
                const isReguler = radioReguler.checked;

                checkboxes.forEach(function(cb) {
                    const isAlreadyReguler = cb.getAttribute('data-is-reguler') === 'true';
                    const tr = cb.closest('tr');
                    const nameCell = tr.querySelector('.student-name');
                    const badgeLocked = tr.querySelector('.status-locked');
                    const badgeReady = tr.querySelector('.status-ready');

                    if (isReguler && isAlreadyReguler) {
                        cb.disabled = true;
                        cb.checked = false;
                        nameCell.classList.add('text-muted');
                        nameCell.style.textDecoration = 'line-through';
                        badgeLocked.style.display = 'inline-block';
                        badgeReady.style.display = 'none';
                    } else {
                        cb.disabled = false;
                        nameCell.classList.remove('text-muted');
                        nameCell.style.textDecoration = 'none';
                        badgeLocked.style.display = 'none';
                        badgeReady.style.display = 'inline-block';
                    }
                });
                countSelected(); // Hitung ulang setelah update
            }

            // 2. Fungsi Hitung Jumlah Centangan Realtime
            function countSelected() {
                const checkedBoxes = document.querySelectorAll('.student-cb:checked');
                counterText.innerText = checkedBoxes.length;
            }

            // 3. Fungsi Filter Kelas Lintas (Menyembunyikan baris tanpa menghapus centangan)
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

            // Pemasangan Event Listener
            if (radioReguler) radioReguler.addEventListener('change', updateCheckboxes);
            if (radioKhusus) radioKhusus.addEventListener('change', updateCheckboxes);
            
            checkboxes.forEach(cb => {
                cb.addEventListener('change', countSelected);
            });

            if (filterDropdown) filterDropdown.addEventListener('change', applyFilter);

            // Eksekusi tampilan awal saat halaman dimuat
            updateCheckboxes();
        });
    </script>
</body>
</html>