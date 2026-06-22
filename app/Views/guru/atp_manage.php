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
                <a href="<?= base_url('guru/atp?rombel_id='.$selectedRombelId.'&mapel_id='.$selectedMapelId.'&print=true') ?>" target="_blank" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">
                    🖨️ Cetak ATP
                </a>
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
                
                <div class="d-flex gap-2">
                    <!-- Tombol Copy -->
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalCopyAtp">
    📋 Copy dari Rombel Lain
</button>
                    <!-- Tombol AI Asli -->
                    <button type="button" id="btn-eksekusi-ai-atp" class="btn btn-sm btn-outline-success font-weight-bold">
                         ✨ AI Generate Kognitif & Sikap
                    </button>
                </div>
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
                                <tr data-cpid="<?= esc($row['id'] ?? $row['cp_detail_id'] ?? $row['id_cp'] ?? '') ?>">
                                    <td class="text-center align-middle bg-light">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'up')" title="Geser ke Atas">▲</button>
                                            <button type="button" class="btn btn-sm btn-move" onclick="moveRow(this, 'down')" title="Geser ke Bawah">▼</button>
                                        </div>
                                    </td>
                                    
                                  <?php 
                                        $tglText = $row['tanggal'] ?? ''; // Ini yang dari Database (Bisa gabungan atau kosong)
                                        $tglDefault = $row['tanggal_default'] ?? 'Jadwal Habis / Belum Diatur'; // Ini Murni HEB
                                        
                                        $isHabis = (strpos($tglDefault, 'Habis') !== false || strpos($tglDefault, 'Belum') !== false);
                                        $colorClass = $isHabis ? 'text-danger' : 'text-success';
                                        
                                        $tpText = trim($row['tujuan_pembelajaran'] ?? $row['tp'] ?? '');
                                        $isTpKosong = empty($tpText) || $tpText === '-';
                                    ?>
                                    <td class="text-center font-weight-bold align-middle cell-tanggal <?= $colorClass ?>" 
                                        data-is-empty="<?= $isTpKosong ? 'true' : 'false' ?>" 
                                        data-is-habis="<?= $isHabis ? 'true' : 'false' ?>"
                                        data-original-date="<?= esc($tglDefault) ?>"
                                        data-alokasi-tanggal="<?= esc($tglText) ?>">
                                        
                                        <span class="date-text d-block">
                                            </span>
                                        
                                        <?php if (!$isHabis && !$isTpKosong): ?>
                                            <div class="d-print-none mt-2 action-date-btns">
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 shadow-sm mb-1" style="font-size: 10px; border-radius: 12px; display: block; width: 100%;" onclick="addDateAllocation(this)" title="Tambah alokasi tanggal dari baris kosong di bawah">
                                                    ➕ Tambah Tgl
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 shadow-sm" style="font-size: 10px; border-radius: 12px; display: none; width: 100%;" onclick="removeDateAllocation(this)" title="Kurangi alokasi tanggal">
                                                    ➖ Kurangi Tgl
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center font-weight-bold align-middle cell-no"><?= esc($tingkatKelas) . '.' . ($idx + 1) ?></td>
                                    
                                    <td dir="auto" class="text-justify"><?= esc($row['tujuan_pembelajaran'] ?? $row['tp'] ?? '-') ?></td>
                                    <td class="font-weight-bold text-secondary"><?= esc($row['lingkup_materi'] ?? $row['lingkup'] ?? '-') ?></td>
                                    
                                    <td class="teks-kognitif">
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
                                            <?php 
                                                // Cek apakah kode ini ada di array dpl_terpilih
                                                $isChecked = (!empty($row['dpl_terpilih']) && in_array($kode, $row['dpl_terpilih'])) ? 'checked' : ''; 
                                            ?>
                                            <div class="custom-check">
                                                <input type="checkbox" id="dpl_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>" <?= $isChecked ?>>
                                                <label for="dpl_<?= $idx ?>_<?= $kode ?>"><b><?= $kode ?></b>: <?= $teks ?></label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                       <div class="checklist-box">
    <?php foreach($listPancaCinta as $kode => $teks): ?>
    <?php 
        // Cek apakah kode (misal 'P1') ada di array panca_cinta_terpilih
        $isChecked = (!empty($row['panca_cinta_terpilih']) && in_array($kode, $row['panca_cinta_terpilih'])) ? 'checked' : ''; 
        
        // Manipulasi string: Ubah huruf 'P' menjadi 'Topik ' (pakai spasi)
        // Hasilnya: 'P1' -> 'Topik 1'
        // Jika tidak ingin ada spasi (Topik1), hapus spasinya menjadi: str_replace('P', 'Topik', $kode);
        $tampilKode = str_replace('P', 'Topik ', $kode);
    ?>
    <div class="custom-check">
        <input type="checkbox" id="pc_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>" <?= $isChecked ?>>
        
        <label for="pc_<?= $idx ?>_<?= $kode ?>"><b><?= $tampilKode ?></b>: <?= $teks ?></label>
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
                <button type="button" class="btn btn-danger font-weight-bold shadow-sm px-4 me-2 btn-reset-atp">🔄 Reset ke Awal</button>
                <button type="button" class="btn btn-success font-weight-bold shadow-sm px-4">💾 Simpan Susunan ATP</button>
            </div>
            <?php endif; ?>            
        </div>
    </div>

    <!-- Modal Copy ATP -->
    <div class="modal fade" id="modalCopyAtp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold">📋 Copy Susunan ATP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Fitur ini akan menyalin Urutan, Aktivitas Kognitif, DPL, dan Panca Cinta dari Rombel paralel ke <b>Rombel <?= esc($namaRombelAktif) ?></b> yang sedang aktif saat ini.</p>
                    
                    <div class="form-group">
                        <label class="font-weight-bold text-primary">Pilih Rombel Sumber:</label>
                        <select id="select-copy-from" class="form-select border-primary">
                            <option value="">-- Pilih Rombel Sumber --</option>
                            <?php if(!empty($rombelTingkatSama)): ?>
                                <?php foreach($rombelTingkatSama as $rt): ?>
                                    <option value="<?= $rt['id'] ?>"><?= esc($rt['class_name'] . ' ' . $rt['rombel_name']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada rombel paralel di tingkat ini.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary font-weight-bold" id="btn-proses-copy">Proses Copy</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
        // =========================================================
        // 1. VARIABEL GLOBAL & INISIALISASI
        // =========================================================
        var tingkatKelas = '<?= esc($tingkatKelas) ?>';
        var globalDates = [];
        var rowsData = [];

        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll('.table-atp tbody tr');
            
            rows.forEach((row) => {
                let cell = row.querySelector('.cell-tanggal');
                if(!cell) return;
                
                let isHabis = cell.getAttribute('data-is-habis') === 'true';
                let isEmptyTP = cell.getAttribute('data-is-empty') === 'true';
                let origDate = cell.getAttribute('data-original-date') || '';
                let savedAlokasi = cell.getAttribute('data-alokasi-tanggal'); 
                
                globalDates.push(origDate); 
                
                let alloc = 1; 
                
                if (!isEmptyTP) {
                    // Untuk baris yang ada TP-nya, pastikan baca jatah memori dari Database
                    if (savedAlokasi === '') {
                        alloc = 0; 
                    } else if (savedAlokasi && savedAlokasi.includes('&')) {
                        alloc = savedAlokasi.split('&').length;
                    }
                } else {
                    // Untuk baris kosong (paling bawah), default sementara 1
                    alloc = 1;
                }

                if(isHabis) {
                    alloc = 1; 
                }

                rowsData.push({
                    rowElement: row,
                    isEmpty: isEmptyTP,
                    isHabis: isHabis,
                    allocation: alloc 
                });
            });

            // 🌟 KUNCI PERBAIKAN: Sinkronisasi Jatah Tanggal
            // Jika TP di atas memiliki tanggal lebih dari 1, artinya ia mencuri tanggal dari baris kosong di bawah.
            // Kita harus mencari baris kosong tersebut dan mengubah alokasinya menjadi 0 agar otomatis tersembunyi.
            let totalRows = rowsData.length;
            let totalAlloc = rowsData.reduce((sum, r) => sum + r.allocation, 0);
            let diff = totalAlloc - totalRows;

            if (diff > 0) {
                // Cari baris kosong dari urutan paling bawah, lalu cabut jatahnya (jadikan 0)
                for (let i = rowsData.length - 1; i >= 0 && diff > 0; i--) {
                    if (rowsData[i].isEmpty && rowsData[i].allocation > 0 && !rowsData[i].isHabis) {
                        rowsData[i].allocation = 0;
                        diff--;
                    }
                }
            }

            // Setelah jatah disesuaikan, jalankan fungsi render untuk mengatur UI
            renderDynamicDates();
        });

        // =========================================================
        // 2. FUNGSI MENGGESER POSISI (UP/DOWN)
        // =========================================================
        function moveRow(btn, direction) {
            const row = btn.closest('tr');
            const tbody = row.parentNode;
            
            row.style.backgroundColor = "#fff3cd"; 
            setTimeout(() => { row.style.backgroundColor = ""; }, 500);

            // Pindah Elemen HTML
            if (direction === 'up' && row.previousElementSibling) {
                tbody.insertBefore(row, row.previousElementSibling);
            } else if (direction === 'down' && row.nextElementSibling) {
                tbody.insertBefore(row.nextElementSibling, row);
            }

            // Sinkronkan ulang urutan Nomor ATP dan array 'rowsData'
            let newRowsData = [];
            let allRows = tbody.querySelectorAll("tr");
            
            allRows.forEach((r, idx) => {
                let cellNo = r.querySelector('.cell-no');
                if (cellNo) {
                    cellNo.innerText = tingkatKelas + "." + (idx + 1);
                }

                let oldData = rowsData.find(data => data.rowElement === r);
                if(oldData) newRowsData.push(oldData);
            });

            rowsData = newRowsData;
            renderDynamicDates();
        }

        // =========================================================
        // 3. FUNGSI ALOKASI TANGGAL DINAMIS
        // =========================================================
        function addDateAllocation(btn) {
            let tr = btn.closest('tr');
            let rowIndex = rowsData.findIndex(r => r.rowElement === tr);
            if(rowIndex === -1) return;

            let donorIndex = -1;
            for(let i = rowsData.length - 1; i > rowIndex; i--) {
                if(rowsData[i].isEmpty && rowsData[i].allocation > 0 && !rowsData[i].isHabis) {
                    donorIndex = i; break;
                }
            }

            if(donorIndex === -1) {
                alert("⚠️ Tidak ada sisa tanggal di baris TP kosong bawah yang bisa digunakan.");
                return;
            }

            rowsData[donorIndex].allocation--;
            rowsData[rowIndex].allocation++;
            renderDynamicDates();
        }

        function removeDateAllocation(btn) {
            let tr = btn.closest('tr');
            let rowIndex = rowsData.findIndex(r => r.rowElement === tr);
            if(rowIndex === -1 || rowsData[rowIndex].allocation <= 1) return;

            let receiverIndex = -1;
            for(let i = rowIndex + 1; i < rowsData.length; i++) {
                if(rowsData[i].isEmpty && rowsData[i].allocation === 0) {
                    receiverIndex = i; break;
                }
            }

            if(receiverIndex !== -1) {
                rowsData[receiverIndex].allocation++; 
                rowsData[rowIndex].allocation--; 
                renderDynamicDates();
            } else {
                alert("⚠️ Sistem tidak dapat menemukan baris kosong untuk mengembalikan tanggal.");
            }
        }

        function renderDynamicDates() {
            let dateCursor = 0;
            
            rowsData.forEach((data) => {
                let cell = data.rowElement.querySelector('.cell-tanggal');
                let dateSpan = cell.querySelector('.date-text');
                let btnRemove = cell.querySelector('button[onclick="removeDateAllocation(this)"]');
                
                if(data.allocation === 0) {
                    data.rowElement.style.display = 'none';
                    data.rowElement.setAttribute('data-alokasi-tanggal', ''); 
                } else {
                    data.rowElement.style.display = ''; 
                    
                    let assignedDates = [];
                    for(let i = 0; i < data.allocation; i++) {
                        if(dateCursor < globalDates.length) {
                            assignedDates.push(globalDates[dateCursor]);
                            dateCursor++;
                        }
                    }
                    
                    let finalDateString = assignedDates.join(' & ');
                    data.rowElement.setAttribute('data-alokasi-tanggal', finalDateString);
                    
                    if(dateSpan) {
                        dateSpan.innerHTML = assignedDates.join('<br><span class="text-secondary fw-bold" style="font-size:10px;">&</span><br>');
                    }
                    
                    if(btnRemove) {
                        btnRemove.style.display = (data.allocation > 1) ? 'block' : 'none';
                    }
                }
            });
        }

        // ====================================================================
        // 4. TRIGGER AI: PENGURUTAN PEDAGOGIS & PEMETAAN KBC (JSON MODE)
        // ====================================================================
        document.getElementById('btn-eksekusi-ai-atp').addEventListener('click', async function() {
            const btnAi = this;
            const tbody = document.getElementById('tbody-atp');
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            
            if (allRows.length === 0 || allRows[0].innerText.includes('Belum ada data')) {
                alert("Tabel ATP kosong. Tidak ada yang bisa dianalisis.");
                return;
            }

            let totalJpInput = 0;
            let dataAtpMentah = [];
            
            allRows.forEach((tr, index) => {
                let tpTeks = tr.cells[3].innerText.trim();
                let lingkupTeks = tr.cells[4].innerText.trim();
                let jpTeks = tr.cells[8].innerText.trim();
                let jp = parseInt(jpTeks) || 0;
                
                if (tpTeks !== '') {
                    totalJpInput += jp;
                    dataAtpMentah.push({
                        id_asli: index,
                        tp: tpTeks,
                        lingkup: lingkupTeks,
                        jp: jp
                    });
                }
            });

            const targetJpSemester = parseInt("<?= $totalJpTersedia ?? 0 ?>") || totalJpInput; 
            
            if (totalJpInput !== targetJpSemester && targetJpSemester > 0) {
                alert(`❌ PROSES DIBATALKAN: Total JP materi (${totalJpInput} JP) belum sesuai dengan alokasi JP Kalender (${targetJpSemester} JP).\n\nSilakan sesuaikan jumlah JP pada tabel terlebih dahulu agar sama dengan target.`);
                return; 
            }

            // 🌟 PERBAIKAN PROMPT: Memasukkan Definisi DPL dan Panca Cinta
            let promptUser = `Anda adalah Pakar Kurikulum Merdeka (Deep Learning) dan KBC Kemenag.
Tugas Anda menganalisis daftar Tujuan Pembelajaran (TP) berikut:

`;
            dataAtpMentah.forEach(r => {
                promptUser += `[ID: ${r.id_asli}] TP: ${r.tp} (Materi: ${r.lingkup})\n`;
            });

            promptUser += `
INSTRUKSI WAJIB:
1. Urutkan ID secara pedagogis (dari materi dasar hingga materi lanjutan/kompleks).
2. Tentukan Aktivitas Kognitif (contoh: Mengidentifikasi, Menganalisis, Mencipta).
3. Petakan maksimal 3 DPL (Dimensi Profil Lulusan) yang relevan dengan TP berdasarkan daftar berikut:
   - DPL1: Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa
   - DPL2: Kewargaan
   - DPL3: Penalaran Kritis
   - DPL4: Kreativitas
   - DPL5: Kolaborasi
   - DPL6: Kemandirian
   - DPL7: Kesehatan
   - DPL8: Komunikasi
4. Petakan maksimal 2 Pilar Panca Cinta yang relevan dengan TP berdasarkan daftar berikut:
   - P1: Cinta Allah dan Rasul-Nya
   - P2: Cinta Ilmu
   - P3: Cinta Lingkungan
   - P4: Cinta Diri dan Sesama Manusia
   - P5: Cinta Tanah Air

ATURAN FORMAT BALASAN (SANGAT KETAT):
Jawab HANYA menggunakan format JSON Array murni. JANGAN gunakan tag markdown (\`\`\`json). JANGAN ada teks pengantar.
Format JSON persis seperti ini:
[
  {"id_asli": 0, "aktivitas_kognitif": "Mengamati, Menjelaskan", "dpl": ["DPL1", "DPL3"], "pilar": ["P1", "P2"]},
  {"id_asli": 1, "aktivitas_kognitif": "Menganalisis, Mengevaluasi", "dpl": ["DPL3", "DPL5"], "pilar": ["P4"]}
]`;

            btnAi.disabled = true;
            let originalText = btnAi.innerHTML;
            btnAi.innerHTML = '⏳ AI Sedang Menganalisis & Menyusun...';
            tbody.style.opacity = '0.5';

            const formData = new FormData(); 
            formData.append('message', promptUser);
            
            try {
                const response = await fetch("<?= base_url('ai/analyze_cp') ?>", { 
                    method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                });
                const resData = await response.json();
                
                if (resData.status === 'success') {
                    let cleanJsonStr = resData.reply.replace(/```json/g, '').replace(/```/g, '').trim();
                    let aiResult = JSON.parse(cleanJsonStr);
                    
                    tbody.innerHTML = '';
                    
                    aiResult.forEach(item => {
                        let targetRow = allRows[item.id_asli];
                        if (!targetRow) return;

                        targetRow.cells[5].innerHTML = `<span class="text-success font-weight-bold">${item.aktivitas_kognitif}</span>`;
                        
                        if(Array.isArray(item.dpl)) {
                            item.dpl.forEach(kodeDpl => {
                                let chk = targetRow.querySelector(`input[value="${kodeDpl}"]`);
                                if(chk) chk.checked = true;
                            });
                        }

                        if(Array.isArray(item.pilar)) {
                            item.pilar.forEach(kodePilar => {
                                let chk = targetRow.querySelector(`input[value="${kodePilar}"]`);
                                if(chk) chk.checked = true;
                            });
                        }

                        tbody.appendChild(targetRow);
                        allRows[item.id_asli] = null; 
                    });

                    allRows.forEach(sisaRow => {
                        if (sisaRow) tbody.appendChild(sisaRow);
                    });

                    let newlyOrderedRows = tbody.querySelectorAll("tr");
                    let newRowsData = [];

                    newlyOrderedRows.forEach((r, idx) => {
                        let cellNo = r.querySelector('.cell-no');
                        if (cellNo) {
                            cellNo.innerText = tingkatKelas + "." + (idx + 1);
                        }
                        
                        let oldData = rowsData.find(data => data.rowElement === r);
                        if(oldData) newRowsData.push(oldData);
                        
                        r.style.backgroundColor = "#e8f5e9"; 
                        setTimeout(() => { r.style.backgroundColor = ""; }, 1500);
                    });

                    rowsData = newRowsData;
                    renderDynamicDates(); 

                    alert("✅ AI Berhasil mengurutkan materi dan memetakan Profil Lulusan & Panca Cinta!");

                } else {
                    alert("⚠️ AI Gagal: " + (resData.reply || resData.message));
                }
            } catch (error) {
                console.error(error);
                alert("⚠️ Kesalahan parsing atau koneksi AI. Pastikan AI menjawab dengan format JSON yang benar.");
            } finally {
                btnAi.disabled = false;
                btnAi.innerHTML = originalText;
                tbody.style.opacity = '1';
            }
        });

        // ====================================================================
        // 5. TRIGGER RESET ATP KE AWAL (HAPUS DARI DATABASE)
        // ====================================================================
        document.querySelector('.btn-reset-atp').addEventListener('click', async function() {
            if (!confirm("⚠️ PERINGATAN: Apakah Anda yakin ingin mereset susunan ini?\nSemua data ATP, Centang DPL, dan Panca Cinta untuk kelas ini akan dihapus permanen dan kembali ke urutan awal Analisis CP!")) {
                return;
            }

            const btnReset = this;
            const tbody = document.getElementById('tbody-atp');
            const rows = tbody.querySelectorAll('tr');
            
            if (rows.length === 0 || rows[0].innerText.includes('Belum ada data')) {
                alert("Tidak ada data untuk di-reset."); return;
            }

            let rombelId = document.querySelector('select[name="rombel_id"]').value;
            let cpIds = [];
            rows.forEach((tr) => {
                let cpId = tr.getAttribute('data-cpid');
                if (cpId) cpIds.push(cpId);
            });

            btnReset.disabled = true;
            let originalText = btnReset.innerHTML;
            btnReset.innerHTML = '⏳ Mereset...';

            const formData = new FormData();
            formData.append('rombel_id', rombelId);
            formData.append('cp_ids', JSON.stringify(cpIds));

            try {
                const response = await fetch("<?= base_url('guru/atp/reset') ?>", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    alert("✅ " + result.message);
                    window.location.reload(); 
                } else {
                    alert("⚠️ Gagal: " + result.message);
                }
            } catch (error) {
                console.error(error);
                alert("⚠️ Terjadi kesalahan koneksi saat mereset data.");
            } finally {
                btnReset.disabled = false;
                btnReset.innerHTML = originalText;
            }
        });

        // ====================================================================
        // 6. TRIGGER COPY ATP DARI ROMBEL LAIN
        // ====================================================================
        document.getElementById('btn-proses-copy').addEventListener('click', async function() {
            let fromRombelId = document.getElementById('select-copy-from').value;
            let toRombelId = '<?= $selectedRombelId ?>';
            
            if(!fromRombelId) {
                alert("Silakan pilih rombel sumber terlebih dahulu!");
                return;
            }

            let cpIds = [];
            document.querySelectorAll("#tbody-atp tr").forEach(tr => {
                let cpId = tr.getAttribute('data-cpid');
                if (cpId) cpIds.push(cpId);
            });

            if (cpIds.length === 0) {
                alert("Tidak ada target materi untuk disalin. Tabel masih kosong!");
                return;
            }

            if (!confirm("Apakah Anda yakin ingin menimpa susunan ATP rombel ini dengan data dari rombel yang dipilih?")) {
                return;
            }

            let btn = this;
            let originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Sedang Menyalin...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('from_rombel_id', fromRombelId);
            formData.append('to_rombel_id', toRombelId);
            formData.append('cp_ids', JSON.stringify(cpIds));

            try {
                const response = await fetch("<?= base_url('guru/atp/copy') ?>", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    alert("✅ " + result.message);
                    window.location.reload();
                } else {
                    alert("⚠️ Gagal: " + result.message);
                }
            } catch (error) {
                console.error(error);
                alert("⚠️ Terjadi kesalahan koneksi saat menyalin data.");
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // ====================================================================
        // 7. TRIGGER SIMPAN ATP KE DATABASE (TERMASUK ALOKASI TANGGAL)
        // ====================================================================
        document.querySelector('.btn-success').addEventListener('click', async function() {
            const btnSave = this;
            const tbody = document.getElementById('tbody-atp');
            const rows = tbody.querySelectorAll('tr');
            
            if (rows.length === 0 || rows[0].innerText.includes('Belum ada data')) {
                alert("Tidak ada data untuk disimpan."); return;
            }

            let dataAtp = [];
            let rombelId = document.querySelector('select[name="rombel_id"]').value;

            rows.forEach((tr, index) => {
                let cpId = tr.getAttribute('data-cpid');
                if (!cpId) return;

                let selKognitif = tr.querySelector('.teks-kognitif');
                let teksKognitif = selKognitif ? selKognitif.innerText.replace('Materi Tersedia:\n', '').trim() : '';
                if(teksKognitif === 'Menunggu AI...') teksKognitif = '';

                let dplTerpilih = [];
                tr.querySelectorAll('input[id^="dpl_"]:checked').forEach(chk => dplTerpilih.push(chk.value));

                let pilarTerpilih = [];
                tr.querySelectorAll('input[id^="pc_"]:checked').forEach(chk => pilarTerpilih.push(chk.value));

                let alokasiTanggal = tr.getAttribute('data-alokasi-tanggal') || '';
                if (tr.style.display === 'none') {
                    alokasiTanggal = '';
                }

                dataAtp.push({
                    cp_detail_id: cpId,
                    urutan: index + 1,
                    aktivitas_kognitif: teksKognitif,
                    dpl: dplTerpilih.join(','), 
                    pilar: pilarTerpilih.join(','),
                    alokasi_tanggal: alokasiTanggal
                });
            });

            btnSave.disabled = true;
            let originalText = btnSave.innerHTML;
            btnSave.innerHTML = '⏳ Sedang Menyimpan...';

            const formData = new FormData();
            formData.append('rombel_id', rombelId);
            formData.append('data_atp', JSON.stringify(dataAtp));

            try {
                const response = await fetch("<?= base_url('guru/atp/simpan') ?>", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                if (result.status === 'success') {
                    alert("✅ " + result.message);
                } else {
                    alert("⚠️ Gagal: " + result.message);
                }
            } catch (error) {
                console.error(error);
                alert("⚠️ Terjadi kesalahan koneksi saat menyimpan data.");
            } finally {
                btnSave.disabled = false;
                btnSave.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>
