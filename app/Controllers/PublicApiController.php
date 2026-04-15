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

class PublicApiController extends BaseApiController
{
    /** GET /api/v1/public/news */
    public function news()
    {
        [$page, $limit] = $this->paginationParams();
        $category = $this->request->getGet('category');
        $tag      = $this->request->getGet('tag');
        $author   = $this->request->getGet('author');
        $search   = $this->request->getGet('search');
        $featured = $this->request->getGet('featured');

        $newsModel = new NewsModel();
        $result = $newsModel->listPublished($page, $limit, $category, $tag, $author, $search, $featured);

        return ApiResponse::paginated($result['data'], $result['meta']);
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
            $article['id'],
            'news',
            'view',
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString()
        );

        return ApiResponse::ok($article);
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
            $ads = $model->getByPlacement($placement);
        } else {
            $ads = $model->getAllActiveGrouped();
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
            $data['entityId'],
            $data['entityType'],
            $data['eventType'],
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString()
        );

        return ApiResponse::ok(null, 'Event tracked');
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
}
