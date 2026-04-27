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
     * Set a key-value pair for global settings.
     *
     * Keeps CI4 Model::set() compatibility for query-builder usage.
     *
     * @param array|object|string               $key
     * @param bool|float|int|object|string|null $value
     * @param bool|string|null                  $escape
     *
     * @return $this
     */
    public function set($key, $value = '', $escape = null)
    {
        // Preserve builder behavior when called like CodeIgniter\Model::set().
        if (is_array($key) || is_object($key) || (is_bool($escape) || $escape === null) && !is_string($key)) {
            return parent::set($key, $value, is_bool($escape) || $escape === null ? $escape : null);
        }

        if (!is_string($key)) {
            return parent::set($key, $value, is_bool($escape) || $escape === null ? $escape : null);
        }

        $group    = is_string($escape) && $escape !== '' ? $escape : 'general';
        $existing = $this->where('key', $key)->first();
        $val      = is_string($value) ? $value : (string) $value;

        if ($existing) {
            $this->update($existing['id'], ['value' => $val]);
        } else {
            $this->insert([
                'key'   => $key,
                'value' => $val,
                'group' => $group,
            ]);
        }

        return $this;
    }
}
