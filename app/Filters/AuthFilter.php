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
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (empty($header) || !str_starts_with($header, 'Bearer ')) {
            return ApiResponse::unauthorized('Missing or invalid Authorization header');
        }

        $token = substr($header, 7);

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
            return ApiResponse::unauthorized('Invalid or expired token');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
