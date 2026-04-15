<?php

namespace App\Services;

use App\Models\MediaImageModel;

class MediaService
{
    private MediaImageModel $imageModel;
    private string $uploadPath;
    private string $publicUrl;

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

        $fileData = base64_decode($data['fileDataBase64'], true);
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

        file_put_contents($fullPath . $filename, $fileData);

        $imageData = [
            'id'                 => $id,
            'file_path'          => $filename,
            'public_url'         => rtrim($this->publicUrl, '/') . '/' . $filename,
            'original_file_name' => $data['fileName'],
            'mime_type'          => $mimeType,
            'title'              => $data['title'] ?? null,
            'alt_text'           => $data['alt'] ?? null,
            'caption'            => $data['caption'] ?? null,
            'marketing_meta'     => isset($data['marketingMeta']) ? json_encode($data['marketingMeta']) : null,
            'uploaded_by'        => $userId,
            'active'             => true,
        ];

        $this->imageModel->insert($imageData);

        return $this->imageModel->find($id)->toArray();
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
        $fields = ['title', 'alt' => 'alt_text', 'caption', 'marketingMeta' => 'marketing_meta'];

        foreach ($fields as $input => $dbField) {
            $key = is_int($input) ? $dbField : $input;
            $col = $dbField;
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
                if ($col === 'marketing_meta' && is_array($value)) {
                    $value = json_encode($value);
                }
                $updateData[$col] = $value;
            }
        }

        if (!empty($updateData)) {
            $this->imageModel->update($id, $updateData);
        }

        return $this->imageModel->find($id)->toArray();
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
}
