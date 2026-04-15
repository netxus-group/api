<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\MediaImageModel;

class MediaController extends BaseApiController
{
    private MediaImageModel $imageModel;

    public function __construct()
    {
        $this->imageModel = new MediaImageModel();
    }

    /**
     * GET /api/v1/images
     */
    public function index()
    {
        [$page, $perPage] = $this->paginationParams();
        $search = $this->request->getGet('search');

        $result = $this->imageModel->listFiltered($search, $page, $perPage);
        $items  = array_map(fn($img) => $img->toArray(), $result['items']);

        return ApiResponse::paginated($items, $result['total'], $page, $perPage);
    }

    /**
     * GET /api/v1/images/{id}
     */
    public function show(string $id)
    {
        $image = $this->imageModel->find($id);
        if (!$image || !$image->active) {
            return ApiResponse::notFound('Image not found');
        }

        return ApiResponse::ok($image->toArray());
    }

    /**
     * POST /api/v1/images
     */
    public function upload()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'uploadImage');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $mediaService = service('mediaService');
            $result = $mediaService->upload($data, $this->userId());
            return ApiResponse::created($result, 'Image uploaded');
        } catch (\RuntimeException $e) {
            return ApiResponse::badRequest($e->getMessage());
        }
    }

    /**
     * PUT /api/v1/images/{id}
     */
    public function update(string $id)
    {
        $data = $this->getJsonInput();

        try {
            $mediaService = service('mediaService');
            $result = $mediaService->updateMetadata($id, $data);
            return ApiResponse::ok($result, 'Image updated');
        } catch (\RuntimeException $e) {
            return ApiResponse::notFound($e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/images/{id}
     */
    public function delete(string $id)
    {
        if (!$this->hasPermission('images.delete')) {
            return ApiResponse::forbidden('Permission images.delete required');
        }

        try {
            $mediaService = service('mediaService');
            $mediaService->delete($id);
            return ApiResponse::noContent('Image deleted');
        } catch (\RuntimeException $e) {
            return ApiResponse::notFound($e->getMessage());
        }
    }
}
