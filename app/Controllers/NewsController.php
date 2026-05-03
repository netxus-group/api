<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\NewsModel;
use App\Services\PortalNewsSerializer;
use App\Support\AssetUrl;

class NewsController extends BaseApiController
{
    private NewsModel $newsModel;

    public function __construct()
    {
        $this->newsModel = new NewsModel();
    }

    /**
     * GET /api/v1/news
     */
    public function index()
    {
        [$page, $perPage] = $this->paginationParams();

        $filters = [
            'status'   => $this->request->getGet('status'),
            'authorId' => $this->request->getGet('authorId'),
            'featured' => $this->request->getGet('featured'),
            'search'   => $this->request->getGet('search'),
        ];

        // Writers only see their own content by default
        if ($this->isWriter()) {
            $filters['mine'] = $this->userId();
        } elseif ($this->request->getGet('mine') === 'true') {
            $filters['mine'] = $this->userId();
        }

        $result = $this->newsModel->listFiltered($filters, $page, $perPage);

        $items = $this->serializeNewsItems($result['items']);
        return ApiResponse::paginated($items, $result['total'], $page, $perPage);
    }

    /**
     * GET /api/v1/news/{id}
     */
    public function show(string $id)
    {
        $result = $this->newsModel->findWithRelations($id);
        if (!$result) {
            return ApiResponse::notFound('News not found');
        }

        // Writers can only see their own articles
        if ($this->isWriter() && ($result['created_by'] ?? null) !== $this->userId()) {
            if (($result['status'] ?? '') !== 'published') {
                return ApiResponse::forbidden('Cannot view this article');
            }
        }

        $serialized = $this->serializeNewsItems([$result]);
        return ApiResponse::ok($serialized[0] ?? null);
    }

    /**
     * POST /api/v1/news
     */
    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createNews');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $newsService = service('newsService');
            $result = $newsService->create($data, $this->userId(), $this->userRole());
            return ApiResponse::created($result, 'News created');
        } catch (\RuntimeException $e) {
            return ApiResponse::badRequest($e->getMessage());
        }
    }

    /**
     * PUT /api/v1/news/{id}
     */
    public function update(string $id)
    {
        $data = $this->getJsonInput();

        try {
            $newsService = service('newsService');
            $result = $newsService->update($id, $data, $this->userId(), $this->userRole());
            return ApiResponse::ok($result, 'News updated');
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            return match (true) {
                $code === 404 => ApiResponse::notFound($e->getMessage()),
                $code === 403 => ApiResponse::forbidden($e->getMessage()),
                default       => ApiResponse::badRequest($e->getMessage()),
            };
        }
    }

    /**
     * POST /api/v1/news/{id}/schedule
     */
    public function schedule(string $id)
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'scheduleNews');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $newsService = service('newsService');
            $result = $newsService->schedule($id, $data['publishAt'], $this->userId(), $this->userRole());
            return ApiResponse::ok($result, 'News scheduled');
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            return match (true) {
                $code === 404 => ApiResponse::notFound($e->getMessage()),
                $code === 403 => ApiResponse::forbidden($e->getMessage()),
                default       => ApiResponse::badRequest($e->getMessage()),
            };
        }
    }

    /**
     * DELETE /api/v1/news/{id}
     */
    public function delete(string $id)
    {
        try {
            $newsService = service('newsService');
            $newsService->delete($id, $this->userId(), $this->userRole());
            return ApiResponse::noContent('News deleted');
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            return match (true) {
                $code === 404 => ApiResponse::notFound($e->getMessage()),
                $code === 403 => ApiResponse::forbidden($e->getMessage()),
                default       => ApiResponse::badRequest($e->getMessage()),
            };
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, mixed>>
     */
    private function serializeNewsItems(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $rows = array_map(fn($item) => $this->toNewsRow($item), $items);
        $newsIds = array_map(static fn(array $row): string => (string) ($row['id'] ?? ''), $rows);

        $db = db_connect();
        [$categoriesByNews, $tagsByNews] = PortalNewsSerializer::loadTaxonomyMaps($db, $newsIds);
        $mediaImageIdByUrl = $this->loadMediaImageIdByUrl($db);

        $authorIds = [];
        foreach ($rows as $row) {
            $authorId = $row['author_id'] ?? $row['authorId'] ?? null;
            if (!empty($authorId)) {
                $authorIds[] = (string) $authorId;
            }
        }
        $authorsById = PortalNewsSerializer::loadAuthorsMap($db, array_values(array_unique($authorIds)));

        return array_map(function (array $row) use ($categoriesByNews, $tagsByNews, $authorsById, $mediaImageIdByUrl): array {
            if (!array_key_exists('author_id', $row) && array_key_exists('authorId', $row)) {
                $row['author_id'] = $row['authorId'];
            }
            if (!array_key_exists('cover_image_url', $row) && array_key_exists('heroImage', $row)) {
                $row['cover_image_url'] = $row['heroImage'];
            }
            if (!array_key_exists('published_at', $row) && array_key_exists('publishAt', $row)) {
                $row['published_at'] = $row['publishAt'];
            }
            if (!array_key_exists('excerpt', $row) && array_key_exists('summary', $row)) {
                $row['excerpt'] = $row['summary'];
            }
            if (!array_key_exists('body', $row) && array_key_exists('content', $row)) {
                $row['body'] = $row['content'];
            }

            $base = PortalNewsSerializer::mapNewsRow($row, $categoriesByNews, $tagsByNews, $authorsById);
            $base['status'] = (string) ($row['status'] ?? 'draft');
            $base['createdBy'] = $row['created_by'] ?? $row['createdBy'] ?? null;
            $base['reviewedBy'] = $row['reviewed_by'] ?? $row['reviewedBy'] ?? null;
            $heroImageId = $row['hero_image_id'] ?? $row['heroImageId'] ?? null;
            if (($heroImageId === null || $heroImageId === '') && !empty($base['heroImage'])) {
                $normalizedHero = AssetUrl::normalize((string) $base['heroImage']);
                if (is_string($normalizedHero) && isset($mediaImageIdByUrl[$normalizedHero])) {
                    $heroImageId = $mediaImageIdByUrl[$normalizedHero];
                }
            }
            $base['heroImageId'] = $heroImageId;
            $base['createdAt'] = $row['created_at'] ?? $row['createdAt'] ?? null;
            $base['updatedAt'] = $row['updated_at'] ?? $row['updatedAt'] ?? null;
            return $base;
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function toNewsRow(mixed $item): array
    {
        if (is_array($item)) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'toArray')) {
            return $item->toArray();
        }

        return (array) $item;
    }

    /**
     * @return array<string, string> map of normalized URL to media image ID
     */
    private function loadMediaImageIdByUrl(\CodeIgniter\Database\BaseConnection $db): array
    {
        $columns = array_map('strtolower', $db->getFieldNames('media_images'));
        $select = ['id'];

        foreach (['public_url', 'url'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                $select[] = $candidate;
            }
        }

        if (count($select) === 1) {
            return [];
        }

        $rows = $db->table('media_images')
            ->select(implode(', ', $select))
            ->where('active', 1)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }

            foreach (['public_url', 'url'] as $key) {
                $raw = $row[$key] ?? null;
                if (!is_string($raw) || trim($raw) === '') {
                    continue;
                }
                $normalized = AssetUrl::normalize($raw);
                if (is_string($normalized) && $normalized !== '') {
                    $map[$normalized] = $id;
                }
            }
        }

        return $map;
    }
}
