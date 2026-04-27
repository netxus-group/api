<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\IntegrationConfigModel;
use App\Services\IntegrationService;

class IntegrationsController extends BaseApiController
{
    private IntegrationService $service;
    private IntegrationConfigModel $configModel;

    public function __construct()
    {
        $this->service = service('integrationService');
        $this->configModel = new IntegrationConfigModel();
    }

    public function index()
    {
        $statuses = $this->service->getStatus();
        return ApiResponse::ok($statuses);
    }

    /**
     * Backward-compatible list endpoint used by dashboard.
     */
    public function listConfigs()
    {
        $providers = ['weather', 'dollar', 'crypto'];
        $items = [];

        foreach ($providers as $provider) {
            $row = $this->configModel->findByProvider($provider);
            $items[] = $this->normalizeConfigRow($provider, $row);
        }

        return ApiResponse::ok($items);
    }

    public function show(string $provider)
    {
        try {
            $data = $this->service->getData($provider);
            return ApiResponse::ok($data);
        } catch (\RuntimeException $exception) {
            return ApiResponse::ok([
                'provider' => $provider,
                'data' => [],
                'fetchedAt' => null,
                'stale' => true,
            ], 'Integration temporarily unavailable');
        }
    }

    public function refresh(string $provider)
    {
        try {
            $data = $this->service->refreshIntegrationCache($provider);
            return ApiResponse::ok($data, 'Integration refreshed');
        } catch (\RuntimeException $exception) {
            return ApiResponse::badRequest($exception->getMessage());
        }
    }

    public function refreshAll()
    {
        $results = $this->service->refreshAll();
        return ApiResponse::ok($results, 'All integrations refreshed');
    }

    public function configure(string $provider)
    {
        return $this->updateConfig($provider);
    }

    /**
     * Backward-compatible alias for public route target in Routes.php.
     */
    public function publicData(string $provider)
    {
        return $this->show($provider);
    }

    /**
     * Backward-compatible alias for route target in Routes.php.
     */
    public function status()
    {
        return $this->index();
    }

    public function updateConfig(string $provider)
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, ['weather', 'dollar', 'crypto'], true)) {
            return ApiResponse::notFound("Provider '{$provider}' not found");
        }

        $data = $this->getJsonInput();
        $columns = $this->configModel->getTableColumns();
        $existing = $this->configModel->findByProvider($provider);
        $existingConfig = $this->resolveConfigPayload($existing);

        $payloadConfig = is_array($data['config'] ?? null) ? $data['config'] : [];
        $mergedConfig = array_merge($existingConfig, $payloadConfig);

        if (isset($data['apiUrl'])) {
            $mergedConfig['apiUrl'] = trim((string) $data['apiUrl']);
        }
        if (isset($data['user'])) {
            $mergedConfig['user'] = trim((string) $data['user']);
        }
        if (isset($data['token'])) {
            $mergedConfig['token'] = trim((string) $data['token']);
        }

        $enabledValue = isset($data['enabled'])
            ? (bool) $data['enabled']
            : $this->resolveEnabled($existing);
        $refreshPolicy = isset($data['refreshPolicy']) && trim((string) $data['refreshPolicy']) !== ''
            ? trim((string) $data['refreshPolicy'])
            : $this->resolveRefreshPolicy($existing, $provider);

        $updateData = ['provider' => $provider];

        if (in_array('enabled', $columns, true)) {
            $updateData['enabled'] = $enabledValue;
        }
        if (in_array('active', $columns, true)) {
            $updateData['active'] = $enabledValue;
        }
        if (in_array('refresh_policy', $columns, true)) {
            $updateData['refresh_policy'] = $refreshPolicy;
        }
        if (in_array('ttl', $columns, true)) {
            $updateData['ttl'] = $refreshPolicy;
        }
        if (in_array('config', $columns, true)) {
            $updateData['config'] = $mergedConfig;
        }
        if (in_array('extra_config', $columns, true)) {
            $updateData['extra_config'] = $mergedConfig;
        }
        if (in_array('endpoint', $columns, true)) {
            $updateData['endpoint'] = (string) ($mergedConfig['apiUrl'] ?? ($existing['endpoint'] ?? ''));
        }
        if (in_array('api_key', $columns, true)) {
            $updateData['api_key'] = (string) ($mergedConfig['token'] ?? ($existing['api_key'] ?? ''));
        }

        if ($existing) {
            $this->configModel->update($existing['id'], $updateData);
        } else {
            if (in_array('id', $columns, true)) {
                $updateData['id'] = $this->uuid();
            }
            $this->configModel->insert($updateData);
        }

        $saved = $this->configModel->findByProvider($provider);

        return ApiResponse::ok($this->normalizeConfigRow($provider, $saved), "Integration '{$provider}' configured");
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>
     */
    private function normalizeConfigRow(string $provider, ?array $row): array
    {
        $config = $this->resolveConfigPayload($row);
        $enabled = $this->resolveEnabled($row);
        $refreshPolicy = $this->resolveRefreshPolicy($row, $provider);
        $apiUrl = (string) ($config['apiUrl'] ?? ($row['endpoint'] ?? ''));
        $token = (string) ($config['token'] ?? ($row['api_key'] ?? ''));

        return [
            'id' => (string) ($row['id'] ?? $provider),
            'provider' => $provider,
            'enabled' => $enabled,
            'refreshPolicy' => $refreshPolicy,
            'apiUrl' => $apiUrl,
            'user' => (string) ($config['user'] ?? ''),
            'token' => $token,
            'config' => $config,
        ];
    }

    private function resolveEnabled(?array $row): bool
    {
        if (!$row) {
            return false;
        }
        if (array_key_exists('enabled', $row)) {
            return (bool) $row['enabled'];
        }
        if (array_key_exists('active', $row)) {
            return (bool) $row['active'];
        }
        return false;
    }

    private function resolveRefreshPolicy(?array $row, string $provider): string
    {
        $fallback = '1h';
        if (!$row) {
            return $fallback;
        }
        if (!empty($row['refresh_policy'])) {
            return (string) $row['refresh_policy'];
        }
        if (!empty($row['ttl'])) {
            return (string) $row['ttl'];
        }
        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfigPayload(?array $row): array
    {
        if (!$row) {
            return [];
        }

        $raw = $row['config'] ?? $row['extra_config'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
