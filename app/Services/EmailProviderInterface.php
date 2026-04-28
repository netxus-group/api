<?php

namespace App\Services;

interface EmailProviderInterface
{
    /**
     * @return array{status:string,provider:string,message:string,externalId?:string|null}
     */
    public function send(string $to, string $subject, string $html, string $text = '', array $metadata = []): array;
}
