<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSurveysTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'title' => ['type' => 'VARCHAR', 'constraint' => 300],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 180],
            'description' => ['type' => 'TEXT', 'null' => true],
            'initial_message' => ['type' => 'TEXT', 'null' => true],
            'final_message' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'paused', 'closed'], 'default' => 'draft'],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'requires_login' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'allow_back_navigation' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'questions_per_view' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_by' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'updated_by' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug', 'uk_surveys_slug');
        $this->forge->addKey('status', false, false, 'idx_surveys_status');
        $this->forge->addKey('starts_at', false, false, 'idx_surveys_starts_at');
        $this->forge->addKey('ends_at', false, false, 'idx_surveys_ends_at');
        $this->forge->addKey('deleted_at', false, false, 'idx_surveys_deleted_at');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL', 'fk_surveys_created_by');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL', 'fk_surveys_updated_by');
        $this->forge->createTable('surveys');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_id' => ['type' => 'CHAR', 'constraint' => 36],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('survey_id', false, false, 'idx_survey_sections_survey');
        $this->forge->addKey(['survey_id', 'sort_order'], false, false, 'idx_survey_sections_order');
        $this->forge->addForeignKey('survey_id', 'surveys', 'id', 'CASCADE', 'CASCADE', 'fk_survey_sections_survey');
        $this->forge->createTable('survey_sections');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_id' => ['type' => 'CHAR', 'constraint' => 36],
            'section_id' => ['type' => 'CHAR', 'constraint' => 36],
            'question_text' => ['type' => 'VARCHAR', 'constraint' => 500],
            'help_text' => ['type' => 'TEXT', 'null' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['short_text', 'long_text', 'single_choice', 'multiple_choice', 'dropdown', 'numeric_scale', 'date'], 'default' => 'short_text'],
            'is_required' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'config' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('survey_id', false, false, 'idx_survey_questions_survey');
        $this->forge->addKey('section_id', false, false, 'idx_survey_questions_section');
        $this->forge->addKey(['section_id', 'sort_order'], false, false, 'idx_survey_questions_order');
        $this->forge->addForeignKey('survey_id', 'surveys', 'id', 'CASCADE', 'CASCADE', 'fk_survey_questions_survey');
        $this->forge->addForeignKey('section_id', 'survey_sections', 'id', 'CASCADE', 'CASCADE', 'fk_survey_questions_section');
        $this->forge->createTable('survey_questions');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'question_id' => ['type' => 'CHAR', 'constraint' => 36],
            'label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'value' => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('question_id', false, false, 'idx_survey_question_options_question');
        $this->forge->addKey(['question_id', 'sort_order'], false, false, 'idx_survey_question_options_order');
        $this->forge->addForeignKey('question_id', 'survey_questions', 'id', 'CASCADE', 'CASCADE', 'fk_survey_question_options_question');
        $this->forge->createTable('survey_question_options');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_id' => ['type' => 'CHAR', 'constraint' => 36],
            'user_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'anonymous_key' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['in_progress', 'completed'], 'default' => 'in_progress'],
            'current_section_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'ip_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'user_agent_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['survey_id', 'user_id'], 'uk_survey_responses_user');
        $this->forge->addUniqueKey(['survey_id', 'anonymous_key'], 'uk_survey_responses_anon');
        $this->forge->addKey('survey_id', false, false, 'idx_survey_responses_survey');
        $this->forge->addKey('status', false, false, 'idx_survey_responses_status');
        $this->forge->addKey('completed_at', false, false, 'idx_survey_responses_completed_at');
        $this->forge->addKey('current_section_id', false, false, 'idx_survey_responses_current_section');
        $this->forge->addKey('ip_hash', false, false, 'idx_survey_responses_ip_hash');
        $this->forge->addKey('user_agent_hash', false, false, 'idx_survey_responses_user_agent_hash');
        $this->forge->addForeignKey('survey_id', 'surveys', 'id', 'CASCADE', 'CASCADE', 'fk_survey_responses_survey');
        $this->forge->addForeignKey('user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_survey_responses_user');
        $this->forge->addForeignKey('current_section_id', 'survey_sections', 'id', 'SET NULL', 'SET NULL', 'fk_survey_responses_current_section');
        $this->forge->createTable('survey_responses');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_response_id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_id' => ['type' => 'CHAR', 'constraint' => 36],
            'section_id' => ['type' => 'CHAR', 'constraint' => 36],
            'question_id' => ['type' => 'CHAR', 'constraint' => 36],
            'value_text' => ['type' => 'TEXT', 'null' => true],
            'value_json' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['survey_response_id', 'question_id'], 'uk_survey_answers_response_question');
        $this->forge->addKey('survey_response_id', false, false, 'idx_survey_answers_response');
        $this->forge->addKey('survey_id', false, false, 'idx_survey_answers_survey');
        $this->forge->addKey('section_id', false, false, 'idx_survey_answers_section');
        $this->forge->addKey('question_id', false, false, 'idx_survey_answers_question');
        $this->forge->addForeignKey('survey_response_id', 'survey_responses', 'id', 'CASCADE', 'CASCADE', 'fk_survey_answers_response');
        $this->forge->addForeignKey('survey_id', 'surveys', 'id', 'CASCADE', 'CASCADE', 'fk_survey_answers_survey');
        $this->forge->addForeignKey('section_id', 'survey_sections', 'id', 'CASCADE', 'CASCADE', 'fk_survey_answers_section');
        $this->forge->addForeignKey('question_id', 'survey_questions', 'id', 'CASCADE', 'CASCADE', 'fk_survey_answers_question');
        $this->forge->createTable('survey_answers');
    }

    public function down()
    {
        $this->forge->dropTable('survey_answers', true);
        $this->forge->dropTable('survey_responses', true);
        $this->forge->dropTable('survey_question_options', true);
        $this->forge->dropTable('survey_questions', true);
        $this->forge->dropTable('survey_sections', true);
        $this->forge->dropTable('surveys', true);
    }
}
