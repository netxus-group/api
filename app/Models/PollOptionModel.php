<?php

namespace App\Models;

use CodeIgniter\Model;

class PollOptionModel extends Model
{
    protected $table         = 'poll_options';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id', 'question_id', 'option_text', 'sort_order',
    ];
}
