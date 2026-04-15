<?php

namespace App\Models;

use CodeIgniter\Model;

class IntegrationSnapshotModel extends Model
{
    protected $table         = 'integration_snapshots';
    protected $primaryKey    = 'provider';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'provider', 'payload', 'fetched_at', 'ttl_seconds',
    ];

    protected $casts = [
        'payload' => 'json-array',
    ];

    /**
     * Get cached snapshot if still valid.
     */
    public function getValidSnapshot(string $provider): ?array
    {
        $snapshot = $this->find($provider);
        if (!$snapshot) {
            return null;
        }

        $fetchedAt = strtotime($snapshot['fetched_at']);
        $ttl       = (int) ($snapshot['ttl_seconds'] ?? 300);

        if (time() - $fetchedAt > $ttl) {
            return null; // expired
        }

        return $snapshot['payload'];
    }

    /**
     * Update or insert a snapshot.
     */
    public function upsert(string $provider, array $payload, int $ttlSeconds): void
    {
        $existing = $this->find($provider);

        $data = [
            'provider'    => $provider,
            'payload'     => json_encode($payload),
            'fetched_at'  => date('Y-m-d H:i:s'),
            'ttl_seconds' => $ttlSeconds,
        ];

        if ($existing) {
            $this->update($provider, $data);
        } else {
            $this->insert($data);
        }
    }

    /**
     * Get last snapshot even if expired (fallback).
     */
    public function getFallbackSnapshot(string $provider): ?array
    {
        $snapshot = $this->find($provider);
        return $snapshot ? $snapshot['payload'] : null;
    }
}
