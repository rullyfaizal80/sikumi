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
        .table-input select { border: 1px solid #ced4da; font-weight: bold; padding: 2px 5px; height: 30px; }
        .table-input select:disabled { background-color: #e9ecef; cursor: not-allowed; }
        .col-locked { background-color: #f4f6f9 !important; text-align: center; }
    </style>
</head>
<body class="p-4 bg-light">
    
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-edit mr-2"></i> Input Nilai Bulanan</h3>
                <p class="text-muted small mb-0">Kelompok: <strong class="text-dark"><?= esc($kelompok['nama_kelompok']) ?></strong> | Tahun Ajaran: <strong><?= $tahun_ajaran ?> (<?= $semester ?>)</strong></p>
            </div>
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
            <form action="<?= base_url('guru/ekstrakurikuler/kelompok/save_nilai/'.$kelompok['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <!-- Informasi Parameter -->
                <input type="hidden" name="tahun_ajaran" value="<?= $tahun_ajaran ?>">
                <input type="hidden" name="semester" value="<?= $semester ?>">

                <div class="card-body p-0">
                    <div class="alert alert-info m-3 py-2 small">
                        <i class="fas fa-info-circle mr-1"></i> Kolom bulan yang ditandai dengan ikon gembok (<i class="fas fa-lock"></i>) tidak dapat diisi karena bulannya belum tiba berdasarkan waktu sistem.
                    </div>

                    <?php if(empty($anggota)): ?>
                        <div class="text-center py-4 text-muted">Belum ada siswa di kelompok ini.</div>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 550px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-bordered table-sm table-hover mb-0 table-input">
                                <thead>
                                    <tr class="sticky-header">
                                        <th width="3%" class="text-center">No</th>
                                        <th width="20%">Nama Siswa</th>
                                        
                                        <!-- Header Kolom 6 Bulan -->
                                        <?php foreach($list_bulan as $bln): ?>
                                            <th>
                                                <?= $bln['nama'] ?>
                                                <?php if($bln['is_locked']): ?>
                                                    <br><i class="fas fa-lock text-warning mt-1" title="Bulan belum tiba"></i>
                                                <?php else: ?>
                                                    <br><i class="fas fa-unlock text-success mt-1"></i>
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
                                            
                                          <!-- Render Input Nilai per Bulan -->
<?php foreach($list_bulan as $bln): ?>
    <?php 
        // Ambil nilai saat ini jika ada
        $currentGrade = $grades[$a['student_id']][$bln['angka']] ?? '';
        
        // SINKRONISASI TAMPILAN: Cast ke float untuk membuang desimal .00 yang tidak perlu, lalu ubah titik jadi koma
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
                    <?php endif; ?>
                </div>
                
                <div class="card-footer bg-light py-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Nilai Eskul
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.nilai-input:not([readonly])');

        inputs.forEach(input => {
            // 1. Validasi saat guru mengetik
            input.addEventListener('input', function(e) {
                // Ubah koma ke titik secara internal agar mudah divalidasi
                let val = this.value.replace(/,/g, '.');
                
                // Hanya izinkan angka dan satu titik desimal
                val = val.replace(/[^0-9.]/g, '');

                // Cegah input titik ganda (misal: 95..5)
                const parts = val.split('.');
                if (parts.length > 2) {
                    val = parts[0] + '.' + parts.slice(1).join('').replace(/\./g, '');
                }

                // Batasi maksimal 2 angka di belakang desimal
                if (val.includes('.')) {
                    const splitVal = val.split('.');
                    if (splitVal[1].length > 2) {
                        val = splitVal[0] + '.' + splitVal[1].substring(0, 2);
                    }
                }
                
                // Batasi maksimal nilai 100
                if (val !== '' && parseFloat(val) > 100) {
                    val = '100';
                }

                // Kembalikan visual ke format koma agar familiar bagi guru
                this.value = val.replace(/\./g, ',');
            });

            // 2. Bersihkan angka desimal tidak perlu saat input kehilangan fokus (blur)
            input.addEventListener('blur', function() {
                let val = this.value.trim();
                if (val !== '') {
                    // Konversi koma ke titik untuk kalkulasi float di JS
                    let floatVal = parseFloat(val.replace(/,/g, '.'));
                    
                    if (!isNaN(floatVal)) {
                        // floatVal.toString() otomatis membuang .00 atau desimal kosong di belakangnya
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