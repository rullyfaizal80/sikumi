<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKTP - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; font-family: 'Source Sans Pro', sans-serif; }
        .table-kktp th { background-color: #002060; color: #ffffff; text-align: center; vertical-align: middle; font-size: 11px; padding: 8px; }
        .table-kktp td { vertical-align: top; font-size: 11px; padding: 6px; }
        .editable-cell { border: 1px dashed #ccc; padding: 8px; min-height: 60px; background: #fff; border-radius: 4px; }
        .editable-cell:focus { outline: 2px solid #FF9F00; background: #fffdf5; border: 1px solid #FF9F00; }
        /* Trik Vanilla JS Modal Backdrop */
        .modal-vanilla-backdrop { background-color: rgba(0,0,0,0.5); }
    </style>
</head>
<body>
    <div class="wrapper p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">
                    <i class="bi bi-ui-checks-grid me-2"></i> Kriteria Ketercapaian TP (KKTP)
                </h3>
                <p class="text-muted mb-0">Atur deskripsi rubrik penilaian untuk tiap Tujuan Pembelajaran</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold shadow-sm px-3 me-2">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold shadow-sm px-3 me-2" data-bs-toggle="modal" data-bs-target="#modalCopyKktp">
                    📋 Copy dari Rombel Lain
                </button>
                <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm px-3 btn-ai-kktp">
                    <i class="bi bi-magic"></i> AI Generate Rubrik
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small font-weight-bold mb-1">Mata Pelajaran</label>
                        <select name="mapel_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach($daftarMapel as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $selectedMapelId == $m['id'] ? 'selected' : '' ?>>
                                    <?= esc($m['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small font-weight-bold mb-1">Rombongan Belajar (Kelas)</label>
                        <select name="rombel_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach($daftarRombel as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $selectedRombelId == $r['id'] ? 'selected' : '' ?>>
                                    <?= esc($r['class_name'] . ' - ' . $r['rombel_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow border-top border-primary border-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-kktp m-0">
                        <thead>
                            <tr>
                                <th width="3%">No</th>
                                <th width="7%">No TP</th>
                                <th width="20%">Tujuan Pembelajaran (TP)</th>
                                <th width="20%">Indikator Ketercapaian</th>
                                <th width="12%">Sangat Baik (90-100)</th>
                                <th width="13%">Baik (80-89)</th>
                                <th width="12%">Cukup (70-79)</th>
                                <th width="13%">Perlu Bimbingan (<70)</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-kktp">
                            <?php if(empty($dataKktp)): ?>
                                <tr><td colspan="8" class="text-center py-4 text-danger font-weight-bold">Belum ada data Tujuan Pembelajaran.</td></tr>
                            <?php else: ?>
                                <?php foreach($dataKktp as $idx => $row): ?>
                                <tr data-cpid="<?= esc($row['id'] ?? '') ?>">
                                    <td class="text-center font-weight-bold"><?= $idx + 1 ?></td>
                                    <td class="text-center font-weight-bold text-muted"><?= esc($tingkatKelas) . '.' . esc($row['no_tp'] ?? ($idx + 1)) ?></td>
                                    <td class="tp-text font-weight-bold"><?= esc($row['tujuan_pembelajaran']) ?></td>
                                    <td><div class="editable-cell cell-indikator" contenteditable="true"><?= esc($row['indikator']) ?></div></td>
                                    <td><div class="editable-cell cell-sb" contenteditable="true"><?= esc($row['skor_sangat_baik']) ?></div></td>
                                    <td><div class="editable-cell cell-b" contenteditable="true"><?= esc($row['skor_baik']) ?></div></td>
                                    <td><div class="editable-cell cell-c" contenteditable="true"><?= esc($row['skor_cukup']) ?></div></td>
                                    <td><div class="editable-cell cell-pb" contenteditable="true"><?= esc($row['skor_perlu_bimbingan']) ?></div></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if(!empty($dataKktp)): ?>
            <div class="card-footer bg-white text-end py-3">
                <button type="button" class="btn btn-danger font-weight-bold shadow-sm px-4 me-2 btn-reset-kktp">🔄 Reset ke Kosong</button>
                <button type="button" class="btn btn-success font-weight-bold shadow-sm px-4 btn-save-kktp">💾 Simpan Rubrik KKTP</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal fade" id="modalCopyKktp" tabindex="-1" aria-labelledby="modalCopyKktpLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="modalCopyKktpLabel">📋 Copy Rubrik KKTP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Fitur ini akan menyalin seluruh Indikator dan Deskripsi Rubrik dari Rombel paralel ke <b>Rombel <?= esc($namaRombelAktif) ?></b> yang sedang aktif saat ini.</p>
                    
                    <div class="mb-3">
                        <label class="font-weight-bold text-primary form-label">Pilih Rombel Sumber:</label>
                        <select id="select-copy-from-kktp" class="form-select border-primary">
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
                    <button type="button" class="btn btn-primary font-weight-bold" id="btn-proses-copy-kktp">Proses Copy</button>
                </div>
            </div>
        </div>
    </div>

     <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
        // FUNGSI BANTUAN MODAL VANILLA JS (BEBAS JQUERY)
        function showVanillaModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.classList.add('show', 'modal-vanilla-backdrop');
                modal.style.display = 'block';
            }
        }
        
        function hideVanillaModal(modalId) {
            const modal = document.getElementById(modalId);
            if(modal) {
                modal.classList.remove('show', 'modal-vanilla-backdrop');
                modal.style.display = 'none';
            }
        }

        // ==============================================================
        // 1. AI GENERATOR (VERSI 100% VANILLA JS BEBAS ERROR)
        // ==============================================================
        document.querySelector('.btn-ai-kktp').addEventListener('click', function(e) {
            e.preventDefault();
            
            const allRows = document.querySelectorAll('#tbody-kktp tr[data-cpid]');
            if(allRows.length === 0) return alert("Tidak ada TP untuk diproses.");

            // Buat elemen Modal jika belum ada
            if (!document.getElementById('modalAiPrompt')) {
                const modalHTML = `
                <div class="modal fade" id="modalAiPrompt" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1050;">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white" style="border-bottom: 3px solid #001a4d;">
                                <h5 class="modal-title font-weight-bold" style="font-size: 16px;">✨ Kustomisasi Rubrik AI</h5>
                                <button type="button" class="btn-close-vanilla text-white bg-transparent border-0" style="font-size: 20px; font-weight: bold; cursor: pointer;">&times;</button>
                            </div>
                            <div class="modal-body bg-light">
                                <div class="form-group mb-2">
                                    <label for="custom-prompt" class="font-weight-bold small text-dark">Instruksi Tambahan Guru (Opsional):</label>
                                    <textarea id="custom-prompt" class="form-control shadow-sm" rows="3" placeholder="Contoh: Buatkan masing-masing 3 indikator ketercapaian, fokus pada hafalan..."></textarea>
                                    <small class="text-muted mt-1 d-block">💡 <i>Kosongkan jika ingin menggunakan gaya bahasa standar AI.</i></small>
                                </div>
                                <div class="alert alert-warning py-2 small mb-0 mt-3 border-0 shadow-sm" style="background-color: #fffdf5; border-left: 4px solid #FF9F00 !important;">
                                    <b>Info:</b> AI hanya akan memproses baris yang <b>masih kosong</b> atau berstatus <b>gagal</b>.
                                </div>
                            </div>
                            <div class="modal-footer bg-white border-top-0 py-2">
                                <button type="button" class="btn btn-secondary btn-sm font-weight-bold px-3 shadow-sm btn-close-vanilla">Batal</button>
                                <button type="button" id="btn-proses-ai-start" class="btn btn-success btn-sm font-weight-bold px-4 shadow-sm">🚀 Mulai Susun AI</button>
                            </div>
                        </div>
                    </div>
                </div>`;
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Event Listener untuk Tombol Close Modal (Silang & Batal)
                document.querySelectorAll('.btn-close-vanilla').forEach(btn => {
                    btn.addEventListener('click', () => hideVanillaModal('modalAiPrompt'));
                });

                // Event Listener Eksekusi AI
                document.getElementById('btn-proses-ai-start').addEventListener('click', async function() {
                    const btnUtama = document.querySelector('.btn-ai-kktp');
                    const customPromptTxt = document.getElementById('custom-prompt').value.trim();
                    let instruksiTambahan = customPromptTxt ? `\n\nINSTRUKSI TAMBAHAN WAJIB DARI GURU: "${customPromptTxt}"` : '';

                    let rowsToProcess = [];
                    allRows.forEach(tr => {
                        const cellSb = tr.querySelector('.cell-sb');
                        const txtSb = cellSb ? cellSb.innerText.trim() : '';
                        if (txtSb === "" || txtSb === "-" || txtSb.includes("Gagal") || txtSb.includes("Menganalisis")) {
                            rowsToProcess.push(tr);
                        }
                    });

                    if (rowsToProcess.length === 0) {
                        alert("🎉 Semua baris di tabel ini sudah terisi! Tidak ada yang perlu diproses.");
                        hideVanillaModal('modalAiPrompt');
                        return;
                    }

                    hideVanillaModal('modalAiPrompt');
                    btnUtama.disabled = true;
                    let originalText = btnUtama.innerHTML;
                    btnUtama.innerHTML = `⏳ AI Memproses ${rowsToProcess.length} Baris...`;

                    for (let tr of rowsToProcess) {
                        let tp = tr.querySelector('.tp-text').innerText.trim();
                        
                        const cellIndikator = tr.querySelector('.cell-indikator');
                        const cellSb = tr.querySelector('.cell-sb');
                        const cellB = tr.querySelector('.cell-b');
                        const cellC = tr.querySelector('.cell-c');
                        const cellPb = tr.querySelector('.cell-pb');

                        const oldIndikator = cellIndikator ? cellIndikator.innerText : '';
                        const oldSb = cellSb.innerText;
                        const oldB = cellB.innerText;
                        const oldC = cellC.innerText;
                        const oldPb = cellPb.innerText;

                        let success = false;
                        let retries = 2; 

                        while (!success && retries >= 0) {
                            if (cellIndikator) cellIndikator.innerHTML = `<span class="text-muted">⏳ Menganalisis... ${retries < 2 ? '(Ulang)' : ''}</span>`;
                            cellSb.innerHTML = `<span class="text-muted">⏳...</span>`;
                            cellB.innerHTML = `<span class="text-muted">⏳...</span>`;
                            cellC.innerHTML = `<span class="text-muted">⏳...</span>`;
                            cellPb.innerHTML = `<span class="text-muted">⏳...</span>`;

                            let prompt = `Anda adalah pakar Kurikulum Merdeka. Buatkan deskripsi Rubrik KKTP untuk Tujuan Pembelajaran: "${tp}".${instruksiTambahan}
                            Output WAJIB berupa raw JSON murni 1 baris saja (TANPA markdown, TANPA enter/newline):
                            {"indikator":"", "sb":"", "b":"", "c":"", "pb":""}
                            Aturan teks rubrik:
                            - JANGAN gunakan enter atau baris baru di dalam teks nilai JSON.
                            - JANGAN gunakan tanda petik ganda (") di dalam nilai teks, gunakan petik tunggal (') jika diperlukan.
                            - Kata-kata singkat, padat, dan operasional.`;

                            try {
                                const fd = new FormData();
                                fd.append('message', prompt);

                                const response = await fetch("<?= base_url('ai/analyze_cp') ?>", {
                                    method: 'POST',
                                    body: fd
                                });
                                
                                if (!response.ok) throw new Error("Koneksi drop");
                                
                                const res = await response.json();
                                let rawReply = res.reply || "";
                                let jsonMatch = rawReply.match(/\{[\s\S]*\}/);
                                
                                if (!jsonMatch) throw new Error("Bukan JSON");
                                
                                let jsonString = jsonMatch[0].trim().replace(/[\u0000-\u001F\u007F-\u009F]/g, ""); 
                                let data = JSON.parse(jsonString);
                                
                                let valIndikator = data.indikator || data.evidence || data.bukti || '';
                                let valSb = data.sb || data.sangat_baik || data['sangat baik'] || '';
                                let valB = data.b || data.baik || '';
                                let valC = data.c || data.cukup || '';
                                let valPb = data.pb || data.perlu_bimbingan || data['perlu bimbingan'] || '';

                                if (!valSb && !valB && !valC) throw new Error("Data kosong");

                                if(cellIndikator) cellIndikator.innerText = valIndikator;
                                cellSb.innerText = valSb;
                                cellB.innerText = valB;
                                cellC.innerText = valC;
                                cellPb.innerText = valPb;
                                
                                success = true; 

                            } catch(e) { 
                                retries--;
                                if (retries < 0) {
                                    if(cellIndikator) cellIndikator.innerHTML = oldIndikator || `<span class="text-danger small font-weight-bold">⚠️ Gagal (Klik Lagi)</span>`;
                                    cellSb.innerText = oldSb || '-';
                                    cellB.innerText = oldB || '-';
                                    cellC.innerText = oldC || '-';
                                    cellPb.innerText = oldPb || '-';
                                } else {
                                    await new Promise(resolve => setTimeout(resolve, 600));
                                }
                            }
                        }
                        await new Promise(resolve => setTimeout(resolve, 300));
                    }
                    
                    btnUtama.disabled = false;
                    btnUtama.innerHTML = originalText;
                    alert("✅ AI selesai memeriksa dan mengisi baris!");
                });
            }

            // Tampilkan Modal secara native JS
            showVanillaModal('modalAiPrompt');
        });

        // ==============================================================
        // 2. SIMPAN KKTP
        // ==============================================================
        document.querySelector('.btn-save-kktp').addEventListener('click', async function() {
            const btnSave = this;
            const dataKktp = [];
            let rombelId = document.querySelector('select[name="rombel_id"]').value;

            document.querySelectorAll('#tbody-kktp tr[data-cpid]').forEach(tr => {
                let indikatorText = tr.querySelector('.cell-indikator') ? tr.querySelector('.cell-indikator').innerText.trim() : '';

                dataKktp.push({
                    cp_id: tr.getAttribute('data-cpid'),
                    indikator: indikatorText,
                    sangat_baik: tr.querySelector('.cell-sb').innerText.trim(),
                    baik: tr.querySelector('.cell-b').innerText.trim(),
                    cukup: tr.querySelector('.cell-c').innerText.trim(),
                    perlu_bimbingan: tr.querySelector('.cell-pb').innerText.trim()
                });
            });

            btnSave.disabled = true;
            let originalText = btnSave.innerHTML;
            btnSave.innerHTML = '⏳ Menyimpan...';

            const fd = new FormData();
            fd.append('rombel_id', rombelId);
            fd.append('data_kktp', JSON.stringify(dataKktp));

            try {
                const res = await fetch("<?= base_url('guru/kktp/simpan') ?>", { method: 'POST', body: fd });
                const result = await res.json();
                alert(result.status === 'success' ? "✅ " + result.message : "⚠️ " + result.message);
            } catch(e) {
                alert("⚠️ Gagal terhubung ke server.");
            } finally {
                btnSave.disabled = false;
                btnSave.innerHTML = originalText;
            }
        });

        // ==============================================================
        // 3. RESET KKTP
        // ==============================================================
        document.querySelector('.btn-reset-kktp').addEventListener('click', async function() {
            if (!confirm("⚠️ PERINGATAN: Semua deskripsi rubrik untuk kelas ini akan dihapus permanen! Lanjutkan?")) return;

            const btnReset = this;
            let rombelId = document.querySelector('select[name="rombel_id"]').value;
            let cpIds = [];
            
            document.querySelectorAll('#tbody-kktp tr[data-cpid]').forEach(tr => {
                cpIds.push(tr.getAttribute('data-cpid'));
            });

            btnReset.disabled = true;
            let originalText = btnReset.innerHTML;
            btnReset.innerHTML = '⏳ Mereset...';

            const fd = new FormData();
            fd.append('rombel_id', rombelId);
            fd.append('cp_ids', JSON.stringify(cpIds));

            try {
                const res = await fetch("<?= base_url('guru/kktp/reset') ?>", { method: 'POST', body: fd });
                const result = await res.json();
                if (result.status === 'success') {
                    alert("✅ " + result.message);
                    window.location.reload();
                } else {
                    alert("⚠️ Gagal: " + result.message);
                }
            } catch(e) {
                alert("⚠️ Gagal terhubung ke server.");
            } finally {
                btnReset.disabled = false;
                btnReset.innerHTML = originalText;
            }
        });
        // ==============================================================
        // 4. COPY KKTP DARI ROMBEL LAIN
        // ==============================================================
        const btnProsesCopyKktp = document.getElementById('btn-proses-copy-kktp');
        if (btnProsesCopyKktp) {
            btnProsesCopyKktp.addEventListener('click', async function() {
                let fromRombelId = document.getElementById('select-copy-from-kktp').value;
                let toRombelId = document.querySelector('select[name="rombel_id"]').value;
                
                if(!fromRombelId) {
                    alert("Silakan pilih rombel sumber terlebih dahulu!");
                    return;
                }

                // Kumpulkan ID CP yang sedang tampil
                let cpIds = [];
                document.querySelectorAll("#tbody-kktp tr[data-cpid]").forEach(tr => {
                    let cpId = tr.getAttribute('data-cpid');
                    if (cpId) cpIds.push(cpId);
                });

                if (cpIds.length === 0) {
                    alert("Tidak ada target materi untuk disalin. Tabel masih kosong!");
                    return;
                }

                if (!confirm("Apakah Anda yakin ingin menimpa Rubrik KKTP rombel ini dengan data dari rombel yang dipilih?")) {
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
                    const response = await fetch("<?= base_url('guru/kktp/copy') ?>", {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    const result = await response.json();
                    if (result.status === 'success') {
                        alert("✅ " + result.message);
                        window.location.reload(); // Reload agar tabel update
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
        }
    </script>
</body>
</html>
