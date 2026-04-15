<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SlugGenerator;
use App\Models\TagModel;

class TagsController extends BaseApiController
{
    private TagModel $model;

    public function __construct()
    {
        $this->model = new TagModel();
    }

    public function index()
    {
        $tags = $this->model->getActive();
        return ApiResponse::ok(array_map(fn($t) => $t->toArray(), $tags));
    }

    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createTag');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $id   = $this->uuid();
        $slug = SlugGenerator::generate($data['name'], 'tags', 'slug');

        $this->model->insert([
            'id'     => $id,
            'slug'   => $slug,
            'name'   => $data['name'],
            'active' => true,
        ]);

        $tag = $this->model->find($id);
        return ApiResponse::created($tag->toArray(), 'Tag created');
    }

    public function update(string $id)
    {
        $tag = $this->model->find($id);
        if (!$tag || !$tag->active) {
            return ApiResponse::notFound('Tag not found');
        }

        $data = $this->getJsonInput();
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = SlugGenerator::generate($data['name'], 'tags', 'slug', $id);
        }

        if (!empty($updateData)) {
            $this->model->update($id, $updateData);
        }

        $tag = $this->model->find($id);
        return ApiResponse::ok($tag->toArray(), 'Tag updated');
    }

    public function delete(string $id)
    {
        $tag = $this->model->find($id);
        if (!$tag || !$tag->active) {
            return ApiResponse::notFound('Tag not found');
        }

        $this->model->update($id, ['active' => false]);
        return ApiResponse::noContent('Tag deactivated');
    }
}
