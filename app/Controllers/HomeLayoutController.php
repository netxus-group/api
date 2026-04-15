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
        $layout = $this->model->getByKey('home_layout');
        return ApiResponse::ok($layout);
    }

    public function update()
    {
        $data = $this->getJsonInput();

        if (empty($data['sections']) || !is_array($data['sections'])) {
            return ApiResponse::badRequest('Sections array is required');
        }

        $this->model->upsertByKey('home_layout', $data['sections'], $this->userId());
        $layout = $this->model->getByKey('home_layout');

        return ApiResponse::ok($layout, 'Home layout updated');
    }
}
