<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePortalNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'event_key' => ['type' => 'VARCHAR', 'constraint' => 190],
            'type' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'body' => ['type' => 'TEXT', 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'metadata_json' => ['type' => 'JSON', 'null' => true],
            'is_read' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'event_key'], 'uk_portal_user_notifications_event');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_notifications_user');
        $this->forge->addKey('is_read', false, false, 'idx_portal_user_notifications_read');
        $this->forge->addKey('created_at', false, false, 'idx_portal_user_notifications_created_at');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_notifications_user');
        $this->forge->createTable('portal_user_notifications');
    }

    public function down()
    {
        $this->forge->dropTable('portal_user_notifications', true);
    }
}
