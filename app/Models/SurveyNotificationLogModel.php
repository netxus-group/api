<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyNotificationLogModel extends Model
{
    protected $table = 'survey_notification_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'survey_id',
        'recipient_user_id',
        'recipient_email',
        'notification_type',
        'sent_count',
        'last_sent_at',
        'status',
        'error_message',
        'metadata_json',
        'created_at',
        'updated_at',
    ];
}
