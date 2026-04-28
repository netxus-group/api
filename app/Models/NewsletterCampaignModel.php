<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterCampaignModel extends Model
{
    protected $table = 'newsletter_campaigns';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'title',
        'subject',
        'template_key',
        'news_ids_json',
        'audience',
        'status',
        'preview_html',
        'scheduled_at',
        'sent_at',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'preview_html' => '?string',
    ];
}
