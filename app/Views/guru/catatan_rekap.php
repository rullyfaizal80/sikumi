<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Rekap Catatan Siswa</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Tambahkan jQuery dan Bootstrap JS untuk Modal & AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1200px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;">📊 REKAPITULASI CATATAN SISWA</h3>
                <p class="text-muted small mb-0">Melihat daftar seluruh riwayat catatan anekdot dan prestasi di kelas ini.</p>
            </div>
            <div>
                <button onclick="window.history.back()" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </button>
            </div>
        </div>

        <!-- Tabel Catatan Anekdot -->
        <div class="mb-4">
            <h5 class="font-weight-bold" style="color: #ffc107;"><i class="fas fa-edit mr-2"></i> Daftar Catatan Anekdot</h5>
        </div>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-warning text-dark">
                        <tr>
                            <th width="15%" class="text-center">Tanggal</th>
                            <th width="20%">Nama Siswa</th>
                            <th>Catatan Kejadian</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($rekapAnekdot)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan anekdot di kelas ini.</td></tr>
                        <?php else: foreach($rekapAnekdot as $a): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= date('d-m-Y', strtotime($a['tanggal'])) ?></td>
                                <td><strong><?= esc($a['name']) ?></strong></td>
                                <td><?= nl2br(esc($a['kejadian'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white btn-edit-anekdot" 
                                            data-id="<?= $a['id'] ?>" 
                                            data-tanggal="<?= $a['tanggal'] ?>" 
                                            data-kejadian="<?= esc($a['kejadian']) ?>" 
                                            data-nama="<?= esc($a['name']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Penambahan atribut data-url di sini -->
                                    <button class="btn btn-sm btn-danger btn-hapus-anekdot" 
                                            data-id="<?= $a['id'] ?>"
                                            data-url="<?= base_url('catatansiswa/hapusAnekdot') ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Catatan Prestasi -->
        <div class="mb-4">
            <h5 class="font-weight-bold text-success"><i class="fas fa-trophy mr-2"></i> Daftar Catatan Prestasi</h5>
        </div>
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="bg-success text-white">
                        <tr>
                            <th width="20%">Nama Siswa</th>
                            <th width="25%">Catatan Prestasi</th>
                            <th>Keterangan</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($rekapPrestasi)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada catatan prestasi di kelas ini.</td></tr>
                        <?php else: foreach($rekapPrestasi as $p): ?>
                            <tr>
                                <td><strong><?= esc($p['name']) ?></strong></td>
                                <td class="font-weight-bold text-success"><?= esc($p['nama_prestasi']) ?></td>
                                <td><?= nl2br(esc($p['keterangan'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info text-white btn-edit-prestasi" 
                                            data-id="<?= $p['id'] ?>" 
                                            data-nama_prestasi="<?= esc($p['nama_prestasi']) ?>" 
                                            data-keterangan="<?= esc($p['keterangan']) ?>" 
                                            data-nama="<?= esc($p['name']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Penambahan atribut data-url di sini -->
                                    <button class="btn btn-sm btn-danger btn-hapus-prestasi" 
                                            data-id="<?= $p['id'] ?>"
                                            data-url="<?= base_url('catatansiswa/hapusPrestasi') ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================= MODAL EDIT ANEKDOT ========================= -->
    <div class="modal fade" id="modalEditAnekdot" tabindex="-1">
        <div class="modal-dialog">
            <form id="formEditAnekdot" class="modal-content" data-url="<?= base_url('catatansiswa/updateAnekdot') ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold text-dark">Edit Catatan Anekdot</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Siswa: <strong id="editNamaAnekdot"></strong></p>
                    <input type="hidden" name="id" id="editIdAnekdot">
                    
                    <div class="form-group">
                        <label>Hari & Tanggal</label>
                        <input type="date" name="tanggal" id="editTanggalAnekdot" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan Kejadian</label>
                        <textarea name="kejadian" id="editKejadianAnekdot" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Update Anekdot</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================= MODAL EDIT PRESTASI ========================= -->
    <div class="modal fade" id="modalEditPrestasi" tabindex="-1">
        <div class="modal-dialog">
            <form id="formEditPrestasi" class="modal-content" data-url="<?= base_url('catatansiswa/updatePrestasi') ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold">Edit Catatan Prestasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Siswa: <strong id="editNamaPrestasi"></strong></p>
                    <input type="hidden" name="id" id="editIdPrestasi">
                    
                    <div class="form-group">
                        <label>Catatan Prestasi</label>
                        <input type="text" name="nama_prestasi" id="editNamaLomba" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="editKeteranganPrestasi" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Update Prestasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Javascript untuk Hapus & Edit via AJAX -->
    <script>
        $(document).ready(function() {

            // --- ANEKDOT ---
            $('.btn-edit-anekdot').on('click', function() {
                $('#editIdAnekdot').val($(this).data('id'));
                $('#editTanggalAnekdot').val($(this).data('tanggal'));
                $('#editKejadianAnekdot').val($(this).data('kejadian'));
                $('#editNamaAnekdot').text($(this).data('nama'));
                $('#modalEditAnekdot').modal('show');
            });

            $('#formEditAnekdot').on('submit', function(e) {
                e.preventDefault();
                let urlPost = $(this).data('url');
                $.post(urlPost, $(this).serialize(), function(response) {
                    alert(response.message);
                    location.reload(); 
                });
            });

            $('.btn-hapus-anekdot').on('click', function() {
                if(confirm('Yakin ingin menghapus catatan anekdot ini?')) {
                    // Ambil URL dari atribut HTML untuk menghindari syntax error di editor
                    let urlPost = $(this).data('url');
                    let recordId = $(this).data('id');
                    
                    $.post(urlPost, { id: recordId }, function(response) {
                        alert(response.message);
                        location.reload();
                    });
                }
            });

            // --- PRESTASI ---
            $('.btn-edit-prestasi').on('click', function() {
                $('#editIdPrestasi').val($(this).data('id'));
                $('#editNamaLomba').val($(this).data('nama_prestasi'));
                $('#editKeteranganPrestasi').val($(this).data('keterangan'));
                $('#editNamaPrestasi').text($(this).data('nama'));
                $('#modalEditPrestasi').modal('show');
            });

            $('#formEditPrestasi').on('submit', function(e) {
                e.preventDefault();
                let urlPost = $(this).data('url');
                $.post(urlPost, $(this).serialize(), function(response) {
                    alert(response.message);
                    location.reload(); 
                });
            });

            $('.btn-hapus-prestasi').on('click', function() {
                if(confirm('Yakin ingin menghapus catatan prestasi ini?')) {
                    // Ambil URL dari atribut HTML untuk menghindari syntax error di editor
                    let urlPost = $(this).data('url');
                    let recordId = $(this).data('id');
                    
                    $.post(urlPost, { id: recordId }, function(response) {
                        alert(response.message);
                        location.reload();
                    });
                }
            });

        });
    </script>
</body>
</html>