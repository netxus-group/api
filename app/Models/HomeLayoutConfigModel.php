<?php

namespace App\Models;

use CodeIgniter\Model;

class HomeLayoutConfigModel extends Model
{
    protected $table         = 'home_layout_configs';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['id', 'key', 'value'];

    protected $casts = [
        'value' => 'json-array',
    ];

    /**
     * Get layout config by key.
     */
    public function getByKey(string $key = 'landing_home'): ?array
    {
        $row = $this->where('key', $key)->first();
        return $row ? $row['value'] : $this->defaultLayout();
    }

    /**
     * Update or insert layout config.
     */
    public function upsertByKey(string $key, array $value): void
    {
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            $this->update($existing['id'], ['value' => json_encode($value)]);
        } else {
            $this->insert([
                'id'    => $this->generateUuid(),
                'key'   => $key,
                'value' => json_encode($value),
            ]);
        }
    }

    /**
     * Default layout structure.
     */
    public function defaultLayout(): array
    {
        return [
            'marquee' => [
                'label'              => 'Último Momento',
                'topics'             => [],
                'showQuickHeadlines' => true,
            ],
            'hero' => [
                'spotlightLabel'  => 'Destacada',
                'secondaryCount'  => 2,
                'latestLimit'     => 6,
            ],
            'ads' => [
                'inlineEvery' => 5,
            ],
            'modules' => [],
        ];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }
}
