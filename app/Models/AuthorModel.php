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
        'id', 'slug', 'name', 'bio', 'avatar_url',
        'social', 'active',
    ];

    protected array $casts = [
        'social' => '?json-array',
        'active' => 'boolean',
    ];

    /**
     * Get all active authors.
     */
    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Find by slug.
     */
    public function findBySlug(string $slug): ?Author
    {
        return $this->where('slug', $slug)->first();
    }
}
