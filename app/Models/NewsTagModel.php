<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsTagModel extends Model
{
    protected $table         = 'news_tags';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['news_id', 'tag_id'];

    /**
     * Get tags for a news article.
     */
    public function getTagsForNews(string $newsId): array
    {
        return $this->select('tags.id, tags.slug, tags.name')
            ->join('tags', 'tags.id = news_tags.tag_id')
            ->where('news_tags.news_id', $newsId)
            ->where('tags.active', 1)
            ->findAll();
    }

    /**
     * Sync tags for a news article.
     */
    public function syncTags(string $newsId, array $tagIds): void
    {
        $this->where('news_id', $newsId)->delete();

        foreach (array_unique($tagIds) as $tagId) {
            $this->insert([
                'news_id' => $newsId,
                'tag_id'  => $tagId,
            ]);
        }
    }
}
