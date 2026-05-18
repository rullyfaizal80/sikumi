<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Permission Matrix</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper p-4">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 style="color: #FF9F00; font-weight: 700;">🎯 Matriks Hak Akses Menu <span style="color: #FFC107;">SiKuMi</span></h3>
                <div>
                    <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary btn-sm me-2">👥 Kelola Pengguna</a>
                    <a href="<?= base_url('/') ?>" class="btn btn-secondary btn-sm">⬅️ Ke Dashboard</a>
                </div>
            </div>

            <?php if (session()->getFlashdata('sukses')): ?>
                <div class="alert alert-success shadow-sm mb-4" role="alert">
                    🎉 <strong>Berhasil!</strong> <?= session()->getFlashdata('sukses') ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-top border-warning border-3">
                <form action="<?= base_url('admin/permission-matrix/save') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0 align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th style="width: 40%;" class="text-start ps-4">Struktur Menu & Sub-Menu Berjenjang</th>
                                        <?php foreach ($roles as $r): ?>
                                            <th><?= $r['role_title'] ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menus as $mId => $mNode): ?>
                                        <!-- BARIS MENU INDUK / UTAMA (FOLDER) -->
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

                                        <!-- BARIS SUB-MENU ANAK (DI BAWAHNYA) -->
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
                    </div>
                    <div class="card-footer bg-white py-3 text-end">
                        <button type="submit" class="btn btn-warning text-white px-4 shadow-sm" style="font-weight: 600;">
                            💾 Simpan Perubahan Matriks Akses
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</body>
</html>
