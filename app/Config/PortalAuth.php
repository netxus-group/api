<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class PortalAuth extends BaseConfig
{
    public string $jwtSecret = '';

    public string $jwtRefreshSecret = '';

    public int $accessTokenExpires = 900;

    public int $refreshTokenExpires = 1209600;

    public int $passwordResetExpires = 3600;

    public int $bcryptCost = 10;

    public int $maxLoginAttempts = 8;

    public int $attemptWindowSeconds = 300;

    public function __construct()
    {
        parent::__construct();

        $this->jwtSecret = env('PORTAL_JWT_SECRET', $this->jwtSecret ?: (string) env('JWT_SECRET', ''));
        $this->jwtRefreshSecret = env('PORTAL_JWT_REFRESH_SECRET', $this->jwtRefreshSecret ?: (string) env('JWT_REFRESH_SECRET', ''));
        $this->accessTokenExpires = (int) env('PORTAL_JWT_ACCESS_EXPIRES', $this->accessTokenExpires);
        $this->refreshTokenExpires = (int) env('PORTAL_JWT_REFRESH_EXPIRES', $this->refreshTokenExpires);
        $this->passwordResetExpires = (int) env('PORTAL_PASSWORD_RESET_EXPIRES', $this->passwordResetExpires);
        $this->bcryptCost = (int) env('PORTAL_BCRYPT_COST', $this->bcryptCost);
        $this->maxLoginAttempts = (int) env('PORTAL_MAX_LOGIN_ATTEMPTS', $this->maxLoginAttempts);
        $this->attemptWindowSeconds = (int) env('PORTAL_LOGIN_ATTEMPT_WINDOW', $this->attemptWindowSeconds);
    }
}
