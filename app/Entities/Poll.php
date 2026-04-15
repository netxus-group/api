<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Poll extends Entity
{
    protected $casts = [
        'active'                   => 'boolean',
        'allow_multiple_responses' => 'boolean',
    ];

    protected $datamap = [
        'allowMultipleResponses' => 'allow_multiple_responses',
        'createdBy'              => 'created_by',
        'startsAt'               => 'starts_at',
        'endsAt'                 => 'ends_at',
    ];
}
