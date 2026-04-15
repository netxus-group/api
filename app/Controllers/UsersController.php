<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\UserSpecialPermissionModel;
use App\Entities\User;
use Config\Auth;

class UsersController extends BaseApiController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * GET /api/v1/users
     */
    public function index()
    {
        $users = $this->userModel->listAll();
        return ApiResponse::ok($users);
    }

    /**
     * POST /api/v1/users
     */
    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createUser');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $id   = $this->uuid();
        $user = new User();
        $user->id         = $id;
        $user->email      = $data['email'];
        $user->role       = $data['role'] ?? 'writer';
        $user->first_name = $data['firstName'] ?? null;
        $user->last_name  = $data['lastName'] ?? null;
        $user->username   = $data['username'] ?? null;
        $user->active     = $data['active'] ?? true;
        $user->setPassword($data['password']);

        $this->userModel->insert($user);

        // Sync roles
        $roles = $data['roles'] ?? [$user->role];
        $roleModel = new UserRoleModel();
        $roleModel->syncRoles($id, $roles);

        // Sync special permissions
        if (!empty($data['specialPermissions'])) {
            $permModel = new UserSpecialPermissionModel();
            $permModel->syncPermissions($id, $data['specialPermissions']);
        }

        $result = $this->userModel->findWithAccess($id);
        return ApiResponse::created($result, 'User created');
    }

    /**
     * GET /api/v1/users/{id}/profile
     */
    public function profile(string $id)
    {
        $result = $this->userModel->findWithAccess($id);
        if (!$result) {
            return ApiResponse::notFound('User not found');
        }

        return ApiResponse::ok($result);
    }

    /**
     * PUT /api/v1/users/{id}
     */
    public function update(string $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        $data = $this->getJsonInput();

        // Check email uniqueness if changing
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            $existing = $this->userModel->findByEmail($data['email']);
            if ($existing) {
                return ApiResponse::conflict('Email already in use');
            }
        }

        $updateData = [];
        if (isset($data['email']))     $updateData['email']      = $data['email'];
        if (isset($data['firstName'])) $updateData['first_name'] = $data['firstName'];
        if (isset($data['lastName']))  $updateData['last_name']  = $data['lastName'];
        if (isset($data['username']))  $updateData['username']   = $data['username'];
        if (isset($data['role']))      $updateData['role']       = $data['role'];
        if (isset($data['active']))    $updateData['active']     = (bool) $data['active'];

        if (!empty($data['password'])) {
            $cost = config('Auth')->bcryptCost ?? 10;
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => $cost]);
        }

        if (!empty($updateData)) {
            $this->userModel->update($id, $updateData);
        }

        // Sync roles if provided
        if (isset($data['roles'])) {
            $roleModel = new UserRoleModel();
            $roleModel->syncRoles($id, $data['roles']);
        }

        // Sync special permissions if provided
        if (isset($data['specialPermissions'])) {
            $permModel = new UserSpecialPermissionModel();
            $permModel->syncPermissions($id, $data['specialPermissions']);
        }

        $result = $this->userModel->findWithAccess($id);
        return ApiResponse::ok($result, 'User updated');
    }

    /**
     * PUT /api/v1/users/{id}/access
     */
    public function updateAccess(string $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        $data = $this->getJsonInput();

        if (isset($data['roles'])) {
            $roleModel = new UserRoleModel();
            $roleModel->syncRoles($id, $data['roles']);
        }

        if (isset($data['specialPermissions'])) {
            $permModel = new UserSpecialPermissionModel();
            $permModel->syncPermissions($id, $data['specialPermissions']);
        }

        $result = $this->userModel->findWithAccess($id);
        return ApiResponse::ok($result, 'User access updated');
    }

    /**
     * DELETE /api/v1/users/{id}
     */
    public function delete(string $id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        // Prevent self-deletion
        if ($id === $this->userId()) {
            return ApiResponse::badRequest('Cannot deactivate your own account');
        }

        $this->userModel->update($id, ['active' => false]);
        return ApiResponse::noContent('User deactivated');
    }

    /**
     * GET /api/v1/users/special-permissions
     */
    public function specialPermissions()
    {
        $authConfig = config('Auth');
        return ApiResponse::ok($authConfig->specialPermissions);
    }
}
