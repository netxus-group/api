<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdImpressionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'ad_slot_id' => ['type' => 'CHAR', 'constraint' => 36],
            'event_type' => ['type' => 'ENUM', 'constraint' => ['impression', 'click'], 'default' => 'impression'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('ad_slot_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('ad_slot_id', 'ad_slots', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ad_impressions');
    }

    public function down()
    {
        $this->forge->dropTable('ad_impressions', true);
    }
}
