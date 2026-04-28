<?php

namespace App\Models;

use CodeIgniter\Model;

class SurveyModel extends Model
{
    protected $table            = 'surveys';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement  = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'id',
        'title',
        'slug',
        'description',
        'initial_message',
        'final_message',
        'status',
        'starts_at',
        'ends_at',
        'requires_login',
        'allow_back_navigation',
        'questions_per_view',
        'notify_on_publish',
        'notify_active_users',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'requires_login' => 'boolean',
        'allow_back_navigation' => 'boolean',
        'notify_on_publish' => 'boolean',
        'notify_active_users' => 'boolean',
    ];
}
