<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .sticky-header th { position: sticky; top: 0; background: #343a40; color: #fff; z-index: 10; border: 1px solid #454d55; text-align: center; vertical-align: middle; }
        .col-locked { background-color: #f4f6f9 !important; }
    </style>
</head>
<body class="p-4 bg-light">
    
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-info font-weight-bold"><i class="fas fa-book-reader mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Kelas: <strong class="text-dark"><?= esc($rombel['rombel_name']) ?></strong> | Tahun Ajaran: <strong><?= $tahun_ajaran ?> (<?= $semester ?>)</strong></p>
            </div>
            <!-- Sesuaikan dengan URL kembali ke halaman utama tabel kelas Anda -->
            <a href="<?= base_url('guru/ekstrakurikuler') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success shadow-sm">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <form action="<?= base_url('guru/peminatan/save_nilai/'.$rombel['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="tahun_ajaran" value="<?= $tahun_ajaran ?>">
                <input type="hidden" name="semester" value="<?= $semester ?>">

                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                        <table class="table table-bordered table-sm table-hover mb-0">
                            <thead>
                                <tr class="sticky-header">
                                    <th width="3%" class="text-center">No</th>
                                    <th width="20%">Nama Siswa</th>
                                    
                                    <!-- Header Kolom 6 Bulan -->
                                    <?php foreach($list_bulan as $bln): ?>
                                        <th>
                                            <?= $bln['nama'] ?>
                                            <?php if($bln['is_locked']): ?>
                                                <br><i class="fas fa-lock text-warning mt-1" title="Belum tiba"></i>
                                            <?php else: ?>
                                                <br><i class="fas fa-unlock text-info mt-1"></i>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach($anggota as $a): ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold text-muted"><?= $no++ ?></td>
                                        <td class="align-middle font-weight-bold text-dark text-nowrap">
                                            <?= esc($a['nama_siswa']) ?>
                                        </td>
                                        
                                        <!-- Render Input per Bulan -->
                                        <?php foreach($list_bulan as $bln): ?>
                                            <?php 
                                                $currentGrade = $grades[$a['student_id']][$bln['angka']] ?? '';
                                                $displayGrade = '';
                                                if ($currentGrade !== null && $currentGrade !== '') {
                                                    $displayGrade = str_replace('.', ',', (string)(float)$currentGrade);
                                                }
                                            ?>
                                            <td class="<?= $bln['is_locked'] ? 'col-locked' : 'text-center' ?> align-middle">
                                                <input type="text" 
                                                       name="grades[<?= $a['student_id'] ?>][<?= $bln['angka'] ?>]" 
                                                       class="form-control text-center mx-auto nilai-input" 
                                                       style="width: 70px; font-weight: bold; <?= $bln['is_locked'] ? 'background-color: #e9ecef;' : '' ?>"
                                                       value="<?= esc($displayGrade) ?>"
                                                       placeholder="-"
                                                       <?= $bln['is_locked'] ? 'readonly tabindex="-1"' : '' ?>>
                                            </td>
                                        <?php endforeach; ?>
                                        
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer bg-light py-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-info text-white font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Nilai Peminatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT VALIDASI KOMA (Tanpa Fitur Copy) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.nilai-input:not([readonly])');

            inputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let val = this.value.replace(/,/g, '.');
                    val = val.replace(/[^0-9.]/g, '');
                    
                    const parts = val.split('.');
                    if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('').replace(/\./g, '');
                    
                    if (val.includes('.')) {
                        const splitVal = val.split('.');
                        if (splitVal[1].length > 2) val = splitVal[0] + '.' + splitVal[1].substring(0, 2);
                    }
                    
                    if (val !== '' && parseFloat(val) > 100) val = '100';
                    
                    this.value = val.replace(/\./g, ',');
                });

                input.addEventListener('blur', function() {
                    let val = this.value.trim();
                    if (val !== '') {
                        let floatVal = parseFloat(val.replace(/,/g, '.'));
                        if (!isNaN(floatVal)) {
                            this.value = floatVal.toString().replace(/\./g, ',');
                        } else {
                            this.value = '';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>