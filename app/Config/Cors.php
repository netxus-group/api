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
        'X-Requested-With',
        'Accept',
        'Origin',
    ];

    public bool $allowCredentials = true;

    public int $maxAge = 86400;

    public function __construct()
    {
        parent::__construct();

        $origins = env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3002');
        $this->allowedOrigins = array_map('trim', explode(',', $origins));
    }
}
