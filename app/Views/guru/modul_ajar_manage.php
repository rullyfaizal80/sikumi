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
<?php
$db = \Config\Database::connect();
// Ambil infomasi master_class_id dan tahun akademik dari rombel saat ini
$currentRombel = $db->table('class_rombel')->where('id', $selectedRombelId)->get()->getRowArray();
$listRombelSatuTingkat = [];

if ($currentRombel) {
    $listRombelSatuTingkat = $db->table('class_rombel cr')
        ->select('cr.id, cr.rombel_name, mc.class_name')
        ->join('master_classes mc', 'mc.id = cr.master_class_id')
        ->where('cr.master_class_id', $currentRombel['master_class_id'])
        ->where('cr.id !=', $selectedRombelId) // Blokir rombel sendiri agar tidak muncul pilihan
        ->where('cr.academic_year_id', $currentRombel['academic_year_id'])
        ->orderBy('cr.rombel_name', 'ASC')
        ->get()->getResultArray();
}
?>


    <div class="wrapper p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📚 Manajemen Modul Ajar</h3>
                <p class="text-muted mb-0">Penyusunan Modul Ajar Berdasarkan Alur Tujuan Pembelajaran (ATP)</p>
            </div>
            <div>
               <button type="button" class="btn btn-info btn-sm shadow-sm fw-bold ms-2" data-bs-toggle="modal" data-bs-target="#modalCopyMasal">
                    💾 Salin Semua Modul
                </button>
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
                            <!-- PERBAIKAN: Teks diubah, memanggil JP Modul -->
                            <span class="d-block small text-muted font-weight-bold">Total JP Modul Ajar</span>
                            <span class="h5 font-weight-bold text-info mb-0"><?= $totalJpModul ?> JP</span>
                        </div>
                        
                        <!-- PERBAIKAN: Kotak ini akan otomatis hijau HANYA JIKA JP Modul sudah memenuhi seluruh JP ATP -->
                        <?php $isLengkap = ($totalJpAtp == $totalJpModul && $totalJpAtp > 0); ?>
                        <div class="d-inline-block bg-light rounded p-2 border <?= $isLengkap ? 'border-success' : 'border-warning' ?> text-center min-w-100">
                            <span class="d-block small text-muted font-weight-bold">Total JP ATP</span>
                            <span class="h5 font-weight-bold <?= $isLengkap ? 'text-success' : 'text-warning' ?> mb-0"><?= $totalJpAtp ?> JP</span>
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
                    <div class="alert alert-warning rounded-lg shadow-sm p-4 text-center border-warning">
                        <i class="bi bi-exclamation-triangle text-warning mb-2" style="font-size: 3rem; display: block;"></i>
                        <h5 class="font-weight-bold mt-2">Susunan ATP Belum Tersimpan!</h5>
                        <p class="text-muted mb-4">
                            Modul Ajar belum dapat disusun karena data Alur Tujuan Pembelajaran (ATP) untuk Rombel dan Mapel ini belum tersimpan ke dalam database.<br>
                            Silakan selesaikan penyusunan dan penentuan tanggal pada menu ATP terlebih dahulu.
                        </p>
                        <a href="<?= base_url('guru/atp?mapel_id='.$selectedMapelId.'&rombel_id='.$selectedRombelId) ?>" class="btn btn-primary font-weight-bold shadow-sm px-4 rounded-pill">
                            <i class="bi bi-arrow-right-circle me-1"></i> Buka Menu Penyusunan ATP
                        </a>
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

   <div class="modal fade" id="modalCopyMasal" tabindex="-1" aria-labelledby="modalCopyMasalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold" id="modalCopyMasalLabel">
                    <i class="bi bi-arrow-left-right me-2"></i> Salin Seluruh Modul Ajar (Satu Tingkat)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Fitur ini akan menyalin <strong>SELURUH</strong> Modul Ajar mapel ini dari Rombel saat ini ke Rombel tujuan dalam satu tingkat secara masal.</p>
                
                <div class="alert alert-warning py-2 small d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i> 
                    <div><strong>Perhatian:</strong> Data Modul Ajar lama yang ada di Rombel tujuan untuk mata pelajaran ini akan dihapus dan digantikan penuh oleh salinan baru ini!</div>
                </div>
                
                <div class="mt-3">
                    <label class="fw-bold text-dark small mb-1">Pilih Rombel Target (Tingkat yang Sama):</label>
                    <select id="target_rombel_copy" class="form-select form-select-sm">
                        <option value="">-- Pilih Rombel Tujuan --</option>
                        <?php if(!empty($listRombelSatuTingkat)): ?>
                            <?php foreach($listRombelSatuTingkat as $target): ?>
                                <option value="<?= $target['id'] ?>">
                                    Kelas <?= esc($target['class_name']) ?> - Rombel <?= esc($target['rombel_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ditemukan Rombel paralel lain di tingkat ini.</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info btn-sm fw-bold" onclick="eksekusiCopyModulMasal(this)">
                    <i class="bi bi-check2-circle me-1"></i> Mulai Salin Data
                </button>
            </div>
        </div>
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

        async function eksekusiCopyModulMasal(btn) {
    const toRombelId = document.getElementById('target_rombel_copy').value;
    const fromRombelId = "<?= $selectedRombelId ?>";
    const mapelId = "<?= $selectedMapelId ?>";

    if (!toRombelId) {
        alert("⚠️ Silakan pilih Rombel tujuan terlebih dahulu!");
        return;
    }

    if (!confirm("Apakah Anda yakin ingin menyalin SELURUH Modul Ajar ke rombel tersebut?\nProses ini tidak dapat dibatalkan.")) {
        return;
    }

    // Kunci Tombol & Tampilkan Efek Loading
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sedang Menyalin...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('from_rombel_id', fromRombelId);
    formData.append('to_rombel_id', toRombelId);
    formData.append('mapel_id', mapelId);

    try {
        const response = await fetch("<?= base_url('guru/modul-ajar/copy-all') ?>", {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();

        if (result.status === 'success') {
            alert("✅ " + result.message);
            
            // CARA MENUTUP MODAL DI BOOTSTRAP 5
            const modalEl = document.getElementById('modalCopyMasal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
            
            // Refresh halaman agar data terbaru termuat
            window.location.reload();
        } else {
            alert("⚠️ " + result.message);
        }
    } catch (error) {
        console.error(error);
        alert("❌ Terjadi kesalahan jaringan atau kendala server.");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
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

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
