<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Services\IntegrationService;

class IntegrationsController extends BaseApiController
{
    private IntegrationService $service;

    public function __construct()
    {
        $this->service = service('integrationService');
    }

    public function index()
    {
        $statuses = $this->service->getStatus();
        return ApiResponse::ok($statuses);
    }

    public function show(string $provider)
    {
        $data = $this->service->getData($provider);
        if ($data === null) {
            return ApiResponse::notFound("Provider '{$provider}' not found");
        }
        return ApiResponse::ok($data);
    }

    public function refresh(string $provider)
    {
        $data = $this->service->getData($provider, true);
        if ($data === null) {
            return ApiResponse::notFound("Provider '{$provider}' not found");
        }
        return ApiResponse::ok($data, 'Integration refreshed');
    }

    public function refreshAll()
    {
        $results = $this->service->refreshAll();
        return ApiResponse::ok($results, 'All integrations refreshed');
    }

    public function configure(string $provider)
    {
        $data = $this->getJsonInput();

        $configModel = new \App\Models\IntegrationConfigModel();
        $existing    = $configModel->findByProvider($provider);

        $updateData = array_filter([
            'api_key'    => $data['apiKey'] ?? null,
            'endpoint'   => $data['endpoint'] ?? null,
            'ttl'        => $data['ttl'] ?? null,
            'active'     => isset($data['active']) ? (bool) $data['active'] : null,
            'extra_config' => isset($data['extraConfig']) ? json_encode($data['extraConfig']) : null,
        ], fn($v) => $v !== null);

        if ($existing) {
            $configModel->update($existing->id, $updateData);
        } else {
            $configModel->insert(array_merge($updateData, [
                'id'       => $this->uuid(),
                'provider' => $provider,
            ]));
        }

        return ApiResponse::ok(null, "Integration '{$provider}' configured");
    }
}
