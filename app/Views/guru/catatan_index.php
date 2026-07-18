<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Catatan Siswa</title>
    <!-- Sesuaikan path CSS dengan konfigurasi Anda -->
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
                <h3 class="mb-0" style="color: #17a2b8; font-weight: 700;">📝 CATATAN ANEKDOT & PRESTASI</h3>
                <p class="text-muted small mb-0">Pilih kelas untuk menginput atau melihat rekap catatan perkembangan siswa.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Filter Kelas -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="font-weight-bold">Pilih Kelas / Rombel</label>
                            <select name="rombel_id" class="form-control" onchange="this.form.submit()">
                                <?php foreach ($daftarRombel as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $r['id'] == $selectedRombelId ? 'selected' : '' ?>>
                                        <?= esc($r['rombel_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 text-right">
                            <a href="<?= base_url('catatansiswa/rekap?rombel_id=' . $selectedRombelId . '&academic_year_id=' . $tahunAktifId) ?>" class="btn btn-info font-weight-bold">
                                <i class="fas fa-print mr-1"></i> Lihat Rekap Kelas Ini
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Siswa -->
       <div class="card shadow-sm border-0">
            <!-- Tambahkan d-flex, justify-content-between, dan align-items-center di sini -->
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center w-100">
    <h5 class="mb-0" style="font-weight: 600;">Daftar Siswa</h5>
    <a href="<?= base_url('catatansiswa/rekapAll?academic_year_id=' . $tahunAktifId . '&semester=' . $semesterAktif) ?>" class="btn btn-primary btn-sm font-weight-bold ms-auto">
        <i class="fas fa-list-alt me-1"></i> Rekap Semua Kelas (1 Semester)
    </a>
</div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th class="text-center" style="width: 350px;">Aksi (Input Catatan)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($siswaData)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Data siswa belum tersedia di kelas ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no=1; foreach($siswaData as $siswa): ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                <td>
                                    <strong><?= esc($siswa['name']) ?></strong>
                                </td>
                                <td class="text-center">
                                    <!-- Penggunaan data-attribute (Lebih rapi dan bebas error di VS Code) -->
                                    <button type="button" class="btn btn-sm btn-warning text-dark font-weight-bold btn-anekdot mr-2" 
                                            data-id="<?= $siswa['student_id'] ?>" 
                                            data-nama="<?= esc($siswa['name']) ?>">
                                        <i class="fas fa-edit"></i> Anekdot 
                                        <span class="badge badge-light ml-1"><?= $siswa['jml_anekdot'] ?></span>
                                    </button>
                                    
                                    <button type="button" class="btn btn-sm btn-success text-white font-weight-bold btn-prestasi" 
                                            data-id="<?= $siswa['student_id'] ?>" 
                                            data-nama="<?= esc($siswa['name']) ?>">
                                        <i class="fas fa-trophy"></i> Prestasi 
                                        <span class="badge badge-light ml-1"><?= $siswa['jml_prestasi'] ?></span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================= MODAL ANEKDOT ========================= -->
    <div class="modal fade" id="modalAnekdot" tabindex="-1">
        <div class="modal-dialog">
            <form id="formAnekdot" class="modal-content" data-url="<?= base_url('catatansiswa/simpanAnekdot') ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold text-dark">Input Catatan Anekdot</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Siswa: <strong id="namaSiswaAnekdot"></strong></p>
                    <input type="hidden" name="student_id" id="anekdot_student_id">
                    <input type="hidden" name="rombel_id" value="<?= $selectedRombelId ?>">
                    <input type="hidden" name="academic_year_id" value="<?= $tahunAktifId ?>">
                    
                    <div class="form-group">
                        <label>Hari & Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Catatan Kejadian</label>
                        <textarea name="kejadian" class="form-control" rows="4" placeholder="Tulis kejadian detail di sini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Simpan Anekdot</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================= MODAL PRESTASI ========================= -->
    <div class="modal fade" id="modalPrestasi" tabindex="-1">
        <div class="modal-dialog">
            <form id="formPrestasi" class="modal-content" data-url="<?= base_url('catatansiswa/simpanPrestasi') ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold">Input Catatan Prestasi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Siswa: <strong id="namaSiswaPrestasi"></strong></p>
                    <input type="hidden" name="student_id" id="prestasi_student_id">
                    <input type="hidden" name="rombel_id" value="<?= $selectedRombelId ?>">
                    <input type="hidden" name="academic_year_id" value="<?= $tahunAktifId ?>">
                    
                    <div class="form-group">
                        <label>Catatan Prestasi (Nama Lomba/Penghargaan)</label>
                        <input type="text" name="nama_prestasi" class="form-control" placeholder="Contoh: Juara 1 Lomba Pidato" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Tingkat Kabupaten/Provinsi, Keterangan tambahan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">Simpan Prestasi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Javascript Terpisah (Ramah VS Code) -->
    <script>
        $(document).ready(function() {
            // Event Listener untuk Tombol Anekdot
            $('.btn-anekdot').on('click', function() {
                let studentId = $(this).data('id');
                let studentName = $(this).data('nama');
                
                $('#anekdot_student_id').val(studentId);
                $('#namaSiswaAnekdot').text(studentName);
                $('#formAnekdot').trigger("reset");
                $('#modalAnekdot').modal('show');
            });

            // Event Listener untuk Tombol Prestasi
            $('.btn-prestasi').on('click', function() {
                let studentId = $(this).data('id');
                let studentName = $(this).data('nama');
                
                $('#prestasi_student_id').val(studentId);
                $('#namaSiswaPrestasi').text(studentName);
                $('#formPrestasi').trigger("reset");
                $('#modalPrestasi').modal('show');
            });

            // AJAX Submit Anekdot
            $('#formAnekdot').on('submit', function(e) {
                e.preventDefault();
                let postUrl = $(this).data('url');
                $.post(postUrl, $(this).serialize(), function(response) {
                    alert(response.message);
                    location.reload(); 
                });
            });

            // AJAX Submit Prestasi
            $('#formPrestasi').on('submit', function(e) {
                e.preventDefault();
                let postUrl = $(this).data('url');
                $.post(postUrl, $(this).serialize(), function(response) {
                    alert(response.message);
                    location.reload(); 
                });
            });
        });
    </script>
</body>
</html>