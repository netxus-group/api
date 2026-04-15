<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoleProfilesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'CHAR', 'constraint' => 36],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'capabilities' => ['type' => 'JSON', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('role_profiles');

        // Seed default roles
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
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'           => '00000000-0000-0000-0000-000000000002',
                'name'         => 'editor',
                'display_name' => 'Editor',
                'capabilities' => json_encode([
                    'manage_news', 'publish_news', 'manage_categories', 'manage_tags',
                    'manage_authors', 'manage_media', 'manage_polls', 'view_metrics',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id'           => '00000000-0000-0000-0000-000000000003',
                'name'         => 'writer',
                'display_name' => 'Redactor',
                'capabilities' => json_encode([
                    'manage_news', 'manage_media',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('role_profiles', true);
    }
}
