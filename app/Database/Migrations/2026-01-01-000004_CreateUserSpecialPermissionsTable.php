<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserSpecialPermissionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'user_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'permission' => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'permission']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_special_permissions');
    }

    public function down()
    {
        $this->forge->dropTable('user_special_permissions', true);
    }
}
