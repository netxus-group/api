<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public array $allowedOrigins = [];

    public array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];

    public array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'x-public-api-token',
        'X-Requested-With',
        'Accept',
        'Origin',
    ];

    public bool $allowCredentials = true;

    public int $maxAge = 86400;

    public function __construct()
    {
        parent::__construct();

        $defaultOrigins = ENVIRONMENT === 'production'
            ? 'https://netxus.com.ar,https://api.netxus.com.ar,https://admin.netxus.com.ar'
            : 'http://localhost:3000,http://localhost:3001,http://localhost:3002,http://localhost:5173,http://localhost:5174';

        $origins = env('CORS_ALLOWED_ORIGINS', $defaultOrigins);
        $parsedOrigins = array_map(
            static fn(string $origin): string => self::normalizeOrigin($origin),
            explode(',', $origins)
        );
        $parsedOrigins = array_values(array_filter(
            $parsedOrigins,
            static fn(string $origin): bool => $origin !== '' && $origin !== '*'
        ));

        $this->allowedOrigins = $parsedOrigins !== []
            ? array_values(array_unique($parsedOrigins))
            : array_map([self::class, 'normalizeOrigin'], explode(',', $defaultOrigins));
    }

    public static function normalizeOrigin(string $origin): string
    {
        $origin = trim($origin);
        $origin = trim($origin, "\"'");
        return rtrim($origin, '/');
    }
}
