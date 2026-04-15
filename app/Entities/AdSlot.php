<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AdSlot extends Entity
{
    protected $casts = [
        'active'  => 'boolean',
        'content' => 'json-array',
    ];

    protected $datamap = [
        'targetUrl' => 'target_url',
        'startsAt'  => 'starts_at',
        'endsAt'    => 'ends_at',
    ];
}
