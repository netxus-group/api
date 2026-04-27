<?php

namespace App\Services;

use App\Models\IntegrationConfigModel;
use App\Models\IntegrationSnapshotModel;

class IntegrationService
{
    private const DEFAULT_TTL_SECONDS = 3600;
    /**
     * @var array<int, array<string, float|string>>
     */
    private const WEATHER_LOCATIONS = [
        ['province' => 'Buenos Aires', 'city' => 'La Plata', 'latitude' => -34.9205, 'longitude' => -57.9536],
        ['province' => 'Cordoba', 'city' => 'Cordoba', 'latitude' => -31.4201, 'longitude' => -64.1888],
        ['province' => 'Santa Fe', 'city' => 'Rosario', 'latitude' => -32.9442, 'longitude' => -60.6505],
        ['province' => 'Mendoza', 'city' => 'Mendoza', 'latitude' => -32.8895, 'longitude' => -68.8458],
        ['province' => 'Tucuman', 'city' => 'San Miguel de Tucuman', 'latitude' => -26.8083, 'longitude' => -65.2176],
        ['province' => 'Salta', 'city' => 'Salta', 'latitude' => -24.7821, 'longitude' => -65.4232],
        ['province' => 'Rio Negro', 'city' => 'Bariloche', 'latitude' => -41.1335, 'longitude' => -71.3103],
        ['province' => 'Chubut', 'city' => 'Comodoro Rivadavia', 'latitude' => -45.8641, 'longitude' => -67.4966],
    ];

    private IntegrationConfigModel $configModel;
    private IntegrationSnapshotModel $snapshotModel;

    public function __construct()
    {
        $this->configModel   = new IntegrationConfigModel();
        $this->snapshotModel = new IntegrationSnapshotModel();
    }

    /**
     * Get data for a provider (weather, dollar, crypto).
     * Uses snapshot caching with TTL and fallback.
     */
    public function getData(string $provider): array
    {
        $ttl = $this->getTtlForProvider($provider);

        // Check cached snapshot first
        $cached = $this->snapshotModel->getValidSnapshot($provider, $ttl);
        if ($cached !== null) {
            return $cached;
        }

        // Prevent concurrent refresh storms for the same provider.
        $hasLock = $this->snapshotModel->acquireRefreshLock($provider, 45);
        if (!$hasLock) {
            $retryCached = $this->snapshotModel->getValidSnapshot($provider, $ttl);
            if ($retryCached !== null) {
                return $retryCached;
            }
        }

        // Try fetching fresh data
        try {
            $data = $this->fetchFromProvider($provider);

            // Cache the result
            $this->snapshotModel->upsert($provider, $data, $ttl);
            $this->snapshotModel->releaseRefreshLock($provider);

            return $data;
        } catch (\Throwable $e) {
            $this->snapshotModel->markRefreshError($provider, $e->getMessage());

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
        $providers = ['weather', 'dollar', 'crypto'];
        $statuses  = [];

        foreach ($providers as $provider) {
            $config   = $this->configModel->findByProvider($provider);
            $snapshot = $this->snapshotModel->findByProvider($provider);

            $statuses[$provider] = [
                'enabled'     => $this->isProviderEnabled($config),
                'lastFetched' => $snapshot['fetched_at'] ?? null,
                'ttl'         => $snapshot['ttl_seconds'] ?? $this->getTtlForProvider($provider),
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
        $providers = ['weather', 'dollar', 'crypto'];
        $results   = [];

        foreach ($providers as $provider) {
            $config = $this->configModel->findByProvider($provider);
            if ($this->isProviderEnabled($config)) {
                try {
                    $data = $this->fetchFromProvider($provider);
                    $ttl  = $this->getTtlForProvider($provider);
                    $this->snapshotModel->upsert($provider, $data, $ttl);
                    $results[$provider] = ['success' => true, 'message' => 'refreshed'];
                } catch (\Throwable $e) {
                    $this->snapshotModel->markRefreshError($provider, $e->getMessage());
                    $results[$provider] = ['success' => false, 'message' => $e->getMessage()];
                }
            } else {
                $results[$provider] = ['success' => false, 'message' => 'disabled'];
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
            'crypto'  => $this->fetchCrypto($config),
            default   => throw new \RuntimeException("Unknown provider: {$provider}"),
        };
    }

    public function getCachedIntegration(string $provider): ?array
    {
        return $this->snapshotModel->getValidSnapshot($provider, $this->getTtlForProvider($provider));
    }

    public function isCacheExpired(string $provider): bool
    {
        return $this->getCachedIntegration($provider) === null;
    }

    public function refreshIntegrationCache(string $provider): array
    {
        $data = $this->fetchFromProvider($provider);
        $ttl = $this->getTtlForProvider($provider);
        $this->snapshotModel->upsert($provider, $data, $ttl);
        return $data;
    }

    private function fetchWeather(?array $config): array
    {
        $cfg = $this->resolveConfigPayload($config);
        $apiUrl = (string) ($cfg['apiUrl'] ?? env('WEATHER_API_URL', 'https://api.open-meteo.com/v1/forecast'));
        $locations = $this->resolveWeatherLocations($cfg);
        $items = [];

        foreach ($locations as $location) {
            $lat = (float) $location['latitude'];
            $lon = (float) $location['longitude'];
            $url = "{$apiUrl}?latitude={$lat}&longitude={$lon}&current=temperature_2m,weather_code,is_day&timezone=auto";

            try {
                $response = $this->httpGet($url);
                $data     = json_decode($response, true);

                $current = is_array($data['current'] ?? null)
                    ? $data['current']
                    : (is_array($data['current_weather'] ?? null) ? $data['current_weather'] : null);
                if (!$current) {
                    continue;
                }

                $temperature = $current['temperature_2m'] ?? $current['temperature'] ?? null;
                $weatherCode = isset($current['weather_code'])
                    ? (int) $current['weather_code']
                    : (isset($current['weathercode']) ? (int) $current['weathercode'] : null);
                $isDay = isset($current['is_day'])
                    ? ((int) $current['is_day'] === 1)
                    : true;
                $conditions = $this->mapWeatherCondition($weatherCode, $isDay);

                $items[] = [
                    'province'    => (string) $location['province'],
                    'city'        => (string) $location['city'],
                    'temperature' => is_numeric($temperature) ? (float) $temperature : null,
                    'condition'   => $conditions['label'],
                    'iconKey'     => $conditions['iconKey'],
                    'weatherCode' => $weatherCode,
                    'isDay'       => $isDay,
                    'updatedAt'   => date('c'),
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($items === []) {
            throw new \RuntimeException('Invalid weather API response');
        }

        return [
            'provider'  => 'weather',
            'data'      => [
                'locations' => $items,
            ],
            'fetchedAt' => date('c'),
        ];
    }

    private function fetchDollar(?array $config): array
    {
        $apiUrl = env('CURRENCY_API_URL', 'https://criptoya.com/api/dolar');
        $cfg = $this->resolveConfigPayload($config);

        if ($cfg !== []) {
            $apiUrl = $cfg['apiUrl'] ?? $apiUrl;
        }

        if (is_string($apiUrl) && stripos($apiUrl, 'open.er-api.com') !== false) {
            $apiUrl = 'https://criptoya.com/api/dolar';
        }

        $response = $this->httpGet($apiUrl);
        $data     = json_decode($response, true);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid currency API response');
        }

        if (isset($data['rates']) && is_array($data['rates'])) {
            $ars = $this->toNullableFloat($data['rates']['ARS'] ?? null);
            if ($ars !== null) {
                return [
                    'provider'  => 'dollar',
                    'data'      => [
                        'variants' => [[
                            'key' => 'oficial',
                            'label' => 'Oficial',
                            'buy' => $ars,
                            'sell' => $ars,
                            'price' => $ars,
                            'variation' => null,
                            'updatedAt' => date('c'),
                        ]],
                    ],
                    'fetchedAt' => date('c'),
                ];
            }
        }

        $official = $this->extractMarketQuote($data['oficial'] ?? null, 'oficial');
        $blue = $this->extractMarketQuote($data['blue'] ?? null, 'blue');
        $mep = $this->extractBondQuote($data['mep'] ?? null, 'mep');
        $ccl = $this->extractBondQuote($data['ccl'] ?? null, 'ccl');
        $card = $this->extractMarketQuote($data['tarjeta'] ?? null, 'tarjeta');
        $savings = $this->extractMarketQuote($data['ahorro'] ?? null, 'ahorro');
        $wholesale = $this->extractMarketQuote($data['mayorista'] ?? null, 'mayorista');
        $crypto = $this->extractCryptoDollarQuote($data['cripto'] ?? null);

        $variants = array_values(array_filter([
            $official,
            $blue,
            $mep,
            $ccl,
            $card,
            $savings,
            $wholesale,
            $crypto,
        ], static fn ($entry) => is_array($entry)));

        if ($variants === []) {
            throw new \RuntimeException('Invalid currency API response');
        }

        return [
            'provider'  => 'dollar',
            'data'      => [
                'variants' => $variants,
            ],
            'fetchedAt' => date('c'),
        ];
    }

    private function fetchCrypto(?array $config): array
    {
        $cfg = $this->resolveConfigPayload($config);
        $apiUrl = (string) ($cfg['apiUrl'] ?? env('CRIPTOYA_API_URL', 'https://criptoya.com/api'));
        $coinsRaw = (string) ($cfg['coins'] ?? env('CRYPTO_COINS', 'BTC,ETH,USDT,USDC,DAI'));
        $coins = array_values(array_filter(array_map(
            static fn (string $coin): string => strtoupper(trim($coin)),
            explode(',', $coinsRaw)
        )));
        $fiat = strtoupper((string) ($cfg['fiat'] ?? env('CRYPTO_FIAT', 'ARS')));
        $volume = (string) ($cfg['volume'] ?? env('CRYPTO_VOLUME', '1'));

        if ($coins === []) {
            throw new \RuntimeException('No crypto coins configured');
        }

        $assets = [];
        foreach ($coins as $symbol) {
            $endpoint = rtrim($apiUrl, '/') . '/' . rawurlencode($symbol) . '/' . rawurlencode($fiat) . '/' . rawurlencode($volume);
            try {
                $response = $this->httpGet($endpoint);
                $payload = json_decode($response, true);
                if (!is_array($payload)) {
                    continue;
                }
                $quote = $this->extractCryptoQuote($payload);
                $ask = $quote['ask'];
                $bid = $quote['bid'];
                $totalAsk = $quote['totalAsk'];
                $totalBid = $quote['totalBid'];
                $time = $quote['time'];

                if ($ask === null && $bid === null) {
                    continue;
                }

                $assets[] = [
                    'symbol' => $symbol,
                    'name' => $this->cryptoName($symbol),
                    'buy' => $ask,
                    'sell' => $bid,
                    'totalBuy' => $totalAsk,
                    'totalSell' => $totalBid,
                    'spread' => ($ask !== null && $bid !== null) ? round($ask - $bid, 3) : null,
                    'updatedAt' => $time ? gmdate('c', $time) : date('c'),
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($assets === []) {
            throw new \RuntimeException('Invalid crypto API response');
        }

        return [
            'provider' => 'crypto',
            'data' => [
                'fiat' => $fiat,
                'volume' => $volume,
                'coins' => $assets,
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

        if ($config) {
            if (!empty($config['refresh_policy'])) {
                return $this->parseTtl((string) $config['refresh_policy']);
            }
            if (!empty($config['ttl'])) {
                return $this->parseTtl((string) $config['ttl']);
            }
        }

        return match ($provider) {
            'weather' => self::DEFAULT_TTL_SECONDS,
            'dollar'  => self::DEFAULT_TTL_SECONDS,
            'crypto'  => self::DEFAULT_TTL_SECONDS,
            default   => self::DEFAULT_TTL_SECONDS,
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

        return self::DEFAULT_TTL_SECONDS;
    }

    private function isProviderEnabled(?array $config): bool
    {
        if (!$config) {
            return false;
        }

        if (array_key_exists('enabled', $config)) {
            return (bool) $config['enabled'];
        }

        if (array_key_exists('active', $config)) {
            return (bool) $config['active'];
        }

        return false;
    }

    private function resolveConfigPayload(?array $config): array
    {
        if (!$config) {
            return [];
        }

        $raw = $config['config'] ?? $config['extra_config'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $cfg
     * @return array<int, array<string, mixed>>
     */
    private function resolveWeatherLocations(array $cfg): array
    {
        $rawLocations = $cfg['locations'] ?? null;
        if (!is_array($rawLocations)) {
            return self::WEATHER_LOCATIONS;
        }

        $normalized = [];
        foreach ($rawLocations as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!isset($item['province'], $item['city'], $item['latitude'], $item['longitude'])) {
                continue;
            }

            $lat = $this->toNullableFloat($item['latitude']);
            $lon = $this->toNullableFloat($item['longitude']);
            if ($lat === null || $lon === null) {
                continue;
            }

            $normalized[] = [
                'province' => (string) $item['province'],
                'city' => (string) $item['city'],
                'latitude' => $lat,
                'longitude' => $lon,
            ];
        }

        return $normalized !== [] ? $normalized : self::WEATHER_LOCATIONS;
    }

    /**
     * @return array{label: string, iconKey: string}
     */
    private function mapWeatherCondition(?int $weatherCode, bool $isDay): array
    {
        if ($weatherCode === null) {
            return ['label' => 'Sin datos', 'iconKey' => 'unknown'];
        }

        return match (true) {
            $weatherCode === 0 => ['label' => $isDay ? 'Despejado' : 'Cielo claro', 'iconKey' => 'sunny'],
            in_array($weatherCode, [1, 2], true) => ['label' => 'Parcialmente nublado', 'iconKey' => 'partly-cloudy'],
            $weatherCode === 3 => ['label' => 'Nublado', 'iconKey' => 'cloudy'],
            in_array($weatherCode, [45, 48], true) => ['label' => 'Niebla', 'iconKey' => 'fog'],
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => ['label' => 'Llovizna', 'iconKey' => 'drizzle'],
            in_array($weatherCode, [61, 63, 65, 66, 67, 80, 81, 82], true) => ['label' => 'Lluvia', 'iconKey' => 'rain'],
            in_array($weatherCode, [71, 73, 75, 77, 85, 86], true) => ['label' => 'Nieve', 'iconKey' => 'snow'],
            in_array($weatherCode, [95, 96, 99], true) => ['label' => 'Tormenta', 'iconKey' => 'storm'],
            default => ['label' => 'Variable', 'iconKey' => 'unknown'],
        };
    }

    /**
     * @param mixed $value
     */
    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return null;
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>|null
     */
    private function extractMarketQuote(mixed $raw, string $key): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $ask = $this->toNullableFloat($raw['ask'] ?? null);
        $bid = $this->toNullableFloat($raw['bid'] ?? null);
        $price = $this->toNullableFloat($raw['price'] ?? null);
        $variation = $this->toNullableFloat($raw['variation'] ?? null);
        $timestamp = isset($raw['timestamp']) ? (int) $raw['timestamp'] : null;

        if ($ask === null && $bid === null && $price === null) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $this->dollarLabel($key),
            'buy' => $bid,
            'sell' => $ask,
            'price' => $price ?? $ask ?? $bid,
            'variation' => $variation,
            'updatedAt' => $timestamp ? gmdate('c', $timestamp) : date('c'),
        ];
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>|null
     */
    private function extractBondQuote(mixed $raw, string $key): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $preferred = null;
        if (isset($raw['al30']) && is_array($raw['al30'])) {
            $preferred = $raw['al30'];
        } else {
            foreach ($raw as $entry) {
                if (is_array($entry)) {
                    $preferred = $entry;
                    break;
                }
            }
        }
        if (!is_array($preferred)) {
            return null;
        }

        $bucket = is_array($preferred['24hs'] ?? null)
            ? $preferred['24hs']
            : (is_array($preferred['ci'] ?? null) ? $preferred['ci'] : null);
        if (!is_array($bucket)) {
            return null;
        }

        $price = $this->toNullableFloat($bucket['price'] ?? null);
        if ($price === null) {
            return null;
        }

        $variation = $this->toNullableFloat($bucket['variation'] ?? null);
        $timestamp = isset($bucket['timestamp']) ? (int) $bucket['timestamp'] : null;

        return [
            'key' => $key,
            'label' => $this->dollarLabel($key),
            'buy' => null,
            'sell' => null,
            'price' => $price,
            'variation' => $variation,
            'updatedAt' => $timestamp ? gmdate('c', $timestamp) : date('c'),
        ];
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>|null
     */
    private function extractCryptoDollarQuote(mixed $raw): ?array
    {
        if (!is_array($raw)) {
            return null;
        }

        $preferred = null;
        foreach (['usdt', 'ccb', 'usdc'] as $key) {
            if (isset($raw[$key]) && is_array($raw[$key])) {
                $preferred = $raw[$key];
                break;
            }
        }
        if (!is_array($preferred)) {
            return null;
        }

        $ask = $this->toNullableFloat($preferred['ask'] ?? null);
        $bid = $this->toNullableFloat($preferred['bid'] ?? null);
        if ($ask === null && $bid === null) {
            return null;
        }

        $variation = $this->toNullableFloat($preferred['variation'] ?? null);
        $timestamp = isset($preferred['timestamp']) ? (int) $preferred['timestamp'] : null;

        return [
            'key' => 'cripto',
            'label' => 'Cripto',
            'buy' => $bid,
            'sell' => $ask,
            'price' => $ask ?? $bid,
            'variation' => $variation,
            'updatedAt' => $timestamp ? gmdate('c', $timestamp) : date('c'),
        ];
    }

    private function dollarLabel(string $key): string
    {
        return match ($key) {
            'oficial' => 'Oficial',
            'blue' => 'Blue',
            'mep' => 'MEP',
            'ccl' => 'CCL',
            'tarjeta' => 'Tarjeta',
            'ahorro' => 'Ahorro',
            'mayorista' => 'Mayorista',
            'cripto' => 'Cripto',
            default => ucfirst($key),
        };
    }

    private function cryptoName(string $symbol): string
    {
        return match ($symbol) {
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
            'USDT' => 'Tether',
            'USDC' => 'USD Coin',
            'DAI' => 'Dai',
            default => $symbol,
        };
    }

    /**
     * CriptoYa general endpoint can return:
     * - flat quote: {ask, bid, totalAsk, totalBid, time}
     * - map by exchange: {exchange1:{ask,bid,...}, exchange2:{...}}
     *
     * @param array<string, mixed> $payload
     * @return array{ask:?float,bid:?float,totalAsk:?float,totalBid:?float,time:?int}
     */
    private function extractCryptoQuote(array $payload): array
    {
        $flatAsk = $this->toNullableFloat($payload['ask'] ?? null);
        $flatBid = $this->toNullableFloat($payload['bid'] ?? null);
        $flatTotalAsk = $this->toNullableFloat($payload['totalAsk'] ?? null);
        $flatTotalBid = $this->toNullableFloat($payload['totalBid'] ?? null);
        $flatTime = isset($payload['time']) ? (int) $payload['time'] : null;

        if ($flatAsk !== null || $flatBid !== null) {
            return [
                'ask' => $flatAsk,
                'bid' => $flatBid,
                'totalAsk' => $flatTotalAsk,
                'totalBid' => $flatTotalBid,
                'time' => $flatTime,
            ];
        }

        $bestAsk = null;
        $bestBid = null;
        $bestTotalAsk = null;
        $bestTotalBid = null;
        $latestTime = null;

        foreach ($payload as $exchangeQuote) {
            if (!is_array($exchangeQuote)) {
                continue;
            }

            $ask = $this->toNullableFloat($exchangeQuote['ask'] ?? null);
            $bid = $this->toNullableFloat($exchangeQuote['bid'] ?? null);
            $totalAsk = $this->toNullableFloat($exchangeQuote['totalAsk'] ?? null);
            $totalBid = $this->toNullableFloat($exchangeQuote['totalBid'] ?? null);
            $time = isset($exchangeQuote['time']) ? (int) $exchangeQuote['time'] : null;

            if ($ask !== null && $ask > 0 && ($bestAsk === null || $ask < $bestAsk)) {
                $bestAsk = $ask;
                $bestTotalAsk = $totalAsk;
            }

            if ($bid !== null && $bid > 0 && ($bestBid === null || $bid > $bestBid)) {
                $bestBid = $bid;
                $bestTotalBid = $totalBid;
            }

            if ($time !== null && ($latestTime === null || $time > $latestTime)) {
                $latestTime = $time;
            }
        }

        return [
            'ask' => $bestAsk,
            'bid' => $bestBid,
            'totalAsk' => $bestTotalAsk,
            'totalBid' => $bestTotalBid,
            'time' => $latestTime,
        ];
    }
}
