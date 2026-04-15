<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaImagesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'filename'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'size'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'width'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'height'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'url'         => ['type' => 'VARCHAR', 'constraint' => 500],
            'alt_text'    => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'caption'     => ['type' => 'TEXT', 'null' => true],
            'folder'      => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'general'],
            'uploaded_by' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('uploaded_by');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('media_images');
    }

    public function down()
    {
        $this->forge->dropTable('media_images', true);
    }
}
