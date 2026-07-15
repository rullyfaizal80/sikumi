<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 600px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 text-warning font-weight-bold"><i class="fas fa-edit mr-2"></i> Edit Kelompok</h3>
            <a href="<?= base_url('guru/quran_kelompok') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
    <i class="fas fa-arrow-left mr-1"></i> Kembali
</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form action="<?= base_url('kelompok/update/'.$kelompok['id']) ?>" method="POST">
                    
                    <div class="form-group">
                        <label>Nama Kelompok</label>
                        <input type="text" name="nama_kelompok" class="form-control" required value="<?= esc($kelompok['nama_kelompok']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Jenis Kelompok</label>
                        <select name="jenis_kelompok" class="form-control" required>
                            <option value="Reguler" <?= ($kelompok['jenis_kelompok'] == 'Reguler') ? 'selected' : '' ?>>Reguler</option>
                            <option value="Khusus" <?= ($kelompok['jenis_kelompok'] == 'Khusus') ? 'selected' : '' ?>>Khusus</option>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label>Pembimbing</label>
                        <select name="pembimbing_id" class="form-control" required>
                            <?php foreach($pembimbing as $guru): ?>
                                <option value="<?= $guru['id'] ?>" <?= ($guru['id'] == $kelompok['pembimbing_id']) ? 'selected' : '' ?>>
                                    <?= esc($guru['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="text-right border-top pt-3">
                        <button type="submit" class="btn btn-warning font-weight-bold text-white px-4">
                            <i class="fas fa-save mr-1"></i> Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>