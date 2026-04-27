<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class UploadsController extends Controller
{
    public function show(string $path = '')
    {
        $relativePath = ltrim(str_replace('\\', '/', rawurldecode($path)), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return $this->response->setStatusCode(404);
        }

        $uploadBasePath = rtrim((string) env('UPLOADS_PATH', WRITEPATH . 'uploads/'), '/\\');
        $baseRealPath = realpath($uploadBasePath);
        if ($baseRealPath === false) {
            return $this->response->setStatusCode(404);
        }

        $candidatePath = $baseRealPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $fileRealPath = realpath($candidatePath);

        if ($fileRealPath === false || !is_file($fileRealPath)) {
            return $this->response->setStatusCode(404);
        }

        if (str_starts_with($fileRealPath, $baseRealPath) === false) {
            return $this->response->setStatusCode(404);
        }

        $mimeType = mime_content_type($fileRealPath) ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setContentType($mimeType)
            ->setBody((string) file_get_contents($fileRealPath));
    }
}
