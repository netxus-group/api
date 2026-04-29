<?php

namespace App\Services;

use App\Libraries\SlugGenerator;
use App\Models\CommunicationLogModel;
use App\Models\CommunicationSettingModel;
use App\Models\EmailTemplateModel;
use App\Models\NewsletterCampaignItemModel;
use App\Models\NewsletterCampaignModel;
use App\Models\NewsletterSubscriberModel;
use App\Models\NewsModel;
use App\Models\SurveyNotificationLogModel;
use Config\Communications;

class CommunicationService
{
    private CommunicationSettingModel $settingModel;
    private EmailTemplateModel $templateModel;
    private CommunicationLogModel $logModel;
    private NewsletterCampaignModel $campaignModel;
    private NewsletterCampaignItemModel $campaignItemModel;
    private NewsletterSubscriberModel $subscriberModel;
    private NewsModel $newsModel;
    private SurveyNotificationLogModel $surveyNotificationModel;
    private Communications $config;

    public function __construct()
    {
        $this->settingModel = new CommunicationSettingModel();
        $this->templateModel = new EmailTemplateModel();
        $this->logModel = new CommunicationLogModel();
        $this->campaignModel = new NewsletterCampaignModel();
        $this->campaignItemModel = new NewsletterCampaignItemModel();
        $this->subscriberModel = new NewsletterSubscriberModel();
        $this->newsModel = new NewsModel();
        $this->surveyNotificationModel = new SurveyNotificationLogModel();
        $this->config = config('Communications');
    }

    public function getConfig(bool $includeSecrets = false): array
    {
        $row = $this->settingModel->findDefault();
        $public = $this->defaultPublicConfig();
        $secret = $this->defaultSecretConfig();

        if ($row) {
            $rowPublic = $this->normalizePublicConfig($row['public_config'] ?? []);
            $rowSecret = $this->decodeSecrets($row['secret_config_encrypted'] ?? null);
            $public = array_replace_recursive($public, $rowPublic);
            $secret = array_replace_recursive($secret, $rowSecret);
        }

        return [
            'email' => [
                'provider' => (string) ($public['email']['provider'] ?? $this->config->mailProvider),
                'fromAddress' => (string) ($public['email']['fromAddress'] ?? $this->config->mailFromAddress),
                'fromName' => (string) ($public['email']['fromName'] ?? $this->config->mailFromName),
                'replyTo' => (string) ($public['email']['replyTo'] ?? $this->config->mailReplyTo),
                'sendEnabled' => (bool) ($public['email']['sendEnabled'] ?? $this->config->mailSendEnabled),
                'testMode' => (bool) ($public['email']['testMode'] ?? $this->config->mailTestMode),
                'testEmail' => (string) ($public['email']['testEmail'] ?? $this->config->testEmail),
                'smtp' => [
                    'host' => (string) ($public['email']['smtp']['host'] ?? $this->config->smtpHost),
                    'port' => (int) ($public['email']['smtp']['port'] ?? $this->config->smtpPort),
                    'encryption' => (string) ($public['email']['smtp']['encryption'] ?? $this->config->smtpEncryption),
                    'user' => $includeSecrets ? (string) ($secret['email']['smtp']['user'] ?? $this->config->smtpUser) : '',
                    'password' => $includeSecrets ? (string) ($secret['email']['smtp']['password'] ?? $this->config->smtpPass) : '',
                ],
                'envialoSimple' => [
                    'accountId' => (string) ($public['email']['envialoSimple']['accountId'] ?? $this->config->envialoSimpleAccountId),
                    'apiKey' => $includeSecrets ? (string) ($secret['email']['envialoSimple']['apiKey'] ?? $this->config->envialoSimpleApiKey) : '',
                ],
                'status' => $this->emailProviderStatus($public, $secret),
                'masked' => [
                    'smtp' => [
                        'user' => $this->maskSecret((string) ($secret['email']['smtp']['user'] ?? $this->config->smtpUser)),
                        'password' => $this->maskSecret((string) ($secret['email']['smtp']['password'] ?? $this->config->smtpPass)),
                    ],
                    'envialoSimple' => [
                        'apiKey' => $this->maskSecret((string) ($secret['email']['envialoSimple']['apiKey'] ?? $this->config->envialoSimpleApiKey)),
                    ],
                ],
            ],
            'push' => [
                'provider' => (string) ($public['push']['provider'] ?? $this->config->pushProvider),
                'sendEnabled' => (bool) ($public['push']['sendEnabled'] ?? false),
                'testMode' => (bool) ($public['push']['testMode'] ?? false),
                'apiKey' => $includeSecrets ? (string) ($secret['push']['apiKey'] ?? $this->config->pushApiKey) : '',
                'appId' => $includeSecrets ? (string) ($secret['push']['appId'] ?? $this->config->pushAppId) : '',
                'masked' => [
                    'apiKey' => $this->maskSecret((string) ($secret['push']['apiKey'] ?? $this->config->pushApiKey)),
                    'appId' => $this->maskSecret((string) ($secret['push']['appId'] ?? $this->config->pushAppId)),
                ],
            ],
            'meta' => [
                'dashboardUrl' => $this->config->dashboardUrl,
                'portalUrl' => $this->config->portalUrl,
                'siteName' => $this->config->siteName,
                'mailProviderConfigured' => $this->emailProviderStatus($public, $secret) !== 'disabled',
            ],
            'stored' => [
                'public' => $public,
                'secret' => $includeSecrets ? $secret : [],
            ],
        ];
    }

    public function saveConfig(array $payload): array
    {
        $public = $this->normalizePublicConfig($payload);
        $secret = $this->normalizeSecretConfig($payload);

        $existing = $this->settingModel->findDefault();
        $existingPublic = $existing ? $this->normalizePublicConfig($existing['public_config'] ?? []) : $this->defaultPublicConfig();
        $existingSecret = $existing ? $this->decodeSecrets($existing['secret_config_encrypted'] ?? null) : $this->defaultSecretConfig();

        $mergedPublic = array_replace_recursive($this->defaultPublicConfig(), $existingPublic);
        $mergedSecret = array_replace_recursive($this->defaultSecretConfig(), $existingSecret);

        $mergedPublic['email'] = array_replace_recursive($mergedPublic['email'], $public['email'] ?? []);
        $mergedPublic['push'] = array_replace_recursive($mergedPublic['push'], $public['push'] ?? []);
        $mergedSecret['email'] = array_replace_recursive($mergedSecret['email'], $secret['email'] ?? []);
        $mergedSecret['push'] = array_replace_recursive($mergedSecret['push'], $secret['push'] ?? []);

        $data = [
            'scope' => 'default',
            'public_config' => json_encode($mergedPublic, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'secret_config_encrypted' => $this->encryptSecrets($mergedSecret),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->settingModel->findDefault();
        if ($existing) {
            $this->settingModel->update($existing['id'], $data);
        } else {
            $this->settingModel->insert(array_merge([
                'id' => $this->uuid(),
                'created_at' => date('Y-m-d H:i:s'),
            ], $data));
        }

        return $this->getConfig(false);
    }

    public function listTemplates(): array
    {
        $this->ensureDefaultTemplates();
        $rows = $this->templateModel->builder()
            ->orderBy('key', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(function (array $row): array {
            return $this->normalizeTemplateRow($row);
        }, $rows);
    }

    public function getTemplate(string $key): ?array
    {
        $this->ensureDefaultTemplates();
        $row = $this->templateModel->findByKey($key);
        return $row ? $this->normalizeTemplateRow($row) : null;
    }

    public function saveTemplate(string $key, array $payload): array
    {
        $existing = $this->templateModel->findByKey($key);
        $now = date('Y-m-d H:i:s');

        $row = [
            'key' => $key,
            'name' => $this->sanitizeText((string) ($payload['name'] ?? $key)),
            'subject' => $this->sanitizeText((string) ($payload['subject'] ?? '')),
            'html_body' => $this->sanitizeTemplateHtml((string) ($payload['html_body'] ?? '')),
            'text_body' => $this->sanitizeNullableText($payload['text_body'] ?? null),
            'variables_json' => json_encode($this->normalizeVariables($payload['variables'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_active' => $this->toBool($payload['is_active'] ?? true) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->templateModel->update($existing['id'], $row);
        } else {
            $row['id'] = $this->uuid();
            $row['created_at'] = $now;
            $this->templateModel->insert($row);
        }

        return $this->getTemplate($key) ?? [];
    }

    public function sendEmail(string $to, string $subject, string $html, string $text = '', array $metadata = []): array
    {
        $settings = $this->getConfig(true);
        $provider = strtolower((string) ($settings['email']['provider'] ?? $this->config->mailProvider));
        $sendEnabled = (bool) ($settings['email']['sendEnabled'] ?? $this->config->mailSendEnabled);
        $testMode = (bool) ($settings['email']['testMode'] ?? $this->config->mailTestMode);
        $testEmail = trim((string) ($settings['email']['testEmail'] ?? $this->config->testEmail));

        $recipient = strtolower(trim($to));
        if ($recipient === '') {
            return $this->logEmailResult('skipped', $provider, null, '', null, 'Recipient email is required', $metadata);
        }

        if (!$sendEnabled) {
            return $this->logEmailResult('skipped', $provider, $metadata['templateKey'] ?? null, $recipient, $metadata['recipient_user_id'] ?? null, 'Email sending is disabled', $metadata);
        }

        if ($testMode && $testEmail !== '') {
            $metadata['originalRecipient'] = $recipient;
            $recipient = $testEmail;
        }

        if (!empty($metadata['dedupeKey']) && $this->alreadySent((string) $metadata['dedupeKey'], $recipient, $subject)) {
            return $this->logEmailResult('skipped', $provider, $metadata['templateKey'] ?? null, $recipient, $metadata['recipient_user_id'] ?? null, 'Duplicate email skipped', $metadata);
        }

        $templateKey = isset($metadata['templateKey']) ? (string) $metadata['templateKey'] : null;
        $recipientUserId = isset($metadata['recipient_user_id']) ? (string) $metadata['recipient_user_id'] : null;
        $sender = $this->resolveSender($settings, $metadata);

        if ($sender['fromAddress'] !== '') {
            $metadata['fromAddress'] = $sender['fromAddress'];
        }
        if ($sender['fromName'] !== '') {
            $metadata['fromName'] = $sender['fromName'];
        }
        if ($sender['replyTo'] !== '') {
            $metadata['replyTo'] = $sender['replyTo'];
        }
        if (!isset($metadata['context']) || !is_array($metadata['context'])) {
            $metadata['context'] = [];
        }

        $logId = $this->createEmailLog($provider, $templateKey, $recipient, $recipientUserId, $subject, $metadata);

        $providerResult = match ($provider) {
            'envialo_simple' => $this->envialoSimpleProvider()->send($recipient, $subject, $html, $text, $metadata),
            'smtp' => $this->smtpProvider()->send($recipient, $subject, $html, $text, $metadata),
            default => [
                'status' => 'skipped',
                'provider' => $provider,
                'message' => 'Email provider is not configured',
            ],
        };

        $this->updateEmailLog($logId, $providerResult, $metadata);

        return $providerResult;
    }

    public function sendTemplateEmail(string $to, string $templateKey, array $variables = [], array $metadata = []): array
    {
        $this->ensureDefaultTemplates();
        $template = $this->templateModel->findByKey($templateKey);
        if (!$template) {
            return $this->sendEmail($to, 'Missing template', '', '', array_merge($metadata, ['templateKey' => $templateKey]));
        }

        if (!$this->toBool($template['is_active'] ?? true)) {
            return $this->logEmailResult('skipped', 'template', $templateKey, strtolower(trim($to)), $metadata['recipient_user_id'] ?? null, 'Template is inactive', $metadata);
        }

        $rendered = $this->renderTemplateRow($template, $variables);
        $metadata['templateKey'] = $templateKey;

        return $this->sendEmail($to, $rendered['subject'], $rendered['html'], $rendered['text'], $metadata);
    }

    public function sendPush(string $userId, string $title, string $body, string $url = '', array $metadata = []): array
    {
        $settings = $this->getConfig(true);
        $provider = strtolower((string) ($settings['push']['provider'] ?? $this->config->pushProvider));
        $sendEnabled = (bool) ($settings['push']['sendEnabled'] ?? false);

        $status = 'skipped';
        $message = 'Push notifications are not implemented yet';
        if (!$sendEnabled) {
            $message = 'Push notifications are disabled';
        } elseif ($provider === 'none') {
            $message = 'Push provider is not configured';
        }

        return $this->logPushResult($status, $provider, $userId, $title, $message, $metadata + ['body' => $body, 'url' => $url]);
    }

    public function listLogs(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = $this->logModel;
        if (!empty($filters['channel'])) {
            $builder = $builder->where('channel', (string) $filters['channel']);
        }
        if (!empty($filters['status'])) {
            $builder = $builder->where('status', (string) $filters['status']);
        }
        if (!empty($filters['templateKey'])) {
            $builder = $builder->where('template_key', (string) $filters['templateKey']);
        }
        if (!empty($filters['search'])) {
            $builder = $builder->groupStart()
                ->like('recipient_email', (string) $filters['search'])
                ->orLike('subject', (string) $filters['search'])
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('created_at', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->findAll();

        $items = array_map(static function (array $row): array {
            return [
                'id' => (string) ($row['id'] ?? ''),
                'channel' => (string) ($row['channel'] ?? 'email'),
                'provider' => (string) ($row['provider'] ?? ''),
                'templateKey' => $row['template_key'] ?? null,
                'recipientEmail' => $row['recipient_email'] ?? null,
                'recipientUserId' => $row['recipient_user_id'] ?? null,
                'subject' => $row['subject'] ?? null,
                'status' => (string) ($row['status'] ?? 'pending'),
                'errorMessage' => $row['error_message'] ?? null,
                'metadata' => $row['metadata_json'] ? json_decode((string) $row['metadata_json'], true) : [],
                'sentAt' => $row['sent_at'] ?? null,
                'createdAt' => $row['created_at'] ?? null,
                'updatedAt' => $row['updated_at'] ?? null,
            ];
        }, $rows);

        return [
            'items' => $items,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / max($perPage, 1)),
            ],
        ];
    }

    public function createNewsletterCampaign(array $payload, ?string $createdBy = null): array
    {
        $campaignId = $this->uuid();
        $newsIds = array_values(array_unique(array_map('strval', (array) ($payload['newsIds'] ?? []))));
        $now = date('Y-m-d H:i:s');

        $this->campaignModel->insert([
            'id' => $campaignId,
            'title' => $this->sanitizeText((string) ($payload['title'] ?? 'Newsletter')),
            'subject' => $this->sanitizeText((string) ($payload['subject'] ?? '')),
            'template_key' => (string) ($payload['templateKey'] ?? 'newsletter_news_digest'),
            'news_ids_json' => json_encode($newsIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'audience' => (string) ($payload['audience'] ?? 'active_subscribers'),
            'status' => 'draft',
            'preview_html' => null,
            'scheduled_at' => null,
            'sent_at' => null,
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($newsIds as $index => $newsId) {
            $this->campaignItemModel->insert([
                'id' => $this->uuid(),
                'campaign_id' => $campaignId,
                'news_id' => $newsId,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $this->getCampaign($campaignId);
    }

    public function getCampaign(string $campaignId): ?array
    {
        $campaign = $this->campaignModel->find($campaignId);
        if (!$campaign) {
            return null;
        }

        $items = $this->campaignItemModel->where('campaign_id', $campaignId)->orderBy('sort_order', 'ASC')->findAll();

        return [
            'id' => (string) $campaign['id'],
            'title' => (string) $campaign['title'],
            'subject' => (string) $campaign['subject'],
            'templateKey' => (string) ($campaign['template_key'] ?? 'newsletter_news_digest'),
            'newsIds' => json_decode((string) ($campaign['news_ids_json'] ?? '[]'), true) ?: [],
            'audience' => (string) ($campaign['audience'] ?? 'active_subscribers'),
            'status' => (string) ($campaign['status'] ?? 'draft'),
            'previewHtml' => $campaign['preview_html'] ?? null,
            'scheduledAt' => $campaign['scheduled_at'] ?? null,
            'sentAt' => $campaign['sent_at'] ?? null,
            'items' => array_map(static fn (array $row): array => [
                'id' => (string) ($row['id'] ?? ''),
                'newsId' => (string) ($row['news_id'] ?? ''),
                'sortOrder' => (int) ($row['sort_order'] ?? 1),
            ], $items),
            'createdAt' => $campaign['created_at'] ?? null,
            'updatedAt' => $campaign['updated_at'] ?? null,
        ];
    }

    public function listCampaigns(): array
    {
        $rows = $this->campaignModel->orderBy('created_at', 'DESC')->findAll();
        return array_map(fn (array $campaign): array => $this->getCampaign((string) $campaign['id']) ?? [], $rows);
    }

    public function sendNewsletterCampaign(array $payload, bool $testOnly = false, string $testEmail = ''): array
    {
        $newsIds = array_values(array_filter(
            array_unique(array_map(static fn (mixed $id): string => trim((string) $id), (array) ($payload['newsIds'] ?? []))),
            static fn (string $id): bool => $id !== ''
        ));
        $newsItems = [];
        if ($newsIds !== []) {
            $newsItems = $this->newsModel->whereIn('id', $newsIds)->where('deleted_at', null)->findAll();
        }
        $newsById = [];
        foreach ($newsItems as $news) {
            $newsById[$news->id] = $news->toArray();
        }

        $orderedItems = [];
        foreach ($newsIds as $newsId) {
            if (isset($newsById[$newsId])) {
                $orderedItems[] = $newsById[$newsId];
            }
        }

        $campaign = $this->createNewsletterCampaign($payload, (string) ($payload['createdBy'] ?? ''));
        $rendered = $this->renderNewsletterPreview($orderedItems, [
            'newsletter_title' => (string) ($payload['title'] ?? 'Newsletter'),
            'portal_url' => $this->config->portalUrl,
        ]);

        $previewHtml = $rendered['html'];
        $campaignId = (string) ($campaign['id'] ?? '');
        if ($campaignId !== '') {
            $this->campaignModel->update($campaignId, [
                'preview_html' => $previewHtml,
                'status' => $testOnly ? 'draft' : 'sending',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($testOnly) {
            if ($testEmail === '') {
                return [
                    'campaign' => $this->getCampaign($campaignId),
                    'summary' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 1],
                    'previewHtml' => $previewHtml,
                ];
            }

            $testResult = $this->sendTemplateEmail($testEmail, 'newsletter_news_digest', [
                'newsletter_title' => (string) ($payload['title'] ?? 'Newsletter'),
                'news_list' => $this->buildNewsListHtml($orderedItems),
                'unsubscribe_url' => rtrim($this->config->portalUrl, '/') . '/newsletter/unsubscribe',
                'portal_url' => $this->config->portalUrl,
            ], [
                'templateKey' => 'newsletter_news_digest',
                'senderProfile' => 'newsletter',
                'campaignId' => $campaignId,
                'recipient_user_id' => null,
                'dedupeKey' => "newsletter-test:{$campaignId}:{$testEmail}",
            ]);

            $summary = ['total' => 1, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
            if (($testResult['status'] ?? '') === 'sent') {
                $summary['sent'] = 1;
            } elseif (($testResult['status'] ?? '') === 'failed') {
                $summary['failed'] = 1;
            } else {
                $summary['skipped'] = 1;
            }

            return [
                'campaign' => $this->getCampaign($campaignId),
                'summary' => $summary,
                'previewHtml' => $previewHtml,
            ];
        }

        $subscribers = $this->subscriberModel->where('status', 'active')->findAll();
        $summary = ['total' => count($subscribers), 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($subscribers as $subscriber) {
            $unsubscribeUrl = rtrim($this->config->portalUrl, '/') . '/newsletter/unsubscribe/' . $this->issueNewsletterUnsubscribeToken((string) $subscriber->id, (string) $subscriber->email);
            $result = $this->sendTemplateEmail(
                (string) $subscriber->email,
                'newsletter_news_digest',
                [
                    'newsletter_title' => (string) ($payload['title'] ?? 'Newsletter'),
                    'newsletter_subject' => (string) ($payload['subject'] ?? ''),
                    'news_list' => $this->buildNewsListHtml($orderedItems),
                    'unsubscribe_url' => $unsubscribeUrl,
                    'portal_url' => $this->config->portalUrl,
                ],
                [
                    'templateKey' => 'newsletter_news_digest',
                    'senderProfile' => 'newsletter',
                    'campaignId' => $campaignId,
                    'subscriberId' => $subscriber->id,
                    'recipient_user_id' => null,
                    'dedupeKey' => "newsletter:{$campaignId}:{$subscriber->id}",
                ]
            );

            if ($result['status'] === 'sent') {
                $summary['sent']++;
            } elseif ($result['status'] === 'failed') {
                $summary['failed']++;
            } else {
                $summary['skipped']++;
            }
        }

        if ($campaignId !== '') {
            $this->campaignModel->update($campaignId, [
                'status' => $summary['failed'] > 0 ? 'failed' : 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'campaign' => $this->getCampaign($campaignId),
            'summary' => $summary,
            'previewHtml' => $previewHtml,
        ];
    }

    public function renderNewsletterPreview(array $newsItems, array $variables = []): array
    {
        $template = $this->templateModel->findByKey('newsletter_news_digest');
        if (!$template) {
            return ['subject' => '', 'html' => '', 'text' => ''];
        }

        $listHtml = $this->buildNewsListHtml($newsItems);
        $rendered = $this->renderTemplateRow($template, array_merge($variables, ['news_list' => $listHtml]));
        return $rendered;
    }

    public function notifySurveyPublished(string $surveyId, array $recipients, array $variables = [], bool $manual = false): array
    {
        $result = [
            'total' => count($recipients),
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($recipients as $recipient) {
            $recipientUserId = is_array($recipient) ? (string) ($recipient['user_id'] ?? '') : '';
            $recipientEmail = is_array($recipient) ? (string) ($recipient['email'] ?? '') : '';
            if ($recipientEmail === '') {
                $result['skipped']++;
                continue;
            }

            $stats = $this->sendSurveyNotification($surveyId, $recipientEmail, $recipientUserId !== '' ? $recipientUserId : null, $variables, $manual);
            $result[$stats['status']]++;
        }

        return $result;
    }

    public function sendSurveyNotification(string $surveyId, string $recipientEmail, ?string $recipientUserId, array $variables = [], bool $manual = false): array
    {
        $templateKey = 'new_survey_available';
        $dedupeType = $manual ? 'manual' : 'published';
        $existing = $this->surveyNotificationModel
            ->where('survey_id', $surveyId)
            ->where('notification_type', $dedupeType)
            ->groupStart()
            ->where('recipient_user_id', $recipientUserId)
            ->orWhere('recipient_email', strtolower(trim($recipientEmail)))
            ->groupEnd()
            ->first();

        if ($existing && (int) ($existing['sent_count'] ?? 0) > 0) {
            return ['status' => 'skipped', 'message' => 'Already notified'];
        }

        $rendered = $this->sendTemplateEmail($recipientEmail, $templateKey, $variables, [
            'templateKey' => $templateKey,
            'surveyId' => $surveyId,
            'recipient_user_id' => $recipientUserId,
            'notificationType' => $dedupeType,
            'dedupeKey' => $manual ? null : "survey:{$surveyId}:{$recipientEmail}",
        ]);

        $now = date('Y-m-d H:i:s');
        $log = [
            'survey_id' => $surveyId,
            'recipient_user_id' => $recipientUserId,
            'recipient_email' => strtolower(trim($recipientEmail)),
            'notification_type' => $dedupeType,
            'sent_count' => $rendered['status'] === 'sent' ? 1 : 0,
            'last_sent_at' => $rendered['status'] === 'sent' ? $now : null,
            'status' => $rendered['status'],
            'error_message' => $rendered['status'] === 'failed' ? ($rendered['message'] ?? null) : null,
            'metadata_json' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->surveyNotificationModel->update($existing['id'], [
                'sent_count' => (int) ($existing['sent_count'] ?? 0) + ($rendered['status'] === 'sent' ? 1 : 0),
                'last_sent_at' => $rendered['status'] === 'sent' ? $now : ($existing['last_sent_at'] ?? null),
                'status' => $rendered['status'],
                'error_message' => $log['error_message'],
                'metadata_json' => $log['metadata_json'],
                'updated_at' => $now,
            ]);
        } else {
            $log['id'] = $this->uuid();
            $this->surveyNotificationModel->insert($log);
        }

        return $rendered;
    }

    public function surveyNotificationStats(string $surveyId): array
    {
        $rows = $this->surveyNotificationModel->where('survey_id', $surveyId)->findAll();
        $stats = [
            'published' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0],
            'reminder' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0],
            'manual' => ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0],
        ];

        foreach ($rows as $row) {
            $type = (string) ($row['notification_type'] ?? 'published');
            if (!isset($stats[$type])) {
                $stats[$type] = ['total' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
            }
            $stats[$type]['total']++;
            $status = (string) ($row['status'] ?? 'skipped');
            if (isset($stats[$type][$status])) {
                $stats[$type][$status]++;
            }
        }

        return [
            'surveyId' => $surveyId,
            'totals' => $stats,
            'notifiedUsers' => count(array_filter($rows, static fn (array $row): bool => (int) ($row['sent_count'] ?? 0) > 0)),
        ];
    }

    public function runSurveyReminders(?string $surveyId = null, int $daysAfter = 3, int $maxReminders = 1): array
    {
        $threshold = date('Y-m-d H:i:s', time() - max(1, $daysAfter) * 86400);
        $db = db_connect();
        $builder = $db->table('survey_responses')
            ->select('survey_responses.*, surveys.title as survey_title, surveys.slug as survey_slug, surveys.initial_message, surveys.ends_at')
            ->join('surveys', 'surveys.id = survey_responses.survey_id', 'left')
            ->where('survey_responses.status', 'in_progress')
            ->where('survey_responses.updated_at <', $threshold);

        if ($surveyId) {
            $builder->where('survey_responses.survey_id', $surveyId);
        }

        $rows = $builder->get()->getResultArray();
        $result = ['total' => count($rows), 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        $users = [];
        $userIds = array_values(array_filter(array_column($rows, 'user_id')));
        if ($userIds !== []) {
            $userRows = $db->table('portal_users')->select('id, email, active')->whereIn('id', array_values(array_unique($userIds)))->get()->getResultArray();
            foreach ($userRows as $row) {
                $users[$row['id']] = $row;
            }
        }

        foreach ($rows as $row) {
            $userId = (string) ($row['user_id'] ?? '');
            $user = $userId !== '' ? ($users[$userId] ?? null) : null;
            if (!$user || (int) ($user['active'] ?? 0) !== 1) {
                $result['skipped']++;
                continue;
            }

            $log = $this->surveyNotificationModel
                ->where('survey_id', $row['survey_id'])
                ->where('recipient_user_id', $userId)
                ->where('notification_type', 'reminder')
                ->first();

            if ($log && (int) ($log['sent_count'] ?? 0) >= $maxReminders) {
                $result['skipped']++;
                continue;
            }

            if ($log && !empty($log['last_sent_at']) && strtotime((string) $log['last_sent_at']) > strtotime("-{$daysAfter} days")) {
                $result['skipped']++;
                continue;
            }

            $rendered = $this->sendTemplateEmail((string) $user['email'], 'survey_incomplete_reminder', [
                'user_name' => (string) ($row['display_name'] ?? $user['email']),
                'user_email' => (string) $user['email'],
                'survey_title' => (string) ($row['survey_title'] ?? ''),
                'survey_url' => rtrim($this->config->portalUrl, '/') . '/encuestas/' . (string) ($row['survey_slug'] ?? ''),
                'survey_initial_message' => (string) ($row['initial_message'] ?? ''),
                'survey_ends_at' => (string) ($row['ends_at'] ?? ''),
            ], [
                'surveyId' => $row['survey_id'],
                'recipient_user_id' => $userId,
                'notificationType' => 'reminder',
                'dedupeKey' => "survey-reminder:{$row['survey_id']}:{$userId}",
            ]);

            $now = date('Y-m-d H:i:s');
            $payload = [
                'id' => $log['id'] ?? $this->uuid(),
                'survey_id' => $row['survey_id'],
                'recipient_user_id' => $userId,
                'recipient_email' => (string) $user['email'],
                'notification_type' => 'reminder',
                'sent_count' => ($log ? (int) ($log['sent_count'] ?? 0) : 0) + ($rendered['status'] === 'sent' ? 1 : 0),
                'last_sent_at' => $rendered['status'] === 'sent' ? $now : ($log['last_sent_at'] ?? null),
                'status' => $rendered['status'],
                'error_message' => $rendered['status'] === 'failed' ? ($rendered['message'] ?? null) : null,
                'metadata_json' => json_encode(['daysAfter' => $daysAfter, 'maxReminders' => $maxReminders], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if ($log) {
                $this->surveyNotificationModel->update($log['id'], $payload);
            } else {
                $this->surveyNotificationModel->insert($payload);
            }

            if ($rendered['status'] === 'sent') {
                $result['sent']++;
            } elseif ($rendered['status'] === 'failed') {
                $result['failed']++;
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    private function smtpProvider(): EmailProviderInterface
    {
        return new SmtpEmailProvider($this->config);
    }

    private function envialoSimpleProvider(): EmailProviderInterface
    {
        return new EnvialoSimpleEmailProvider($this->config);
    }

    private function defaultPublicConfig(): array
    {
        return [
            'email' => [
                'provider' => $this->config->mailProvider,
                'fromAddress' => $this->config->mailFromAddress,
                'fromName' => $this->config->mailFromName,
                'replyTo' => $this->config->mailReplyTo,
                'sendEnabled' => $this->config->mailSendEnabled,
                'testMode' => $this->config->mailTestMode,
                'testEmail' => $this->config->testEmail,
                'smtp' => [
                    'host' => $this->config->smtpHost,
                    'port' => $this->config->smtpPort,
                    'encryption' => $this->config->smtpEncryption,
                ],
                'envialoSimple' => [
                    'accountId' => $this->config->envialoSimpleAccountId,
                ],
            ],
            'push' => [
                'provider' => $this->config->pushProvider,
                'sendEnabled' => false,
                'testMode' => false,
            ],
        ];
    }

    private function defaultSecretConfig(): array
    {
        return [
            'email' => [
                'smtp' => [
                    'user' => $this->config->smtpUser,
                    'password' => $this->config->smtpPass,
                ],
                'envialoSimple' => [
                    'apiKey' => $this->config->envialoSimpleApiKey,
                ],
            ],
            'push' => [
                'apiKey' => $this->config->pushApiKey,
                'appId' => $this->config->pushAppId,
            ],
        ];
    }

    private function normalizePublicConfig(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $email = is_array($payload['email'] ?? null) ? $payload['email'] : [];
        $push = is_array($payload['push'] ?? null) ? $payload['push'] : [];

        return [
            'email' => [
                'provider' => $this->sanitizeText((string) ($email['provider'] ?? 'smtp')),
                'fromAddress' => $this->sanitizeText((string) ($email['fromAddress'] ?? '')),
                'fromName' => $this->sanitizeText((string) ($email['fromName'] ?? '')),
                'replyTo' => $this->sanitizeText((string) ($email['replyTo'] ?? '')),
                'sendEnabled' => $this->toBool($email['sendEnabled'] ?? false),
                'testMode' => $this->toBool($email['testMode'] ?? false),
                'testEmail' => $this->sanitizeText((string) ($email['testEmail'] ?? '')),
                'smtp' => [
                    'host' => $this->sanitizeText((string) (($email['smtp']['host'] ?? '') ?: '')),
                    'port' => max(1, (int) ($email['smtp']['port'] ?? 587)),
                    'encryption' => $this->sanitizeText((string) ($email['smtp']['encryption'] ?? 'tls')),
                ],
                'envialoSimple' => [
                    'accountId' => $this->sanitizeText((string) ($email['envialoSimple']['accountId'] ?? '')),
                ],
            ],
            'push' => [
                'provider' => $this->sanitizeText((string) ($push['provider'] ?? 'none')),
                'sendEnabled' => $this->toBool($push['sendEnabled'] ?? false),
                'testMode' => $this->toBool($push['testMode'] ?? false),
            ],
        ];
    }

    private function normalizeSecretConfig(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $email = is_array($payload['email'] ?? null) ? $payload['email'] : [];
        $push = is_array($payload['push'] ?? null) ? $payload['push'] : [];

        $secret = [
            'email' => [
                'smtp' => [],
                'envialoSimple' => [],
            ],
            'push' => [],
        ];

        $smtpUser = $this->sanitizeNullableText($email['smtp']['user'] ?? null);
        if ($smtpUser !== null) {
            $secret['email']['smtp']['user'] = $smtpUser;
        }
        $smtpPass = $this->sanitizeNullableText($email['smtp']['password'] ?? null);
        if ($smtpPass !== null) {
            $secret['email']['smtp']['password'] = $smtpPass;
        }
        $envialoApiKey = $this->sanitizeNullableText($email['envialoSimple']['apiKey'] ?? null);
        if ($envialoApiKey !== null) {
            $secret['email']['envialoSimple']['apiKey'] = $envialoApiKey;
        }
        $pushApiKey = $this->sanitizeNullableText($push['apiKey'] ?? null);
        if ($pushApiKey !== null) {
            $secret['push']['apiKey'] = $pushApiKey;
        }
        $pushAppId = $this->sanitizeNullableText($push['appId'] ?? null);
        if ($pushAppId !== null) {
            $secret['push']['appId'] = $pushAppId;
        }

        return $secret;
    }

    private function renderTemplateRow(array $template, array $variables): array
    {
        $context = array_merge($this->baseTemplateVariables(), $this->normalizeTemplateVariables($variables));
        $subject = $this->replaceVariables((string) ($template['subject'] ?? ''), $context);
        $html = $this->replaceVariables((string) ($template['html_body'] ?? ''), $context, true);
        $text = $template['text_body'] !== null && $template['text_body'] !== ''
            ? $this->replaceVariables((string) $template['text_body'], $context, false)
            : trim(strip_tags($html));

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'template' => $this->normalizeTemplateRow($template),
        ];
    }

    private function normalizeTemplateRow(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'key' => (string) ($row['key'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'subject' => (string) ($row['subject'] ?? ''),
            'htmlBody' => (string) ($row['html_body'] ?? ''),
            'textBody' => $row['text_body'] ?? null,
            'variables' => $this->decodeVariables($row['variables_json'] ?? null),
            'isActive' => $this->toBool($row['is_active'] ?? true),
            'createdAt' => $row['created_at'] ?? null,
            'updatedAt' => $row['updated_at'] ?? null,
        ];
    }

    private function decodeVariables(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }

    private function normalizeVariables(mixed $raw): array
    {
        $values = $this->decodeVariables($raw);
        return array_values(array_filter($values, static fn (string $item): bool => trim($item) !== ''));
    }

    private function normalizeTemplateVariables(array $variables): array
    {
        $normalized = [];
        foreach ($variables as $key => $value) {
            $normalized[(string) $key] = $value;
        }
        return $normalized;
    }

    private function resolveSender(array $settings, array $metadata): array
    {
        $defaultFromAddress = trim((string) ($settings['email']['fromAddress'] ?? $this->config->mailFromAddress));
        $defaultFromName = trim((string) ($settings['email']['fromName'] ?? $this->config->mailFromName));
        $defaultReplyTo = trim((string) ($settings['email']['replyTo'] ?? $this->config->mailReplyTo));

        $profile = strtolower(trim((string) ($metadata['senderProfile'] ?? '')));
        if ($profile === '') {
            $profile = $this->inferSenderProfile($metadata['templateKey'] ?? null);
        }

        $fromAddress = $defaultFromAddress;
        $fromName = $defaultFromName;
        $replyTo = $defaultReplyTo;

        if ($profile === 'welcome') {
            $fromAddress = trim($this->config->mailWelcomeFromAddress) !== '' ? trim($this->config->mailWelcomeFromAddress) : $fromAddress;
            $fromName = trim($this->config->mailWelcomeFromName) !== '' ? trim($this->config->mailWelcomeFromName) : $fromName;
            $replyTo = trim($this->config->mailWelcomeReplyTo) !== '' ? trim($this->config->mailWelcomeReplyTo) : $replyTo;
        }

        if ($profile === 'newsletter') {
            $fromAddress = trim($this->config->mailNewsletterFromAddress) !== '' ? trim($this->config->mailNewsletterFromAddress) : $fromAddress;
            $fromName = trim($this->config->mailNewsletterFromName) !== '' ? trim($this->config->mailNewsletterFromName) : $fromName;
            $replyTo = trim($this->config->mailNewsletterReplyTo) !== '' ? trim($this->config->mailNewsletterReplyTo) : $replyTo;
        }

        $metadataFromAddress = trim((string) ($metadata['fromAddress'] ?? ''));
        $metadataFromName = trim((string) ($metadata['fromName'] ?? ''));
        $metadataReplyTo = trim((string) ($metadata['replyTo'] ?? ''));

        if ($metadataFromAddress !== '') {
            $fromAddress = $metadataFromAddress;
        }
        if ($metadataFromName !== '') {
            $fromName = $metadataFromName;
        }
        if ($metadataReplyTo !== '') {
            $replyTo = $metadataReplyTo;
        }

        return [
            'fromAddress' => $fromAddress,
            'fromName' => $fromName,
            'replyTo' => $replyTo,
        ];
    }

    private function inferSenderProfile(mixed $templateKey): string
    {
        $key = strtolower(trim((string) $templateKey));
        if ($key === '') {
            return 'default';
        }

        if ($key === 'welcome_user') {
            return 'welcome';
        }

        if (str_starts_with($key, 'newsletter_')) {
            return 'newsletter';
        }

        return 'default';
    }

    private function baseTemplateVariables(): array
    {
        $settings = $this->getConfig(false);
        return [
            'site_name' => (string) ($settings['meta']['siteName'] ?? $this->config->siteName),
            'site_url' => (string) ($this->config->dashboardUrl !== '' ? $this->config->dashboardUrl : ''),
            'portal_url' => (string) ($this->config->portalUrl !== '' ? $this->config->portalUrl : ''),
            'current_year' => date('Y'),
        ];
    }

    private function replaceVariables(string $content, array $variables, bool $html = false): string
    {
        return (string) preg_replace_callback('/{{\s*([a-zA-Z0-9_]+)\s*}}/', static function (array $matches) use ($variables, $html): string {
            $key = $matches[1];
            $value = $variables[$key] ?? '';
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $stringValue = (string) $value;
            if ($html && $key === 'news_list') {
                return $stringValue;
            }
            return htmlspecialchars($stringValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $content);
    }

    private function buildNewsListHtml(array $newsItems): string
    {
        $items = [];
        foreach ($newsItems as $news) {
            $title = htmlspecialchars((string) ($news['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $summary = htmlspecialchars((string) ($news['excerpt'] ?? $news['summary'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $slug = htmlspecialchars((string) ($news['slug'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $image = htmlspecialchars((string) ($news['cover_image_url'] ?? $news['heroImage'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $url = rtrim($this->config->portalUrl, '/') . '/news/' . $slug;
            $items[] = '<article style="margin:0 0 24px;padding:0 0 24px;border-bottom:1px solid #e5e5e5">'
                . ($image !== '' ? '<img src="' . $image . '" alt="' . $title . '" style="width:100%;max-width:100%;height:auto;border-radius:12px;margin-bottom:12px" />' : '')
                . '<h3 style="margin:0 0 8px;font-size:20px;line-height:1.25">' . $title . '</h3>'
                . '<p style="margin:0 0 10px;color:#555;line-height:1.6">' . $summary . '</p>'
                . '<p style="margin:0"><a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="color:#0f172a;text-decoration:none;font-weight:600">Leer nota</a></p>'
                . '</article>';
        }

        return implode('', $items);
    }

    private function createEmailLog(string $provider, ?string $templateKey, string $recipientEmail, ?string $recipientUserId, string $subject, array $metadata): string
    {
        $now = date('Y-m-d H:i:s');
        $id = $this->uuid();
        $this->logModel->insert([
            'id' => $id,
            'channel' => 'email',
            'provider' => $provider,
            'template_key' => $templateKey,
            'recipient_email' => $recipientEmail,
            'recipient_user_id' => $recipientUserId,
            'subject' => $subject,
            'status' => 'pending',
            'error_message' => null,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sent_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function issueNewsletterUnsubscribeToken(string $subscriberId, string $email): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);

        $this->subscriberModel->update($subscriberId, [
            'unsubscribe_token_hash' => $hash,
            'unsubscribe_token_expires_at' => date('Y-m-d H:i:s', time() + (int) env('NEWSLETTER_UNSUBSCRIBE_EXPIRES', 15552000)),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $raw;
    }

    public function findSubscriberByUnsubscribeToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        return $this->subscriberModel
            ->where('unsubscribe_token_hash', $hash)
            ->where('unsubscribe_token_expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    private function updateEmailLog(string $id, array $providerResult, array $metadata): void
    {
        $status = (string) ($providerResult['status'] ?? 'skipped');
        $this->logModel->update($id, [
            'status' => $status,
            'error_message' => $status === 'failed' ? (string) ($providerResult['message'] ?? 'Email delivery failed') : null,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function logEmailResult(string $status, string $provider, ?string $templateKey, string $recipientEmail, ?string $recipientUserId, string $message, array $metadata): array
    {
        $logId = $this->createEmailLog($provider, $templateKey, $recipientEmail, $recipientUserId, (string) ($metadata['subject'] ?? ''), $metadata);
        $this->logModel->update($logId, [
            'status' => $status,
            'error_message' => $status === 'failed' ? $message : null,
            'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'status' => $status,
            'provider' => $provider,
            'message' => $message,
            'externalId' => null,
        ];
    }

    private function logPushResult(string $status, string $provider, string $userId, string $title, string $message, array $metadata): array
    {
        $now = date('Y-m-d H:i:s');
        $id = $this->uuid();
        $this->logModel->insert([
            'id' => $id,
            'channel' => 'push',
            'provider' => $provider,
            'template_key' => null,
            'recipient_email' => null,
            'recipient_user_id' => $userId,
            'subject' => $title,
            'status' => $status,
            'error_message' => $status === 'failed' ? $message : null,
            'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sent_at' => $status === 'sent' ? $now : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'status' => $status,
            'provider' => $provider,
            'message' => $message,
            'externalId' => null,
        ];
    }

    private function emailProviderStatus(array $public, array $secret): string
    {
        if (!($public['email']['sendEnabled'] ?? false)) {
            return 'disabled';
        }

        $provider = strtolower((string) ($public['email']['provider'] ?? $this->config->mailProvider));
        if ($provider === 'smtp') {
            return trim((string) ($public['email']['smtp']['host'] ?? '')) !== '' && trim((string) ($secret['email']['smtp']['user'] ?? '')) !== '' && trim((string) ($secret['email']['smtp']['password'] ?? '')) !== ''
                ? 'ready'
                : 'missing_credentials';
        }

        if ($provider === 'envialo_simple') {
            return trim((string) ($secret['email']['envialoSimple']['apiKey'] ?? '')) !== ''
                ? 'ready'
                : 'missing_credentials';
        }

        return 'unknown';
    }

    private function maskSecret(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        $length = strlen($trimmed);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($trimmed, 0, 2) . str_repeat('*', max(3, $length - 4)) . substr($trimmed, -2);
    }

    private function encryptSecrets(array $payload): string
    {
        return service('encrypter')->encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function decodeSecrets(?string $encrypted): array
    {
        if ($encrypted === null || trim($encrypted) === '') {
            return [];
        }

        try {
            $decoded = service('encrypter')->decrypt($encrypted);
            $payload = json_decode((string) $decoded, true);
            return is_array($payload) ? $payload : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function alreadySent(string $dedupeKey, string $recipient, string $subject): bool
    {
        return $this->logModel
            ->where('recipient_email', $recipient)
            ->where('subject', $subject)
            ->where('status', 'sent')
            ->groupStart()
            ->like('metadata_json', $dedupeKey)
            ->orLike('metadata_json', json_encode(['dedupeKey' => $dedupeKey], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->groupEnd()
            ->countAllResults() > 0;
    }

    private function sanitizeText(string $value): string
    {
        return trim(mb_substr(strip_tags($value), 0, 5000));
    }

    private function hasTables(array $tables): bool
    {
        try {
            $db = db_connect();
            foreach ($tables as $table) {
                if (!$db->tableExists($table)) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function ensureDefaultTemplates(): void
    {
        if (!$this->hasTables(['email_templates'])) {
            return;
        }

        if ($this->templateModel->builder()->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($this->defaultTemplates($now) as $template) {
            $this->templateModel->insert($template);
        }
    }

    private function defaultTemplates(string $now): array
    {
        return [
            [
                'id' => $this->uuid(),
                'key' => 'welcome_user',
                'name' => 'Bienvenida',
                'subject' => 'Bienvenido a {{site_name}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Bienvenido</h1><p>Hola {{user_name}}, gracias por sumarte a {{site_name}}.</p><p><a href="{{site_url}}">Entrar a {{site_name}}</a></p></div>',
                'text_body' => "Hola {{user_name}}, gracias por sumarte a {{site_name}}.\n\nEntrar a {{site_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'password_reset',
                'name' => 'Recuperacion de contrasena',
                'subject' => 'Recuperar acceso a {{site_name}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Recuperar contrasena</h1><p>Hola {{user_name}}, recibimos una solicitud para cambiar tu contrasena.</p><p><a href="{{reset_url}}">Crear una nueva contrasena</a></p><p>Este enlace vence el {{expires_at}}.</p></div>',
                'text_body' => "Hola {{user_name}},\n\nUsa este enlace para crear una nueva contrasena:\n{{reset_url}}\n\nVence el {{expires_at}}.",
                'variables_json' => json_encode(['user_name', 'user_email', 'reset_url', 'expires_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'newsletter_subscription',
                'name' => 'Suscripcion newsletter',
                'subject' => 'Tu suscripcion a {{site_name}} esta activa',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Suscripcion confirmada</h1><p>Gracias por suscribirte, {{user_name}}.</p><p>Vas a recibir novedades de {{site_name}} en tu correo.</p><p><a href="{{unsubscribe_url}}">Gestionar baja segura</a></p></div>',
                'text_body' => "Gracias por suscribirte, {{user_name}}.\n\nVas a recibir novedades de {{site_name}} en tu correo.\nBaja segura: {{unsubscribe_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'unsubscribe_url', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'newsletter_news_digest',
                'name' => 'Digest de noticias',
                'subject' => 'Las noticias destacadas de {{site_name}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Resumen de noticias</h1><p>Hola {{user_name}}, estas son las noticias seleccionadas.</p>{{news_list}}<p><a href="{{unsubscribe_url}}">Cancelar suscripcion</a></p></div>',
                'text_body' => "Hola {{user_name}}, estas son las noticias seleccionadas.\n\n{{news_list}}\n\nBaja segura: {{unsubscribe_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'newsletter_title', 'news_list', 'unsubscribe_url', 'portal_url', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'new_survey_available',
                'name' => 'Nueva encuesta disponible',
                'subject' => 'Nueva encuesta disponible en {{site_name}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Nueva encuesta</h1><p>Hola {{user_name}}, ya esta disponible la encuesta <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Responder encuesta</a></p><p>{{survey_initial_message}}</p></div>',
                'text_body' => "Hola {{user_name}}, ya esta disponible la encuesta {{survey_title}}.\n\nResponder encuesta: {{survey_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'survey_title', 'survey_url', 'survey_initial_message', 'survey_ends_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'survey_incomplete_reminder',
                'name' => 'Recordatorio encuesta incompleta',
                'subject' => 'No dejes incompleta la encuesta {{survey_title}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><h1 style="margin:0 0 16px;font-size:24px">Recordatorio</h1><p>Hola {{user_name}}, retomá tu avance en <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Continuar encuesta</a></p></div>',
                'text_body' => "Hola {{user_name}}, retomá tu avance en {{survey_title}}.\n\nContinuar encuesta: {{survey_url}}",
                'variables_json' => json_encode(['user_name', 'user_email', 'survey_title', 'survey_url', 'survey_initial_message', 'survey_ends_at', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $this->uuid(),
                'key' => 'generic_notification',
                'name' => 'Notificacion generica',
                'subject' => '{{site_name}}',
                'html_body' => '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827"><p>{{message}}</p></div>',
                'text_body' => '{{message}}',
                'variables_json' => json_encode(['message', 'site_name', 'site_url', 'current_year'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }

    private function sanitizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = $this->sanitizeText((string) $value);
        return $text !== '' ? $text : null;
    }

    private function sanitizeTemplateHtml(string $value): string
    {
        $value = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $value) ?? $value;
        $value = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $value) ?? $value;
        $value = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $value) ?? $value;
        $value = preg_replace("/\\son[a-z]+\\s*=\\s*'[^']*'/i", '', $value) ?? $value;
        $value = preg_replace('/javascript:/i', '', $value) ?? $value;
        return trim($value);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
