<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Mengajar Guru - SiKuMi</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body { background-color: #f4f6f9; font-family: 'Source Sans Pro', sans-serif; }
        .jurnal-card { border-top: 4px solid #002060; }
        .table-jurnal th { 
            background-color: #002060; 
            color: #ffffff; 
            text-align: center; 
            vertical-align: middle; 
            font-size: 12px; 
            padding: 10px 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-jurnal td { 
            vertical-align: top; 
            font-size: 12px; 
            line-height: 1.4; 
            padding: 8px 6px; 
        }
        .textarea-custom {
            font-size: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            resize: vertical;
            min-height: 65px;
            transition: border-color 0.15s ease-in-out;
        }
        .textarea-custom:focus {
            border-color: #002060;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 32, 96, 0.25);
        }
        .input-absen {
            font-size: 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .btn-save-jurnal {
            padding: 4px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <div class="content p-3">
        <div class="container-fluid">
            
            <div class="card card-outline jurnal-card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="bi bi-journal-check text-primary me-2"></i> Jurnal Mengajar Guru
                        </h5>
                        <small class="text-muted">Periode Bulan: <strong><?= $namaBulan ?></strong> (Digabung Semua Kelas)</small>
                    </div>
                    
                    <div class="ms-auto">
                        <form method="GET" action="<?= current_url() ?>" class="d-flex align-items-center">
                            <label class="small font-weight-bold text-secondary mb-0 me-2">Pilih Bulan:</label>
                            <select name="bulan" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                                <?php foreach($listBulan as $num => $nama): ?>
                                    <option value="<?= $num ?>" <?= ($bulanPilih === $num) ? 'selected' : '' ?>>
                                        <?= $nama ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered table-hover table-jurnal mb-0">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 7%;">
                            <col style="width: 6%;">
                            <col style="width: 25%;">
                            <col style="width: 22%;">
                            <col style="width: 17%;">
                            <col style="width: 8%;">
                            <col style="width: 5%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Hari / Tanggal</th>
                                <th>Kelas</th>
                                <th>Alokasi</th>
                                <th>Tujuan Pembelajaran (ATP)</th>
                                <th>Kegiatan Pembelajaran</th>
                                <th>Refleksi Pembelajaran</th>
                                <th>Siswa Tidak Hadir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($jurnalList)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-calendar-x d-block fs-3 mb-2"></i>
                                        Tidak ada alokasi tanggal penugasan ATP pada bulan <strong><?= $namaBulan ?></strong>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($jurnalList as $index => $row): ?>
                                <tr>
                                    <td class="text-center fw-bold text-dark bg-light"><?= $row['hari_tanggal'] ?></td>
                                    
                                    <td class="text-center align-middle fw-bold text-primary"><?= esc($row['kelas']) ?></td>
                                    
                                    <td class="text-center align-middle fw-bold"><?= esc($row['jp']) ?> JP</td>
                                    
                                    <td style="text-align: justify;"><?= nl2br(esc($row['tujuan_pembelajaran'])) ?></td>
                                    
                                    <td>
                                        <textarea id="kegiatan_<?= $index ?>" class="form-control textarea-custom" placeholder="Tuliskan ringkasan aktivitas pembelajaran..."><?= esc($row['kegiatan']) ?></textarea>
                                    </td>
                                    <td>
                                        <textarea id="refleksi_<?= $index ?>" class="form-control textarea-custom" placeholder="Evaluasi/catatan guru..."><?= esc($row['refleksi']) ?></textarea>
                                    </td>
                                    <td>
                                        <input type="text" id="absen_<?= $index ?>" class="form-control input-absen text-center" placeholder="Nama / -" value="<?= esc($row['absen']) ?>">
                                    </td>
                                    
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-success btn-sm btn-save-jurnal shadow-sm" 
                                                data-idx="<?= $index ?>" 
                                                data-atpid="<?= $row['atp_id'] ?>" 
                                                data-tanggal="<?= $row['tanggal_asli'] ?>"
                                                onclick="simpanJurnalBaris(this)">
                                            <i class="bi bi-save"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white py-2">
                    <small class="text-muted"><i class="bi bi-info-circle-fill me-1 text-info"></i> Isi kolom kegiatan, refleksi, atau siswa absen lalu klik tombol hijau di ujung kanan baris untuk menyimpan data.</small>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    async function simpanJurnalBaris(btn) {
        // Ambil data-attribute dari tombol yang diklik
        const idx = btn.getAttribute('data-idx');
        const atpId = btn.getAttribute('data-atpid');
        const tanggalAsli = btn.getAttribute('data-tanggal');

        // Ambil elemen inputan berdasarkan index baris
        const kegiatanVal = document.getElementById('kegiatan_' + idx).value;
        const refleksiVal = document.getElementById('refleksi_' + idx).value;
        const absenVal = document.getElementById('absen_' + idx).value;

        // Kunci tombol & ubah icon jadi spinner loading
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        btn.disabled = true;

        // Susun payload Form Data
        const formData = new FormData();
        formData.append('atp_id', atpId);
        formData.append('tanggal', tanggalAsli);
        formData.append('kegiatan', kegiatanVal);
        formData.append('refleksi', refleksiVal);
        formData.append('absen', absenVal);

        try {
            // Jalankan pengiriman data ke backend Controller
            const response = await fetch("<?= base_url('guru/jurnal-mengajar/simpan') ?>", {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                // Beri efek feedback sukses (Tombol berubah centang biru sejenak)
                btn.classList.replace('btn-success', 'btn-primary');
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                
                setTimeout(() => {
                    btn.classList.replace('btn-primary', 'btn-success');
                    btn.innerHTML = originalHtml;
                }, 1500);
            } else {
                alert("⚠️ Gagal menyimpan: " + result.message);
                btn.innerHTML = originalHtml;
            }
        } catch (error) {
            console.error(error);
            alert("❌ Terjadi kesalahan jaringan atau server.");
            btn.innerHTML = originalHtml;
        } finally {
            // Buka kembali kuncian tombol
            btn.disabled = false;
        }
    }
</script>
</body>
</html>