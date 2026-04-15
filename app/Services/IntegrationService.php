<?php

namespace App\Services;

use App\Models\IntegrationConfigModel;
use App\Models\IntegrationSnapshotModel;

class IntegrationService
{
    private IntegrationConfigModel $configModel;
    private IntegrationSnapshotModel $snapshotModel;

    public function __construct()
    {
        $this->configModel   = new IntegrationConfigModel();
        $this->snapshotModel = new IntegrationSnapshotModel();
    }

    /**
     * Get data for a provider (weather, dollar).
     * Uses snapshot caching with TTL and fallback.
     */
    public function getData(string $provider): array
    {
        // Check cached snapshot first
        $cached = $this->snapshotModel->getValidSnapshot($provider);
        if ($cached !== null) {
            return $cached;
        }

        // Try fetching fresh data
        try {
            $data = $this->fetchFromProvider($provider);

            // Cache the result
            $ttl = $this->getTtlForProvider($provider);
            $this->snapshotModel->upsert($provider, $data, $ttl);

            return $data;
        } catch (\Throwable $e) {
            // Fallback to expired snapshot
            $fallback = $this->snapshotModel->getFallbackSnapshot($provider);
            if ($fallback !== null) {
                return $fallback;
            }

            throw new \RuntimeException("Integration '{$provider}' unavailable: " . $e->getMessage(), 503);
        }
    }

    /**
     * Get provider statuses.
     */
    public function getStatus(): array
    {
        $providers = ['weather', 'dollar'];
        $statuses  = [];

        foreach ($providers as $provider) {
            $config   = $this->configModel->findByProvider($provider);
            $snapshot = $this->snapshotModel->find($provider);

            $statuses[$provider] = [
                'enabled'     => $config ? (bool) $config['enabled'] : false,
                'lastFetched' => $snapshot['fetched_at'] ?? null,
                'ttl'         => $snapshot['ttl_seconds'] ?? null,
                'hasData'     => $snapshot !== null,
            ];
        }

        return $statuses;
    }

    /**
     * Refresh all enabled integrations. Used by cron command.
     */
    public function refreshAll(): array
    {
        $providers = ['weather', 'dollar'];
        $results   = [];

        foreach ($providers as $provider) {
            $config = $this->configModel->findByProvider($provider);
            if ($config && $config['enabled']) {
                try {
                    $data = $this->fetchFromProvider($provider);
                    $ttl  = $this->getTtlForProvider($provider);
                    $this->snapshotModel->upsert($provider, $data, $ttl);
                    $results[$provider] = 'refreshed';
                } catch (\Throwable $e) {
                    $results[$provider] = 'error: ' . $e->getMessage();
                }
            } else {
                $results[$provider] = 'disabled';
            }
        }

        return $results;
    }

    /**
     * Fetch fresh data from external provider API.
     */
    private function fetchFromProvider(string $provider): array
    {
        $config = $this->configModel->findByProvider($provider);

        return match ($provider) {
            'weather' => $this->fetchWeather($config),
            'dollar'  => $this->fetchDollar($config),
            default   => throw new \RuntimeException("Unknown provider: {$provider}"),
        };
    }

    private function fetchWeather(?array $config): array
    {
        $apiUrl = env('WEATHER_API_URL', 'https://api.open-meteo.com/v1/forecast');
        $lat    = env('WEATHER_LATITUDE', '-34.6037');
        $lon    = env('WEATHER_LONGITUDE', '-58.3816');

        if ($config && !empty($config['config'])) {
            $cfg    = is_string($config['config']) ? json_decode($config['config'], true) : $config['config'];
            $apiUrl = $cfg['apiUrl'] ?? $apiUrl;
            $lat    = $cfg['latitude'] ?? $lat;
            $lon    = $cfg['longitude'] ?? $lon;
        }

        $url = "{$apiUrl}?latitude={$lat}&longitude={$lon}&current_weather=true&timezone=auto";

        $response = $this->httpGet($url);
        $data     = json_decode($response, true);

        if (!$data || !isset($data['current_weather'])) {
            throw new \RuntimeException('Invalid weather API response');
        }

        return [
            'provider'  => 'weather',
            'data'      => $data['current_weather'],
            'location'  => ['latitude' => $lat, 'longitude' => $lon],
            'fetchedAt' => date('c'),
        ];
    }

    private function fetchDollar(?array $config): array
    {
        $apiUrl = env('CURRENCY_API_URL', 'https://open.er-api.com/v6/latest/USD');

        if ($config && !empty($config['config'])) {
            $cfg    = is_string($config['config']) ? json_decode($config['config'], true) : $config['config'];
            $apiUrl = $cfg['apiUrl'] ?? $apiUrl;
        }

        $response = $this->httpGet($apiUrl);
        $data     = json_decode($response, true);

        if (!$data || !isset($data['rates'])) {
            throw new \RuntimeException('Invalid currency API response');
        }

        return [
            'provider'  => 'dollar',
            'data'      => [
                'base'  => $data['base_code'] ?? 'USD',
                'rates' => [
                    'ARS' => $data['rates']['ARS'] ?? null,
                    'BRL' => $data['rates']['BRL'] ?? null,
                    'EUR' => $data['rates']['EUR'] ?? null,
                    'GBP' => $data['rates']['GBP'] ?? null,
                ],
            ],
            'fetchedAt' => date('c'),
        ];
    }

    private function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Netxus-API/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            throw new \RuntimeException("HTTP request failed: {$error} (HTTP {$httpCode})");
        }

        return $response;
    }

    private function getTtlForProvider(string $provider): int
    {
        $config = $this->configModel->findByProvider($provider);

        if ($config && !empty($config['refresh_policy'])) {
            return $this->parseTtl($config['refresh_policy']);
        }

        return match ($provider) {
            'weather' => 300,   // 5 minutes
            'dollar'  => 3600,  // 1 hour
            default   => 600,
        };
    }

    /**
     * Parse TTL string like "5m", "1h", "30s" to seconds.
     */
    private function parseTtl(string $policy): int
    {
        if (preg_match('/^(\d+)(s|m|h|d)$/', $policy, $m)) {
            $value = (int) $m[1];
            return match ($m[2]) {
                's' => $value,
                'm' => $value * 60,
                'h' => $value * 3600,
                'd' => $value * 86400,
            };
        }

        return 300;
    }
}
