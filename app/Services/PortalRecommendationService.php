<?php

namespace App\Services;

use App\Models\PortalUserInteractionModel;
use App\Models\PortalUserRecommendationScoreModel;
use App\Models\PortalUserSavedPostModel;
use Config\PortalRecommendation;

class PortalRecommendationService
{
    private PortalUserInteractionModel $interactionModel;
    private PortalUserSavedPostModel $savedPostModel;
    private PortalUserRecommendationScoreModel $scoreModel;
    private PortalUserService $portalUserService;
    private PortalRecommendation $config;

    public function __construct()
    {
        $this->interactionModel = new PortalUserInteractionModel();
        $this->savedPostModel = new PortalUserSavedPostModel();
        $this->scoreModel = new PortalUserRecommendationScoreModel();
        $this->portalUserService = new PortalUserService();
        $this->config = config('PortalRecommendation');
    }

    public function getRecommendations(string $portalUserId, int $limit = 20, bool $forceRecalculate = false): array
    {
        $limit = max(1, min(60, $limit));

        if (!$forceRecalculate) {
            $cached = $this->scoreModel->listFreshForUser($portalUserId, $limit);
            if (count($cached) >= max(1, min(5, $limit))) {
                return $this->hydrateFromCachedScores($cached);
            }
        }

        return $this->recalculateRecommendations($portalUserId, $limit);
    }

    public function getHomeFeed(string $portalUserId, int $limit = 24): array
    {
        $recommendations = $this->getRecommendations($portalUserId, $limit, false);
        $items = $recommendations['items'];

        $savedPostIds = $this->portalUserService->getSavedPostIds($portalUserId);
        $savedSet = array_fill_keys($savedPostIds, true);

        foreach ($items as &$item) {
            $item['isSaved'] = isset($savedSet[$item['id']]);
        }
        unset($item);

        $spotlight = $items[0] ?? null;
        $recommended = array_slice($items, 0, 8);
        $accordingToInterests = array_slice($items, 8, 8);

        $savedRelated = [];
        foreach ($items as $item) {
            if (($item['recommendation']['components']['relatedSavedPosts'] ?? 0) > 0) {
                $savedRelated[] = $item;
            }
            if (count($savedRelated) >= 6) {
                break;
            }
        }

        return [
            'spotlight' => $spotlight,
            'recommendedForYou' => $recommended,
            'accordingToYourInterests' => $accordingToInterests,
            'becauseYouSaved' => $savedRelated,
            'items' => $items,
            'meta' => [
                'algorithm' => 'portal_recommendation_v1',
                'generatedAt' => date('c'),
                'weights' => $this->config->weights,
            ],
        ];
    }

    private function hydrateFromCachedScores(array $cachedRows): array
    {
        $newsIds = array_map(static fn(array $row): string => (string) $row['news_id'], $cachedRows);
        $newsById = $this->portalUserService->fetchNewsByIds($newsIds);

        $items = [];

        foreach ($cachedRows as $row) {
            $newsId = (string) $row['news_id'];
            if (!isset($newsById[$newsId])) {
                continue;
            }

            $components = [];
            if (!empty($row['components'])) {
                $decoded = json_decode((string) $row['components'], true);
                if (is_array($decoded)) {
                    $components = $decoded;
                }
            }

            $news = $newsById[$newsId];
            $news['recommendation'] = [
                'score' => (float) $row['score'],
                'rank' => (int) ($row['rank_position'] ?? 0),
                'components' => $components,
                'reasons' => $this->buildReasons($components),
                'fromCache' => true,
            ];
            $items[] = $news;
        }

        return [
            'items' => $items,
            'meta' => [
                'cached' => true,
                'generatedAt' => date('c'),
            ],
        ];
    }

    private function recalculateRecommendations(string $portalUserId, int $limit): array
    {
        $preferences = $this->portalUserService->getPreferences($portalUserId);
        $interactions = $this->interactionModel->getRecentByUser($portalUserId, 45);
        $savedRows = $this->savedPostModel->listActiveForUser($portalUserId);

        $db = db_connect();

        $candidateRows = $db->table('news')
            ->select('news.*')
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit(max($limit * 6, $this->config->candidateLimit))
            ->get()
            ->getResultArray();

        if ($candidateRows === []) {
            return ['items' => [], 'meta' => ['cached' => false, 'generatedAt' => date('c')]];
        }

        $candidateIds = array_map(static fn(array $row): string => (string) $row['id'], $candidateRows);
        [$categoriesByNews, $tagsByNews] = PortalNewsSerializer::loadTaxonomyMaps($db, $candidateIds);

        $authorIds = [];
        foreach ($candidateRows as $row) {
            if (!empty($row['author_id'])) {
                $authorIds[] = (string) $row['author_id'];
            }
        }
        $authorsById = PortalNewsSerializer::loadAuthorsMap($db, array_values(array_unique($authorIds)));

        $savedNewsById = $this->portalUserService->fetchNewsByIds(array_map(static fn(array $row): string => (string) $row['news_id'], $savedRows));

        $favoriteCategorySet = array_fill_keys($preferences['favoriteCategoryIds'] ?? [], true);
        $favoriteTagSet = array_fill_keys($preferences['favoriteTagIds'] ?? [], true);
        $favoriteAuthorSet = array_fill_keys($preferences['favoriteAuthorIds'] ?? [], true);

        $interactionCategoryAffinity = [];
        $interactionTagAffinity = [];
        $interactionAuthorAffinity = [];
        $viewedPostCount = [];

        foreach ($interactions as $interaction) {
            $action = (string) ($interaction['action'] ?? '');
            $weight = (float) ($this->config->interactionActionWeight[$action] ?? 0);
            $weight += (float) ($interaction['score_delta'] ?? 0);

            if ($action === 'read_time') {
                $weight += ((int) ($interaction['time_spent_seconds'] ?? 0)) * (float) ($this->config->interactionActionWeight['read_time'] ?? 0.05);
            }

            $categoryId = (string) ($interaction['category_id'] ?? '');
            $tagId = (string) ($interaction['tag_id'] ?? '');
            $authorId = (string) ($interaction['author_id'] ?? '');
            $newsId = (string) ($interaction['news_id'] ?? '');

            if ($categoryId !== '') {
                $interactionCategoryAffinity[$categoryId] = ($interactionCategoryAffinity[$categoryId] ?? 0) + $weight;
            }
            if ($tagId !== '') {
                $interactionTagAffinity[$tagId] = ($interactionTagAffinity[$tagId] ?? 0) + $weight;
            }
            if ($authorId !== '') {
                $interactionAuthorAffinity[$authorId] = ($interactionAuthorAffinity[$authorId] ?? 0) + $weight;
            }
            if ($newsId !== '' && $action === 'view_post') {
                $viewedPostCount[$newsId] = ($viewedPostCount[$newsId] ?? 0) + 1;
            }
        }

        $savedCategoryAffinity = [];
        $savedTagAffinity = [];

        foreach ($savedNewsById as $newsItem) {
            foreach ($newsItem['categories'] as $category) {
                $savedCategoryAffinity[$category['id']] = ($savedCategoryAffinity[$category['id']] ?? 0) + 1;
            }
            foreach ($newsItem['tags'] as $tag) {
                $savedTagAffinity[$tag['id']] = ($savedTagAffinity[$tag['id']] ?? 0) + 1;
            }
        }

        $maxPopularity = 1.0;
        foreach ($candidateRows as $row) {
            $popularity = ((int) ($row['view_count'] ?? 0)) + (((int) ($row['share_count'] ?? 0)) * 2);
            $maxPopularity = max($maxPopularity, (float) $popularity);
        }

        $scored = [];

        foreach ($candidateRows as $row) {
            $newsId = (string) $row['id'];
            $categoryIds = array_map(static fn(array $c): string => $c['id'], $categoriesByNews[$newsId] ?? []);
            $tagIds = array_map(static fn(array $t): string => $t['id'], $tagsByNews[$newsId] ?? []);
            $authorId = (string) ($row['author_id'] ?? '');

            $categoryFavoriteMatches = 0;
            foreach ($categoryIds as $categoryId) {
                if (isset($favoriteCategorySet[$categoryId])) {
                    $categoryFavoriteMatches++;
                }
            }

            $tagFavoriteMatches = 0;
            foreach ($tagIds as $tagId) {
                if (isset($favoriteTagSet[$tagId])) {
                    $tagFavoriteMatches++;
                }
            }

            $favoriteAuthorMatch = $authorId !== '' && isset($favoriteAuthorSet[$authorId]) ? 1 : 0;

            $interactionCategoryScore = 0.0;
            foreach ($categoryIds as $categoryId) {
                $interactionCategoryScore += (float) ($interactionCategoryAffinity[$categoryId] ?? 0);
            }

            $interactionTagScore = 0.0;
            foreach ($tagIds as $tagId) {
                $interactionTagScore += (float) ($interactionTagAffinity[$tagId] ?? 0);
            }

            $interactionAuthorScore = (float) ($interactionAuthorAffinity[$authorId] ?? 0);

            $savedSimilarity = 0;
            foreach ($categoryIds as $categoryId) {
                if (isset($savedCategoryAffinity[$categoryId])) {
                    $savedSimilarity++;
                }
            }
            foreach ($tagIds as $tagId) {
                if (isset($savedTagAffinity[$tagId])) {
                    $savedSimilarity++;
                }
            }

            $publishedAtRaw = $row['publish_at'] ?? $row['published_at'] ?? $row['created_at'] ?? null;
            $ageHours = 9999;
            if ($publishedAtRaw) {
                $timestamp = strtotime((string) $publishedAtRaw);
                if ($timestamp !== false) {
                    $ageHours = max(0, (time() - $timestamp) / 3600);
                }
            }

            $recentBonus = 0.0;
            $recentWindow = max(1, (float) $this->config->weights['recentHoursWindow']);
            if ($ageHours <= $recentWindow) {
                $recentBonus = (float) $this->config->weights['recentMaxBonus'] * (1 - ($ageHours / $recentWindow));
            }

            $popularity = ((int) ($row['view_count'] ?? 0)) + (((int) ($row['share_count'] ?? 0)) * 2);
            $trendingBonus = ((float) $this->config->weights['trendingMaxBonus']) * (($popularity > 0 ? log(1 + $popularity) : 0) / log(1 + $maxPopularity));

            $editorialBonus = 0.0;
            if (!empty($row['featured'])) {
                $editorialBonus += (float) $this->config->weights['featuredBonus'];
                $editorialBonus += (float) $this->config->weights['editorialPriorityBonus'];
            }
            if (!empty($row['breaking'])) {
                $editorialBonus += (float) $this->config->weights['breakingBonus'];
                $editorialBonus += (float) $this->config->weights['editorialPriorityBonus'];
            }
            if ($ageHours <= 6) {
                $editorialBonus += (float) $this->config->weights['globalFallbackBonus'];
            }

            $seenPenalty = ((int) ($viewedPostCount[$newsId] ?? 0)) * (float) $this->config->weights['seenPenalty'];

            $components = [
                'favoriteCategory' => $categoryFavoriteMatches * (float) $this->config->weights['favoriteCategory'],
                'favoriteTags' => $tagFavoriteMatches * (float) $this->config->weights['favoriteTag'],
                'favoriteAuthor' => $favoriteAuthorMatch * (float) $this->config->weights['favoriteAuthor'],
                'interactionCategory' => $interactionCategoryScore * (float) $this->config->weights['interactionCategory'],
                'interactionTag' => $interactionTagScore * (float) $this->config->weights['interactionTag'],
                'interactionAuthor' => $interactionAuthorScore * (float) $this->config->weights['interactionAuthor'],
                'relatedSavedPosts' => $savedSimilarity > 0 ? (float) $this->config->weights['relatedSavedPost'] : 0,
                'recentBonus' => $recentBonus,
                'trendingBonus' => $trendingBonus,
                'editorialBonus' => $editorialBonus,
                'seenPenalty' => -$seenPenalty,
            ];

            $baseScore = array_sum($components);

            $scored[] = [
                'newsId' => $newsId,
                'baseScore' => $baseScore,
                'score' => $baseScore,
                'components' => $components,
                'editorialSignal' => $editorialBonus + $trendingBonus + $recentBonus,
                'primaryCategoryId' => $categoryIds[0] ?? null,
                'raw' => $row,
            ];
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        $categoryCounter = [];
        foreach ($scored as &$item) {
            $primaryCategoryId = $item['primaryCategoryId'];
            if (!$primaryCategoryId) {
                continue;
            }

            $currentCount = (int) ($categoryCounter[$primaryCategoryId] ?? 0);
            if ($currentCount >= $this->config->maxPerPrimaryCategory) {
                $penaltyUnits = ($currentCount - $this->config->maxPerPrimaryCategory) + 1;
                $dynamicPenalty = $penaltyUnits * (float) $this->config->weights['themeOverloadPenalty'];
                $item['score'] -= $dynamicPenalty;
                $item['components']['diversityPenalty'] = -$dynamicPenalty;
            }

            $categoryCounter[$primaryCategoryId] = $currentCount + 1;
        }
        unset($item);

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        $selected = array_slice($scored, 0, $limit);
        $editorialSorted = $scored;
        usort($editorialSorted, static fn(array $a, array $b): int => $b['editorialSignal'] <=> $a['editorialSignal']);

        $selectedById = array_fill_keys(array_map(static fn(array $item): string => $item['newsId'], $selected), true);
        $editorialCount = count(array_filter($selected, static fn(array $item): bool => $item['editorialSignal'] > 0));

        if ($editorialCount < $this->config->minEditorialGuarantee) {
            foreach ($editorialSorted as $candidate) {
                if ($editorialCount >= $this->config->minEditorialGuarantee) {
                    break;
                }
                if (isset($selectedById[$candidate['newsId']])) {
                    continue;
                }

                $replaceIndex = null;
                foreach ($selected as $idx => $selectedItem) {
                    if ($selectedItem['editorialSignal'] <= 0) {
                        $replaceIndex = $idx;
                        break;
                    }
                }

                if ($replaceIndex === null) {
                    break;
                }

                unset($selectedById[$selected[$replaceIndex]['newsId']]);
                $selected[$replaceIndex] = $candidate;
                $selectedById[$candidate['newsId']] = true;
                $editorialCount++;
            }

            usort($selected, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        }

        $newsById = [];
        foreach ($candidateRows as $row) {
            $newsById[(string) $row['id']] = PortalNewsSerializer::mapNewsRow($row, $categoriesByNews, $tagsByNews, $authorsById);
        }

        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config->cacheTtlSeconds);

        $this->scoreModel->clearForUser($portalUserId);

        $items = [];
        foreach ($selected as $rank => $item) {
            $newsId = $item['newsId'];
            if (!isset($newsById[$newsId])) {
                continue;
            }

            $this->scoreModel->insert([
                'id' => $this->uuid(),
                'portal_user_id' => $portalUserId,
                'news_id' => $newsId,
                'score' => $item['score'],
                'rank_position' => $rank + 1,
                'components' => json_encode($item['components']),
                'calculated_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            $news = $newsById[$newsId];
            $news['recommendation'] = [
                'score' => round((float) $item['score'], 2),
                'rank' => $rank + 1,
                'components' => $item['components'],
                'reasons' => $this->buildReasons($item['components']),
                'fromCache' => false,
            ];

            $items[] = $news;
        }

        return [
            'items' => $items,
            'meta' => [
                'cached' => false,
                'generatedAt' => date('c'),
                'algorithm' => 'portal_recommendation_v1',
            ],
        ];
    }

    private function buildReasons(array $components): array
    {
        $reasonMap = [
            'favoriteCategory' => 'Coincide con tus categorias favoritas',
            'favoriteTags' => 'Coincide con tus tags favoritos',
            'favoriteAuthor' => 'Coincide con autor favorito',
            'interactionCategory' => 'Sueles leer esta categoria',
            'interactionTag' => 'Sueles leer este tema',
            'interactionAuthor' => 'Sueles leer este autor',
            'relatedSavedPosts' => 'Relacionado con tus guardados',
            'recentBonus' => 'Noticia reciente',
            'trendingBonus' => 'Contenido en tendencia',
            'editorialBonus' => 'Prioridad editorial',
        ];

        $positives = [];
        foreach ($components as $key => $value) {
            if (($value ?? 0) <= 0 || !isset($reasonMap[$key])) {
                continue;
            }
            $positives[] = ['label' => $reasonMap[$key], 'score' => (float) $value];
        }

        usort($positives, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(static fn(array $item): string => $item['label'], array_slice($positives, 0, 3));
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
