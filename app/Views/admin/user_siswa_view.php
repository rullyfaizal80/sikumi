<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Kelola Siswa</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="p-4 bg-light">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0" style="color: #28A745; font-weight: 700;">🧑‍🎓 Data Akun Login Siswa</h3>
        <p class="text-muted small mb-0">Modul Rebuild: Manajemen Akun Ratusan Siswa Terukur.</p>
    </div>
    <div>
        <a href="<?= base_url('admin/users/siswa-trash') ?>" class="btn btn-outline-danger btn-sm font-weight-bold shadow-sm me-2">
            <i class="fas fa-trash-alt mr-1"></i> Jendela Arsip Trash
        </a>
        <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">Dashboard</a>
    </div>
</div>
        <?php if (session()->getFlashdata('sukses')): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-3" role="alert">
                <?= session()->getFlashdata('sukses') ?>
            </div>
        <?php endif; ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0" style="font-weight: 600;">Daftar Siswa Aktif</h5>
                    </div>
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success text-white btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">
                            ➕ Tambah Siswa Baru
                        </button>
                    </div>
                    <div class="col-md-8">
                        <form action="<?= base_url('admin/users/siswa-tes') ?>" method="GET" class="d-flex justify-content-end">
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama atau email siswa..." value="<?= esc($keyword) ?>">
                                <button class="btn btn-success text-white" type="submit">🔍 Cari</button>
                                <?php if (!empty($keyword)): ?>
                                    <a href="<?= base_url('admin/users/siswa-tes') ?>" class="btn btn-outline-secondary">❌ Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark" style="background-color: #28A745;">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Siswa</th>
                            <th>Email Akun</th>
                            <th>Tanggal Terdaftar</th>
                            <th class="text-center">Status Aktivasi</th>
                            <th class="text-center">Izin Login</th>
                            <th class="text-center" style="width: 120px;">Tindakan</th>
                        </tr>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarSiswa)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Data siswa tidak ditemukan atau database kosong.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1 + ($limit * ($page - 1)); 
                            foreach ($daftarSiswa as $siswa): 
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                <td><strong><?= esc($siswa['username']) ?></strong></td>
                                <td><?= esc($siswa['email']) ?></td>
                                <td><?= date('d M Y', strtotime($siswa['created_at'])) ?></td>
                                <td class="text-center">
                                    <?= $siswa['active'] == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Belum Aktivasi</span>' ?>
                                </td>
                               <td class="text-center">
                                    <?php if (strtolower($siswa['status'] ?? '') === 'banned'): ?>
                                        <span class="badge bg-danger">❌ Ditolak</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">✅ Diizinkan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (strtolower($siswa['status'] ?? '') === 'banned'): ?>
                                        <a href="<?= base_url('admin/users/siswa-toggle/' . $siswa['id']) ?>" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">✔️ Pulihkan</a>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/users/siswa-toggle/' . $siswa['id']) ?>" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Bekukan akses login siswa ini?')">🚫 Blokir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                <div class="text-muted small">
                    Menampilkan <?= count($daftarSiswa) ?> data dari total <strong><?= $totalData ?></strong> data siswa.
                </div>
                
                <?php if ($totalHalaman > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/users/siswa-tes?page_siswa=' . ($page - 1) . '&search=' . urlencode($keyword)) ?>">Sebelumnya</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/users/siswa-tes?page_siswa=' . $i . '&search=' . urlencode($keyword)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $totalHalaman) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/users/siswa-tes?page_siswa=' . ($page + 1) . '&search=' . urlencode($keyword)) ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalTambahSiswa" tabindex="-1" role="dialog" aria-labelledby="modalTambahSiswaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="modalTambahSiswaLabel">🎓 Formulir Akun & Properti Siswa Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/users/siswa-store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        
                        <h6 class="text-success font-weight-bold mb-3">🔑 DATA AKSES LOGIN</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control form-control-sm" placeholder="Contoh: Aditya Pratama" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Email Akun / Siswa <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="Contoh: aditya@student.mimha.sch.id" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Password Akun <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control form-control-sm" placeholder="Minimal 8 Karakter" required>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-success font-weight-bold mb-3">📋 INDUK PROPERTI PERSONAL (STATIS)</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">NISN (Nomor Induk Siswa Nasional)</label>
                                <input type="text" name="nisn" class="form-control form-control-sm" placeholder="Masukkan 10 digit NISN">
                            </div>
                            <div class="col-md-6">
                                <label class="small font-weight-bold">NIS (Nomor Induk Internal)</label>
                                <input type="text" name="nis" class="form-control form-control-sm" placeholder="Masukkan NIS Madrasah">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control form-control-sm" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small font-weight-bold">No. WA Orang Tua / Wali</label>
                                <input type="text" name="phone_ortu" class="form-control form-control-sm" placeholder="Contoh: 081234567xxx">
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-success font-weight-bold mb-3">🏫 RIWAYAT AKADEMIK & PENEMPATAN KELAS (DINAMIS)</h6>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Tahun Pelajaran Masuk <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control form-control-sm" required>
                                    <option value="1">2026/2027 - Ganjil</option> 
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Tingkat / Jenjang Kelas <span class="text-danger">*</span></label>
                                <select name="class_level" class="form-control form-control-sm" required>
                                    <option value="7">Kelas 7</option>
                                    <option value="8">Kelas 8</option>
                                    <option value="9">Kelas 9</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Nama Rombel / Ruang <span class="text-danger">*</span></label>
                                <input type="text" name="class_room" class="form-control form-control-sm" placeholder="Contoh: A, B, Umar, atau Abu Bakar" required>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success text-white btn-sm">💾 Terbitkan Akun Siswa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>