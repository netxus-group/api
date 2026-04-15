<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\ExportService;
use App\Services\MetricsService;
use App\Models\NewsModel;

class ReportsController extends BaseApiController
{
    private ExportService $export;
    private MetricsService $metrics;

    public function __construct()
    {
        $this->export  = service('exportService');
        $this->metrics = service('metricsService');
    }

    /** GET /api/v1/reports/news */
    public function news()
    {
        $format = $this->request->getGet('format') ?? 'csv';
        $status = $this->request->getGet('status');

        $newsModel = new NewsModel();
        $builder   = $newsModel->builder();

        if ($status) {
            $builder->where('status', $status);
        }

        $articles = $builder->orderBy('created_at', 'DESC')->get()->getResultArray();

        switch ($format) {
            case 'csv':
                $content  = $this->export->newsToCsv($articles);
                $filename = 'news-report-' . date('Y-m-d') . '.csv';
                return $this->response
                    ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);

            case 'excel':
                $content  = $this->export->newsToExcel($articles);
                $filename = 'news-report-' . date('Y-m-d') . '.xlsx';
                return $this->response
                    ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);

            case 'txt':
                $content  = $this->export->newsToTxt($articles);
                $filename = 'news-report-' . date('Y-m-d') . '.txt';
                return $this->response
                    ->setHeader('Content-Type', 'text/plain; charset=utf-8')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);

            case 'pdf':
                $content  = $this->export->newsToPdf($articles);
                $filename = 'news-report-' . date('Y-m-d') . '.pdf';
                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);

            default:
                return ApiResponse::badRequest('Unsupported format. Use: csv, excel, txt, pdf');
        }
    }

    /** GET /api/v1/reports/metrics */
    public function metrics()
    {
        $format = $this->request->getGet('format') ?? 'csv';
        $range  = $this->request->getGet('range') ?? 'last_month';
        $from   = $this->request->getGet('from');
        $to     = $this->request->getGet('to');

        $data = $this->metrics->getDashboard($range, $from, $to);

        switch ($format) {
            case 'csv':
                $content  = $this->export->metricsToCsv($data);
                $filename = 'metrics-report-' . date('Y-m-d') . '.csv';
                return $this->response
                    ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);
            default:
                return ApiResponse::badRequest('Unsupported format. Use: csv');
        }
    }

    /** GET /api/v1/reports/audit-log */
    public function auditLog()
    {
        [$page, $limit] = $this->paginationParams();

        $model   = new \App\Models\AuditLogModel();
        $builder = $model->builder();

        $userId = $this->request->getGet('userId');
        $action = $this->request->getGet('action');
        $entity = $this->request->getGet('entity');

        if ($userId) {
            $builder->where('user_id', $userId);
        }
        if ($action) {
            $builder->where('action', $action);
        }
        if ($entity) {
            $builder->where('entity_type', $entity);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->get()
            ->getResultArray();

        return ApiResponse::paginated($rows, [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ]);
    }
}
