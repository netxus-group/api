<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEngagementEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'entity_id'   => ['type' => 'CHAR', 'constraint' => 36],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'event_type'  => ['type' => 'VARCHAR', 'constraint' => 30],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['entity_id', 'entity_type']);
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('engagement_events');
    }

    public function down()
    {
        $this->forge->dropTable('engagement_events', true);
    }
}
