<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\NewsModel;
use App\Models\CategoryModel;
use App\Models\TagModel;
use App\Models\AuthorModel;
use App\Models\AdSlotModel;
use App\Models\HomeLayoutConfigModel;
use App\Models\EngagementEventModel;
use App\Services\PortalNewsSerializer;

class PublicApiController extends BaseApiController
{
    /**
     * Backward-compatible alias used by Routes.php.
     * GET /api/v1/public/home
     */
    public function home()
    {
        return $this->homeLayout();
    }

    /** GET /api/v1/public/news */
    public function news()
    {
        [$page, $limit] = $this->paginationParams();
        $featured = $this->request->getGet('featured');

        $filters = [
            'categorySlug' => $this->request->getGet('category'),
            'tagSlug'      => $this->request->getGet('tag'),
            'authorSlug'   => $this->request->getGet('author'),
            'search'       => $this->request->getGet('search'),
            'featured'     => $featured === null ? null : in_array(strtolower((string) $featured), ['1', 'true', 'yes'], true),
        ];

        $newsModel = new NewsModel();
        $result = $newsModel->listPublished($filters, $page, $limit);
        $items = $this->serializePublishedItems($result['items']);

        return ApiResponse::paginated($items, $result['total'], $page, $limit);
    }

    /**
     * Backward-compatible alias for the route target declared in Routes.php.
     */
    public function newsList()
    {
        return $this->news();
    }

    /** GET /api/v1/public/news/:slug */
    public function newsDetail(string $slug)
    {
        $newsModel = new NewsModel();
        $article = $newsModel->findPublishedBySlug($slug);

        if (!$article) {
            return ApiResponse::notFound('Article not found');
        }

        // Track view
        $engagement = new EngagementEventModel();
        $engagement->track(
            'view',
            $article['id'],
            'news',
            [
                'ipAddress' => $this->request->getIPAddress(),
                'userAgent' => $this->request->getUserAgent()->getAgentString(),
            ]
        );

        $serialized = $this->serializePublishedItems([$article]);

        return ApiResponse::ok($serialized[0] ?? null);
    }

    /** GET /api/v1/public/categories */
    public function categories()
    {
        $model = new CategoryModel();
        $categories = $model->getActive();
        return ApiResponse::ok(array_map(fn($c) => $c->toArray(), $categories));
    }

    /** GET /api/v1/public/tags */
    public function tags()
    {
        $model = new TagModel();
        $tags = $model->getActive();
        return ApiResponse::ok(array_map(fn($t) => $t->toArray(), $tags));
    }

    /** GET /api/v1/public/authors */
    public function authors()
    {
        $model = new AuthorModel();
        $authors = $model->getActive();
        return ApiResponse::ok(array_map(fn($a) => $a->toArray(), $authors));
    }

    /** GET /api/v1/public/ads */
    public function ads()
    {
        $placement = $this->request->getGet('placement');
        $model     = new AdSlotModel();

        if ($placement) {
            $ads = array_map(static fn($ad) => $ad->toArray(), $model->getByPlacement($placement));
        } else {
            $ads = [];
            foreach ($model->getAllActiveGrouped() as $key => $items) {
                $ads[$key] = array_map(static fn($ad) => $ad->toArray(), $items);
            }
        }

        return ApiResponse::ok($ads);
    }

    /** GET /api/v1/public/home-layout */
    public function homeLayout()
    {
        $model  = new HomeLayoutConfigModel();
        $layout = $model->getByKey('home_layout');
        return ApiResponse::ok($layout);
    }

    /** GET /api/v1/public/integrations/:provider */
    public function integration(string $provider)
    {
        $service = service('integrationService');
        $data = $service->getData($provider);
        if ($data === null) {
            return ApiResponse::notFound("Integration '{$provider}' not available");
        }
        return ApiResponse::ok($data);
    }

    /** POST /api/v1/public/engagement */
    public function trackEngagement()
    {
        $data = $this->getJsonInput();

        if (empty($data['entityId']) || empty($data['entityType']) || empty($data['eventType'])) {
            return ApiResponse::badRequest('entityId, entityType and eventType are required');
        }

        $allowed = ['view', 'share', 'click', 'reaction'];
        if (!in_array($data['eventType'], $allowed, true)) {
            return ApiResponse::badRequest('Invalid event type');
        }

        $engagement = new EngagementEventModel();
        $engagement->track(
            $data['eventType'],
            (string) $data['entityId'],
            (string) $data['entityType'],
            [
                'ipAddress' => $this->request->getIPAddress(),
                'userAgent' => $this->request->getUserAgent()->getAgentString(),
            ]
        );

        return ApiResponse::ok(null, 'Event tracked');
    }

    /**
     * Backward-compatible alias for the route target declared in Routes.php.
     */
    public function trackEvent()
    {
        return $this->trackEngagement();
    }

    /**
     * Search endpoint alias using the same filter machinery as /public/news.
     */
    public function search()
    {
        return $this->news();
    }

    /** GET /api/v1/public/polls/:id */
    public function poll(string $id)
    {
        $service = service('pollService');
        $poll = $service->getWithStats($id);
        if (!$poll) {
            return ApiResponse::notFound('Poll not found');
        }
        return ApiResponse::ok($poll);
    }

    public function survey(string $slug)
    {
        $portalUserId = $this->portalUserIdFromBearer();
        $anonymousKey = (string) ($this->request->getGet('anonymousKey') ?? '');

        $survey = service('surveyService')->getSurveyBySlug($slug, $portalUserId, $anonymousKey !== '' ? $anonymousKey : null);
        if (!$survey) {
            return ApiResponse::notFound('Survey not found');
        }

        return ApiResponse::ok($survey);
    }

    public function surveys()
    {
        $portalUserId = $this->portalUserIdFromBearer();

        return ApiResponse::ok(service('surveyService')->listPublicSurveys($portalUserId));
    }

    public function surveyStart(string $slug)
    {
        $data = $this->getJsonInput();
        $portalUserId = $this->portalUserIdFromBearer();

        try {
            $payload = service('surveyService')->startResponse(
                $slug,
                $portalUserId,
                isset($data['anonymousKey']) ? (string) $data['anonymousKey'] : null,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );
            return ApiResponse::ok($payload, 'Survey started');
        } catch (\RuntimeException $exception) {
            return $this->mapSurveyException($exception);
        }
    }

    public function surveySaveSection(string $slug, string $sectionId)
    {
        $data = $this->getJsonInput();
        $portalUserId = $this->portalUserIdFromBearer();

        try {
            $payload = service('surveyService')->saveSection(
                $slug,
                $sectionId,
                $data,
                $portalUserId,
                isset($data['anonymousKey']) ? (string) $data['anonymousKey'] : null,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );
            return ApiResponse::ok($payload, 'Section saved');
        } catch (\RuntimeException $exception) {
            return $this->mapSurveyException($exception);
        }
    }

    public function surveyComplete(string $slug)
    {
        $data = $this->getJsonInput();
        $portalUserId = $this->portalUserIdFromBearer();

        try {
            $payload = service('surveyService')->completeSurvey(
                $slug,
                $data,
                $portalUserId,
                isset($data['anonymousKey']) ? (string) $data['anonymousKey'] : null,
                $this->request->getIPAddress(),
                $this->request->getUserAgent()->getAgentString()
            );
            return ApiResponse::ok($payload, 'Survey completed');
        } catch (\RuntimeException $exception) {
            return $this->mapSurveyException($exception);
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, mixed>>
     */
    private function serializePublishedItems(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $rows = array_map(fn($item) => $this->toNewsRow($item), $items);
        $newsIds = array_map(static fn(array $row): string => (string) $row['id'], $rows);

        $db = db_connect();
        [$categoriesByNews, $tagsByNews] = PortalNewsSerializer::loadTaxonomyMaps($db, $newsIds);

        $authorIds = [];
        foreach ($rows as $row) {
            if (!empty($row['author_id'])) {
                $authorIds[] = (string) $row['author_id'];
            }
        }
        $authorsById = PortalNewsSerializer::loadAuthorsMap($db, array_values(array_unique($authorIds)));

        $serialized = [];
        foreach ($rows as $row) {
            $serialized[] = PortalNewsSerializer::mapNewsRow($row, $categoriesByNews, $tagsByNews, $authorsById);
        }

        return $serialized;
    }

    /**
     * @return array<string, mixed>
     */
    private function toNewsRow(mixed $item): array
    {
        if (is_array($item)) {
            return $item;
        }

        if (is_object($item) && method_exists($item, 'toArray')) {
            return $item->toArray();
        }

        return (array) $item;
    }

    private function portalUserIdFromBearer(): ?string
    {
        $header = trim($this->request->getHeaderLine('Authorization'));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        try {
            $decoded = service('portalJwtManager')->validateAccessToken(trim($matches[1]));
            return (string) ($decoded->sub ?? '');
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapSurveyException(\RuntimeException $exception)
    {
        return match ($exception->getCode()) {
            401 => ApiResponse::unauthorized($exception->getMessage()),
            404 => ApiResponse::notFound($exception->getMessage()),
            409 => ApiResponse::conflict($exception->getMessage()),
            422 => ApiResponse::validationError(['survey' => $exception->getMessage()]),
            default => ApiResponse::badRequest($exception->getMessage()),
        };
    }
}
