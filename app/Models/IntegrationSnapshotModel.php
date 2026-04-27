<?php

namespace App\Models;

use CodeIgniter\Model;

class IntegrationSnapshotModel extends Model
{
    protected $table         = 'integration_snapshots';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id', 'provider', 'integration_key', 'payload', 'data', 'fetched_at', 'expires_at',
        'ttl_seconds', 'is_fallback', 'status', 'error_message', 'refresh_lock_until',
    ];

    protected array $casts = [
        'payload' => '?json-array',
        'data'    => '?json-array',
    ];

    private ?array $tableColumns = null;

    /**
     * Get cached snapshot if still valid.
     */
    public function getValidSnapshot(string $provider, int $ttlSeconds = 300): ?array
    {
        $snapshot = $this->findByProvider($provider);
        if (!$snapshot) {
            return null;
        }

        $expiresAtRaw = (string) ($snapshot['expires_at'] ?? '');
        $expiresAt = $expiresAtRaw !== '' ? strtotime($expiresAtRaw) : false;

        if ($expiresAt !== false) {
            if (time() >= $expiresAt) {
                return null;
            }
        } else {
            $fetchedAt = strtotime((string) ($snapshot['fetched_at'] ?? ''));
            $ttl = (int) ($snapshot['ttl_seconds'] ?? $ttlSeconds);
            if ($ttl <= 0) {
                $ttl = $ttlSeconds;
            }

            if ($fetchedAt === false || time() - $fetchedAt > $ttl) {
                return null; // expired
            }
        }

        return $this->extractPayload($snapshot);
    }

    /**
     * Update or insert a snapshot.
     */
    public function upsert(string $provider, array $payload, int $ttlSeconds): void
    {
        $existing = $this->findByProvider($provider);
        $columns = $this->getTableColumns();
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $data = [
            'provider'   => $provider,
            'integration_key' => $provider,
            'fetched_at' => date('Y-m-d H:i:s'),
        ];

        if (in_array('payload', $columns, true)) {
            $data['payload'] = $encodedPayload;
        }
        if (in_array('data', $columns, true)) {
            $data['data'] = $encodedPayload;
        }
        if (in_array('ttl_seconds', $columns, true)) {
            $data['ttl_seconds'] = $ttlSeconds;
        }
        if (in_array('expires_at', $columns, true)) {
            $data['expires_at'] = date('Y-m-d H:i:s', time() + max(60, $ttlSeconds));
        }
        if (in_array('is_fallback', $columns, true)) {
            $data['is_fallback'] = 0;
        }
        if (in_array('status', $columns, true)) {
            $data['status'] = 'ok';
        }
        if (in_array('error_message', $columns, true)) {
            $data['error_message'] = null;
        }
        if (in_array('refresh_lock_until', $columns, true)) {
            $data['refresh_lock_until'] = null;
        }
        if (!$existing && in_array('id', $columns, true)) {
            $data['id'] = $this->generateUuid();
        }

        $builder = $this->builder();
        if ($existing) {
            $builder->where('provider', $provider)->update($data);
        } else {
            $builder->insert($data);
        }
    }

    /**
     * Get last snapshot even if expired (fallback).
     */
    public function getFallbackSnapshot(string $provider): ?array
    {
        $snapshot = $this->findByProvider($provider);
        return $snapshot ? $this->extractPayload($snapshot) : null;
    }

    public function acquireRefreshLock(string $provider, int $lockSeconds = 30): bool
    {
        $columns = $this->getTableColumns();
        if (!in_array('refresh_lock_until', $columns, true)) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $lockUntil = date('Y-m-d H:i:s', time() + max(5, $lockSeconds));
        $existing = $this->findByProvider($provider);
        $builder = $this->builder();

        if ($existing) {
            $whereKey = in_array('integration_key', $columns, true) ? 'integration_key' : 'provider';
            $builder->where($whereKey, $provider);
            $builder->groupStart()
                ->where('refresh_lock_until', null)
                ->orWhere('refresh_lock_until <=', $now)
                ->groupEnd();
            $builder->update(['refresh_lock_until' => $lockUntil]);
            return $this->db->affectedRows() > 0;
        }

        $insert = [
            'provider' => $provider,
            'fetched_at' => $now,
            'refresh_lock_until' => $lockUntil,
        ];
        if (in_array('integration_key', $columns, true)) {
            $insert['integration_key'] = $provider;
        }
        if (in_array('status', $columns, true)) {
            $insert['status'] = 'pending';
        }
        if (in_array('id', $columns, true)) {
            $insert['id'] = $this->generateUuid();
        }

        try {
            $builder->insert($insert);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function releaseRefreshLock(string $provider): void
    {
        $columns = $this->getTableColumns();
        if (!in_array('refresh_lock_until', $columns, true)) {
            return;
        }

        $whereKey = in_array('integration_key', $columns, true) ? 'integration_key' : 'provider';
        $this->builder()
            ->where($whereKey, $provider)
            ->update(['refresh_lock_until' => null]);
    }

    public function markRefreshError(string $provider, string $message): void
    {
        $columns = $this->getTableColumns();
        $whereKey = in_array('integration_key', $columns, true) ? 'integration_key' : 'provider';
        $update = [];

        if (in_array('status', $columns, true)) {
            $update['status'] = 'error';
        }
        if (in_array('error_message', $columns, true)) {
            $update['error_message'] = mb_substr($message, 0, 1000);
        }
        if (in_array('refresh_lock_until', $columns, true)) {
            $update['refresh_lock_until'] = null;
        }

        if ($update !== []) {
            $this->builder()->where($whereKey, $provider)->update($update);
        }
    }

    public function findByProvider(string $provider): ?array
    {
        $columns = $this->getTableColumns();
        if (in_array('integration_key', $columns, true)) {
            return $this->where('integration_key', $provider)->first();
        }
        return $this->where('provider', $provider)->first();
    }

    private function getTableColumns(): array
    {
        if ($this->tableColumns === null) {
            $this->tableColumns = $this->db->getFieldNames($this->table);
        }

        return $this->tableColumns;
    }

    private function extractPayload(array $snapshot): ?array
    {
        $raw = $snapshot['payload'] ?? $snapshot['data'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
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
