<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Permission Matrix</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <style>
        /* Memaksa halaman menggunakan tinggi penuh layar */
        html, body, .app-wrapper {
            height: 100vh;
            overflow: hidden;
        }
        /* Mengatur form agar fleksibel mengisi sisa ruang */
        .matrix-form {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 110px); /* Potong tinggi header */
        }
        .matrix-card {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            overflow: hidden;
        }
        /* Membuat area tabel bisa di-scroll secara internal */
        .matrix-table-container {
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: auto;
        }
        /* Menjaga header tabel tetap membeku di atas saat di-scroll */
        .table-sticky-header th {
            position: sticky;
            top: 0;
            z-index: 2;
            background-color: #212529 !important; /* Warna table-dark */
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper p-3 d-flex flex-column">
        <div class="container-fluid d-flex flex-column h-100">
            
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-2 flex-shrink-0">
                <h3 class="mb-0" style="color: #FF9F00; font-weight: 700; font-size: 1.5rem;">🎯 Matriks Hak Akses Menu <span style="color: #FFC107;">SiKuMi</span></h3>
                <div>
                    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">⬅️ Ke Dashboard</a>
                </div>
            </div>

            <!-- ALERT NOTIFIKASI -->
            <?php if (session()->getFlashdata('sukses')): ?>
                <div class="alert alert-success shadow-sm p-2 mb-2 flex-shrink-0" role="alert" style="font-size: 0.9rem;">
                    🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                </div>
            <?php endif; ?>

            <!-- KARTU UTAMA -->
            <div class="card shadow-sm border-top border-warning border-3 matrix-card">
                <form action="<?= base_url('admin/permission-matrix/save') ?>" method="POST" class="matrix-form">
                    <?= csrf_field() ?>
                    
                    <!-- KONTEN TABEL DENGAN SCROLL INTERNAL -->
                    <div class="card-body p-0 matrix-table-container">
                        <table class="table table-bordered table-hover mb-0 align-middle table-sm">
                            <thead class="table-dark text-center table-sticky-header">
                                <tr>
                                    <th style="width: 40%; min-width: 250px;" class="text-start ps-4">Struktur Menu & Sub-Menu</th>
                                    <?php foreach ($roles as $r): ?>
                                        <th style="min-width: 100px;"><?= $r['role_title'] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menus as $mId => $mNode): ?>
                                    <!-- BARIS MENU INDUK -->
                                    <tr class="table-light font-weight-bold">
                                        <td class="ps-4 text-dark">
                                            <i class="<?= $mNode['induk']['icon'] ?> text-warning me-2"></i> 
                                            <strong><?= $mNode['induk']['permission_description'] ?></strong>
                                        </td>
                                        <?php foreach ($roles as $r): ?>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="matrix[<?= $r['id'] ?>][]" 
                                                       value="<?= $mNode['induk']['id'] ?>"
                                                       <?= (isset($matrixActive[$r['id']]) && in_array($mNode['induk']['id'], $matrixActive[$r['id']])) ? 'checked' : '' ?>>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>

                                    <!-- BARIS SUB-MENU ANAK -->
                                    <?php if (isset($mNode['anak'])): ?>
                                        <?php foreach ($mNode['anak'] as $sub): ?>
                                        <tr>
                                            <td class="ps-5 text-muted small">
                                                <i class="<?= $sub['icon'] ?> me-2"></i> <?= $sub['permission_description'] ?>
                                            </td>
                                            <?php foreach ($roles as $r): ?>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input" 
                                                           name="matrix[<?= $r['id'] ?>][]" 
                                                           value="<?= $sub['id'] ?>"
                                                           <?= (isset($matrixActive[$r['id']]) && in_array($sub['id'], $matrixActive[$r['id']])) ? 'checked' : '' ?>>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- FOOTER TETAP DI BAWAH -->
                    <div class="card-footer bg-white py-2 text-end flex-shrink-0">
                        <button type="submit" class="btn btn-warning text-white px-4 shadow-sm btn-sm" style="font-weight: 600;">
                            💾 Simpan Perubahan Matriks Akses
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
