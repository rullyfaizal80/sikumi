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
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalDetailSiswa<?= $siswa['id'] ?>" title="Lihat Detail Profil">
                                            👁️ Detail
                                        </button>
                                        <button type="button" class="btn btn-xs btn-warning text-white py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalEditSiswa<?= $siswa['id'] ?>">
                                            📝 Edit
                                        </button>

                                        <?php if (strtolower($siswa['status'] ?? '') === 'banned'): ?>
                                            <a href="<?= base_url('admin/users/siswa-toggle/' . $siswa['id']) ?>" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">✔️ Izinkan</a>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/users/siswa-toggle/' . $siswa['id']) ?>" class="btn btn-xs btn-outline-dark py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Bekukan akses login akun ini?')">🚫 Blokir</a>
                                        <?php endif; ?>

                                        <a href="<?= base_url('admin/users/siswa-delete/' . $siswa['id']) ?>" class="btn btn-danger btn-sm text-white font-weight-bold shadow-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus akun siswa ini?\n\nSistem akan otomatis menentukan Hard Delete (Permanen) atau Soft Delete (Arsip) berdasarkan rekam jejak kelas siswa.')" title="Hapus Akun Siswa">
                                            🗑️ Hapus
                                        </a>
                                        
                                    </div>
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
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php if (!empty($tahun_akademik)): ?>
                                    <?php foreach ($tahun_akademik as $ta): ?>
                                        <option value="<?= $ta['id'] ?>">
                                            <?= esc($ta['academic_year']) ?> - <?= esc($ta['semester']) ?> 
                                            <?= ($ta['is_active'] == 1) ? '(Aktif)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Data Tahun Akademik Kosong</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small font-weight-bold">Tingkat / Jenjang Kelas <span class="text-danger">*</span></label>
                            <select name="class_level" class="form-control form-control-sm" required>
                                <option value="">-- Pilih Tingkat --</option>
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

    <?php if (!empty($daftarSiswa)): ?>
        <?php foreach ($daftarSiswa as $siswa): ?>
            <div class="modal fade" id="modalDetailSiswa<?= $siswa['id'] ?>" tabindex="-1" aria-labelledby="labelModalSiswa<?= $siswa['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-info text-white py-3">
                            <h5 class="modal-title font-weight-bold" id="labelModalSiswa<?= $siswa['id'] ?>">
                                <i class="bi bi-person-vcard mr-2"></i> Profil & Rekam Jejak: <?= esc($siswa['username']) ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            
                            <h6 class="text-info font-weight-bold mb-3 border-bottom pb-2">📌 Informasi Biodata Dasar</h6>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless small m-0">
                                        <tr>
                                            <td style="width: 35%; font-weight: 600;">Nama Akun</td>
                                            <td>: <?= esc($siswa['username']) ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">Email Terdaftar</td>
                                            <td>: <?= esc($siswa['email'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">NISN</td>
                                            <td>: <span class="badge bg-secondary"><?= esc($siswa['nisn'] ?? '-') ?></span></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">NIS Lokal</td>
                                            <td>: <?= esc($siswa['nis'] ?? '-') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless small m-0">
                                        <tr>
                                            <td style="width: 35%; font-weight: 600;">Jenis Kelamin</td>
                                            <td>: <?= (isset($siswa['gender']) && $siswa['gender'] == 'L') ? 'Laki-laki' : ((isset($siswa['gender']) && $siswa['gender'] == 'P') ? 'Perempuan' : '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">Tempat Lahir</td>
                                            <td>: <?= esc($siswa['birth_place'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">Tanggal Lahir</td>
                                            <td>: <?= !empty($siswa['birth_date']) ? date('d-m-Y', strtotime($siswa['birth_date'])) : '-' ?></td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600;">No. HP Orang Tua</td>
                                            <td>: <span class="text-success font-weight-bold"><?= esc($siswa['phone_ortu'] ?? '-') ?></span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <h6 class="text-info font-weight-bold mb-3 border-bottom pb-2">⏳ Kronologi Akademik & Kelas (Otomatis)</h6>
                            
                            <div class="alert alert-info py-2 small shadow-sm border-0 mb-3">
                                <i class="bi bi-info-circle-fill"></i> <strong>Informasi:</strong> Pemindahan atau pengeluaran siswa dari kelas dilakukan melalui menu <strong>Manajemen Rombel > Kelola Siswa</strong>.
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-bordered small m-0 text-center">
                                    <thead class="bg-light font-weight-bold">
                                        <tr>
                                            <th style="width: 5%;">No</th>
                                            <th>Tahun Pelajaran</th>
                                            <th>Semester</th>
                                            <th>Tingkat</th>
                                            <th>Rombel / Ruang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($siswa['history'])): ?>
                                            <?php $noHist = 1; foreach ($siswa['history'] as $hist): ?>
                                                <tr>
                                                    <td><?= $noHist++ ?></td>
                                                    <td class="font-weight-bold"><?= esc($hist['academic_year']) ?></td>
                                                    <td><?= esc($hist['semester']) ?></td>
                                                    <td>Tingkat <?= esc($hist['tingkat']) ?></td>
                                                    <td class="font-weight-bold text-primary"><?= esc($hist['rombel_name']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    Siswa ini berstatus <strong>Siswa Bebas</strong> dan belum di-plot ke kelas manapun.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup Jendela</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
<?php endif; ?>

    <?php if (!empty($daftarSiswa)): ?>
        <?php foreach ($daftarSiswa as $siswa): ?>
            <div class="modal fade" id="modalEditSiswa<?= $siswa['id'] ?>" tabindex="-1" aria-labelledby="labelModalEdit<?= $siswa['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-warning text-dark py-3">
                            <h5 class="modal-title font-weight-bold" id="labelModalEdit<?= $siswa['id'] ?>">
                                <i class="fas fa-user-edit mr-2"></i> Perbarui Akun & Rekam Jejak: <?= esc($siswa['username']) ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <form action="<?= base_url('admin/users/siswa-update/' . $siswa['id']) ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="modal-body p-4 pb-0">
                                <h6 class="text-warning font-weight-bold mb-3 border-bottom pb-2">🔑 KREDENSIAL LOGIN AKUN</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control form-control-sm" value="<?= esc($siswa['username']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Email Akun <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control form-control-sm" value="<?= esc($siswa['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Password Baru <span class="text-muted text-xs">(Kosongkan jika tidak diganti)</span></label>
                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Minimal 8 karakter jika ingin diubah">
                                    </div>
                                </div>

                                <h6 class="text-warning font-weight-bold mb-3 border-bottom pb-2">📋 INDUK PROPERTI PERSONAL (STATIS)</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">NISN (10 Digit)</label>
                                        <input type="text" name="nisn" class="form-control form-control-sm" value="<?= esc($siswa['nisn'] ?? '') ?>" placeholder="Masukkan NISN">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">NIS Internal</label>
                                        <input type="text" name="nis" class="form-control form-control-sm" value="<?= esc($siswa['nis'] ?? '') ?>" placeholder="Masukkan NIS Internal">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-control form-control-sm" required>
                                            <option value="L" <?= ($siswa['gender'] == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                            <option value="P" <?= ($siswa['gender'] == 'P') ? 'selected' : '' ?>>Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">No. WA Orang Tua / Wali</label>
                                        <input type="text" name="phone_ortu" class="form-control form-control-sm" value="<?= esc($siswa['phone_ortu'] ?? '') ?>" placeholder="Contoh: 081234xxx">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Tempat Lahir</label>
                                        <input type="text" name="birth_place" class="form-control form-control-sm" value="<?= esc($siswa['birth_place'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Tanggal Lahir</label>
                                        <input type="date" name="birth_date" class="form-control form-control-sm" value="<?= esc($siswa['birth_date'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="text-right mb-3">
                                    <button type="submit" class="btn btn-warning btn-sm font-weight-bold text-dark shadow-sm">💾 Perbarui Biodata Utama</button>
                                </div>
                            </div>
                        </form>

                        <div class="modal-body p-4 pt-0">
                            <hr class="mt-0">
                            
                            <h6 class="text-success font-weight-bold mb-3"><i class="fas fa-plus-circle mr-1"></i> PLOTTING TAHUN AJARAN / KELAS BARU</h6>
                            <form action="<?= base_url('admin/users/siswa-add-history/' . $siswa['id']) ?>" method="POST" class="bg-light p-3 rounded border mb-4">
                                <?= csrf_field() ?>
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted">Tahun Pelajaran <span class="text-danger">*</span></label>
                                        <select name="academic_year_id" class="form-control form-control-sm" required>
                                            <option value="">-- Pilih --</option>
                                            <?php if (!empty($tahun_akademik)): ?>
                                                <?php foreach ($tahun_akademik as $ta): ?>
                                                    <option value="<?= $ta['id'] ?>"><?= esc($ta['academic_year']) ?> - <?= esc($ta['semester']) ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted">Tingkat Kelas <span class="text-danger">*</span></label>
                                        <select name="class_level" class="form-control form-control-sm" required>
                                            <option value="7">Kelas 7</option>
                                            <option value="8">Kelas 8</option>
                                            <option value="9">Kelas 9</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2 mb-md-0">
                                        <label class="small font-weight-bold text-muted">Rombel / Ruang <span class="text-danger">*</span></label>
                                        <input type="text" name="class_room" class="form-control form-control-sm" placeholder="Contoh: Umar" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success btn-sm w-100 font-weight-bold text-white shadow-sm">➕ Plotting</button>
                                    </div>
                                </div>
                            </form>

                            <h6 class="text-primary font-weight-bold mb-3"><i class="fas fa-history mr-1"></i> REKAM JEJAK / RIWAYAT KELAS SISWA</h6>
                            <div class="riwayat-container" style="max-height: 250px; overflow-y: auto;">
                                <?php if (!empty($riwayat_siswa[$siswa['id']])): ?>
                                    <?php foreach ($riwayat_siswa[$siswa['id']] as $hist): ?>
                                        <form action="<?= base_url('admin/users/siswa-update-history/' . $hist['history_id']) ?>" method="POST" class="mb-2 shadow-sm border rounded bg-white p-2">
                                            <?= csrf_field() ?>
                                            <div class="row align-items-center">
                                                <div class="col-md-4">
                                                    <span class="small font-weight-bold text-secondary">
                                                        🗓️ <?= esc($hist['academic_year']) ?> - <?= esc($hist['semester']) ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-2">
                                                    <select name="class_level" class="form-control form-control-sm">
                                                        <option value="7" <?= ($hist['class_level'] == '7') ? 'selected' : '' ?>>7</option>
                                                        <option value="8" <?= ($hist['class_level'] == '8') ? 'selected' : '' ?>>8</option>
                                                        <option value="9" <?= ($hist['class_level'] == '9') ? 'selected' : '' ?>>9</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="text" name="class_room" class="form-control form-control-sm font-weight-bold text-primary" value="<?= esc($hist['class_room']) ?>" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <select name="status" class="form-control form-control-sm">
                                                        <option value="aktif" <?= ($hist['status'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="lulus" <?= ($hist['status'] == 'lulus') ? 'selected' : '' ?>>Lulus</option>
                                                        <option value="pindah" <?= ($hist['status'] == 'pindah') ? 'selected' : '' ?>>Pindah</option>
                                                        <option value="keluar" <?= ($hist['status'] == 'keluar') ? 'selected' : '' ?>>Keluar</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 text-center">
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <button type="submit" class="btn btn-outline-success py-1" title="Update Baris Ini">✔️</button>
                                                        <a href="<?= base_url('admin/users/siswa-delete-history/' . $hist['history_id']) ?>" class="btn btn-outline-danger py-1" onclick="return confirm('Hapus baris riwayat kelas ini?')" title="Hapus Riwayat">🗑️</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted small border border-dashed py-3 rounded">
                                        Siswa ini belum memiliki catatan riwayat kelas aktif.
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup Jendela</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>