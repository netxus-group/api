<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\AdSlotModel;

class AdsController extends BaseApiController
{
    private AdSlotModel $model;

    public function __construct()
    {
        $this->model = new AdSlotModel();
    }

    public function index()
    {
        $slots = $this->model->where('active', 1)->orderBy('placement')->findAll();
        return ApiResponse::ok(array_map(fn($s) => $s->toArray(), $slots));
    }

    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createAd');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $id = $this->uuid();
        $this->model->insert([
            'id'         => $id,
            'name'       => $data['name'],
            'placement'  => $data['placement'],
            'type'       => $data['type'],
            'content'    => isset($data['content']) ? json_encode($data['content']) : '{}',
            'target_url' => $data['targetUrl'] ?? null,
            'active'     => true,
            'starts_at'  => $data['startsAt'] ?? null,
            'ends_at'    => $data['endsAt'] ?? null,
        ]);

        $slot = $this->model->find($id);
        return ApiResponse::created($slot->toArray(), 'Ad slot created');
    }

    public function update(string $id)
    {
        $slot = $this->model->find($id);
        if (!$slot || !$slot->active) {
            return ApiResponse::notFound('Ad slot not found');
        }

        $data = $this->getJsonInput();
        $updateData = [];

        foreach (['name', 'placement', 'type', 'target_url', 'starts_at', 'ends_at'] as $field) {
            $camelKey = lcfirst(str_replace('_', '', ucwords($field, '_')));
            if (isset($data[$camelKey])) {
                $updateData[$field] = $data[$camelKey];
            } elseif (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['content'])) {
            $updateData['content'] = json_encode($data['content']);
        }
        if (isset($data['active'])) {
            $updateData['active'] = (bool) $data['active'];
        }

        if (!empty($updateData)) {
            $this->model->update($id, $updateData);
        }

        $slot = $this->model->find($id);
        return ApiResponse::ok($slot->toArray(), 'Ad slot updated');
    }

    public function delete(string $id)
    {
        $slot = $this->model->find($id);
        if (!$slot || !$slot->active) {
            return ApiResponse::notFound('Ad slot not found');
        }

        $this->model->update($id, ['active' => false]);
        return ApiResponse::noContent('Ad slot deactivated');
    }

    public function campaigns()
    {
        return ApiResponse::ok([], 'Campaigns placeholder');
    }
}
