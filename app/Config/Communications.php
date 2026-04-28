<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Communications extends BaseConfig
{
    public string $mailProvider = 'smtp';

    public bool $mailSendEnabled = false;

    public bool $mailTestMode = false;

    public string $mailFromAddress = '';

    public string $mailFromName = 'Netxus';

    public string $mailReplyTo = '';

    public string $smtpHost = '';

    public int $smtpPort = 587;

    public string $smtpEncryption = 'tls';

    public string $smtpUser = '';

    public string $smtpPass = '';

    public string $envialoSimpleApiKey = '';

    public string $envialoSimpleAccountId = '';

    public string $pushProvider = 'none';

    public string $pushApiKey = '';

    public string $pushAppId = '';

    public string $dashboardUrl = '';

    public string $portalUrl = '';

    public string $siteName = 'Netxus';

    public string $testEmail = '';

    public function __construct()
    {
        parent::__construct();

        $this->mailProvider = (string) env('MAIL_PROVIDER', $this->mailProvider);
        $this->mailSendEnabled = filter_var(env('MAIL_SEND_ENABLED', $this->mailSendEnabled ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
        $this->mailTestMode = filter_var(env('MAIL_TEST_MODE', $this->mailTestMode ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
        $this->mailFromAddress = (string) env('MAIL_FROM_ADDRESS', $this->mailFromAddress);
        $this->mailFromName = (string) env('MAIL_FROM_NAME', $this->mailFromName);
        $this->mailReplyTo = (string) env('MAIL_REPLY_TO', $this->mailReplyTo);
        $this->smtpHost = (string) env('SMTP_HOST', $this->smtpHost);
        $this->smtpPort = (int) env('SMTP_PORT', (string) $this->smtpPort);
        $this->smtpEncryption = (string) env('SMTP_ENCRYPTION', $this->smtpEncryption);
        $this->smtpUser = (string) env('SMTP_USER', $this->smtpUser);
        $this->smtpPass = (string) env('SMTP_PASS', $this->smtpPass);
        $this->envialoSimpleApiKey = (string) env('ENVIALO_SIMPLE_API_KEY', $this->envialoSimpleApiKey);
        $this->envialoSimpleAccountId = (string) env('ENVIALO_SIMPLE_ACCOUNT_ID', $this->envialoSimpleAccountId);
        $this->pushProvider = (string) env('PUSH_PROVIDER', $this->pushProvider);
        $this->pushApiKey = (string) env('PUSH_API_KEY', $this->pushApiKey);
        $this->pushAppId = (string) env('PUSH_APP_ID', $this->pushAppId);
        $defaultDashboardUrl = ENVIRONMENT === 'production' ? 'https://admin.netxus.com.ar' : 'http://localhost:3000';
        $defaultPortalUrl = ENVIRONMENT === 'production' ? 'https://netxus.com.ar' : 'http://localhost:5173';

        $this->dashboardUrl = rtrim((string) env('DASHBOARD_SITE_URL', $defaultDashboardUrl), '/');
        $this->portalUrl = rtrim((string) env('PUBLIC_SITE_URL', $defaultPortalUrl), '/');
        $this->siteName = (string) env('SITE_NAME', $this->siteName);
        $this->testEmail = (string) env('MAIL_TEST_EMAIL', $this->testEmail);
    }
}
