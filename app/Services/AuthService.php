<?php

namespace App\Services;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\UserSpecialPermissionModel;
use App\Models\RefreshTokenModel;
use App\Models\UserPasswordResetModel;
use App\Libraries\JwtManager;
use Config\Auth;

class AuthService
{
    private UserModel $userModel;
    private RefreshTokenModel $refreshModel;
    private UserPasswordResetModel $passwordResetModel;
    private JwtManager $jwt;
    private Auth $config;

    public function __construct()
    {
        $this->userModel    = new UserModel();
        $this->refreshModel = new RefreshTokenModel();
        $this->passwordResetModel = new UserPasswordResetModel();
        $this->jwt          = service('jwtManager');
        $this->config       = config('Auth');
        $this->assertJwtConfig();
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

        // Update last login timestamp
        $this->userModel->update($user->id, ['last_login_at' => date('Y-m-d H:i:s')]);

        // Generate tokens
        $role         = $this->resolvePrimaryRole($user->id);
        $accessToken  = $this->jwt->createAccessToken($user->id, $user->email, $role);
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
        $role            = $this->resolvePrimaryRole($user->id);
        $newAccessToken  = $this->jwt->createAccessToken($user->id, $user->email, $role);
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

    /**
     * Request password reset token for backoffice users.
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user || !$user->active) {
            return ['requested' => true];
        }

        $now = date('Y-m-d H:i:s');
        $this->passwordResetModel
            ->where('user_id', $user->id)
            ->where('used_at IS NULL')
            ->set(['used_at' => $now])
            ->update();

        $rawToken = bin2hex(random_bytes(32));
        $this->passwordResetModel->insert([
            'id'         => $this->generateUuid(),
            'user_id'    => $user->id,
            'token_hash' => JwtManager::hashToken($rawToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->passwordResetExpires),
        ]);

        $communication = service('communicationService');
        $communication->sendTemplateEmail($user->email, 'password_reset', [
            'user_name' => trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? '')) ?: (string) $user->email,
            'user_email' => (string) $user->email,
            'reset_url' => rtrim(config('Communications')->dashboardUrl, '/') . '/?resetToken=' . $rawToken,
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->passwordResetExpires),
            'site_name' => config('Communications')->siteName,
            'site_url' => config('Communications')->dashboardUrl,
        ], [
            'templateKey' => 'password_reset',
            'recipient_user_id' => $user->id,
            'dedupeKey' => 'password-reset:' . $user->id,
        ]);

        return [
            'requested'  => true,
            'expiresIn'  => $this->config->passwordResetExpires,
        ];
    }

    /**
     * Reset password from a valid reset token.
     */
    public function resetPassword(string $resetToken, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('New password must have at least 8 characters', 422);
        }

        $row = $this->passwordResetModel->findValidByTokenHash(JwtManager::hashToken($resetToken));
        if (!$row) {
            throw new \RuntimeException('Invalid or expired reset token', 401);
        }

        $user = $this->userModel->find((string) $row['user_id']);
        if (!$user || !$user->active) {
            throw new \RuntimeException('User not found or inactive', 404);
        }

        $cost = $this->config->bcryptCost ?? 10;
        $this->userModel->update($user->id, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $cost]),
        ]);
        $this->passwordResetModel->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);
        $this->refreshModel->revokeAllForUser($user->id);
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

    private function resolvePrimaryRole(string $userId): string
    {
        $roleModel = new UserRoleModel();
        $roles = $roleModel->getUserRoles($userId);

        if (empty($roles)) {
            throw new \RuntimeException('User has no role assigned', 403);
        }

        $priority = ['super_admin', 'editor', 'writer'];
        foreach ($priority as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return (string) $roles[0];
    }

    private function assertJwtConfig(): void
    {
        $accessSecret = trim((string) $this->config->jwtSecret);
        $refreshSecret = trim((string) $this->config->jwtRefreshSecret);

        if ($accessSecret === '' || $refreshSecret === '') {
            throw new \RuntimeException(
                'JWT configuration is missing. Define JWT_SECRET and JWT_REFRESH_SECRET in server environment.',
                500
            );
        }
    }
}
