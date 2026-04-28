<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserNotificationModel extends Model
{
    protected $table = 'portal_user_notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'event_key',
        'type',
        'title',
        'body',
        'url',
        'metadata_json',
        'is_read',
        'read_at',
        'created_at',
        'updated_at',
    ];

    public function findByUserAndEventKey(string $portalUserId, string $eventKey): ?array
    {
        return $this->where('portal_user_id', $portalUserId)
            ->where('event_key', $eventKey)
            ->first();
    }

    public function unreadCount(string $portalUserId): int
    {
        return $this->where('portal_user_id', $portalUserId)
            ->where('is_read', 0)
            ->countAllResults();
    }
}
