<?php
namespace App\Models;
use CodeIgniter\Model;

class CustomPermissionModel extends Model
{
    protected $table            = 'custom_permissions';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['permission_name', 'permission_description'];

    public function getPermissionsForShield(): array
    {
        $permissions = $this->findAll();
        $formatShield = [];
        foreach ($permissions as $perm) {
            $formatShield[$perm['permission_name']] = $perm['permission_description'];
        }
        return $formatShield;
    }
}
