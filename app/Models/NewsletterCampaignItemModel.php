<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterCampaignItemModel extends Model
{
    protected $table = 'newsletter_campaign_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'campaign_id',
        'news_id',
        'sort_order',
        'created_at',
        'updated_at',
    ];
}
