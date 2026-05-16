<?php

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;
use App\Models\CustomRoleModel;
use App\Models\CustomPermissionModel;

class AuthGroups extends ShieldAuthGroups
{
    public array $groups = [];
    public array $permissions = [];
    public array $matrix = [];
    
    // TAMBAHKAN BARIS INI untuk mengunci peran bawaan pendaftar baru
    public string $defaultGroup = 'guru_pengajar'; 

    public function __construct()
    {
        parent::__construct();

        if (db_connect()->tableExists('custom_roles')) {
            $roleModel = new CustomRoleModel();
            $permModel = new CustomPermissionModel();

            $this->groups      = $roleModel->getRolesForShield();
            $this->permissions = $permModel->getPermissionsForShield();
            $this->matrix      = $roleModel->getMatrixForShield();
        }
    }
}
