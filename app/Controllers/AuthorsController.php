<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Libraries\SlugGenerator;
use App\Models\AuthorModel;

class AuthorsController extends BaseApiController
{
    private AuthorModel $model;

    public function __construct()
    {
        $this->model = new AuthorModel();
    }

    /**
     * GET /api/v1/authors
     */
    public function index()
    {
        $authors = $this->model->orderBy('name', 'ASC')->findAll();
        return ApiResponse::ok(array_map(fn($a) => $a->toArray(), $authors));
    }

    /**
     * POST /api/v1/authors
     */
    public function create()
    {
        $data   = $this->getJsonInput();
        $errors = $this->validateInput($data, 'createAuthor');
        if ($errors) {
            return ApiResponse::validationError($errors);
        }

        $id   = $this->uuid();
        $slug = SlugGenerator::generate($data['displayName'], 'authors', 'slug');

        $this->model->insert([
            'id'         => $id,
            'slug'       => $slug,
            'name'       => $data['displayName'],
            'bio'        => $data['bio'] ?? null,
            'avatar_url' => $data['avatar'] ?? null,
            'social'     => isset($data['socialLinks']) ? json_encode($data['socialLinks']) : '[]',
            'active'     => true,
        ]);

        $author = $this->model->find($id);
        return ApiResponse::created($author->toArray(), 'Author created');
    }

    /**
     * PUT /api/v1/authors/{id}
     */
    public function update(string $id)
    {
        $author = $this->model->find($id);
        if (!$author || !$author->active) {
            return ApiResponse::notFound('Author not found');
        }

        $data = $this->getJsonInput();
        $updateData = [];

        if (isset($data['displayName'])) {
            $updateData['name'] = $data['displayName'];
            $updateData['slug'] = SlugGenerator::generate($data['displayName'], 'authors', 'slug', $id);
        }
        if (isset($data['bio']))         $updateData['bio']    = $data['bio'];
        if (isset($data['avatar']))      $updateData['avatar_url'] = $data['avatar'];
        if (isset($data['socialLinks'])) $updateData['social'] = json_encode($data['socialLinks']);

        if (!empty($updateData)) {
            $this->model->update($id, $updateData);
        }

        $author = $this->model->find($id);
        return ApiResponse::ok($author->toArray(), 'Author updated');
    }

    /**
     * DELETE /api/v1/authors/{id}
     */
    public function delete(string $id)
    {
        $author = $this->model->find($id);
        if (!$author || !$author->active) {
            return ApiResponse::notFound('Author not found');
        }

        $this->model->update($id, ['active' => false]);
        return ApiResponse::noContent('Author deactivated');
    }
}
