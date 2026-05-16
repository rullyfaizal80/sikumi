<?php

namespace Config;

use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    public array $views = [
        'login'           => '\CodeIgniter\Shield\Views\login',
        'register'        => '\CodeIgniter\Shield\Views\register',
        'layout'          => '\CodeIgniter\Shield\Views\layout',
    ];
}
