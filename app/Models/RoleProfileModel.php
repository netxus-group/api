<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleProfileModel extends Model
{
    protected $table         = 'role_profiles';
    protected $primaryKey    = 'key';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['key', 'name', 'capabilities'];

    protected array $casts = [
        'capabilities' => 'json-array',
    ];
}
