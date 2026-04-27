<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * JWT & Auth configuration for Netxus API.
 */
class Auth extends BaseConfig
{
    /** Secret key for signing access tokens */
    public string $jwtSecret = '';

    /** Secret key for signing refresh tokens */
    public string $jwtRefreshSecret = '';

    /** Access token TTL in seconds (default 15min) */
    public int $accessTokenExpires = 900;

    /** Refresh token TTL in seconds (default 7 days) */
    public int $refreshTokenExpires = 604800;

    /** JWT algorithm */
    public string $jwtAlgorithm = 'HS256';

    /** Newsletter unsubscribe token secret */
    public string $newsletterSecret = '';

    /** Newsletter unsubscribe token TTL (180 days) */
    public int $newsletterTokenExpires = 15552000;

    /** Password hash cost (bcrypt rounds) */
    public int $bcryptCost = 10;

    /** Password reset token TTL in seconds (default 60min) */
    public int $passwordResetExpires = 3600;

    /** Available roles */
    public array $roles = ['super_admin', 'editor', 'writer'];

    /** Available direct permissions */
    public array $specialPermissions = [
        'users.manage_roles',
        'users.manage_special_permissions',
        'images.manage',
        'images.delete',
        'news.schedule',
        'newsletter.manage',
    ];

    /** Default capabilities per role */
    public array $roleCapabilities = [
        'super_admin' => ['*'],
        'editor'      => [
            'news.create', 'news.edit', 'news.publish', 'news.review',
            'news.schedule', 'news.delete',
            'categories.manage', 'tags.manage', 'authors.manage',
            'images.manage', 'images.delete',
            'ads.manage', 'newsletter.manage',
            'integrations.manage', 'home_layout.manage',
            'polls.manage', 'settings.manage',
        ],
        'writer' => [
            'news.create', 'news.edit_own', 'news.submit_review',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->jwtSecret           = env('JWT_SECRET', $this->jwtSecret);
        $this->jwtRefreshSecret    = env('JWT_REFRESH_SECRET', $this->jwtRefreshSecret);
        $this->accessTokenExpires  = (int) env('TOKEN_TTL', env('JWT_ACCESS_EXPIRES', $this->accessTokenExpires));
        $this->refreshTokenExpires = (int) env('JWT_REFRESH_EXPIRES', $this->refreshTokenExpires);
        $this->passwordResetExpires = (int) env('JWT_PASSWORD_RESET_EXPIRES', $this->passwordResetExpires);
        $this->newsletterSecret    = env('NEWSLETTER_UNSUBSCRIBE_SECRET', $this->newsletterSecret);
    }

    /**
     * Check if a role has a specific capability.
     */
    public function roleHasCapability(string $role, string $capability): bool
    {
        $caps = $this->roleCapabilities[$role] ?? [];

        if (in_array('*', $caps, true)) {
            return true;
        }

        return in_array($capability, $caps, true);
    }
}
