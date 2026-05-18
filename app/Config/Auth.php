<?php

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    public bool $allowRegistration = false; 
    public bool $setupRoutes        = false; 

    public array $views = [
        // WAJIB MENGGUNAKAN HURUF 'A' KAPITAL DI DEPAN: App\Views\...
        'login'           => 'App\Views\Shield\login',
        'register'        => 'App\Views\Shield\register',
        'layout'          => 'App\Views\Shield\layout',
    ];
}
