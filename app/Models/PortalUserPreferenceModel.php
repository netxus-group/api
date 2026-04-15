<?php

namespace App\Models;

use CodeIgniter\Model;

class PortalUserPreferenceModel extends Model
{
    protected $table            = 'portal_user_preferences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'id',
        'portal_user_id',
        'timezone',
        'language',
        'digest_frequency',
        'personalization_opt_in',
    ];

    public function getByUserId(string $portalUserId): ?array
    {
        return $this->where('portal_user_id', $portalUserId)->first();
    }
}
