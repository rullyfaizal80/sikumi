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
                <button type="button" id="btn-eksekusi-ai-atp" class="btn btn-sm btn-outline-success font-weight-bold">
                     ✨ AI Generate Kognitif & Sikap
                </button>
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
                                                // Cek apakah kode ini ada di array panca_cinta_terpilih
                                                $isChecked = (!empty($row['panca_cinta_terpilih']) && in_array($kode, $row['panca_cinta_terpilih'])) ? 'checked' : ''; 
                                            ?>
                                            <div class="custom-check">
                                                <input type="checkbox" id="pc_<?= $idx ?>_<?= $kode ?>" value="<?= $kode ?>" <?= $isChecked ?>>
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
                <button type="button" class="btn btn-danger font-weight-bold shadow-sm px-4 me-2 btn-reset-atp">🔄 Reset ke Awal</button>
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
    <script>
        // ====================================================================
        // TRIGGER AI: PENGURUTAN PEDAGOGIS & PEMETAAN KBC (JSON MODE)
        // ====================================================================
        document.getElementById('btn-eksekusi-ai-atp').addEventListener('click', async function() {
            const btnAi = this;
            const tbody = document.getElementById('tbody-atp');
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            
            // 1. VALIDASI DATA KOSONG
            if (allRows.length === 0 || allRows[0].innerText.includes('Belum ada data')) {
                alert("Tabel ATP kosong. Tidak ada yang bisa dianalisis.");
                return;
            }

            /* // 2. VALIDASI BEBAN JP
            let totalJpInput = 0;
            let dataAtpMentah = [];
            
            allRows.forEach((tr, index) => {
                // Pastikan baris memiliki data (bukan baris sisa jadwal kosong)
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

            // Simulasi Target JP Semester (Jika belum ada dari Controller)
            // Bapak bisa mengganti angka 0 di bawah ini dengan variabel PHP jika sudah ditarik dari Kaldik
            const targetJpSemester = parseInt("<?= $totalJpTersedia ?? 0 ?>") || totalJpInput; 
            
            if (totalJpInput !== targetJpSemester && targetJpSemester > 0) {
                let konfirmasi = confirm(`⚠️ VALIDASI JP: Total JP di tabel (${totalJpInput} JP) tidak sama dengan Target JP Kaldik (${targetJpSemester} JP).\n\nApakah Anda tetap ingin melanjutkan proses AI?`);
                if (!konfirmasi) return;
            } */

            // 2. VALIDASI BEBAN JP
            let totalJpInput = 0;
            let dataAtpMentah = [];
            
            allRows.forEach((tr, index) => {
                // Pastikan baris memiliki data (bukan baris sisa jadwal kosong)
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

            // Simulasi Target JP Semester (Jika belum ada dari Controller)
            const targetJpSemester = parseInt("<?= $totalJpTersedia ?? 0 ?>") || totalJpInput; 
            
            // 🌟 PERBAIKAN: Gunakan alert dan return mutlak agar tidak bisa dilanjutkan
            if (totalJpInput !== targetJpSemester && targetJpSemester > 0) {
                alert(`❌ PROSES DIBATALKAN: Total JP materi (${totalJpInput} JP) belum sesuai dengan alokasi JP Kalender (${targetJpSemester} JP).\n\nSilakan sesuaikan jumlah JP pada tabel terlebih dahulu agar sama dengan target.`);
                return; // Proses akan langsung berhenti di sini, tombol akan kembali normal
            }

            // 3. SUSUN PROMPT UNTUK AI
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
3. Petakan maksimal 3 DPL (Dimensi Profil Lulusan) yang relevan. Pilihan: DPL1, DPL2, DPL3, DPL4, DPL5, DPL6, DPL7, DPL8.
4. Petakan maksimal 2 Pilar Panca Cinta yang relevan. Pilihan: P1, P2, P3, P4, P5.

ATURAN FORMAT BALASAN (SANGAT KETAT):
Jawab HANYA menggunakan format JSON Array murni. JANGAN gunakan tag markdown (\`\`\`json). JANGAN ada teks pengantar.
Format JSON persis seperti ini:
[
  {"id_asli": 0, "aktivitas_kognitif": "Mengamati, Menjelaskan", "dpl": ["DPL1", "DPL3"], "pilar": ["P1", "P2"]},
  {"id_asli": 1, "aktivitas_kognitif": "Menganalisis, Mengevaluasi", "dpl": ["DPL3", "DPL5"], "pilar": ["P4"]}
]`;

            // 4. PROSES LOADING
            btnAi.disabled = true;
            let originalText = btnAi.innerHTML;
            btnAi.innerHTML = '⏳ AI Sedang Menganalisis & Menyusun...';
            tbody.style.opacity = '0.5';

            const formData = new FormData(); 
            formData.append('message', promptUser);
            
            try {
                // Panggil Controller AI yang sudah Bapak buat sebelumnya
                const response = await fetch("<?= base_url('ai/analyze_cp') ?>", { 
                    method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } 
                });
                const resData = await response.json();
                
                if (resData.status === 'success') {
                    // Bersihkan response dari markdown jika AI membandel
                    let cleanJsonStr = resData.reply.replace(/```json/g, '').replace(/```/g, '').trim();
                    let aiResult = JSON.parse(cleanJsonStr);
                    
                    // Kosongkan Tabel Sementara
                    tbody.innerHTML = '';
                    
                    // 5. TERAPKAN HASIL AI KE DALAM TABEL (REORDERING & CHECKING)
                    aiResult.forEach(item => {
                        let targetRow = allRows[item.id_asli];
                        if (!targetRow) return;

                        // A. Isi Aktivitas Kognitif
                        targetRow.cells[5].innerHTML = `<span class="text-success font-weight-bold">${item.aktivitas_kognitif}</span>`;
                        
                        // B. Centang DPL Otomatis
                        if(Array.isArray(item.dpl)) {
                            item.dpl.forEach(kodeDpl => {
                                let chk = targetRow.querySelector(`input[value="${kodeDpl}"]`);
                                if(chk) chk.checked = true;
                            });
                        }

                        // C. Centang Panca Cinta Otomatis
                        if(Array.isArray(item.pilar)) {
                            item.pilar.forEach(kodePilar => {
                                let chk = targetRow.querySelector(`input[value="${kodePilar}"]`);
                                if(chk) chk.checked = true;
                            });
                        }

                        // D. Masukkan kembali ke tabel dengan urutan baru
                        tbody.appendChild(targetRow);
                        
                        // Hapus row yang sudah diproses dari array asli
                        allRows[item.id_asli] = null; 
                    });

                    // Masukkan sisa baris yang mungkin tidak ikut terproses AI (misal baris kosong sisa jadwal)
                    allRows.forEach(sisaRow => {
                        if (sisaRow) tbody.appendChild(sisaRow);
                    });

                    // 6. RAPIKAN KEMBALI TANGGAL & NOMOR URUT (Seperti fitur moveRow)
                    let newlyOrderedRows = tbody.querySelectorAll("tr");
                    newlyOrderedRows.forEach((r, idx) => {
                        let cellTgl = r.querySelector('.cell-tanggal');
                        let cellNo = r.querySelector('.cell-no');
                        
                        if (cellTgl && arrTanggal[idx]) {
                            cellTgl.innerText = arrTanggal[idx];
                        }
                        if (cellNo) {
                            cellNo.innerText = tingkatKelas + "." + (idx + 1);
                        }
                        
                        // Beri efek highlight sukses
                        r.style.backgroundColor = "#e8f5e9"; 
                        setTimeout(() => { r.style.backgroundColor = ""; }, 1500);
                    });

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
        // TRIGGER SIMPAN ATP KE DATABASE
        // ====================================================================
        document.querySelector('.btn-success').addEventListener('click', async function() {
            const btnSave = this;
            const tbody = document.getElementById('tbody-atp');
            const rows = tbody.querySelectorAll('tr');
            
            if (rows.length === 0 || rows[0].innerText.includes('Belum ada data')) {
                alert("Tidak ada data untuk disimpan."); return;
            }

            // 1. Kumpulkan semua data dari tabel HTML
            let dataAtp = [];
            let rombelId = document.querySelector('select[name="rombel_id"]').value;

            rows.forEach((tr, index) => {
                let cpId = tr.getAttribute('data-cpid');
                if (!cpId) return;

                // Ambil teks aktivitas kognitif (Abaikan span HTML-nya)
                let selKognitif = tr.querySelector('.teks-kognitif');
                let teksKognitif = selKognitif ? selKognitif.innerText.replace('Materi Tersedia:\n', '').trim() : '';
                if(teksKognitif === 'Menunggu AI...') teksKognitif = '';

                // Kumpulkan DPL yang dicentang
                let dplTerpilih = [];
                tr.querySelectorAll('input[id^="dpl_"]:checked').forEach(chk => dplTerpilih.push(chk.value));

                // Kumpulkan Panca Cinta yang dicentang
                let pilarTerpilih = [];
                tr.querySelectorAll('input[id^="pc_"]:checked').forEach(chk => pilarTerpilih.push(chk.value));

                dataAtp.push({
                    cp_detail_id: cpId,
                    urutan: index + 1, // Urutan berdasarkan posisi baris saat ini
                    aktivitas_kognitif: teksKognitif,
                    dpl: dplTerpilih.join(','), // Gabungkan dengan koma (Contoh: DPL1,DPL3)
                    pilar: pilarTerpilih.join(',')
                });
            });

            // 2. Kirim ke Controller
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

        // ====================================================================
        // TRIGGER RESET ATP KE AWAL (HAPUS DARI DATABASE)
        // ====================================================================
        document.querySelector('.btn-reset-atp').addEventListener('click', async function() {
            // Berikan peringatan agar tidak terklik secara tidak sengaja
            if (!confirm("⚠️ PERINGATAN: Apakah Anda yakin ingin mereset susunan ini?\nSemua data ATP, Centang DPL, dan Panca Cinta untuk kelas ini akan dihapus permanen dan kembali ke urutan awal Analisis CP!")) {
                return;
            }

            const btnReset = this;
            const tbody = document.getElementById('tbody-atp');
            const rows = tbody.querySelectorAll('tr');
            
            if (rows.length === 0 || rows[0].innerText.includes('Belum ada data')) {
                alert("Tidak ada data untuk di-reset."); return;
            }

            // Ambil ID Rombel dan kumpulan ID CP yang sedang tampil
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
                    // Reload halaman otomatis agar tabel kembali ke urutan Analisis CP
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
    </script>
</body>
</html>