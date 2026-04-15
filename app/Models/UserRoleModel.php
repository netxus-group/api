<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table         = 'user_roles';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'role'];

    /**
     * Get roles for a user.
     */
    public function getUserRoles(string $userId): array
    {
        return array_column(
            $this->where('user_id', $userId)->findAll(),
            'role'
        );
    }

    /**
     * Sync roles for a user (delete old, insert new).
     */
    public function syncRoles(string $userId, array $roles): void
    {
        $this->where('user_id', $userId)->delete();

        foreach (array_unique($roles) as $role) {
            $this->insert([
                'user_id' => $userId,
                'role'    => $role,
            ]);
        }
    }
}
