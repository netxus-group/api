<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds required base data: roles, default admin, integration configs, settings.
 */
class InitialSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Roles ----
        $this->db->table('role_profiles')->insertBatch([
            [
                'id'           => '00000000-0000-0000-0000-000000000001',
                'name'         => 'super_admin',
                'display_name' => 'Super Administrador',
                'capabilities' => json_encode([
                    'manage_users', 'manage_roles', 'manage_news', 'publish_news',
                    'manage_categories', 'manage_tags', 'manage_authors', 'manage_media',
                    'manage_ads', 'manage_polls', 'manage_newsletter', 'manage_integrations',
                    'manage_settings', 'view_metrics', 'export_data', 'manage_home_layout',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'           => '00000000-0000-0000-0000-000000000002',
                'name'         => 'editor',
                'display_name' => 'Editor',
                'capabilities' => json_encode([
                    'manage_news', 'publish_news', 'manage_categories', 'manage_tags',
                    'manage_authors', 'manage_media', 'manage_polls', 'view_metrics',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'           => '00000000-0000-0000-0000-000000000003',
                'name'         => 'writer',
                'display_name' => 'Redactor',
                'capabilities' => json_encode(['manage_news', 'manage_media']),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
        ]);

        // ---- Default Admin (password: Admin123!) ----
        $this->db->table('users')->insert([
            'id'             => '10000000-0000-0000-0000-000000000001',
            'email'          => 'admin@netxus.com',
            'password_hash'  => password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 10]),
            'first_name'     => 'Admin',
            'last_name'      => 'Netxus',
            'display_name'   => 'Admin Netxus',
            'active'         => 1,
            'email_verified' => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $this->db->table('user_roles')->insert([
            'id'              => '20000000-0000-0000-0000-000000000001',
            'user_id'         => '10000000-0000-0000-0000-000000000001',
            'role_profile_id' => '00000000-0000-0000-0000-000000000001',
            'created_at'      => $now,
        ]);

        // ---- Integration Configs ----
        $this->db->table('integration_configs')->insertBatch([
            [
                'id'           => '30000000-0000-0000-0000-000000000001',
                'provider'     => 'weather',
                'endpoint'     => 'https://api.open-meteo.com/v1/forecast',
                'ttl'          => '30m',
                'active'       => 1,
                'extra_config' => json_encode(['latitude' => -34.6037, 'longitude' => -58.3816]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'         => '30000000-0000-0000-0000-000000000002',
                'provider'   => 'dollar',
                'endpoint'   => 'https://open.er-api.com/v6/latest/USD',
                'ttl'        => '1h',
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ---- Default Settings ----
        $settings = [
            'site_name'              => '"Netxus Portal News"',
            'site_description'       => '"Portal de noticias profesional"',
            'articles_per_page'      => '12',
            'allow_newsletter_signup' => 'true',
            'default_news_status'    => '"draft"',
        ];

        foreach ($settings as $key => $value) {
            $this->db->table('settings')->insert([
                'id'         => $this->uuid(),
                'key'        => $key,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "✓ Initial seed completed: roles, admin user, integrations, settings\n";
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
