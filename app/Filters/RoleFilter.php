<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\ApiResponse;
use App\Models\UserSpecialPermissionModel;

/**
 * Role-based authorization filter.
 *
 * Usage in routes: 'filter' => 'role:super_admin,editor'
 * Also checks direct special permissions from DB.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = $request->auth ?? null;

        if (!$auth) {
            return ApiResponse::unauthorized('Authentication required');
        }

        // super_admin always passes
        if ($auth->role === 'super_admin') {
            return null;
        }

        // Check if user's role is in the allowed list
        $allowedRoles = $arguments ?? [];

        if (in_array($auth->role, $allowedRoles, true)) {
            return null;
        }

        // Check for special permissions in DB as fallback
        $permModel   = new UserSpecialPermissionModel();
        $permissions = $permModel->getUserPermissions($auth->userId);
        $auth->specialPermissions = $permissions;

        // Role capabilities from config
        $authConfig = config('Auth');
        $roleCaps   = $authConfig->roleCapabilities[$auth->role] ?? [];

        // If the route requires a role the user doesn't have,
        // they need specific permissions which are checked at controller level
        // For now, deny if role doesn't match
        if (!empty($allowedRoles) && !in_array($auth->role, $allowedRoles, true)) {
            return ApiResponse::forbidden('Insufficient permissions for this action');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
