<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Author;

class AuthorModel extends Model
{
    protected $table         = 'authors';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = Author::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'slug', 'display_name', 'bio', 'avatar',
        'social_links', 'active',
    ];

    protected $casts = [
        'social_links' => 'json-array',
        'active'       => 'boolean',
    ];

    /**
     * Get all active authors.
     */
    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('display_name', 'ASC')->findAll();
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $slug): ?Author
    {
        return $this->where('slug', $slug)->first();
    }
}
