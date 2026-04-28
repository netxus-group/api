<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyAnswerModel extends Model
{
    protected $table            = 'survey_answers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'id',
        'survey_response_id',
        'survey_id',
        'section_id',
        'question_id',
        'value_text',
        'value_json',
    ];
}
