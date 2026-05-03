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
        $items  = array_map(fn($img) => $this->normalizeImageOutput($img->toArray()), $result['items']);

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

        return ApiResponse::ok($this->normalizeImageOutput($image->toArray()));
    }

    /**
     * POST /api/v1/images
     */
    public function upload()
    {
        $data   = $this->normalizeUploadInput($this->getJsonInput());
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
     * Normalize upload payload to support camelCase and snake_case keys.
     */
    private function normalizeUploadInput(array $data): array
    {
        $aliases = [
            'file_name'        => 'fileName',
            'mime_type'        => 'mimeType',
            'base64'           => 'fileDataBase64',
            'file_data_base64' => 'fileDataBase64',
            'alt_text'         => 'alt',
            'marketing_meta'   => 'marketingMeta',
        ];

        foreach ($aliases as $source => $target) {
            if (!array_key_exists($target, $data) && array_key_exists($source, $data)) {
                $data[$target] = $data[$source];
            }
        }

        return $data;
    }

    private function normalizeImageOutput(array $row): array
    {
        if (!array_key_exists('file_path', $row) && array_key_exists('filename', $row)) {
            $row['file_path'] = $row['filename'];
        }
        if (!array_key_exists('public_url', $row) && array_key_exists('url', $row)) {
            $row['public_url'] = $row['url'];
        }
        if (!array_key_exists('original_file_name', $row) && array_key_exists('original_name', $row)) {
            $row['original_file_name'] = $row['original_name'];
        }

        if ((!array_key_exists('filePath', $row) || empty($row['filePath'])) && !empty($row['file_path'])) {
            $row['filePath'] = $row['file_path'];
        }
        if ((!array_key_exists('publicUrl', $row) || empty($row['publicUrl'])) && !empty($row['public_url'])) {
            $row['publicUrl'] = $row['public_url'];
        }
        if ((!array_key_exists('originalFileName', $row) || empty($row['originalFileName'])) && !empty($row['original_file_name'])) {
            $row['originalFileName'] = $row['original_file_name'];
        }
        if ((!array_key_exists('mimeType', $row) || empty($row['mimeType'])) && !empty($row['mime_type'])) {
            $row['mimeType'] = $row['mime_type'];
        }
        if ((!array_key_exists('alt', $row) || $row['alt'] === null || $row['alt'] === '') && !empty($row['alt_text'])) {
            $row['alt'] = $row['alt_text'];
        }
        if ((!array_key_exists('alt', $row) || $row['alt'] === null || $row['alt'] === '') && !empty($row['altText'])) {
            $row['alt'] = $row['altText'];
        }
        if ((!array_key_exists('uploadedBy', $row) || empty($row['uploadedBy'])) && !empty($row['uploaded_by'])) {
            $row['uploadedBy'] = $row['uploaded_by'];
        }
        if (!array_key_exists('title', $row) || $row['title'] === null) {
            $row['title'] = '';
        }
        if (!array_key_exists('caption', $row) || $row['caption'] === null) {
            $row['caption'] = '';
        }
        if (!array_key_exists('marketingMeta', $row) || $row['marketingMeta'] === null) {
            $row['marketingMeta'] = [];
        }
        if (array_key_exists('createdAt', $row) && is_array($row['createdAt'])) {
            $row['createdAt'] = $row['createdAt']['date'] ?? null;
        }
        if (array_key_exists('createdAt', $row) && is_object($row['createdAt'])) {
            if (isset($row['createdAt']->date)) {
                $row['createdAt'] = $row['createdAt']->date;
            } elseif (method_exists($row['createdAt'], '__toString')) {
                $row['createdAt'] = (string) $row['createdAt'];
            }
        }
        if ((!array_key_exists('createdAt', $row) || $row['createdAt'] === null) && array_key_exists('created_at', $row)) {
            if (is_array($row['created_at'])) {
                $row['createdAt'] = $row['created_at']['date'] ?? null;
            } elseif (is_object($row['created_at'])) {
                if (isset($row['created_at']->date)) {
                    $row['createdAt'] = $row['created_at']->date;
                } elseif (method_exists($row['created_at'], '__toString')) {
                    $row['createdAt'] = (string) $row['created_at'];
                } else {
                    $row['createdAt'] = null;
                }
            } else {
                $row['createdAt'] = $row['created_at'];
            }
        }
        if (array_key_exists('updatedAt', $row) && is_array($row['updatedAt'])) {
            $row['updatedAt'] = $row['updatedAt']['date'] ?? null;
        }
        if (array_key_exists('updatedAt', $row) && is_object($row['updatedAt'])) {
            if (isset($row['updatedAt']->date)) {
                $row['updatedAt'] = $row['updatedAt']->date;
            } elseif (method_exists($row['updatedAt'], '__toString')) {
                $row['updatedAt'] = (string) $row['updatedAt'];
            }
        }
        if ((!array_key_exists('updatedAt', $row) || $row['updatedAt'] === null) && array_key_exists('updated_at', $row)) {
            if (is_array($row['updated_at'])) {
                $row['updatedAt'] = $row['updated_at']['date'] ?? null;
            } elseif (is_object($row['updated_at'])) {
                if (isset($row['updated_at']->date)) {
                    $row['updatedAt'] = $row['updated_at']->date;
                } elseif (method_exists($row['updated_at'], '__toString')) {
                    $row['updatedAt'] = (string) $row['updated_at'];
                } else {
                    $row['updatedAt'] = null;
                }
            } else {
                $row['updatedAt'] = $row['updated_at'];
            }
        }

        return $row;
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
