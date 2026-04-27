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

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function index()
    {
        return $this->dashboard();
    }

    /** GET /api/v1/metrics/content */
    public function content()
    {
        $data = $this->service->getContentMetrics();
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/newsletter */
    public function newsletter()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        [$fromDate, $toDate] = $this->resolveDateRange($range, $from, $to);
        $data = $this->service->getNewsletterMetrics($fromDate, $toDate);
        return ApiResponse::ok($data);
    }

    /** GET /api/v1/metrics/engagement */
    public function engagement()
    {
        $range = $this->request->getGet('range') ?? 'last_week';
        $from  = $this->request->getGet('from');
        $to    = $this->request->getGet('to');

        [$fromDate, $toDate] = $this->resolveDateRange($range, $from, $to);
        $data = $this->service->getEngagementMetrics($fromDate, $toDate);
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

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function dailyEngagement()
    {
        return $this->engagementDaily();
    }

    private function resolveDateRange(string $range, ?string $start, ?string $end): array
    {
        $to = date('Y-m-d 23:59:59');

        return match ($range) {
            'yesterday'  => [
                date('Y-m-d 00:00:00', strtotime('-1 day')),
                date('Y-m-d 23:59:59', strtotime('-1 day')),
            ],
            'last_week'  => [date('Y-m-d 00:00:00', strtotime('-7 days')), $to],
            'last_month' => [date('Y-m-d 00:00:00', strtotime('-30 days')), $to],
            'custom'     => [
                $start ? $start . ' 00:00:00' : date('Y-m-d 00:00:00', strtotime('-30 days')),
                $end ? $end . ' 23:59:59' : $to,
            ],
            default => [date('Y-m-d 00:00:00', strtotime('-7 days')), $to],
        };
    }
}
