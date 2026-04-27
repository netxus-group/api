<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Tag;

class TagModel extends Model
{
    protected $table         = 'tags';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = Tag::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'slug', 'name', 'active',
    ];

    protected array $casts = [
        'active' => 'boolean',
    ];

    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }

    public function findBySlug(string $slug): ?Tag
    {
        return $this->where('slug', $slug)->first();
    }
}
