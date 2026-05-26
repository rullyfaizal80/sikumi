<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =========================================================================
// RUTE UTAMA & AUTENTIKASI
// =========================================================================
$routes->get('/', 'Home::index', ['as' => 'dashboard', 'filter' => 'session']);

$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');

// Panggil rute otomatis bawaan CodeIgniter Shield secara resmi
service('auth')->routes($routes);

// Rute Halaman Diblokir (Tanpa membuat controller baru)
$routes->get('blocked', function() {
    return view('403_kustom'); 
});

// =========================================================================
// JALUR KHUSUS HALAMAN ADMIN (GRUP TERPUSAT)
// =========================================================================
$routes->group('admin', static function ($routes) {
    
    // --- MANAJEMEN PENGGUNA & HAK AKSES ---
    $routes->get('users', 'AdminController::index');
    $routes->post('users/store', 'AdminController::storeUser');
    $routes->post('users/update-roles', 'AdminController::updateUserRoles');
    $routes->post('users/reset-password/(:num)', 'AdminController::resetPassword/$1');

    $routes->post('roles/store', 'AdminController::storeRole');
    $routes->post('users/role-update/(:num)', 'AdminController::updateRole/$1');
    $routes->get('users/role-delete/(:num)', 'AdminController::deleteRole/$1'); // Note: Rentan CSRF, biarkan GET agar View tetap jalan

    $routes->get('permission-matrix', 'AdminController::permissionMatrix');
    $routes->post('permission-matrix/save', 'AdminController::saveMatrix');

    // --- PUSAT PENGATURAN TERPADU SIKUMI (SATU JENDELA) ---
    $routes->get('settings', 'AdminController::appSettings');
    $routes->post('settings/save', 'AdminController::saveSettings');
    $routes->post('settings/add-angkatan', 'AdminController::addAngkatan');
    $routes->post('settings/delete-angkatan', 'AdminController::deleteAngkatan'); 
    
    // SINKRONISASI TAB 4: Mengaktifkan Semester Berjalan
    $routes->get('academic/activate/(:num)', 'AdminController::activateAcademic/$1');

    // --- KALENDER PENDIDIKAN (KALDIK) ---
    $routes->get('kaldik', 'KaldikController::index');
    $routes->post('kaldik/store', 'KaldikController::storeAgenda');
    $routes->post('kaldik/copy', 'KaldikController::copyKaldik');
    $routes->post('kaldik/update', 'KaldikController::updateAgenda');
    $routes->post('kaldik/delete/(:num)', 'KaldikController::deleteAgenda/$1');
    $routes->get('kaldik/print', 'KaldikController::printKaldik');

    // --- MANAJEMEN GURU ---
    $routes->get('users/guru-tes', 'UserGuruController::index');
    $routes->post('users/guru-store', 'UserGuruController::storeGuru');
    $routes->post('users/guru-update/(:num)', 'UserGuruController::updateGuru/$1');
    $routes->get('users/guru-toggle/(:num)', 'UserGuruController::toggleStatus/$1');
    
    $routes->get('users/guru-delete/(:num)', 'UserGuruController::deleteGuru/$1');
    $routes->get('users/guru-trash', 'UserGuruController::trashGuru');
    $routes->get('users/guru-restore/(:num)', 'UserGuruController::restoreGuru/$1');
    
    $routes->post('users/guru-update-history/(:num)', 'UserGuruController::updateHistory/$1');
    $routes->get('users/guru-delete-history/(:num)', 'UserGuruController::deleteHistory/$1');

    // --- MANAJEMEN SISWA ---
    $routes->get('users/siswa-tes', 'UserSiswaController::index');
    $routes->post('users/siswa-store', 'UserSiswaController::storeSiswa');
    $routes->post('users/siswa-update/(:num)', 'UserSiswaController::updateSiswa/$1');
    $routes->get('users/siswa-toggle/(:num)', 'UserSiswaController::toggleStatus/$1');

    $routes->get('users/siswa-delete/(:num)', 'UserSiswaController::deleteSiswa/$1');
    $routes->get('users/siswa-trash', 'UserSiswaController::trashSiswa');
    $routes->get('users/siswa-restore/(:num)', 'UserSiswaController::restoreSiswa/$1');

    // --- MASTER DATA ---
    $routes->get('master-data', 'MasterDataController::index');
    
    // Master Data Mata Pelajaran
    $routes->post('master-data/subject-store', 'MasterDataController::storeSubject');
    $routes->post('master-data/subject-update/(:num)', 'MasterDataController::updateSubject/$1');
    $routes->get('master-data/subject-delete/(:num)', 'MasterDataController::deleteSubject/$1');

    // Master Data Kelas
    $routes->post('master-data/class-store', 'MasterDataController::storeClass');
    $routes->post('master-data/class-update/(:num)', 'MasterDataController::updateClass/$1');
    $routes->get('master-data/class-delete/(:num)', 'MasterDataController::deleteClass/$1');

    $routes->get('rombel', 'RombelController::index');
    $routes->post('rombel/store', 'RombelController::store');
    $routes->post('rombel/update/(:num)', 'RombelController::update/$1');
    $routes->post('rombel/plot-store', 'RombelController::plotStore');
    $routes->get('rombel/plot-delete/(:num)', 'RombelController::plotDelete/$1');
    $routes->post('rombel/copy', 'RombelController::copySemester');
    $routes->get('rombel/delete/(:num)', 'RombelController::delete/$1');

    $routes->get('rombel/siswa/(:num)', 'RombelSiswaController::manage/$1');
    $routes->post('rombel/siswa/add', 'RombelSiswaController::add');
    $routes->post('rombel/siswa/remove', 'RombelSiswaController::remove');
});
