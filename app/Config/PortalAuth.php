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

        $globalJwtSecret = $this->resolveEnvSecret(['JWT_SECRET', 'TOKEN_SECRET']);
        $globalRefreshSecret = $this->resolveEnvSecret(['JWT_REFRESH_SECRET', 'REFRESH_TOKEN_SECRET'], $globalJwtSecret);

        $this->jwtSecret = $this->resolveEnvSecret(
            ['PORTAL_JWT_SECRET'],
            $this->jwtSecret ?: $globalJwtSecret
        );

        $this->jwtRefreshSecret = $this->resolveEnvSecret(
            ['PORTAL_JWT_REFRESH_SECRET'],
            $this->jwtRefreshSecret ?: $globalRefreshSecret ?: $this->jwtSecret
        );

        if ($this->normalizeEnvValue($this->jwtRefreshSecret) === '') {
            $this->jwtRefreshSecret = $this->jwtSecret;
        }

        $this->accessTokenExpires = $this->resolveEnvInt(
            ['PORTAL_JWT_ACCESS_EXPIRES', 'PORTAL_JWT_ACCESS_TTL'],
            $this->accessTokenExpires
        );
        $this->refreshTokenExpires = $this->resolveEnvInt(
            ['PORTAL_JWT_REFRESH_EXPIRES', 'PORTAL_JWT_REFRESH_TTL'],
            $this->refreshTokenExpires
        );
        $this->passwordResetExpires = (int) env('PORTAL_PASSWORD_RESET_EXPIRES', $this->passwordResetExpires);
        $this->bcryptCost = (int) env('PORTAL_BCRYPT_COST', $this->bcryptCost);
        $this->maxLoginAttempts = (int) env('PORTAL_MAX_LOGIN_ATTEMPTS', $this->maxLoginAttempts);
        $this->attemptWindowSeconds = (int) env('PORTAL_LOGIN_ATTEMPT_WINDOW', $this->attemptWindowSeconds);
    }

    private function resolveEnvSecret(array $keys, string $fallback = ''): string
    {
        foreach ($keys as $key) {
            $value = $this->normalizeEnvValue((string) env($key, ''));
            if ($value !== '') {
                return $value;
            }
        }

        return $this->normalizeEnvValue($fallback);
    }

    private function resolveEnvInt(array $keys, int $fallback): int
    {
        foreach ($keys as $key) {
            $raw = $this->normalizeEnvValue((string) env($key, ''));
            if ($raw !== '' && is_numeric($raw)) {
                return (int) $raw;
            }
        }

        return $fallback;
    }

    private function normalizeEnvValue(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B'\"");
    }
}
