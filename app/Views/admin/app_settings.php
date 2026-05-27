<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9 !important; font-family: 'Source Sans Pro', sans-serif; }
        .card-tabs { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        
        /* TRICK HP FRIENDLY: Membuat Tab Menu Bisa Di-scroll Horizontal pada Layar Ponsel */
        .nav-tabs-responsive {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-bottom: 2px solid #dee2e6;
        }
        .nav-tabs-responsive .nav-item {
            flex: 0 0 auto;
        }
        .nav-link { color: #495057 !important; font-weight: 600; border-radius: 4px; white-space: nowrap; }
        .nav-link.active { background-color: #dee2e6 !important; color: #000000 !important; border-color: #dee2e6 !important; }
        
        /* Tombol khas ekosistem MIMHa Finance */
        .btn-warning-custom { background-color: #FF9F00 !important; border: none !important; color: #ffffff !important; font-weight: 600; }
        .btn-warning-custom:hover { background-color: #e68f00 !important; }
        
        /* Menghilangkan scrollbar secara visual tapi fungsi scroll tetap aktif di HP */
        .nav-tabs-responsive::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper p-3 p-md-4">
        <div class="container-fluid ps-1 pe-1">
            
            <div class="row align-items-center mb-4 g-3">
                <div class="col-12 col-sm-6 text-center text-sm-start">
                    <h3 class="mb-0" style="color: #FF9F00; font-weight: 700; font-size: 1.5rem;">⚙️ Pusat Pengaturan <span style="color: #FFC107;">SiKuMi</span></h3>
                </div>
                <div class="col-12 col-sm-6 text-center text-sm-end">
                    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm mb-1"><i class="bi bi-arrow-left-short"></i> Dashboard</a>
                </div>
            </div>

            <?php if (session()->getFlashdata('sukses')): ?>
                <div class="alert alert-success shadow-sm mb-4 small" role="alert">
                    🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger shadow-sm mb-4 small" role="alert">
                    ⚠️ <strong>Peringatan:</strong> <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <div class="card card-tabs shadow-sm">
                <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                    <ul class="nav nav-tabs nav-tabs-responsive" id="sikumiSettingsTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-profil-tab" data-bs-toggle="tab" href="#tab-profil" role="tab">
                                <i class="bi bi-building me-1 text-warning"></i>Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-kurikulum-tab" data-bs-toggle="tab" href="#tab-kurikulum" role="tab">
                                <i class="bi bi-sliders me-1 text-warning"></i>Kurikulum
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-angkatan-tab" data-bs-toggle="tab" href="#tab-angkatan" role="tab">
                                <i class="bi bi-people me-1 text-warning"></i>Angkatan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-akademik-tab" data-bs-toggle="tab" href="#tab-akademik" role="tab">
                                <i class="bi bi-calendar-check me-1 text-warning"></i>Semester Aktif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-ai-tab" data-bs-toggle="tab" href="#tab-ai" role="tab">
                                <i class="bi bi-cpu me-1 text-primary"></i>AI Engine
                            </a>
                        </li>
                    </ul>
                </div>
                
                <form action="<?= base_url('admin/settings/save') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    
                    <div class="card-body bg-white p-3 p-md-4">
                        <div class="tab-content" id="sikumiSettingsTabContent">
                            
                            <div class="tab-pane fade show active" id="tab-profil" role="tabpanel">
                                <h6 class="mb-3 font-weight-bold text-secondary border-bottom pb-2">🏢 Identitas Madrasah / Sekolah</h6>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div>
                                        <label class="form-label font-weight-bold small">Nama Resmi Instansi</label>
                                        <input type="text" name="kaldik_lembaga_nama" class="form-control form-control-sm" value="<?= $settings['kaldik_lembaga_nama'] ?? 'MTs MIMHa Putra Padasuka' ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Nama Kepala Madrasah</label>
                                        <input type="text" name="kaldik_kepala_nama" class="form-control form-control-sm" value="<?= $settings['kaldik_kepala_nama'] ?? 'Yana Purnama, S.Pd.' ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">NPK Kepala</label>
                                        <input type="text" name="kaldik_kepala_npk" class="form-control form-control-sm" value="<?= $settings['kaldik_kepala_npk'] ?? '2102309482039' ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Titi Mangsa Kaldik</label>
                                        <input type="text" name="kaldik_titi_mangsa" class="form-control form-control-sm" value="<?= $settings['kaldik_titi_mangsa'] ?? 'Bandung, 02 Januari 2026' ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Logo Kiri (Yayasan/Kemenag)</label>
                                        <input type="file" name="logo_kaldik1" class="form-control form-control-sm" accept="image/png">
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Logo Kanan (Lembaga)</label>
                                        <input type="file" name="logo_kaldik2" class="form-control form-control-sm" accept="image/png">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="tab-kurikulum" role="tabpanel">
                                <h6 class="mb-3 font-weight-bold text-secondary border-bottom pb-2">⚡ Aturan Jam Pelajaran & Operasional</h6>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div>
                                        <label class="form-label font-weight-bold small">Sistem Hari Kerja Efektif</label>
                                        <select name="kaldik_hari_kerja" class="form-select form-control form-control-sm">
                                            <option value="5" <?= isset($settings['kaldik_hari_kerja']) && $settings['kaldik_hari_kerja'] == '5' ? 'selected' : '' ?>>5 Hari Kerja (Senin - Jumat)</option>
                                            <option value="6" <?= isset($settings['kaldik_hari_kerja']) && $settings['kaldik_hari_kerja'] == '6' ? 'selected' : '' ?>>6 Hari Kerja (Senin - Sabtu)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Durasi per JP</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="durasi_menit_jp" class="form-control" value="<?= $settings['durasi_menit_jp'] ?? '40' ?>" required>
                                            <span class="input-group-text bg-light text-dark font-weight-bold">Menit</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-angkatan" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 font-weight-bold text-secondary">👥 Riwayat Tahun Angkatan Siswa (Unik)</h6>
                                    <button type="button" class="btn btn-success btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalTambahAngkatan">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Angkatan
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped text-nowrap table-sm small align-middle">
                                        <thead>
                                            <tr class="table-light">
                                                <th width="50">No</th>
                                                <th>Tahun Angkatan</th>
                                                <th>Catatan Keterangan Sistem</th>
                                                <th width="80" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1; 
                                            $angkatanTercetak = []; 
                                            foreach($academic as $ang) : 
                                                $tahunMurni = substr($ang['academic_year'], 0, 9); 
                                                if (!in_array($tahunMurni, $angkatanTercetak)) :
                                                    $angkatanTercetak[] = $tahunMurni;
                                            ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td class="font-weight-bold">Angkatan <?= $tahunMurni ?></td>
                                                <td class="text-muted">Menampung database siswa yang terdaftar masuk pada TP <?= $tahunMurni ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm py-1 px-2 font-weight-bold btn-buka-hapus" data-tahun="<?= $tahunMurni ?>">
                                                        X </button>
                                                </td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-akademik" role="tabpanel">
                                <h6 class="mb-3 font-weight-bold text-secondary border-bottom pb-2">🗓️ Sakelar Semester Utama (Central Switch)</h6>
                                <div class="alert alert-warning border-0 mb-3 p-2 style-sm" style="font-size: 11px;">
                                    ⚠️ <strong>Perhatian:</strong> Pengalihan ini berdampak langsung pada acuan kerja guru, rombel, dan modul ajar.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover text-nowrap table-sm small">
                                        <thead>
                                            <tr class="table-light">
                                                <th>Tahun Pelajaran - Semester</th>
                                                <th width="140">Status</th>
                                                <th width="150" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($academic as $tp) : ?>
                                            <tr>
                                                <td class="font-weight-bold align-middle">
                                                    <?= $tp['academic_year'] ?> <?= isset($tp['semester']) ? '- ' . $tp['semester'] : '' ?>
                                                </td>
                                                <td class="align-middle">
                                                    <?= $tp['is_active'] == 1 ? '<span class="badge bg-success p-1 w-100">🚀 Aktif</span>' : '<span class="badge bg-secondary p-1 w-100">Non-Aktif</span>' ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <?php if($tp['is_active'] == 0) : ?>
                                                        
                                                        <a href="<?= base_url('admin/settings/academic-activate/'.$tp['id']) ?>" class="btn btn-xs btn-primary btn-sm w-100 py-1" style="font-size: 11px;">Aktifkan</a>                                                    
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-xs btn-outline-success btn-sm w-100 py-1" style="font-size: 11px;" disabled>Digunakan</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tab-ai" role="tabpanel">
                                <h6 class="mb-3 font-weight-bold text-secondary border-bottom pb-2">🧠 Konfigurasi Asisten Kecerdasan Buatan</h6>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div>
                                        <label class="form-label font-weight-bold small">Pilihan Core LLM AI</label>
                                        <select name="ai_provider" class="form-select form-control form-control-sm">
                                            <option value="gemini" <?= isset($settings['ai_provider']) && $settings['ai_provider'] == 'gemini' ? 'selected' : '' ?>>Google Gemini Flash</option>
                                            <option value="openai" <?= isset($settings['ai_provider']) && $settings['ai_provider'] == 'openai' ? 'selected' : '' ?>>OpenAI GPT-4o Mini</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label font-weight-bold small">Secret API Key Cloud Token</label>
                                        <input type="password" name="ai_api_key" class="form-control form-control-sm" value="<?= $settings['ai_api_key'] ?? '••••••••••••••••••••••••' ?>">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <div class="card-footer bg-light border-top d-grid d-md-flex justify-content-md-end p-3">
                        <button type="submit" class="btn btn-warning-custom shadow-sm px-4 btn-sm">
                            <i class="bi bi-check-circle me-1"></i> Simpan Konfigurasi Pusat
                        </button>
                    </div>
                </form> 
                </div> </div> </div> <div class="modal fade" id="modalTambahAngkatan" tabindex="-1" aria-labelledby="modalAngkatanLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('admin/settings/add-angkatan') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAngkatanLabel" style="font-weight: 600;">👥 Tambah Angkatan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>            
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">Tahun Angkatan Baru</label>
                            <input type="text" name="academic_year" class="form-control" placeholder="Contoh: 2026/2027" maxlength="9" required>
                            <small class="text-muted" style="font-size: 11px;">Format input wajib 9 karakter. Sistem otomatis mendaftarkannya langsung untuk Semester Ganjil & Genap.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm shadow-sm">Simpan Angkatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalHapusAngkatan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= base_url('admin/settings/delete-angkatan') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-header bg-danger text-white py-2">
                        <h6 class="modal-title font-weight-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Konfirmasi Hapus Data</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-wrap small">
                        <p class="mb-2">Apakah Anda yakin ingin menghapus data Tahun Akademik <strong id="teks-tahun-hapus" class="text-danger"></strong> dari sistem?</p>
                        
                        <span class="text-muted text-xs d-block" style="font-size: 11px; line-height: 1.4;">
                            🛡️ <strong>Sistem Keamanan Relasi Aktif:</strong><br>
                            • Jika salah satu semester sudah memiliki agenda kegiatan di kalender akademik (`academic_calendars`), sistem akan <strong>MENOLAK</strong> penghapusan.<br>
                            • Penghapusan hanya diizinkan jika tahun pelajaran ini baru dan belum dihubungkan ke data operasional manapun.
                        </span>
                        
                        <input type="hidden" name="academic_year" id="input-tahun-hapus">
                    </div>
                    <div class="modal-footer py-1">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm shadow-sm font-weight-bold">Ya, Hapus Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Otomatis Deteksi dan Aktifkan Tab Sesuai URL Parameter (?tab=angkatan)
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const targetTab = document.querySelector(`[href="#tab-${tabParam}"]`);
            if (targetTab) {
                const tabTrigger = new bootstrap.Tab(targetTab);
                tabTrigger.show();
                targetTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        }

        // 2. Kirim Data Tahun Pelajaran ke Modal Hapus Dinamis
        const tombolHapus = document.querySelectorAll('.btn-buka-hapus');
        const modalHapusElement = new bootstrap.Modal(document.getElementById('modalHapusAngkatan'));
        
        tombolHapus.forEach(btn => {
            btn.addEventListener('click', function() {
                const tahun = this.getAttribute('data-tahun');
                document.getElementById('teks-tahun-hapus').innerText = tahun;
                document.getElementById('input-tahun-hapus').value = tahun;
                modalHapusElement.show();
            });
        });
    });
    </script>
</body>
</html>