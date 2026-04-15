<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsletterSubscribersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'CHAR', 'constraint' => 36],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'status'             => ['type' => 'ENUM', 'constraint' => ['pending', 'active', 'unsubscribed'], 'default' => 'pending'],
            'confirmation_token' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'confirmed_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('status');
        $this->forge->createTable('newsletter_subscribers');
    }

    public function down()
    {
        $this->forge->dropTable('newsletter_subscribers', true);
    }
}
