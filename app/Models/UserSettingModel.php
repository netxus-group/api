<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettingModel extends Model
{
    protected $table         = 'user_settings';
    protected $primaryKey    = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'user_id', 'theme', 'notifications',
    ];

    protected $casts = [
        'notifications' => 'json-array',
    ];

    /**
     * Get or create default settings for a user.
     */
    public function getOrDefault(string $userId): array
    {
        $settings = $this->find($userId);

        if (!$settings) {
            return [
                'user_id'       => $userId,
                'theme'         => 'system',
                'notifications' => [
                    'email'            => true,
                    'browser'          => true,
                    'newsletterDigest' => false,
                ],
            ];
        }

        return $settings;
    }

    /**
     * Upsert user settings.
     */
    public function upsert(string $userId, array $data): void
    {
        $existing = $this->find($userId);

        $data['user_id'] = $userId;
        if (isset($data['notifications']) && is_array($data['notifications'])) {
            $data['notifications'] = json_encode($data['notifications']);
        }

        if ($existing) {
            $this->update($userId, $data);
        } else {
            $this->insert($data);
        }
    }
}
