<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['key', 'value', 'group'];

    /**
     * Get all settings, optionally by group.
     */
    public function getAll(?string $group = null): array
    {
        $builder = $this;
        if ($group) {
            $builder = $builder->where('group', $group);
        }

        $settings = $builder->findAll();
        $result   = [];

        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }

        return $result;
    }

    /**
     * Set a key-value pair.
     */
    public function set(string $key, string $value, string $group = 'general'): void
    {
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            $this->update($existing['id'], ['value' => $value]);
        } else {
            $this->insert([
                'key'   => $key,
                'value' => $value,
                'group' => $group,
            ]);
        }
    }
}
