<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SlugGenerator;
use App\Models\CategoryModel;

class CategoriesController extends BaseApiController
{
    private CategoryModel $model;

    public function __construct()
    {
        $this->model = new CategoryModel();
    }

    public function index()
    {
        $categories = $this->model->getActive();
        return ApiResponse::ok(array_map(fn($c) => $c->toArray(), $categories));
    }

    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createCategory');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $id   = $this->uuid();
        $slug = SlugGenerator::generate($data['name'], 'categories', 'slug');

        $this->model->insert([
            'id'     => $id,
            'slug'   => $slug,
            'name'   => $data['name'],
            'active' => true,
        ]);

        $cat = $this->model->find($id);
        return ApiResponse::created($cat->toArray(), 'Category created');
    }

    public function update(string $id)
    {
        $cat = $this->model->find($id);
        if (!$cat || !$cat->active) {
            return ApiResponse::notFound('Category not found');
        }

        $data = $this->getJsonInput();
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = SlugGenerator::generate($data['name'], 'categories', 'slug', $id);
        }

        if (!empty($updateData)) {
            $this->model->update($id, $updateData);
        }

        $cat = $this->model->find($id);
        return ApiResponse::ok($cat->toArray(), 'Category updated');
    }

    public function delete(string $id)
    {
        $cat = $this->model->find($id);
        if (!$cat || !$cat->active) {
            return ApiResponse::notFound('Category not found');
        }

        $this->model->update($id, ['active' => false]);
        return ApiResponse::noContent('Category deactivated');
    }
}
