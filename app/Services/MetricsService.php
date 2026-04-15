<?php

namespace App\Services;

use App\Models\NewsModel;
use App\Models\NewsletterSubscriberModel;
use App\Models\EngagementEventModel;
use App\Models\AuthorModel;
use App\Models\CategoryModel;
use App\Models\TagModel;

class MetricsService
{
    /**
     * Get dashboard overview metrics for a date range.
     */
    public function getDashboard(string $range, ?string $start = null, ?string $end = null): array
    {
        [$from, $to] = $this->resolveDateRange($range, $start, $end);

        return [
            'range'      => ['from' => $from, 'to' => $to],
            'content'    => $this->getContentMetrics(),
            'newsletter' => $this->getNewsletterMetrics($from, $to),
            'engagement' => $this->getEngagementMetrics($from, $to),
        ];
    }

    /**
     * Content metrics: counts by status.
     */
    public function getContentMetrics(): array
    {
        $newsModel = new NewsModel();
        $db = \Config\Database::connect();

        $statusCounts = $db->table('news')
            ->select('status, COUNT(*) as count')
            ->where('active', 1)
            ->groupBy('status')
            ->get()->getResultArray();

        $counts = ['draft' => 0, 'in_review' => 0, 'approved' => 0, 'scheduled' => 0, 'published' => 0];
        foreach ($statusCounts as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        $authorModel = new AuthorModel();
        $catModel    = new CategoryModel();
        $tagModel    = new TagModel();

        return [
            'totalNews'    => array_sum($counts),
            'statusBreakdown' => $counts,
            'totalAuthors'    => $authorModel->where('active', 1)->countAllResults(),
            'totalCategories' => $catModel->where('active', 1)->countAllResults(),
            'totalTags'       => $tagModel->where('active', 1)->countAllResults(),
            'featuredCount'   => $newsModel->where('active', 1)->where('featured', 1)->countAllResults(),
        ];
    }

    /**
     * Newsletter metrics for a date range.
     */
    public function getNewsletterMetrics(string $from, string $to): array
    {
        $subModel = new NewsletterSubscriberModel();
        $counts   = $subModel->countByStatus();

        $db = \Config\Database::connect();

        // Recent subscriptions in range
        $recentSubs = $db->table('newsletter_subscribers')
            ->where('subscribed_at >=', $from)
            ->where('subscribed_at <=', $to)
            ->countAllResults();

        // Last 7 days
        $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $last7d  = $db->table('newsletter_subscribers')
            ->where('subscribed_at >=', $weekAgo)
            ->countAllResults();

        return [
            'totalSubscribed'   => $counts['subscribed'],
            'totalUnsubscribed' => $counts['unsubscribed'],
            'subscriptionsInRange' => $recentSubs,
            'subscriptionsLast7d'  => $last7d,
        ];
    }

    /**
     * Engagement metrics for a date range.
     */
    public function getEngagementMetrics(string $from, string $to): array
    {
        $eventModel = new EngagementEventModel();
        $summary    = $eventModel->getSummary($from, $to);

        // Last 24 hours
        $day = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $last24h = $eventModel->getSummary($day, date('Y-m-d H:i:s'));

        // Top content
        $topViewed  = $eventModel->getTopContent('view', 10, $from, $to);
        $topClicked = $eventModel->getTopContent('click', 10, $from, $to);

        // Enrich top content with news titles
        $this->enrichTopContent($topViewed);
        $this->enrichTopContent($topClicked);

        return [
            'totalViews'    => $summary['view'],
            'totalClicks'   => $summary['click'],
            'viewsLast24h'  => $last24h['view'],
            'clicksLast24h' => $last24h['click'],
            'topViewed'     => $topViewed,
            'topClicked'    => $topClicked,
        ];
    }

    /**
     * Daily engagement series.
     */
    public function getDailyEngagement(string $range, ?string $start = null, ?string $end = null): array
    {
        [$from, $to] = $this->resolveDateRange($range, $start, $end);

        $eventModel = new EngagementEventModel();
        $daily      = $eventModel->getDailySeries($from, $to);

        // Group by date
        $series = [];
        foreach ($daily as $row) {
            $date = $row['date'];
            if (!isset($series[$date])) {
                $series[$date] = ['date' => $date, 'views' => 0, 'clicks' => 0];
            }
            if ($row['event_type'] === 'view') {
                $series[$date]['views'] = (int) $row['count'];
            } elseif ($row['event_type'] === 'click') {
                $series[$date]['clicks'] = (int) $row['count'];
            }
        }

        // Daily subscriptions
        $db = \Config\Database::connect();
        $dailySubs = $db->table('newsletter_subscribers')
            ->select('DATE(subscribed_at) as date, COUNT(*) as count')
            ->where('subscribed_at >=', $from)
            ->where('subscribed_at <=', $to)
            ->groupBy('DATE(subscribed_at)')
            ->get()->getResultArray();

        foreach ($dailySubs as $row) {
            $date = $row['date'];
            if (isset($series[$date])) {
                $series[$date]['subscriptions'] = (int) $row['count'];
            }
        }

        // Daily publications
        $dailyPubs = $db->table('news')
            ->select('DATE(created_at) as date, COUNT(*) as count')
            ->where('status', 'published')
            ->where('active', 1)
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->groupBy('DATE(created_at)')
            ->get()->getResultArray();

        foreach ($dailyPubs as $row) {
            $date = $row['date'];
            if (isset($series[$date])) {
                $series[$date]['publications'] = (int) $row['count'];
            }
        }

        return array_values($series);
    }

    /**
     * Resolve date range parameters.
     */
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

    /**
     * Enrich top content with news titles.
     */
    private function enrichTopContent(array &$items): void
    {
        if (empty($items)) {
            return;
        }

        $newsModel = new NewsModel();
        foreach ($items as &$item) {
            if (!empty($item['news_id'])) {
                $news = $newsModel->find($item['news_id']);
                $item['title'] = $news ? $news->title : '[deleted]';
                $item['slug']  = $news ? $news->slug : null;
            }
        }
    }
}
