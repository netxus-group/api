<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveySectionModel extends Model
{
    protected $table            = 'survey_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'id',
        'survey_id',
        'title',
        'description',
        'sort_order',
    ];
}
