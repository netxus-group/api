<?php

namespace App\Services;

use Config\Communications;

class EnvialoSimpleEmailProvider implements EmailProviderInterface
{
    public function __construct(private readonly Communications $config)
    {
    }

    public function send(string $to, string $subject, string $html, string $text = '', array $metadata = []): array
    {
        if ($this->config->envialoSimpleApiKey === '' || $this->config->envialoSimpleAccountId === '') {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'Envialo Simple credentials are not configured',
                'externalId' => null,
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'cURL is not available for Envialo Simple',
                'externalId' => null,
            ];
        }

        $endpoint = (string) env('ENVIALO_SIMPLE_API_URL', '');
        if ($endpoint === '') {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'Envialo Simple endpoint is not configured',
                'externalId' => null,
            ];
        }

        $payload = [
            'account_id' => $this->config->envialoSimpleAccountId,
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'metadata' => $metadata,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config->envialoSimpleApiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 20,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body !== false && $error === '' && $statusCode >= 200 && $statusCode < 300) {
            return [
                'status' => 'sent',
                'provider' => 'envialo_simple',
                'message' => 'Email sent successfully',
                'externalId' => null,
            ];
        }

        return [
            'status' => 'failed',
            'provider' => 'envialo_simple',
            'message' => $error !== '' ? $error : 'Envialo Simple delivery failed',
            'externalId' => null,
        ];
    }
}
