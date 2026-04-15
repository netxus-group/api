<?php

namespace App\Models;

use CodeIgniter\Model;

class EngagementEventModel extends Model
{
    protected $table         = 'engagement_events';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'id', 'event_type', 'news_id', 'source', 'metadata',
    ];

    /**
     * Track a view or click event.
     */
    public function track(string $eventType, ?string $newsId, ?string $source = null, ?array $metadata = null): void
    {
        $this->insert([
            'id'         => $this->generateUuid(),
            'event_type' => $eventType,
            'news_id'    => $newsId,
            'source'     => $source,
            'metadata'   => $metadata ? json_encode($metadata) : null,
        ]);
    }

    /**
     * Get engagement summary for a date range.
     */
    public function getSummary(string $from, string $to): array
    {
        $results = $this->select('event_type, COUNT(*) as count')
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->groupBy('event_type')
            ->findAll();

        $summary = ['view' => 0, 'click' => 0];
        foreach ($results as $row) {
            $summary[$row['event_type']] = (int) $row['count'];
        }

        return $summary;
    }

    /**
     * Get top content by engagement.
     */
    public function getTopContent(string $eventType, int $limit, string $from, string $to): array
    {
        return $this->select('news_id, COUNT(*) as count')
            ->where('event_type', $eventType)
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->where('news_id IS NOT NULL')
            ->groupBy('news_id')
            ->orderBy('count', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get daily engagement series.
     */
    public function getDailySeries(string $from, string $to): array
    {
        return $this->select('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->groupBy('DATE(created_at), event_type')
            ->orderBy('date', 'ASC')
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
