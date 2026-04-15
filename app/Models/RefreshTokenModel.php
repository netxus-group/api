<?php

namespace App\Models;

use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $table         = 'refresh_tokens';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'id', 'user_id', 'token_hash', 'expires_at', 'revoked_at', 'created_at',
    ];

    /**
     * Find an active (non-revoked, non-expired) token by hash.
     */
    public function findActiveByHash(string $hash): ?array
    {
        return $this->where('token_hash', $hash)
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Revoke a token.
     */
    public function revoke(string $id): bool
    {
        return $this->update($id, ['revoked_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllForUser(string $userId): void
    {
        $this->where('user_id', $userId)
            ->where('revoked_at IS NULL')
            ->set(['revoked_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete();
    }
}
