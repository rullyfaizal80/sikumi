<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Susun Modul Ajar KBC - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; font-family: 'Source Sans Pro', sans-serif; }
        .form-section-title {
            background-color: #002060; color: white; padding: 8px 15px;
            font-weight: bold; border-radius: 5px; margin-bottom: 15px; font-size: 14px;
        }
        .auto-filled { background-color: #e9ecef; cursor: not-allowed; font-weight: 600; }
        .custom-checkbox label { font-size: 13px; font-weight: 500; cursor: pointer; }
        .box-tp { background: #f8f9fa; border-left: 4px solid #28a745; padding: 10px; margin-bottom: 10px; }
        
        .form-control::placeholder {
            color: #adb5bd !important; 
            opacity: 0.8 !important;
            font-style: italic;
            font-weight: 400;
        }
        
        .text-pudar {
            color: #adb5bd !important;
        }

        textarea.form-control {
            overflow-y: hidden;
            min-height: 60px;   
            max-height: 350px;  
            resize: none;       
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body class="layout-fixed">
    <div class="wrapper p-4">
        
        <form id="formModul" action="<?= base_url('guru/modul-ajar/store') ?>" method="POST">
            <input type="hidden" name="modul_id" value="<?= esc($modulId) ?>">
            <input type="hidden" name="rombel_id" value="<?= esc($rombelId) ?>">
            <input type="hidden" name="mapel_id" value="<?= esc($mapelId) ?>">
            <input type="hidden" name="atp_ids" value="<?= esc($atpIdsStr) ?>">
            <input type="hidden" name="alokasi_jp" value="<?= esc($totalJp) ?>">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📝 Form Penyusunan Modul Ajar</h3>
                    <p class="text-muted mb-0">Insersi Kurikulum Berbasis Cinta (KBC) & Deep Learning</p>
                </div>
                <div>
                    <button type="button" class="btn btn-info btn-sm font-weight-bold shadow-sm me-1 text-white" data-bs-toggle="modal" data-bs-target="#modalAi">🪄 SiKuMi AI</button>
                    
                    <?php if(!empty($modulId)): ?>
                        <a href="<?= base_url('guru/modul-ajar/print/' . $modulId) ?>" target="_blank" class="btn btn-success btn-sm font-weight-bold shadow-sm me-1">🖨️ Print Modul</a>
                        <button type="button" class="btn btn-danger btn-sm font-weight-bold shadow-sm me-1" onclick="if(confirm('Yakin ingin mereset/menghapus modul ini? Seluruh isian akan hilang dan TP akan kembali ke status Belum Dibuat.')) document.getElementById('formReset').submit();">🗑️ Reset Modul</button>
                    <?php endif; ?>
                    
                    <a href="<?= base_url("guru/modul-ajar?rombel_id={$rombelId}&mapel_id={$mapelId}") ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm me-1">⬅️ Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold shadow-sm">💾 Simpan Progress</button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🗂️ BAGIAN A: Identitas Modul</div>
                            
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Satuan Pendidikan</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($namaMadrasah) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Mata Pelajaran</label>
                                <div class="col-sm-8">
                                    <input type="text" id="input_mapel" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($namaMapelAktif) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Rombel</label>
                                <div class="col-sm-8">
                                    <input type="text" id="input_rombel" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($namaRombel) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Tanggal Pelaksanaan</label>
                                <div class="col-sm-8">
                                    <input type="text" name="tanggal_pelaksanaan" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($tanggalPelaksanaan) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Alokasi Waktu <span class="text-danger">*</span></label>
                                <div class="col-sm-8 d-flex align-items-center">
                                    <input type="text" class="form-control form-control-sm auto-filled text-center font-weight-bold text-muted me-2" style="width: 70px;" value="<?= $totalJp ?> JP" readonly>
                                    <span class="small text-muted me-2 font-weight-bold">x</span>
                                    <input type="number" name="menit_per_jp" class="form-control form-control-sm text-center font-weight-bold" style="width: 80px;" value="<?= esc($modulData['menit_per_jp'] ?? '30') ?>" min="10">
                                    <span class="small text-muted ms-2">Menit</span>
                                </div>
                            </div>

                            <div class="row mb-0 align-items-start">
                                <label class="col-sm-4 small font-weight-bold text-dark mt-2 mb-0">Pertemuan Ke- <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" name="pertemuan_ke" class="form-control form-control-sm" value="<?= esc($modulData['pertemuan_ke'] ?? '') ?>" placeholder="Contoh: 1, atau 1-2">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🎯 BAGIAN B: Identifikasi</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Kesiapan Murid <span class="text-danger">*</span></label>
                                <textarea name="kesiapan_murid" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kondisi/keadaan murid yang berkaitan dengan aspek pengetahuan, fisik, mental, sosial, dan/atau spiritual)"><?= esc($modulData['kesiapan_murid'] ?? '') ?></textarea>
                            </div>

                           <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Materi Pembelajaran <span class="text-danger">*</span></label>
                                <textarea id="input_materi" class="form-control form-control-sm auto-filled text-muted" rows="2" readonly><?= esc($gabunganMateri) ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="small font-weight-bold text-dark mb-2">Dimensi Profil Lulusan <span class="text-danger">*</span></label>
                                    <div class="border p-2 rounded bg-light">
                                        <?php foreach($listProfilLulusan as $kode => $teks): ?>
                                            <?php $isChecked = in_array($kode, $gabunganDpl) ? 'checked' : ''; ?>
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" id="<?= $kode ?>" <?= $isChecked ?> disabled>
                                                <label class="form-check-label text-muted" for="<?= $kode ?>"><?= $kode ?> - <?= $teks ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small font-weight-bold text-dark mb-2">Topik Panca Cinta <span class="text-danger">*</span></label>
                                    <div class="border p-2 rounded bg-light">
                                        <?php foreach($listPancaCinta as $kode => $teks): ?>
                                            <?php $isChecked = in_array($kode, $gabunganPilar) ? 'checked' : ''; ?>
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" id="<?= $kode ?>" <?= $isChecked ?> disabled>
                                                <label class="form-check-label text-muted" for="<?= $kode ?>"><?= $teks ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Materi Integrasi KBC <span class="text-danger">*</span></label>
                                <textarea name="insersi_kbc" class="form-control form-control-sm" rows="4" placeholder="(Tuliskan materi integrasi KBC (Panca Cinta) yang akan dikembangkan dan relevan dengan materi pembelajaran)"><?= esc($modulData['insersi_kbc'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">💡 BAGIAN C: Desain Pembelajaran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Capaian Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="capaian_pembelajaran" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan kalimat inti dari CP pemerintah yang sesuai dengan TP)"><?= esc($modulData['capaian_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lintas Disiplin Ilmu <span class="text-danger">*</span></label>
                                <textarea name="lintas_disiplin" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan keterkaitan materi ini dengan disiplin ilmu lain)"><?= esc($modulData['lintas_disiplin'] ?? '') ?></textarea>
                            </div>

                            <label class="small font-weight-bold text-dark mb-1">Tujuan Pembelajaran (Otomatis dari ATP)</label>
                            <div class="mb-3">
                                <?php foreach($selectedAtpData as $idx => $tp): ?>
                                    <div class="box-tp small text-muted">
                                        <b>TP <?= esc($tp['nomor_atp'] ?? ($idx+1)) ?>:</b> <?= esc($tp['tujuan_pembelajaran']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Topik Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="topik_pembelajaran" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan sub-materi spesifik pada pertemuan ini)"><?= esc($modulData['topik_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Praktik Pedagogis <span class="text-danger">*</span></label>
                                <textarea name="praktik_pedagogis" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan Model/Strategi/Metode pembelajaran)"><?= esc($modulData['praktik_pedagogis'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Kemitraan Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="kemitraan_pembelajaran" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kolaborasi dalam/luar sekolah)"><?= esc($modulData['kemitraan_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lingkungan Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="lingkungan_pembelajaran" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan lingkungan pembelajaran yang dikembangkan)"><?= esc($modulData['lingkungan_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Pemanfaatan Digital <span class="text-danger">*</span></label>
                                <textarea name="pemanfaatan_digital" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan pemanfaatan teknologi digital)"><?= esc($modulData['pemanfaatan_digital'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                </div> 

                <div class="col-lg-6">
                    
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🏃‍♂️ BAGIAN D: Pengalaman Belajar</div>
                            
                            <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">1. Kegiatan Awal <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[awal][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($menitAwal) ?>">
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                <textarea name="kegiatan[awal][isi]" class="form-control form-control-sm border-0 bg-light" rows="3" placeholder="(Tuliskan rincian kegiatan pendahuluan)"><?= esc($kegiatan['awal']['isi'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">2. Kegiatan Inti <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[inti][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($menitInti) ?>">
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                
                                <div class="ps-2 border-left" style="border-left: 3px solid #dee2e6 !important;">
                                    <label class="small font-weight-bold text-dark mb-1 mt-2">a. Memahami</label>
                                    <textarea name="kegiatan[inti][memahami]" class="form-control form-control-sm mb-3 bg-light border-0" rows="2" placeholder="(Kegiatan eksplorasi makna / Meaningful Learning)"><?= esc($kegiatan['inti']['memahami'] ?? '') ?></textarea>

                                    <label class="small font-weight-bold text-dark mb-1">b. Mengaplikasikan</label>
                                    <textarea name="kegiatan[inti][mengaplikasikan]" class="form-control form-control-sm mb-3 bg-light border-0" rows="2" placeholder="(Kegiatan praktik, proyek / Joyful Learning)"><?= esc($kegiatan['inti']['mengaplikasikan'] ?? '') ?></textarea>

                                    <label class="small font-weight-bold text-dark mb-1">c. Merefleksi</label>
                                    <textarea name="kegiatan[inti][merefleksi]" class="form-control form-control-sm mb-1 bg-light border-0" rows="2" placeholder="(Kegiatan evaluasi proses / Mindful Learning)"><?= esc($kegiatan['inti']['merefleksi'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="mb-0 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">3. Kegiatan Penutup <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[penutup][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($menitPenutup) ?>">
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                <textarea name="kegiatan[penutup][isi]" class="form-control form-control-sm border-0 bg-light" rows="3" placeholder="(Tuliskan rincian kegiatan penutup)"><?= esc($kegiatan['penutup']['isi'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">📊 BAGIAN E: Asesmen Pembelajaran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Asesmen pada Awal Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_awal" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen diagnostik)"><?= esc($modulData['asesmen_awal'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Asesmen pada Proses Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_proses" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen formatif selama kegiatan)"><?= esc($modulData['asesmen_proses'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Asesmen pada Akhir Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_akhir" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen sumatif)"><?= esc($modulData['asesmen_akhir'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">📎 BAGIAN F: Lampiran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lembar Materi atau Handout <span class="text-danger">*</span></label>
                                <textarea name="lampiran_materi" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan ringkasan materi pokok)"><?= esc($modulData['lampiran_materi'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">LKM (Lembar Kerja Murid) <span class="text-danger">*</span></label>
                                <textarea name="lampiran_lkm" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan instruksi langkah kerja murid)"><?= esc($modulData['lampiran_lkm'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Rubrik Penilaian <span class="text-danger">*</span></label>
                                <textarea name="lampiran_rubrik" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kriteria skor penilaian)"><?= esc($modulData['lampiran_rubrik'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Sumber Belajar <span class="text-danger">*</span></label>
                                <textarea name="sumber_belajar" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan referensi belajar utama)"><?= esc($modulData['sumber_belajar'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Contoh Produk <span class="text-danger">*</span></label>
                                <textarea name="contoh_produk" class="form-control form-control-sm" rows="2" placeholder="(Deskripsikan wujud hasil belajar siswa)"><?= esc($modulData['contoh_produk'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                </div> 
            </div>
        </form> 

        <?php if(!empty($modulId)): ?>
        <form id="formReset" action="<?= base_url('guru/modul-ajar/reset') ?>" method="POST" style="display:none;">
            <input type="hidden" name="modul_id" value="<?= esc($modulId) ?>">
            <input type="hidden" name="rombel_id" value="<?= esc($rombelId) ?>">
            <input type="hidden" name="mapel_id" value="<?= esc($mapelId) ?>">
        </form>
        <?php endif; ?>

    </div>

    <!-- MODAL SIKUMI AI -->
    <div class="modal fade" id="modalAi" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold"><i class="bi bi-magic"></i> Generate dengan SiKuMi AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        Robot SiKuMi AI akan menganalisis Tujuan Pembelajaran Anda dan otomatis mengisi kolom-kolom yang <b>masih kosong</b>. Isian yang sudah Anda ketik manual aman terjaga.
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-dark">Instruksi Tambahan (Opsional)</label>
                        <textarea id="ai_instruksi" class="form-control" rows="4" placeholder="Contoh: Gunakan pendekatan TaRL, buat kegiatan game tebak kata di awal..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info btn-sm font-weight-bold text-white shadow-sm" id="btnProsesAi" data-url="<?= base_url('guru/modul-ajar/generate-ai') ?>" onclick="prosesAi()">🚀 Mulai Generate</button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>

    <script>
        function autoResizeTextarea(el) {
            el.style.height = 'auto'; 
            let newHeight = el.scrollHeight; 
            el.style.height = newHeight + 'px';
            
            let maxHeight = parseInt(window.getComputedStyle(el).maxHeight);
            if (newHeight >= maxHeight) {
                el.style.overflowY = 'auto';
            } else {
                el.style.overflowY = 'hidden';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            let textareas = document.querySelectorAll('textarea.form-control');
            textareas.forEach(ta => {
                ta.addEventListener('input', function() {
                    autoResizeTextarea(this);
                });
                if(ta.value.trim() !== '') {
                    setTimeout(() => autoResizeTextarea(ta), 100); 
                }
            });
        });

        async function prosesAi() {
            let btn = document.getElementById('btnProsesAi');
            let instruksi = document.getElementById('ai_instruksi').value;
            
            let mapel = document.getElementById('input_mapel').value;
            let rombel = document.getElementById('input_rombel').value;
            let materi = document.getElementById('input_materi').value;
            
            let atpIds = document.querySelector('input[name="atp_ids"]').value;

            let tpElements = document.querySelectorAll('.box-tp');
            let tp = "";
            tpElements.forEach(el => tp += el.innerText + "\n");

            let dplElements = document.querySelectorAll('input[id^="DPL"]:checked');
            let dpl = Array.from(dplElements).map(el => el.nextElementSibling.innerText).join(', ');

            let pilarElements = document.querySelectorAll('input[id^="P"]:checked');
            let pancaCinta = Array.from(pilarElements).map(el => el.nextElementSibling.innerText).join(', ');

            let targetUrl = btn.getAttribute('data-url');

            const formFields = [
                'kesiapan_murid', 'insersi_kbc', 'capaian_pembelajaran', 'lintas_disiplin',
                'topik_pembelajaran', 'praktik_pedagogis', 'kemitraan_pembelajaran',
                'lingkungan_pembelajaran', 'pemanfaatan_digital', 'kegiatan[awal][isi]',
                'kegiatan[inti][memahami]', 'kegiatan[inti][mengaplikasikan]',
                'kegiatan[inti][merefleksi]', 'kegiatan[penutup][isi]', 'asesmen_awal',
                'asesmen_proses', 'asesmen_akhir', 'lampiran_materi', 'lampiran_lkm',
                'lampiran_rubrik', 'sumber_belajar', 'contoh_produk'
            ];

            let emptyFields = [];
            formFields.forEach(f => {
                let el = document.querySelector(`[name="${f}"]`);
                if(el && el.value.trim() === '') {
                    emptyFields.push(f);
                }
            });

            if (emptyFields.length === 0) {
                alert("👍 Semua kolom modul sudah terisi dengan lengkap! Anda tidak perlu memanggil AI lagi.");
                var myModalEl = document.getElementById('modalAi');
                var modal = bootstrap.Modal.getInstance(myModalEl);
                if(modal) modal.hide();
                return;
            }

            const chunkSize = 5;
            let fieldChunks = [];
            for (let i = 0; i < emptyFields.length; i += chunkSize) {
                fieldChunks.push(emptyFields.slice(i, i + chunkSize));
            }

            btn.disabled = true;
            let totalChunks = fieldChunks.length;
            let totalBerhasil = 0;
            let isErrorOccurred = false;

            for (let i = 0; i < totalChunks; i++) {
                if (isErrorOccurred) break; 

                btn.innerHTML = `⏳ Memproses Bagian ${i + 1} dari ${totalChunks}...`;

                let formData = new FormData();
                formData.append('mapel', mapel);
                formData.append('rombel', rombel);
                formData.append('materi', materi);
                formData.append('tp', tp);
                formData.append('instruksi', instruksi);
                formData.append('dpl', dpl);
                formData.append('panca_cinta', pancaCinta);
                formData.append('atp_ids', atpIds);
                formData.append('empty_fields', JSON.stringify(fieldChunks[i]));

                try {
                    let response = await fetch(targetUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });

                    let res = await response.json();

                    if (res.status === 'success') {
                        let d = res.data;
                        
                        const fillData = (name, jsonKey) => {
                            let el = document.querySelector(`[name="${name}"]`);
                            if(el && el.value.trim() === '' && d[jsonKey]) {
                                let val = d[jsonKey];
                                if (typeof val === 'object' && val !== null) {
                                    val = Object.values(val).join('\n\n');
                                }
                                el.value = val;
                                autoResizeTextarea(el);
                                el.style.backgroundColor = '#e8f4fd'; 
                                setTimeout(() => { el.style.backgroundColor = ''; }, 2000);
                            }
                        };

                        const keyMapping = {
                            'kegiatan[awal][isi]' : 'kegiatan_awal',
                            'kegiatan[inti][memahami]' : 'kegiatan_inti_memahami',
                            'kegiatan[inti][mengaplikasikan]' : 'kegiatan_inti_mengaplikasikan',
                            'kegiatan[inti][merefleksi]' : 'kegiatan_inti_merefleksi',
                            'kegiatan[penutup][isi]' : 'kegiatan_penutup'
                        };

                        fieldChunks[i].forEach(f => {
                            let jKey = keyMapping[f] || f;
                            fillData(f, jKey);
                            totalBerhasil++;
                        });

                    } else {
                        alert(`⚠️ Proses terhenti pada bagian ${i + 1}:\n${res.message}\n\nSebagian kolom sudah berhasil diisi. Silakan tunggu 1 menit, lalu klik Generate lagi untuk melanjutkan sisanya.`);
                        isErrorOccurred = true;
                    }
                } catch (error) {
                    alert('Terjadi kesalahan jaringan saat memanggil AI.');
                    console.error(error);
                    isErrorOccurred = true;
                }

                if (i < totalChunks - 1 && !isErrorOccurred) {
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }

            btn.innerHTML = '🚀 Mulai Generate';
            btn.disabled = false;

            if (!isErrorOccurred && totalBerhasil > 0) {
                var myModalEl = document.getElementById('modalAi');
                var modal = bootstrap.Modal.getInstance(myModalEl);
                if(modal) modal.hide();
                
                alert(`🪄 Sempurna! SiKuMi AI telah berhasil merumuskan seluruh bagian secara otomatis.`);
            }
        }

    document.addEventListener('DOMContentLoaded', function() {
        const alokasiJp = parseInt(document.querySelector('input[name="alokasi_jp"]').value) || 0;
        const inputMenitPerJp = document.querySelector('input[name="menit_per_jp"]');
        
        const inputAwal = document.querySelector('input[name="kegiatan[awal][menit]"]');
        const inputInti = document.querySelector('input[name="kegiatan[inti][menit]"]');
        const inputPenutup = document.querySelector('input[name="kegiatan[penutup][menit]"]');

        function hitungTotal() {
            return alokasiJp * (parseInt(inputMenitPerJp.value) || 0);
        }

        function distribusiProporsional() {
            let total = hitungTotal();
            if (total === 0) return;

            let proporsi = Math.round((total * 0.15) / 5) * 5;
            if (proporsi === 0 && total > 0) proporsi = 5;

            inputAwal.value = proporsi;
            inputPenutup.value = proporsi;
            inputInti.value = total - (proporsi * 2);
        }

        function sesuaikanInti() {
            let total = hitungTotal();
            let awal = parseInt(inputAwal.value) || 0;
            let akhir = parseInt(inputPenutup.value) || 0;
            
            let sisa = total - awal - akhir;
            inputInti.value = sisa < 0 ? 0 : sisa; 
        }

        function sesuaikanPenutup() {
            let total = hitungTotal();
            let awal = parseInt(inputAwal.value) || 0;
            let inti = parseInt(inputInti.value) || 0;
            
            let sisa = total - awal - inti;
            inputPenutup.value = sisa < 0 ? 0 : sisa; 
        }

        inputMenitPerJp.addEventListener('input', distribusiProporsional);
        inputAwal.addEventListener('input', sesuaikanInti);
        inputPenutup.addEventListener('input', sesuaikanInti);
        inputInti.addEventListener('input', sesuaikanPenutup); 
    });
    </script>
</body>
</html>
