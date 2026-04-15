<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\MetricsService;

class MetricsController extends BaseApiController
{
    private MetricsService $service;

    public function __construct()
    {
        $this->service = service('metricsService');
    }

    /** GET /api/v1/metrics/dashboard */
    public function dashboard()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        $data = $this->service->getDashboard($range, $from, $to);
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/content */
    public function content()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        $data = $this->service->getContentMetrics($range, $from, $to);
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/newsletter */
    public function newsletter()
    {
        $data = $this->service->getNewsletterMetrics();
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/engagement */
    public function engagement()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        $data = $this->service->getEngagementMetrics($range, $from, $to);
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/engagement/daily */
    public function engagementDaily()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        $data = $this->service->getDailyEngagement($range, $from, $to);
        return ApiResponse::ok($data);
    }
}
