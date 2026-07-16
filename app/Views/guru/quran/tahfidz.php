<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-input th { vertical-align: middle !important; background-color: #f4f6f9; }
        .btn-copy { font-size: 10px; padding: 2px 6px; border-radius: 12px; margin-top: 5px; }
        .btn-copy:hover { background-color: #007bff; color: white; border-color: #007bff; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-primary font-weight-bold"><i class="fas fa-quran mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Kelola penilaian Tahfidz siswa berdasarkan rentang pekan per bulan.</p>
            </div>
            <div>
                <a href="<?= base_url('guru/quran') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Menu
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success shadow-sm alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="close" onclick="this.parentElement.remove()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- FILTER BULAN, TAHUN & PEKAN -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('guru/quran/tahfidz/'.$kelompok['id']) ?>" method="GET" class="d-flex align-items-center">
                    
                    <label class="font-weight-bold mr-2 mb-0">Bulan:</label>
                    <select name="bulan" class="form-control form-control-sm mr-3" style="width: 130px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($angka == $bulan) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="font-weight-bold mr-2 mb-0">Tahun:</label>
                    <select name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;">
                        <?php for($t = date('Y') - 2; $t <= date('Y') + 1; $t++): ?>
                            <option value="<?= $t ?>" <?= ($t == $tahun) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>

                    <label class="font-weight-bold mr-2 mb-0">Pekan Ke:</label>
                    <select name="pekan" class="form-control form-control-sm mr-3 bg-primary text-white font-weight-bold border-primary" style="width: 90px;">
                        <?php for($p = 1; $p <= 5; $p++): ?>
                            <option value="<?= $p ?>" <?= ($p == $pekan) ? 'selected' : '' ?>>Pekan <?= $p ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3">
                        <i class="fas fa-filter mr-1"></i> Buka Form
                    </button>
                </form>
            </div>
        </div>

        <!-- FORM INPUT NILAI TAHFIDZ -->
        <form action="<?= base_url('guru/quran/tahfidz/save') ?>" method="POST">
            <!-- Ubah hidden input menjadi group_id -->
            <input type="hidden" name="group_id" value="<?= $kelompok['id'] ?>">
            <input type="hidden" name="bulan" value="<?= $bulan ?>">
            <input type="hidden" name="tahun" value="<?= $tahun ?>">
            <input type="hidden" name="pekan" value="<?= $pekan ?>">

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered table-input mb-0 text-center" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th width="5%" rowspan="2">No</th>
                                <th width="20%" rowspan="2" class="text-left px-3">Nama Siswa</th>
                                <th colspan="4" class="text-primary bg-light">KATEGORI TAHFIDZ (PEKAN <?= $pekan ?>)</th>
                            </tr>
                            <tr>
                                <th width="20%">
                                    SABQI/MUROJAAH (Surat)<br>
                                    <button type="button" class="btn btn-outline-primary btn-copy" onclick="copyToAll('input-sabqi')">
                                        <i class="fas fa-copy"></i> Copy ke Bawah
                                    </button>
                                </th>
                                <th width="20%">
                                    SABAQ/ZIYADAH (Surat)<br>
                                    <button type="button" class="btn btn-outline-primary btn-copy" onclick="copyToAll('input-sabaq')">
                                        <i class="fas fa-copy"></i> Copy ke Bawah
                                    </button>
                                </th>
                                <th width="10%">NILAI</th>
                                <th width="25%">CATATAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($daftarSiswa as $siswa): 
                                $sId = $siswa['student_id'];
                                $vSabqi   = isset($nilaiData[$sId]) ? $nilaiData[$sId]['tahfidz_sabqi'] : '';
                                $vSabaq   = isset($nilaiData[$sId]) ? $nilaiData[$sId]['tahfidz_sabaq'] : '';
                                $vNilai   = isset($nilaiData[$sId]) ? $nilaiData[$sId]['tahfidz_nilai'] : '';
                                $vCatatan = isset($nilaiData[$sId]) ? $nilaiData[$sId]['tahfidz_catatan'] : '';
                            ?>
                            <tr>
                                <td class="font-weight-bold"><?= $no++ ?></td>
                                <td class="text-left font-weight-bold px-3"><?= esc($siswa['username']) ?></td>
                                
                                <td>
                                    <input type="text" name="data[<?= $sId ?>][sabqi]" class="form-control form-control-sm input-sabqi" value="<?= esc($vSabqi) ?>" placeholder="Contoh: Al-Mulk">
                                </td>
                                <td>
                                    <input type="text" name="data[<?= $sId ?>][sabaq]" class="form-control form-control-sm input-sabaq" value="<?= esc($vSabaq) ?>" placeholder="Contoh: Al-Qalam">
                                </td>
                                <td>
                                    <!-- Input nilai hanya menerima angka dan koma, tanpa placeholder -->
                                    <input type="text" name="data[<?= $sId ?>][nilai]" class="form-control form-control-sm font-weight-bold text-center" value="<?= esc($vNilai) ?>" oninput="this.value = this.value.replace(/[^0-9,]/g, '');">
                                </td>
                                <td>
                                    <input type="text" name="data[<?= $sId ?>][catatan]" class="form-control form-control-sm" value="<?= esc($vCatatan) ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-right py-3 border-top-0">
                    <button type="submit" class="btn btn-primary font-weight-bold px-5 py-2 shadow-sm">
                        <i class="fas fa-save mr-2"></i> Simpan Data Tahfidz Pekan <?= $pekan ?>
                    </button>
                </div>
            </div>
        </form>

    </div>

    <!-- SCRIPT COPY MASSAL -->
    <script>
        function copyToAll(className) {
            let inputs = document.getElementsByClassName(className);
            if (inputs.length > 0) {
                let valueToCopy = inputs[0].value;
                if(valueToCopy.trim() === '') {
                    alert('Isi dulu baris pertama (Siswa No 1), baru klik Copy ke Bawah.');
                    return;
                }
                for (let i = 1; i < inputs.length; i++) {
                    inputs[i].value = valueToCopy;
                }
            }
        }
    </script>
</body>
</html>