<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - <?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .sticky-header th { position: sticky; top: 0; background: #e2e3e5; z-index: 10; }
    </style>
</head>
<body class="p-4 bg-light">
    <div class="container-fluid" style="max-width: 900px;">
        
        <!-- Header & Navigasi -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0 text-success font-weight-bold"><i class="fas fa-search mr-2"></i> <?= esc($title) ?></h3>
                <p class="text-muted small mb-0">Informasi detail kelompok ekstrakurikuler beserta daftar anggota siswa yang terdaftar.</p>
            </div>
            <div>
                <a href="<?= base_url('guru/ekstrakurikuler') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold mr-1">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
                <!-- Ganti URL parameter edit sesuai dengan struktur routing edit Anda -->
                <a href="<?= base_url('guru/ekstrakurikuler/kelompok/edit/' . $kelompok['id']) ?>" class="btn btn-warning btn-sm font-weight-bold text-dark">
                    <i class="fas fa-edit mr-1"></i> Edit Anggota / Kelompok
                </a>
            </div>
        </div>

        <!-- CARD 1: INFORMASI UTAMA KELOMPOK -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4 bg-white">
                <h5 class="font-weight-bold text-dark mb-3 pb-2 border-bottom"><i class="fas fa-info-circle mr-1 text-success"></i> Profil Kelompok</h5>
                
                <div class="row">
                    <!-- Sisi Kiri -->
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="font-weight-bold text-secondary" width="40%">Nama Kelompok</td>
                                <td class="text-dark">: <?= esc($kelompok['nama_kelompok']) ?></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-secondary">Guru Pembina</td>
                                <td class="text-dark">: <?= esc($kelompok['pembimbing'] ?? 'Belum Ditentukan') ?></td>
                            </tr>
                        </table>
                    </div>
                    <!-- Sisi Kanan -->
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="font-weight-bold text-secondary" width="40%">Jenis Kelompok</td>
                                <td>: 
                                    <?php if($kelompok['jenis_kelompok'] === 'Reguler'): ?>
                                        <span class="badge badge-primary text-dark py-1 px-2 font-weight-bold">Reguler</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning text-dark py-1 px-2 font-weight-bold">Khusus (Remedial / Bakat)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-secondary">Total Anggota</td>
                                <td class="text-dark font-weight-bold">: <?= count($anggota) ?> Siswa</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: DAFTAR ANGGOTA SISWA -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 bg-white">
                
                <!-- Toolbar Pencarian Anggota -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 pb-2 border-bottom">
                    <h5 class="font-weight-bold text-dark mb-2 mb-sm-0"><i class="fas fa-users mr-1 text-primary"></i> Anggota Terdaftar</h5>
                    
                    <!-- Form Input Pencarian Realtime -->
                    <div style="max-width: 300px; width: 100%;">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                            </div>
                            <input type="text" id="search_siswa" class="form-control border-left-0" placeholder="Cari nama atau kelas siswa...">
                        </div>
                    </div>
                </div>

                <!-- Tabel Anggota -->
                <?php if(empty($anggota)): ?>
                    <div class="text-center py-5 border rounded bg-light">
                        <i class="fas fa-user-slash fa-2x text-muted mb-2"></i>
                        <p class="text-muted font-weight-bold mb-0">Belum ada siswa yang bergabung di kelompok ini.</p>
                        <p class="text-muted small">Klik tombol "Edit Anggota" di atas untuk menambahkan siswa.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive border rounded bg-light" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0 bg-white">
                            <thead class="sticky-header text-dark">
                                <tr>
                                    <th width="8%" class="text-center">No</th>
                                    <th>Nama Siswa</th>
                                    <th width="25%">Kelas / Rombel</th>
                                    <th width="15%" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="member_table_body">
                                <?php $no = 1; foreach($anggota as $a): ?>
                                    <tr class="member-row">
                                        <td class="text-center align-middle font-weight-bold text-muted"><?= $no++ ?></td>
                                        <td class="align-middle font-weight-bold text-dark member-name">
                                            <?= esc($a['nama_siswa']) ?>
                                        </td>
                                        <td class="align-middle font-weight-bold text-primary member-class">
                                            <?= esc($a['rombel_name'] ?? 'Tidak Diketahui') ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-success text-dark py-1 px-2" style="font-size: 85%;">
                                                <i class="fas fa-check mr-1"></i> Aktif
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Notifikasi pencarian kosong -->
                    <div id="no_result" class="text-center py-4 border rounded bg-light mt-2" style="display: none;">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0 font-weight-bold">Siswa yang Anda cari tidak ditemukan.</p>
                    </div>
                <?php endif; ?>

            </div>
            
            <!-- Footer Detail -->
            <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Terakhir diperbarui: <?= $kelompok['updated_at'] ? date('d-m-Y H:i', strtotime($kelompok['updated_at'])) : date('d-m-Y H:i', strtotime($kelompok['created_at'])) ?></span>
                <a href="<?= base_url('guru/ekstrakurikuler') ?>" class="btn btn-secondary btn-sm font-weight-bold px-3">Tutup Detail</a>
            </div>
        </div>

    </div>

    <!-- SCRIPT REALTIME FILTER ANGGOTA -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search_siswa');
            const rows        = document.querySelectorAll('.member-row');
            const noResult    = document.getElementById('no_result');

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const filter = searchInput.value.toLowerCase().trim();
                    let hasVisibleRow = false;

                    rows.forEach(function(row) {
                        const name  = row.querySelector('.member-name').textContent.toLowerCase();
                        const clazz = row.querySelector('.member-class').textContent.toLowerCase();

                        if (name.includes(filter) || clazz.includes(filter)) {
                            row.style.display = '';
                            hasVisibleRow = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Tampilkan pesan kosong jika semua baris tersembunyi
                    if (noResult) {
                        noResult.style.display = hasVisibleRow ? 'none' : 'block';
                    }
                });
            }
        });
    </script>
</body>
</html>