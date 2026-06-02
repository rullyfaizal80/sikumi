<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SiKuMi - Analisis CP</title>
    <!-- CSS AdminLTE Lokal -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="p-4 bg-light">
    
    <div id="app-data" data-url-reload="<?= base_url('guru/analisis-cp') ?>"></div>

    <div class="container-fluid">
        <h3 class="mb-3" style="color: #FF9F00; font-weight: 700;">🤖 Analisis Capaian Pembelajaran</h3>

        <!-- Notifikasi PHP -->
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible shadow-sm">
                ✅ <?php echo session()->getFlashdata('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 1. FILTER MAPEL & KELAS -->
        <div class="card p-3 mb-4 shadow-sm border-0">
            <div class="row">
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Mata Pelajaran Anda</label>
                    <select id="mapel_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($subjectOptions)): ?>
                            <option value="">- Tidak ada mapel di jadwal Anda -</option>
                        <?php else: ?>
                            <?php foreach($subjectOptions as $id => $val): ?>
                                <?php if ($id == $selectedMapelId): ?>
                                    <option value="<?= esc($id) ?>" selected><?= esc($val) ?></option>
                                <?php else: ?>
                                    <option value="<?= esc($id) ?>"><?= esc($val) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Fase / Kelas Anda</label>
                    <select id="kelas_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($classOptions)): ?>
                            <option value="">- Tidak ada kelas di jadwal Anda -</option>
                        <?php else: ?>
                            <?php foreach($classOptions as $id => $val): ?>
                                <?php if ($id == $selectedKelasId): ?>
                                    <option value="<?= esc($id) ?>" selected><?= esc($val) ?></option>
                                <?php else: ?>
                                    <option value="<?= esc($id) ?>"><?= esc($val) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- 2. TABEL DATA DRAFT (Elemen CP) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="m-0 font-weight-bold" style="color: #FF9F00;">📋 Tabel Elemen CP Tersimpan</h6>
            </div>
            
            <div class="card-body">
                <table class="table table-bordered bg-white table-hover" id="tabel-elemen">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="25%">Nama Elemen</th>
                            <th width="60%">Deskripsi CP</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($draftElemen)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada elemen yang ditambahkan. Silakan klik tombol "Tambah Elemen Baru" di bawah.</td></tr>
                        <?php else: ?>
                            <?php foreach($draftElemen as $no => $d): ?>
                            <tr>
                                <td class="text-center"><?= $no+1 ?></td>
                                <td class="font-weight-bold"><?= esc($d['nama_elemen']) ?></td>
                                <td class="small"><?= esc($d['deskripsi_cp']) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('perangkat/delete_draft/'.$d['id']) ?>" class="btn btn-danger btn-sm py-0 px-2" onclick="return confirm('Hapus elemen ini?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- POSISI BARU: Tombol Tambah Elemen dan Lanjut AI Berdampingan -->
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-sm text-white font-weight-bold shadow-sm me-2" style="background-color: #FF9F00;" data-bs-toggle="modal" data-bs-target="#modalTambahElemen">
                        ➕ Tambah Elemen Baru
                    </button>
                    
                    <button type="button" id="btn-lanjut-ai" class="btn btn-success btn-sm font-weight-bold" <?php echo empty($draftElemen) ? 'disabled' : ''; ?>>
                        ✨ Lanjut Analisis dengan SiKuMi (AI)
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. TEMPAT HASIL AI -->
        <div id="area-hasil-ai" class="mt-4" style="display: none;">
            <h5 class="font-weight-bold text-success mb-3">✅ Hasil Analisis AI (Siap Diedit)</h5>
            <hr>
        </div>

    </div>


    <!-- ========================================================= -->
    <!-- MODAL: FORM TAMBAH ELEMEN -->
    <!-- ========================================================= -->
    <div class="modal fade" id="modalTambahElemen" tabindex="-1" aria-labelledby="modalTambahElemenLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #FF9F00;">
                    <h6 class="modal-title font-weight-bold" id="modalTambahElemenLabel">➕ Tambah Elemen Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= base_url('perangkat/save_draft') ?>" method="POST">
                    <div class="modal-body">
                        <!-- Data Tersembunyi -->
                        <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>">
                        <input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Nama Elemen</label>
                            <input type="text" name="nama_elemen" class="form-control" placeholder="Contoh: Berpikir Komputasional" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Deskripsi CP</label>
                            <textarea name="deskripsi_cp" class="form-control" rows="5" placeholder="Kopi dan paste teks CP dari dokumen BSKAP di sini..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white font-weight-bold" style="background-color: #FF9F00;">
                            💾 Simpan ke Tabel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- END MODAL -->


    <!-- Hanya menggunakan file lokal tanpa jQuery CDN internet -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <!-- Script Custom Aplikasi (Vanilla JS) -->
    <script>
        // Reload saat dropdown berubah
        function reloadTabel() {
            const baseUrl = document.getElementById('app-data').getAttribute('data-url-reload');
            const mapelId = document.getElementById('mapel_id').value;
            const kelasId = document.getElementById('kelas_id').value;
            
            if(mapelId !== '' && kelasId !== '') {
                window.location.href = baseUrl + "?mapel_id=" + mapelId + "&kelas_id=" + kelasId;
            }
        }

        // Trigger AI
        document.getElementById('btn-lanjut-ai').addEventListener('click', function() {
            const areaHasil = document.getElementById('area-hasil-ai');
            areaHasil.style.display = 'block';
            
            // Animasi scroll halus murni Javascript (Tanpa jQuery)
            areaHasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            alert('Tahap selanjutnya: Data dari tabel akan dikirim ke SiKuMi untuk meracik RPP!');
        });
    </script>
</body>
</html>
