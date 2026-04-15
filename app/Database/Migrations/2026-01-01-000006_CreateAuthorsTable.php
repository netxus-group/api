<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthorsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 250],
            'bio'        => ['type' => 'TEXT', 'null' => true],
            'avatar_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'social'     => ['type' => 'JSON', 'null' => true],
            'active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('authors');
    }

    public function down()
    {
        $this->forge->dropTable('authors', true);
    }
}
