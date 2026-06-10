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
    </style>
</head>
<body class="layout-fixed">
    <div class="wrapper p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📝 Form Penyusunan Modul Ajar</h3>
                <p class="text-muted mb-0">Insersi Kurikulum Berbasis Cinta (KBC) & Deep Learning</p>
            </div>
            <div>
                <?php if(!empty($modulId)): ?>
                    <button type="button" class="btn btn-danger btn-sm font-weight-bold shadow-sm me-1" onclick="if(confirm('Yakin ingin mereset/menghapus modul ini? Seluruh isian akan hilang dan TP akan kembali ke status Belum Dibuat.')) document.getElementById('formReset').submit();">🗑️ Reset Modul</button>
                <?php endif; ?>
                
                <a href="<?= base_url("guru/modul-ajar?rombel_id={$rombelId}&mapel_id={$mapelId}") ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm me-1">⬅️ Batal</a>
                <button type="submit" form="formModul" class="btn btn-primary btn-sm font-weight-bold shadow-sm">💾 Simpan Modul</button>
            </div>
        </div>

        <?php if(!empty($modulId)): ?>
        <form id="formReset" action="<?= base_url('guru/modul-ajar/reset') ?>" method="POST" style="display:none;">
            <input type="hidden" name="modul_id" value="<?= esc($modulId) ?>">
            <input type="hidden" name="rombel_id" value="<?= esc($rombelId) ?>">
            <input type="hidden" name="mapel_id" value="<?= esc($mapelId) ?>">
        </form>
        <?php endif; ?>
        
        <form id="formModul" action="<?= base_url('guru/modul-ajar/store') ?>" method="POST">
            <input type="hidden" name="modul_id" value="<?= esc($modulId) ?>">
            <input type="hidden" name="rombel_id" value="<?= esc($rombelId) ?>">
            <input type="hidden" name="mapel_id" value="<?= esc($mapelId) ?>">
            <input type="hidden" name="atp_ids" value="<?= esc($atpIdsStr) ?>">
            <input type="hidden" name="alokasi_jp" value="<?= esc($totalJp) ?>">

            <div class="row">
                <!-- ========================================== -->
                <!-- KOLOM KIRI (Bagian A, B, C)                  -->
                <!-- ========================================== -->
                <div class="col-lg-6">
                    
                    <!-- BAGIAN A -->
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
                                    <input type="text" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($namaMapelAktif) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Rombel</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control form-control-sm auto-filled text-muted" value="<?= esc($namaRombel) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <label class="col-sm-4 small font-weight-bold text-muted mb-0">Alokasi Waktu <span class="text-danger">*</span></label>
                                <div class="col-sm-8 d-flex align-items-center">
                                    <input type="text" class="form-control form-control-sm auto-filled text-center font-weight-bold text-muted me-2" style="width: 70px;" value="<?= $totalJp ?> JP" readonly>
                                    <span class="small text-muted me-2 font-weight-bold">x</span>
                                    
                                    <input type="number" name="menit_per_jp" class="form-control form-control-sm text-center font-weight-bold" style="width: 80px;" value="<?= esc($modulData['menit_per_jp'] ?? '30') ?>" min="10" required>
                                    <span class="small text-muted ms-2">Menit</span>
                                </div>
                            </div>

                            <div class="row mb-0 align-items-start">
                                <label class="col-sm-4 small font-weight-bold text-dark mt-2 mb-0">Pertemuan Ke- <span class="text-danger">*</span></label>
                                <div class="col-sm-8">
                                    <input type="text" name="pertemuan_ke" class="form-control form-control-sm" value="<?= esc($modulData['pertemuan_ke'] ?? '') ?>" placeholder="Contoh: 1, atau 1-2" required>
                                    <div class="mt-1">
                                        <small class="text-pudar" style="font-size: 11px;"><i>Catatan: Angka pertemuan tidak boleh sama dengan modul yang sudah Anda simpan sebelumnya.</i></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BAGIAN B -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🎯 BAGIAN B: Identifikasi</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Kesiapan Murid <span class="text-danger">*</span></label>
                                <textarea name="kesiapan_murid" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kondisi/keadaan murid yang berkaitan dengan aspek pengetahuan, fisik, mental, sosial, dan/atau spiritual)" required><?= esc($modulData['kesiapan_murid'] ?? '') ?></textarea>
                            </div>

                           <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Materi Pembelajaran <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm auto-filled text-muted" rows="2" readonly><?= esc($gabunganMateri) ?></textarea>
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
                                <textarea name="insersi_kbc" class="form-control form-control-sm" rows="4" placeholder="(Tuliskan materi integrasi KBC (Panca Cinta) yang akan dikembangkan dan relevan dengan materi pembelajaran)" required><?= esc($modulData['insersi_kbc'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- BAGIAN C -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">💡 BAGIAN C: Desain Pembelajaran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Capaian Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="capaian_pembelajaran" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan kalimat inti dari CP pemerintah yang sesuai dengan TP)" required><?= esc($modulData['capaian_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lintas Disiplin Ilmu <span class="text-danger">*</span></label>
                                <textarea name="lintas_disiplin" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan keterkaitan materi ini dengan disiplin ilmu lain. Contoh: Terhubung dengan Matematika terkait logika, atau PAI terkait adab)" required><?= esc($modulData['lintas_disiplin'] ?? '') ?></textarea>
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
                                <input type="text" name="topik_pembelajaran" class="form-control form-control-sm" value="<?= esc($modulData['topik_pembelajaran'] ?? '') ?>" placeholder="(Tuliskan sub-materi spesifik pada pertemuan ini. Contoh: Pengenalan Algoritma Dasar)" required>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Praktik Pedagogis <span class="text-danger">*</span></label>
                                <textarea name="praktik_pedagogis" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan Model/Strategi/Metode pembelajaran. Contoh: Pembelajaran berbasis proyek, inkuiri, TaRL, dll)" required><?= esc($modulData['praktik_pedagogis'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Kemitraan Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="kemitraan_pembelajaran" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kolaborasi dalam/luar sekolah. Contoh: kemitraan antar guru lintas mapel, praktisi profesional, dsb)" required><?= esc($modulData['kemitraan_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lingkungan Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="lingkungan_pembelajaran" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan lingkungan pembelajaran yang dikembangkan. Contoh: memberikan kesempatan murid berpendapat di ruang kelas atau platform daring)" required><?= esc($modulData['lingkungan_pembelajaran'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Pemanfaatan Digital <span class="text-danger">*</span></label>
                                <textarea name="pemanfaatan_digital" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan pemanfaatan teknologi digital. Contoh: penggunaan Chromebook, Canva, Capcut, atau LMS Google Workspace)" required><?= esc($modulData['pemanfaatan_digital'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                </div> <!-- Tutup Kolom Kiri -->

                <!-- ========================================== -->
                <!-- KOLOM KANAN (Bagian D, E, F)                 -->
                <!-- ========================================== -->
                <div class="col-lg-6">
                    
                    <!-- BAGIAN D -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🏃‍♂️ BAGIAN D: Pengalaman Belajar</div>
                            
                            <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">1. Kegiatan Awal <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[awal][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($kegiatan['awal']['menit'] ?? '10') ?>" required>
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                <textarea name="kegiatan[awal][isi]" class="form-control form-control-sm border-0 bg-light" rows="3" placeholder="(Tuliskan rincian kegiatan pendahuluan seperti berdoa, presensi, apersepsi, pemantik, dll)" required><?= esc($kegiatan['awal']['isi'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">2. Kegiatan Inti <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[inti][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($kegiatan['inti']['menit'] ?? '40') ?>" required>
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                
                                <div class="ps-2 border-left" style="border-left: 3px solid #dee2e6 !important;">
                                    <label class="small font-weight-bold text-dark mb-1 mt-2">a. Memahami</label>
                                    <textarea name="kegiatan[inti][memahami]" class="form-control form-control-sm mb-3 bg-light border-0" rows="2" placeholder="(Kegiatan eksplorasi makna, membangun konsep dasar secara bermakna / Meaningful Learning)" required><?= esc($kegiatan['inti']['memahami'] ?? '') ?></textarea>

                                    <label class="small font-weight-bold text-dark mb-1">b. Mengaplikasikan</label>
                                    <textarea name="kegiatan[inti][mengaplikasikan]" class="form-control form-control-sm mb-3 bg-light border-0" rows="2" placeholder="(Kegiatan praktik, proyek, kolaborasi, dan elaborasi / Joyful Learning)" required><?= esc($kegiatan['inti']['mengaplikasikan'] ?? '') ?></textarea>

                                    <label class="small font-weight-bold text-dark mb-1">c. Merefleksi</label>
                                    <textarea name="kegiatan[inti][merefleksi]" class="form-control form-control-sm mb-1 bg-light border-0" rows="2" placeholder="(Kegiatan konfirmasi pemahaman, evaluasi proses, dan mindfulness / Mindful Learning)" required><?= esc($kegiatan['inti']['merefleksi'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="mb-0 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <label class="small font-weight-bold text-dark mb-0">3. Kegiatan Penutup <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center">
                                        <input type="number" name="kegiatan[penutup][menit]" class="form-control form-control-sm text-center" style="width: 70px;" value="<?= esc($kegiatan['penutup']['menit'] ?? '10') ?>" required>
                                        <span class="small text-dark font-weight-bold ms-2">Menit</span>
                                    </div>
                                </div>
                                <textarea name="kegiatan[penutup][isi]" class="form-control form-control-sm border-0 bg-light" rows="3" placeholder="(Tuliskan rincian kegiatan penutup seperti kesimpulan, tindak lanjut, dan doa penutup)" required><?= esc($kegiatan['penutup']['isi'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- BAGIAN E: Asesmen Pembelajaran -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">📊 BAGIAN E: Asesmen Pembelajaran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Asesmen pada Awal Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_awal" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen diagnostik untuk memetakan kesiapan murid. Contoh: Kuis interaktif fisik, tanya jawab pemantik, atau pre-test)" required><?= esc($modulData['asesmen_awal'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Asesmen pada Proses Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_proses" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen formatif/observasi selama kegiatan. Contoh: Penilaian sikap kolaborasi, nalar kritis, kelengkapan alat, dan kepedulian lingkungan)" required><?= esc($modulData['asesmen_proses'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Asesmen pada Akhir Pembelajaran <span class="text-danger">*</span></label>
                                <textarea name="asesmen_akhir" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan asesmen sumatif untuk mengukur ketercapaian TP. Contoh: Penilaian hasil akhir produk AI, post-test, atau unjuk kerja/presentasi)" required><?= esc($modulData['asesmen_akhir'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- BAGIAN F: Lampiran -->
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">📎 BAGIAN F: Lampiran</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Lembar Materi atau Handout <span class="text-danger">*</span></label>
                                <textarea name="lampiran_materi" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan ringkasan materi pokok, narasi KBC, atau tautan bahan bacaan pendukung untuk murid)" required><?= esc($modulData['lampiran_materi'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">LKM (Lembar Kerja Murid) <span class="text-danger">*</span></label>
                                <textarea name="lampiran_lkm" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan instruksi langkah kerja murid, tabel pengamatan, atau tautan ke file LKPD/LKM cetak)" required><?= esc($modulData['lampiran_lkm'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Rubrik Penilaian <span class="text-danger">*</span></label>
                                <textarea name="lampiran_rubrik" class="form-control form-control-sm" rows="3" placeholder="(Tuliskan kriteria skor penilaian sikap dan keterampilan. Contoh: Skor 4 jika sangat baik, Skor 3 jika cukup, dst.)" required><?= esc($modulData['lampiran_rubrik'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-dark">Sumber Belajar <span class="text-danger">*</span></label>
                                <textarea name="sumber_belajar" class="form-control form-control-sm" rows="2" placeholder="(Tuliskan referensi belajar utama. Contoh: Website Teachable Machine, Buku Paket Informatika Kelas 8, Lingkungan Madrasah)" required><?= esc($modulData['sumber_belajar'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-dark">Contoh Produk <span class="text-danger">*</span></label>
                                <textarea name="contoh_produk" class="form-control form-control-sm" rows="2" placeholder="(Deskripsikan wujud hasil belajar siswa. Contoh: Tangkapan layar model AI pendeteksi sampah atau tautan hasil karya murid)" required><?= esc($modulData['contoh_produk'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                </div> <!-- Tutup Kolom Kanan -->
            </div>
        </form>

    </div>
</body>
</html>