<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;

class PortalUserController extends PortalBaseApiController
{
    public function profile()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $service = service('portalUserService');

        if ($this->request->getMethod() === 'get') {
            $profile = $service->getProfile($portalUserId);
            if (!$profile) {
                return ApiResponse::notFound('Portal user profile not found');
            }

            return ApiResponse::ok($profile);
        }

        $data = $this->getJsonInput();

        try {
            $profile = $service->updateProfile($portalUserId, $data);
            return ApiResponse::ok($profile, 'Portal profile updated');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function password()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $data = $this->getJsonInput();

        $errors = $this->validateInput($data, [
            'currentPassword' => 'required|min_length[8]',
            'newPassword' => 'required|min_length[8]',
        ]);

        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        try {
            $authService = service('portalAuthService');
            $authService->changePassword($portalUserId, (string) $data['currentPassword'], (string) $data['newPassword']);

            return ApiResponse::ok(null, 'Portal password updated. Please login again.');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function preferences()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $service = service('portalUserService');

        if ($this->request->getMethod() === 'get') {
            return ApiResponse::ok($service->getPreferences($portalUserId));
        }

        try {
            $preferences = $service->updatePreferences($portalUserId, $this->getJsonInput());
            return ApiResponse::ok($preferences, 'Portal preferences updated');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function savedPosts()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $service = service('portalUserService');

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(50, max(1, (int) ($this->request->getGet('perPage') ?? 20)));

        $payload = $service->getSavedPosts($portalUserId, $page, $perPage);

        return ApiResponse::ok($payload['items'], 'Saved posts loaded', $payload['meta']);
    }

    public function savePost(string $postId)
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $data = $this->getJsonInput();
        $note = $data['note'] ?? null;

        $service = service('portalUserService');
        $saved = $service->savePost($portalUserId, $postId, is_string($note) ? $note : null);

        return ApiResponse::created($saved, 'Post saved for later');
    }

    public function unsavePost(string $postId)
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $service = service('portalUserService');
        $service->unsavePost($portalUserId, $postId);

        return ApiResponse::ok(null, 'Saved post removed');
    }

    public function interactions()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        try {
            $service = service('portalUserService');
            $payload = $service->recordInteraction($portalUserId, $this->getJsonInput());
            return ApiResponse::created($payload, 'Interaction recorded');
        } catch (\RuntimeException $exception) {
            return $this->mapException($exception);
        }
    }

    public function recommendations()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $limit = min(60, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $refresh = (string) ($this->request->getGet('refresh') ?? '0');
        $force = in_array(strtolower($refresh), ['1', 'true', 'yes'], true);

        $service = service('portalRecommendationService');
        $payload = $service->getRecommendations($portalUserId, $limit, $force);

        return ApiResponse::ok($payload['items'], 'Personalized recommendations generated', $payload['meta']);
    }

    public function homeFeed()
    {
        $portalUserId = $this->portalUserId();
        if (!$portalUserId) {
            return ApiResponse::unauthorized('Portal user not authenticated');
        }

        $limit = min(60, max(8, (int) ($this->request->getGet('limit') ?? 24)));

        $service = service('portalRecommendationService');
        $payload = $service->getHomeFeed($portalUserId, $limit);

        return ApiResponse::ok($payload, 'Personalized home feed ready');
    }

    private function mapException(\RuntimeException $exception)
    {
        return match ($exception->getCode()) {
            401 => ApiResponse::unauthorized($exception->getMessage()),
            404 => ApiResponse::notFound($exception->getMessage()),
            409 => ApiResponse::conflict($exception->getMessage()),
            422 => ApiResponse::validationError(['portalUser' => $exception->getMessage()]),
            default => ApiResponse::badRequest($exception->getMessage()),
        };
    }
}
