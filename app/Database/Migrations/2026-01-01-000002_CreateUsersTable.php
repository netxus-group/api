<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'CHAR', 'constraint' => 36],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'password_hash'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'last_name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'display_name'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'avatar_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'bio'             => ['type' => 'TEXT', 'null' => true],
            'active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'email_verified'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_login_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}
