<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Halaman Utama Aplikasi
$routes->get('/', 'Home::index', ['as' => 'dashboard', 'filter' => 'session']);

// Contoh memproteksi halaman login agar dilempar ke dashboard jika sudah login
$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');

// Panggil rute otomatis bawaan CodeIgniter Shield secara resmi
service('auth')->routes($routes);

// Jalur Khusus Halaman Admin Ruang Kontrol Utama
$routes->group('admin', ['filter' => 'session'], static function ($routes) {
    $routes->get('users', 'AdminController::index');
    $routes->post('users/store', 'AdminController::storeUser');
    $routes->post('roles/store', 'AdminController::storeRole');
    $routes->post('users/update-roles', 'AdminController::updateUserRoles');

    $routes->get('permission-matrix', 'AdminController::permissionMatrix');
    $routes->post('permission-matrix/save', 'AdminController::saveMatrix');

    // =========================================================================
    // KODE BARU: PUSAT PENGATURAN TERPADU SIKUMI (SATU JENDELA)
    // =========================================================================
    // Menggantikan rute 'academic' lama agar langsung masuk ke jendela 5 Tab
    $routes->get('settings', 'AdminController::appSettings');
    $routes->post('settings/save', 'AdminController::saveSettings');
    $routes->post('settings/add-angkatan', 'AdminController::addAngkatan');
    $routes->post('settings/delete-angkatan', 'AdminController::deleteAngkatan'); // <- TAMBAHKAN BARIS INI
    
    // SINKRONISASI TAB 4: Mengaktifkan Semester Berjalan
    // Diubah menjadi GET agar sinkron dengan tag <a> pada elemen tombol di View
    $routes->get('academic/activate/(:num)', 'AdminController::activateAcademic/$1');
    // =========================================================================

    $routes->post('users/reset-password/(:num)', 'AdminController::resetPassword/$1');

    $routes->get('kaldik', 'KaldikController::index');
    $routes->post('kaldik/store', 'KaldikController::storeAgenda');
    $routes->post('kaldik/copy', 'KaldikController::copyKaldik');

    $routes->post('kaldik/update', 'KaldikController::updateAgenda');
    $routes->post('kaldik/delete/(:num)', 'KaldikController::deleteAgenda/$1');

    $routes->get('kaldik/print', 'KaldikController::printKaldik');
});

$routes->get('blocked', function() {
    return view('403_kustom'); // Mengarah ke app/Views/403_kustom.php
});

$routes->group('admin', ['filter' => 'session'], function($routes) {
    // ... rute lama Anda ...
    $routes->get('users/guru-tes', 'UserGuruController::index');
    $routes->get('users/siswa-tes', 'UserSiswaController::index');

    $routes->post('users/guru-store', 'UserGuruController::storeGuru');
    $routes->post('users/siswa-store', 'UserSiswaController::storeSiswa');

    // RUTE BARU UNTUK SAKLAR STATUS LOGIN (Menerima ID)
    $routes->get('users/guru-toggle/(:num)', 'UserGuruController::toggleStatus/$1');
    $routes->get('users/siswa-toggle/(:num)', 'UserSiswaController::toggleStatus/$1');

    $routes->get('users/guru-delete/(:num)', 'UserGuruController::deleteGuru/$1');
    $routes->post('users/guru-update/(:num)', 'UserGuruController::updateGuru/$1');

    $routes->post('users/guru-update-history/(:num)', 'UserGuruController::updateHistory/$1');
    $routes->get('users/guru-delete-history/(:num)', 'UserGuruController::deleteHistory/$1');

    $routes->get('users/guru-trash', 'UserGuruController::trashGuru');
    $routes->get('users/guru-restore/(:num)', 'UserGuruController::restoreGuru/$1');

    // Tambahkan ini di dalam grup rute admin Anda
    $routes->get('users/siswa-trash', 'UserSiswaController::trashSiswa');
    $routes->get('users/siswa-restore/(:num)', 'UserSiswaController::restoreSiswa/$1');
    $routes->get('users/siswa-delete/(:num)', 'UserSiswaController::deleteSiswa/$1'); // Untuk trigger masuk trash

    $routes->post('users/siswa-update/(:num)', 'UserSiswaController::updateSiswa/$1');
    $routes->post('users/siswa-add-history/(:num)', 'UserSiswaController::addHistorySiswa/$1');
    $routes->post('users/siswa-update-history/(:num)', 'UserSiswaController::updateHistorySiswa/$1');
    $routes->get('users/siswa-delete-history/(:num)', 'UserSiswaController::deleteHistorySiswa/$1');

    $routes->get('users/siswa-trash', 'UserSiswaController::trashSiswa');
    $routes->get('users/siswa-restore/(:num)', 'UserSiswaController::restoreSiswa/$1');
});
