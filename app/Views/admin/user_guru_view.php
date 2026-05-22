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
            <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">Dashboard</a>
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
                        <button type="button" class="btn btn-warning text-white btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahGuru">
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
                                    <?php if (strtolower($guru['status'] ?? '') === 'banned'): ?>
                                        <a href="<?= base_url('admin/users/guru-toggle/' . $guru['id']) ?>" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 0.75rem;">✔️ Pulihkan</a>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/users/guru-toggle/' . $guru['id']) ?>" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size: 0.75rem;" onclick="return confirm('Bekukan akses login akun ini?')">🚫 Blokir</a>
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
                                    <option value="1">2026/2027 - Ganjil</option> 
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small font-weight-bold">Jabatan / Role <span class="text-danger">*</span></label>
                                <select name="assignment_role" class="form-control form-control-sm" required>
                                    <option value="guru_pengajar">Guru Pengajar</option>
                                    <option value="waka_kurikulum">Waka Kurikulum</option>
                                    <option value="waka_kesiswaan">Waka Kesiswaan</option>
                                    <option value="superadmin">Super Admin / Kamad</option>
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

     <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
</body>
</html>