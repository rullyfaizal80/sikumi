<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Rapor Berjalan - Panel Admin</title>
    
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CDN untuk Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f4f7f6;
            font-family: 'Open Sans', sans-serif;
            color: #333;
        }
        .filter-container {
            margin-top: 8vh;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        .card-header-custom {
            background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container filter-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Alert Pesan Error (Jika Belum Memilih Siswa) -->
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom">
                <div class="card-header card-header-custom d-flex align-items-center">
                    <i class="fas fa-book-open fs-4 me-3"></i>
                    <h5 class="mb-0 fw-bold">Pencarian Rapor Berjalan Siswa</h5>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <div class="mb-4 text-muted" style="font-size: 15px;">
                        Silakan tentukan tahun ajaran, semester, dan kelas terlebih dahulu untuk memunculkan daftar siswa.
                    </div>

                    <form action="<?= base_url('admin/rapor-berjalan/lihat') ?>" method="GET">
                        <div class="row g-4">
                            <!-- Pilihan Tahun Ajaran -->
                            <div class="col-md-6 col-lg-3">
                                <label for="tahun" class="form-label">Tahun (Awal Ajaran)</label>
                                <input type="number" id="tahun" name="tahun" class="form-control" value="<?= esc($tahun) ?>" required placeholder="Contoh: 2023">
                            </div>

                            <!-- Pilihan Semester -->
                            <div class="col-md-6 col-lg-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select id="semester" name="semester" class="form-select" required>
                                    <option value="ganjil">Ganjil</option>
                                    <option value="genap">Genap</option>
                                </select>
                            </div>

                            <!-- Pilihan Kelas -->
                            <div class="col-md-6 col-lg-3">
                                <label for="rombel_id" class="form-label">Kelas (Rombel)</label>
                                <select id="rombel_id" class="form-select" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php if (!empty($daftarRombel)): ?>
                                        <?php foreach ($daftarRombel as $rombel): ?>
                                            <option value="<?= $rombel['id'] ?>"><?= esc($rombel['rombel_name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Pilihan Siswa (Otomatis via AJAX) -->
                            <div class="col-md-6 col-lg-3">
                                <label for="student_id" class="form-label">Nama Siswa</label>
                                <select name="student_id" id="student_id" class="form-select" required>
                                    <option value="">-- Tunggu Kelas --</option>
                                </select>
                            </div>
                        </div>

                        <hr class="mt-5 mb-4 text-muted">

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= base_url('/') ?>" class="btn btn-light border text-secondary px-4">
                                <i class="fas fa-arrow-left me-2"></i> Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" style="background-color: #1976d2; border-color: #1976d2;">
                                <i class="fas fa-search me-2"></i> Tampilkan Rapor
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 JS Bundle (termasuk Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script AJAX Pemanggilan Siswa -->
<script>
$(document).ready(function() {
    $('#rombel_id').change(function() {
        var rombelId = $(this).val();
        var studentDropdown = $('#student_id');
        
        // Animasi loading pada teks dropdown
        studentDropdown.html('<option value="">Mencari siswa...</option>');
        
        if(rombelId !== '') {
            $.ajax({
                url: '<?= base_url("admin/rapor-berjalan/get-siswa") ?>',
                type: 'POST',
                data: { rombel_id: rombelId },
                dataType: 'json',
                success: function(response) {
                    studentDropdown.html('<option value="">-- Pilih Siswa --</option>');
                    
                    // Lakukan looping data dari response json
                    if(response.length > 0) {
                        $.each(response, function(index, siswa) {
                            studentDropdown.append('<option value="' + siswa.id + '">' + siswa.name + '</option>');
                        });
                    } else {
                        studentDropdown.html('<option value="">Siswa tidak ditemukan</option>');
                    }
                },
                error: function() {
                    studentDropdown.html('<option value="">Gagal memuat data</option>');
                }
            });
        } else {
            studentDropdown.html('<option value="">-- Tunggu Kelas --</option>');
        }
    });
});
</script>

</body>
</html>