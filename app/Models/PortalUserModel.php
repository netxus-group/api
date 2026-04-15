<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserModel extends Model
{
    protected $table            = 'portal_users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'id',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'display_name',
        'avatar_url',
        'active',
        'last_login_at',
    ];

    public function findActiveByEmail(string $email): ?array
    {
        return $this->where('email', mb_strtolower(trim($email)))
            ->where('active', 1)
            ->first();
    }

    public function findPublicProfile(string $id): ?array
    {
        return $this->select('id, email, first_name, last_name, display_name, avatar_url, active, last_login_at, created_at, updated_at')
            ->where('id', $id)
            ->where('active', 1)
            ->first();
    }

    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        $builder = $this->where('email', mb_strtolower(trim($email)));

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return (bool) $builder->countAllResults();
    }
}
