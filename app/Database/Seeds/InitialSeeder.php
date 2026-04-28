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
        $roles = [
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
        ];
        foreach ($roles as $role) {
            $exists = $this->db->table('role_profiles')->where('id', $role['id'])->countAllResults() > 0;
            if ($exists) {
                $this->db->table('role_profiles')
                    ->where('id', $role['id'])
                    ->update([
                        'name'         => $role['name'],
                        'display_name' => $role['display_name'],
                        'capabilities' => $role['capabilities'],
                        'updated_at'   => $now,
                    ]);
                continue;
            }
            $this->db->table('role_profiles')->insert($role);
        }

        // ---- Default Admin (password: Admin123!) ----
        $admin = [
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
        ];
        $adminExists = $this->db->table('users')->where('email', $admin['email'])->countAllResults() > 0;
        if ($adminExists) {
            $this->db->table('users')
                ->where('email', $admin['email'])
                ->update([
                    'first_name'     => $admin['first_name'],
                    'last_name'      => $admin['last_name'],
                    'display_name'   => $admin['display_name'],
                    'active'         => $admin['active'],
                    'email_verified' => $admin['email_verified'],
                    'updated_at'     => $now,
                ]);
            $adminId = (string) ($this->db->table('users')->select('id')->where('email', $admin['email'])->get()->getRow('id'));
        } else {
            $this->db->table('users')->insert($admin);
            $adminId = $admin['id'];
        }

        $adminRoleExists = $this->db->table('user_roles')
            ->where('user_id', $adminId)
            ->where('role_profile_id', '00000000-0000-0000-0000-000000000001')
            ->countAllResults() > 0;
        if (! $adminRoleExists) {
            $this->db->table('user_roles')->insert([
                'id'              => '20000000-0000-0000-0000-000000000001',
                'user_id'         => $adminId,
                'role_profile_id' => '00000000-0000-0000-0000-000000000001',
                'created_at'      => $now,
            ]);
        }

        // ---- Integration Configs ----
        $integrations = [
            [
                'id'           => '30000000-0000-0000-0000-000000000001',
                'provider'     => 'weather',
                'api_key'      => null,
                'endpoint'     => 'https://api.open-meteo.com/v1/forecast',
                'ttl'          => '1h',
                'active'       => 1,
                'extra_config' => json_encode(['latitude' => -34.6037, 'longitude' => -58.3816]),
                'created_at'   => $now,
                'updated_at'   => $now,
            ],
            [
                'id'         => '30000000-0000-0000-0000-000000000002',
                'provider'   => 'dollar',
                'endpoint'   => 'https://criptoya.com/api/dolar',
                'ttl'        => '1h',
                'active'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        foreach ($integrations as $integration) {
            $exists = $this->db->table('integration_configs')
                ->where('provider', $integration['provider'])
                ->countAllResults() > 0;
            if ($exists) {
                $this->db->table('integration_configs')
                    ->where('provider', $integration['provider'])
                    ->update([
                        'endpoint'     => $integration['endpoint'],
                        'ttl'          => $integration['ttl'],
                        'active'       => $integration['active'],
                        'extra_config' => $integration['extra_config'] ?? null,
                        'updated_at'   => $now,
                    ]);
                continue;
            }
            $this->db->table('integration_configs')->insert($integration);
        }

        // ---- Default Settings ----
        $settings = [
            'site_name'              => '"Netxus Portal News"',
            'site_description'       => '"Portal de noticias profesional"',
            'articles_per_page'      => '12',
            'allow_newsletter_signup' => 'true',
            'default_news_status'    => '"draft"',
        ];

        foreach ($settings as $key => $value) {
            $exists = $this->db->table('settings')->where('key', $key)->countAllResults() > 0;
            if ($exists) {
                $this->db->table('settings')
                    ->where('key', $key)
                    ->update([
                        'value'      => $value,
                        'updated_at' => $now,
                    ]);
                continue;
            }

            $this->db->table('settings')->insert([
                'id'         => $this->uuid(),
                'key'        => $key,
                'value'      => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->call('CommunicationsSeeder');

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
