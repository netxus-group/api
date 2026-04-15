<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Cors;

/**
 * CORS filter for API cross-origin requests.
 */
class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(Cors::class);
        $origin = $request->getHeaderLine('Origin');

        if ($request->getMethod() === 'options') {
            $response = service('response');
            $response->setStatusCode(204);
            $this->setHeaders($response, $config, $origin);
            return $response;
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $config = config(Cors::class);
        $origin = $request->getHeaderLine('Origin');
        $this->setHeaders($response, $config, $origin);
    }

    private function setHeaders(ResponseInterface $response, Cors $config, string $origin): void
    {
        $allowed = $config->allowedOrigins;

        if (in_array('*', $allowed, true) || in_array($origin, $allowed, true)) {
            $effectiveOrigin = $origin ?: '*';
        } else {
            $effectiveOrigin = '';
        }

        if ($effectiveOrigin) {
            $response->setHeader('Access-Control-Allow-Origin', $effectiveOrigin);
        }

        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $config->allowedMethods));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $config->allowedHeaders));
        $response->setHeader('Access-Control-Max-Age', (string) $config->maxAge);

        if ($config->allowCredentials) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }
}
