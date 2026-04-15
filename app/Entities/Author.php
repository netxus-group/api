<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Author extends Entity
{
    protected $casts = [
        'active'       => 'boolean',
        'social_links' => 'json-array',
    ];

    protected $datamap = [
        'displayName' => 'display_name',
        'socialLinks' => 'social_links',
    ];
}
