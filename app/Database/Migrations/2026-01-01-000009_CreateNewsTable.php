<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNewsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'CHAR', 'constraint' => 36],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 500],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 550],
            'subtitle'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'excerpt'          => ['type' => 'TEXT', 'null' => true],
            'body'             => ['type' => 'LONGTEXT', 'null' => true],
            'cover_image_url'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'author_id'        => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['draft', 'in_review', 'approved', 'scheduled', 'published', 'archived'], 'default' => 'draft'],
            'featured'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'breaking'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'source_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'source_name'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'seo_title'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'seo_description'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'seo_keywords'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'published_at'     => ['type' => 'DATETIME', 'null' => true],
            'scheduled_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_by'       => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'reviewed_by'      => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'view_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'share_count'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('status');
        $this->forge->addKey('author_id');
        $this->forge->addKey('created_by');
        $this->forge->addKey('published_at');
        $this->forge->addKey('scheduled_at');
        $this->forge->addForeignKey('author_id', 'authors', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('news');
    }

    public function down()
    {
        $this->forge->dropTable('news', true);
    }
}
