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
        $this->setHeaders(service('response'), $config, $origin);

        if ($request->getMethod() === 'options') {
            $response = service('response');
            $response->setStatusCode(204);
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
        $allowed = array_map([Cors::class, 'normalizeOrigin'], $config->allowedOrigins);
        $normalizedOrigin = Cors::normalizeOrigin($origin);

        if ($normalizedOrigin !== '' && in_array($normalizedOrigin, $allowed, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $normalizedOrigin);
            $response->setHeader('Vary', 'Origin');
        }

        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $config->allowedMethods));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $config->allowedHeaders));
        $response->setHeader('Access-Control-Max-Age', (string) $config->maxAge);

        if ($config->allowCredentials) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
    }
}
