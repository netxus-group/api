<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Standardized JSON API responses.
 */
class ApiResponse
{
    /**
     * Successful response.
     */
    public static function ok($data = null, string $message = 'OK', array $meta = []): ResponseInterface
    {
        return self::json(200, 'success', $message, $data, $meta);
    }

    /**
     * Created response.
     */
    public static function created($data = null, string $message = 'Created'): ResponseInterface
    {
        return self::json(201, 'success', $message, $data);
    }

    /**
     * No content response (delete operations).
     */
    public static function noContent(string $message = 'Deleted'): ResponseInterface
    {
        return self::json(200, 'success', $message);
    }

    /**
     * Bad request (validation, malformed input).
     */
    public static function badRequest(string $message = 'Bad request', $errors = null): ResponseInterface
    {
        return self::json(400, 'error', $message, null, [], $errors);
    }

    /**
     * Validation error (422 with field errors).
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        return self::json(422, 'error', $message, null, [], $errors);
    }

    /**
     * Unauthorized (not authenticated).
     */
    public static function unauthorized(string $message = 'Unauthorized'): ResponseInterface
    {
        return self::json(401, 'error', $message);
    }

    /**
     * Forbidden (not enough permissions).
     */
    public static function forbidden(string $message = 'Forbidden'): ResponseInterface
    {
        return self::json(403, 'error', $message);
    }

    /**
     * Not found.
     */
    public static function notFound(string $message = 'Not found'): ResponseInterface
    {
        return self::json(404, 'error', $message);
    }

    /**
     * Conflict.
     */
    public static function conflict(string $message = 'Conflict'): ResponseInterface
    {
        return self::json(409, 'error', $message);
    }

    /**
     * Internal server error.
     */
    public static function serverError(string $message = 'Internal server error'): ResponseInterface
    {
        return self::json(500, 'error', $message);
    }

    /**
     * Paginated response.
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = 'OK'): ResponseInterface
    {
        $meta = [
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / max($perPage, 1)),
        ];

        return self::json(200, 'success', $message, $items, $meta);
    }

    /**
     * Build JSON response.
     */
    private static function json(
        int $httpCode,
        string $status,
        string $message,
        $data = null,
        array $meta = [],
        $errors = null
    ): ResponseInterface {
        $body = [
            'status'  => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return service('response')
            ->setStatusCode($httpCode)
            ->setJSON($body);
    }
}
