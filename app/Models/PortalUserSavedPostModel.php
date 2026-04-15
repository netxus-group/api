<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserSavedPostModel extends Model
{
    protected $table            = 'portal_user_saved_posts';
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
        'portal_user_id',
        'news_id',
        'note',
        'saved_at',
        'read_at',
        'deleted_at',
    ];

    public function listActiveForUser(string $portalUserId): array
    {
        return $this->where('portal_user_id', $portalUserId)
            ->where('deleted_at IS NULL')
            ->orderBy('saved_at', 'DESC')
            ->findAll();
    }

    public function findAnyByUserAndPost(string $portalUserId, string $newsId): ?array
    {
        return $this->withDeleted()
            ->where('portal_user_id', $portalUserId)
            ->where('news_id', $newsId)
            ->first();
    }
}
