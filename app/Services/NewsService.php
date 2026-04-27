<?php

namespace App\Services;

use App\Models\NewsModel;
use App\Models\NewsCategoryModel;
use App\Models\NewsTagModel;
use App\Models\PostStatusHistoryModel;
use App\Libraries\SlugGenerator;
use App\Entities\News;
use App\Support\AssetUrl;

class NewsService
{
    private NewsModel $newsModel;
    private NewsCategoryModel $catModel;
    private NewsTagModel $tagModel;
    private PostStatusHistoryModel $historyModel;

    public function __construct()
    {
        $this->newsModel    = new NewsModel();
        $this->catModel     = new NewsCategoryModel();
        $this->tagModel     = new NewsTagModel();
        $this->historyModel = new PostStatusHistoryModel();
    }

    /**
     * Create a news article.
     */
    public function create(array $data, string $userId, string $userRole): array
    {
        $id   = $this->generateUuid();
        $slug = SlugGenerator::generate($data['title'], 'news', 'slug');

        // Writers can only create drafts
        $status = $data['status'] ?? 'draft';
        if ($userRole === 'writer') {
            $status = 'draft';
        }

        $publishAt = $data['publishAt'] ?? null;

        $newsData = [
            'id'              => $id,
            'slug'            => $slug,
            'title'           => $data['title'],
            'excerpt'         => $data['summary'],
            'body'            => $data['content'],
            'cover_image_url' => AssetUrl::normalize($data['heroImage'] ?? null),
            'status'          => $status,
            'published_at'    => $status === 'published' ? $publishAt : null,
            'scheduled_at'    => $status === 'scheduled' ? $publishAt : null,
            'featured'        => ($userRole !== 'writer') ? ($data['featured'] ?? false) : false,
            'author_id'       => $data['authorId'] ?? null,
            'created_by'      => $userId,
        ];

        $this->newsModel->insert($newsData);

        // Sync categories
        if (!empty($data['categoryIds'])) {
            $this->catModel->syncCategories($id, $data['categoryIds']);
        }

        // Sync tags
        if (!empty($data['tagIds'])) {
            $this->tagModel->syncTags($id, $data['tagIds']);
        }

        // Log status creation
        $this->historyModel->logTransition($id, null, $status, $userId);

        return $this->newsModel->findWithRelations($id);
    }

    /**
     * Update a news article with ownership and status checks.
     */
    public function update(string $id, array $data, string $userId, string $userRole): array
    {
        $news = $this->newsModel->find($id);
        if (!$news || !empty($news->deleted_at)) {
            throw new \RuntimeException('News not found', 404);
        }

        // Writer can only edit their own drafts
        if ($userRole === 'writer') {
            if ($news->created_by !== $userId) {
                throw new \RuntimeException('Cannot edit another user\'s article', 403);
            }
            if ($news->status === 'published') {
                throw new \RuntimeException('Cannot edit published articles', 403);
            }
            // Writers cannot change featured or status beyond draft/in_review
            unset($data['featured']);
            if (isset($data['status']) && !in_array($data['status'], ['draft', 'in_review'], true)) {
                throw new \RuntimeException('Writers can only set draft or in_review', 403);
            }
        }

        $oldStatus = $news->status;
        $newStatus = $data['status'] ?? $oldStatus;

        // Validate status transition
        if ($newStatus !== $oldStatus && !News::isValidTransition($oldStatus, $newStatus)) {
            throw new \RuntimeException("Invalid status transition: {$oldStatus} → {$newStatus}", 400);
        }

        $updateData = [];
        $fields = ['title', 'summary', 'content', 'heroImage', 'authorId', 'status', 'featured', 'publishAt'];

        $fieldMap = [
            'summary'     => 'excerpt',
            'content'     => 'body',
            'heroImage'   => 'cover_image_url',
            'authorId'    => 'author_id',
            'publishAt'   => 'published_at',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $dbField = $fieldMap[$field] ?? $field;
                $value = $data[$field];
                if ($field === 'heroImage') {
                    $value = AssetUrl::normalize(is_string($value) ? $value : null);
                }
                $updateData[$dbField] = $value;
            }
        }

        // If status changed to approved, record reviewer
        if ($newStatus !== $oldStatus) {
            if (in_array($newStatus, ['approved', 'published'], true)) {
                $updateData['reviewed_by'] = $userId;
            }
        }

        // Regenerate slug if title changed
        if (!empty($data['title']) && $data['title'] !== $news->title) {
            $updateData['slug'] = SlugGenerator::generate($data['title'], 'news', 'slug', $id);
        }

        if (!empty($updateData)) {
            $this->newsModel->update($id, $updateData);
        }

        // Sync categories/tags
        if (isset($data['categoryIds'])) {
            $this->catModel->syncCategories($id, $data['categoryIds']);
        }
        if (isset($data['tagIds'])) {
            $this->tagModel->syncTags($id, $data['tagIds']);
        }

        // Log status change
        if ($newStatus !== $oldStatus) {
            $this->historyModel->logTransition($id, $oldStatus, $newStatus, $userId);
        }

        return $this->newsModel->findWithRelations($id);
    }

    /**
     * Schedule a news article for future publication.
     */
    public function schedule(string $id, string $publishAt, string $userId, string $userRole): array
    {
        $news = $this->newsModel->find($id);
        if (!$news || !empty($news->deleted_at)) {
            throw new \RuntimeException('News not found', 404);
        }

        // Only editors and super_admin can schedule
        if ($userRole === 'writer') {
            throw new \RuntimeException('Writers cannot schedule publications', 403);
        }

        // publishAt must be in the future
        if (strtotime($publishAt) <= time()) {
            throw new \RuntimeException('Schedule date must be in the future', 400);
        }

        $oldStatus = $news->status;

        $this->newsModel->update($id, [
            'status'       => 'scheduled',
            'scheduled_at' => $publishAt,
        ]);

        $this->historyModel->logTransition($id, $oldStatus, 'scheduled', $userId);

        return $this->newsModel->findWithRelations($id);
    }

    /**
     * Soft-delete a news article.
     */
    public function delete(string $id, string $userId, string $userRole): void
    {
        $news = $this->newsModel->find($id);
        if (!$news || !empty($news->deleted_at)) {
            throw new \RuntimeException('News not found', 404);
        }

        // Writers can only delete their own unpublished content
        if ($userRole === 'writer') {
            if ($news->created_by !== $userId) {
                throw new \RuntimeException('Cannot delete another user\'s article', 403);
            }
            if ($news->status === 'published') {
                throw new \RuntimeException('Cannot delete published articles', 403);
            }
        }

        $this->newsModel->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
