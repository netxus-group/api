<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostStatusHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'news_id'     => ['type' => 'CHAR', 'constraint' => 36],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'to_status'   => ['type' => 'VARCHAR', 'constraint' => 30],
            'changed_by'  => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'comment'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('news_id');
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('changed_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('post_status_history');
    }

    public function down()
    {
        $this->forge->dropTable('post_status_history', true);
    }
}
