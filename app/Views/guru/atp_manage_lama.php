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

        <!-- INFO HEADER & FILTER -->
        <div class="card shadow-sm border-0 rounded-lg mb-4">
            <div class="card-body bg-white border-bottom p-3">
                <div class="row">
                    <div class="col-md-5 border-end">
                        <table class="table table-sm table-borderless font-weight-bold mb-0" style="font-size: 12px;">
                            <tr><td width="130" class="text-muted">Satuan Pendidikan</td><td width="10">:</td><td><?= esc($namaMadrasah) ?></td></tr>
                            <tr><td class="text-muted">Tahun Pelajaran</td><td>:</td><td><?= $tahunAktif ? esc($tahunAktif['academic_year']) : '-' ?> (Semester <?= $tahunAktif ? esc($tahunAktif['semester']) : '-' ?>)</td></tr>
                            <tr><td class="text-muted">Guru Pengampu</td><td>:</td><td class="text-primary"><?= esc($namaGuruCetak) ?></td></tr>
                        </table>
                    </div>
                    
                    <div class="col-md-7 ps-4">
                        <form action="<?= base_url('guru/atp') ?>" method="GET" class="row g-2 align-items-end h-100">
                            <div class="col-md-5">
                                <label class="small font-weight-bold text-muted">Mata Pelajaran Anda</label>
                                <select name="mapel_id" class="form-select form-select-sm font-weight-bold text-primary" onchange="this.form.submit()">
                                    <?php if(empty($daftarMapel)): ?>
                                        <option value="">- Belum Ada Mapel Terhubung -</option>
                                    <?php else: ?>
                                        <?php foreach($daftarMapel as $m): ?>
                                            <option value="<?= $m['id'] ?>" <?= $selectedMapelId == $m['id'] ? 'selected' : '' ?>><?= esc($m['subject_name'] ?? 'Mapel') ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="small font-weight-bold text-muted">Pilih Rombel (Kelas Spesifik)</label>
                                <select name="rombel_id" class="form-select form-select-sm font-weight-bold border-success" onchange="this.form.submit()">
                                    <?php foreach($daftarRombel as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= $selectedRombelId == $r['id'] ? 'selected' : '' ?>>
    <?= esc($r['class_name'] ?? '') ?> <?= !empty($r['rombel_name']) ? '- ' . esc($r['rombel_name']) : '' ?>
</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-dark w-100 font-weight-bold">Tampilkan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL ATP UTAMA -->
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
                                <th width="9%">Tanggal</th>
                                <th width="4%">No</th>
                                <th width="23%">Tujuan Pembelajaran</th>
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
                                    <span class="text-muted small font-weight-normal">Silakan selesaikan <a href="<?= base_url('guru/analisis-cp') ?>">Analisis CP</a> terlebih dahulu untuk Mapel & Kelas ini.</span>
                                </td></tr>
                            <?php else: ?>
                                <?php foreach($dataAtp as $idx => $row): ?>
                                <tr>
                                    <!-- Aksi Geser -->
                                    <td class="text-center align-middle bg-light">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'up')" title="Geser ke Atas">▲</button>
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'down')" title="Geser ke Bawah">▼</button>
                                        </div>
                                    </td>
                                    
                                    <!-- Elemen Statis: Tanggal & No -->
                                    <td class="text-center font-weight-bold text-primary align-middle cell-tanggal">
                                        <!-- Placeholder Tanggal, nantinya dari logika Kalender -->
                                        Menunggu Plot Jadwal
                                    </td>
                                    <td class="text-center font-weight-bold align-middle cell-no"><?= esc($tingkatKelas) . '.' . ($idx + 1) ?></td>
                                    
                                    <!-- Data Real dari DB (Analisis CP) -->
                                    <td dir="auto" class="text-justify"><?= esc($row['tujuan_pembelajaran'] ?? $row['tp'] ?? '-') ?></td>
                                    <td class="font-weight-bold text-secondary"><?= esc($row['lingkup_materi'] ?? $row['lingkup'] ?? '-') ?></td>
                                    
                                    <!-- Aktivitas Kognitif (Bisa ditarik dari aktivitas_tarl atau dikosongkan untuk AI) -->
                                    <td>
                                        <?php if(!empty($row['aktivitas_tarl'])): ?>
                                            <span class="text-muted small">Materi Tersedia:</span><br>
                                            <?= esc($row['aktivitas_tarl']) ?>
                                        <?php else: ?>
                                            <span class="text-muted italic">Menunggu AI...</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Ceklis 8 Dimensi DPL -->
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
                                    
                                    <!-- Ceklis 5 Pilar KBC -->
                                    <td>
                                        <div class="checklist-box">
                                            <?php foreach($listPancaCinta as $kode => $teks): ?>
                                            <div class="custom-check">
                                                <input type="checkbox" id="pc_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>">
                                                <label for="pc_<?= $idx ?>_<?= $kode ?>"><?= $teks ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    
                                    <!-- JP dari Analisis CP -->
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
            
            <!-- Tombol Simpan (Nantinya untuk menyimpan Ceklis dan Urutan ke Database) -->
            <?php if(!empty($dataAtp)): ?>
            <div class="card-footer bg-white text-end py-3">
                <button type="button" class="btn btn-success font-weight-bold shadow-sm px-4">💾 Simpan Susunan ATP</button>
            </div>
            <?php endif; ?>
            
        </div>

    </div>

    <!-- SCRIPT LOGIKA PERGESERAN BARIS -->
    <script>
        let arrTanggal = [];
        let tingkatKelas = '<?= esc($tingkatKelas) ?>';

        document.addEventListener("DOMContentLoaded", function() {
            let rows = document.querySelectorAll("#tbody-atp tr");
            rows.forEach(r => {
                let cellTgl = r.querySelector('.cell-tanggal');
                if(cellTgl) arrTanggal.push(cellTgl.innerText);
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
                    cellTgl.innerText = arrTanggal[idx];
                }
                if (cellNo) {
                    cellNo.innerText = tingkatKelas + "." + (idx + 1);
                }
            });
        }
    </script>
</body>
</html>