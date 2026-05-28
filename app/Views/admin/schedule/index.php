<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9 !important; font-family: 'Source Sans Pro', sans-serif; }
        .card-tabs { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        .nav-tabs-responsive { display: flex; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; border-bottom: 2px solid #dee2e6; }
        .nav-tabs-responsive .nav-item { flex: 0 0 auto; }
        .nav-link { color: #495057 !important; font-weight: 600; border-radius: 4px; white-space: nowrap; padding: 10px 20px; margin-right: 5px; }
        .nav-link.active { background-color: #dee2e6 !important; color: #000000 !important; border-bottom: 3px solid #0d6efd !important; }
        .nav-link:hover:not(.active) { background-color: #f8f9fa !important; }
        .btn-emoji { font-size: 14px; text-decoration: none; padding: 3px 8px; border-radius: 4px; border: 1px solid #ccc; background: #fff; cursor: pointer; display: inline-block; }
        .btn-emoji:hover { background: #f0f0f0; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg">

    <?php
    $taId     = !empty($tahunAktif) ? $tahunAktif['id'] : '';
    $verId    = !empty($activeVersion) ? $activeVersion['id'] : '';
    $verName  = !empty($activeVersion) ? esc($activeVersion['version_name']) : '';
    $verTitle = (!empty($activeVersion) && !empty($activeVersion['schedule_title'])) ? ' - ' . esc($activeVersion['schedule_title']) : '';
    $urlParam = ($taId ? 'ta='.$taId : '') . ($verId ? '&v='.$verId : '');
    ?>

    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-white border-bottom shadow-sm py-2">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item d-flex align-items-center ps-2">
                        <i class="bi bi-calendar-week text-primary me-2 fs-4"></i>
                        <h4 class="navbar-text my-0 font-weight-bold text-dark" style="font-weight: 700; font-size: 18px;">
                            Pusat Penjadwalan Akademik
                        </h4>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a href="<?= base_url('/') ?>" class="btn btn-sm btn-secondary font-weight-bold px-3">⬅️ Dashboard</a></li>
                </ul>
            </div>
        </nav>

        <main class="app-main pt-4 pb-4">
            <div class="app-content">
                <div class="container-fluid px-4">

                    <?php if (session()->getFlashdata('sukses')): ?>
                        <div class="alert alert-success fw-bold shadow-sm py-2"><?= session()->getFlashdata('sukses') ?></div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger fw-bold shadow-sm py-2">⚠️ <?= session()->getFlashdata('error') ?></div>
                    <?php endif; ?>

                    <?php if(empty($tahunAktif)): ?>
                        <div class="alert alert-danger font-weight-bold shadow-sm">⚠️ Tidak ada Tahun Pelajaran yang berstatus Aktif.</div>
                    <?php else: ?>
                        
                        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-3">
                            <form action="<?= base_url('admin/schedule') ?>" method="GET" class="d-flex flex-wrap align-items-center gap-2 bg-white p-1 rounded border shadow-sm">
                                <input type="hidden" name="tab" value="<?= esc($activeTab) ?>">
                                
                                <span class="small font-weight-bold text-muted ps-2">Semester:</span>
                                <select name="ta" class="form-select form-select-sm border-0 font-weight-bold bg-light" onchange="this.form.submit()" style="width: auto;">
                                    <?php foreach ($daftarTahun as $ta) : ?>
                                        <option value="<?= $ta['id'] ?>" <?= ($taId == $ta['id']) ? 'selected' : '' ?>>
                                            <?= $ta['academic_year'] ?> - <?= $ta['semester'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <span class="small font-weight-bold text-muted ms-2">Versi Jadwal:</span>
                                <select name="v" class="form-select form-select-sm border-0 font-weight-bold bg-light text-primary" onchange="if(this.value==='NEW'){ new bootstrap.Modal(document.getElementById('modalNewVersion')).show(); this.value='<?= $verId ?>'; } else { this.form.submit(); }" style="width: auto; min-width:250px;">
                                    <?php if(empty($versions)): ?>
                                        <option value="" disabled selected>Belum Ada Versi</option>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($versions as $ver) : ?>
                                        <?php $judul = !empty($ver['schedule_title']) ? ' - ' . esc($ver['schedule_title']) : ''; ?>
                                        <option value="<?= $ver['id'] ?>" <?= ($verId == $ver['id']) ? 'selected' : '' ?>>
                                            📄 <?= esc($ver['version_name']) ?><?= $judul ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="NEW" class="fw-bold text-success">➕ Buat Versi Baru...</option>
                                </select>
                            </form>
                        </div>
                        
                        <ul class="nav nav-tabs nav-tabs-responsive mb-4">
                            <li class="nav-item"><a class="nav-link <?= $activeTab == 'matriks' ? 'active' : '' ?>" href="<?= base_url('admin/schedule?tab=matriks&'.$urlParam) ?>">📅 Matriks Jadwal Utama</a></li>
                            <li class="nav-item"><a class="nav-link <?= $activeTab == 'plotting' ? 'active' : '' ?>" href="<?= base_url('admin/schedule?tab=plotting&'.$urlParam) ?>">🎯 Plotting Beban JP Guru</a></li>
                            <li class="nav-item"><a class="nav-link <?= $activeTab == 'waktu' ? 'active' : '' ?>" href="<?= base_url('admin/schedule?tab=waktu&'.$urlParam) ?>">⏱️ Pengaturan Slot & Waktu</a></li>
                        </ul>

                        <div class="card card-tabs shadow-sm p-4">
                            <?php if(empty($activeVersion)): ?>
                                <div class="text-center py-5">
                                    <h1 style="font-size: 50px;">📄</h1>
                                    <h5>Tidak Ada Versi Jadwal Aktif</h5>
                                    <p class="text-muted small">Buatlah versi jadwal pertama Anda untuk mulai mengatur waktu dan pelajaran.</p>
                                    <button type="button" class="btn btn-success font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNewVersion">➕ Buat Versi Jadwal Baru</button>
                                </div>
                            <?php else: ?>

                                <div class="tab-content">
                                    <?php if($activeTab == 'matriks'): ?> <h5 class="text-muted text-center py-5">Matriks Akan Tampil Disini</h5> <?php endif; ?>
                                    <?php if($activeTab == 'plotting'): ?> <h5 class="text-muted text-center py-5">Form Plotting Akan Tampil Disini</h5> <?php endif; ?>

                                    <!-- TAB 3: PENGATURAN WAKTU -->
                                    <?php if($activeTab == 'waktu'): ?>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                            <div>
                                                <h5 class="font-weight-bold text-dark mb-1">Manajemen Slot Waktu Harian</h5>
                                                <span class="text-muted small">Versi Aktif: <strong><?= $verName ?><?= $verTitle ?></strong></span>
                                            </div>
                                            <div>
                                                <a href="<?= base_url('admin/schedule/reset-all-slots?'.$urlParam) ?>" onclick="return confirm('Yakin ingin menyapu bersih seluruh jadwal di versi ini?')" class="btn btn-danger btn-sm font-weight-bold shadow-sm me-2">
                                                    🗑️ Hapus Semua Slot
                                                </a>
                                                <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGenerateWaktu">
                                                    ⚙️ Generate Slot Masal
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <?php if(empty($timeSlots)): ?>
                                            <div class="text-center py-5 text-muted">
                                                <h1 style="font-size: 50px;">⏱️</h1>
                                                <h5 class="mt-3">Belum Ada Slot Waktu</h5>
                                                <p class="small">Klik ⚙️ Generate Slot Masal di atas untuk mengisi jam masuk ke jadwal ini.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php 
                                            $groupedSlots = []; 
                                            foreach ($timeSlots as $ts) {
                                                $day = ucfirst(strtolower(trim($ts['day_name'])));
                                                if (!isset($groupedSlots[$day])) $groupedSlots[$day] = [];
                                                $groupedSlots[$day][] = $ts;
                                            }
                                            
                                            $urutanStandar = ['Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7];
                                            uksort($groupedSlots, function($a, $b) use ($urutanStandar) {
                                                return ($urutanStandar[$a] ?? 99) <=> ($urutanStandar[$b] ?? 99);
                                            });
                                            ?>
                                            <div class="row g-3">
                                                <?php foreach($groupedSlots as $dName => $slots): ?>
                                                    <div class="col-lg-6">
                                                        <div class="card shadow-sm border-0">
                                                            <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0 font-weight-bold">📅 <?= esc($dName) ?></h6>
                                                                <a href="<?= base_url('admin/schedule/delete-day-time/'.urlencode($dName).'?'.$urlParam) ?>" class="btn btn-sm btn-danger py-0 px-2 text-white shadow-sm" onclick="return confirm('Hapus seluruh jam hari <?= esc($dName) ?>?')">🗑️ Hapus Semua</a>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <table class="table table-sm table-striped table-hover mb-0" style="font-size: 13px;">
                                                                    <thead class="bg-light">
                                                                        <tr>
                                                                            <th class="text-center">Ke-</th>
                                                                            <th>Waktu</th>
                                                                            <th>Label Slot</th>
                                                                            <th class="text-center">Aksi</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach($slots as $slot): 
                                                                            $durasi = (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 60;
                                                                        ?>
                                                                        <tr>
                                                                            <td class="text-center align-middle fw-bold"><?= $slot['slot_number'] ?></td>
                                                                            <td class="align-middle">
                                                                                <?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?>
                                                                            </td>
                                                                            <td class="align-middle">
                                                                                <?= esc($slot['slot_label']) ?> 
                                                                            </td>
                                                                            <td class="text-center align-middle">
                                                                                <button type="button" class="btn-emoji btn-edit-slot shadow-sm" title="Edit Baris & Durasi"
                                                                                    data-id="<?= $slot['id'] ?>" data-label="<?= esc($slot['slot_label']) ?>"
                                                                                    data-duration="<?= $durasi ?>">✏️</button>
                                                                                <a href="<?= base_url('admin/schedule/delete-slot-time/'.$slot['id'].'?'.$urlParam) ?>" class="btn-emoji shadow-sm" onclick="return confirm('Hapus baris ini saja?')">🗑️</a>
                                                                            </td>
                                                                        </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL POP UP AMAN -->
    <div class="modal fade" id="modalNewVersion" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/create-version') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ta" value="<?= $taId ?>">
                    <div class="modal-header bg-success text-white py-2">
                        <h6 class="modal-title fw-bold">➕ Buat Versi Jadwal</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="mb-2">
                            <label class="form-label font-weight-bold small">Kode / Versi (Misal: V1)</label>
                            <input type="text" name="version_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-weight-bold small">Judul (Misal: Jadwal Ramadhan)</label>
                            <input type="text" name="schedule_title" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="modal-footer py-1">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">💾 Simpan Versi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if($activeTab == 'waktu' && !empty($verId)): ?>
    <!-- Modal Generate Waktu Otomatis -->
    <div class="modal fade" id="modalGenerateWaktu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/generate-time') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ta" value="<?= $taId ?>">
                    <input type="hidden" name="v" value="<?= $verId ?>">
                    <div class="modal-header bg-primary text-white py-2">
                        <h6 class="modal-title font-weight-bold">⚙️ Generator Slot Waktu Multi-Hari</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-primary border-bottom w-100 pb-1">1. Centang Hari Target:</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="day_names[]" value="<?= $h ?>" id="chk_<?= $h ?>">
                                        <label class="form-check-label fw-bold" for="chk_<?= $h ?>"><?= $h ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <label class="form-label font-weight-bold small text-primary border-bottom w-100 pb-1">2. Pengaturan Jam:</label>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold small">Jam Mulai Masuk</label>
                                <input type="time" name="start_time" class="form-control form-control-sm" value="07:30" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label font-weight-bold small">Durasi 1 JP (Menit)</label>
                                <input type="number" name="interval_minutes" class="form-control form-control-sm" value="30" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">Jumlah Baris/Slot</label>
                            <input type="number" name="total_slots" class="form-control form-control-sm" value="8" required>
                        </div>
                    </div>
                    <div class="modal-footer py-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 font-weight-bold">⚙️ Mulai Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Slot Manual -->
    <div class="modal fade" id="modalEditSlot" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/update-time') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ta" value="<?= $taId ?>">
                    <input type="hidden" name="v" value="<?= $verId ?>">
                    <input type="hidden" name="id" id="edit_slot_id">
                    
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title font-weight-bold text-warning">✏️ Penyesuaian Slot Waktu</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Label Waktu / Nama JP</label>
                            <input type="text" name="slot_label" id="edit_slot_label" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Durasi Baru (Menit)</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="duration_minutes" id="edit_duration_minutes" class="form-control font-weight-bold" required>
                                <span class="input-group-text bg-white">Menit</span>
                            </div>
                            <div class="form-text text-primary" style="font-size: 10px;">*Jika menit diperpendek, baris di bawahnya otomatis bergeser maju.</div>
                        </div>
                    </div>
                    <div class="modal-footer py-1">
                        <button type="submit" class="btn btn-warning btn-sm w-100 font-weight-bold text-dark">💾 Simpan & Geser Waktu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.btn-edit-slot').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_slot_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_slot_label').value = this.getAttribute('data-label');
                    document.getElementById('edit_duration_minutes').value = this.getAttribute('data-duration');
                    new bootstrap.Modal(document.getElementById('modalEditSlot')).show();
                });
            });
        });
    </script>
</body>
</html>