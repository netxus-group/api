<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\NewsModel;

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

        $items = array_map(fn($n) => $n->toArray(), $result['items']);
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

        return ApiResponse::ok($result);
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
}
