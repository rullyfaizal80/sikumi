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
        .matriks-select { font-size: 11px; cursor: pointer; }
        .matriks-select { font-size: 11px; cursor: pointer; }
        .matriks-select.bg-success { color: #ffffff !important; }
        .matriks-select option { background-color: #ffffff !important; color: #000000 !important; }
        .is-clash { background-color: #dc3545 !important; color: #ffffff !important; border-color: #dc3545 !important; }
        .is-overload { background-color: #ffc107 !important; color: #000000 !important; border-color: #ffc107 !important; }
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
                                <select name="ta" id="selectTa" class="form-select form-select-sm border-0 font-weight-bold bg-light" style="width: auto;">
                                    <?php foreach ($daftarTahun as $ta) : ?>
                                        <option value="<?= $ta['id'] ?>" <?= ($taId == $ta['id']) ? 'selected' : '' ?>>
                                            <?= $ta['academic_year'] ?> - <?= $ta['semester'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <span class="small font-weight-bold text-muted ms-2">Versi Jadwal:</span>
                                <span class="small font-weight-bold text-muted ms-2">Versi Jadwal:</span>
                                <select name="v" id="selectVersion" class="form-select form-select-sm border-0 font-weight-bold bg-light text-primary" style="width: auto; min-width:250px;">
                                    <?php if(empty($versions)): ?><option value="" disabled selected>Belum Ada Versi</option><?php endif; ?>
                                    <?php foreach ($versions as $ver) : ?>
                                        <option value="<?= $ver['id'] ?>" <?= ($verId == $ver['id']) ? 'selected' : '' ?>>📄 <?= esc($ver['version_name']) ?><?= !empty($ver['schedule_title']) ? ' - ' . esc($ver['schedule_title']) : '' ?></option>
                                    <?php endforeach; ?>
                                    <option value="NEW" class="fw-bold text-success">➕ Buat Versi Baru...</option>
                                </select>
                                
                                <?php if(!empty($activeVersion)): ?>
                                    <a href="<?= base_url('admin/schedule/delete-version/'.$activeVersion['id'].'?ta='.$taId) ?>" class="btn btn-sm btn-outline-danger shadow-sm ms-1 px-2" onclick="return confirm('⚠️ YAKIN HAPUS PERMANEN? Seluruh struktur matriks, waktu, dan plotting versi ini akan hilang selamanya!')" title="Hapus Versi Jadwal">🗑️</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-info text-white shadow-sm font-weight-bold ms-1" data-bs-toggle="modal" data-bs-target="#modalCopyVersion" title="Salin Jadwal Lintas Semester">♻️ Copy</button>
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
                                    
                                    <?php if($activeTab == 'matriks'): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5>📅 Matriks Jadwal Seluruh Kelas</h5>
                                            <div>
                                                <button type="button" class="btn btn-info btn-sm font-weight-bold shadow-sm me-2 text-white" data-bs-toggle="modal" data-bs-target="#modalActivity">
                                                    ✨ Manajemen Kegiatan
                                                </button>
                                                <button type="submit" form="formMatriks" id="btnSaveMatriks" class="btn btn-success btn-sm font-weight-bold shadow-sm">
                                                    💾 Simpan Semua Perubahan
                                                </button>
                                            </div>
                                        </div>

                                        <form action="<?= base_url('admin/schedule/save-matrix') ?>" method="POST" id="formMatriks">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="ta" value="<?= $taId ?>">
                                            <input type="hidden" name="v" value="<?= $verId ?>">

                                            <?php foreach($matrixDays as $day): ?>
                                                <?php 
                                                    $slotsHariIni = array_filter($timeSlots, function($s) use ($day) { return $s['day_name'] == $day; });
                                                    if(empty($slotsHariIni)) continue;
                                                ?>
                                                <div class="card mb-4 shadow-sm border-primary">
                                                    <div class="card-header bg-primary text-white font-weight-bold py-2"><?= $day ?></div>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm text-center mb-0" style="font-size: 11px;">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th width="10%" class="align-middle">Jam / Waktu</th>
                                                                    <?php foreach($rombels as $r): ?>
                                                                        <th class="align-middle"><?= $r['class_name'] ?>-<?= $r['rombel_name'] ?></th>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach($slotsHariIni as $slot): ?>
                                                                <tr>
                                                                    <td class="bg-light align-middle text-center">
                                                                        <span class="font-weight-bold d-block text-dark"><?= esc($slot['slot_label']) ?></span>
                                                                        <span class="text-primary fw-bold" style="font-size: 10px;">
                                                                            <?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?>
                                                                        </span>
                                                                    </td>
                                                                    
                                                                    <?php foreach($rombels as $r): 
                                                                        $sch = $classSchedules[$slot['id']][$r['id']] ?? null;
                                                                    ?>
                                                                    <td class="align-middle">
                                                                        <select name="matrix[<?= $slot['id'] ?>][<?= $r['id'] ?>]" data-rombel="<?= $r['id'] ?>" class="form-select form-select-sm matriks-select <?= $sch ? 'bg-success text-white fw-bold border-success' : '' ?>" style="font-size: 11px; cursor: pointer;">
    <option value="">-</option>
    
    <optgroup label="✨ KEGIATAN UMUM">
        <?php foreach($kegiatan as $act): ?>
            <option value="ACT_<?= $act['id'] ?>" <?= ($sch && $sch['activity_id'] == $act['id']) ? 'selected' : '' ?>>☕ <?= esc($act['activity_name']) ?></option>
        <?php endforeach; ?>
    </optgroup>
    
    <optgroup label="🔗 MAPEL GABUNGAN">
        <?php foreach($combinedSubjects as $cs): 
            if(!isset($plottingDataCombined[$cs['id']][$r['id']])) continue;
            $target = $plottingDataCombined[$cs['id']][$r['id']]['target_jp'];
        ?>
            <option value="COM_<?= $cs['id'] ?>_<?= $plottingDataCombined[$cs['id']][$r['id']]['teacher_id'] ?>" data-target-jp="<?= $target ?>" <?= ($sch && $sch['combined_subject_id'] == $cs['id']) ? 'selected' : '' ?>>🔗 <?= esc($cs['combined_name']) ?></option>
        <?php endforeach; ?>
    </optgroup>
    
    <optgroup label="📚 MATA PELAJARAN">
        <?php foreach($subjects as $sub): 
            if(in_array($sub['id'], $combinedChildIds) || !isset($plottingDataNormal[$sub['id']][$r['id']])) continue;
            $target = $plottingDataNormal[$sub['id']][$r['id']]['target_jp'];
        ?>
            <option value="SUB_<?= $sub['id'] ?>_<?= $plottingDataNormal[$sub['id']][$r['id']]['teacher_id'] ?>" data-target-jp="<?= $target ?>" <?= ($sch && $sch['subject_id'] == $sub['id']) ? 'selected' : '' ?>><?= esc($sub['subject_name'] ?? $sub['nama_mapel']) ?></option>
        <?php endforeach; ?>
    </optgroup>
</select>
                                                                    </td>
                                                                    <?php endforeach; ?>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </form>
                                    <?php endif; ?>

                                    <?php if($activeTab == 'plotting'): ?>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                            <div>
                                                <h5 class="font-weight-bold text-dark mb-1">🎯 Plotting Beban JP (Otomatis Guru)</h5>
                                                <span class="text-muted small">Versi Aktif: <strong><?= $verName ?><?= $verTitle ?></strong></span>
                                            </div>
                                            <div>
                                                <button type="button" class="btn btn-warning btn-sm font-weight-bold shadow-sm me-2 text-dark" data-bs-toggle="modal" data-bs-target="#modalCombinedSubject">
                                                    🔗 Manajemen Mapel Gabungan
                                                </button>
                                                <button type="submit" form="formPlotting" class="btn btn-success btn-sm font-weight-bold shadow-sm">
                                                    💾 Simpan Plotting
                                                </button>
                                            </div>
                                        </div>

                                        <form action="<?= base_url('admin/schedule/save-plotting') ?>" method="POST" id="formPlotting">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="ta" value="<?= $taId ?>">
                                            <input type="hidden" name="v" value="<?= $verId ?>">

                                            <div class="alert alert-info border-0 shadow-sm small py-2 mb-3">
                                                <i class="bi bi-info-circle-fill me-1"></i> Data guru ditarik otomatis dari Rombel. Jika Anda membuat Mapel Gabungan, mapel aslinya akan disembunyikan.
                                            </div>

                                            <div class="accordion" id="accordionMapel">
                                                
                                                <?php foreach($combinedSubjects as $comb): 
                                                    $cId = $comb['id']; $cName = $comb['combined_name'];
                                                    $firstMapelId = $comb['detail_ids'][0] ?? null; 
                                                ?>
                                                <div class="accordion-item mb-2 border border-success rounded shadow-sm">
                                                    <h2 class="accordion-header d-flex align-items-center bg-light" id="headingComb<?= $cId ?>">
                                                        <div class="form-check form-switch ms-3 me-2" style="transform: scale(1.2);">
                                                            <input class="form-check-input toggle-mapel" type="checkbox" name="combined_active[<?= $cId ?>]" value="1" data-target="collapseComb<?= $cId ?>" checked>
                                                        </div>
                                                        <button class="accordion-button bg-transparent fw-bold text-success border-0 shadow-none ps-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseComb<?= $cId ?>" style="pointer-events: none;">
                                                            🔗 <?= esc($cName) ?> <span class="badge bg-secondary ms-2 fw-normal" style="font-size:10px;">(<?= esc($comb['detail_names_string']) ?>)</span>
                                                        </button>
                                                    </h2>
                                                    <div id="collapseComb<?= $cId ?>" class="accordion-collapse collapse show">
                                                        <div class="accordion-body p-0">
                                                            <table class="table table-sm table-hover mb-0" style="font-size: 13px;">
                                                                <thead class="bg-success text-white">
                                                                    <tr><th class="ps-3" width="30%">Kelas / Rombel</th><th width="45%">Guru Pengampu (Auto)</th><th width="25%" class="text-center">Target JP Gabungan</th></tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach($rombels as $rombel): 
                                                                        $rId = $rombel['id'];
                                                                        $tData = $plottingDataCombined[$cId][$rId] ?? null; 
                                                                        $cstData = $assignedTeachers[$firstMapelId][$rId] ?? null;
                                                                    ?>
                                                                    <tr>
                                                                        <td class="align-middle ps-3 fw-bold"><?= esc($rombel['class_name']) ?> - <?= esc($rombel['rombel_name']) ?></td>
                                                                        <td class="align-middle pe-3">
                                                                            <?php if($cstData): ?>
                                                                                <span class="badge bg-success px-2 py-1 shadow-sm fs-6"><i class="bi bi-person-check-fill me-1"></i> <?= esc($cstData['teacher_name']) ?></span>
                                                                                <input type="hidden" name="teacher_id_combined[<?= $cId ?>][<?= $rId ?>]" value="<?= $cstData['teacher_id'] ?>">
                                                                            <?php else: ?>
                                                                                <span class="badge bg-danger px-2 py-1 shadow-sm">Belum Diplot di Kelas</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="align-middle px-3">
                                                                            <div class="input-group input-group-sm">
                                                                                <input type="number" name="target_jp_combined[<?= $cId ?>][<?= $rId ?>]" class="form-control text-center fw-bold text-success" value="<?= $tData['target_jp'] ?? 0 ?>" min="0" <?= empty($cstData) ? 'disabled' : '' ?>>
                                                                                <span class="input-group-text bg-white">JP</span>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>

                                                <?php foreach($subjects as $mapel): 
                                                    $mId = $mapel['id'];
                                                    if(in_array($mId, $combinedChildIds)) continue; 
                                                    $mName = $mapel['subject_name'] ?? $mapel['nama_mapel'] ?? 'Mapel ID: '.$mId;
                                                ?>
                                                <div class="accordion-item mb-2 border rounded shadow-sm">
                                                    <h2 class="accordion-header d-flex align-items-center bg-light" id="heading<?= $mId ?>">
                                                        <div class="form-check form-switch ms-3 me-2" style="transform: scale(1.2);">
                                                            <input class="form-check-input toggle-mapel" type="checkbox" name="mapel_active[<?= $mId ?>]" value="1" data-target="collapse<?= $mId ?>" checked>
                                                        </div>
                                                        <button class="accordion-button bg-transparent fw-bold text-dark border-0 shadow-none ps-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $mId ?>" style="pointer-events: none;">
                                                            📚 <?= esc($mName) ?>
                                                        </button>
                                                    </h2>
                                                    <div id="collapse<?= $mId ?>" class="accordion-collapse collapse show">
                                                        <div class="accordion-body p-0">
                                                            <table class="table table-sm table-hover mb-0" style="font-size: 13px;">
                                                                <thead class="bg-dark text-white">
                                                                    <tr><th class="ps-3" width="30%">Kelas / Rombel</th><th width="45%">Guru Pengampu (Auto)</th><th width="25%" class="text-center">Target JP / Minggu</th></tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach($rombels as $rombel): 
                                                                        $rId = $rombel['id'];
                                                                        $tData = $plottingDataNormal[$mId][$rId] ?? null; 
                                                                        $cstData = $assignedTeachers[$mId][$rId] ?? null;
                                                                    ?>
                                                                    <tr>
                                                                        <td class="align-middle ps-3 fw-bold"><?= esc($rombel['class_name']) ?> - <?= esc($rombel['rombel_name']) ?></td>
                                                                        <td class="align-middle pe-3">
                                                                            <?php if($cstData): ?>
                                                                                <span class="badge bg-success px-2 py-1 shadow-sm fs-6"><i class="bi bi-person-check-fill me-1"></i> <?= esc($cstData['teacher_name']) ?></span>
                                                                                <input type="hidden" name="teacher_id[<?= $mId ?>][<?= $rId ?>]" value="<?= $cstData['teacher_id'] ?>">
                                                                            <?php else: ?>
                                                                                <span class="badge bg-danger px-2 py-1 shadow-sm">Belum Diplot di Kelas</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td class="align-middle px-3">
                                                                            <div class="input-group input-group-sm">
                                                                                <input type="number" name="target_jp[<?= $mId ?>][<?= $rId ?>]" class="form-control text-center fw-bold text-primary" value="<?= $tData['target_jp'] ?? 0 ?>" min="0" <?= empty($cstData) ? 'disabled' : '' ?>>
                                                                                <span class="input-group-text bg-white">JP</span>
                                                                            </div>
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

                                            <div class="mt-3 text-end pb-3">
                                                <button type="submit" class="btn btn-success font-weight-bold shadow-sm px-4">
                                                    💾 Simpan Semua Plotting
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>

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
                                            $groupedSlots = []; foreach ($timeSlots as $ts) { $day = ucfirst(strtolower(trim($ts['day_name']))); if (!isset($groupedSlots[$day])) $groupedSlots[$day] = []; $groupedSlots[$day][] = $ts; }
                                            $urutanStandar = ['Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7];
                                            uksort($groupedSlots, function($a, $b) use ($urutanStandar) { return ($urutanStandar[$a] ?? 99) <=> ($urutanStandar[$b] ?? 99); });
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
                                                                        <tr><th class="text-center">Ke-</th><th>Waktu</th><th>Label Slot</th><th class="text-center">Aksi</th></tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach($slots as $slot): 
                                                                            $durasi = (strtotime($slot['end_time']) - strtotime($slot['start_time'])) / 60;
                                                                        ?>
                                                                        <tr>
                                                                            <td class="text-center align-middle fw-bold"><?= $slot['slot_number'] ?></td>
                                                                            <td class="align-middle"><?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?></td>
                                                                            <td class="align-middle"><?= esc($slot['slot_label']) ?></td>
                                                                            <td class="text-center align-middle">
                                                                                <button type="button" class="btn-emoji btn-edit-slot shadow-sm" data-id="<?= $slot['id'] ?>" data-label="<?= esc($slot['slot_label']) ?>" data-duration="<?= $durasi ?>">✏️</button>
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
                        <div class="mb-2"><label class="form-label font-weight-bold small">Kode / Versi (Misal: V1)</label><input type="text" name="version_name" class="form-control form-control-sm" required></div>
                        <div class="mb-2"><label class="form-label font-weight-bold small">Judul (Misal: Jadwal Ramadhan)</label><input type="text" name="schedule_title" class="form-control form-control-sm" required></div>
                    </div>
                    <div class="modal-footer py-1"><button type="submit" class="btn btn-success btn-sm w-100 fw-bold">💾 Simpan Versi</button></div>
                </form>
            </div>
        </div>
    </div>

    <?php if($activeTab == 'plotting'): ?>
    <div class="modal fade" id="modalCombinedSubject" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title font-weight-bold">🔗 Manajemen Mapel Gabungan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <h6 class="fw-bold border-bottom pb-2">Daftar Mapel Gabungan Tersimpan</h6>
                    <?php if(empty($combinedSubjects)): ?>
                        <div class="alert alert-secondary small py-2 mb-4">Belum ada mapel gabungan yang dibuat.</div>
                    <?php else: ?>
                        <div class="table-responsive mb-4 shadow-sm border rounded">
                            <table class="table table-sm table-hover mb-0 bg-white" style="font-size: 13px;">
                                <thead class="table-dark"><tr><th class="ps-2" width="25%">Nama Gabungan</th><th width="60%">Mapel Yang Tergabung</th><th class="text-center" width="15%">Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($combinedSubjects as $comb): ?>
                                    <tr>
                                        <td class="align-middle fw-bold ps-2 text-primary">🔗 <?= esc($comb['combined_name']) ?></td>
                                        <td class="align-middle"><?= esc($comb['detail_names_string']) ?></td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn-emoji shadow-sm btn-edit-combined" data-id="<?= $comb['id'] ?>" data-name="<?= esc($comb['combined_name']) ?>" data-ids="<?= $comb['detail_ids_string'] ?>" title="Edit Gabungan">✏️</button>
                                            <a href="<?= base_url('admin/schedule/delete-combined/'.$comb['id'].'?'.$urlParam) ?>" class="btn-emoji shadow-sm" onclick="return confirm('Yakin ingin menghapus mapel gabungan ini?')" title="Hapus">🗑️</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('admin/schedule/save-combined') ?>" method="POST" class="border p-3 rounded bg-white shadow-sm border-success">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ta" value="<?= $taId ?>">
                        <input type="hidden" name="v" value="<?= $verId ?>">
                        <h6 class="fw-bold text-success border-bottom pb-2 mb-3">➕ Buat Mapel Gabungan Baru</h6>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted">Nama Singkatan / Gabungan (Cth: Diniyah 1)</label><input type="text" name="combined_name" class="form-control fw-bold" required></div>
                        <label class="form-label small fw-bold text-muted">Centang Mapel Yang Akan Digabung:</label>
                        <div class="row g-2">
                            <?php foreach($subjects as $m): ?>
                                <div class="col-md-6">
                                    <div class="form-check border p-1 rounded bg-light">
                                        <input class="form-check-input ms-1" type="checkbox" name="subject_ids[]" value="<?= $m['id'] ?>" id="chk_mapel_<?= $m['id'] ?>">
                                        <label class="form-check-label fw-bold ms-2" style="font-size: 13px;" for="chk_mapel_<?= $m['id'] ?>"><?= esc($m['subject_name'] ?? $m['nama_mapel']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-end"><button type="submit" class="btn btn-success fw-bold shadow-sm px-4">💾 Simpan Mapel Gabungan</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditCombined" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/update-combined') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ta" value="<?= $taId ?>"><input type="hidden" name="v" value="<?= $verId ?>"><input type="hidden" name="id" id="edit_combined_id">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title font-weight-bold text-warning">✏️ Edit Mapel Gabungan</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="mb-3"><label class="form-label font-weight-bold small">Nama Gabungan</label><input type="text" name="combined_name" id="edit_combined_name" class="form-control fw-bold" required></div>
                        <label class="form-label font-weight-bold small">Ubah Centang Mapel:</label>
                        <div class="row g-2" id="edit_checkbox_container">
                            <?php foreach($subjects as $m): ?>
                                <div class="col-md-6">
                                    <div class="form-check border p-1 rounded bg-white shadow-sm">
                                        <input class="form-check-input ms-1 edit-chk" type="checkbox" name="subject_ids[]" value="<?= $m['id'] ?>" id="editchk_<?= $m['id'] ?>">
                                        <label class="form-check-label fw-bold ms-2" style="font-size: 13px;" for="editchk_<?= $m['id'] ?>"><?= esc($m['subject_name'] ?? $m['nama_mapel']) ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer py-1"><button type="submit" class="btn btn-warning btn-sm w-100 font-weight-bold text-dark">💾 Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($activeTab == 'waktu' && !empty($verId)): ?>
    <div class="modal fade" id="modalGenerateWaktu" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form action="<?= base_url('admin/schedule/generate-time') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="ta" value="<?= $taId ?>"><input type="hidden" name="v" value="<?= $verId ?>"><div class="modal-header bg-primary text-white py-2"><h6 class="modal-title font-weight-bold">⚙️ Generator Slot Waktu</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light"><div class="mb-3"><label class="form-label font-weight-bold small text-primary border-bottom w-100 pb-1">1. Centang Hari Target:</label><div class="d-flex flex-wrap gap-3"><?php foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?><div class="form-check"><input class="form-check-input" type="checkbox" name="day_names[]" value="<?= $h ?>" id="chk_<?= $h ?>"><label class="form-check-label fw-bold" for="chk_<?= $h ?>"><?= $h ?></label></div><?php endforeach; ?></div></div><label class="form-label font-weight-bold small text-primary border-bottom w-100 pb-1">2. Pengaturan Jam:</label><div class="row"><div class="col-md-6 mb-3"><label class="form-label font-weight-bold small">Jam Mulai Masuk</label><input type="time" name="start_time" class="form-control form-control-sm" value="07:00" required></div><div class="col-md-6 mb-3"><label class="form-label font-weight-bold small">Durasi 1 JP (Menit)</label><input type="number" name="interval_minutes" class="form-control form-control-sm" value="40" required></div></div><div class="mb-3"><label class="form-label font-weight-bold small">Jumlah Baris/Slot</label><input type="number" name="total_slots" class="form-control form-control-sm" value="8" required></div></div><div class="modal-footer py-1"><button type="submit" class="btn btn-primary btn-sm w-100 font-weight-bold">⚙️ Mulai Generate</button></div></form></div></div></div>
    <div class="modal fade" id="modalEditSlot" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><form action="<?= base_url('admin/schedule/update-time') ?>" method="POST"><?= csrf_field() ?><input type="hidden" name="ta" value="<?= $taId ?>"><input type="hidden" name="v" value="<?= $verId ?>"><input type="hidden" name="id" id="edit_slot_id"><div class="modal-header bg-dark text-white py-2"><h6 class="modal-title font-weight-bold text-warning">✏️ Penyesuaian Slot Waktu</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body bg-light"><div class="mb-3"><label class="form-label font-weight-bold small text-muted">Label Waktu / Nama JP</label><input type="text" name="slot_label" id="edit_slot_label" class="form-control form-control-sm" required></div><div class="mb-3"><label class="form-label font-weight-bold small text-muted">Durasi Baru (Menit)</label><div class="input-group input-group-sm"><input type="number" name="duration_minutes" id="edit_duration_minutes" class="form-control font-weight-bold" required><span class="input-group-text bg-white">Menit</span></div></div></div><div class="modal-footer py-1"><button type="submit" class="btn btn-warning btn-sm w-100 font-weight-bold text-dark">💾 Simpan & Geser Waktu</button></div></form></div></div></div>
    <?php endif; ?>

    <?php if($activeTab == 'matriks'): ?>
    <div class="modal fade" id="modalActivity" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title font-weight-bold">✨ Manajemen Kegiatan Umum</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <form action="<?= base_url('admin/schedule/save-activity') ?>" method="POST" class="mb-4 border p-3 rounded bg-white shadow-sm border-info">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ta" value="<?= $taId ?>"><input type="hidden" name="v" value="<?= $verId ?>">
                        <h6 class="fw-bold text-info border-bottom pb-2 mb-3">➕ Tambah Kegiatan Baru</h6>
                        <div class="input-group input-group-sm">
                            <input type="text" name="activity_name" class="form-control font-weight-bold" placeholder="Cth: Istirahat 1, Upacara, Shalat Dhuha..." required>
                            <button class="btn btn-info text-white fw-bold px-3" type="submit">Simpan</button>
                        </div>
                    </form>

                    <h6 class="fw-bold border-bottom pb-2">Daftar Kegiatan Tersimpan</h6>
                    <div class="table-responsive shadow-sm border rounded">
                        <table class="table table-sm table-hover mb-0 bg-white" style="font-size: 13px;">
                            <thead class="table-dark"><tr><th class="ps-3">Nama Kegiatan</th><th class="text-center" width="25%">Aksi</th></tr></thead>
                            <tbody>
                                <?php if(empty($kegiatan)): ?>
                                    <tr><td colspan="2" class="text-center text-muted fst-italic">Belum ada kegiatan.</td></tr>
                                <?php else: ?>
                                    <?php foreach($kegiatan as $act): ?>
                                    <tr>
                                        <td class="align-middle fw-bold ps-3 text-dark">☕ <?= esc($act['activity_name']) ?></td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn-emoji shadow-sm btn-edit-activity" data-id="<?= $act['id'] ?>" data-name="<?= esc($act['activity_name']) ?>" title="Edit">✏️</button>
                                            <a href="<?= base_url('admin/schedule/delete-activity/'.$act['id'].'?'.$urlParam) ?>" class="btn-emoji shadow-sm" onclick="return confirm('Yakin ingin menghapus kegiatan ini?')" title="Hapus">🗑️</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditActivity" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/update-activity') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="ta" value="<?= $taId ?>"><input type="hidden" name="v" value="<?= $verId ?>"><input type="hidden" name="id" id="edit_act_id">
                    <div class="modal-header bg-dark text-white py-2">
                        <h6 class="modal-title font-weight-bold text-warning">✏️ Edit Kegiatan</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <label class="form-label font-weight-bold small">Nama Kegiatan</label>
                        <input type="text" name="activity_name" id="edit_act_name" class="form-control form-control-sm fw-bold" required>
                    </div>
                    <div class="modal-footer py-1"><button type="submit" class="btn btn-warning btn-sm w-100 font-weight-bold text-dark">💾 Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="modalCopyVersion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= base_url('admin/schedule/copy-version') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="target_ta_id" value="<?= $taId ?>">
                    
                    <div class="modal-header bg-info text-white py-2">
                        <h6 class="modal-title font-weight-bold">♻️ Salin Jadwal (Lintas Semester)</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <div class="alert alert-warning p-2 small mb-3 border-0 shadow-sm">
                            <i class="bi bi-info-circle-fill"></i> Sistem akan menduplikasi <strong>Slot Waktu, Beban Target JP, dan Formasi Matriks</strong> secara utuh. Jika menyalin beda semester, nama rombel akan dicocokkan secara otomatis!
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small text-muted">Pilih Jadwal Sumber (Yang mau disalin):</label>
                            <select name="source_version_id" class="form-select form-select-sm font-weight-bold border-info shadow-sm" required>
                                <option value="">-- Pilih Jadwal Sumber --</option>
                                <?php foreach($allVersions ?? [] as $ver): ?>
                                    <option value="<?= $ver['id'] ?>">SMT <?= $ver['semester'] ?> (<?= $ver['academic_year'] ?>) - <?= esc($ver['version_name']) ?> <?= !empty($ver['schedule_title']) ? '('.esc($ver['schedule_title']).')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label font-weight-bold small text-success">Nama Kode Versi Baru:</label>
                            <input type="text" name="new_version_name" class="form-control form-control-sm border-success fw-bold shadow-sm" placeholder="Contoh: V2 atau Copy V1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-weight-bold small text-success">Judul Jadwal Baru:</label>
                            <input type="text" name="new_schedule_title" class="form-control form-control-sm border-success fw-bold shadow-sm" placeholder="Contoh: Jadwal Normal Genap" required>
                        </div>
                        
                        <div class="text-muted mt-3" style="font-size: 11px; line-height: 1.2;">
                            *Jadwal baru akan diterbitkan di Tahun Ajaran / Semester yang sedang Anda buka saat ini (Target: <strong>Semester aktif di layar</strong>).
                        </div>
                    </div>
                    <div class="modal-footer py-1">
                        <button type="submit" class="btn btn-info btn-sm w-100 font-weight-bold text-white shadow-sm">♻️ Eksekusi Salin Master Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Fix untuk onchange manual (Mengganti onchange di HTML)
        const selectTa = document.getElementById('selectTa');
        if(selectTa) selectTa.addEventListener('change', function() { this.form.submit(); });

        const selectVersion = document.getElementById('selectVersion');
        if(selectVersion) {
            selectVersion.addEventListener('change', function() {
                if(this.value === 'NEW') {
                    new bootstrap.Modal(document.getElementById('modalNewVersion')).show();
                    this.value = '<?= $verId ?>';
                } else {
                    this.form.submit();
                }
            });
        }

            // JS Untuk Toggle Mapel Plotting
            document.querySelectorAll('.toggle-mapel').forEach(toggle => {
                toggle.addEventListener('change', function() {
                    let targetId = this.getAttribute('data-target');
                    let collapseElement = document.getElementById(targetId);
                    let bsCollapse = bootstrap.Collapse.getInstance(collapseElement);
                    if (!bsCollapse) bsCollapse = new bootstrap.Collapse(collapseElement, {toggle: false});
                    if (this.checked) { bsCollapse.show(); } else { bsCollapse.hide(); collapseElement.querySelectorAll('input[type="number"]').forEach(input => input.value = 0); }
                });
            });

            // JS Untuk Edit Waktu Tab 3
            document.querySelectorAll('.btn-edit-slot').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_slot_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_slot_label').value = this.getAttribute('data-label');
                    document.getElementById('edit_duration_minutes').value = this.getAttribute('data-duration');
                    new bootstrap.Modal(document.getElementById('modalEditSlot')).show();
                });
            });

            // JS Untuk Edit Mapel Gabungan
            document.querySelectorAll('.btn-edit-combined').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_combined_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_combined_name').value = this.getAttribute('data-name');
                    document.querySelectorAll('.edit-chk').forEach(chk => chk.checked = false);
                    let idsString = this.getAttribute('data-ids');
                    if (idsString) {
                        let idsArray = idsString.split(',');
                        idsArray.forEach(id => {
                            let chk = document.getElementById('editchk_' + id);
                            if (chk) chk.checked = true;
                        });
                    }
                    let modalList = bootstrap.Modal.getInstance(document.getElementById('modalCombinedSubject'));
                    if(modalList) modalList.hide();
                    new bootstrap.Modal(document.getElementById('modalEditCombined')).show();
                });
            });
        });

        // JS Untuk Edit Kegiatan Umum
            document.querySelectorAll('.btn-edit-activity').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_act_id').value = this.getAttribute('data-id');
                    document.getElementById('edit_act_name').value = this.getAttribute('data-name');
                    
                    let modalList = bootstrap.Modal.getInstance(document.getElementById('modalActivity'));
                    if(modalList) modalList.hide();
                    
                    new bootstrap.Modal(document.getElementById('modalEditActivity')).show();
                });
            });

        // ==========================================
            // RADAR BENTROK & OVERLOAD (LIVE JAVASCRIPT)
            // ==========================================
            function checkClashes() {
                let hasGlobalClash = false;
                let hasOverload = false;

                // 0. Bersihkan semua peringatan sebelumnya
                document.querySelectorAll('.matriks-select').forEach(sel => {
                    sel.classList.remove('is-clash', 'is-overload');
                });

                // 1. CEK GURU BENTROK (Per Baris Waktu / TR)
                document.querySelectorAll('tbody tr').forEach(tr => {
                    let selects = tr.querySelectorAll('.matriks-select');
                    let teacherMap = {};

                    selects.forEach(sel => {
                        let val = sel.value;
                        if (val && !val.startsWith('ACT_')) {
                            let parts = val.split('_');
                            if (parts.length >= 3) {
                                let teacherId = parts[2];
                                if (!teacherMap[teacherId]) teacherMap[teacherId] = [];
                                teacherMap[teacherId].push(sel);
                            }
                        }
                    });

                    // Nyalakan Merah jika Guru dipakai > 1
                    for (let tId in teacherMap) {
                        if (teacherMap[tId].length > 1) {
                            hasGlobalClash = true;
                            teacherMap[tId].forEach(sel => sel.classList.add('is-clash'));
                        }
                    }
                });

                // 2. CEK BEBAN JP BERLEBIH (Per Kolom Rombel)
                let subjectCountMap = {}; // Format: { rombelId: { subjectCode: { count: X, target: Y, elements: [] } } }

                document.querySelectorAll('.matriks-select').forEach(sel => {
                    let val = sel.value;
                    if (val && !val.startsWith('ACT_')) {
                        let rombelId = sel.getAttribute('data-rombel');
                        let parts = val.split('_');
                        let subjectCode = parts[0] + '_' + parts[1]; // Misal: SUB_14
                        
                        let opt = sel.options[sel.selectedIndex];
                        let targetJp = parseInt(opt.getAttribute('data-target-jp')) || 0;

                        if (!subjectCountMap[rombelId]) subjectCountMap[rombelId] = {};
                        if (!subjectCountMap[rombelId][subjectCode]) {
                            subjectCountMap[rombelId][subjectCode] = { count: 0, target: targetJp, elements: [] };
                        }

                        subjectCountMap[rombelId][subjectCode].count++;
                        subjectCountMap[rombelId][subjectCode].elements.push(sel);
                    }
                });

                // Nyalakan Kuning jika JP melebihi target
                for (let rId in subjectCountMap) {
                    for (let subCode in subjectCountMap[rId]) {
                        let data = subjectCountMap[rId][subCode];
                        if (data.count > data.target) {
                            hasOverload = true;
                            data.elements.forEach(sel => {
                                // Jika tidak sedang bentrok merah, beri warna kuning
                                if (!sel.classList.contains('is-clash')) {
                                    sel.classList.add('is-overload');
                                }
                            });
                        }
                    }
                }

                // 3. KUNCI TOMBOL SIMPAN
                let btnSave = document.getElementById('btnSaveMatriks');
                if (btnSave) {
                    if (hasGlobalClash || hasOverload) {
                        btnSave.disabled = true;
                        if (hasGlobalClash) {
                            btnSave.innerHTML = '⛔ Perbaiki Guru Bentrok (Merah)';
                            btnSave.className = 'btn btn-danger btn-sm font-weight-bold shadow-sm';
                        } else {
                            btnSave.innerHTML = '⚠️ Perbaiki JP Berlebih (Kuning)';
                            btnSave.className = 'btn btn-warning btn-sm font-weight-bold shadow-sm text-dark';
                        }
                    } else {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '💾 Simpan Semua Perubahan';
                        btnSave.className = 'btn btn-success btn-sm font-weight-bold shadow-sm';
                    }
                }
            }

            document.querySelectorAll('.matriks-select').forEach(sel => {
                sel.addEventListener('change', checkClashes);
            });
            checkClashes(); // Panggil saat awal load
    </script>
</body>
</html>