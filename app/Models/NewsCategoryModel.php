<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsCategoryModel extends Model
{
    protected $table         = 'news_categories';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['news_id', 'category_id'];

    /**
     * Get categories for a news article.
     */
    public function getCategoriesForNews(string $newsId): array
    {
        return $this->select('categories.id, categories.slug, categories.name')
            ->join('categories', 'categories.id = news_categories.category_id')
            ->where('news_categories.news_id', $newsId)
            ->where('categories.active', 1)
            ->findAll();
    }

    /**
     * Sync categories for a news article.
     */
    public function syncCategories(string $newsId, array $categoryIds): void
    {
        $this->where('news_id', $newsId)->delete();

        foreach (array_unique($categoryIds) as $catId) {
            $this->insert([
                'news_id'     => $newsId,
                'category_id' => $catId,
            ]);
        }
    }
}
