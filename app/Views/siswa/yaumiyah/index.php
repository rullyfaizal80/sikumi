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
        .table-custom th { font-size: 13px; background-color: #f8f9fa; }
        .chk-box { width: 18px; height: 18px; cursor: pointer; }
        
        /* Warna khusus untuk menandai hari ini */
        .row-today { background-color: #e2f3f5 !important; } 
        .row-today td.bg-white { background-color: #e2f3f5 !important; }
        
        /* Warna khusus untuk menandai hari libur (Sabtu & Minggu) */
        .row-weekend { background-color: #ffeded !important; color: #c0392b; }
        .row-weekend td.bg-white { background-color: #ffeded !important; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #2c3e50; font-weight: 700;">
                    <i class="fas fa-book-open mr-2" style="color: #27ae60;"></i> <?= esc($title) ?>
                </h3>
            </div>
            <div>
                <!-- Sesuaikan link 'home' dengan rute dashboard siswa Anda -->
                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle mr-1"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <!-- Filter Bulan & Tahun -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('siswa/yaumiyah') ?>" method="GET" class="d-flex align-items-center">
                    <label class="font-weight-bold mr-3 mb-0" style="white-space: nowrap;">
                        <i class="fas fa-calendar-alt mr-1"></i> Filter Bulan:   
                    </label>
                    <select name="bulan" class="form-control form-control-sm mr-2" style="width: 150px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($bulan === $angka) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;" value="<?= esc($tahun) ?>">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">
                        <i class="fas fa-search mr-1"></i> Tampilkan
                    </button>
                </form>
            </div>
        </div>

        <!-- Form Checklist Yaumiyah -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <form action="<?= base_url('siswa/yaumiyah/save') ?>" method="POST">
                    <input type="hidden" name="bulan" value="<?= esc($bulan) ?>">
                    <input type="hidden" name="tahun" value="<?= esc($tahun) ?>">
                    
                    <table class="table table-bordered table-hover table-custom mb-0 text-center">
                        <thead>
                            <tr>
                                <th width="5%">Tgl</th>
                                <th width="10%" title="Shalat Dzuhur">Dzuhur</th>
                                <th width="10%" title="Shalat Ashar">Ashar</th>
                                <th width="10%" title="Shalat Sunnah Ba'diyah Dzuhur">Ba'diyah<br>Dzuhur</th>
                                <th width="10%" title="Shalat Duha">Duha</th>
                                <th width="10%" title="Shalat Tahajud">Tahajud</th>
                                <th width="10%" title="Membaca Al-Qur'an">Tilawah</th>
                                <th width="10%" title="Infaq/Sedekah">Infaq</th>
                                <th width="10%" title="Puasa Sunnah">Shaum</th>
                                <th width="15%" title="Membaca Buku/Artikel">Literasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Indikator nama kolom di database
                            $indikator = ['dzuhur', 'ashar', 'bakdiah_dzuhur', 'duha', 'tahajud', 'tilawah', 'infaq', 'shaum', 'literasi'];
                            
                            // Looping berdasarkan jumlah hari di bulan dan tahun terpilih
                            for ($i = 1; $i <= $jumlahHari; $i++): 
                                $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $i);
                                
                                // Cek Hari dalam seminggu: 1 (Senin) s/d 7 (Minggu)
                                $dayOfWeek = date('N', strtotime($tgl));
                                $isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7);
                                
                                // Deteksi class untuk baris (Hari ini diprioritaskan, jika bukan maka cek apakah weekend)
                                $rowClass = '';
                                if ($tgl == date('Y-m-d')) {
                                    $rowClass = 'row-today font-weight-bold';
                                } elseif ($isWeekend) {
                                    $rowClass = 'row-weekend font-weight-bold';
                                }
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <!-- Kolom Tanggal (Ditambahkan nama hari khusus untuk weekend) -->
                                    <td class="align-middle">
                                        <?= $i ?>
                                        <?php if($dayOfWeek == 6): ?>
                                            <div style="font-size: 10px; color: #c0392b;">(Sabtu)</div>
                                        <?php elseif($dayOfWeek == 7): ?>
                                            <div style="font-size: 10px; color: #c0392b;">(Minggu)</div>
                                        <?php endif; ?>
                                    </td>
                                    
                                   <!-- Kolom Checkbox -->
<?php foreach ($indikator as $ind): 
    $isChecked = isset($yaumiyahData[$tgl][$ind]) && $yaumiyahData[$tgl][$ind] == 1;
?>
    <td class="bg-white">
        <?php if ($isWeekend): ?>
            <!-- Jika Sabtu/Minggu, tampilkan strip atau checkbox terkunci -->
            <span class="text-muted" style="font-size: 10px;">-</span>
        <?php else: ?>
            <input type="checkbox" 
                   class="chk-box"
                   name="yaumiyah[<?= $tgl ?>][<?= $ind ?>]" 
                   value="1" 
                   <?= $isChecked ? 'checked' : '' ?>>
        <?php endif; ?>
    </td>
<?php endforeach; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                    
                    <div class="p-3 bg-light border-top text-right">
                        <button type="submit" class="btn btn-success font-weight-bold px-4">
                            <i class="fas fa-save mr-1"></i> Simpan Jurnal Bulan Ini
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>