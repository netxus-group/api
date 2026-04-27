<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = User::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id', 'email', 'password_hash', 'role',
        'first_name', 'last_name', 'username',
        'active', 'last_login_at',
    ];

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find active user by ID with roles and permissions.
     */
    public function findWithAccess(string $id): ?array
    {
        $user = $this->find($id);
        if (!$user) {
            return null;
        }

        $userData = $user->toPublicArray();

        $roleModel = new UserRoleModel();
        $userData['roles'] = $roleModel->getUserRoles($id);

        $permModel = new UserSpecialPermissionModel();
        $userData['specialPermissions'] = $permModel->getUserPermissions($id);

        return $userData;
    }

    /**
     * List all users with basic access info.
     */
    public function listAll(): array
    {
        $users = $this->orderBy('created_at', 'DESC')->findAll();
        $result = [];

        foreach ($users as $user) {
            $data = $user->toPublicArray();
            $roleModel = new UserRoleModel();
            $data['roles'] = $roleModel->getUserRoles($user->id);
            $result[] = $data;
        }

        return $result;
    }
}
