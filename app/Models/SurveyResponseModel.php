<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyResponseModel extends Model
{
    protected $table            = 'survey_responses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'id',
        'survey_id',
        'user_id',
        'anonymous_key',
        'status',
        'current_section_id',
        'completed_at',
        'ip_hash',
        'user_agent_hash',
    ];
}
