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
        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">🤖 Analisis Capaian Pembelajaran</h3>
                <p class="text-muted small mb-0">Susun TP & KKTP secara manual, atau biarkan AI SiKuMi membantu Anda.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">🏠 Kembali ke Dashboard</a>
            </div>
        </div>

        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible shadow-sm">
                ✅ <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible shadow-sm">
                ⚠️ <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- 1. FILTER MAPEL, KELAS, & TOTAL JP -->
        <div class="card p-3 mb-4 shadow-sm border-0">
            <div class="row">
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Mata Pelajaran Anda</label>
                    <select id="mapel_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($subjectOptions)): ?><option value="">- Tidak ada mapel -</option><?php else: ?>
                            <?php foreach($subjectOptions as $id => $val): ?>
                                <option value="<?= esc($id) ?>" <?= ($id == $selectedMapelId) ? 'selected' : '' ?>><?= esc($val) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Fase / Kelas Anda</label>
                    <select id="kelas_id" class="form-control form-control-sm" onchange="reloadTabel()">
                        <?php if(empty($classOptions)): ?><option value="">- Tidak ada kelas -</option><?php else: ?>
                            <?php foreach($classOptions as $id => $val): ?>
                                <option value="<?= esc($id) ?>" <?= ($id == $selectedKelasId) ? 'selected' : '' ?>><?= esc($val) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Total JP Tersedia (Semester Ini)</label>
                    <div class="form-control form-control-sm bg-light border-success text-success font-weight-bold text-center" id="label-jp-tersedia">
                        ⏳ <?= isset($totalJpTersedia) && $totalJpTersedia > 0 ? esc($totalJpTersedia) . ' JP' : '0 JP' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TABEL DATA DRAFT (Elemen CP) -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="m-0 font-weight-bold" style="color: #FF9F00;">📋 Langkah 1: Tabel Elemen CP Tersimpan</h6>
            </div>
            <div class="card-body">
                <table class="table table-bordered bg-white table-hover" id="tabel-elemen">
                    <thead class="bg-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">Nama Elemen</th>
                            <th width="60%">Deskripsi CP</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($draftElemen)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada elemen yang ditambahkan.</td></tr>
                        <?php else: ?>
                            <?php foreach($draftElemen as $no => $d): ?>
                            <tr class="baris-data-elemen">
                                <td class="text-center"><?= $no+1 ?></td>
                                <td class="font-weight-bold kolom-nama" dir="auto"><?= esc($d['nama_elemen']) ?></td>
                                <td class="small kolom-teks" dir="auto"><?= nl2br(esc($d['deskripsi_cp'])) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-edit-elemen" data-bs-toggle="modal" data-bs-target="#modalEditElemen" data-id="<?= $d['id'] ?>" data-nama="<?= esc($d['nama_elemen']) ?>" data-teks="<?= esc($d['deskripsi_cp']) ?>">✏️</button>
                                    <a href="<?= base_url('perangkat/delete_draft/'.$d['id']) ?>" class="btn btn-danger btn-sm py-0 px-2" onclick="return confirm('Hapus elemen ini?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-sm text-white font-weight-bold shadow-sm me-2" style="background-color: #17a2b8;" data-bs-toggle="modal" data-bs-target="#modalCopyElemen">
                        🔄 Salin CP dari Kelas Lain
                    </button>

                    <button type="button" class="btn btn-sm text-white font-weight-bold shadow-sm me-2" style="background-color: #FF9F00;" data-bs-toggle="modal" data-bs-target="#modalTambahElemen">
                        ➕ Tambah Elemen Baru
                    </button>
                    
                    <button type="button" class="btn btn-success btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#modalSettingAI" <?= empty($draftElemen) ? 'disabled' : '' ?>>
                        ✨ Lanjut Analisis dengan SiKuMi (AI)
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. TEMPAT HASIL AI (DIPINDAHKAN KE TENGAH) -->
        <div id="area-hasil-ai" class="mt-4 mb-4" style="display: none;">
            <h5 class="font-weight-bold text-success mb-3">✅ Hasil Analisis AI (Review & Salin)</h5>
            <hr>
        </div>

        <!-- 4. TABEL ANALISIS CP (DIPINDAHKAN KE BAWAH) -->
        <div class="card shadow-sm mb-4 border-success">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="m-0 font-weight-bold text-success">📊 Langkah 2: Tabel Analisis CP (Pemetaan TP & KKTP)</h6>
            </div>
            
            <div class="card-body">
                <div class="alert alert-info py-2 small shadow-sm">
                    💡 <b>Info:</b> Salin hasil dari AI ke tabel ini, atau ketik manual TP milik Anda. Pastikan Total Alokasi JP seimbang.
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover bg-white small">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="15%">Elemen CP</th>
                                <th width="20%">Tujuan Pembelajaran</th>
                                <th width="15%">Lingkup Materi</th>
                                <th width="25%">KKTP</th>
                                <th width="10%">Aktivitas</th>
                                <th width="5%" class="text-center">JP</th>
                                <th width="5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($analisisData)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data analisis CP. Silakan tambah manual dari hasil AI.</td></tr>
                            <?php else: ?>
                                <?php foreach($analisisData as $no => $dt): ?>
                                <tr>
                                    <td class="text-center"><?= $no+1 ?></td>
                                    <td class="font-weight-bold text-primary" dir="auto"><?= esc($dt['elemen_cp']) ?></td>
                                    <td dir="auto"><?= esc($dt['tujuan_pembelajaran']) ?></td>
                                    <td dir="auto"><?= esc($dt['lingkup_materi']) ?></td>
                                    <td dir="auto"><?= nl2br(esc($dt['kktp'])) ?></td>
                                    <td dir="auto"><?= esc($dt['aktivitas_tarl']) ?></td>
                                    <td class="text-center font-weight-bold kolom-jp-analisis"><?= esc($dt['estimasi_jp']) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-edit-analisis mb-1" 
                                                data-bs-toggle="modal" data-bs-target="#modalEditAnalisis"
                                                data-id="<?= $dt['id'] ?>"
                                                data-elemen="<?= esc($dt['elemen_cp']) ?>"
                                                data-lingkup="<?= esc($dt['lingkup_materi']) ?>"
                                                data-tp="<?= esc($dt['tujuan_pembelajaran']) ?>"
                                                data-kktp="<?= esc($dt['kktp']) ?>"
                                                data-jp="<?= esc($dt['estimasi_jp']) ?>"
                                                data-akt="<?= esc($dt['aktivitas_tarl']) ?>">✏️</button>
                                        <a href="<?= base_url('perangkat/delete_analisis_manual/'.$dt['id']) ?>" class="btn btn-danger btn-sm py-0 px-2" onclick="return confirm('Hapus baris analisis ini?')">🗑️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="6" class="text-right">Total Alokasi JP saat ini:</td>
                                <td class="text-center text-primary" id="total-jp-alokasi" style="font-size: 1.1em;">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="jp-warning" style="display:none;"></div>
                
                <!-- TOMBOL TAMBAH DIPINDAH KE BAWAH KANAN -->
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-sm text-white font-weight-bold shadow-sm" style="background-color: #28a745;" data-bs-toggle="modal" data-bs-target="#modalTambahAnalisis" <?= empty($draftElemen) ? 'disabled title="Isi Elemen CP dulu"' : '' ?>>
                        ➕ Tambah Analisis Manual
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- SEMUA MODAL DI BAWAH SINI -->

    <!-- Modal Tambah Elemen -->
    <div class="modal fade" id="modalTambahElemen" tabindex="-1">
        <!-- Sama seperti sebelumnya... -->
        <div class="modal-dialog modal-lg"><div class="modal-content shadow-lg">
            <div class="modal-header text-white" style="background-color: #FF9F00;">
                <h6 class="modal-title font-weight-bold">➕ Tambah Elemen Baru</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('perangkat/save_draft') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>"><input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">
                    <div class="form-group mb-3"><label class="small font-weight-bold">Nama Elemen</label><input type="text" name="nama_elemen" class="form-control" required dir="auto"></div>
                    <div class="form-group mb-0"><label class="small font-weight-bold">Deskripsi CP</label><textarea name="deskripsi_cp" class="form-control" rows="5" required dir="auto"></textarea></div>
                </div>
                <div class="modal-footer bg-light"><button type="submit" class="btn btn-sm text-white font-weight-bold" style="background-color: #FF9F00;">💾 Simpan</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Edit Elemen -->
    <div class="modal fade" id="modalEditElemen" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content shadow-lg">
            <div class="modal-header text-white bg-primary">
                <h6 class="modal-title font-weight-bold">✏️ Edit Elemen CP</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('perangkat/update_draft') ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="draft_id" id="edit_draft_id"><input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>"><input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">
                    <div class="form-group mb-3"><label class="small font-weight-bold">Nama Elemen</label><input type="text" name="nama_elemen" id="edit_nama_elemen" class="form-control" required dir="auto"></div>
                    <div class="form-group mb-0"><label class="small font-weight-bold">Deskripsi CP</label><textarea name="deskripsi_cp" id="edit_deskripsi_cp" class="form-control" rows="5" required dir="auto"></textarea></div>
                </div>
                <div class="modal-footer bg-light"><button type="submit" class="btn btn-primary btn-sm font-weight-bold">💾 Simpan Perubahan</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Tambah Analisis -->
    <div class="modal fade" id="modalTambahAnalisis" tabindex="-1">
        <div class="modal-dialog modal-xl"><div class="modal-content shadow-lg">
            <div class="modal-header text-white bg-success">
                <h6 class="modal-title font-weight-bold">➕ Tambah Analisis CP Manual</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('perangkat/save_analisis_manual') ?>" method="POST">
                <div class="modal-body bg-light">
                    <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>"><input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="small font-weight-bold">Pilih Elemen Induk</label>
                            <select name="draft_id" class="form-control" required dir="auto">
                                <option value="">-- Pilih Elemen --</option>
                                <?php foreach($draftElemen as $d): ?><option value="<?= $d['id'] ?>"><?= esc($d['nama_elemen']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="small font-weight-bold">Lingkup Materi</label><input type="text" name="lingkup_materi" class="form-control" required dir="auto"></div>
                        <div class="col-md-2 mb-3"><label class="small font-weight-bold">Estimasi JP</label><input type="number" name="estimasi_jp" class="form-control" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="small font-weight-bold">Tujuan Pembelajaran (TP)</label><textarea name="tujuan_pembelajaran" class="form-control" rows="3" required dir="auto"></textarea></div>
                        <div class="col-md-6 mb-3"><label class="small font-weight-bold">Kriteria (KKTP)</label><textarea name="kktp" class="form-control" rows="3" required dir="auto"></textarea></div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-0"><label class="small font-weight-bold">Aktivitas (Opsional)</label><input type="text" name="aktivitas" class="form-control" dir="auto"></div>
                    </div>
                </div>
                <div class="modal-footer bg-white"><button type="submit" class="btn btn-success btn-sm font-weight-bold">💾 Simpan Analisis</button></div>
            </form>
        </div></div>
    </div>

    <!-- Modal Edit Analisis -->
    <div class="modal fade" id="modalEditAnalisis" tabindex="-1">
        <div class="modal-dialog modal-xl"><div class="modal-content shadow-lg">
            <div class="modal-header text-white bg-primary">
                <h6 class="modal-title font-weight-bold">✏️ Edit Analisis CP</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('perangkat/update_analisis_manual') ?>" method="POST">
                <div class="modal-body bg-light">
                        <input type="hidden" name="detail_id" id="edit_analisis_id">
                        <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>">
                        <input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="small font-weight-bold">Pilih Elemen Induk</label>
                                <select name="draft_id" id="ea_draft_id" class="form-control" required dir="auto">
                                    <option value="">-- Pilih Elemen --</option>
                                    <?php foreach($draftElemen as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= esc($d['nama_elemen']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="small font-weight-bold">Lingkup Materi</label>
                                <input type="text" name="lingkup_materi" id="ea_lingkup" class="form-control" required dir="auto">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="small font-weight-bold">Estimasi JP</label>
                                <input type="number" name="estimasi_jp" id="ea_jp" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="small font-weight-bold">Tujuan Pembelajaran (TP)</label><textarea name="tujuan_pembelajaran" id="ea_tp" class="form-control" rows="3" required dir="auto"></textarea></div>
                            <div class="col-md-6 mb-3"><label class="small font-weight-bold">Kriteria (KKTP)</label><textarea name="kktp" id="ea_kktp" class="form-control" rows="3" required dir="auto"></textarea></div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-0"><label class="small font-weight-bold">Aktivitas (Opsional)</label><input type="text" name="aktivitas" id="ea_akt" class="form-control" dir="auto"></div>
                        </div>
                    </div>
                <div class="modal-footer bg-white"><button type="submit" class="btn btn-primary btn-sm font-weight-bold">💾 Simpan Perubahan</button></div>
            </form>
        </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 4: PENGATURAN LIMITASI AI            -->
    <!-- ========================================== -->
    <div class="modal fade" id="modalSettingAI" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header text-white bg-success">
                    <h6 class="modal-title font-weight-bold">🤖 Pengaturan AI SiKuMi</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <label class="small font-weight-bold">Target Rata-rata JP per Tujuan Pembelajaran (TP)</label>
                    <p class="text-muted small mb-2">Berapa JP estimasi waktu ideal untuk 1 materi/TP di kelas Anda? (Ini akan memaksa AI memecah CP menjadi materi-materi berdurasi pendek).</p>
                    <div class="input-group mb-3">
                        <input type="number" id="input_target_jp" class="form-control font-weight-bold text-center" value="2" min="1" max="10">
                        <span class="input-group-text font-weight-bold bg-white">Jam Pelajaran (JP)</span>
                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-eksekusi-ai" class="btn btn-success btn-sm font-weight-bold">
                        🚀 Mulai Analisis Otomatis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCopyElemen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white bg-info">
                    <h6 class="modal-title font-weight-bold">🔄 Salin Draft Elemen CP</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= base_url('perangkat/copy_draft') ?>" method="POST">
                    <div class="modal-body bg-light">
                        <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>">
                        <input type="hidden" name="kelas_tujuan_id" value="<?= esc($selectedKelasId) ?>">
                        
                        <div class="alert alert-info small p-2 mb-3">
                            Pilih kelas asal untuk menyalin semua <b>Elemen CP</b> ke kelas <b><?= esc($namaKelasAktif ?? '') ?></b> pada mapel <b><?= esc($namaMapelAktif ?? '') ?></b>.
                        </div>
                        
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Sumber Salinan (Kelas Asal)</label>
                            <select name="kelas_asal_id" class="form-control" required>
                                <option value="">-- Pilih Kelas Asal --</option>
                                <?php foreach($classOptions as $id => $val): ?>
                                    <?php if($id != $selectedKelasId): ?>
                                        <option value="<?= esc($id) ?>"><?= esc($val) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info btn-sm font-weight-bold text-white">🔄 Salin Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <script>
        // DATA UNTUK PROMPT AI (Teks)
        const mapelAktif = "<?= esc($namaMapelAktif ?? '') ?>";
        const kelasAktif = "<?= esc($namaKelasAktif ?? '') ?>";
        
        // DATA UNTUK DATABASE (ID - Perbaikan di sini)
        const mapelIdAktif = "<?= esc($selectedMapelId ?? '') ?>";
        const kelasIdAktif = "<?= esc($selectedKelasId ?? '') ?>";
        
        const totalJpSemester = parseInt("<?= ($totalJpTersedia ?? 0) ?>") || 0;
        const urlAiAnalyze = "<?= base_url('ai/analyze_cp') ?>";

        // Fungsi Reload Tabel saat Filter Diganti
        function reloadTabel() {
            const b = document.getElementById('app-data').getAttribute('data-url-reload');
            const m = document.getElementById('mapel_id').value;
            const k = document.getElementById('kelas_id').value;
            if(m && k) window.location.href = b + "?mapel_id=" + m + "&kelas_id=" + k;
        }

        // Auto-Fill Form Edit Elemen
        document.querySelectorAll('.btn-edit-elemen').forEach(b => {
            b.addEventListener('click', function() {
                document.getElementById('edit_draft_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nama_elemen').value = this.getAttribute('data-nama');
                document.getElementById('edit_deskripsi_cp').value = this.getAttribute('data-teks');
            });
        });

        // Auto-Fill Form Edit Analisis & Auto-Select Dropdown
        document.querySelectorAll('.btn-edit-analisis').forEach(b => {
            b.addEventListener('click', function() {
                document.getElementById('edit_analisis_id').value = this.getAttribute('data-id');
                document.getElementById('ea_lingkup').value = this.getAttribute('data-lingkup');
                document.getElementById('ea_jp').value = this.getAttribute('data-jp');
                document.getElementById('ea_tp').value = this.getAttribute('data-tp');
                document.getElementById('ea_kktp').value = this.getAttribute('data-kktp');
                document.getElementById('ea_akt').value = this.getAttribute('data-akt');
                
                let selectedElemenText = this.getAttribute('data-elemen');
                let selectDraft = document.getElementById('ea_draft_id');
                if(selectDraft) {
                    for (let i = 0; i < selectDraft.options.length; i++) {
                        if (selectDraft.options[i].text === selectedElemenText) {
                            selectDraft.selectedIndex = i; break;
                        }
                    }
                }
            });
        });

        // Kalkulator Real-time JP
        function kalkulasiJP() {
            let t = 0;
            document.querySelectorAll('.kolom-jp-analisis').forEach(td => { t += parseInt(td.innerText) || 0; });
            const lbl = document.getElementById('total-jp-alokasi');
            const wrn = document.getElementById('jp-warning');
            
            if(!lbl || !wrn) return;
            lbl.innerText = t;

            if (t === 0) { wrn.style.display = 'none'; } 
            else if (t > totalJpSemester) {
                wrn.style.display = 'block'; wrn.className = "alert alert-danger py-2 small mt-3 shadow-sm";
                wrn.innerHTML = `🚨 <b>Kelebihan JP!</b> Anda mengalokasikan <b>${t} JP</b>, padahal batas maksimal hanya <b>${totalJpSemester} JP</b>.`;
                lbl.className = "text-center text-danger font-weight-bold";
            } else if (t < totalJpSemester) {
                wrn.style.display = 'block'; wrn.className = "alert alert-warning py-2 small mt-3 shadow-sm";
                wrn.innerHTML = `⚠️ <b>Kekurangan JP.</b> Anda baru mengalokasikan <b>${t} JP</b> dari total target <b>${totalJpSemester} JP</b>.`;
                lbl.className = "text-center text-warning font-weight-bold";
            } else {
                wrn.style.display = 'block'; wrn.className = "alert alert-success py-2 small mt-3 shadow-sm";
                wrn.innerHTML = `✅ <b>Sempurna!</b> Total alokasi JP sudah seimbang.`;
                lbl.className = "text-center text-success font-weight-bold";
            }
        }
        document.addEventListener("DOMContentLoaded", kalkulasiJP);

        // ========================================================
        // TRIGGER AI DENGAN BATASAN JP & PARSING TABEL
        // ========================================================
        document.getElementById('btn-eksekusi-ai').addEventListener('click', async function() {
            
            const targetJp = document.getElementById('input_target_jp').value || "2";
            const btnAi = this; 
            const areaHasil = document.getElementById('area-hasil-ai');
            
            // Tutup Modal dengan aman
            let modalEl = document.getElementById('modalSettingAI');
            if (modalEl) {
                let modalObj = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if(modalObj) modalObj.hide();
            }

            let kumpulanCP = ""; let elemenList = [];
            document.querySelectorAll('.baris-data-elemen').forEach(function(r, i) {
                let nama = r.querySelector('.kolom-nama').innerText.trim();
                let teks = r.querySelector('.kolom-teks').innerText.trim();
                kumpulanCP += `${i + 1}. Elemen ${nama}:\n${teks}\n\n`;
                elemenList.push(nama);
            });

            if (kumpulanCP === "") { alert("Tabel elemen kosong! Silakan tambah elemen CP terlebih dahulu."); return; }

            const promptUser = `Guru sedang menyusun rencana pembelajaran dengan konteks berikut:
- Mata Pelajaran: ${mapelAktif}
- Fase/Kelas: ${kelasAktif}
- Total JP Tersedia per Semester: ${totalJpSemester} JP
- Batasan JP: Pecah TP sedemikian rupa sehingga setiap 1 TP berbobot rata-rata ${targetJp} JP. (Maksimal ${parseInt(targetJp) + 2} JP per TP).
- Capaian Pembelajaran (CP) yang dianalisis:
${kumpulanCP}
- Fokus Elemen: ${elemenList.join(", ")}

Berdasarkan data di atas, tolong berikan analisis lengkap dan pemetaan materi.
ATURAN WAJIB: 
1. Jawab menggunakan tag tabel HTML murni (<table>, <thead>, <tbody>, <tr>, <th>, <td>).
2. DILARANG KERAS menggunakan format tabel Markdown.
3. Buat tepat 6 kolom persis urutan ini: Elemen CP, Tujuan Pembelajaran, Lingkup Materi, KKTP, Estimasi JP, Aktivitas Pembelajaran. (Jangan tambahkan kolom Nomor!).`;

            areaHasil.style.display = 'block';
            areaHasil.innerHTML = `
                <h5 class="font-weight-bold text-success mb-3">✨ SiKuMi Sedang Menganalisis...</h5>
                <div class="alert alert-info shadow-sm" dir="auto">
                    <i class="spinner-border spinner-border-sm mr-2"></i> Memecah CP menjadi TP berbobot rata-rata <b>${targetJp} JP</b>. Harap tunggu...
                </div>`;
            areaHasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            btnAi.disabled = true; btnAi.innerHTML = '⏳ Memproses...';

            const formData = new FormData(); formData.append('message', promptUser);
            
            try {
                const response = await fetch(urlAiAnalyze, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const resData = await response.json();
                
                if(resData.status === 'success') {
                    let tempDiv = document.createElement('div');
                    tempDiv.innerHTML = resData.reply;
                    let aiTables = tempDiv.querySelectorAll('table');
                    
                    if (aiTables.length > 0) {
                        let newTableHtml = `
                            <h5 class="font-weight-bold text-success mb-3">✅ Hasil Analisis AI</h5>
                            <div class="alert alert-warning py-2 small shadow-sm">
                                💡 <b>Perhatian:</b> Silakan baca hasil rancangan AI. Biarkan centang pada TP yang ingin digunakan, lalu klik tombol Simpan di kanan bawah.
                            </div>
                            <div class="table-responsive shadow-sm border border-success rounded">
                                <table class="table table-bordered table-hover small bg-white m-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-center align-middle" width="5%">
                                                <input type="checkbox" checked onchange="document.querySelectorAll('.chk-ai-row').forEach(c => c.checked = this.checked)">
                                            </th>
                                            <th width="15%">Elemen CP</th>
                                            <th width="25%">Tujuan Pembelajaran (TP)</th>
                                            <th width="15%">Lingkup Materi</th>
                                            <th width="20%">KKTP</th>
                                            <th width="5%" class="text-center">JP</th>
                                            <th width="15%">Aktivitas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        let rows = aiTables[0].querySelectorAll('tr');
                        rows.forEach((tr) => {
                            let tds = tr.querySelectorAll('td');
                            if (tds.length < 5) return;
                            
                            let offset = 0;
                            let firstColText = tds[0].innerText.trim().toLowerCase();
                            if (/^\d+$/.test(firstColText) || firstColText === 'no' || firstColText === 'no.') offset = 1;

                            let elemen = tds[offset] ? tds[offset].innerText.trim() : '';
                            let tp = tds[offset+1] ? tds[offset+1].innerText.trim() : '';
                            let lingkup = tds[offset+2] ? tds[offset+2].innerText.trim() : '';
                            let kktp = tds[offset+3] ? tds[offset+3].innerText.trim() : '';
                            let jpText = tds[offset+4] ? tds[offset+4].innerText : '0';
                            let jp = jpText.replace(/[^0-9]/g, '') || '0'; 
                            let aktivitas = tds[offset+5] ? tds[offset+5].innerText.trim() : '';

                            let safeElemen = elemen.replace(/"/g, '&quot;');
                            let safeTp = tp.replace(/"/g, '&quot;');
                            let safeLingkup = lingkup.replace(/"/g, '&quot;');
                            let safeKktp = kktp.replace(/"/g, '&quot;');
                            let safeAkt = aktivitas.replace(/"/g, '&quot;');

                            newTableHtml += `
                                <tr>
                                    <td class="text-center align-middle">
                                        <input type="checkbox" class="chk-ai-row" checked
                                            data-elemen="${safeElemen}" data-tp="${safeTp}"
                                            data-lingkup="${safeLingkup}" data-kktp="${safeKktp}"
                                            data-jp="${jp}" data-aktivitas="${safeAkt}">
                                    </td>
                                    <td class="font-weight-bold text-primary" dir="auto">${elemen}</td>
                                    <td dir="auto">${tp}</td>
                                    <td dir="auto">${lingkup}</td>
                                    <td dir="auto">${kktp}</td>
                                    <td class="text-center font-weight-bold">${jp}</td>
                                    <td dir="auto">${aktivitas}</td>
                                </tr>
                            `;
                        });

                        newTableHtml += `</tbody></table></div>`;

                        let paragraphs = tempDiv.querySelectorAll('p');
                        let summaryText = "";
                        paragraphs.forEach(p => { summaryText += `<div class="alert alert-secondary mt-3 small shadow-sm" dir="auto"><b>Saran AI:</b> ${p.innerText}</div>`; });
                        newTableHtml += summaryText;

                        newTableHtml += `
                            <div class="d-flex justify-content-end mt-4 mb-2">
                                <button class="btn btn-primary btn-sm font-weight-bold shadow-sm px-4" id="btn-save-ai-batch">
                                    ⬇️ Simpan yang Dicentang ke Tabel Analisis
                                </button>
                            </div>
                        `;
                        areaHasil.innerHTML = newTableHtml;

                        // BINDING EVENT SIMPAN KE DATABASE
                        document.getElementById('btn-save-ai-batch').addEventListener('click', async function() {
                            const btnSave = this;
                            btnSave.disabled = true; btnSave.innerHTML = "⏳ Menyimpan ke Database...";
                            
                            let dataToSave = [];
                            document.querySelectorAll('.chk-ai-row:checked').forEach(chk => {
                                dataToSave.push({
                                    elemen: chk.getAttribute('data-elemen'),
                                    tp: chk.getAttribute('data-tp'),
                                    lingkup: chk.getAttribute('data-lingkup'),
                                    kktp: chk.getAttribute('data-kktp'),
                                    jp: parseInt(chk.getAttribute('data-jp')) || 0,
                                    aktivitas: chk.getAttribute('data-aktivitas')
                                });
                            });

                            if(dataToSave.length === 0) {
                                alert("Tidak ada baris yang dicentang!"); 
                                btnSave.disabled = false; btnSave.innerHTML = "⬇️ Simpan yang Dicentang ke Tabel Analisis"; 
                                return;
                            }

                            let batchForm = new FormData();
                            // PERBAIKAN: Menggunakan Variabel ID, bukan Nama String
                            batchForm.append('mapel_id', mapelIdAktif); 
                            batchForm.append('master_class_id', kelasIdAktif);
                            batchForm.append('data_rows', JSON.stringify(dataToSave));

                            try {
                                const svRes = await fetch("<?= base_url('perangkat/save_analisis_batch') ?>", { method: 'POST', body: batchForm, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                const svData = await svRes.json();
                                if(svData.status === 'success') {
                                    window.location.reload(); 
                                } else {
                                    alert("Gagal menyimpan: " + svData.message);
                                    btnSave.disabled = false; btnSave.innerHTML = "⬇️ Simpan yang Dicentang ke Tabel Analisis";
                                }
                            } catch (e) {
                                alert("Terjadi kesalahan sistem saat menyimpan ke database.");
                                btnSave.disabled = false; btnSave.innerHTML = "⬇️ Simpan yang Dicentang ke Tabel Analisis";
                            }
                        });

                    } else {
                        areaHasil.innerHTML = `
                            <h5 class="font-weight-bold text-warning mb-3">⚠️ AI Merespons Tanpa Format Tabel</h5>
                            <div class="alert alert-info py-2 small shadow-sm">AI gagal merangkai format tabel, silakan klik tombol <b>🚀 Mulai Analisis Otomatis</b> sekali lagi.</div>
                            <div class="card shadow-sm border-warning mb-4"><div class="card-body" dir="auto" style="white-space: pre-wrap;">${resData.reply}</div></div>
                        `;
                    }
                } else {
                    areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Gagal: ${resData.reply || resData.message || "Error"}</div>`;
                }
            } catch (error) {
                areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Kesalahan saat mengambil data AI.</div>`;
            } finally {
                btnAi.disabled = false; btnAi.innerHTML = '🚀 Mulai Analisis Otomatis';
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style = '';
            }
        });
    </script>
</body>
</html>
