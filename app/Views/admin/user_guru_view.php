<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Kelola Guru</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="p-4 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">👨‍🏫 Data Akun Guru & Staf</h3>
                <p class="text-muted small mb-0">Modul Rebuild: Fitur Pencarian & Pagination Manual Terukur.</p>
            </div>
            <div>
                <a href="<?= base_url('admin/users/guru-trash') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
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
                        <h5 class="mb-0" style="font-weight: 600;">Daftar Guru Terdaftar</h5>
                    </div>                  
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                               <button type="button" class="btn btn-primary btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
                                    ➕ Tambah Guru Baru
                                </button>
                            </div>
                            <div class="col-md-8">
                                <form action="<?= base_url('admin/users/guru-tes') ?>" method="GET" class="d-flex justify-content-end">
                                    <div class="input-group input-group-sm" style="max-width: 300px;">
                                        <input type="text" name="search" class="form-control" placeholder="Cari nama atau email..." value="<?= esc($keyword) ?>">
                                        <button class="btn btn-warning text-white" type="submit">🔍 Cari</button>
                                        <?php if (!empty($keyword)): ?>
                                        <a href="<?= base_url('admin/users/guru-tes') ?>" class="btn btn-outline-secondary">❌ Reset</a>
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
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>Jabatan</th>
                            <th class="text-center">Status Aktivasi</th>
                            <th class="text-center">Izin Login</th>
                            <th class="text-center" style="width: 120px;">Tindakan</th> </tr>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftarGuru)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Data guru tidak ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            // Rumus nomor urut tabel yang dinamis mengikuti halaman aktif
                            $no = 1 + ($limit * ($page - 1)); 
                            foreach ($daftarGuru as $guru): 
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold"><?= $no++ ?></td>
                                <td><strong><?= esc($guru['username']) ?></strong></td>
                                <td><?= esc($guru['email']) ?></td>
                                <td>
                                    <?php if (isset($peranUser[$guru['id']])): ?>
                                        <?php foreach ($peranUser[$guru['id']] as $title): ?>
                                            <span class="badge bg-secondary me-1"><?= esc($title) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted small"><i>Tanpa Jabatan</i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= $guru['active'] == 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Belum Aktivasi</span>' ?>
                                </td>
                                <td class="text-center">
                                    <?php if (strtolower($guru['status'] ?? '') === 'banned'): ?>
                                        <span class="badge bg-danger">❌ Ditolak (Banned)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">✅ Diizinkan</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-xs btn-info text-white py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalDetailGuru<?= $guru['id'] ?>">
                                            👁️ Detail
                                        </button>

                                        <button type="button" class="btn btn-xs btn-warning text-white py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#modalEditGuru<?= $guru['id'] ?>">
                                            📝 Edit
                                        </button>

                                        <?php if (strtolower($guru['status'] ?? '') === 'banned'): ?>
                                            <a href="<?= base_url('admin/users/guru-toggle/' . $guru['id']) ?>" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">✔️ Izinkan</a>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/users/guru-toggle/' . $guru['id']) ?>" class="btn btn-xs btn-outline-dark py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Bekukan akses login akun ini?')">🚫 Blokir</a>
                                        <?php endif; ?>

                                        <a href="<?= base_url('admin/users/guru-delete/' . $guru['id']) ?>" class="btn btn-xs btn-danger py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini? Sistem akan mendeteksi otomatis antara hapus bersih atau arsip.')">
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
                    Menampilkan <?= count($daftarGuru) ?> data dari total <strong><?= $totalData ?></strong> data guru.
                </div>
                
                <?php if ($totalHalaman > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/users/guru-tes?page_guru=' . ($page - 1) . '&search=' . urlencode($keyword)) ?>">Sebelumnya</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/users/guru-tes?page_guru=' . $i . '&search=' . urlencode($keyword)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $totalHalaman) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/users/guru-tes?page_guru=' . ($page + 1) . '&search=' . urlencode($keyword)) ?>">Berikutnya</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modalTambahGuru" tabindex="-1" role="dialog" aria-labelledby="modalTambahGuruLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title font-weight-bold" id="modalTambahGuruLabel">👨‍🏫 Formulir Akun & Properti Guru Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="<?= base_url('admin/users/guru-store') ?>" method="POST">
                    <?= csrf_field() ?> <div class="modal-body">
                        
                        <h6 class="text-warning font-weight-bold mb-3">🔑 DATA LOGIN (KREDENSIAL)</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control form-control-sm" placeholder="Contoh: Rully Faizal, M.Pd" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Email Resmi <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control form-control-sm" placeholder="Contoh: rully@mimha.sch.id" required>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">Password Akun <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control form-control-sm" placeholder="Minimal 8 Karakter" required>
                            </div>
                        </div>

                        <hr>
                        <h6 class="text-warning font-weight-bold mb-3">📋 PROPERTI PERSONAL (STATIS)</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="small font-weight-bold">NIP (Jika Ada)</label>
                                <input type="text" name="nip" class="form-control form-control-sm" placeholder="Masukkan 18 digit NIP">
                            </div>
                            <div class="col-md-6">
                                <label class="small font-weight-bold">NUPTK (Jika Ada)</label>
                                <input type="text" name="nuptk" class="form-control form-control-sm" placeholder="Masukkan 16 digit NUPTK">
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
                        </div>

                        <hr>
                        <h6 class="text-warning font-weight-bold mb-3">🏛️ TUGAS PERDANA TAHUNAN (DINAMIS)</h6>
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Tahun Pelajaran <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-control form-control-sm" required>
                                    <option value="">-- Pilih Tahun Pelajaran --</option>
                                    <?php if(!empty($listAcademicYears)): ?>
                                        <?php foreach($listAcademicYears as $ay): ?>
                                            <option value="<?= $ay['id'] ?>" <?= $ay['is_active'] == 1 ? 'selected' : '' ?>>
                                                <?= esc($ay['academic_year']) ?> - <?= esc($ay['semester']) ?> <?= $ay['is_active'] == 1 ? '🔥 (Aktif Saat Ini)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Jabatan / Role <span class="text-danger">*</span></label>
                                <select name="assignment_role" class="form-control form-control-sm" required>
                                    <option value="">-- Pilih Jabatan --</option>
                                    <?php if(!empty($listRoles)): ?>
                                        <?php foreach($listRoles as $role): ?>
                                            <option value="<?= esc($role['role_name']) ?>"><?= esc($role['role_title']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Keterangan Detail Tugas</label>
                                <input type="text" name="assignment_detail" class="form-control form-control-sm" placeholder="Contoh: Mengampu Matematika / Kelas 7">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning text-white btn-sm">💾 Terbitkan Akun Guru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($daftarGuru)): ?>
        <?php foreach ($daftarGuru as $guru): ?>
            <div class="modal fade" id="modalDetailGuru<?= $guru['id'] ?>" tabindex="-1" aria-labelledby="labelModalDetail<?= $guru['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title font-weight-bold" id="labelModalDetail<?= $guru['id'] ?>">👁️ Lembar Data Lengkap & Rekam Jejak Guru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            
                            <div class="card card-body border-0 shadow-sm mb-4">
                                <h6 class="text-info font-weight-bold mb-3">👤 Biodata Profil Guru</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Nama Lengkap & Gelar</small>
                                        <strong class="text-dark"><?= esc($guru['username']) ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Email Akun Resmi</small>
                                        <span class="text-dark"><?= esc($guru['email']) ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Nomor Induk Pegawai (NIP)</small>
                                        <span class="text-dark"><?= esc($guru['nip'] ?? '-') ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">NUPTK Kedinasan</small>
                                        <span class="text-dark"><?= esc($guru['nuptk'] ?? '-') ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Jenis Kelamin</small>
                                        <span class="text-dark"><?= ($guru['gender'] ?? '') === 'L' ? 'Laki-laki' : (($guru['gender'] ?? '') === 'P' ? 'Perempuan' : '-') ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted d-block">Tanggal Pembuatan Akun</small>
                                        <span class="text-dark"><?= date('d F Y (H:i)', strtotime($guru['created_at'])) ?> WIB</span>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-body border-0 shadow-sm">
                                <h6 class="text-primary font-weight-bold mb-3">⏳ Sejarah Riwayat Jabatan & Tugas Mengajar</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th style="width: 50px;" class="text-center">No</th>
                                                <th style="width: 160px;">Tahun Pelajaran</th> <th>Jabatan / Role Pelayanan</th>
                                                <th>Detail Tugas / Kelas Ampuan</th>
                                                <th class="text-center">Tanggal Diplot</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (isset($historiGuru[$guru['id']])): ?>
                                                <?php $nh = 1; foreach ($historiGuru[$guru['id']] as $h): ?>
                                                    <tr>
                                                        <td class="text-center font-weight-bold"><?= $nh++ ?></td>
                                                        <td>
                                                            <span class="text-dark font-weight-bold"><?= esc($h['academic_year'] ?? '-') ?></span>
                                                            <small class="text-muted d-block" style="font-size: 0.75rem;">Semester <?= esc($h['semester'] ?? '-') ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary px-2"><?= esc(str_replace('_', ' ', strtoupper($h['assignment_role']))) ?></span>
                                                        </td>
                                                        <td><strong><?= esc($h['assignment_detail'] ?? 'Guru Bidang Studi') ?></strong></td>
                                                        <td class="text-center text-muted"><?= date('d-m-Y', strtotime($h['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted italic py-3">Belum memiliki riwayat penugasan tahunan tercatat.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                        </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($daftarGuru)): ?>
        <?php foreach ($daftarGuru as $guru): ?>
            <div class="modal fade" id="modalEditGuru<?= $guru['id'] ?>" tabindex="-1" aria-labelledby="labelModalEdit<?= $guru['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title font-weight-bold" id="labelModalEdit<?= $guru['id'] ?>">📝 Formulir Ubah & Manajemen Riwayat Guru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-light">
                            
                            <form action="<?= base_url('admin/users/guru-update/' . $guru['id']) ?>" method="POST" class="mb-4">
                                <?= csrf_field() ?>
                                
                                <h6 class="text-warning font-weight-bold mb-3">🔑 PEMBARUAN DATA LOGIN</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control form-control-sm" value="<?= esc($guru['username']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Email Resmi <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control form-control-sm" value="<?= esc($guru['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Ganti Password (Kosongkan jika tidak diubah)</label>
                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Isi hanya jika ingin ganti password baru">
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-warning font-weight-bold mb-3">📋 PROPERTI PERSONAL (STATIS)</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">NIP (Nomor Induk Pegawai)</label>
                                        <input type="text" name="nip" class="form-control form-control-sm" value="<?= esc($guru['nip'] ?? '') ?>" placeholder="18 digit NIP">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">NUPTK</label>
                                        <input type="text" name="nuptk" class="form-control form-control-sm" value="<?= esc($guru['nuptk'] ?? '') ?>" placeholder="16 digit NUPTK">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="small font-weight-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                        <select name="gender" class="form-control form-control-sm" required>
                                            <option value="L" <?= ($guru['gender'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                            <option value="P" <?= ($guru['gender'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                        </select>
                                    </div>
                                </div>

                                <hr class="border-warning">
                                <div class="bg-white p-3 rounded border border-warning shadow-sm mb-4">
                                    <h6 class="text-primary font-weight-bold mb-1">➕ PLOTTING JABATAN / TUGAS BARU</h6>
                                    <p class="text-muted small mb-3">Gunakan bagian ini untuk menambah riwayat penugasan baru di tahun pelajaran berbeda.</p>
                                    
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="xs font-weight-bold text-secondary">Tahun Pelajaran Baru</label>
                                            <select name="new_academic_year_id" class="form-control form-control-sm">
                                                <option value="">-- Tidak Tambah Tugas --</option>
                                                <?php if(!empty($listAcademicYears)): ?>
                                                    <?php foreach($listAcademicYears as $ay): ?>
                                                        <option value="<?= $ay['id'] ?>"><?= esc($ay['academic_year']) ?> - <?= esc($ay['semester']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="xs font-weight-bold text-secondary">Jabatan Baru</label>
                                            <select name="new_assignment_role" class="form-control form-control-sm">
                                                <option value="">-- Tidak Tambah Tugas --</option>
                                                <?php if(!empty($listRoles)): ?>
                                                    <?php foreach($listRoles as $role): ?>
                                                        <option value="<?= esc($role['role_name']) ?>"><?= esc($role['role_title']) ?></option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="xs font-weight-bold text-secondary">Keterangan Tugas Baru</label>
                                            <input type="text" name="new_assignment_detail" class="form-control form-control-sm" placeholder="Contoh: Wali Kelas 8-B">
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-warning btn-sm text-white font-weight-bold">💾 Simpan Perubahan Profil & Tugas Baru</button>
                                    </div>
                                </div>
                            </form>

                            <hr class="border-secondary">
                            <h6 class="text-dark font-weight-bold mb-3">🛠️ DAFTAR RIWAYAT JABATAN YANG SUDAH TERDAFTAR</h6>
                            
                            <?php if (isset($historiGuru[$guru['id']])): ?>
                                <?php foreach ($historiGuru[$guru['id']] as $index => $h): ?>
                                    
                                    <form action="<?= base_url('admin/users/guru-update-history/' . $h['history_id']) ?>" method="POST" class="bg-white p-2 rounded border mb-2 shadow-sm">
                                        <?= csrf_field() ?>
                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-3">
                                                <select name="edit_academic_year_id" class="form-control form-control-sm" style="font-size:0.8rem;" required>
                                                    <?php foreach($listAcademicYears as $ay): ?>
                                                        <option value="<?= $ay['id'] ?>" <?= $ay['id'] == $h['academic_year_id'] ? 'selected' : '' ?>>
                                                            <?= esc($ay['academic_year']) ?> - <?= esc($ay['semester']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select name="edit_assignment_role" class="form-control form-control-sm" style="font-size:0.8rem;" required>
                                                    <?php foreach($listRoles as $role): ?>
                                                        <option value="<?= esc($role['role_name']) ?>" <?= $role['role_name'] == $h['assignment_role'] ? 'selected' : '' ?>>
                                                            <?= esc($role['role_title']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" name="edit_assignment_detail" class="form-control form-control-sm" style="font-size:0.8rem;" value="<?= esc($h['assignment_detail'] ?? '') ?>" placeholder="Detail tugas">
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="submit" class="btn btn-sm btn-outline-success py-1" title="Simpan Perubahan Baris Ini">✔️ Update</button>
                                                    
                                                    <a href="<?= base_url('admin/users/guru-delete-history/' . $h['history_id']) ?>" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Hapus baris riwayat ini?')" title="Hapus Riwayat Ini">🗑️</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small italic">Tidak ada riwayat jabatan.</p>
                            <?php endif; ?>

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