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

    protected $casts = [
        'active'  => 'boolean',
        'content' => 'json-array',
    ];

    /**
     * Get active ads by placement.
     */
    public function getByPlacement(string $placement): array
    {
        $now = date('Y-m-d H:i:s');

        return $this->where('active', 1)
            ->where('placement', $placement)
            ->groupStart()
                ->where('starts_at IS NULL')
                ->orWhere('starts_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('ends_at IS NULL')
                ->orWhere('ends_at >=', $now)
            ->groupEnd()
            ->findAll();
    }

    /**
     * Get all active ads grouped by placement.
     */
    public function getAllActiveGrouped(): array
    {
        $ads = $this->where('active', 1)->orderBy('placement')->findAll();
        $grouped = [];
        foreach ($ads as $ad) {
            $grouped[$ad->placement][] = $ad;
        }
        return $grouped;
    }
}
