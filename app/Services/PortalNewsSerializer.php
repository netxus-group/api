<?php

namespace App\Services;

use App\Support\AssetUrl;
use CodeIgniter\Database\BaseConnection;

class PortalNewsSerializer
{
    public static function mapNewsRow(array $row, array $categoriesByNews, array $tagsByNews, array $authorsById): array
    {
        $newsId = (string) ($row['id'] ?? '');

        $title = (string) ($row['title'] ?? '');
        $summary = (string) ($row['summary'] ?? $row['excerpt'] ?? '');
        $content = (string) ($row['content'] ?? $row['body'] ?? '');
        $heroImage = AssetUrl::normalize($row['hero_image'] ?? $row['cover_image_url'] ?? null);
        $publishAt = $row['publish_at'] ?? $row['published_at'] ?? null;

        $authorId = $row['author_id'] ?? null;
        $author = null;

        if ($authorId && isset($authorsById[$authorId])) {
            $authorRow = $authorsById[$authorId];
            $author = [
                'id' => $authorRow['id'],
                'slug' => $authorRow['slug'] ?? '',
                'displayName' => $authorRow['display_name'] ?? $authorRow['name'] ?? 'Redaccion',
            ];
        }

        return [
            'id' => $newsId,
            'slug' => (string) ($row['slug'] ?? ''),
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'heroImage' => $heroImage,
            'publishAt' => $publishAt,
            'featured' => (bool) ($row['featured'] ?? false),
            'author' => $author,
            'categories' => $categoriesByNews[$newsId] ?? [],
            'tags' => $tagsByNews[$newsId] ?? [],
            'metrics' => [
                'viewCount' => (int) ($row['view_count'] ?? 0),
                'shareCount' => (int) ($row['share_count'] ?? 0),
                'breaking' => (bool) ($row['breaking'] ?? false),
            ],
        ];
    }

    public static function loadTaxonomyMaps(BaseConnection $db, array $newsIds): array
    {
        if ($newsIds === []) {
            return [[], []];
        }

        $categoriesRows = $db->table('news_categories nc')
            ->select('nc.news_id, c.id as category_id, c.slug, c.name')
            ->join('categories c', 'c.id = nc.category_id', 'inner')
            ->whereIn('nc.news_id', $newsIds)
            ->where('c.active', 1)
            ->get()
            ->getResultArray();

        $tagsRows = $db->table('news_tags nt')
            ->select('nt.news_id, t.id as tag_id, t.slug, t.name')
            ->join('tags t', 't.id = nt.tag_id', 'inner')
            ->whereIn('nt.news_id', $newsIds)
            ->where('t.active', 1)
            ->get()
            ->getResultArray();

        $categoriesByNews = [];
        foreach ($categoriesRows as $row) {
            $newsId = (string) $row['news_id'];
            $categoriesByNews[$newsId][] = [
                'id' => (string) $row['category_id'],
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
            ];
        }

        $tagsByNews = [];
        foreach ($tagsRows as $row) {
            $newsId = (string) $row['news_id'];
            $tagsByNews[$newsId][] = [
                'id' => (string) $row['tag_id'],
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
            ];
        }

        return [$categoriesByNews, $tagsByNews];
    }

    public static function loadAuthorsMap(BaseConnection $db, array $authorIds): array
    {
        if ($authorIds === []) {
            return [];
        }

        $rows = $db->table('authors')
            ->select('id, slug, name, COALESCE(name, slug) as display_name')
            ->whereIn('id', $authorIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = $row;
        }

        return $map;
    }
}
