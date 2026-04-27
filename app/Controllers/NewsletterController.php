<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\NewsletterSubscriberModel;

class NewsletterController extends BaseApiController
{
    private NewsletterSubscriberModel $model;

    public function __construct()
    {
        $this->model = new NewsletterSubscriberModel();
    }

    /** Public: subscribe */
    public function subscribe()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'subscribe');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $email    = strtolower(trim($data['email']));
        $existing = $this->model->findByEmail($email);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            if (($existing->status ?? '') === 'active') {
                return ApiResponse::ok(null, 'Already subscribed');
            }
            $this->model->update($existing->id, [
                'status' => 'active',
                'updated_at' => $now,
            ]);
            return ApiResponse::ok([
                'subscriber' => [
                    'id' => $existing->id,
                    'email' => $email,
                    'status' => 'active',
                    'subscribedAt' => $existing->created_at ?? $now,
                    'unsubscribedAt' => null,
                    'source' => 'public',
                ],
            ], 'Subscription reactivated');
        }

        $id = $this->uuid();

        $this->model->insert([
            'id' => $id,
            'email' => $email,
            'name' => isset($data['name']) ? (string) $data['name'] : null,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return ApiResponse::created([
            'subscriber' => [
                'id' => $id,
                'email' => $email,
                'status' => 'active',
                'subscribedAt' => $now,
                'unsubscribedAt' => null,
                'source' => 'public',
            ],
        ], 'Subscription created');
    }

    /** Public: confirm */
    public function confirm()
    {
        $token = $this->request->getGet('token');
        if (empty($token)) {
            return ApiResponse::badRequest('Token required');
        }

        $jwtManager = service('jwtManager');
        $payload    = $jwtManager->validateNewsletterToken($token);
        if (!$payload) {
            return ApiResponse::badRequest('Invalid or expired token');
        }

        $subscriber = $this->model->findByEmail($payload->email);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->update($subscriber->id, [
            'status'             => 'active',
            'confirmed_at'       => date('Y-m-d H:i:s'),
            'confirmation_token' => null,
        ]);

        return ApiResponse::ok(null, 'Subscription confirmed');
    }

    /** Public: unsubscribe */
    public function unsubscribe()
    {
        $data = $this->getJsonInput();
        if (empty($data['email'])) {
            return ApiResponse::badRequest('Email required');
        }

        $subscriber = $this->model->findByEmail($data['email']);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->update($subscriber->id, [
            'status' => 'unsubscribed',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return ApiResponse::ok(null, 'Unsubscribed successfully');
    }

    /** Admin: list subscribers */
    public function index()
    {
        [$page, $limit] = $this->paginationParams();
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $builder = $this->model;
        if ($status) {
            $builder = $builder->where('status', $status);
        }
        if ($search) {
            $builder = $builder->like('email', $search);
        }

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('created_at', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->findAll();

        $items = array_map(function ($item) {
            $row = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
            return [
                'id' => (string) ($row['id'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'status' => (string) ($row['status'] ?? 'active'),
                'subscribedAt' => $row['created_at'] ?? null,
                'unsubscribedAt' => ($row['status'] ?? null) === 'unsubscribed' ? ($row['updated_at'] ?? null) : null,
                'source' => 'public',
                'metadata' => [],
                'createdAt' => $row['created_at'] ?? null,
                'updatedAt' => $row['updated_at'] ?? null,
            ];
        }, $rows);

        return ApiResponse::paginated($items, $total, $page, $limit);
    }

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function subscribers()
    {
        return $this->index();
    }

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function adminUnsubscribe(string $id)
    {
        $subscriber = $this->model->find($id);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->update($id, [
            'status' => 'unsubscribed',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ApiResponse::ok(null, 'Subscriber unsubscribed');
    }

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function unsubscribePublic()
    {
        return $this->unsubscribe();
    }

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function unsubscribeLink()
    {
        $email = (string) ($this->request->getGet('email') ?? '');
        if ($email === '') {
            return ApiResponse::badRequest('Email required');
        }

        $subscriber = $this->model->findByEmail(strtolower(trim($email)));
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->update($subscriber->id, [
            'status' => 'unsubscribed',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ApiResponse::ok(null, 'Unsubscribed successfully');
    }

    /** Admin: delete */
    public function delete(string $id)
    {
        $subscriber = $this->model->find($id);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->delete($id);
        return ApiResponse::noContent('Subscriber removed');
    }

    /** Admin: export */
    public function export()
    {
        $format = $this->request->getGet('format') ?? 'csv';
        $subscribers = $this->model
            ->where('status', 'active')
            ->findAll();

        $exportService = service('exportService');

        switch ($format) {
            case 'csv':
                $content  = $exportService->subscribersToCsv($subscribers);
                $filename = 'subscribers-' . date('Y-m-d') . '.csv';
                return $this->response
                    ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);
            default:
                return ApiResponse::badRequest('Unsupported export format');
        }
    }
}
