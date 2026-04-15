<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\RoleProfileModel;

class RolesController extends BaseApiController
{
    private RoleProfileModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleProfileModel();
    }

    /**
     * GET /api/v1/roles
     */
    public function index()
    {
        $roles = $this->roleModel->findAll();

        if (empty($roles)) {
            // Return defaults from config
            $authConfig = config('Auth');
            $roles = [];
            foreach ($authConfig->roleCapabilities as $key => $caps) {
                $roles[] = [
                    'key'          => $key,
                    'name'         => ucfirst(str_replace('_', ' ', $key)),
                    'capabilities' => $caps,
                ];
            }
        }

        return ApiResponse::ok($roles);
    }

    /**
     * PUT /api/v1/roles/{key} (super_admin only)
     */
    public function update(string $key)
    {
        $data = $this->getJsonInput();

        $existing = $this->roleModel->find($key);

        $profileData = [
            'key'          => $key,
            'name'         => $data['name'] ?? ucfirst(str_replace('_', ' ', $key)),
            'capabilities' => isset($data['capabilities']) ? json_encode($data['capabilities']) : '[]',
        ];

        if ($existing) {
            $this->roleModel->update($key, $profileData);
        } else {
            $this->roleModel->insert($profileData);
        }

        $result = $this->roleModel->find($key);
        return ApiResponse::ok($result, 'Role updated');
    }

    /**
     * GET /api/v1/roles/special-permissions
     */
    public function specialPermissions()
    {
        $authConfig = config('Auth');

        $descriptions = [
            'users.manage_roles'                   => 'Manage user roles',
            'users.manage_special_permissions'      => 'Manage user special permissions',
            'images.manage'                         => 'Manage images (CRUD, metadata)',
            'images.delete'                         => 'Delete images',
            'news.schedule'                         => 'Schedule news publications',
            'newsletter.manage'                     => 'Manage newsletter subscribers',
        ];

        $result = [];
        foreach ($authConfig->specialPermissions as $perm) {
            $result[] = [
                'key'         => $perm,
                'description' => $descriptions[$perm] ?? $perm,
            ];
        }

        return ApiResponse::ok($result);
    }
}
