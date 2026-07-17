<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Sumatif - Guru</title>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- JS Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .table-input-nilai input {
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .table-input-nilai input:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .locked-input {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 1300px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #4e73df; font-weight: 700;">
                    <i class="fas fa-edit mr-2"></i> PENILAIAN SUMATIF
                </h3>
                <p class="text-muted small mb-0">Silakan pilih kelas dan mata pelajaran untuk menginput nilai siswa per bulan.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Filter Data -->
        <div class="card shadow-sm border-0 border-left-primary mb-4" style="border-left: 4px solid #4e73df;">
            <div class="card-body">
                <form action="" method="GET" id="filterForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-md-0 mb-3">
                                <label class="font-weight-bold text-secondary">Pilih Kelas / Rombel</label>
                                <select name="rombel_id" class="form-control bg-light" onchange="document.getElementById('filterForm').submit()">
                                    <?php if (!empty($daftarRombel)) : ?>
                                        <?php foreach ($daftarRombel as $rombel): ?>
                                            <?php $isSelected = ($rombel['id'] == $selectedRombelId) ? 'selected' : ''; ?>
                                            <option value="<?= esc($rombel['id']) ?>" <?= $isSelected ?>>
                                                <?= esc($rombel['class_name'] . ' - ' . $rombel['rombel_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold text-secondary">Pilih Mata Pelajaran</label>
                                <select name="mapel_id" class="form-control bg-light" onchange="document.getElementById('filterForm').submit()">
                                    <?php if (!empty($daftarMapel)) : ?>
                                        <?php foreach ($daftarMapel as $mapel): ?>
                                            <?php 
                                                $isSelectedMapel = ($mapel['id'] == $selectedMapelId) ? 'selected' : ''; 
                                                $labelGabungan = ($mapel['type'] === 'gabungan') ? '(Gabungan)' : '';
                                            ?>
                                            <option value="<?= esc($mapel['id']) ?>" <?= $isSelectedMapel ?>>
                                                <?= esc($mapel['subject_name']) ?> <?= esc($labelGabungan) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daftar Siswa dan Tabel Input Nilai -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h5 class="m-0 font-weight-bold" style="color: #333;">Daftar Siswa & Input Nilai Bulanan</h5>
                <button type="button" class="btn btn-success btn-sm font-weight-bold shadow-sm" onclick="simpanDataNilai()">
                    <i class="fas fa-save mr-1"></i> Simpan Nilai
                </button>
            </div>
            
            <div class="card-body p-0 table-responsive">
                <form id="formNilaiSumatif" data-url="<?= base_url('guru/nilai-sumatif/simpan') ?>">
                    <input type="hidden" name="rombel_id" value="<?= esc($selectedRombelId ?? '') ?>">
                    <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId ?? '') ?>">
                    <input type="hidden" name="academic_year_id" value="<?= esc($tahunAktifId ?? 0) ?>">

                    <table class="table table-striped table-hover align-middle mb-0 table-input-nilai">
                        <thead class="table-dark text-center">
                            <tr>
                                <th style="width: 50px; vertical-align: middle;">No</th>
                                <th style="min-width: 250px; vertical-align: middle;" class="text-left">Nama Siswa</th>
                                
                                <!-- Generate Header Bulan -->
                                <?php if (!empty($statusBulan)): ?>
                                    <?php foreach ($statusBulan as $bulan): ?>
                                        <th style="min-width: 100px; padding: 10px 5px;">
                                            <?= esc($bulan['nama_bulan']) ?>
                                            <?php if ($bulan['is_locked']): ?>
                                                <div class="text-warning mt-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-lock"></i> Terkunci
                                                </div>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($siswaData)): ?>
                                <tr>
                                    <?php $colSpan = 2 + (empty($statusBulan) ? 0 : count($statusBulan)); ?>
                                    <td colspan="<?= $colSpan ?>" class="text-center text-muted py-5">
                                        <i class="fas fa-info-circle mr-2"></i>Data siswa belum tersedia atau rombel tidak dipilih.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; ?>
                                <?php foreach ($siswaData as $siswa): ?>
                                    <tr>
                                        <td class="text-center font-weight-bold align-middle"><?= $no++ ?></td>
                                        <td class="align-middle text-uppercase"><strong><?= esc($siswa['name']) ?></strong></td>
                                        
                                        <!-- Generate Input Bulan per Siswa -->
                                        <?php if (!empty($statusBulan)): ?>
                                            <?php foreach ($statusBulan as $bulan): ?>
                                                <?php 
                                                    $nilaiRaw = $siswa['nilai'][$bulan['id_bulan']] ?? ''; 
                                                    $nilaiSaatIni = ($nilaiRaw !== '') ? (float)$nilaiRaw : ''; 
                                                    
                                                    $isLockedClass = $bulan['is_locked'] ? 'locked-input' : '';
                                                    $readonlyAttr = $bulan['is_locked'] ? 'readonly tabindex="-1"' : '';
                                                ?>
                                                <td class="align-middle">
                                                    <!-- Ditambahkan class "nilai-input" untuk divalidasi JS -->
                                                    <input type="number" step="0.01" min="0" max="100" 
                                                           class="form-control form-control-sm text-center nilai-input <?= $isLockedClass ?>" 
                                                           name="data_nilai[<?= esc($siswa['student_id']) ?>][<?= esc($bulan['id_bulan']) ?>]" 
                                                           value="<?= esc($nilaiSaatIni) ?>"
                                                           <?= $readonlyAttr ?>
                                                           placeholder="-">
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>

    </div>

    <!-- Script Proses AJAX & Validasi Ketat -->
    <script>
    $(document).ready(function() {
        // Intersepsi ketikan user agar patuh aturan max 100 & 2 desimal
        $(document).on('input', '.nilai-input', function() {
            let val = $(this).val();
            
            if (val !== '') {
                // 1. Validasi Maksimal 100 dan Minimal 0
                if (parseFloat(val) > 100) {
                    $(this).val(100);
                    val = "100";
                }
                if (parseFloat(val) < 0) {
                    $(this).val(0);
                    val = "0";
                }
                
                // 2. Validasi Maksimal 2 Angka Di Belakang Koma
                if (val.indexOf('.') !== -1) {
                    let parts = val.split('.');
                    if (parts[1].length > 2) {
                        // Potong paksa jika user mengetik angka desimal ke-3
                        $(this).val(parts[0] + '.' + parts[1].substring(0, 2));
                    }
                }
            }
        });
    });

    function simpanDataNilai() {
        // Cek validasi HTML5 bawaan form sebelum kirim AJAX
        let formNative = document.getElementById('formNilaiSumatif');
        if (formNative && !formNative.reportValidity()) {
            return; // Berhenti jika ada input tidak valid
        }

        Swal.fire({
            title: 'Menyimpan Nilai...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        let formElement = $('#formNilaiSumatif');
        let formData = formElement.serialize();
        let targetUrl = formElement.attr('data-url') || '';

        $.ajax({
            url: targetUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: 'Gagal terhubung ke server. Silakan coba lagi nanti.'
                });
            }
        });
    }
    </script>
</body>
</html>