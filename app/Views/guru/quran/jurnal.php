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
            background-color: #cce5ff; 
            color: #000; 
            text-align: center; 
            vertical-align: middle !important; 
            font-size: 13px;
            font-weight: bold;
            border: 1px solid #a6b5cc !important;
        }
        .table-jurnal td { 
            font-size: 13px; 
            vertical-align: middle !important;
            border: 1px solid #dee2e6 !important;
        }
        .table-jurnal tbody tr:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-primary font-weight-bold"><i class="fas fa-book-open mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small">Catatan harian kegiatan pembelajaran Al-Qur'an.</p>
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
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- FILTER & TOMBOL TAMBAH -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <form action="<?= base_url('guru/quran/jurnal/'.$kelompok['id']) ?>" method="GET" class="d-flex align-items-center m-0">
                    <label class="font-weight-bold mr-2 mb-0">Periode:</label>
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

                <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" onclick="openJurnalModal()">
                    <i class="fas fa-plus mr-1"></i> Isi Jurnal Baru
                </button>
            </div>
        </div>

        <!-- TABEL JURNAL -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-jurnal mb-0">
                    <thead>
                        <tr>
                            <th width="15%">HARI / TANGGAL</th>
                            <th width="30%">KEGIATAN PEMBELAJARAN</th>
                            <th width="20%">KENDALA</th>
                            <th width="20%">TINDAK LANJUT</th>
                            <th width="15%">MURID TIDAK HADIR</th>
                            <th width="5%"><i class="fas fa-cog"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hariIndo = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
                        
                        foreach ($jurnalList as $row): 
                            $timestamp = strtotime($row['tanggal']);
                            $namaHari = $hariIndo[date('l', $timestamp)];
                            $tglFormat = date('d/m/Y', $timestamp);
                        ?>
                        <tr>
                            <td class="text-center font-weight-bold text-nowrap">
                                <?= $namaHari ?><br> <?= $tglFormat ?>
                            </td>
                            <td><?= nl2br(esc($row['kegiatan'])) ?></td>
                            <td><?= nl2br(esc($row['kendala'])) ?></td>
                            <td><?= nl2br(esc($row['tindak_lanjut'])) ?></td>
                            <td><?= nl2br(esc($row['murid_tidak_hadir'])) ?></td>
                            <!-- GANTI BAGIAN TD INI -->
                            <td class="text-center text-nowrap">
                                <!-- Tombol Edit -->
                                <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2 mr-1" 
                                    data-tanggal="<?= esc($row['tanggal']) ?>"
                                    data-kegiatan="<?= esc($row['kegiatan']) ?>"
                                    data-kendala="<?= esc($row['kendala']) ?>"
                                    data-tindaklanjut="<?= esc($row['tindak_lanjut']) ?>"
                                    data-absen="<?= esc($row['murid_tidak_hadir']) ?>"
                                    onclick="editJurnal(this)" title="Edit Jurnal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Tombol Hapus -->
                                <a href="<?= base_url('guru/quran/jurnal/delete/' . $row['id']) ?>" 
                                   class="btn btn-sm btn-danger py-1 px-2" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus catatan jurnal tanggal <?= $tglFormat ?>? Data yang dihapus tidak dapat dikembalikan.');" 
                                   title="Hapus Jurnal">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(empty($jurnalList)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada catatan jurnal pada bulan ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL INPUT/EDIT JURNAL -->
    <div class="modal fade" id="modalJurnal" tabindex="-1" aria-labelledby="modalJurnalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="modalJurnalLabel">Form Jurnal</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="<?= base_url('guru/quran/jurnal/save') ?>" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="group_id" value="<?= $kelompok['id'] ?>">
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Tanggal</label>
                            <div class="col-sm-5">
                                <input type="date" name="tanggal" id="input_tanggal" class="form-control form-control-sm" required>
                                <small class="text-muted">Otomatis menimpa/update jika tanggal sudah ada.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Kegiatan Pembelajaran</label>
                            <textarea name="kegiatan" id="input_kegiatan" class="form-control form-control-sm" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-danger">Kendala (Opsional)</label>
                                <textarea name="kendala" id="input_kendala" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold text-success">Tindak Lanjut (Opsional)</label>
                                <textarea name="tindak_lanjut" id="input_tindak_lanjut" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- GANTI BAGIAN INPUT MURID TIDAK HADIR INI -->
<div class="form-group mb-0">
    <label class="font-weight-bold text-warning">Murid Tidak Hadir (Opsional)</label>
    
    <!-- Dropdown untuk memilih murid -->
    <select id="pilih_absen" class="form-control form-control-sm mb-2" onchange="tambahAbsen()">
        <option value="">-- Klik untuk memilih murid --</option>
        <?php foreach($daftarSiswa as $siswa): ?>
            <option value="<?= esc($siswa['username']) ?>"><?= esc($siswa['username']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Textarea untuk menampung nama (Ini yang akan disimpan ke database) -->
    <textarea name="murid_tidak_hadir" id="input_absen" class="form-control form-control-sm" rows="2" placeholder="Daftar murid tidak hadir akan muncul di sini..."></textarea>
    <small class="text-muted">Pilih nama dari dropdown di atas. Anda juga bebas mengetik manual atau menambah keterangan (Misal: Sakit/Izin) di kotak ini.</small>
</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT KENDALI MODAL -->
<!-- SCRIPT KENDALI MODAL -->
<script>
    // Fungsi untuk memindahkan nama dari dropdown ke textarea
    function tambahAbsen() {
        let select = document.getElementById('pilih_absen');
        let textArea = document.getElementById('input_absen');
        let nama = select.value;

        if (nama !== "") {
            // Cek apakah nama sudah ada di textarea untuk mencegah klik ganda tak sengaja
            if (!textArea.value.includes(nama)) {
                if (textArea.value.trim() === "") {
                    textArea.value = nama;
                } else {
                    textArea.value += ", " + nama;
                }
            }
            // Kembalikan dropdown ke pilihan default setelah diklik
            select.value = "";
        }
    }

    function openJurnalModal() {
        document.getElementById('modalJurnalLabel').innerHTML = '<i class="fas fa-plus-circle mr-2"></i> Tambah Jurnal Baru';
        
        document.getElementById('input_tanggal').value = "<?= date('Y-m-d') ?>";
        document.getElementById('input_kegiatan').value = '';
        document.getElementById('input_kendala').value = '';
        document.getElementById('input_tindak_lanjut').value = '';
        document.getElementById('input_tanggal').removeAttribute('readonly'); 
        
        // Kosongkan textarea absen dan reset dropdown
        document.getElementById('input_absen').value = '';
        document.getElementById('pilih_absen').value = '';
        
        $('#modalJurnal').modal('show');
    }

    function editJurnal(btnElement) {
        let tanggal = btnElement.getAttribute('data-tanggal');
        let kegiatan = btnElement.getAttribute('data-kegiatan');
        let kendala = btnElement.getAttribute('data-kendala');
        let tindakLanjut = btnElement.getAttribute('data-tindaklanjut');
        let absen = btnElement.getAttribute('data-absen'); 

        document.getElementById('modalJurnalLabel').innerHTML = '<i class="fas fa-edit mr-2"></i> Edit Jurnal Tanggal: ' + tanggal;
        document.getElementById('input_tanggal').value = tanggal;
        document.getElementById('input_kegiatan').value = kegiatan;
        document.getElementById('input_kendala').value = kendala;
        document.getElementById('input_tindak_lanjut').value = tindakLanjut;
        
        // Kunci tanggal
        document.getElementById('input_tanggal').setAttribute('readonly', 'true');
        
        // Isi textarea absen dengan data dari database dan reset dropdown
        document.getElementById('input_absen').value = absen;
        document.getElementById('pilih_absen').value = '';
        
        $('#modalJurnal').modal('show');
    }
</script>
</body>
</html>