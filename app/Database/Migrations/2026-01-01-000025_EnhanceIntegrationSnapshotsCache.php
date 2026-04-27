<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceIntegrationSnapshotsCache extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('integration_snapshots')) {
            return;
        }

        $fields = $this->db->getFieldNames('integration_snapshots');
        $toAdd = [];

        if (!in_array('integration_key', $fields, true)) {
            $toAdd['integration_key'] = ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'provider'];
        }
        if (!in_array('payload', $fields, true)) {
            $toAdd['payload'] = ['type' => 'JSON', 'null' => true, 'after' => 'integration_key'];
        }
        if (!in_array('ttl_seconds', $fields, true)) {
            $toAdd['ttl_seconds'] = ['type' => 'INT', 'constraint' => 11, 'default' => 3600, 'after' => 'fetched_at'];
        }
        if (!in_array('expires_at', $fields, true)) {
            $toAdd['expires_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'ttl_seconds'];
        }
        if (!in_array('status', $fields, true)) {
            $toAdd['status'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ok', 'after' => 'expires_at'];
        }
        if (!in_array('error_message', $fields, true)) {
            $toAdd['error_message'] = ['type' => 'TEXT', 'null' => true, 'after' => 'status'];
        }
        if (!in_array('refresh_lock_until', $fields, true)) {
            $toAdd['refresh_lock_until'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'error_message'];
        }

        if ($toAdd !== []) {
            $this->forge->addColumn('integration_snapshots', $toAdd);
        }

        $fieldsAfter = $this->db->getFieldNames('integration_snapshots');
        if (in_array('integration_key', $fieldsAfter, true)) {
            $this->db->query('UPDATE integration_snapshots SET integration_key = provider WHERE integration_key IS NULL OR integration_key = ""');

            $indexExists = false;
            $indexes = $this->db->query('SHOW INDEX FROM integration_snapshots')->getResultArray();
            foreach ($indexes as $index) {
                if (($index['Key_name'] ?? '') === 'idx_integration_key') {
                    $indexExists = true;
                    break;
                }
            }
            if (!$indexExists) {
                $this->db->query('CREATE INDEX idx_integration_key ON integration_snapshots (integration_key)');
            }
        }

        if (in_array('expires_at', $fieldsAfter, true)) {
            $this->db->query('UPDATE integration_snapshots SET expires_at = DATE_ADD(fetched_at, INTERVAL IFNULL(ttl_seconds, 3600) SECOND) WHERE expires_at IS NULL');
        }

        if (in_array('status', $fieldsAfter, true)) {
            $this->db->query("UPDATE integration_snapshots SET status = 'ok' WHERE status IS NULL OR status = ''");
        }
    }

    public function down()
    {
        // Non-destructive rollback for shared hosting compatibility.
    }
}
