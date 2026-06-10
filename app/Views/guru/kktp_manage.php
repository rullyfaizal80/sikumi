<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KKTP - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        .table-kktp th { background-color: #002060; color: white; font-size: 11px; text-align: center; vertical-align: middle; }
        .table-kktp td { font-size: 11px; padding: 8px; vertical-align: top; }
        .editable-cell { border: 1px solid #ddd; padding: 5px; min-height: 50px; background: #fff; border-radius: 4px; }
        .editable-cell:focus { outline: 2px solid #FF9F00; }
    </style>
</head>
<body class="bg-light">
    <div class="wrapper p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">🎯 Kriteria Ketercapaian TP (KKTP)</h3>
                <p class="text-muted mb-0">Penentuan Kriteria Ketuntasan Berdasarkan Indikator</p>
            </div>
            <div>
                <button type="button" id="btn-ai-kktp" class="btn btn-outline-success btn-sm font-weight-bold me-2">✨ AI Generate Rubrik</button>
                <button type="button" id="btn-save-kktp" class="btn btn-success btn-sm font-weight-bold">💾 Simpan KKTP</button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Pilih Rombel</label>
                        <select name="rombel_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <?php foreach($daftarRombel as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $selectedRombelId == $r['id'] ? 'selected' : '' ?>><?= $r['rombel_name'] ?></option>
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
                                <th width="15%">Tujuan Pembelajaran</th>
                                <th width="15%">Indikator (Evidence)</th>
                                <th width="16%">Sangat Baik (90-100)</th>
                                <th width="16%">Baik (80-89)</th>
                                <th width="16%">Cukup (70-79)</th>
                                <th width="16%">Perlu Bimbingan (<70)</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-kktp">
                            <?php foreach($dataKktp as $idx => $row): ?>
                            <tr data-id="<?= $row['id'] ?>">
                                <td class="text-center font-weight-bold"><?= $row['urutan'] ?></td>
                                <td class="tp-text"><?= esc($row['tujuan_pembelajaran']) ?></td>
                                <td><div class="editable-cell cell-indikator" contenteditable="true"><?= esc($row['indikator']) ?></div></td>
                                <td><div class="editable-cell cell-sb" contenteditable="true"><?= esc($row['skor_sangat_baik']) ?></div></td>
                                <td><div class="editable-cell cell-b" contenteditable="true"><?= esc($row['skor_baik']) ?></div></td>
                                <td><div class="editable-cell cell-c" contenteditable="true"><?= esc($row['skor_cukup']) ?></div></td>
                                <td><div class="editable-cell cell-pb" contenteditable="true"><?= esc($row['skor_perlu_bimbingan']) ?></div></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // TRIGGER AI GENERATE
        document.getElementById('btn-ai-kktp').addEventListener('click', async function() {
            const btn = this;
            const rows = document.querySelectorAll('#tbody-kktp tr');
            
            if(!confirm("AI akan membuatkan rubrik otomatis berdasarkan TP. Lanjutkan?")) return;

            btn.disabled = true;
            btn.innerHTML = "⏳ AI Berpikir...";

            for (let tr of rows) {
                let tp = tr.querySelector('.tp-text').innerText;
                
                // Prompt khusus KKTP
                let prompt = `Anda adalah pakar Kurikulum Merdeka. Buatkan Rubrik KKTP untuk TP: "${tp}".
                Output harus JSON: {"indikator":"", "sb":"", "b":"", "c":"", "pb":""}. 
                "sb" adalah kriteria skor 90-100, "b" 80-89, "c" 70-79, "pb" < 70. 
                Singkat, padat, operasional. JANGAN PAKAI MARKDOWN.`;

                try {
                    const response = await fetch("<?= base_url('ai/analyze_cp') ?>", {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: "message=" + encodeURIComponent(prompt)
                    });
                    const res = await response.json();
                    let data = JSON.parse(res.reply.replace(/```json/g, '').replace(/```/g, ''));
                    
                    tr.querySelector('.cell-indikator').innerText = data.indikator;
                    tr.querySelector('.cell-sb').innerText = data.sb;
                    tr.querySelector('.cell-b').innerText = data.b;
                    tr.querySelector('.cell-c').innerText = data.c;
                    tr.querySelector('.cell-pb').innerText = data.pb;
                } catch(e) { console.error("Gagal generate baris ini", e); }
            }
            
            btn.disabled = false;
            btn.innerHTML = "✨ AI Generate Rubrik";
            alert("✅ AI selesai meng-generate rubrik!");
        });

        // TRIGGER SIMPAN
        document.getElementById('btn-save-kktp').addEventListener('click', async function() {
            const dataKktp = [];
            document.querySelectorAll('#tbody-kktp tr').forEach(tr => {
                dataKktp.push({
                    cp_id: tr.getAttribute('data-id'),
                    indikator: tr.querySelector('.cell-indikator').innerText,
                    sangat_baik: tr.querySelector('.cell-sb').innerText,
                    baik: tr.querySelector('.cell-b').innerText,
                    cukup: tr.querySelector('.cell-c').innerText,
                    perlu_bimbingan: tr.querySelector('.cell-pb').innerText
                });
            });

            const fd = new FormData();
            fd.append('rombel_id', '<?= $selectedRombelId ?>');
            fd.append('data_kktp', JSON.stringify(dataKktp));

            const res = await fetch("<?= base_url('guru/kktp/simpan') ?>", { method: 'POST', body: fd });
            const result = await res.json();
            alert(result.message);
        });
    </script>
</body>
</html>