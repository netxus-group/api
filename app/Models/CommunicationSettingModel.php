<?php

namespace App\Models;

use CodeIgniter\Model;

class CommunicationSettingModel extends Model
{
    protected $table = 'communication_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'scope',
        'public_config',
        'secret_config_encrypted',
        'created_at',
        'updated_at',
    ];

    public function findDefault(): ?array
    {
        return $this->where('scope', 'default')->first();
    }
}
