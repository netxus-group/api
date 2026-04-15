<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserPasswordResetModel extends Model
{
    protected $table            = 'portal_user_password_resets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'created_at',
    ];

    public function findValidByTokenHash(string $hash): ?array
    {
        return $this->where('token_hash', $hash)
            ->where('used_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }
}
