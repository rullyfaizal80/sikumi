<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Halaman Utama Aplikasi
$routes->get('/', function() {
    return view('dashboard_test');
});

// Panggil rute otomatis bawaan CodeIgniter Shield secara resmi
service('auth')->routes($routes);

// Jalur Khusus Halaman Admin Ruang Kontrol Utama
$routes->group('admin', ['filter' => 'session'], static function ($routes) {
    $routes->get('users', 'AdminController::index');
    $routes->post('roles/store', 'AdminController::storeRole');
    $routes->post('users/update-roles', 'AdminController::updateUserRoles');

    $routes->get('permission-matrix', 'AdminController::permissionMatrix');
    $routes->post('permission-matrix/save', 'AdminController::saveMatrix');
});


