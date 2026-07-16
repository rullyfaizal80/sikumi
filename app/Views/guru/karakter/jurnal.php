<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .table-jurnal th { 
            background-color: #f4f6f9; color: #333; text-align: center; 
            vertical-align: middle !important; font-size: 13px; font-weight: bold; border: 1px solid #dee2e6 !important;
        }
        .table-jurnal td { font-size: 13px; vertical-align: middle !important; border: 1px solid #dee2e6 !important; }
        .header-putra { background-color: #e7f1ff !important; color: #004085 !important; }
        .header-putri { background-color: #fff0f3 !important; color: #721c24 !important; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-primary font-weight-bold"><i class="fas fa-book mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Catatan rekam kegiatan mingguan Keputraan dan Keputrian.</p>
            </div>
            <div>
                 <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold ml-2">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success shadow-sm alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> <?= session()->getFlashdata('success') ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- FILTER & TOMBOL TAMBAH UTAMA -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap">
                <form action="<?= base_url('guru/jurnal-karakter') ?>" method="GET" class="d-flex align-items-center m-0">
                    <label class="font-weight-bold mr-2 mb-0">Periode Jurnal:</label>
                    <select name="bulan" class="form-control form-control-sm mr-2" style="width: 130px;">
                        <?php 
                            $namaBulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                            foreach ($namaBulan as $angka => $nama): 
                        ?>
                            <option value="<?= $angka ?>" <?= ($angka == $bulan) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" class="form-control form-control-sm mr-3" style="width: 100px;">
                        <?php for($t = date('Y') - 2; $t <= date('Y') + 1; $t++): ?>
                            <option value="<?= $t ?>" <?= ($t == $tahun) ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm font-weight-bold px-3">
                        <i class="fas fa-search mr-1"></i> Tampilkan
                    </button>
                </form>

                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm mr-2" onclick="openJurnalModal('keputraan')">
                        <i class="fas fa-plus mr-1"></i> Jurnal Keputraan
                    </button>
                    <button type="button" class="btn btn-danger btn-sm font-weight-bold shadow-sm" onclick="openJurnalModal('keputrian')">
                        <i class="fas fa-plus mr-1"></i> Jurnal Keputrian
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TABEL 1: JURNAL KEPUTRAAN -->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 text-primary font-weight-bold"><i class="fas fa-male mr-2"></i> DATA JURNAL KEPUTRAAN</h5>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-jurnal mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="header-putra">NO</th>
                            <th width="15%" class="header-putra">HARI / TANGGAL</th>
                            <th width="15%" class="header-putra">WAKTU & TEMPAT</th>
                            <th width="20%" class="header-putra">MATERI</th>
                            <th width="15%" class="header-putra">PEMATERI</th>
                            <th width="15%" class="header-putra">KENDALA</th>
                            <th width="10%" class="header-putra">TINDAK LANJUT</th>
                            <th width="10%" class="header-putra"><i class="fas fa-cog"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hariIndo = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
                        
                        $noPutra = 1;
                        foreach ($jurnalKeputraan as $row): 
                            $timestamp = strtotime($row['tanggal']);
                            $namaHari = $hariIndo[date('l', $timestamp)];
                            $tglFormat = date('d/m/Y', $timestamp);
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?= $noPutra++ ?></td>
                            <td class="text-center font-weight-bold text-nowrap"><?= $namaHari ?><br><?= $tglFormat ?></td>
                            <td class="text-center">
                                <?= esc($row['waktu']) ?><br>
                                <span class="text-muted small"><i class="fas fa-map-marker-alt"></i> <?= esc($row['tempat']) ?></span>
                            </td>
                            <td><?= nl2br(esc($row['materi'])) ?></td>
                            <td class="text-center font-weight-bold"><?= esc($row['pemateri']) ?></td>
                            <td><?= nl2br(esc($row['kendala'])) ?></td>
                            <td><?= nl2br(esc($row['tindak_lanjut'])) ?></td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2 mr-1" 
                                    data-id="<?= $row['id'] ?>"
                                    data-jenis="keputraan"
                                    data-tanggal="<?= esc($row['tanggal']) ?>"
                                    data-waktu="<?= esc($row['waktu']) ?>"
                                    data-tempat="<?= esc($row['tempat']) ?>"
                                    data-materi="<?= esc($row['materi']) ?>"
                                    data-pemateri="<?= esc($row['pemateri']) ?>"
                                    data-kendala="<?= esc($row['kendala']) ?>"
                                    data-tindaklanjut="<?= esc($row['tindak_lanjut']) ?>"
                                    onclick="editJurnal(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="<?= base_url('guru/jurnal-karakter/delete/keputraan/' . $row['id']) ?>" 
                                   class="btn btn-sm btn-danger py-1 px-2" 
                                   onclick="return confirm('Hapus catatan Jurnal Keputraan ini?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($jurnalKeputraan)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada catatan jurnal Keputraan pada bulan ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TABEL 2: JURNAL KEPUTRIAN -->
        <!-- ========================================== -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 text-danger font-weight-bold"><i class="fas fa-female mr-2"></i> DATA JURNAL KEPUTRIAN</h5>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-jurnal mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="header-putri">NO</th>
                            <th width="15%" class="header-putri">HARI / TANGGAL</th>
                            <th width="15%" class="header-putri">WAKTU & TEMPAT</th>
                            <th width="20%" class="header-putri">MATERI</th>
                            <th width="15%" class="header-putri">PEMATERI</th>
                            <th width="15%" class="header-putri">KENDALA</th>
                            <th width="10%" class="header-putri">TINDAK LANJUT</th>
                            <th width="10%" class="header-putri"><i class="fas fa-cog"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $noPutri = 1;
                        foreach ($jurnalKeputrian as $row): 
                            $timestamp = strtotime($row['tanggal']);
                            $namaHari = $hariIndo[date('l', $timestamp)];
                            $tglFormat = date('d/m/Y', $timestamp);
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold"><?= $noPutri++ ?></td>
                            <td class="text-center font-weight-bold text-nowrap"><?= $namaHari ?><br><?= $tglFormat ?></td>
                            <td class="text-center">
                                <?= esc($row['waktu']) ?><br>
                                <span class="text-muted small"><i class="fas fa-map-marker-alt"></i> <?= esc($row['tempat']) ?></span>
                            </td>
                            <td><?= nl2br(esc($row['materi'])) ?></td>
                            <td class="text-center font-weight-bold"><?= esc($row['pemateri']) ?></td>
                            <td><?= nl2br(esc($row['kendala'])) ?></td>
                            <td><?= nl2br(esc($row['tindak_lanjut'])) ?></td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2 mr-1" 
                                    data-id="<?= $row['id'] ?>"
                                    data-jenis="keputrian"
                                    data-tanggal="<?= esc($row['tanggal']) ?>"
                                    data-waktu="<?= esc($row['waktu']) ?>"
                                    data-tempat="<?= esc($row['tempat']) ?>"
                                    data-materi="<?= esc($row['materi']) ?>"
                                    data-pemateri="<?= esc($row['pemateri']) ?>"
                                    data-kendala="<?= esc($row['kendala']) ?>"
                                    data-tindaklanjut="<?= esc($row['tindak_lanjut']) ?>"
                                    onclick="editJurnal(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="<?= base_url('guru/jurnal-karakter/delete/keputrian/' . $row['id']) ?>" 
                                   class="btn btn-sm btn-danger py-1 px-2" 
                                   onclick="return confirm('Hapus catatan Jurnal Keputrian ini?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($jurnalKeputrian)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada catatan jurnal Keputrian pada bulan ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL FORM DIALOG (VERSI SPASI PROPORSIAL) -->
    <div class="modal fade" id="modalJurnal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white py-3" id="modalHeaderColor">
                    <h5 class="modal-title font-weight-bold" id="modalJurnalLabel">
                        <i class="fas fa-plus-circle mr-2"></i> Form Jurnal
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="<?= base_url('guru/jurnal-karakter/save') ?>" method="POST">
                    <!-- px-4 py-4 memberikan padding dalam modal yang lebih luas dan lega -->
                    <div class="modal-body bg-white px-4 py-4"> 
                        <input type="hidden" name="id" id="input_id" value="">
                        
                        <!-- 1. Pilihan Jenis Jurnal -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="font-weight-bold mb-2 d-block text-secondary">Jenis Jurnal</label>
                                <div class="custom-control custom-radio custom-control-inline mr-4">
                                    <input type="radio" id="radio_keputraan" name="jenis" value="keputraan" class="custom-control-input">
                                    <label class="custom-control-label font-weight-bold text-primary" for="radio_keputraan" style="cursor:pointer;">Keputraan</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="radio_keputrian" name="jenis" value="keputrian" class="custom-control-input">
                                    <label class="custom-control-label font-weight-bold text-danger" for="radio_keputrian" style="cursor:pointer;">Keputrian</label>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Baris Tanggal & Waktu -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="font-weight-bold mb-2 text-secondary">Tanggal Kegiatan</label>
                                <input type="date" name="tanggal" id="input_tanggal" class="form-control custom-input" required>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="font-weight-bold mb-2 text-secondary">Waktu / Sesi</label>
                                <input type="text" name="waktu" id="input_waktu" class="form-control custom-input" placeholder="Contoh: 13.00 - 14.30 WIB">
                            </div>
                        </div>

                        <!-- 3. Baris Tempat & Pemateri -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="font-weight-bold mb-2 text-secondary">Tempat Pelaksanaan</label>
                                <input type="text" name="tempat" id="input_tempat" class="form-control custom-input" placeholder="Contoh: Masjid Sekolah / Aula">
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="font-weight-bold mb-2 text-secondary">Pemateri</label>
                                <input list="listGuru" name="pemateri" id="input_pemateri" class="form-control custom-input" placeholder="Ketik atau pilih nama guru..." autocomplete="off" required>
                                <datalist id="listGuru">
                                    <?php foreach($daftarGuru as $guru): ?>
                                        <option value="<?= esc($guru['username']) ?>"></option> <!--[cite: 1] -->
                                    <?php endforeach; ?>
                                </datalist>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    <i class="fas fa-info-circle mr-1"></i> Klik untuk list internal guru, atau ketik manual jika dari luar.
                                </small>
                            </div>
                        </div>

                        <!-- 4. Baris Materi -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="font-weight-bold mb-2 text-secondary">Materi yang Disampaikan</label>
                                <textarea name="materi" id="input_materi" class="form-control custom-input" rows="3" placeholder="Tuliskan ringkasan materi atau poin-poin penting pembahasan..." required></textarea>
                            </div>
                        </div>

                        <!-- 5. Baris Kendala & Tindak Lanjut -->
                        <div class="row">
                            <div class="col-md-6">
                                <label class="font-weight-bold text-danger mb-2">Kendala Lapangan (Opsional)</label>
                                <textarea name="kendala" id="input_kendala" class="form-control custom-input border-danger-light" rows="3" placeholder="Tuliskan kendala atau catatan evaluasi jika ada..."></textarea>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label class="font-weight-bold text-success mb-2">Tindak Lanjut (Opsional)</label>
                                <textarea name="tindak_lanjut" id="input_tindak_lanjut" class="form-control custom-input border-success-light" rows="3" placeholder="Rencana solusi atau tindak lanjut untuk sesi berikutnya..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-3 px-4">
                        <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm"><i class="fas fa-save mr-1"></i> Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT LOGIC -->
    <script>
        function openJurnalModal(targetJenis) {
            document.getElementById('modalJurnalLabel').innerHTML = '<i class="fas fa-plus-circle mr-2"></i> Tambah Jurnal Baru (' + targetJenis.toUpperCase() + ')';
            document.getElementById('input_id').value = '';
            document.getElementById('input_tanggal').value = "<?= date('Y-m-d') ?>";
            document.getElementById('input_waktu').value = '';
            document.getElementById('input_tempat').value = '';
            document.getElementById('input_pemateri').value = '';
            document.getElementById('input_materi').value = '';
            document.getElementById('input_kendala').value = '';
            document.getElementById('input_tindak_lanjut').value = '';
            
            // Otomatis pilih radio button sesuai tombol tambah yang diklik
            document.getElementById('radio_' + targetJenis).checked = true;

            // Sesuaikan warna header modal agar match dengan tema tombol
            let header = document.getElementById('modalHeaderColor');
            if(targetJenis === 'keputrian') {
                header.className = "modal-header bg-danger text-white";
            } else {
                header.className = "modal-header bg-primary text-white";
            }
            
            $('#modalJurnal').modal('show');
        }

        function editJurnal(btnElement) {
            let id = btnElement.getAttribute('data-id');
            let jenis = btnElement.getAttribute('data-jenis');
            let tanggal = btnElement.getAttribute('data-tanggal');
            let waktu = btnElement.getAttribute('data-waktu');
            let tempat = btnElement.getAttribute('data-tempat');
            let materi = btnElement.getAttribute('data-materi');
            let pemateri = btnElement.getAttribute('data-pemateri');
            let kendala = btnElement.getAttribute('data-kendala');
            let tindakLanjut = btnElement.getAttribute('data-tindaklanjut');

            document.getElementById('modalJurnalLabel').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Jurnal ' + jenis.toUpperCase();
            document.getElementById('input_id').value = id;
            document.getElementById('input_tanggal').value = tanggal;
            document.getElementById('input_waktu').value = waktu;
            document.getElementById('input_tempat').value = tempat;
            document.getElementById('input_pemateri').value = pemateri;
            document.getElementById('input_materi').value = materi;
            document.getElementById('input_kendala').value = kendala;
            document.getElementById('input_tindak_lanjut').value = tindakLanjut;
            
            // Set radio button sesuai data jenis yang di-edit
            document.getElementById('radio_' + jenis).checked = true;

            // Sesuaikan warna header modal pas edit
            let header = document.getElementById('modalHeaderColor');
            if(jenis === 'keputrian') {
                header.className = "modal-header bg-danger text-white";
            } else {
                header.className = "modal-header bg-primary text-white";
            }
            
            $('#modalJurnal').modal('show');
        }
    </script>
</body>
</html>