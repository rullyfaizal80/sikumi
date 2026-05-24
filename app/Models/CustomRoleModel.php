<?php
namespace App\Models;
use CodeIgniter\Model;

class CustomRoleModel extends Model
{
    protected $table            = 'custom_roles';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['role_name', 'role_title', 'created_at', 'updated_at'];

    public function getRolesForShield(): array
    {
        $roles = $this->findAll();
        $formatShield = [];
        foreach ($roles as $role) {
            $formatShield[$role['role_name']] = [
                'title'       => $role['role_title'],
                'description' => 'Grup pengguna dinamis sistem.',
            ];
        }
        return $formatShield;
    }

    public function getMatrixForShield(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('custom_roles_permissions crp');
        $builder->select('cr.role_name, cp.permission_name');
        $builder->join('custom_roles cr', 'cr.id = crp.role_id');
        $builder->join('custom_permissions cp', 'cp.id = crp.permission_id');
        
        // Eksekusi HANYA 1 kali saja
        $results = $builder->get()->getResultArray();

        $matrix = [];
        foreach ($results as $row) {
            $groupName = $row['role_name'];
            $permissionName = $row['permission_name'];

            if (!isset($matrix[$groupName])) {
                $matrix[$groupName] = [];
            }

            // Masukkan permission ke dalam groupnya masing-masing
            $matrix[$groupName][] = $permissionName;
        }

        return $matrix;
    }
}