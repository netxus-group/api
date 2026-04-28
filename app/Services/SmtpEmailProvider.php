<?php

namespace App\Services;

use Config\Communications;

class SmtpEmailProvider implements EmailProviderInterface
{
    public function __construct(private readonly Communications $config)
    {
    }

    public function send(string $to, string $subject, string $html, string $text = '', array $metadata = []): array
    {
        $email = service('email');
        $email->clear(true);

        $email->initialize([
            'protocol' => 'smtp',
            'SMTPHost' => $this->config->smtpHost,
            'SMTPUser' => $this->config->smtpUser,
            'SMTPPass' => $this->config->smtpPass,
            'SMTPPort' => $this->config->smtpPort,
            'SMTPCrypto' => $this->config->smtpEncryption,
            'mailType' => 'html',
            'charset' => 'UTF-8',
            'wordWrap' => true,
            'newline' => "\r\n",
            'CRLF' => "\r\n",
            'validate' => false,
        ]);

        $email->setFrom($this->config->mailFromAddress, $this->config->mailFromName);
        if ($this->config->mailReplyTo !== '') {
            $email->setReplyTo($this->config->mailReplyTo);
        }
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($html);
        if ($text !== '') {
            $email->setAltMessage($text);
        }

        if ($email->send(false)) {
            return [
                'status' => 'sent',
                'provider' => 'smtp',
                'message' => 'Email sent successfully',
                'externalId' => null,
            ];
        }

        $debug = trim($email->printDebugger(['headers', 'subject', 'body']));
        return [
            'status' => 'failed',
            'provider' => 'smtp',
            'message' => $debug !== '' ? $debug : 'SMTP delivery failed',
            'externalId' => null,
        ];
    }
}
