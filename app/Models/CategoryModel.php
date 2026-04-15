<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Category;

class CategoryModel extends Model
{
    protected $table         = 'categories';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = Category::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'slug', 'name', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function getActive(): array
    {
        return $this->where('active', 1)->orderBy('name', 'ASC')->findAll();
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->where('slug', $slug)->first();
    }
}
