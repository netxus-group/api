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

    public function subscribe()
    {
        $data = $this->getJsonInput();
        $errors = $this->validateInput($data, 'subscribe');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $email = strtolower(trim((string) $data['email']));
        $name = trim((string) ($data['name'] ?? ''));
        $source = trim((string) ($data['source'] ?? 'public'));
        $now = date('Y-m-d H:i:s');
        $subscriber = $this->model->findByEmail($email);
        $communication = service('communicationService');

        if ($subscriber && strtolower((string) ($subscriber['status'] ?? $subscriber->status ?? '')) === 'active') {
            return ApiResponse::ok([
                'subscriber' => $this->mapSubscriber($subscriber),
            ], 'Already subscribed');
        }

        if ($subscriber) {
            $id = (string) ($subscriber['id'] ?? $subscriber->id);
            $this->model->update($id, [
                'name' => $name !== '' ? $name : ($subscriber['name'] ?? null),
                'source' => $source !== '' ? $source : ($subscriber['source'] ?? null),
                'status' => 'active',
                'confirmed_at' => $now,
                'unsubscribe_token_hash' => null,
                'unsubscribe_token_expires_at' => null,
                'updated_at' => $now,
            ]);
            $rawToken = $communication->issueNewsletterUnsubscribeToken($id, $email);
        } else {
            $id = $this->uuid();
            $this->model->insert([
                'id' => $id,
                'email' => $email,
                'name' => $name !== '' ? $name : null,
                'source' => $source !== '' ? $source : null,
                'metadata' => json_encode(['source' => $source], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'active',
                'confirmed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $rawToken = $communication->issueNewsletterUnsubscribeToken($id, $email);
        }

        $subscriber = $this->model->find($id);
        $unsubscribeUrl = rtrim((string) config('App')->baseURL, '/') . '/api/v1/public/newsletter/unsubscribe/' . $rawToken;

        $communication->sendTemplateEmail($email, 'newsletter_subscription', [
            'user_name' => $name !== '' ? $name : $email,
            'user_email' => $email,
            'unsubscribe_url' => $unsubscribeUrl,
            'site_name' => $communication->getConfig(false)['meta']['siteName'] ?? 'Netxus',
            'site_url' => $communication->getConfig(false)['meta']['portalUrl'] ?? rtrim((string) config('App')->baseURL, '/'),
        ], [
            'templateKey' => 'newsletter_subscription',
            'recipient_user_id' => null,
            'dedupeKey' => 'newsletter-subscription:' . $id,
        ]);

        return ApiResponse::created([
            'subscriber' => $this->mapSubscriber($subscriber),
        ], 'Subscription created');
    }

    public function confirm()
    {
        $token = $this->request->getGet('token');
        if (empty($token)) {
            return ApiResponse::badRequest('Token required');
        }

        $jwtManager = service('jwtManager');
        $payload = $jwtManager->validateNewsletterToken($token);
        if (!$payload) {
            return ApiResponse::badRequest('Invalid or expired token');
        }

        $subscriber = $this->model->findByEmail($payload->email);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->update($subscriber->id, [
            'status' => 'active',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmation_token' => null,
        ]);

        return ApiResponse::ok(null, 'Subscription confirmed');
    }

    public function unsubscribe()
    {
        $data = $this->getJsonInput();
        if (empty($data['email'])) {
            return ApiResponse::badRequest('Email required');
        }

        $email = strtolower(trim((string) $data['email']));
        $subscriber = $this->model->findByEmail($email);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->unsubscribeSubscriber((string) $subscriber->id);
        return ApiResponse::ok(null, 'Unsubscribed successfully');
    }

    public function unsubscribeLink(string $token)
    {
        $subscriber = service('communicationService')->findSubscriberByUnsubscribeToken($token);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->unsubscribeSubscriber((string) ($subscriber['id'] ?? ''));
        return ApiResponse::ok(null, 'Unsubscribed successfully');
    }

    public function index()
    {
        [$page, $limit] = $this->paginationParams();
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $builder = $this->model;
        if ($status) {
            $normalizedStatus = $status === 'subscribed' ? 'active' : ($status === 'unsubscribed' ? 'unsubscribed' : $status);
            $builder = $builder->where('status', $normalizedStatus);
        }
        if ($search) {
            $builder = $builder->like('email', $search);
        }

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('created_at', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->findAll();

        $items = array_map(fn ($item) => $this->mapSubscriber($item), $rows);

        return ApiResponse::paginated($items, $total, $page, $limit);
    }

    public function subscribers()
    {
        return $this->index();
    }

    public function adminUnsubscribe(string $id)
    {
        $subscriber = $this->model->find($id);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->unsubscribeSubscriber($id);
        return ApiResponse::ok(null, 'Subscriber unsubscribed');
    }

    public function unsubscribePublic()
    {
        return $this->unsubscribe();
    }

    public function delete(string $id)
    {
        $subscriber = $this->model->find($id);
        if (!$subscriber) {
            return ApiResponse::notFound('Subscriber not found');
        }

        $this->model->delete($id);
        return ApiResponse::noContent('Subscriber removed');
    }

    public function export()
    {
        $format = $this->request->getGet('format') ?? 'csv';
        $subscribers = $this->model
            ->whereIn('status', ['active', 'pending'])
            ->findAll();

        $exportService = service('exportService');

        switch ($format) {
            case 'csv':
                $content = $exportService->subscribersToCsv($subscribers);
                $filename = 'subscribers-' . date('Y-m-d') . '.csv';
                return $this->response
                    ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                    ->setHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->setBody($content);
            default:
                return ApiResponse::badRequest('Unsupported export format');
        }
    }

    private function unsubscribeSubscriber(string $id): void
    {
        $this->model->update($id, [
            'status' => 'unsubscribed',
            'unsubscribe_token_hash' => null,
            'unsubscribe_token_expires_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function mapSubscriber($item): array
    {
        $row = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item;
        $status = (string) ($row['status'] ?? 'active');
        $normalizedStatus = $status === 'unsubscribed' ? 'unsubscribed' : 'subscribed';

        return [
            'id' => (string) ($row['id'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'status' => $normalizedStatus,
            'subscribedAt' => $row['confirmed_at'] ?? $row['created_at'] ?? null,
            'unsubscribedAt' => $status === 'unsubscribed' ? ($row['updated_at'] ?? null) : null,
            'source' => (string) ($row['source'] ?? 'public'),
            'metadata' => isset($row['metadata']) && $row['metadata'] ? json_decode((string) $row['metadata'], true) : [],
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        ];
    }
}
