<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailTemplateModel extends Model
{
    protected $table = 'email_templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id',
        'key',
        'name',
        'subject',
        'html_body',
        'text_body',
        'variables_json',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
    ];

    public function findByKey(string $key): ?array
    {
        return $this->where('key', $key)->first();
    }
}
