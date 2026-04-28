<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;

class CommunicationsController extends BaseApiController
{
    public function config()
    {
        if ($this->isRequestMethod('get')) {
            return ApiResponse::ok(service('communicationService')->getConfig(false));
        }

        if (!$this->isSuperAdmin()) {
            return ApiResponse::forbidden('Only super administrators can update communication settings');
        }

        $payload = $this->getJsonInput();
        return ApiResponse::ok(service('communicationService')->saveConfig($payload), 'Communications configuration updated');
    }

    public function templates(?string $key = null)
    {
        $service = service('communicationService');

        if ($this->isRequestMethod('get')) {
            if ($key !== null && $key !== '') {
                $template = $service->getTemplate($key);
                if (!$template) {
                    return ApiResponse::notFound('Template not found');
                }
                return ApiResponse::ok($template);
            }

            return ApiResponse::ok($service->listTemplates());
        }

        if (!$this->isSuperAdmin() && !$this->hasPermission('newsletter.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        if ($key === null || $key === '') {
            return ApiResponse::badRequest('Template key required');
        }

        $payload = $this->getJsonInput();
        return ApiResponse::ok($service->saveTemplate($key, $payload), 'Template updated');
    }

    public function templateTest(string $key)
    {
        if (!$this->isSuperAdmin() && !$this->hasPermission('newsletter.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        $payload = $this->getJsonInput();
        $to = (string) ($payload['to'] ?? '');
        if ($to === '') {
            return ApiResponse::badRequest('Recipient email required');
        }

        $variables = is_array($payload['variables'] ?? null) ? $payload['variables'] : [];
        $result = service('communicationService')->sendTemplateEmail($to, $key, $variables, [
            'templateKey' => $key,
            'recipient_user_id' => null,
            'subject' => (string) ($payload['subject'] ?? ''),
        ]);

        return ApiResponse::ok($result, 'Template test sent');
    }

    public function testEmail()
    {
        if (!$this->isSuperAdmin()) {
            return ApiResponse::forbidden('Only super administrators can send generic tests');
        }

        $payload = $this->getJsonInput();
        $to = (string) ($payload['to'] ?? '');
        if ($to === '') {
            return ApiResponse::badRequest('Recipient email required');
        }

        $subject = (string) ($payload['subject'] ?? 'Netxus communication test');
        $html = (string) ($payload['html'] ?? '<p>Test email from Netxus.</p>');
        $text = (string) ($payload['text'] ?? strip_tags($html));

        return ApiResponse::ok(service('communicationService')->sendEmail($to, $subject, $html, $text, [
            'templateKey' => 'generic_notification',
            'recipient_user_id' => null,
            'subject' => $subject,
        ]), 'Test email processed');
    }

    public function logs()
    {
        [$page, $perPage] = $this->paginationParams();
        $filters = [
            'channel' => $this->request->getGet('channel'),
            'status' => $this->request->getGet('status'),
            'templateKey' => $this->request->getGet('templateKey'),
            'search' => $this->request->getGet('search'),
        ];

        $payload = service('communicationService')->listLogs($filters, $page, $perPage);
        return ApiResponse::ok($payload['items'], 'Communication logs', $payload['meta']);
    }

    public function campaigns()
    {
        if (!$this->isSuperAdmin() && !$this->hasPermission('newsletter.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        return ApiResponse::ok(service('communicationService')->listCampaigns());
    }

    public function newsletterSendTest()
    {
        if (!$this->isSuperAdmin() && !$this->hasPermission('newsletter.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        $payload = $this->getJsonInput();
        $to = (string) ($payload['to'] ?? '');
        if ($to === '') {
            return ApiResponse::badRequest('Recipient email required');
        }

        $result = service('communicationService')->sendNewsletterCampaign([
            'title' => (string) ($payload['title'] ?? 'Newsletter'),
            'subject' => (string) ($payload['subject'] ?? ''),
            'templateKey' => (string) ($payload['templateKey'] ?? 'newsletter_news_digest'),
            'newsIds' => (array) ($payload['newsIds'] ?? []),
            'audience' => 'test',
            'createdBy' => $this->userId(),
        ], true, $to);

        return ApiResponse::ok($result, 'Newsletter test processed');
    }

    public function newsletterSend()
    {
        if (!$this->isSuperAdmin() && !$this->hasPermission('newsletter.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        $payload = $this->getJsonInput();
        $result = service('communicationService')->sendNewsletterCampaign([
            'title' => (string) ($payload['title'] ?? 'Newsletter'),
            'subject' => (string) ($payload['subject'] ?? ''),
            'templateKey' => (string) ($payload['templateKey'] ?? 'newsletter_news_digest'),
            'newsIds' => (array) ($payload['newsIds'] ?? []),
            'audience' => (string) ($payload['audience'] ?? 'active_subscribers'),
            'createdBy' => $this->userId(),
        ], false, (string) ($payload['testEmail'] ?? ''));

        return ApiResponse::ok($result, 'Newsletter campaign processed');
    }

    public function runSurveyReminders()
    {
        if (!$this->isSuperAdmin() && !$this->hasPermission('surveys.manage')) {
            return ApiResponse::forbidden('Not enough permissions');
        }

        $days = max(1, (int) ($this->request->getGet('days') ?? 3));
        $maxReminders = max(1, (int) ($this->request->getGet('maxReminders') ?? 1));
        $surveyId = trim((string) ($this->request->getGet('surveyId') ?? ''));

        return ApiResponse::ok(service('surveyService')->runSurveyReminders($days, $maxReminders, $surveyId !== '' ? $surveyId : null));
    }
}
