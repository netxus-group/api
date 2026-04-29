<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;

class PortalAuthController extends PortalBaseApiController
{
    public function register()
    {
        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
            'firstName' => 'permit_empty|min_length[2]|max_length[120]',
            'lastName' => 'permit_empty|min_length[2]|max_length[120]',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $service = service('portalAuthService');
            $result = $service->register(
                $data,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );

            return ApiResponse::created($result, 'Portal user registered successfully');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        } catch (\Throwable) {
            return ApiResponse::serverError('Portal authentication service error');
        }
    }

    public function login()
    {
        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $service = service('portalAuthService');
            $result = $service->login(
                (string) $data['email'],
                (string) $data['password'],
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );

            return ApiResponse::ok($result, 'Portal login successful');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        } catch (\Throwable) {
            return ApiResponse::serverError('Portal authentication service error');
        }
    }

    public function refresh()
    {
        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'refreshToken' => 'required|min_length[16]',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $service = service('portalAuthService');
            $result = $service->refresh(
                (string) $data['refreshToken'],
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );

            return ApiResponse::ok($result, 'Portal token refreshed');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        } catch (\Throwable) {
            return ApiResponse::serverError('Portal authentication service error');
        }
    }

    public function logout()
    {
        $data = $this->getJsonInput();

        if (empty($data['refreshToken'])) {
            return ApiResponse::badRequest('refreshToken is required');
        }

        $service = service('portalAuthService');
        $service->logout((string) $data['refreshToken']);

        return ApiResponse::ok(null, 'Portal logout successful');
    }

    public function me()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $service = service('portalAuthService');
        $profile = $service->me($portalUserId);

        if (!$profile) {
            return ApiResponse::notFound('Portal user not found');
        }

        return ApiResponse::ok($profile);
    }

    public function forgotPassword()
    {
        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'email' => 'required|valid_email',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $service = service('portalAuthService');
        $result = $service->requestPasswordReset((string) $data['email']);

        return ApiResponse::ok($result, 'If the email exists, reset instructions were generated');
    }

    public function resetPassword()
    {
        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'resetToken' => 'required|min_length[20]',
            'newPassword' => 'required|min_length[8]',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $service = service('portalAuthService');
            $service->resetPassword((string) $data['resetToken'], (string) $data['newPassword']);

            return ApiResponse::ok(null, 'Password reset successful');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        } catch (\Throwable) {
            return ApiResponse::serverError('Portal authentication service error');
        }
    }

    private function mapException(\RuntimeException $exception)
    {
        return match ($exception->getCode()) {
            401 => ApiResponse::unauthorized($exception->getMessage()),
            404 => ApiResponse::notFound($exception->getMessage()),
            409 => ApiResponse::conflict($exception->getMessage()),
            422 => ApiResponse::validationError(['portalAuth' => $exception->getMessage()]),
            429 => service('response')->setStatusCode(429)->setJSON([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]),
            500 => ApiResponse::serverError($exception->getMessage()),
            default => ApiResponse::badRequest($exception->getMessage()),
        };
    }
}
