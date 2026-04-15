<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserSessionModel extends Model
{
    protected $table            = 'portal_user_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'refresh_token_hash',
        'ip_address',
        'user_agent',
        'expires_at',
        'revoked_at',
    ];

    public function findActiveByHash(string $hash): ?array
    {
        return $this->where('refresh_token_hash', $hash)
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function revokeById(string $id): bool
    {
        return $this->update($id, ['revoked_at' => date('Y-m-d H:i:s')]);
    }

    public function revokeAllForUser(string $portalUserId): void
    {
        $this->where('portal_user_id', $portalUserId)
            ->where('revoked_at IS NULL')
            ->set(['revoked_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    public function cleanExpired(): int
    {
        return $this->where('expires_at <', date('Y-m-d H:i:s'))->delete();
    }
}
