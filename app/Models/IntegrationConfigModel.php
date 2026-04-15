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
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config'  => 'json-array',
    ];

    public function findByProvider(string $provider): ?array
    {
        return $this->where('provider', $provider)->first();
    }
}
