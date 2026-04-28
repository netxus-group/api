<?php

namespace App\Models;

use CodeIgniter\Model;

class CommunicationLogModel extends Model
{
    protected $table = 'communication_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'channel',
        'provider',
        'template_key',
        'recipient_email',
        'recipient_user_id',
        'subject',
        'status',
        'error_message',
        'metadata_json',
        'sent_at',
        'created_at',
        'updated_at',
    ];
}
