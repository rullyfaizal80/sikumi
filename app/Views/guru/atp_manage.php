<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alur Tujuan Pembelajaran (ATP) - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; font-family: 'Source Sans Pro', sans-serif; }
        .table-atp th { background-color: #002060; color: #ffffff; text-align: center; vertical-align: middle; font-size: 11px; padding: 6px; }
        .table-atp td { vertical-align: top; font-size: 11px; line-height: 1.3; padding: 6px; }
        
        .checklist-box { max-height: 140px; overflow-y: auto; padding-right: 5px; }
        .custom-check { display: flex; align-items: flex-start; margin-bottom: 2px; }
        .custom-check input { margin-top: 2px; margin-right: 5px; width: 12px; height: 12px; cursor: pointer; }
        .custom-check label { font-size: 10px; font-weight: 500; cursor: pointer; margin-bottom: 0; line-height: 1.2; }
        
        .btn-move { padding: 0px 4px; font-size: 14px; line-height: 1; border-color: #ccc; background: #fff; cursor: pointer; }
        .btn-move:hover { background: #e9ecef; }
        tr { transition: background-color 0.3s ease; }
    </style>
</head>
<body class="layout-fixed">
    <?php
        // Hitung total estimasi JP ATP secara aman dari data yang ada
        $totalJpAtp = 0;
        if (!empty($dataAtp)) {
            foreach ($dataAtp as $row) {
                $totalJpAtp += (int)($row['estimasi_jp'] ?? $row['jp'] ?? 0);
            }
        }
        $jpTersedia = isset($totalJpTersedia) ? $totalJpTersedia : 0;
    ?>

    <div class="wrapper p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📑 Alur Tujuan Pembelajaran (ATP)</h3>
                <p class="text-muted mb-0">Integrasi Kurikulum Merdeka (Deep Learning) & KBC Kemenag</p>
            </div>
            <div>
                <button class="btn btn-primary btn-sm font-weight-bold shadow-sm me-2">🖨️ Cetak ATP</button>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">🏠 Dashboard</a>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-lg mb-4">
            <div class="card-body bg-white p-3">
                <form action="<?= base_url('guru/atp') ?>" method="GET" class="row g-3 align-items-end justify-content-center">
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted">Mata Pelajaran (Reguler & Gabungan)</label>
                        <select name="mapel_id" class="form-select form-select-sm font-weight-bold text-primary border-primary" onchange="this.form.submit()">
                            <?php if(empty($daftarMapel)): ?>
                                <option value="">- Anda Belum Memiliki Jadwal Mapel -</option>
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

                    <div class="col-md-4">
                        <label class="small font-weight-bold text-secondary">Analisis Beban Waktu Terkecil Tingkat</label>
                        <div class="d-flex gap-2">
                            <div class="form-control form-control-sm bg-light border-success text-success font-weight-bold text-center w-50" title="Alokasi JP paling sedikit di antara seluruh rombel paralel tingkat ini">
                                ⏳ Min Tersedia: <?= $jpTersedia ?> JP
                            </div>
                            
                            <?php $warnaAtp = ($totalJpAtp > $jpTersedia && $jpTersedia > 0) ? 'border-danger text-danger' : 'border-primary text-primary'; ?>
                            <div class="form-control form-control-sm bg-light <?= $warnaAtp ?> font-weight-bold text-center w-50" title="Total Akumulasi JP yang telah disusun dalam tabel ATP">
                                📚 Target ATP: <?= $totalJpAtp ?> JP
                            </div>
                        </div>
                        <?php if($totalJpAtp > $jpTersedia && $jpTersedia > 0): ?>
                            <small class="text-danger font-weight-bold d-block mt-1 animate__animated animate__headShake" style="font-size: 10px; line-height: 1.1;">
                                ⚠️ Perhatian: Beban JP ATP melebihi waktu minimum paralel!
                            </small>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow border-top border-success border-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <h6 class="font-weight-bold my-0">Tabel Distribusi ATP - Rombel <?= esc($namaRombelAktif) ?></h6>
                <button class="btn btn-sm btn-outline-success font-weight-bold">✨ AI Generate Kognitif & Sikap</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-atp m-0">
                        <thead>
                            <tr>
                                <th width="4%">Aksi</th>
                                <th width="10%">Tanggal</th>
                                <th width="4%">No</th>
                                <th width="22%">Tujuan Pembelajaran</th>
                                <th width="12%">Lingkup Materi</th>
                                <th width="12%">Aktivitas Kognitif</th>
                                <th width="16%">8 Dimensi Profil Lulusan</th>
                                <th width="16%">Lima Pilar Panca Cinta</th>
                                <th width="4%">JP</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-atp">
                            <?php if(empty($dataAtp)): ?>
                                <tr><td colspan="9" class="text-center py-4 text-danger font-weight-bold">
                                    <i class="bi bi-exclamation-triangle"></i> Belum ada data Tujuan Pembelajaran.<br>
                                    <span class="text-muted small font-weight-normal">Pastikan Anda telah melakukan <a href="<?= base_url('guru/analisis-cp') ?>">Analisis CP</a> untuk mata pelajaran dan kelas ini.</span>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach($dataAtp as $idx => $row): ?>
                                <tr>
                                    <td class="text-center align-middle bg-light">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'up')" title="Geser ke Atas">▲</button>
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'down')" title="Geser ke Bawah">▼</button>
                                        </div>
                                    </td>
                                    
                                    <?php 
                                        $tglText = $row['tanggal'] ?? 'Jadwal Habis / Belum Diatur';
                                        $isHabis = (strpos($tglText, 'Habis') !== false || strpos($tglText, 'Belum') !== false);
                                        $colorClass = $isHabis ? 'text-danger' : 'text-success';
                                    ?>
                                    <td class="text-center font-weight-bold align-middle cell-tanggal <?= $colorClass ?>">
                                        <?= esc($tglText) ?>
                                    </td>

                                    <td class="text-center font-weight-bold align-middle cell-no"><?= esc($tingkatKelas) . '.' . ($idx + 1) ?></td>
                                    
                                    <td dir="auto" class="text-justify"><?= esc($row['tujuan_pembelajaran'] ?? $row['tp'] ?? '-') ?></td>
                                    <td class="font-weight-bold text-secondary"><?= esc($row['lingkup_materi'] ?? $row['lingkup'] ?? '-') ?></td>
                                    
                                    <td>
                                        <?php if(!empty($row['aktivitas_tarl'])): ?>
                                            <span class="text-muted small">Materi Tersedia:</span><br>
                                            <?= esc($row['aktivitas_tarl']) ?>
                                        <?php else: ?>
                                            <span class="text-muted italic">Menunggu AI...</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div class="checklist-box">
                                            <?php foreach($listProfilLulusan as $kode => $teks): ?>
                                            <div class="custom-check">
                                                <input type="checkbox" id="dpl_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>">
                                                <label for="dpl_<?= $idx ?>_<?= $kode ?>"><b><?= $kode ?></b>: <?= $teks ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="checklist-box">
                                            <?php foreach($listPancaCinta as $kode => $teks): ?>
                                            <div class="custom-check">
                                                <input type="checkbox" id="pc_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>">
                                                <label for="pc_<?= $idx ?>_<?= $kode ?>"><b><?= $kode ?></b>: <?= $teks ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center font-weight-bold align-middle bg-light" style="font-size: 13px;">
                                        <?= esc($row['estimasi_jp'] ?? $row['jp'] ?? 0) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if(!empty($dataAtp)): ?>
            <div class="card-footer bg-white text-end py-3">
                <button type="button" class="btn btn-success font-weight-bold shadow-sm px-4">💾 Simpan Susunan ATP</button>
            </div>
            <?php endif; ?>
            
        </div>

    </div>

    <script>
        let arrTanggal = [];
        let tingkatKelas = '<?= esc($tingkatKelas) ?>';

        document.addEventListener("DOMContentLoaded", function() {
            let rows = document.querySelectorAll("#tbody-atp tr");
            rows.forEach(r => {
                let cellTgl = r.querySelector('.cell-tanggal');
                if(cellTgl) arrTanggal.push(cellTgl.innerText.trim());
            });
        });

        function moveRow(btn, direction) {
            const row = btn.closest('tr');
            const tbody = row.parentNode;
            
            row.style.backgroundColor = "#fff3cd"; 
            setTimeout(() => { row.style.backgroundColor = ""; }, 500);

            if (direction === 'up' && row.previousElementSibling) {
                tbody.insertBefore(row, row.previousElementSibling);
            } else if (direction === 'down' && row.nextElementSibling) {
                tbody.insertBefore(row.nextElementSibling, row);
            }

            let allRows = tbody.querySelectorAll("tr");
            allRows.forEach((r, idx) => {
                let cellTgl = r.querySelector('.cell-tanggal');
                let cellNo = r.querySelector('.cell-no');
                
                if (cellTgl && arrTanggal[idx]) {
                    let currentText = arrTanggal[idx];
                    cellTgl.innerText = currentText;
                    
                    // Jaga konsistensi warna teks saat baris ditukar posisi (Drag & Drop Safe)
                    if (currentText.includes('Habis') || currentText.includes('Belum')) {
                        cellTgl.classList.remove('text-success');
                        cellTgl.classList.add('text-danger');
                    } else {
                        cellTgl.classList.remove('text-danger');
                        cellTgl.classList.add('text-success');
                    }
                }
                if (cellNo) {
                    cellNo.innerText = tingkatKelas + "." + (idx + 1);
                }
            });
        }
    </script>
</body>
</html>