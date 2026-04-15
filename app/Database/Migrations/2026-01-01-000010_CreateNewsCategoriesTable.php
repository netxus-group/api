<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsCategoriesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'news_id'     => ['type' => 'CHAR', 'constraint' => 36],
            'category_id' => ['type' => 'CHAR', 'constraint' => 36],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['news_id', 'category_id']);
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('news_categories');
    }

    public function down()
    {
        $this->forge->dropTable('news_categories', true);
    }
}
