<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserInteractionModel extends Model
{
    protected $table            = 'portal_user_interactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'news_id',
        'category_id',
        'tag_id',
        'author_id',
        'action',
        'context',
        'time_spent_seconds',
        'score_delta',
        'metadata',
        'created_at',
    ];

    public function getRecentByUser(string $portalUserId, int $days = 30): array
    {
        $from = date('Y-m-d H:i:s', time() - ($days * 86400));

        return $this->where('portal_user_id', $portalUserId)
            ->where('created_at >=', $from)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function getPostViewCount(string $portalUserId, string $newsId): int
    {
        return (int) $this->where('portal_user_id', $portalUserId)
            ->where('news_id', $newsId)
            ->where('action', 'view_post')
            ->countAllResults();
    }
}
