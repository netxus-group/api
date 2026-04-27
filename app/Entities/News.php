<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class News extends Entity
{
    protected $casts = [
        'featured' => 'boolean',
        'breaking' => 'boolean',
    ];

    protected $datamap = [
        'heroImage'   => 'cover_image_url',
        'authorId'    => 'author_id',
        'createdBy'   => 'created_by',
        'reviewedBy'  => 'reviewed_by',
        'publishAt'   => 'published_at',
    ];

    /**
     * Check if a status transition is valid.
     */
    public static function isValidTransition(string $from, string $to): bool
    {
        $transitions = [
            'draft'      => ['in_review', 'draft'],
            'in_review'  => ['approved', 'draft', 'published'],
            'approved'   => ['scheduled', 'published', 'draft'],
            'scheduled'  => ['published', 'draft'],
            'published'  => ['draft'],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }
}
