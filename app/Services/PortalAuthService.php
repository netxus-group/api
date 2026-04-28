<?php

namespace App\Services;

use App\Libraries\PortalJwtManager;
use App\Models\PortalUserModel;
use App\Models\PortalUserPasswordResetModel;
use App\Models\PortalUserPreferenceModel;
use App\Models\PortalUserSessionModel;
use Config\PortalAuth;

class PortalAuthService
{
    private PortalUserModel $portalUserModel;
    private PortalUserSessionModel $sessionModel;
    private PortalUserPreferenceModel $preferenceModel;
    private PortalUserPasswordResetModel $passwordResetModel;
    private PortalJwtManager $jwt;
    private PortalAuth $config;

    public function __construct()
    {
        $this->portalUserModel = new PortalUserModel();
        $this->sessionModel = new PortalUserSessionModel();
        $this->preferenceModel = new PortalUserPreferenceModel();
        $this->passwordResetModel = new PortalUserPasswordResetModel();
        $this->jwt = service('portalJwtManager');
        $this->config = config('PortalAuth');
    }

    public function register(array $data, string $ipAddress = '', string $userAgent = ''): array
    {
        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));
        $this->enforceRateLimit('register', $email . '|' . $ipAddress);

        if ($email === '') {
            throw new \RuntimeException('Email is required', 422);
        }

        if ($this->portalUserModel->emailExists($email)) {
            throw new \RuntimeException('Email already registered', 409);
        }

        $password = (string) ($data['password'] ?? '');
        if (strlen($password) < 8) {
            throw new \RuntimeException('Password must have at least 8 characters', 422);
        }

        $portalUserId = $this->uuid();
        $firstName = $this->sanitizeName((string) ($data['firstName'] ?? $data['first_name'] ?? ''));
        $lastName = $this->sanitizeName((string) ($data['lastName'] ?? $data['last_name'] ?? ''));

        $displayName = trim((string) ($data['displayName'] ?? $data['display_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        $this->portalUserModel->insert([
            'id' => $portalUserId,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptCost]),
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'display_name' => $displayName !== '' ? $displayName : null,
            'avatar_url' => null,
            'active' => 1,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $this->preferenceModel->insert([
            'id' => $this->uuid(),
            'portal_user_id' => $portalUserId,
            'timezone' => $data['timezone'] ?? null,
            'language' => $data['language'] ?? 'es',
            'digest_frequency' => 'none',
            'personalization_opt_in' => 1,
        ]);

        $communication = service('communicationService');
        $communication->sendTemplateEmail($email, 'welcome_user', [
            'user_name' => $displayName !== '' ? $displayName : $email,
            'user_email' => $email,
            'site_name' => config('Communications')->siteName,
            'site_url' => config('Communications')->portalUrl,
        ], [
            'templateKey' => 'welcome_user',
            'recipient_user_id' => $portalUserId,
            'dedupeKey' => 'portal-welcome:' . $portalUserId,
        ]);

        service('portalUserService')->createNotification($portalUserId, [
            'type' => 'welcome',
            'title' => 'Bienvenido a Netxus',
            'body' => 'Tu cuenta fue creada correctamente. Ya podes seguir noticias, encuestas y novedades.',
            'url' => rtrim((string) config('Communications')->portalUrl, '/') . '/mi-perfil',
            'metadata' => [
                'email' => $email,
            ],
        ], 'welcome_user:' . $portalUserId);

        $this->clearRateLimit('register', $email . '|' . $ipAddress);

        $user = $this->portalUserModel->find($portalUserId);
        return $this->issueTokens($user, $ipAddress, $userAgent);
    }

    public function login(string $email, string $password, string $ipAddress = '', string $userAgent = ''): array
    {
        $email = $this->normalizeEmail($email);
        $this->enforceRateLimit('login', $email . '|' . $ipAddress);

        $user = $this->portalUserModel->findActiveByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            throw new \RuntimeException('Invalid credentials', 401);
        }

        $this->portalUserModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        $this->clearRateLimit('login', $email . '|' . $ipAddress);

        return $this->issueTokens($this->portalUserModel->find($user['id']), $ipAddress, $userAgent);
    }

    public function refresh(string $refreshToken, string $ipAddress = '', string $userAgent = ''): array
    {
        $decoded = $this->jwt->validateRefreshToken($refreshToken);

        $session = $this->sessionModel->findActiveByHash(PortalJwtManager::hashToken($refreshToken));
        if (!$session) {
            throw new \RuntimeException('Portal refresh token revoked or expired', 401);
        }

        $portalUser = $this->portalUserModel->find($decoded->sub);
        if (!$portalUser || (int) ($portalUser['active'] ?? 0) !== 1) {
            throw new \RuntimeException('Portal user not found or inactive', 401);
        }

        $this->sessionModel->revokeById($session['id']);

        return $this->issueTokens($portalUser, $ipAddress, $userAgent, false);
    }

    public function logout(string $refreshToken): void
    {
        $hash = PortalJwtManager::hashToken($refreshToken);
        $session = $this->sessionModel->findActiveByHash($hash);
        if ($session) {
            $this->sessionModel->revokeById($session['id']);
        }
    }

    public function me(string $portalUserId): ?array
    {
        $user = $this->portalUserModel->findPublicProfile($portalUserId);
        if (!$user) {
            return null;
        }

        return $this->normalizeProfile($user);
    }

    public function requestPasswordReset(string $email): array
    {
        $email = $this->normalizeEmail($email);
        $user = $this->portalUserModel->findActiveByEmail($email);

        if (!$user) {
            return ['requested' => true];
        }

        $rawToken = bin2hex(random_bytes(32));

        $this->passwordResetModel->insert([
            'id' => $this->uuid(),
            'portal_user_id' => $user['id'],
            'token_hash' => PortalJwtManager::hashToken($rawToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->passwordResetExpires),
        ]);

        $communication = service('communicationService');
        $communication->sendTemplateEmail($email, 'password_reset', [
            'user_name' => (string) ($user['display_name'] ?? $email),
            'user_email' => $email,
            'reset_url' => rtrim(config('Communications')->portalUrl, '/') . '/auth/login?resetToken=' . $rawToken,
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->passwordResetExpires),
            'site_name' => config('Communications')->siteName,
            'site_url' => config('Communications')->portalUrl,
        ], [
            'templateKey' => 'password_reset',
            'recipient_user_id' => $user['id'],
            'dedupeKey' => 'portal-password-reset:' . $user['id'],
        ]);

        return [
            'requested' => true,
            'expiresIn' => $this->config->passwordResetExpires,
        ];
    }

    public function resetPassword(string $resetToken, string $newPassword): void
    {
        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('New password must have at least 8 characters', 422);
        }

        $row = $this->passwordResetModel->findValidByTokenHash(PortalJwtManager::hashToken($resetToken));
        if (!$row) {
            throw new \RuntimeException('Invalid or expired reset token', 401);
        }

        $this->portalUserModel->update($row['portal_user_id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptCost]),
        ]);

        $this->passwordResetModel->update($row['id'], ['used_at' => date('Y-m-d H:i:s')]);
        $this->sessionModel->revokeAllForUser($row['portal_user_id']);
    }

    public function changePassword(string $portalUserId, string $currentPassword, string $newPassword): void
    {
        $user = $this->portalUserModel->find($portalUserId);
        if (!$user) {
            throw new \RuntimeException('Portal user not found', 404);
        }

        if (!password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            throw new \RuntimeException('Current password is invalid', 401);
        }

        if (strlen($newPassword) < 8) {
            throw new \RuntimeException('New password must have at least 8 characters', 422);
        }

        $this->portalUserModel->update($portalUserId, [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptCost]),
        ]);

        $this->sessionModel->revokeAllForUser($portalUserId);
    }

    private function issueTokens(array $portalUser, string $ipAddress, string $userAgent, bool $includeProfile = true): array
    {
        $sessionId = $this->uuid();
        $accessToken = $this->jwt->createAccessToken($portalUser['id'], (string) $portalUser['email']);
        $refreshToken = $this->jwt->createRefreshToken($portalUser['id'], $sessionId);

        $this->sessionModel->insert([
            'id' => $sessionId,
            'portal_user_id' => $portalUser['id'],
            'refresh_token_hash' => PortalJwtManager::hashToken($refreshToken),
            'ip_address' => $ipAddress ?: null,
            'user_agent' => $userAgent !== '' ? mb_substr($userAgent, 0, 500) : null,
            'expires_at' => date('Y-m-d H:i:s', time() + $this->config->refreshTokenExpires),
            'revoked_at' => null,
        ]);

        $response = [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'expiresIn' => $this->config->accessTokenExpires,
            'refreshExpiresIn' => $this->config->refreshTokenExpires,
        ];

        if ($includeProfile) {
            $response['user'] = $this->normalizeProfile($portalUser);
        }

        return $response;
    }

    private function normalizeProfile(array $user): array
    {
        $firstName = trim((string) ($user['first_name'] ?? ''));
        $lastName = trim((string) ($user['last_name'] ?? ''));
        $displayName = trim((string) ($user['display_name'] ?? ''));

        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        if ($displayName === '') {
            $displayName = (string) ($user['email'] ?? 'Portal user');
        }

        return [
            'id' => (string) ($user['id'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'firstName' => $firstName !== '' ? $firstName : null,
            'lastName' => $lastName !== '' ? $lastName : null,
            'displayName' => $displayName,
            'avatarUrl' => $user['avatar_url'] ?? null,
            'initials' => $this->buildInitials($firstName, $lastName, (string) ($user['email'] ?? '')),
            'lastLoginAt' => $user['last_login_at'] ?? null,
            'createdAt' => $user['created_at'] ?? null,
        ];
    }

    private function enforceRateLimit(string $scope, string $keySeed): void
    {
        $cache = cache();
        if (!$cache) {
            return;
        }

        $key = $this->rateKey($scope, $keySeed);
        $window = $this->config->attemptWindowSeconds;

        $bucket = $cache->get($key);
        if (!is_array($bucket) || !isset($bucket['count'], $bucket['started_at'])) {
            $bucket = ['count' => 0, 'started_at' => time()];
        }

        if ((time() - (int) $bucket['started_at']) > $window) {
            $bucket = ['count' => 0, 'started_at' => time()];
        }

        if ((int) $bucket['count'] >= $this->config->maxLoginAttempts) {
            throw new \RuntimeException('Too many attempts. Try again in a few minutes.', 429);
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        $cache->save($key, $bucket, $window);
    }

    private function clearRateLimit(string $scope, string $keySeed): void
    {
        $cache = cache();
        if (!$cache) {
            return;
        }

        $cache->delete($this->rateKey($scope, $keySeed));
    }

    private function rateKey(string $scope, string $keySeed): string
    {
        // CI4 cache keys cannot contain reserved chars like ":".
        return 'portal_auth_' . $scope . '_' . sha1($keySeed);
    }

    private function buildInitials(string $firstName, string $lastName, string $email): string
    {
        $seed = trim($firstName . ' ' . $lastName);
        if ($seed === '') {
            $seed = $email;
        }

        $parts = preg_split('/\s+/', trim($seed)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'NU';
    }

    private function sanitizeName(string $value): string
    {
        return trim(mb_substr($value, 0, 120));
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
