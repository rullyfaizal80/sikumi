<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* CSS Khusus untuk Scroll & Freeze Column */
        .table-scrollable {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 70vh; /* Tinggi maksimal agar header vertikal bisa di-scroll */
            position: relative;
        }
        
        .table-scrollable th, .table-scrollable td {
            white-space: nowrap;
            vertical-align: middle;
        }

        /* Freeze Header Atas */
        .table-scrollable thead th {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: #fff;
            z-index: 3;
            box-shadow: inset 0 -1px 0 #dee2e6;
        }

        /* Freeze Kolom 1 (No) & Kolom 2 (Nama) */
        .sticky-col-1 {
            position: sticky;
            left: 0;
            min-width: 40px;
            max-width: 40px;
            background-color: #f8f9fa !important;
            z-index: 2;
        }
        .sticky-col-2 {
            position: sticky;
            left: 40px; /* Lebar dari sticky-col-1 */
            min-width: 250px;
            max-width: 250px;
            background-color: #f8f9fa !important;
            border-right: 2px solid #6c757d;
            z-index: 2;
        }

        /* Supaya Header No & Nama tetap di atas dan kiri saat di scroll silang */
        thead .sticky-col-1, thead .sticky-col-2 {
            z-index: 4; 
            background-color: #343a40 !important;
        }

        /* Border pembatas per tanggal */
        .border-date-left { border-left: 2px solid #dee2e6 !important; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-primary font-weight-bold"><i class="fas fa-search mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Monitoring detail pengisian yaumiyah (Senin - Jumat) per siswa dalam satu bulan.</p>
            </div>
            <div>
                <a href="<?= base_url('guru/yaumiyah') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Pilih Kelas
    </a>
            </div>
        </div>

        <!-- Legenda Aspek 1-9 -->
        <div class="alert alert-info py-2 small mb-3 shadow-sm border-0">
            <strong><i class="fas fa-info-circle mr-1"></i> Legenda Aspek Yaumiyah:</strong>
            <div class="row mt-1 font-weight-bold text-dark">
                <div class="col-md-auto mr-3">1 = Dzuhur</div>
                <div class="col-md-auto mr-3">2 = Ashar</div>
                <div class="col-md-auto mr-3">3 = Ba'diah Dzuhur</div>
                <div class="col-md-auto mr-3">4 = Dhuha</div>
                <div class="col-md-auto mr-3">5 = Tahajud</div>
                <div class="col-md-auto mr-3">6 = Tilawah</div>
                <div class="col-md-auto mr-3">7 = Infaq</div>
                <div class="col-md-auto mr-3">8 = Shaum</div>
                <div class="col-md-auto">9 = Literasi</div>
            </div>
        </div>

        <!-- Filter Bulan & Tahun -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form action="<?= base_url('guru/yaumiyah/monitoring/'.$rombel['id']) ?>" method="GET" class="d-flex align-items-center">
                    <label class="font-weight-bold mr-3 mb-0"><i class="fas fa-calendar-alt mr-1"></i> Bulan:</label>
                    <select name="bulan" class="form-control form-control-sm mr-2" style="width: 150px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($angka == $bulan) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="font-weight-bold mx-3 mb-0">Tahun:</label>
                    <select name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;">
                        <?php for($t = date('Y') - 2; $t <= date('Y') + 1; $t++): ?>
                            <option value="<?= $t ?>" <?= ($t == $tahun) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-4">Tampilkan</button>
                </form>
            </div>
        </div>

        <!-- Tabel Matrix -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-scrollable bg-white">
                <table class="table table-sm table-hover table-bordered mb-0 text-center" style="font-size: 11px;">
                    <thead>
                        <!-- Baris 1 Header -->
                        <tr>
                            <th rowspan="2" class="sticky-col-1 text-center">No</th>
                            <th rowspan="2" class="sticky-col-2 text-left px-3">Nama Siswa</th>
                            <?php foreach ($hariAktif as $tgl): ?>
                                <th colspan="9" class="border-date-left bg-dark text-warning">Tgl <?= $tgl ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Baris 2 Header (Legenda 1-9) -->
                        <tr>
                            <?php foreach ($hariAktif as $tgl): ?>
                                <?php for ($i=1; $i<=9; $i++): ?>
                                    <th class="<?= $i==1 ? 'border-date-left' : '' ?> p-1" style="min-width:25px;" title="Tgl <?= $tgl ?> - Aspek <?= $i ?>"><?= $i ?></th>
                                <?php endfor; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($daftarSiswa as $siswa): ?>
                        <tr>
                            <td class="sticky-col-1 font-weight-bold"><?= $no++ ?></td>
                            <td class="sticky-col-2 text-left font-weight-bold text-dark px-3"><?= esc($siswa['username']) ?></td>
                            
                            <?php foreach ($hariAktif as $tgl): ?>
                                <?php for ($i=1; $i<=9; $i++): ?>
                                    <?php 
                                        $sId = $siswa['student_id'];
                                        // Cek apakah siswa punya data (sudah submit form) di tanggal tersebut
                                        $sudahMengisiForm = isset($yaumiyahData[$sId][$tgl]);
                                        // Cek apakah aspek tertentu diceklis (bernilai 1)
                                        $aspekTerisi = $sudahMengisiForm && $yaumiyahData[$sId][$tgl][$i] == 1;
                                    ?>
                                    <td class="p-1 <?= $i==1 ? 'border-date-left' : '' ?>">
                                        <?php if ($aspekTerisi): ?>
                                            <!-- Sudah mengisi form & mengerjakan amalan -->
                                            <i class="fas fa-check text-success"></i>
                                        <?php elseif ($sudahMengisiForm): ?>
                                            <!-- Sudah mengisi form TAPI tidak mengerjakan amalan -->
                                            <i class="fas fa-times text-danger"></i>
                                        <?php else: ?>
                                            <!-- Belum mengisi form sama sekali di hari tersebut -->
                                            <span class="text-black-50 font-weight-bold">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            <?php endforeach; ?>

                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($daftarSiswa)): ?>
                            <tr><td colspan="<?= (count($hariAktif) * 9) + 2 ?>" class="py-4">Belum ada data siswa.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>