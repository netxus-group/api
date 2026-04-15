<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\ApiResponse;
use Psr\Log\LoggerInterface;

/**
 * Base controller for all API controllers.
 * Provides auth context access and common helpers.
 */
abstract class BaseApiController extends Controller
{
    protected $format = 'json';

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Get authenticated user data from the request (set by AuthFilter).
     */
    protected function auth(): ?object
    {
        return $this->request->auth ?? null;
    }

    /**
     * Get authenticated user's ID.
     */
    protected function userId(): ?string
    {
        return $this->auth()?->userId;
    }

    /**
     * Get authenticated user's role.
     */
    protected function userRole(): ?string
    {
        return $this->auth()?->role;
    }

    /**
     * Check if current user is super_admin.
     */
    protected function isSuperAdmin(): bool
    {
        return $this->userRole() === 'super_admin';
    }

    /**
     * Check if current user is editor.
     */
    protected function isEditor(): bool
    {
        return $this->userRole() === 'editor';
    }

    /**
     * Check if current user is writer.
     */
    protected function isWriter(): bool
    {
        return $this->userRole() === 'writer';
    }

    /**
     * Check if user has a specific special permission.
     */
    protected function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check role default capabilities
        $authConfig = config('Auth');
        if ($authConfig->roleHasCapability($this->userRole(), $permission)) {
            return true;
        }

        // Check direct special permissions
        $specialPerms = $this->auth()->specialPermissions ?? null;
        if ($specialPerms === null) {
            $permModel    = new \App\Models\UserSpecialPermissionModel();
            $specialPerms = $permModel->getUserPermissions($this->userId());
        }

        return in_array($permission, $specialPerms, true);
    }

    /**
     * Get JSON body from request.
     */
    protected function getJsonInput(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : [];
    }

    /**
     * Validate input with named rules or custom rules.
     * Returns errors array or null if valid.
     */
    protected function validateInput(array $data, $rules): ?array
    {
        $validation = \Config\Services::validation();

        if (is_string($rules)) {
            $validation->setRuleGroup($rules);
        } else {
            $validation->setRules($rules);
        }

        if (!$validation->run($data)) {
            return $validation->getErrors();
        }

        return null;
    }

    /**
     * Parse pagination params from query string.
     */
    protected function paginationParams(int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        $page    = max(1, (int) $this->request->getGet('page'));
        $perPage = min($maxPerPage, max(1, (int) ($this->request->getGet('perPage') ?: $defaultPerPage)));

        return [$page, $perPage];
    }

    /**
     * Generate a UUID v4.
     */
    protected function uuid(): string
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
