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
            return $this->mapException($e);
        } catch (\Throwable) {
            return ApiResponse::serverError('Authentication service error');
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
            return $this->mapException($e);
        } catch (\Throwable) {
            return ApiResponse::serverError('Authentication service error');
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

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'forgotPassword');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $authService = service('authService');
        $result = $authService->requestPasswordReset((string) $data['email']);
        return ApiResponse::ok($result, 'If the email exists, reset instructions were generated');
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'resetPassword');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $authService = service('authService');

        try {
            $authService->resetPassword((string) $data['resetToken'], (string) $data['newPassword']);
            return ApiResponse::ok(null, 'Password reset successful');
        } catch (\RuntimeException $exception) {
            return match ($exception->getCode()) {
                401 => ApiResponse::unauthorized($exception->getMessage()),
                404 => ApiResponse::notFound($exception->getMessage()),
                422 => ApiResponse::validationError(['newPassword' => $exception->getMessage()]),
                default => ApiResponse::badRequest($exception->getMessage()),
            };
        }
    }

    private function mapException(\RuntimeException $exception)
    {
        return match ($exception->getCode()) {
            401 => ApiResponse::unauthorized($exception->getMessage()),
            403 => ApiResponse::forbidden($exception->getMessage()),
            404 => ApiResponse::notFound($exception->getMessage()),
            409 => ApiResponse::conflict($exception->getMessage()),
            422 => ApiResponse::validationError(['auth' => $exception->getMessage()]),
            429 => service('response')->setStatusCode(429)->setJSON([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]),
            500 => ApiResponse::serverError($exception->getMessage()),
            default => ApiResponse::badRequest($exception->getMessage()),
        };
    }
}
