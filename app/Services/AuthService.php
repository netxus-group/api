<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\UserSpecialPermissionModel;
use App\Models\RefreshTokenModel;
use App\Libraries\JwtManager;
use Config\Auth;

class AuthService
{
    private UserModel $userModel;
    private RefreshTokenModel $refreshModel;
    private JwtManager $jwt;
    private Auth $config;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->refreshModel = new RefreshTokenModel();
        $this->jwt          = service('jwtManager');
        $this->config       = config('Auth');
    }

    /**
     * Authenticate user with email/password. Returns tokens + user data.
     *
     * @throws \RuntimeException on invalid credentials
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !$user->active) {
            throw new \RuntimeException('Invalid credentials', 401);
        }

        if (!$user->verifyPassword($password)) {
            throw new \RuntimeException('Invalid credentials', 401);
        }

        // Update last_login
        $this->userModel->update($user->id, ['last_login' => date('Y-m-d H:i:s')]);

        // Generate tokens
        $accessToken  = $this->jwt->createAccessToken($user->id, $user->email, $user->role);
        $tokenId      = $this->generateUuid();
        $refreshToken = $this->jwt->createRefreshToken($user->id, $tokenId);

        // Store refresh token hash
        $this->refreshModel->insert([
            'id'         => $tokenId,
            'user_id'    => $user->id,
            'token_hash' => JwtManager::hashToken($refreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->refreshTokenExpires),
        ]);

        return [
            'accessToken'  => $accessToken,
            'refreshToken' => $refreshToken,
            'user'         => $this->getUserProfile($user->id),
        ];
    }

    /**
     * Refresh access token using a valid refresh token.
     */
    public function refresh(string $refreshToken): array
    {
        try {
            $decoded = $this->jwt->validateRefreshToken($refreshToken);
        } catch (\Throwable) {
            throw new \RuntimeException('Invalid refresh token', 401);
        }

        $hash = JwtManager::hashToken($refreshToken);
        $stored = $this->refreshModel->findActiveByHash($hash);

        if (!$stored) {
            throw new \RuntimeException('Refresh token revoked or expired', 401);
        }

        // Revoke old token
        $this->refreshModel->revoke($stored['id']);

        // Get user
        $user = $this->userModel->find($decoded->sub);
        if (!$user || !$user->active) {
            throw new \RuntimeException('User inactive', 401);
        }

        // Issue new tokens
        $newAccessToken  = $this->jwt->createAccessToken($user->id, $user->email, $user->role);
        $newTokenId      = $this->generateUuid();
        $newRefreshToken = $this->jwt->createRefreshToken($user->id, $newTokenId);

        $this->refreshModel->insert([
            'id'         => $newTokenId,
            'user_id'    => $user->id,
            'token_hash' => JwtManager::hashToken($newRefreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->refreshTokenExpires),
        ]);

        return [
            'accessToken'  => $newAccessToken,
            'refreshToken' => $newRefreshToken,
        ];
    }

    /**
     * Logout: revoke refresh token.
     */
    public function logout(string $refreshToken): void
    {
        $hash   = JwtManager::hashToken($refreshToken);
        $stored = $this->refreshModel->findActiveByHash($hash);

        if ($stored) {
            $this->refreshModel->revoke($stored['id']);
        }
    }

    /**
     * Get full user profile with roles and permissions.
     */
    public function getUserProfile(string $userId): ?array
    {
        return $this->userModel->findWithAccess($userId);
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
