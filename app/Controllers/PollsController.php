<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\PollService;

class PollsController extends BaseApiController
{
    private PollService $service;

    public function __construct()
    {
        $this->service = service('pollService');
    }

    public function index()
    {
        $polls = (new \App\Models\PollModel())
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $result = [];
        foreach ($polls as $poll) {
            $full = $this->service->getWithStats($poll->id);
            $result[] = $full;
        }

        return ApiResponse::ok($result);
    }

    public function show(string $id)
    {
        $poll = $this->service->getWithStats($id);
        if (!$poll) {
            return ApiResponse::notFound('Poll not found');
        }
        return ApiResponse::ok($poll);
    }

    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createPoll');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $poll = $this->service->create($data, $this->userId());
        return ApiResponse::created($poll, 'Poll created');
    }

    public function update(string $id)
    {
        $existing = (new \App\Models\PollModel())->find($id);
        if (!$existing) {
            return ApiResponse::notFound('Poll not found');
        }

        $data = $this->getJsonInput();
        $poll = $this->service->update($id, $data);
        return ApiResponse::ok($poll, 'Poll updated');
    }

    public function respond(string $id)
    {
        $existing = (new \App\Models\PollModel())->find($id);
        if (!$existing) {
            return ApiResponse::notFound('Poll not found');
        }

        $data = $this->getJsonInput();

        // Use session or fingerprint for anonymous responses
        $respondentId = $this->request->getHeaderLine('X-Fingerprint')
            ?: $this->request->getIPAddress();

        try {
            $this->service->respond($id, $respondentId, $data['answers'] ?? []);
        } catch (\RuntimeException $e) {
            return ApiResponse::conflict($e->getMessage());
        }

        return ApiResponse::ok(null, 'Response recorded');
    }

    public function results(string $id)
    {
        $stats = $this->service->getWithStats($id);
        if (!$stats) {
            return ApiResponse::notFound('Poll not found');
        }
        return ApiResponse::ok($stats);
    }

    public function delete(string $id)
    {
        $model = new \App\Models\PollModel();
        $existing = $model->find($id);
        if (!$existing) {
            return ApiResponse::notFound('Poll not found');
        }

        $model->update($id, ['active' => false]);
        return ApiResponse::noContent('Poll deactivated');
    }
}
