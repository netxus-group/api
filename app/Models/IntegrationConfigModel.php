<?php

namespace App\Models;

use CodeIgniter\Model;

class IntegrationConfigModel extends Model
{
    protected $table         = 'integration_configs';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'provider', 'enabled', 'config', 'refresh_policy',
        'active', 'endpoint', 'ttl', 'extra_config', 'api_key',
    ];

    protected array $casts = [
        'enabled' => 'boolean',
        'config'  => '?json-array',
        'active'  => 'boolean',
        'extra_config' => '?json-array',
    ];

    private ?array $tableColumns = null;

    public function findByProvider(string $provider): ?array
    {
        return $this->where('provider', $provider)->first();
    }

    /**
     * @return string[]
     */
    public function getTableColumns(): array
    {
        if ($this->tableColumns === null) {
            $this->tableColumns = $this->db->getFieldNames($this->table);
        }

        return $this->tableColumns;
    }
}
