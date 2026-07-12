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
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-edit mr-2" style="color: #3498db;"></i> <?= esc($title) ?>
                </h3>
            </div>
            <div>
                <a href="<?= base_url('admin/kepatuhan') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
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
                <form action="<?= base_url('admin/kepatuhan/input/'.$rombel_id) ?>" method="GET" class="d-flex align-items-center">
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
                <form action="<?= base_url('admin/kepatuhan/save') ?>" method="POST">
                    <input type="hidden" name="rombel_id" value="<?= esc($rombel_id) ?>">
                    <input type="hidden" name="tanggal" value="<?= esc($tanggal) ?>">
                    
                    <table class="table table-bordered table-hover table-custom mb-0">
                        <thead>
                            <tr class="text-center">
                                <th width="3%">No</th>
                                <th width="15%" class="text-left">Nama Siswa</th>
                                <th width="8%">Tidak Berseragam</th>
                                <th width="8%">Atribut Tdk Lengkap</th>
                                <th width="8%">Tidak Bersih Diri</th>
                                <th width="8%">Terlambat Hadir</th>
                                <th width="8%">Melanggar Aturan Kls</th>
                                <th width="8%">Melanggar Masjid</th>
                                <th width="25%">Keterangan (Otomatis)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($siswaData as $siswa): ?>
                            <?php $sId = $siswa['student_id']; ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="font-weight-bold"><?= esc($siswa['username']) ?></td>
                                
                                <!-- Checkbox Pelanggaran (Tag PHP pada onchange dihapus dan diganti (this) + data-studentid) -->
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][seragam]" data-label="Tidak berseragam sesuai jadwal" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['seragam'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][atribut]" data-label="Tidak beratribut lengkap" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['atribut'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][bersih_diri]" data-label="Tidak bersih diri" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['bersih_diri'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][terlambat]" data-label="Terlambat hadir" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['terlambat'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][aturan_kelas]" data-label="Melanggar peraturan kelas" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['aturan_kelas'] ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center bg-white">
                                    <input type="checkbox" class="chk-box cb-<?= $sId ?>" name="students[<?= $sId ?>][masjid]" data-label="Melanggar SOP masjid" data-studentid="<?= $sId ?>" onchange="generateKet(this)" <?= $siswa['masjid'] ? 'checked' : '' ?>>
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
                        <button type="submit" class="btn btn-primary font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Data Kepatuhan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Script Autogenerate Keterangan -->
    <script>
        // Menerima parameter element (this) dari checkbox
        function generateKet(element) {
            // Mengambil ID siswa dari atribut data-studentid (bebas dari tag PHP)
            let studentId = element.getAttribute('data-studentid');
            let combinedText = [];
            
            // Ambil semua checkbox yang dicentang pada baris siswa tersebut
            let checkboxes = document.querySelectorAll('.cb-' + studentId + ':checked');
            
            checkboxes.forEach(function(cb) {
                combinedText.push(cb.getAttribute('data-label'));
            });
            
            // Masukkan teks gabungan ke kolom input keterangan, dipisahkan koma
            let ketInput = document.getElementById('ket-' + studentId);
            ketInput.value = combinedText.join(', ');
            
            // Jika kosong (ceklisan dilepas), kembalikan placeholder
            if(combinedText.length === 0) {
                ketInput.value = "";
            }
        }
    </script>
</body>
</html>