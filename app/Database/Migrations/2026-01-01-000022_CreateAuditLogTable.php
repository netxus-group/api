<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'user_id'     => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_id'   => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'old_values'  => ['type' => 'JSON', 'null' => true],
            'new_values'  => ['type' => 'JSON', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_log');
    }

    public function down()
    {
        $this->forge->dropTable('audit_log', true);
    }
}
