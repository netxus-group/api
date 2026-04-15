<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePollsTable extends Migration
{
    public function up()
    {
        // Polls
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 300],
            'description' => ['type' => 'TEXT', 'null' => true],
            'active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'starts_at'   => ['type' => 'DATETIME', 'null' => true],
            'ends_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_by'  => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('polls');

        // Poll Questions
        $this->forge->addField([
            'id'         => ['type' => 'CHAR', 'constraint' => 36],
            'poll_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'text'       => ['type' => 'VARCHAR', 'constraint' => 500],
            'type'       => ['type' => 'ENUM', 'constraint' => ['single', 'multiple', 'text'], 'default' => 'single'],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'required'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('poll_id');
        $this->forge->addForeignKey('poll_id', 'polls', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('poll_questions');

        // Poll Options
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'question_id' => ['type' => 'CHAR', 'constraint' => 36],
            'text'        => ['type' => 'VARCHAR', 'constraint' => 300],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('question_id');
        $this->forge->addForeignKey('question_id', 'poll_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('poll_options');

        // Poll Responses
        $this->forge->addField([
            'id'            => ['type' => 'CHAR', 'constraint' => 36],
            'poll_id'       => ['type' => 'CHAR', 'constraint' => 36],
            'respondent_id' => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['poll_id', 'respondent_id']);
        $this->forge->addForeignKey('poll_id', 'polls', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('poll_responses');

        // Poll Response Details
        $this->forge->addField([
            'id'          => ['type' => 'CHAR', 'constraint' => 36],
            'response_id' => ['type' => 'CHAR', 'constraint' => 36],
            'question_id' => ['type' => 'CHAR', 'constraint' => 36],
            'option_id'   => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'text_answer' => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('response_id');
        $this->forge->addKey('question_id');
        $this->forge->addForeignKey('response_id', 'poll_responses', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'poll_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('option_id', 'poll_options', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('poll_response_details');
    }

    public function down()
    {
        $this->forge->dropTable('poll_response_details', true);
        $this->forge->dropTable('poll_responses', true);
        $this->forge->dropTable('poll_options', true);
        $this->forge->dropTable('poll_questions', true);
        $this->forge->dropTable('polls', true);
    }
}
