<?php

namespace App\Services;

use App\Models\MediaImageModel;

class MediaService
{
    private MediaImageModel $imageModel;
    private string $uploadPath;
    private string $publicUrl;
    private ?array $mediaColumns = null;

    public function __construct()
    {
        $this->imageModel = new MediaImageModel();
        $this->uploadPath = env('UPLOADS_PATH', WRITEPATH . 'uploads/');
        $this->publicUrl  = env('UPLOADS_PUBLIC_URL', '/uploads/');
    }

    /**
     * Upload an image from base64 data.
     */
    public function upload(array $data, string $userId): array
    {
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        $mimeType = $data['mimeType'];
        if (!isset($allowedMimes[$mimeType])) {
            throw new \RuntimeException('Invalid image type. Allowed: JPEG, PNG, WebP, GIF', 400);
        }

        $fileData = $this->decodeBase64ImageData((string) $data['fileDataBase64']);
        if ($fileData === false) {
            throw new \RuntimeException('Invalid base64 data', 400);
        }

        $maxSize = (int) env('UPLOADS_MAX_SIZE', 8192) * 1024; // KB to bytes
        if (strlen($fileData) > $maxSize) {
            throw new \RuntimeException('File size exceeds maximum allowed', 400);
        }

        // Validate actual file content
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($fileData);
        if ($detectedMime !== $mimeType) {
            throw new \RuntimeException('File content does not match declared MIME type', 400);
        }

        $id        = $this->generateUuid();
        $ext       = $allowedMimes[$mimeType];
        $safeName  = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($data['fileName'], PATHINFO_FILENAME));
        $timestamp = time();
        $filename  = "{$timestamp}-{$safeName}-{$id}.{$ext}";

        // Ensure upload directory exists
        $fullPath = rtrim($this->uploadPath, '/') . '/';
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        if (file_put_contents($fullPath . $filename, $fileData) === false) {
            throw new \RuntimeException('Failed to write uploaded file', 500);
        }

        $publicUrl = rtrim($this->publicUrl, '/') . '/' . $filename;
        $imageData = [
            'id'          => $id,
            'mime_type'   => $mimeType,
            'alt_text'    => $data['alt'] ?? null,
            'caption'     => $data['caption'] ?? null,
            'uploaded_by' => $userId,
            'active'      => true,
        ];

        if ($this->hasMediaColumn('file_path')) {
            $imageData['file_path'] = $filename;
        }
        if ($this->hasMediaColumn('public_url')) {
            $imageData['public_url'] = $publicUrl;
        }
        if ($this->hasMediaColumn('original_file_name')) {
            $imageData['original_file_name'] = $data['fileName'];
        }
        if ($this->hasMediaColumn('title')) {
            $imageData['title'] = $data['title'] ?? null;
        }
        if ($this->hasMediaColumn('marketing_meta')) {
            $imageData['marketing_meta'] = isset($data['marketingMeta']) ? json_encode($data['marketingMeta']) : null;
        }

        // Legacy media schema compatibility.
        if ($this->hasMediaColumn('filename')) {
            $imageData['filename'] = $filename;
        }
        if ($this->hasMediaColumn('original_name')) {
            $imageData['original_name'] = $data['fileName'];
        }
        if ($this->hasMediaColumn('url')) {
            $imageData['url'] = $publicUrl;
        }
        if ($this->hasMediaColumn('size')) {
            $imageData['size'] = strlen($fileData);
        }
        if ($this->hasMediaColumn('folder')) {
            $imageData['folder'] = 'general';
        }
        if ($this->hasMediaColumn('width') || $this->hasMediaColumn('height')) {
            $dimensions = @getimagesizefromstring($fileData);
            if (is_array($dimensions)) {
                if ($this->hasMediaColumn('width')) {
                    $imageData['width'] = (int) $dimensions[0];
                }
                if ($this->hasMediaColumn('height')) {
                    $imageData['height'] = (int) $dimensions[1];
                }
            }
        }

        if ($this->imageModel->insert($imageData) === false) {
            throw new \RuntimeException('Failed to save image metadata', 500);
        }

        $saved = $this->imageModel->find($id);
        if (!$saved) {
            throw new \RuntimeException('Image saved but could not be loaded', 500);
        }

        return $this->normalizeImageOutput($saved->toArray());
    }

    /**
     * Update image metadata.
     */
    public function updateMetadata(string $id, array $data): array
    {
        $image = $this->imageModel->find($id);
        if (!$image || !$image->active) {
            throw new \RuntimeException('Image not found', 404);
        }

        $updateData = [];
        $fieldMap = [
            ['alt', 'alt_text'],
            ['alt_text', 'alt_text'],
            ['caption', 'caption'],
            ['title', 'title'],
            ['marketingMeta', 'marketing_meta'],
            ['marketing_meta', 'marketing_meta'],
        ];

        foreach ($fieldMap as [$inputKey, $dbColumn]) {
            if (!$this->hasMediaColumn($dbColumn) || !array_key_exists($inputKey, $data)) {
                continue;
            }

            $value = $data[$inputKey];
            if ($dbColumn === 'marketing_meta' && is_array($value)) {
                $value = json_encode($value);
            }
            $updateData[$dbColumn] = $value;
        }

        if (!empty($updateData)) {
            $this->imageModel->update($id, $updateData);
        }

        $updated = $this->imageModel->find($id);
        if (!$updated) {
            throw new \RuntimeException('Image not found', 404);
        }

        return $this->normalizeImageOutput($updated->toArray());
    }

    /**
     * Soft-delete an image.
     */
    public function delete(string $id): void
    {
        $image = $this->imageModel->find($id);
        if (!$image || !$image->active) {
            throw new \RuntimeException('Image not found', 404);
        }

        $this->imageModel->update($id, ['active' => false]);
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

    /**
     * Decode image payload from plain base64, base64url, or data URI formats.
     */
    private function decodeBase64ImageData(string $base64): string|false
    {
        $value = trim($base64);

        if (preg_match('/^data:[^;]+;base64,(.+)$/s', $value, $matches) === 1) {
            $value = $matches[1];
        }

        $value = preg_replace('/\s+/', '', $value) ?? '';
        $value = strtr($value, '-_', '+/');

        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value, true);
    }

    private function hasMediaColumn(string $column): bool
    {
        if ($this->mediaColumns === null) {
            $db = db_connect();
            $this->mediaColumns = array_map('strtolower', $db->getFieldNames('media_images'));
        }

        return in_array(strtolower($column), $this->mediaColumns, true);
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
}
