<?php

namespace App\Filters;

use App\Libraries\ApiResponse;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PublicApiTokenFilter implements FilterInterface
{
    private const HEADER_NAME = 'x-public-api-token';

    public function before(RequestInterface $request, $arguments = null)
    {
        if (strtolower($request->getMethod()) === 'options') {
            return null;
        }

        $expectedToken = $this->normalizeToken((string) getenv('PUBLIC_API_SECRET'));
        if ($expectedToken === '') {
            $expectedToken = $this->normalizeToken((string) env('PUBLIC_API_SECRET', ''));
        }
        if ($expectedToken === '') {
            return ApiResponse::serverError('Public API secret is not configured');
        }

        $providedToken = $this->normalizeToken($request->getHeaderLine(self::HEADER_NAME));

        if ($providedToken === '') {
            return ApiResponse::unauthorized('Missing public API token');
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            return ApiResponse::forbidden('Invalid public API token');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }

    private function normalizeToken(string $token): string
    {
        $normalized = trim($token);

        if (strlen($normalized) >= 2) {
            $first = $normalized[0];
            $last  = $normalized[strlen($normalized) - 1];

            if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
                $normalized = substr($normalized, 1, -1);
            }
        }

        return trim($normalized);
    }
}
