<?php

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    public bool $allowRegistration = false; 
    public bool $setupRoutes       = false; 

    public array $views = [
        // UBAH MENJADI SEPERTI DI BAWAH INI (Menggunakan format slash Unix/Linux)
        'login'           => 'CodeIgniter\Shield\Views\login',
        'register'        => 'CodeIgniter\Shield\Views\register',
        'layout'          => 'CodeIgniter\Shield\Views\layout',
    ];
}
