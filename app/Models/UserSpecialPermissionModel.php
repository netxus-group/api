<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSpecialPermissionModel extends Model
{
    protected $table         = 'user_special_permissions';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'permission'];

    /**
     * Get all special permissions for a user.
     */
    public function getUserPermissions(string $userId): array
    {
        return array_column(
            $this->where('user_id', $userId)->findAll(),
            'permission'
        );
    }

    /**
     * Sync permissions for a user.
     */
    public function syncPermissions(string $userId, array $permissions): void
    {
        $this->where('user_id', $userId)->delete();

        foreach (array_unique($permissions) as $perm) {
            $this->insert([
                'user_id'    => $userId,
                'permission' => $perm,
            ]);
        }
    }
}
