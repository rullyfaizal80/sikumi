<?php

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    public bool $allowRegistration = true; 
    
    // TAMBAHKAN BARIS INI untuk menyerahkan kendali rute ke file Routes.php Anda
    public bool $setupRoutes = false; 

    public array $views = [
        'login'           => '\CodeIgniter\Shield\Views\login',
        'register'        => '\CodeIgniter\Shield\Views\register',
        'layout'          => '\CodeIgniter\Shield\Views\layout',
    ];
}
