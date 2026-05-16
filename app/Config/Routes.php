<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', function() {
    return view('dashboard_test');
});

// PASTIKAN KODE GRUP MANUAL INI ADA DI BARIS PALING BAWAH:
$routes->group('', ['namespace' => 'CodeIgniter\Shield\Controllers'], static function ($routes) {
    $routes->get('register', 'RegisterController::registerView');
    $routes->post('register', 'RegisterController::registerAction');
    $routes->get('login', 'LoginController::loginView');
    $routes->post('login', 'LoginController::loginAction');
    $routes->get('logout', 'LoginController::logoutAction');
});
