<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\ApiResponse;

/**
 * JWT Authentication filter.
 * Validates Bearer token and injects auth data into request.
 */
class AuthFilter implements FilterInterface
{
    private const UNAUTHORIZED_MESSAGE = 'Unauthorized';

    public function before(RequestInterface $request, $arguments = null)
    {
        $header = trim($request->getHeaderLine('Authorization'));
        if ($header === '') {
            $header = trim((string) $request->getServer('HTTP_AUTHORIZATION'));
        }
        if ($header === '') {
            $header = trim((string) $request->getServer('REDIRECT_HTTP_AUTHORIZATION'));
        }

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return ApiResponse::unauthorized(self::UNAUTHORIZED_MESSAGE);
        }

        $token = trim($matches[1]);
        if ($token === '') {
            return ApiResponse::unauthorized(self::UNAUTHORIZED_MESSAGE);
        }

        try {
            $jwt     = service('jwtManager');
            $decoded = $jwt->validateAccessToken($token);

            // Inject auth context into request for controllers
            $request->auth = (object) [
                'userId' => $decoded->sub,
                'email'  => $decoded->email,
                'role'   => $decoded->role,
            ];
        } catch (\Throwable $e) {
            return ApiResponse::unauthorized(self::UNAUTHORIZED_MESSAGE);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
