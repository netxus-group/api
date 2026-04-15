<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserRecommendationScoreModel extends Model
{
    protected $table            = 'portal_user_recommendation_scores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'news_id',
        'score',
        'rank_position',
        'components',
        'calculated_at',
        'expires_at',
    ];

    public function listFreshForUser(string $portalUserId, int $limit): array
    {
        return $this->where('portal_user_id', $portalUserId)
            ->groupStart()
                ->where('expires_at IS NULL')
                ->orWhere('expires_at >', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('rank_position', 'ASC')
            ->limit($limit)
            ->findAll();
    }

    public function clearForUser(string $portalUserId): void
    {
        $this->where('portal_user_id', $portalUserId)->delete();
    }
}
