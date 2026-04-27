<?php

namespace App\Libraries;

use Config\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

/**
 * Manages JWT token creation and validation.
 */
class JwtManager
{
    private Auth $config;

    public function __construct(Auth $config)
    {
        $this->config = $config;
    }

    /**
     * Create an access token for a user.
     */
    public function createAccessToken(string $userId, string $email, string $role): string
    {
        $now = time();
        $payload = [
            'iss'       => base_url(),
            'sub'       => $userId,
            'email'     => $email,
            'role'      => $role,
            'tokenType' => 'access',
            'iat'       => $now,
            'exp'       => $now + $this->config->accessTokenExpires,
        ];

        return JWT::encode($payload, $this->config->jwtSecret, $this->config->jwtAlgorithm);
    }

    /**
     * Create a refresh token.
     */
    public function createRefreshToken(string $userId, string $tokenId): string
    {
        $now = time();
        $payload = [
            'iss'       => base_url(),
            'sub'       => $userId,
            'tokenId'   => $tokenId,
            'tokenType' => 'refresh',
            'iat'       => $now,
            'exp'       => $now + $this->config->refreshTokenExpires,
        ];

        return JWT::encode($payload, $this->config->jwtRefreshSecret, $this->config->jwtAlgorithm);
    }

    /**
     * Decode and validate an access token.
     *
     * @return object Decoded payload
     * @throws \Exception on invalid/expired tokens
     */
    public function validateAccessToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtSecret, $this->config->jwtAlgorithm));

            if (($decoded->tokenType ?? '') !== 'access') {
                throw new \RuntimeException('Invalid token type', 401);
            }

            return $decoded;
        } catch (ExpiredException $e) {
            throw new \RuntimeException('Token expired', 401);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invalid token', 401);
        }
    }

    /**
     * Decode and validate a refresh token.
     */
    public function validateRefreshToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtRefreshSecret, $this->config->jwtAlgorithm));

            if (($decoded->tokenType ?? '') !== 'refresh') {
                throw new \RuntimeException('Invalid token type', 401);
            }

            return $decoded;
        } catch (ExpiredException $e) {
            throw new \RuntimeException('Refresh token expired', 401);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invalid refresh token', 401);
        }
    }

    /**
     * Create a newsletter unsubscribe token.
     */
    public function createNewsletterToken(string $subscriberId, string $email): string
    {
        $now = time();
        $payload = [
            'sub'       => $subscriberId,
            'email'     => $email,
            'tokenType' => 'newsletter_unsubscribe',
            'iat'       => $now,
            'exp'       => $now + $this->config->newsletterTokenExpires,
        ];

        return JWT::encode($payload, $this->config->newsletterSecret, $this->config->jwtAlgorithm);
    }

    /**
     * Validate a newsletter unsubscribe token.
     */
    public function validateNewsletterToken(string $token): object
    {
        $decoded = JWT::decode($token, new Key($this->config->newsletterSecret, $this->config->jwtAlgorithm));

        if (($decoded->tokenType ?? '') !== 'newsletter_unsubscribe') {
            throw new \RuntimeException('Invalid token type');
        }

        return $decoded;
    }

    /**
     * Hash a token for DB storage (SHA-256).
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
