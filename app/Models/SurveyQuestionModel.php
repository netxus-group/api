<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyQuestionModel extends Model
{
    protected $table            = 'survey_questions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'id',
        'survey_id',
        'section_id',
        'question_text',
        'help_text',
        'type',
        'is_required',
        'sort_order',
        'config',
    ];

    protected array $casts = [
        'is_required' => 'boolean',
    ];
}
