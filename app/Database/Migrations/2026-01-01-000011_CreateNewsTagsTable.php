<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsTagsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'      => ['type' => 'CHAR', 'constraint' => 36],
            'news_id' => ['type' => 'CHAR', 'constraint' => 36],
            'tag_id'  => ['type' => 'CHAR', 'constraint' => 36],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['news_id', 'tag_id']);
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tag_id', 'tags', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('news_tags');
    }

    public function down()
    {
        $this->forge->dropTable('news_tags', true);
    }
}
