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

        if ($existing) {
            if ($existing->status === 'active') {
                return ApiResponse::ok(null, 'Already subscribed');
            }
            // Re-activate
            $this->model->update($existing->id, [
                'status'       => 'active',
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]);
            return ApiResponse::ok(null, 'Subscription reactivated');
        }

        $jwtManager = service('jwtManager');
        $token = $jwtManager->createNewsletterToken($email);

        $this->model->insert([
            'id'                 => $this->uuid(),
            'email'              => $email,
            'name'               => $data['name'] ?? null,
            'status'             => 'pending',
            'confirmation_token' => $token,
        ]);

        // TODO: send confirmation email with token

        return ApiResponse::created(null, 'Subscription pending confirmation');
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

        $this->model->update($subscriber->id, ['status' => 'unsubscribed']);
        return ApiResponse::ok(null, 'Unsubscribed successfully');
    }

    /** Admin: list subscribers */
    public function index()
    {
        [$page, $limit] = $this->paginationParams();
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $result = $this->model->listFiltered($page, $limit, $status, $search);
        return ApiResponse::paginated($result['data'], $result['meta']);
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
