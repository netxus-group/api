<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;

class AuthController extends BaseApiController
{
    /**
     * POST /api/v1/auth/login
     */
    public function login()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'login');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $authService = service('authService');
            $result      = $authService->login($data['email'], $data['password']);
            return ApiResponse::ok($result, 'Login successful');
        } catch (\RuntimeException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'refresh');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $authService = service('authService');
            $result      = $authService->refresh($data['refreshToken']);
            return ApiResponse::ok($result, 'Token refreshed');
        } catch (\RuntimeException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        $data = $this->getJsonInput();
        if (empty($data['refreshToken'])) {
            return ApiResponse::badRequest('refreshToken is required');
        }

        $authService = service('authService');
        $authService->logout($data['refreshToken']);

        return ApiResponse::ok(null, 'Logged out');
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me()
    {
        $authService = service('authService');
        $profile     = $authService->getUserProfile($this->userId());

        if (!$profile) {
            return ApiResponse::notFound('User not found');
        }

        return ApiResponse::ok($profile);
    }
}
