<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Modul Ajar - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; font-family: 'Source Sans Pro', sans-serif; }
        .card-tp { transition: transform 0.2s, box-shadow 0.2s; border-radius: 10px; border-top: 4px solid #002060; }
        .card-tp:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
        .card-tp.done { border-top-color: #28a745; }
        .card-tp.pending { border-top-color: #ffc107; }
        
        .checkbox-gabung { transform: scale(1.3); cursor: pointer; }
        .modul-badge { font-size: 11px; padding: 5px 8px; border-radius: 20px; font-weight: 600; }
    </style>
</head>
<body class="layout-fixed">
    <div class="wrapper p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📚 Manajemen Modul Ajar</h3>
                <p class="text-muted mb-0">Penyusunan Modul Ajar Berdasarkan Alur Tujuan Pembelajaran (ATP)</p>
            </div>
            <div>
                <button class="btn btn-primary btn-sm font-weight-bold shadow-sm me-2">🖨️ Cetak Rekap Modul</button>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">🏠 Dashboard</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-lg mb-4">
            <div class="card-body bg-white p-3">
                <form action="<?= base_url('guru/modul-ajar') ?>" method="GET" class="row g-3 align-items-center justify-content-between">
                    
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted">Mata Pelajaran</label>
                        <select name="mapel_id" class="form-select form-select-sm font-weight-bold text-primary border-primary" onchange="this.form.submit()">
                            <?php if(empty($daftarMapel)): ?>
                                <option value="">- Belum Ada Mapel -</option>
                            <?php else: ?>
                                <?php foreach($daftarMapel as $m): ?>
                                    <option value="<?= esc($m['id']) ?>" <?= $selectedMapelId == $m['id'] ? 'selected' : '' ?>>
                                        <?= esc($m['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted">Pilih Rombel (Kelas Spesifik)</label>
                        <select name="rombel_id" class="form-select form-select-sm font-weight-bold border-success" onchange="this.form.submit()">
                            <?php foreach($daftarRombel as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $selectedRombelId == $r['id'] ? 'selected' : '' ?>>
                                    Rombel <?= esc($r['class_name'] ?? '') ?> <?= !empty($r['rombel_name']) ? '- ' . esc($r['rombel_name']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 text-end">
                        <div class="d-inline-block bg-light rounded p-2 border border-info text-center me-2 min-w-100">
                            <span class="d-block small text-muted font-weight-bold">Target JP Kaldik</span>
                            <span class="h5 font-weight-bold text-info mb-0"><?= $totalJpTersedia ?> JP</span>
                        </div>
                        <div class="d-inline-block bg-light rounded p-2 border <?= ($totalJpAtp == $totalJpTersedia) ? 'border-success' : 'border-warning' ?> text-center min-w-100">
                            <span class="d-block small text-muted font-weight-bold">Total JP ATP</span>
                            <span class="h5 font-weight-bold <?= ($totalJpAtp == $totalJpTersedia) ? 'text-success' : 'text-warning' ?> mb-0"><?= $totalJpAtp ?> JP</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="font-weight-bold text-secondary mb-0">Daftar TP Rombel <?= esc($namaRombelAktif) ?></h5>
            <button class="btn btn-success btn-sm font-weight-bold shadow-sm" id="btnGabungModul" style="display: none;">
                🔗 Buat 1 Modul untuk TP Terpilih
            </button>
        </div>

        <div class="row">
            <?php if(empty($dataAtpTersimpan)): ?>
                <div class="col-12 text-center py-5">
                    <h5 class="text-muted">Belum ada Alur Tujuan Pembelajaran (ATP) yang tersimpan.</h5>
                    <p class="small">Silakan kembali ke menu ATP dan simpan susunan materi Anda terlebih dahulu.</p>
                </div>
            <?php else: ?>
                
                <?php 
                // ==================================================
                // SIHIR KELOMPOK: Menyatukan TP yang Digabung
                // ==================================================
                $groupedAtp = [];
                foreach($dataAtpTersimpan as $item) { 
                    if (!empty($item['modul_id'])) {
                        $key = 'modul_' . $item['modul_id'];
                        if (!isset($groupedAtp[$key])) {
                            $groupedAtp[$key] = $item;
                            $groupedAtp[$key]['atp_ids_array'] = [$item['atp_id'] ?? $item['cp_detail_id']];
                            $groupedAtp[$key]['nomor_atp_array'] = [$item['nomor_atp']]; // Simpan Label TP
                            $groupedAtp[$key]['tanggal_array'] = [$item['tanggal']];     // Simpan Tanggal
                            $groupedAtp[$key]['tujuan_pembelajaran'] = "• " . $item['tp'];
                        } else {
                            $groupedAtp[$key]['tujuan_pembelajaran'] .= "<br>• " . $item['tp'];
                            $groupedAtp[$key]['atp_ids_array'][] = $item['atp_id'] ?? $item['cp_detail_id'];
                            $groupedAtp[$key]['nomor_atp_array'][] = $item['nomor_atp'];
                            $groupedAtp[$key]['tanggal_array'][] = $item['tanggal'];
                            $groupedAtp[$key]['estimasi_jp'] += $item['estimasi_jp']; 
                        }
                    } else {
                        $item['atp_ids_array'] = [$item['atp_id'] ?? $item['cp_detail_id']];
                        $item['nomor_atp_array'] = [$item['nomor_atp']];
                        $item['tanggal_array'] = [$item['tanggal']];
                        $item['tujuan_pembelajaran'] = $item['tp'];
                        $groupedAtp['single_' . ($item['atp_id'] ?? $item['cp_detail_id'])] = $item;
                    }
                }

                // Logika Penentuan is_merged yang lebih presisi
                foreach($groupedAtp as &$g) {
                    $g['is_merged'] = (count($g['atp_ids_array']) > 1);
                    $g['label_gabungan'] = implode(', ', $g['nomor_atp_array']);
                    $g['label_tanggal'] = implode('; ', array_unique($g['tanggal_array']));
                }
                unset($g);
                ?>

                <?php foreach($groupedAtp as $tp): ?>
                    <?php $isDone = !empty($tp['status_modul']) && $tp['status_modul'] == 1; ?>
                    
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card card-tp shadow-sm h-100 <?= $isDone ? 'done' : 'pending' ?>">
                            
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 border-bottom-0">
                                <div>
                                    <span class="badge bg-dark">TP <?= esc($tp['nomor_atp']) ?> <?= $tp['is_merged'] ? ' dkk' : '' ?></span>
                                    
                                    <?php if($isDone): ?>
                                        <span class="modul-badge bg-success text-white ms-1"><i class="bi bi-check-circle"></i> Sudah Dibuat</span>
                                    <?php else: ?>
                                        <span class="modul-badge bg-warning text-dark ms-1"><i class="bi bi-clock-history"></i> Belum Dibuat</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if(!$isDone): ?>
                                <div class="form-check m-0">
                                    <input class="form-check-input checkbox-gabung tp-checkbox" type="checkbox" value="<?= $tp['atp_id'] ?? $tp['cp_detail_id'] ?>" data-tgl="<?= esc($tp['tanggal']) ?>">
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body pt-2 d-flex flex-column">
                                
                                <div class="flex-grow-1">
                                    <?php if($tp['is_merged']): ?>
                                        <span class="badge bg-info text-dark mb-2 shadow-sm"><i class="bi bi-link-45deg"></i> Gabungan TP: <?= esc($tp['label_gabungan']) ?></span>
                                    <?php endif; ?>
                                    
                                    <h6 class="font-weight-bold text-primary mb-1"><?= esc($tp['lingkup_materi']) ?></h6>
                                    
                                    <p class="small text-justify mb-3" style="line-height: 1.4;">
                                        <?= $tp['is_merged'] ? $tp['tujuan_pembelajaran'] : esc($tp['tp']) ?>
                                    </p>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center text-muted small font-weight-bold border-top pt-2 mt-auto">
                                    <span><i class="bi bi-calendar-event text-danger"></i> <?= esc($tp['tanggal']) ?></span>
                                    <span><i class="bi bi-stopwatch text-success"></i> <?= esc($tp['estimasi_jp']) ?> JP</span>
                                </div>
                            </div>

                            <div class="card-footer bg-white border-top-0 py-2 text-end rounded-bottom">
                                <?php 
                                    $idLemparan = implode(',', $tp['atp_ids_array']);
                                    $tglLemparan = urlencode($tp['label_tanggal']);
                                ?>
                                <?php if($isDone): ?>
                                    <?php $urlEdit = base_url("guru/modul-ajar/create?atp_ids={$idLemparan}&rombel_id={$selectedRombelId}&mapel_id={$selectedMapelId}&tgl={$tglLemparan}"); ?>
                                    <a href="<?= $urlEdit ?>" class="btn btn-outline-success btn-sm font-weight-bold w-100">
                                        👁️ Lihat / Edit Modul
                                    </a>
                                <?php else: ?>
                                    <?php $urlCreate = base_url("guru/modul-ajar/create?atp_ids={$idLemparan}&rombel_id={$selectedRombelId}&mapel_id={$selectedMapelId}&tgl={$tglLemparan}"); ?>
                                    <a href="<?= $urlCreate ?>" class="btn btn-outline-primary btn-sm font-weight-bold w-100">
                                        📝 Susun Modul Ajar Baru
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <script>
        const checkboxes = document.querySelectorAll('.tp-checkbox');
        const btnGabung = document.getElementById('btnGabungModul');

        function updateTombolGabung() {
            let checkedBoxes = document.querySelectorAll('.tp-checkbox:checked');
            let checkedCount = checkedBoxes.length;
            
            if(checkedCount >= 1) { 
                btnGabung.style.display = 'inline-block';
                btnGabung.innerHTML = `📝 Susun 1 Modul untuk ${checkedCount} TP Terpilih`;
                
                let selectedIds = [];
                let selectedTgls = [];
                
                checkedBoxes.forEach(chk => {
                    selectedIds.push(chk.value);
                    let tgl = chk.getAttribute('data-tgl');
                    if(tgl && !selectedTgls.includes(tgl)) {
                        selectedTgls.push(tgl); // Menampung tanggal yang unik
                    }
                });
                
                btnGabung.onclick = function() {
                    let urlParams = new URLSearchParams();
                    urlParams.append('atp_ids', selectedIds.join(','));
                    urlParams.append('rombel_id', '<?= $selectedRombelId ?>');
                    urlParams.append('mapel_id', '<?= $selectedMapelId ?>');
                    urlParams.append('tgl', selectedTgls.join('; ')); // Lempar tanggal
                    
                    window.location.href = "<?= base_url('guru/modul-ajar/create') ?>?" + urlParams.toString();
                };
            } else {
                btnGabung.style.display = 'none';
            }
        }

        checkboxes.forEach(chk => {
            chk.addEventListener('change', updateTombolGabung);
        });
    </script>
    
    <?php if(session()->getFlashdata('success')): ?>
        <script>
            alert("✅ <?= session()->getFlashdata('success') ?>");
        </script>
    <?php endif; ?>
    
    <?php if(session()->getFlashdata('error')): ?>
        <script>
            alert("❌ <?= session()->getFlashdata('error') ?>");
        </script>
    <?php endif; ?>
</body>
</html>
