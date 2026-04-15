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
        'id', 'slug', 'title', 'summary', 'content',
        'hero_image', 'hero_image_id', 'status',
        'publish_at', 'featured', 'author_id',
        'created_by', 'reviewed_by', 'approved_at', 'active',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'active'   => 'boolean',
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
        $builder = $this->where('active', 1);

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
                ->orLike('summary', $search)
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
        $builder = $this->where('active', 1)->where('status', 'published');

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

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('news.title', $search)
                ->orLike('news.summary', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $items = $builder->select('news.*')
            ->orderBy('news.publish_at', 'DESC')
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
            ->where('publish_at IS NOT NULL')
            ->where('publish_at <=', $now)
            ->where('active', 1)
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
            ->where('active', 1)
            ->first();

        if (!$news) {
            return null;
        }

        return $this->findWithRelations($news->id);
    }
}
