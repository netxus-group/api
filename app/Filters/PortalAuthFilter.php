<?php

namespace App\Filters;

use App\Libraries\ApiResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PortalAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            return ApiResponse::unauthorized('Missing or invalid portal authorization header');
        }

        $token = substr($header, 7);

        try {
            $decoded = service('portalJwtManager')->validateAccessToken($token);
        } catch (\Throwable $exception) {
            return ApiResponse::unauthorized($exception->getMessage() ?: 'Invalid portal token');
        }

        $request->portalAuth = (object) [
            'portalUserId' => $decoded->sub,
            'email'        => $decoded->email,
        ];

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
