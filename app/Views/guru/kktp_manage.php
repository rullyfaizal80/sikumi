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
                <button type="button" class="btn btn-outline-success font-weight-bold shadow-sm px-3 btn-ai-kktp">
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

    <script>
        // ==============================================================
        // 1. AI GENERATOR
        // ==============================================================
        document.querySelector('.btn-ai-kktp').addEventListener('click', async function() {
            const btn = this;
            const rows = document.querySelectorAll('#tbody-kktp tr[data-cpid]');
            if(rows.length === 0) return alert("Tidak ada TP untuk diproses.");
            if(!confirm("AI akan membuatkan deskripsi rubrik otomatis untuk setiap Tujuan Pembelajaran. Proses ini mungkin memakan waktu. Lanjutkan?")) return;

            btn.disabled = true;
            let originalText = btn.innerHTML;
            btn.innerHTML = "⏳ AI Sedang Menganalisis...";

            for (let tr of rows) {
                let tp = tr.querySelector('.tp-text').innerText;
                
                // 🌟 PERBAIKAN: Prompt AI ditambahkan format kolom 'indikator'
                let prompt = `Anda adalah pakar Kurikulum Merdeka. Buatkan deskripsi Rubrik KKTP untuk Tujuan Pembelajaran: "${tp}".
                Output WAJIB berupa raw JSON (tanpa markdown, tanpa bungkus apapun): {"indikator":"", "sb":"", "b":"", "c":"", "pb":""}.
                indikator: Bukti ketercapaian singkat (Evidence).
                sb: Deskripsi kriteria Sangat Baik (rentang 90-100).
                b: Deskripsi kriteria Baik (rentang 80-89).
                c: Deskripsi kriteria Cukup (rentang 70-79).
                pb: Deskripsi kriteria Perlu Bimbingan (rentang <70).
                Gunakan bahasa operasional yang singkat dan padat.`;

                try {
                    const response = await fetch("<?= base_url('ai/analyze_cp') ?>", {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: "message=" + encodeURIComponent(prompt)
                    });
                    const res = await response.json();
                    let cleanJson = res.reply.replace(/```json/g, '').replace(/```/g, '').trim();
                    let data = JSON.parse(cleanJson);
                    
                    // 🌟 PERBAIKAN: Mengisi data indikator ke kolom yang tepat
                    if(tr.querySelector('.cell-indikator')) {
                        tr.querySelector('.cell-indikator').innerText = data.indikator || '';
                    }
                    tr.querySelector('.cell-sb').innerText = data.sb || '';
                    tr.querySelector('.cell-b').innerText = data.b || '';
                    tr.querySelector('.cell-c').innerText = data.c || '';
                    tr.querySelector('.cell-pb').innerText = data.pb || '';
                } catch(e) { 
                    console.error("Gagal generate baris TP: " + tp, e); 
                }
            }
            
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert("✅ AI selesai menyusun rubrik! Jangan lupa klik Simpan.");
        });

        // ==============================================================
        // 2. SIMPAN KKTP
        // ==============================================================
        document.querySelector('.btn-save-kktp').addEventListener('click', async function() {
            const btnSave = this;
            const dataKktp = [];
            let rombelId = document.querySelector('select[name="rombel_id"]').value;

            document.querySelectorAll('#tbody-kktp tr[data-cpid]').forEach(tr => {
                // 🌟 PERBAIKAN: Menarik teks dari sel indikator untuk disimpan ke database
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
    </script>
</body>
</html>