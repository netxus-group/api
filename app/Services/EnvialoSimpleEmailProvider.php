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
        if (trim($this->config->envialoSimpleApiKey) === '') {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'Envialo Simple API key is not configured',
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

        $endpoint = trim($this->config->envialoSimpleApiUrl);
        if ($endpoint === '') {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'Envialo Simple endpoint is not configured',
                'externalId' => null,
            ];
        }

        $fromAddress = trim((string) ($metadata['fromAddress'] ?? $this->config->mailFromAddress));
        if ($fromAddress === '') {
            return [
                'status' => 'skipped',
                'provider' => 'envialo_simple',
                'message' => 'Envialo Simple sender address is not configured',
                'externalId' => null,
            ];
        }

        $fromName = trim((string) ($metadata['fromName'] ?? $this->config->mailFromName));
        $toName = trim((string) ($metadata['toName'] ?? ''));
        $replyTo = trim((string) ($metadata['replyTo'] ?? $this->config->mailReplyTo));
        $previewText = trim((string) ($metadata['previewText'] ?? ''));
        $context = is_array($metadata['context'] ?? null) ? $metadata['context'] : [];

        $payload = [
            'from' => $this->formatAddress($fromAddress, $fromName),
            'to' => $this->formatAddress($to, $toName),
            'subject' => $subject,
            'html' => $html,
            'substitutions' => $context,
        ];

        if ($text !== '') {
            $payload['text'] = $text;
        }
        if ($replyTo !== '') {
            $payload['replyTo'] = $replyTo;
        }
        if ($previewText !== '') {
            $payload['previewText'] = $previewText;
        }

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

        $parsed = is_string($body) && trim($body) !== '' ? json_decode($body, true) : null;
        $externalId = null;
        if (is_array($parsed)) {
            $externalId = $parsed['id'] ?? $parsed['messageId'] ?? $parsed['message_id'] ?? null;
            if (!is_string($externalId) && !is_numeric($externalId)) {
                $externalId = null;
            }
        }

        if ($body !== false && $error === '' && $statusCode >= 200 && $statusCode < 300) {
            return [
                'status' => 'sent',
                'provider' => 'envialo_simple',
                'message' => 'Email sent successfully',
                'externalId' => $externalId,
            ];
        }

        $message = $error !== '' ? $error : 'Envialo Simple delivery failed';
        if (is_array($parsed)) {
            $apiMessage = $parsed['message'] ?? $parsed['error'] ?? $parsed['detail'] ?? null;
            if (is_string($apiMessage) && trim($apiMessage) !== '') {
                $message = trim($apiMessage);
            }
        } elseif (is_string($body) && trim($body) !== '') {
            $message = trim($body);
        }

        return [
            'status' => 'failed',
            'provider' => 'envialo_simple',
            'message' => $message,
            'externalId' => $externalId,
        ];
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        $cleanEmail = trim($email);
        if ($cleanEmail === '' || $name === '') {
            return $cleanEmail;
        }

        $cleanName = trim(preg_replace('/[\r\n]+/', ' ', $name) ?? $name);
        return $cleanName . ' <' . $cleanEmail . '>';
    }
}
