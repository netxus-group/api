<?php

namespace App\Models;

use CodeIgniter\Model;

class PollQuestionModel extends Model
{
    protected $table         = 'poll_questions';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id', 'poll_id', 'question_text', 'question_type',
        'required', 'sort_order',
    ];
}
