<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdSlotsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'placement'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'type'       => ['type' => 'ENUM', 'constraint' => ['image', 'html', 'adsense', 'script'], 'default' => 'image'],
            'content'    => ['type' => 'JSON', 'null' => true],
            'target_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'starts_at'  => ['type' => 'DATETIME', 'null' => true],
            'ends_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('placement');
        $this->forge->addKey('active');
        $this->forge->createTable('ad_slots');
    }

    public function down()
    {
        $this->forge->dropTable('ad_slots', true);
    }
}
