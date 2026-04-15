<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class NewsletterSubscriber extends Entity
{
    protected $casts = [
        'metadata' => 'json-array',
    ];

    protected $datamap = [
        'subscribedAt'   => 'subscribed_at',
        'unsubscribedAt' => 'unsubscribed_at',
    ];
}
