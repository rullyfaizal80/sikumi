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

        <div class="card p-3 mb-4 shadow-sm border-0">
            <div class="row">
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Mata Pelajaran Anda</label>
                    <select id="mapel_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($subjectOptions)): ?><option value="">- Tidak ada mapel -</option><?php else: ?>
                            <?php foreach($subjectOptions as $id => $val): ?>
                                <option value="<?= esc($id) ?>" <?= ($id == $selectedMapelId) ? 'selected' : '' ?>><?= esc($val) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Fase / Kelas Anda</label>
                    <select id="kelas_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($classOptions)): ?><option value="">- Tidak ada kelas -</option><?php else: ?>
                            <?php foreach($classOptions as $id => $val): ?>
                                <option value="<?= esc($id) ?>" <?= ($id == $selectedKelasId) ? 'selected' : '' ?>><?= esc($val) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Total JP Tersedia (Semester Ini)</label>
                    <div class="form-control form-control-sm bg-light border-success text-success font-weight-bold text-center" id="label-jp-tersedia">
                        ⏳ <?= isset($totalJpTersedia) && $totalJpTersedia > 0 ? esc($totalJpTersedia) . ' JP' : '0 JP' ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>