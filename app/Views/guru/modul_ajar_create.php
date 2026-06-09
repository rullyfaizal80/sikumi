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
                <a href="<?= base_url('guru/modul-ajar') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">⬅️ Batal / Kembali</a>
                <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" onclick="document.getElementById('formModul').submit();">💾 Simpan Modul</button>
            </div>
        </div>

        <form id="formModul" action="<?= base_url('guru/modul-ajar/store') ?>" method="POST">
            <input type="hidden" name="rombel_id" value="<?= esc($rombelId) ?>">
            <input type="hidden" name="mapel_id" value="<?= esc($mapelId) ?>">
            <input type="hidden" name="atp_ids" value="<?= esc($atpIdsStr) ?>">
            <input type="hidden" name="alokasi_jp" value="<?= esc($totalJp) ?>">

            <div class="row">
                <div class="col-lg-6">
                    
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🗂️ BAGIAN A: Identitas Modul (Otomatis)</div>
                            
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label small font-weight-bold text-muted">Satuan Pendidikan</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control form-control-sm auto-filled" value="MIMHa Tsanawiyah Informatika" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label small font-weight-bold text-muted">Mata Pelajaran</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control form-control-sm auto-filled" value="<?= esc($namaMapelAktif) ?>" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label small font-weight-bold text-muted">Total Alokasi Waktu</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control form-control-sm auto-filled text-success" value="<?= $totalJp ?> JP" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label class="col-sm-4 col-form-label small font-weight-bold text-primary">Pertemuan Ke- *</label>
                                <div class="col-sm-8">
                                    <input type="number" name="pertemuan_ke" class="form-control form-control-sm border-primary" min="1" step="1" placeholder="Misal: 1" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🎯 BAGIAN B: Pemetaan Kurikulum</div>
                            
                            <label class="small font-weight-bold text-muted">Materi Pelajaran (Otomatis Gabungan)</label>
                            <textarea class="form-control form-control-sm auto-filled mb-3" rows="2" readonly><?= esc($gabunganMateri) ?></textarea>

                            <label class="small font-weight-bold text-muted">Tujuan Pembelajaran yang Terkait</label>
                            <div class="mb-3">
                                <?php foreach($selectedAtpData as $idx => $tp): ?>
                                    <div class="box-tp small">
                                        <b>TP <?= esc($tp['nomor_atp'] ?? ($idx+1)) ?>:</b> <?= esc($tp['tujuan_pembelajaran']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="small font-weight-bold text-muted mb-2">Dimensi Profil Lulusan (DPL)</label>
                                    <div class="border p-2 rounded bg-light">
                                        <?php foreach($listProfilLulusan as $kode => $teks): ?>
                                            <?php $isChecked = in_array($kode, $gabunganDpl) ? 'checked' : ''; ?>
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" name="dpl[]" value="<?= $kode ?>" id="<?= $kode ?>" <?= $isChecked ?>>
                                                <label class="form-check-label" for="<?= $kode ?>"><?= $kode ?> - <?= $teks ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="small font-weight-bold text-muted mb-2">Lima Pilar Panca Cinta</label>
                                    <div class="border p-2 rounded bg-light">
                                        <?php foreach($listPancaCinta as $kode => $teks): ?>
                                            <?php $isChecked = in_array($kode, $gabunganPilar) ? 'checked' : ''; ?>
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" name="pilar[]" value="<?= $kode ?>" id="<?= $kode ?>" <?= $isChecked ?>>
                                                <label class="form-check-label" for="<?= $kode ?>"><?= $teks ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6">
                    
                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">💡 BAGIAN C: Desain Kontekstual & Insersi KBC</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-primary">Karakteristik Peserta Didik (Opsional)</label>
                                <textarea name="karakteristik_siswa" class="form-control form-control-sm" rows="2" placeholder="Contoh: Peserta didik kelas VIII yang aktif dalam pembiasaan lingkungan..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-primary">Lintas Disiplin Ilmu (Opsional)</label>
                                <input type="text" name="lintas_disiplin" class="form-control form-control-sm" placeholder="Contoh: Terhubung dengan IPA dan PAI">
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-primary">Insersi Materi (Narasi KBC) *</label>
                                <textarea name="insersi_kbc" class="form-control form-control-sm" rows="3" placeholder="Jelaskan bagaimana nilai Cinta Allah & Lingkungan diintegrasikan..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <div class="form-section-title">🏃‍♂️ BAGIAN D & E: Skenario & Evaluasi</div>
                            
                            <div class="mb-3">
                                <label class="small font-weight-bold text-primary">Langkah-Langkah Kegiatan Pembelajaran *</label>
                                <textarea name="kegiatan_pembelajaran" class="form-control form-control-sm" rows="5" placeholder="1. Pendahuluan... 2. Kegiatan Inti (Deep Learning)... 3. Penutup..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="small font-weight-bold text-primary">Sumber Belajar / Media</label>
                                <textarea name="sumber_belajar" class="form-control form-control-sm" rows="2" placeholder="Contoh: Teachable Machine, Botol Plastik, Buku Paket Informatika..."></textarea>
                            </div>

                            <div class="mb-0">
                                <label class="small font-weight-bold text-primary">Contoh Produk / Asesmen</label>
                                <textarea name="asesmen" class="form-control form-control-sm" rows="2" placeholder="Contoh: Screenshot model AI deteksi sampah yang tingkat akurasinya > 90%"></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>

    </div>
</body>
</html>