<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-custom th, .table-custom td { vertical-align: middle; }
        .table-custom th { font-size: 12px; background-color: #f8f9fa; }
        .chk-box { width: 18px; height: 18px; cursor: pointer; }
        .input-ket { font-size: 12px; }
        .th-rotate { height: 120px; white-space: nowrap; }
        .th-rotate > div { transform: translate(10px, 40px) rotate(270deg); width: 30px; }
        .th-rotate > div > span { padding: 5px 10px; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-praying-hands mr-2" style="color: #28a745;"></i> <?= esc($title) ?>
                </h3>
            </div>
            <div>
                <a href="<?= base_url('admin/spiritual') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
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
                <form action="<?= base_url('admin/spiritual/input/'.$rombel_id) ?>" method="GET" class="d-flex align-items-center">
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
                <form action="<?= base_url('admin/spiritual/save') ?>" method="POST">
                    <input type="hidden" name="rombel_id" value="<?= esc($rombel_id) ?>">
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
  <th>Membiasakan Berdoa</th> 
  <th>Membiasakan Kalimat Thoyibah</th> 
  <th>Menjalankan Ibadah Shalat</th> 
  <th>Membudayakan Salam</th> 
  <th>Membiasakan Rasa Syukur</th> 
  <th>Menjaga Lingkungan Sekolah</th> 
  <th>Toleransi</th> 
</tr>

                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($siswaData as $siswa): ?>
                            <?php $sId = $siswa['student_id']; ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="font-weight-bold"><?= esc($siswa['username']) ?></td>
                                
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][berdoa]" data-label="Tdk berdoa" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['berdoa'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][kalimat_thoyibah]" data-label="Tdk terbiasa kalimat thoyibah" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['kalimat_thoyibah'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][shalat]" data-label="Meninggalkan shalat" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['shalat'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][salam]" data-label="Tdk membudayakan salam" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['salam'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][syukur]" data-label="Kurang rasa syukur" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['syukur'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][lingkungan]" data-label="Merusak lingkungan" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['lingkungan'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][toleransi]" data-label="Kurang toleransi" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['toleransi'] ? 'checked' : '' ?>>
                                </td>
                                
                                <!-- Input Keterangan -->
                                <td>
                                    <input type="text" id="ket-<?= $sId ?>" name="students[<?= $sId ?>][keterangan]" class="form-control form-control-sm input-ket" value="<?= esc($siswa['keterangan']) ?>" placeholder="Aman">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="p-3 bg-light border-top text-right">
                        <button type="submit" class="btn btn-success font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Data Spiritual
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