<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\HomeLayoutConfigModel;

class HomeLayoutController extends BaseApiController
{
    private HomeLayoutConfigModel $model;

    public function __construct()
    {
        $this->model = new HomeLayoutConfigModel();
    }

    public function show()
    {
        return ApiResponse::ok($this->readPayload());
    }

    /**
     * Backward-compatible alias for the route target declared in Routes.php.
     */
    public function index()
    {
        return $this->show();
    }

    public function update()
    {
        $data = $this->getJsonInput();

        if (!is_array($data) || $data === []) {
            return ApiResponse::badRequest('Layout payload is required');
        }

        $this->model->upsertByKey('home_layout', $data);

        return ApiResponse::ok($this->readPayload(), 'Home layout updated');
    }

    /**
     * @return array{config: array<string, mixed>, updatedAt: string|null}
     */
    private function readPayload(): array
    {
        $row = $this->model->where('key', 'home_layout')->first();

        if ($row && is_array($row['value'] ?? null)) {
            return [
                'config' => $row['value'],
                'updatedAt' => $row['updated_at'] ?? null,
            ];
        }

        return [
            'config' => $this->model->defaultLayout(),
            'updatedAt' => null,
        ];
    }
}
