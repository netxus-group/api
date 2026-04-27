<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserPasswordResetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'user_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 128],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash', 'uk_user_password_resets_hash');
        $this->forge->addKey('user_id', false, false, 'idx_user_password_resets_user');
        $this->forge->addKey('expires_at', false, false, 'idx_user_password_resets_expires_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_user_password_resets_user');
        $this->forge->createTable('user_password_resets');
    }

    public function down()
    {
        $this->forge->dropTable('user_password_resets', true);
    }
}
