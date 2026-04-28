<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyQuestionOptionModel extends Model
{
    protected $table            = 'survey_question_options';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'id',
        'question_id',
        'label',
        'value',
        'sort_order',
    ];
}
