<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Halaman Utama Aplikasi
// UBAH BARIS RUTE HALAMAN UTAMA ANDA MENJADI SEPERTI INI:
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

    $routes->get('academic', 'AdminController::academicSetting');
    $routes->post('academic/activate/(:num)', 'AdminController::activateAcademic/$1');

    $routes->post('users/reset-password/(:num)', 'AdminController::resetPassword/$1');
});

// ========================================================
// JALUR RUTE LOGIN GOOGLE SSO (SINGLE SIGN-ON) SIKUMI
// ========================================================
$routes->get('auth/google', 'GoogleAuthController::redirectToGoogle');
$routes->get('auth/google/callback', 'GoogleAuthController::handleCallback');

// Tambahkan ini di dalam kelompok rute yang memiliki filter akses login Anda
$routes->group('admin', ['filter' => 'session'], static function ($routes) {
    // ... rute Fase 1 yang sudah ada ...

    // JALUR RUTE FASE 2: MODUL KALENDER AKADEMIK (KALDIK) PER KELAS
    $routes->get('kaldik', 'KaldikController::index');
    $routes->post('kaldik/store', 'KaldikController::storeAgenda');
    $routes->post('kaldik/copy', 'KaldikController::copyKaldik');
});


