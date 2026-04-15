<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use App\Filters\AuthFilter;
use App\Filters\PortalAuthFilter;
use App\Filters\RoleFilter;
use App\Filters\CorsFilter;

class Filters extends BaseFilters
{
    /**
     * Map of filter aliases.
     */
    public array $aliases = [
        'csrf'          => \CodeIgniter\Filters\CSRF::class,
        'toolbar'       => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'      => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars'  => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'forcehttps'    => \CodeIgniter\Filters\ForceHTTPS::class,
        'pagecache'     => \CodeIgniter\Filters\PageCache::class,
        'performance'   => \CodeIgniter\Filters\PerformanceMetrics::class,
        'auth'          => AuthFilter::class,
        'portalAuth'    => PortalAuthFilter::class,
        'role'          => RoleFilter::class,
        'cors'          => CorsFilter::class,
    ];

    /**
     * Always-applied filters.
     */
    public array $globals = [
        'before' => [
            'cors',
        ],
        'after' => [
            'cors',
        ],
    ];

    /**
     * Filters per HTTP method.
     */
    public array $methods = [];

    /**
     * Filter aliases applied per URI pattern.
     */
    public array $filters = [];
}
