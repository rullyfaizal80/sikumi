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
        
        <!-- HEADER HALAMAN & TOMBOL DASHBOARD -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">🤖 Analisis Capaian Pembelajaran</h3>
                <p class="text-muted small mb-0">Asisten SiKuMi: Bedah CP menjadi Tujuan Pembelajaran & KKTP.</p>
            </div>
            <div>
                <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">
                    🏠 Kembali ke Dashboard
                </a>
            </div>
        </div>

        <!-- Notifikasi PHP -->
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible shadow-sm">
                ✅ <?php echo session()->getFlashdata('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- 1. FILTER MAPEL, KELAS, & TOTAL JP -->
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

                <!-- KOLOM TOTAL JP OTOMATIS -->
                <div class="col-md-4">
                    <label class="small font-weight-bold text-secondary">Total JP Tersedia (Semester Ini)</label>
                    <div class="form-control form-control-sm bg-light border-success text-success font-weight-bold text-center">
                        ⏳ <?= isset($totalJpTersedia) && $totalJpTersedia > 0 ? esc($totalJpTersedia) . ' JP' : '0 JP (Cek Jadwal/Kaldik)' ?>
                    </div>
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
                            <th width="20%">Nama Elemen</th>
                            <th width="60%">Deskripsi CP</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($draftElemen)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada elemen yang ditambahkan. Silakan klik tombol "Tambah Elemen Baru" di bawah.</td></tr>
                        <?php else: ?>
                            <?php foreach($draftElemen as $no => $d): ?>
                            <tr class="baris-data-elemen">
                                <td class="text-center"><?= $no+1 ?></td>
                                <td class="font-weight-bold kolom-nama" dir="auto"><?= esc($d['nama_elemen']) ?></td>
                                <td class="small kolom-teks" dir="auto"><?= nl2br(esc($d['deskripsi_cp'])) ?></td>
                                <td class="text-center">
                                    <!-- TOMBOL EDIT BARU -->
                                    <button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-edit" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditElemen"
                                            data-id="<?= $d['id'] ?>" 
                                            data-nama="<?= esc($d['nama_elemen']) ?>" 
                                            data-teks="<?= esc($d['deskripsi_cp']) ?>" 
                                            title="Edit Elemen">✏️</button>
                                            
                                    <a href="<?= base_url('perangkat/delete_draft/'.$d['id']) ?>" class="btn btn-danger btn-sm py-0 px-2" onclick="return confirm('Hapus elemen ini?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
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


    <!-- ========================================== -->
    <!-- MODAL 1: FORM TAMBAH ELEMEN -->
    <!-- ========================================== -->
    <div class="modal fade" id="modalTambahElemen" tabindex="-1" aria-labelledby="modalTambahElemenLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: #FF9F00;">
                    <h6 class="modal-title font-weight-bold" id="modalTambahElemenLabel">➕ Tambah Elemen Baru</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= base_url('perangkat/save_draft') ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>">
                        <input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Nama Elemen</label>
                            <input type="text" name="nama_elemen" class="form-control" placeholder="Contoh: Berpikir Komputasional" required dir="auto">
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Deskripsi CP</label>
                            <textarea name="deskripsi_cp" class="form-control" rows="5" placeholder="Kopi dan paste teks CP di sini..." required dir="auto"></textarea>
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


    <!-- ========================================== -->
    <!-- MODAL 2: FORM EDIT ELEMEN -->
    <!-- ========================================== -->
    <div class="modal fade" id="modalEditElemen" tabindex="-1" aria-labelledby="modalEditElemenLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white bg-primary">
                    <h6 class="modal-title font-weight-bold" id="modalEditElemenLabel">✏️ Edit Elemen CP</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form action="<?= base_url('perangkat/update_draft') ?>" method="POST">
                    <div class="modal-body">
                        <!-- Input ID Draft yang akan diedit -->
                        <input type="hidden" name="draft_id" id="edit_draft_id">
                        <input type="hidden" name="mapel_id" value="<?= esc($selectedMapelId) ?>">
                        <input type="hidden" name="master_class_id" value="<?= esc($selectedKelasId) ?>">

                        <div class="form-group mb-3">
                            <label class="small font-weight-bold">Nama Elemen</label>
                            <input type="text" name="nama_elemen" id="edit_nama_elemen" class="form-control" required dir="auto">
                        </div>
                        <div class="form-group mb-0">
                            <label class="small font-weight-bold">Deskripsi CP</label>
                            <textarea name="deskripsi_cp" id="edit_deskripsi_cp" class="form-control" rows="5" required dir="auto"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Hanya menggunakan file lokal tanpa jQuery CDN internet -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <!-- Script Custom Aplikasi (Vanilla JS) -->
    <script>
        const mapelAktif = "<?= esc($namaMapelAktif ?? '') ?>";
        const kelasAktif = "<?= esc($namaKelasAktif ?? '') ?>";
        const totalJpSemester = "<?= ($totalJpTersedia ?? 0) ?> JP";
        const urlAiAnalyze = "<?= base_url('ai/analyze_cp') ?>";

        function reloadTabel() {
            const baseUrl = document.getElementById('app-data').getAttribute('data-url-reload');
            const mapelId = document.getElementById('mapel_id').value;
            const kelasId = document.getElementById('kelas_id').value;
            
            if(mapelId !== '' && kelasId !== '') {
                window.location.href = baseUrl + "?mapel_id=" + mapelId + "&kelas_id=" + kelasId;
            }
        }

        // FUNGSI UNTUK MENGISI DATA KE DALAM MODAL EDIT SAAT TOMBOL ✏️ DIKLIK
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_draft_id').value = this.getAttribute('data-id');
                document.getElementById('edit_nama_elemen').value = this.getAttribute('data-nama');
                document.getElementById('edit_deskripsi_cp').value = this.getAttribute('data-teks');
            });
        });

        // TRIGGER AI DAN KIRIM DATA
        document.getElementById('btn-lanjut-ai').addEventListener('click', async function() {
            const btnAi = this;
            const areaHasil = document.getElementById('area-hasil-ai');
            
            let kumpulanCP = "";
            let elemenList = [];
            document.querySelectorAll('.baris-data-elemen').forEach(function(row, index) {
                let nama = row.querySelector('.kolom-nama').innerText.trim();
                let teks = row.querySelector('.kolom-teks').innerText.trim();
                kumpulanCP += `${index + 1}. Elemen ${nama}:\n${teks}\n\n`;
                elemenList.push(nama);
            });

            if (kumpulanCP === "") {
                alert("Tabel elemen kosong! Silakan tambah elemen CP terlebih dahulu.");
                return;
            }

            const promptUser = `Guru sedang menyusun rencana pembelajaran dengan konteks berikut:
- Mata Pelajaran: ${mapelAktif}
- Fase/Kelas: ${kelasAktif}
- Total JP Tersedia per Semester: ${totalJpSemester}
- Capaian Pembelajaran (CP) yang dianalisis:
${kumpulanCP}
- Fokus Elemen: ${elemenList.join(", ")}

Berdasarkan data di atas, tolong berikan analisis lengkap dan pemetaan materi untuk satu semester sesuai dengan aturan System Prompt Anda.`;

            areaHasil.style.display = 'block';
            areaHasil.innerHTML = `
                <h5 class="font-weight-bold text-success mb-3">✨ SiKuMi Sedang Menganalisis...</h5>
                <div class="alert alert-info shadow-sm" dir="auto">
                    <i class="spinner-border spinner-border-sm mr-2"></i> 
                    Membaca CP <b>${mapelAktif}</b> dan membaginya ke dalam <b>${totalJpSemester}</b> secara proporsional. Harap tunggu sekitar 15-30 detik...
                </div>
            `;
            areaHasil.scrollIntoView({ behavior: 'smooth', block: 'start' });

            btnAi.disabled = true;
            btnAi.innerHTML = '⏳ Memproses...';

            const formData = new FormData();
            formData.append('message', promptUser);

            try {
                const response = await fetch(urlAiAnalyze, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const resData = await response.json();

                if(resData.status === 'success') {
                    areaHasil.innerHTML = `
                        <h5 class="font-weight-bold text-success mb-3">✅ Hasil Analisis AI (Siap Diedit)</h5>
                        <div class="card shadow-sm border-success">
                            <div class="card-body" dir="auto" contenteditable="true" style="outline: none;">
                                ${resData.reply}
                            </div>
                            <div class="card-footer bg-light text-right">
                                <button class="btn btn-success font-weight-bold">💾 Simpan Analisis ke Tabel Analisis CP (Segera Hadir)</button>
                            </div>
                        </div>
                    `;
                } else {
                    const pesanError = resData.reply || resData.message || resData.error || JSON.stringify(resData);
                    areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Gagal: ${pesanError}</div>`;
                }
            } catch (error) {
                areaHasil.innerHTML = `<div class="alert alert-danger shadow-sm">⚠️ Terjadi kesalahan jaringan saat menghubungi server AI.</div>`;
            } finally {
                btnAi.disabled = false;
                btnAi.innerHTML = '✨ Lanjut Analisis dengan SiKuMi (AI)';
            }
        });
    </script>
</body>
</html>
