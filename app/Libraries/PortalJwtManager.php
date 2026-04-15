<?php

namespace App\Libraries;

use Config\PortalAuth;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class PortalJwtManager
{
    private PortalAuth $config;

    public function __construct(PortalAuth $config)
    {
        $this->config = $config;
    }

    public function createAccessToken(string $portalUserId, string $email): string
    {
        $now = time();

        $payload = [
            'iss'       => base_url(),
            'sub'       => $portalUserId,
            'email'     => $email,
            'tokenType' => 'portal_access',
            'iat'       => $now,
            'exp'       => $now + $this->config->accessTokenExpires,
        ];

        return JWT::encode($payload, $this->config->jwtSecret, 'HS256');
    }

    public function createRefreshToken(string $portalUserId, string $sessionId): string
    {
        $now = time();

        $payload = [
            'iss'       => base_url(),
            'sub'       => $portalUserId,
            'sessionId' => $sessionId,
            'tokenType' => 'portal_refresh',
            'iat'       => $now,
            'exp'       => $now + $this->config->refreshTokenExpires,
        ];

        return JWT::encode($payload, $this->config->jwtRefreshSecret, 'HS256');
    }

    public function validateAccessToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtSecret, 'HS256'));
        } catch (ExpiredException) {
            throw new \RuntimeException('Portal access token expired', 401);
        } catch (\Throwable) {
            throw new \RuntimeException('Invalid portal access token', 401);
        }

        if (($decoded->tokenType ?? '') !== 'portal_access') {
            throw new \RuntimeException('Invalid portal token type', 401);
        }

        return $decoded;
    }

    public function validateRefreshToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtRefreshSecret, 'HS256'));
        } catch (ExpiredException) {
            throw new \RuntimeException('Portal refresh token expired', 401);
        } catch (\Throwable) {
            throw new \RuntimeException('Invalid portal refresh token', 401);
        }

        if (($decoded->tokenType ?? '') !== 'portal_refresh') {
            throw new \RuntimeException('Invalid portal refresh token type', 401);
        }

        return $decoded;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
