<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\News;

class NewsModel extends Model
{
    protected $table         = 'news';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = News::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'slug', 'title', 'subtitle', 'excerpt', 'body',
        'cover_image_url', 'author_id', 'status',
        'featured', 'breaking', 'source_url', 'source_name',
        'seo_title', 'seo_description', 'seo_keywords',
        'published_at', 'scheduled_at',
        'created_by', 'reviewed_by',
        'view_count', 'share_count', 'deleted_at',
    ];

    protected array $casts = [
        'featured' => 'boolean',
        'breaking' => 'boolean',
    ];

    /**
     * Get news with category/tag/author relations.
     */
    public function findWithRelations(string $id): ?array
    {
        $news = $this->find($id);
        if (!$news) {
            return null;
        }

        $data = (array) $news->toArray();

        // Categories
        $catModel = new NewsCategoryModel();
        $data['categories'] = $catModel->getCategoriesForNews($id);

        // Tags
        $tagModel = new NewsTagModel();
        $data['tags'] = $tagModel->getTagsForNews($id);

        // Author
        if (!empty($data['author_id'])) {
            $authorModel = new AuthorModel();
            $author = $authorModel->find($data['author_id']);
            $data['author'] = $author ? $author->toArray() : null;
        }

        return $data;
    }

    /**
     * List news with filters and pagination.
     */
    public function listFiltered(array $filters, int $page, int $perPage): array
    {
        $builder = $this->where('deleted_at', null);

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['mine'])) {
            $builder->where('created_by', $filters['mine']);
        }

        if (!empty($filters['authorId'])) {
            $builder->where('author_id', $filters['authorId']);
        }

        if (!empty($filters['featured'])) {
            $builder->where('featured', 1);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('excerpt', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $items = $builder->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->find();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * List public published news.
     */
    public function listPublished(array $filters, int $page, int $perPage): array
    {
        $builder = $this->where('news.deleted_at', null)->where('news.status', 'published');

        if (!empty($filters['categorySlug'])) {
            $builder->join('news_categories nc', 'nc.news_id = news.id')
                ->join('categories c', 'c.id = nc.category_id')
                ->where('c.slug', $filters['categorySlug']);
        }

        if (!empty($filters['tagSlug'])) {
            $builder->join('news_tags nt', 'nt.news_id = news.id')
                ->join('tags t', 't.id = nt.tag_id')
                ->where('t.slug', $filters['tagSlug']);
        }

        if (!empty($filters['authorSlug'])) {
            $builder->join('authors a', 'a.id = news.author_id')
                ->where('a.slug', $filters['authorSlug']);
        }

        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $builder->where('news.featured', (int) ((bool) $filters['featured']));
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('news.title', $search)
                ->orLike('news.excerpt', $search)
                ->groupEnd();
        }

        $total = $builder->select('news.id')->distinct()->countAllResults(false);

        $items = $builder->select('news.*')->distinct()
            ->orderBy('news.published_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->find();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Publish all due scheduled news.
     */
    public function publishScheduled(): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->where('status', 'scheduled')
            ->where('scheduled_at IS NOT NULL')
            ->where('scheduled_at <=', $now)
            ->where('deleted_at', null)
            ->set(['status' => 'published'])
            ->update();
    }

    /**
     * Find by slug (for public endpoint).
     */
    public function findPublishedBySlug(string $slug): ?array
    {
        $news = $this->where('slug', $slug)
            ->where('status', 'published')
            ->where('deleted_at', null)
            ->first();

        if (!$news) {
            return null;
        }

        return $this->findWithRelations($news->id);
    }
}
