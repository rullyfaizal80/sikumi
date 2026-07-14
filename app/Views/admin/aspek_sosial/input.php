<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Input Aspek Sosial</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom th, .table-custom td { vertical-align: middle; }
        .table-custom th { font-size: 12px; background-color: #f8f9fa; }
        .chk-box { width: 18px; height: 18px; cursor: pointer; }
        .input-ket { font-size: 12px; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-users mr-2" style="color: #17a2b8;"></i> Input Sosial: <?= esc($rombel['rombel_name']) ?>
                </h3>
            </div>
            <div>
                <a href="<?= base_url('admin/aspek-sosial') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <!-- Filter Tanggal -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('admin/aspek-sosial/input/'.$rombel['id']) ?>" method="GET" class="d-flex align-items-center">
                    <label class="font-weight-bold mr-3 mb-0" style="white-space: nowrap;">
                        <i class="fas fa-calendar-alt mr-1"></i> Pilih Tanggal Input:   
                    </label>
                    <input type="date" name="tanggal" class="form-control form-control-sm" style="width: 150px;" value="<?= esc($tanggal) ?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        <!-- Form Checklist -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <form action="<?= base_url('admin/aspek-sosial/save') ?>" method="POST">
                    <input type="hidden" name="rombel_id" value="<?= esc($rombel['id']) ?>">
                    <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
                    
                    <table class="table table-bordered table-hover table-custom mb-0">
                        <thead>
                            <tr class="text-center">
                                <th width="3%" rowspan="2">No</th>
                                <th width="15%" rowspan="2" class="text-left">Nama Siswa</th>
                                <th colspan="7">Ceklis Jika Melanggar / Ada Catatan</th>
                                <th width="25%" rowspan="2">Keterangan / Catatan</th>
                            </tr>
                            <tr class="text-center"> 
                                <th>Disiplin</th> 
                                <th>Jujur</th> 
                                <th>Percaya Diri</th> 
                                <th>Santun</th> 
                                <th>Kerjasama</th> 
                                <th>Tanggung Jawab</th> 
                                <th>Adil</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($students)): ?>
                                <tr><td colspan="10" class="text-center py-4">Data siswa belum tersedia.</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($students as $siswa): ?>
                                <?php $sId = $siswa['student_id']; ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="font-weight-bold text-left"><?= esc($siswa['name']) ?></td>
                                    
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][disiplin]" value="1" data-label="Kurang disiplin" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['disiplin'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][jujur]" value="1" data-label="Kurang jujur" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['jujur'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][percaya_diri]" value="1" data-label="Kurang percaya diri" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['percaya_diri'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][santun]" value="1" data-label="Kurang santun" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['santun'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][kerjasama]" value="1" data-label="Kurang kerjasama" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['kerjasama'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][tanggung_jawab]" value="1" data-label="Kurang tanggung jawab" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['tanggung_jawab'] ? 'checked' : '' ?>>
                                    </td>
                                    <td class="text-center bg-white">
                                        <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][adil]" value="1" data-label="Kurang adil" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['adil'] ? 'checked' : '' ?>>
                                    </td>
                                    
                                    <!-- Input Keterangan -->
                                    <td>
                                        <input type="text" id="ket-<?= $sId ?>" name="students[<?= $sId ?>][keterangan]" class="form-control form-control-sm input-ket" value="<?= esc($siswa['keterangan'] ?? '') ?>" placeholder="Aman">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div class="p-3 bg-light border-top text-right">
                        <button type="submit" class="btn btn-success font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Data Sosial
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function generateKet(element) {
            let studentId = element.getAttribute('data-studentid');
            let combinedText = [];
            let checkboxes = document.querySelectorAll('.cb-' + studentId + ':checked');
            
            checkboxes.forEach(function(cb) {
                combinedText.push(cb.getAttribute('data-label'));
            });
            
            let ketInput = document.getElementById('ket-' + studentId);
            ketInput.value = combinedText.join(', ');
            if(combinedText.length === 0) ketInput.value = "";
        }
    </script>
</body>
</html>