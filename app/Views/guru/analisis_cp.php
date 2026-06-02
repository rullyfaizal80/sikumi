<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Analisis CP</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="p-4 bg-light">

    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700;">🤖 Analisis Capaian Pembelajaran</h3>
                <p class="text-muted small mb-0">Langkah 1: Kumpulkan semua elemen CP ke dalam tabel sebelum dianalisis.</p>
            </div>
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm font-weight-bold">
                ⬅️ Kembali ke Menu
            </a>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-light rounded">
                <div class="row">
                    
                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small text-secondary">Tahun Ajaran Aktif</label>
                        <input type="text" class="form-control form-control-sm bg-white" value="<?= esc($tahunAktif['academic_year'] ?? 'Tidak ada data') ?> (<?= esc($tahunAktif['semester'] ?? '') ?>)" readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small text-secondary">Pilih Kelas Anda</label>
                        <select name="master_class_id" id="input_master_class_id" class="form-control form-control-sm">
                            <option value="">-- Pilih Kelas --</option>
                            <?php if(!empty($classOptions)): ?>
                                <?php foreach ($classOptions as $class): ?>
                                    <option value="<?= $class['id'] ?>">
                                        Kelas <?= esc($class['class_name']) ?> (<?= esc($class['curriculum_phase']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">- Anda tidak memiliki kelas di rombel aktif -</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="font-weight-bold small text-secondary">Mata Pelajaran Anda</label>
                        <select name="mapel_id" id="input_mapel_id" class="form-control form-control-sm">
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php if(!empty($subjectOptions)): ?>
                                <?php foreach ($subjectOptions as $id => $mapelName): ?>
                                    <option value="<?= $id ?>">
                                        <?= esc($mapelName) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">- Tidak ada mapel di jadwal mengajar Anda -</option>
                            <?php endif; ?>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white pb-0 border-0">
                <h6 class="m-0 font-weight-bold" style="color: #FF9F00;">📋 Tabel Daftar Elemen CP</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="tabel-elemen">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="25%">Nama Elemen</th>
                                <th width="60%">Deskripsi CP</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="body-elemen">
                            <tr id="baris-kosong">
                                <td colspan="4" class="text-center text-muted small py-3">Belum ada elemen yang ditambahkan ke tabel.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-3">
                    <button type="button" id="btn-lanjut-ai" class="btn btn-success btn-sm font-weight-bold" disabled>
                        ✨ Lanjut Analisis dengan SiKuMi (AI)
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-left: 4px solid #FF9F00 !important;">
            <div class="card-body">
                <h6 class="font-weight-bold text-dark mb-3">➕ Tambah Elemen Baru</h6>
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label class="font-weight-bold small">Nama Elemen (Contoh: Berpikir Komputasional)</label>
                        <input type="text" id="input_nama_elemen" class="form-control form-control-sm" placeholder="Ketik nama elemen...">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="font-weight-bold small">Deskripsi CP</label>
                        <textarea id="input_teks_cp" class="form-control form-control-sm" rows="3" placeholder="Paste teks CP di sini..."></textarea>
                    </div>
                    <div class="col-md-12 text-right">
                        <button type="button" id="btn-simpan-ke-tabel" class="btn btn-sm text-white px-3" style="background-color: #FF9F00; font-weight:bold;">
                            💾 Simpan ke Tabel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const btnSimpanKeTabel = document.getElementById('btn-simpan-ke-tabel');
        const bodyElemen = document.getElementById('body-elemen');
        const barisKosong = document.getElementById('baris-kosong');
        const btnLanjutAi = document.getElementById('btn-lanjut-ai');
        
        let nomorUrut = 1;

        // Fungsi saat tombol "Simpan ke Tabel" ditekan
        btnSimpanKeTabel.addEventListener('click', function() {
            const namaElemen = document.getElementById('input_nama_elemen').value.trim();
            const teksCp = document.getElementById('input_teks_cp').value.trim();

            if (namaElemen === '' || teksCp === '') {
                alert('Nama Elemen dan Deskripsi CP tidak boleh kosong!');
                return;
            }

            // Hapus baris "Belum ada elemen" jika masih ada
            if (barisKosong) {
                barisKosong.style.display = 'none';
            }

            // Buat baris (tr) baru untuk tabel
            const tr = document.createElement('tr');
            tr.className = 'baris-data-elemen'; // Penanda untuk dibaca AI nanti
            
            tr.innerHTML = `
                <td class="text-center font-weight-bold">${nomorUrut}</td>
                <td class="kolom-nama">${namaElemen}</td>
                <td class="kolom-teks small">${teksCp}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm py-0 px-2 btn-hapus" title="Hapus Baris">🗑️</button>
                </td>
            `;

            // Tambahkan baris ke dalam tabel
            bodyElemen.appendChild(tr);

            // Aktifkan tombol Generate AI karena tabel sudah ada isinya
            btnLanjutAi.disabled = false;

            // Kosongkan form input kembali untuk elemen berikutnya
            document.getElementById('input_nama_elemen').value = '';
            document.getElementById('input_teks_cp').value = '';
            document.getElementById('input_nama_elemen').focus();

            nomorUrut++;
            
            // Tambahkan event listener untuk tombol hapus di baris yang baru dibuat
            tr.querySelector('.btn-hapus').addEventListener('click', function() {
                tr.remove();
                cekTabelKosong();
            });
        });

        // Fungsi untuk mengecek jika semua baris dihapus, maka kembalikan status kosong
        function cekTabelKosong() {
            const jumlahBaris = document.querySelectorAll('.baris-data-elemen').length;
            if (jumlahBaris === 0) {
                barisKosong.style.display = 'table-row';
                btnLanjutAi.disabled = true;
                nomorUrut = 1; // Reset nomor urut
            }
        }
    });
    </script>
</body>
</html>