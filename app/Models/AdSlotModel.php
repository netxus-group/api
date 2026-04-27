<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\AdSlot;

class AdSlotModel extends Model
{
    protected $table         = 'ad_slots';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = AdSlot::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'placement', 'type', 'name', 'content',
        'target_url', 'active', 'starts_at', 'ends_at',
    ];

    protected array $casts = [
        'active'  => 'boolean',
        'content' => 'json-array',
    ];

    /**
     * Get active ads by placement.
     */
    public function getByPlacement(string $placement): array
    {
        return $this->baseActiveQuery()
            ->where('placement', $placement)
            ->findAll();
    }

    /**
     * Get all active ads grouped by placement.
     */
    public function getAllActiveGrouped(): array
    {
        $ads = $this->baseActiveQuery()->orderBy('placement')->findAll();
        $grouped = [];
        foreach ($ads as $ad) {
            $grouped[$ad->placement][] = $ad;
        }
        return $grouped;
    }

    private function baseActiveQuery()
    {
        $now = date('Y-m-d H:i:s');

        return $this->where('active', 1)
            ->groupStart()
                ->where('starts_at IS NULL')
                ->orWhere('starts_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('ends_at IS NULL')
                ->orWhere('ends_at >=', $now)
            ->groupEnd();
    }
}
