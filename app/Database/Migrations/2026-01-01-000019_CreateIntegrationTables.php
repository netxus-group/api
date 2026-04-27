<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIntegrationTables extends Migration
{
    public function up()
    {
        // Integration Config
        $this->forge->addField([
            'id'           => ['type' => 'CHAR', 'constraint' => 36],
            'provider'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'api_key'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'endpoint'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ttl'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '1h'],
            'active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'extra_config' => ['type' => 'JSON', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('provider');
        $this->forge->createTable('integration_configs');

        // Integration Snapshots
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'provider'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'data'       => ['type' => 'JSON', 'null' => true],
            'fetched_at' => ['type' => 'DATETIME'],
            'is_fallback' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('provider');
        $this->forge->createTable('integration_snapshots');

        // Seed default integrations
        $now = date('Y-m-d H:i:s');
        $this->db->table('integration_configs')->insertBatch([
            [
                'id'       => bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . substr(bin2hex(random_bytes(2)), 1) . '-a' . substr(bin2hex(random_bytes(2)), 1) . '-' . bin2hex(random_bytes(6)),
                'provider' => 'weather',
                'api_key'  => null,
                'endpoint' => 'https://api.open-meteo.com/v1/forecast',
                'ttl'      => '1h',
                'active'   => 1,
                'extra_config' => json_encode(['latitude' => -34.6037, 'longitude' => -58.3816]),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'       => bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . substr(bin2hex(random_bytes(2)), 1) . '-a' . substr(bin2hex(random_bytes(2)), 1) . '-' . bin2hex(random_bytes(6)),
                'provider' => 'dollar',
                'api_key'  => null,
                'endpoint' => 'https://criptoya.com/api/dolar',
                'ttl'      => '1h',
                'active'   => 1,
                'extra_config' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('integration_snapshots', true);
        $this->forge->dropTable('integration_configs', true);
    }
}
