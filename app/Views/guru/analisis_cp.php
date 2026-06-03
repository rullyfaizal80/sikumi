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
                    <button type="button" class="btn btn-sm text-white font-weight-bold shadow-sm" style="background-color: #FF9F00;" data-bs-toggle="modal" data-bs-target="#modalTambahElemen">➕ Tambah Elemen Baru</button>
                    <button type="button" id="btn-lanjut-ai" class="btn btn-success btn-sm font-weight-bold" <?= empty($draftElemen) ? 'disabled' : '' ?>>✨ Lanjut Analisis dengan SiKuMi (AI)</button>
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
        </div></div>
    </div>


    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <script>
        const mapelAktif = "<?= esc($namaMapelAktif ?? '') ?>";
        const kelasAktif = "<?= esc($namaKelasAktif ?? '') ?>";
        const totalJpSemester = parseInt("<?= ($totalJpTersedia ?? 0) ?>") || 0;
        const urlAiAnalyze = "<?= base_url('ai/analyze_cp') ?>";

        function reloadTabel() {
            const b = document.getElementById('app-data').getAttribute('data-url-reload');
            const m = document.getElementById('mapel_id').value;
            const k = document.getElementById('kelas_id').value;
            if(m && k) window.location.href = b + "?mapel_id=" + m + "&kelas_id=" + k;
        }

        // Fill Edit Elemen
        document.querySelectorAll('.btn-edit-elemen').forEach(b => {
            b.addEventListener('click', function() {
                document.getElementById('edit_draft_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nama_elemen').value = this.getAttribute('data-nama');
                document.getElementById('edit_deskripsi_cp').value = this.getAttribute('data-teks');
            });
        });

        // Fill Edit Analisis
        document.querySelectorAll('.btn-edit-analisis').forEach(b => {
            b.addEventListener('click', function() {
                document.getElementById('edit_analisis_id').value = this.getAttribute('data-id');
                document.getElementById('ea_lingkup').value = this.getAttribute('data-lingkup');
                document.getElementById('ea_jp').value = this.getAttribute('data-jp');
                document.getElementById('ea_tp').value = this.getAttribute('data-tp');
                document.getElementById('ea_kktp').value = this.getAttribute('data-kktp');
                document.getElementById('ea_akt').value = this.getAttribute('data-akt');
                
                // Trik Auto-Select Dropdown Elemen Induk
                let selectedElemenText = this.getAttribute('data-elemen');
                let selectDraft = document.getElementById('ea_draft_id');
                for (let i = 0; i < selectDraft.options.length; i++) {
                    if (selectDraft.options[i].text === selectedElemenText) {
                        selectDraft.selectedIndex = i;
                        break;
                    }
                }
            });
        });

        // Kalkulator JP
        function kalkulasiJP() {
            let t = 0;
            document.querySelectorAll('.kolom-jp-analisis').forEach(td => { t += parseInt(td.innerText) || 0; });
            const lbl = document.getElementById('total-jp-alokasi');
            const wrn = document.getElementById('jp-warning');
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

        // AI Logic
        document.getElementById('btn-lanjut-ai').addEventListener('click', async function() {
            const btnAi = this; const areaHasil = document.getElementById('area-hasil-ai');
            let kumpulanCP = ""; let elemenList = [];
            document.querySelectorAll('.baris-data-elemen').forEach(function(r, i) {
                let nama = r.querySelector('.kolom-nama').innerText.trim();
                let teks = r.querySelector('.kolom-teks').innerText.trim();
                kumpulanCP += `${i + 1}. Elemen ${nama}:\n${teks}\n\n`;
                elemenList.push(nama);
            });

            if (kumpulanCP === "") { alert("Isi elemen CP dulu!"); return; }

            const promptUser = `Guru sedang menyusun rencana pembelajaran dengan konteks berikut:
- Mata Pelajaran: ${mapelAktif}
- Fase/Kelas: ${kelasAktif}
- Total JP Tersedia per Semester: ${totalJpSemester} JP
- Capaian Pembelajaran (CP) yang dianalisis:
${kumpulanCP}
- Fokus Elemen: ${elemenList.join(", ")}

Berdasarkan data di atas, tolong berikan analisis lengkap dan pemetaan materi untuk satu semester sesuai dengan aturan System Prompt Anda.`;

            areaHasil.style.display = 'block';
            areaHasil.innerHTML = `
                <h5 class="font-weight-bold text-success mb-3">✨ SiKuMi Sedang Menganalisis...</h5>
                <div class="alert alert-info shadow-sm" dir="auto">
                    <i class="spinner-border spinner-border-sm mr-2"></i> Membaca CP <b>${mapelAktif}</b>...
                </div>`;
            areaHasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
            btnAi.disabled = true; btnAi.innerHTML = '⏳ Memproses...';

            const formData = new FormData(); formData.append('message', promptUser);
            try {
                const response = await fetch(urlAiAnalyze, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const resData = await response.json();
                if(resData.status === 'success') {
                    areaHasil.innerHTML = `
                        <h5 class="font-weight-bold text-success mb-3">✅ Hasil Analisis AI (Salin ke Tabel Bawah)</h5>
                        <div class="card shadow-sm border-success mb-4">
                            <div class="card-body" dir="auto" contenteditable="true" style="outline: none;">${resData.reply}</div>
                        </div>`;
                } else {
                    areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Gagal: ${resData.reply || resData.message || "Error"}</div>`;
                }
            } catch (error) {
                areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Kesalahan jaringan.</div>`;
            } finally {
                btnAi.disabled = false; btnAi.innerHTML = '✨ Lanjut Analisis dengan SiKuMi (AI)';
            }
        });
    </script>
</body>
</html>
