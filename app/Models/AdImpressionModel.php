<?php

namespace App\Models;

use CodeIgniter\Model;

class AdImpressionModel extends Model
{
    protected $table         = 'ad_impressions';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'id', 'ad_slot_id', 'event_type', 'ip_hash',
        'user_agent', 'referrer', 'metadata',
    ];

    /**
     * Record an ad event (click or impression).
     */
    public function recordEvent(string $adSlotId, string $eventType, ?string $ipHash = null, ?string $userAgent = null): void
    {
        $this->insert([
            'id'         => $this->generateUuid(),
            'ad_slot_id' => $adSlotId,
            'event_type' => $eventType,
            'ip_hash'    => $ipHash,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
        ]);
    }

    /**
     * Get impression/click counts for an ad.
     */
    public function getStats(string $adSlotId, ?string $from = null, ?string $to = null): array
    {
        $builder = $this->where('ad_slot_id', $adSlotId);

        if ($from) {
            $builder->where('created_at >=', $from);
        }
        if ($to) {
            $builder->where('created_at <=', $to);
        }

        $results = $builder->select('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->findAll();

        $stats = ['impressions' => 0, 'clicks' => 0];
        foreach ($results as $row) {
            $stats[$row['event_type'] . 's'] = (int) $row['count'];
        }

        return $stats;
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
