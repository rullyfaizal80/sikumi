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
<div class="wrapper p-4"> <div class="content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="font-weight-bold mb-1" style="color: #FF9F00;">📖 Jurnal Mengajar Guru</h3>
                    <p class="text-muted mb-0">Catatan Aktivitas, Refleksi, dan Kehadiran Harian</p>
                </div>               
                <div>
                    <a href="<?= base_url('jurnal-mengajar/print?mapel_id=' . urlencode($selectedMapelId ?? '') . '&bulan=' . ($bulanPilih ?? date('m'))) ?>" 
   target="_blank" 
   class="btn btn-outline-danger btn-sm font-weight-bold shadow-sm px-3 me-2">
    🖨️ Cetak Jurnal
</a>
                    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm font-weight-bold shadow-sm">🏠 Dashboard</a>
                </div>
            </div>
            <div class="card card-outline jurnal-card shadow-sm border-0 rounded-lg mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
                    <div>
                        <h5 class="mb-0 font-weight-bold text-dark">
                            <i class="bi bi-journal-check text-primary me-2"></i> Form Pengisian Jurnal
                        </h5>
                        <small class="text-muted">Periode Bulan: <strong><?= $namaBulan ?></strong></small>
                    </div>
                    
                    <div class="ms-auto">
                        <form method="GET" action="<?= current_url() ?>" class="d-flex align-items-center gap-3">
                            
                            <div class="d-flex align-items-center me-3">
                                <label class="small font-weight-bold text-secondary mb-0 me-2">Mapel:</label>
                                <select name="mapel_id" class="form-select form-select-sm font-weight-bold text-primary border-primary" style="width: 220px;" onchange="this.form.submit()">
                                    <option value="">- Semua Mata Pelajaran -</option>
                                    <?php if(!empty($daftarMapel)): ?>
                                        <?php foreach($daftarMapel as $m): ?>
                                            <option value="<?= esc($m['id']) ?>" <?= ($selectedMapelId == $m['id']) ? 'selected' : '' ?>>
                                                <?= esc($m['subject_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="d-flex align-items-center">
                                <label class="small font-weight-bold text-secondary mb-0 me-2">Bulan:</label>
                                <select name="bulan" class="form-select form-select-sm font-weight-bold text-primary border-primary" style="width: 130px;" onchange="this.form.submit()">
    <?php foreach($listBulan as $num => $nama): ?>
        <option value="<?= $num ?>" <?= ($bulanPilih == $num) ? 'selected' : '' ?>>
            <?= $nama ?>
        </option>
    <?php endforeach; ?>
</select>
                            </div>

                        </form>
                    </div>
                </div>

                <div class="card-body p-0 table-responsive">
                    <table class="table table-bordered table-hover table-jurnal mb-0">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 6%;">
                            <col style="width: 23%;">
                            <col style="width: 23%;">
                            <col style="width: 23%;">
                            <col style="width: 10%;">
                            <col style="width: 5%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Hari / Tanggal</th>
                                <th>Kelas</th>
                                <th>Tujuan Pembelajaran (ATP)</th>
                                <th>Kegiatan Pembelajaran</th>
                                <th>Refleksi Pembelajaran</th>
                                <th>Murid Tidak Hadir</th>
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
                                    <td style="text-align: justify;"><?= nl2br(esc($row['tujuan_pembelajaran'])) ?></td>
                                    
                                   <td>
        <textarea id="kegiatan_<?= $row['atp_id'] ?>_<?= $row['tanggal_asli'] ?>" class="form-control textarea-custom" rows="3" placeholder="Tuliskan ringkasan aktivitas pembelajaran..."><?= esc($row['kegiatan']) ?></textarea>
    </td>
    
    <td>
        <textarea id="refleksi_<?= $row['atp_id'] ?>_<?= $row['tanggal_asli'] ?>" class="form-control textarea-custom" rows="3" placeholder="Evaluasi/catatan guru..."><?= esc($row['refleksi']) ?></textarea>
    </td>

    <td>
        <textarea id="absen_<?= $row['atp_id'] ?>_<?= $row['tanggal_asli'] ?>" class="form-control textarea-custom" rows="3" placeholder="Murid tidak hadir..."><?= esc($row['absen']) ?></textarea>
    </td>
    
    <td class="text-center align-middle">
        <button type="button" 
                class="btn btn-sm btn-light border shadow-sm" style="font-size: 16px;"
                id="btn_<?= $row['atp_id'] ?>_<?= $row['tanggal_asli'] ?>"
                data-atp="<?= $row['atp_id'] ?>" 
                data-tanggal="<?= $row['tanggal_asli'] ?>"
                onclick="simpanJurnal(this)"
                title="Simpan Data">
            💾
        </button>
    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white py-2">
                    <small class="text-muted"><i class="bi bi-info-circle-fill me-1 text-info"></i> Isi kolom kegiatan, refleksi, atau siswa absen lalu klik tombol 💾 di ujung kanan baris untuk menyimpan data.</small>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    async function simpanJurnal(btn) {
        const atpId = btn.getAttribute('data-atp');
        const tanggalAsli = btn.getAttribute('data-tanggal');

        // Ambil nilai dari inputan
        const kegiatanVal = document.getElementById(`kegiatan_${atpId}_${tanggalAsli}`).value;
        const refleksiVal = document.getElementById(`refleksi_${atpId}_${tanggalAsli}`).value;
        const absenVal = document.getElementById(`absen_${atpId}_${tanggalAsli}`).value; // Sesuai ID kolom murid tidak hadir

        // Simpan emoji asli dan ubah ke emoji loading
        const originalHtml = '💾';
        btn.innerHTML = '⏳';
        btn.disabled = true; // Kunci tombol agar tidak di-klik ganda

        const formData = new FormData();
        formData.append('atp_id', atpId);
        formData.append('tanggal', tanggalAsli);
        formData.append('kegiatan', kegiatanVal);
        formData.append('refleksi', refleksiVal);
        formData.append('absen', absenVal);

        try {
            // Jalankan pengiriman data ke backend Controller
            const response = await fetch("<?= base_url('jurnal-mengajar/simpan') ?>", {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                // Ubah menjadi Centang Hijau
                btn.innerHTML = '✅';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 1500);
            } else {
                alert("⚠️ Gagal menyimpan: " + result.message);
                btn.innerHTML = '❌';
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }, 1500);
            }
        } catch (error) {
            console.error(error);
            alert("❌ Terjadi kesalahan jaringan atau server.");
            btn.innerHTML = '❌';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }, 1500);
        }
    }
</script>
</body>
</html>