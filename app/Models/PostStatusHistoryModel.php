<?php

namespace App\Models;

use CodeIgniter\Model;

class PostStatusHistoryModel extends Model
{
    protected $table         = 'post_status_history';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'id', 'news_id', 'from_status', 'to_status',
        'changed_by', 'comment',
    ];

    /**
     * Log a status change.
     */
    public function logTransition(string $newsId, ?string $from, string $to, string $userId, ?string $comment = null): void
    {
        $this->insert([
            'id'          => $this->generateUuid(),
            'news_id'     => $newsId,
            'from_status' => $from,
            'to_status'   => $to,
            'changed_by'  => $userId,
            'comment'     => $comment,
        ]);
    }

    /**
     * Get history for a news article.
     */
    public function getHistory(string $newsId): array
    {
        return $this->where('news_id', $newsId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
