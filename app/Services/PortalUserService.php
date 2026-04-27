<?php

namespace App\Services;

use App\Models\PortalUserFavoriteAuthorModel;
use App\Models\PortalUserFavoriteCategoryModel;
use App\Models\PortalUserFavoriteTagModel;
use App\Models\PortalUserInteractionModel;
use App\Models\PortalUserPreferenceModel;
use App\Models\PortalUserSavedPostModel;
use App\Models\PortalUserModel;

class PortalUserService
{
    private PortalUserModel $portalUserModel;
    private PortalUserPreferenceModel $preferenceModel;
    private PortalUserFavoriteCategoryModel $favoriteCategoryModel;
    private PortalUserFavoriteTagModel $favoriteTagModel;
    private PortalUserFavoriteAuthorModel $favoriteAuthorModel;
    private PortalUserSavedPostModel $savedPostModel;
    private PortalUserInteractionModel $interactionModel;

    public function __construct()
    {
        $this->portalUserModel = new PortalUserModel();
        $this->preferenceModel = new PortalUserPreferenceModel();
        $this->favoriteCategoryModel = new PortalUserFavoriteCategoryModel();
        $this->favoriteTagModel = new PortalUserFavoriteTagModel();
        $this->favoriteAuthorModel = new PortalUserFavoriteAuthorModel();
        $this->savedPostModel = new PortalUserSavedPostModel();
        $this->interactionModel = new PortalUserInteractionModel();
    }

    public function getProfile(string $portalUserId): ?array
    {
        $user = $this->portalUserModel->findPublicProfile($portalUserId);
        if (!$user) {
            return null;
        }

        $preferences = $this->ensurePreferenceRow($portalUserId);

        return [
            'id' => $user['id'],
            'email' => $user['email'],
            'firstName' => $user['first_name'] ?? null,
            'lastName' => $user['last_name'] ?? null,
            'displayName' => $this->resolveDisplayName($user),
            'avatarUrl' => $user['avatar_url'] ?? null,
            'initials' => $this->buildInitials($user),
            'active' => (bool) ($user['active'] ?? false),
            'lastLoginAt' => $user['last_login_at'] ?? null,
            'createdAt' => $user['created_at'] ?? null,
            'preferencesSummary' => [
                'timezone' => $preferences['timezone'] ?? null,
                'language' => $preferences['language'] ?? 'es',
                'digestFrequency' => $preferences['digest_frequency'] ?? 'none',
                'personalizationOptIn' => (bool) ($preferences['personalization_opt_in'] ?? true),
            ],
        ];
    }

    public function updateProfile(string $portalUserId, array $payload): array
    {
        $user = $this->portalUserModel->find($portalUserId);
        if (!$user) {
            throw new \RuntimeException('Portal user not found', 404);
        }

        $update = [];

        if (array_key_exists('email', $payload)) {
            $email = mb_strtolower(trim((string) $payload['email']));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email format', 422);
            }
            if ($this->portalUserModel->emailExists($email, $portalUserId)) {
                throw new \RuntimeException('Email already in use', 409);
            }
            $update['email'] = $email;
        }

        if (array_key_exists('firstName', $payload) || array_key_exists('first_name', $payload)) {
            $update['first_name'] = $this->trimNullable($payload['firstName'] ?? $payload['first_name'], 120);
        }

        if (array_key_exists('lastName', $payload) || array_key_exists('last_name', $payload)) {
            $update['last_name'] = $this->trimNullable($payload['lastName'] ?? $payload['last_name'], 120);
        }

        if (array_key_exists('displayName', $payload) || array_key_exists('display_name', $payload)) {
            $update['display_name'] = $this->trimNullable($payload['displayName'] ?? $payload['display_name'], 200);
        }

        if (array_key_exists('avatarUrl', $payload) || array_key_exists('avatar_url', $payload)) {
            $update['avatar_url'] = $this->trimNullable($payload['avatarUrl'] ?? $payload['avatar_url'], 500);
        }

        if ($update !== []) {
            $this->portalUserModel->update($portalUserId, $update);
        }

        $profile = $this->getProfile($portalUserId);

        if (!$profile) {
            throw new \RuntimeException('Portal user not found after update', 404);
        }

        return $profile;
    }

    public function getPreferences(string $portalUserId): array
    {
        $preferences = $this->ensurePreferenceRow($portalUserId);

        $selectedCategoryIds = $this->favoriteCategoryModel->listIdsByUser($portalUserId);
        $selectedTagIds = $this->favoriteTagModel->listIdsByUser($portalUserId);
        $selectedAuthorIds = $this->favoriteAuthorModel->listIdsByUser($portalUserId);

        $db = db_connect();

        $availableCategories = $db->table('categories')
            ->select('id, slug, name')
            ->where('active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $availableTags = $db->table('tags')
            ->select('id, slug, name')
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $availableAuthors = $db->table('authors')
            ->select('id, slug, name')
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'timezone' => $preferences['timezone'] ?? null,
            'language' => $preferences['language'] ?? 'es',
            'digestFrequency' => $preferences['digest_frequency'] ?? 'none',
            'personalizationOptIn' => (bool) ($preferences['personalization_opt_in'] ?? true),
            'favoriteCategoryIds' => $selectedCategoryIds,
            'favoriteTagIds' => $selectedTagIds,
            'favoriteAuthorIds' => $selectedAuthorIds,
            'available' => [
                'categories' => $availableCategories,
                'tags' => $availableTags,
                'authors' => $availableAuthors,
            ],
        ];
    }

    public function updatePreferences(string $portalUserId, array $payload): array
    {
        $db = db_connect();
        $db->transStart();

        $preferences = $this->ensurePreferenceRow($portalUserId);

        $update = [];

        if (array_key_exists('timezone', $payload)) {
            $update['timezone'] = $this->trimNullable($payload['timezone'], 80);
        }

        if (array_key_exists('language', $payload)) {
            $language = mb_strtolower(trim((string) $payload['language']));
            $update['language'] = $language !== '' ? mb_substr($language, 0, 10) : 'es';
        }

        if (array_key_exists('digestFrequency', $payload) || array_key_exists('digest_frequency', $payload)) {
            $digestFrequency = (string) ($payload['digestFrequency'] ?? $payload['digest_frequency']);
            $allowed = ['none', 'daily', 'weekly'];
            if (!in_array($digestFrequency, $allowed, true)) {
                throw new \RuntimeException('Invalid digest frequency', 422);
            }
            $update['digest_frequency'] = $digestFrequency;
        }

        if (array_key_exists('personalizationOptIn', $payload) || array_key_exists('personalization_opt_in', $payload)) {
            $update['personalization_opt_in'] = $this->toBoolean($payload['personalizationOptIn'] ?? $payload['personalization_opt_in']) ? 1 : 0;
        }

        if ($update !== []) {
            $this->preferenceModel->update($preferences['id'], $update);
        }

        $categoryIds = $this->normalizeUuidList($payload['favoriteCategoryIds'] ?? $payload['favorite_category_ids'] ?? []);
        $tagIds = $this->normalizeUuidList($payload['favoriteTagIds'] ?? $payload['favorite_tag_ids'] ?? []);
        $authorIds = $this->normalizeUuidList($payload['favoriteAuthorIds'] ?? $payload['favorite_author_ids'] ?? []);

        $this->favoriteCategoryModel->replaceAll($portalUserId, $categoryIds, fn(): string => $this->uuid());
        $this->favoriteTagModel->replaceAll($portalUserId, $tagIds, fn(): string => $this->uuid());
        $this->favoriteAuthorModel->replaceAll($portalUserId, $authorIds, fn(): string => $this->uuid());

        $db->transComplete();

        if (!$db->transStatus()) {
            throw new \RuntimeException('Could not update preferences', 500);
        }

        return $this->getPreferences($portalUserId);
    }

    public function getSavedPosts(string $portalUserId, int $page = 1, int $perPage = 20): array
    {
        $builder = $this->savedPostModel->where('portal_user_id', $portalUserId)
            ->where('deleted_at IS NULL');

        $total = $builder->countAllResults(false);

        $savedRows = $builder->orderBy('saved_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->findAll();

        $newsById = $this->fetchNewsByIds(array_map(static fn(array $row): string => $row['news_id'], $savedRows));

        $items = [];
        foreach ($savedRows as $saved) {
            $newsId = $saved['news_id'];
            if (!isset($newsById[$newsId])) {
                continue;
            }

            $items[] = [
                'savedPostId' => $saved['id'],
                'savedAt' => $saved['saved_at'] ?? $saved['created_at'] ?? null,
                'readAt' => $saved['read_at'] ?? null,
                'isRead' => !empty($saved['read_at']),
                'note' => $saved['note'] ?? null,
                'news' => $newsById[$newsId],
            ];
        }

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }

    public function savePost(string $portalUserId, string $newsId, ?string $note = null): array
    {
        $existing = $this->savedPostModel->findAnyByUserAndPost($portalUserId, $newsId);

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $this->savedPostModel->update($existing['id'], [
                'note' => $this->trimNullable($note, 500),
                'saved_at' => $now,
                'deleted_at' => null,
            ]);
            $savedId = $existing['id'];
        } else {
            $savedId = $this->uuid();
            $this->savedPostModel->insert([
                'id' => $savedId,
                'portal_user_id' => $portalUserId,
                'news_id' => $newsId,
                'note' => $this->trimNullable($note, 500),
                'saved_at' => $now,
                'read_at' => null,
            ]);
        }

        $this->recordInteraction($portalUserId, [
            'action' => 'save_post',
            'newsId' => $newsId,
            'context' => 'saved_posts',
        ]);

        return [
            'savedPostId' => $savedId,
            'newsId' => $newsId,
            'savedAt' => $now,
        ];
    }

    public function unsavePost(string $portalUserId, string $newsId): void
    {
        $existing = $this->savedPostModel->findAnyByUserAndPost($portalUserId, $newsId);
        if (!$existing || !empty($existing['deleted_at'])) {
            return;
        }

        $this->savedPostModel->update($existing['id'], [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        $this->recordInteraction($portalUserId, [
            'action' => 'unsave_post',
            'newsId' => $newsId,
            'context' => 'saved_posts',
        ]);
    }

    public function getSavedPostIds(string $portalUserId): array
    {
        return array_map(
            static fn(array $row): string => $row['news_id'],
            $this->savedPostModel
                ->select('news_id')
                ->where('portal_user_id', $portalUserId)
                ->where('deleted_at IS NULL')
                ->findAll()
        );
    }

    public function recordInteraction(string $portalUserId, array $payload): array
    {
        $allowedActions = [
            'view_post',
            'save_post',
            'unsave_post',
            'click_category',
            'click_tag',
            'read_time',
        ];

        $action = (string) ($payload['action'] ?? '');
        if (!in_array($action, $allowedActions, true)) {
            throw new \RuntimeException('Invalid interaction action', 422);
        }

        $newsId = $this->trimNullable($payload['newsId'] ?? $payload['news_id'] ?? null, 36);
        $categoryId = $this->trimNullable($payload['categoryId'] ?? $payload['category_id'] ?? null, 36);
        $tagId = $this->trimNullable($payload['tagId'] ?? $payload['tag_id'] ?? null, 36);
        $authorId = $this->trimNullable($payload['authorId'] ?? $payload['author_id'] ?? null, 36);

        if ($newsId !== null && ($categoryId === null || $tagId === null || $authorId === null)) {
            $context = $this->loadNewsContext($newsId);
            $categoryId = $categoryId ?? $context['categoryId'];
            $tagId = $tagId ?? $context['tagId'];
            $authorId = $authorId ?? $context['authorId'];
        }

        $timeSpent = (int) ($payload['timeSpentSeconds'] ?? $payload['time_spent_seconds'] ?? 0);
        $scoreDelta = (float) ($payload['scoreDelta'] ?? $payload['score_delta'] ?? 0);
        $metadata = $payload['metadata'] ?? null;

        $interactionId = $this->uuid();

        $this->interactionModel->insert([
            'id' => $interactionId,
            'portal_user_id' => $portalUserId,
            'news_id' => $newsId,
            'category_id' => $categoryId,
            'tag_id' => $tagId,
            'author_id' => $authorId,
            'action' => $action,
            'context' => $this->trimNullable($payload['context'] ?? null, 80),
            'time_spent_seconds' => max(0, $timeSpent),
            'score_delta' => $scoreDelta,
            'metadata' => is_array($metadata) ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => $interactionId,
            'action' => $action,
        ];
    }

    public function fetchNewsByIds(array $newsIds): array
    {
        $newsIds = array_values(array_unique(array_filter(array_map('strval', $newsIds))));
        if ($newsIds === []) {
            return [];
        }

        $db = db_connect();

        $newsRows = $db->table('news')
            ->select('news.*')
            ->whereIn('id', $newsIds)
            ->where('status', 'published')
            ->where('deleted_at IS NULL')
            ->get()
            ->getResultArray();

        $newsByIdRaw = [];
        foreach ($newsRows as $row) {
            $newsByIdRaw[(string) $row['id']] = $row;
        }

        [$categoriesByNews, $tagsByNews] = PortalNewsSerializer::loadTaxonomyMaps($db, array_keys($newsByIdRaw));

        $authorIds = [];
        foreach ($newsByIdRaw as $row) {
            if (!empty($row['author_id'])) {
                $authorIds[] = (string) $row['author_id'];
            }
        }
        $authorsById = PortalNewsSerializer::loadAuthorsMap($db, array_values(array_unique($authorIds)));

        $result = [];
        foreach ($newsIds as $newsId) {
            if (!isset($newsByIdRaw[$newsId])) {
                continue;
            }
            $result[$newsId] = PortalNewsSerializer::mapNewsRow($newsByIdRaw[$newsId], $categoriesByNews, $tagsByNews, $authorsById);
        }

        return $result;
    }

    private function loadNewsContext(string $newsId): array
    {
        $db = db_connect();

        $news = $db->table('news')->select('id, author_id')->where('id', $newsId)->get()->getRowArray();

        $category = $db->table('news_categories')->select('category_id')->where('news_id', $newsId)->orderBy('id', 'ASC')->get()->getRowArray();
        $tag = $db->table('news_tags')->select('tag_id')->where('news_id', $newsId)->orderBy('id', 'ASC')->get()->getRowArray();

        return [
            'authorId' => $news['author_id'] ?? null,
            'categoryId' => $category['category_id'] ?? null,
            'tagId' => $tag['tag_id'] ?? null,
        ];
    }

    private function ensurePreferenceRow(string $portalUserId): array
    {
        $preferences = $this->preferenceModel->getByUserId($portalUserId);

        if ($preferences) {
            return $preferences;
        }

        $id = $this->uuid();
        $this->preferenceModel->insert([
            'id' => $id,
            'portal_user_id' => $portalUserId,
            'language' => 'es',
            'digest_frequency' => 'none',
            'personalization_opt_in' => 1,
        ]);

        return $this->preferenceModel->find($id) ?: [
            'id' => $id,
            'portal_user_id' => $portalUserId,
            'language' => 'es',
            'digest_frequency' => 'none',
            'personalization_opt_in' => 1,
        ];
    }

    private function resolveDisplayName(array $user): string
    {
        $displayName = trim((string) ($user['display_name'] ?? ''));
        if ($displayName !== '') {
            return $displayName;
        }

        $fullName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($user['email'] ?? 'Portal user');
    }

    private function buildInitials(array $user): string
    {
        $seed = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        if ($seed === '') {
            $seed = (string) ($user['email'] ?? 'NU');
        }

        $parts = preg_split('/\s+/', $seed) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'NU';
    }

    private function normalizeUuidList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $id) {
            if (!is_string($id)) {
                continue;
            }
            $trimmed = trim($id);
            if ($trimmed !== '' && strlen($trimmed) <= 36) {
                $items[] = $trimmed;
            }
        }

        return array_values(array_unique($items));
    }

    private function trimNullable(mixed $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, $maxLength);
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
