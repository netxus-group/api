<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Author extends Entity
{
    protected $casts = [
        'active' => 'boolean',
        'social' => '?json-array',
    ];

    protected $datamap = [
        'displayName' => 'name',
        'avatar' => 'avatar_url',
        'socialLinks' => 'social',
    ];
}
