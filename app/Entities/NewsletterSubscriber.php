<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class NewsletterSubscriber extends Entity
{
    protected $datamap = [
        'subscribedAt'   => 'created_at',
        'unsubscribedAt' => 'updated_at',
    ];
}
