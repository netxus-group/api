<?php

namespace App\Services;

use App\Models\NewsModel;
use App\Models\NewsCategoryModel;
use App\Models\NewsTagModel;
use App\Models\PostStatusHistoryModel;
use App\Libraries\SlugGenerator;
use App\Entities\News;

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

        $newsData = [
            'id'            => $id,
            'slug'          => $slug,
            'title'         => $data['title'],
            'summary'       => $data['summary'],
            'content'       => $data['content'],
            'hero_image'    => $data['heroImage'] ?? null,
            'hero_image_id' => $data['heroImageId'] ?? null,
            'status'        => $status,
            'publish_at'    => $data['publishAt'] ?? null,
            'featured'      => ($userRole !== 'writer') ? ($data['featured'] ?? false) : false,
            'author_id'     => $data['authorId'] ?? null,
            'created_by'    => $userId,
            'active'        => true,
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
        if (!$news || !$news->active) {
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
        $fields = ['title', 'summary', 'content', 'heroImage', 'heroImageId', 'authorId', 'status', 'featured', 'publishAt'];

        $fieldMap = [
            'heroImage'   => 'hero_image',
            'heroImageId' => 'hero_image_id',
            'authorId'    => 'author_id',
            'publishAt'   => 'publish_at',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $dbField = $fieldMap[$field] ?? $field;
                $updateData[$dbField] = $data[$field];
            }
        }

        // If status changed to approved, record reviewer
        if ($newStatus !== $oldStatus) {
            if (in_array($newStatus, ['approved', 'published'], true)) {
                $updateData['reviewed_by'] = $userId;
                $updateData['approved_at'] = date('Y-m-d H:i:s');
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
        if (!$news || !$news->active) {
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
            'status'     => 'scheduled',
            'publish_at' => $publishAt,
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
        if (!$news || !$news->active) {
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

        $this->newsModel->update($id, ['active' => false]);
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
