<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePortalUserTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'CHAR', 'constraint' => 36],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'password_hash'=> ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'last_name'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'avatar_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_login_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email', 'uk_portal_users_email');
        $this->forge->addKey('active', false, false, 'idx_portal_users_active');
        $this->forge->addKey('last_login_at', false, false, 'idx_portal_users_last_login_at');
        $this->forge->createTable('portal_users');

        $this->forge->addField([
            'id'                 => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id'     => ['type' => 'CHAR', 'constraint' => 36],
            'refresh_token_hash' => ['type' => 'VARCHAR', 'constraint' => 128],
            'ip_address'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'expires_at'         => ['type' => 'DATETIME'],
            'revoked_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('refresh_token_hash', 'uk_portal_user_sessions_hash');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_sessions_user');
        $this->forge->addKey('expires_at', false, false, 'idx_portal_user_sessions_expires');
        $this->forge->addKey('revoked_at', false, false, 'idx_portal_user_sessions_revoked');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_sessions_user');
        $this->forge->createTable('portal_user_sessions');

        $this->forge->addField([
            'id'                     => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id'         => ['type' => 'CHAR', 'constraint' => 36],
            'timezone'               => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'language'               => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'es'],
            'digest_frequency'       => ['type' => 'ENUM', 'constraint' => ['none', 'daily', 'weekly'], 'default' => 'none'],
            'personalization_opt_in' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('portal_user_id', 'uk_portal_user_preferences_user');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_preferences_user');
        $this->forge->createTable('portal_user_preferences');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'category_id'    => ['type' => 'CHAR', 'constraint' => 36],
            'weight'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 1.00],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'category_id'], 'uk_portal_user_favorite_categories');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_favorite_categories_user');
        $this->forge->addKey('category_id', false, false, 'idx_portal_user_favorite_categories_category');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_categories_user');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_categories_category');
        $this->forge->createTable('portal_user_favorite_categories');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'tag_id'         => ['type' => 'CHAR', 'constraint' => 36],
            'weight'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 1.00],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'tag_id'], 'uk_portal_user_favorite_tags');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_favorite_tags_user');
        $this->forge->addKey('tag_id', false, false, 'idx_portal_user_favorite_tags_tag');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_tags_user');
        $this->forge->addForeignKey('tag_id', 'tags', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_tags_tag');
        $this->forge->createTable('portal_user_favorite_tags');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'author_id'      => ['type' => 'CHAR', 'constraint' => 36],
            'weight'         => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 1.00],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'author_id'], 'uk_portal_user_favorite_authors');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_favorite_authors_user');
        $this->forge->addKey('author_id', false, false, 'idx_portal_user_favorite_authors_author');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_authors_user');
        $this->forge->addForeignKey('author_id', 'authors', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_favorite_authors_author');
        $this->forge->createTable('portal_user_favorite_authors');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'news_id'        => ['type' => 'CHAR', 'constraint' => 36],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'saved_at'       => ['type' => 'DATETIME', 'null' => true],
            'read_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'news_id'], 'uk_portal_user_saved_posts');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_saved_posts_user');
        $this->forge->addKey('news_id', false, false, 'idx_portal_user_saved_posts_news');
        $this->forge->addKey('saved_at', false, false, 'idx_portal_user_saved_posts_saved_at');
        $this->forge->addKey('deleted_at', false, false, 'idx_portal_user_saved_posts_deleted_at');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_saved_posts_user');
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_saved_posts_news');
        $this->forge->createTable('portal_user_saved_posts');

        $this->forge->addField([
            'id'                 => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id'     => ['type' => 'CHAR', 'constraint' => 36],
            'news_id'            => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'category_id'        => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'tag_id'             => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'author_id'          => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'action'             => ['type' => 'VARCHAR', 'constraint' => 40],
            'context'            => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'time_spent_seconds' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'score_delta'        => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
            'metadata'           => ['type' => 'JSON', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_interactions_user');
        $this->forge->addKey('action', false, false, 'idx_portal_user_interactions_action');
        $this->forge->addKey('created_at', false, false, 'idx_portal_user_interactions_created_at');
        $this->forge->addKey('news_id', false, false, 'idx_portal_user_interactions_news');
        $this->forge->addKey('category_id', false, false, 'idx_portal_user_interactions_category');
        $this->forge->addKey('tag_id', false, false, 'idx_portal_user_interactions_tag');
        $this->forge->addKey('author_id', false, false, 'idx_portal_user_interactions_author');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_interactions_user');
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'SET NULL', 'fk_portal_user_interactions_news');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'CASCADE', 'SET NULL', 'fk_portal_user_interactions_category');
        $this->forge->addForeignKey('tag_id', 'tags', 'id', 'CASCADE', 'SET NULL', 'fk_portal_user_interactions_tag');
        $this->forge->addForeignKey('author_id', 'authors', 'id', 'CASCADE', 'SET NULL', 'fk_portal_user_interactions_author');
        $this->forge->createTable('portal_user_interactions');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'news_id'        => ['type' => 'CHAR', 'constraint' => 36],
            'score'          => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'rank_position'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'components'     => ['type' => 'JSON', 'null' => true],
            'calculated_at'  => ['type' => 'DATETIME'],
            'expires_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['portal_user_id', 'news_id'], 'uk_portal_user_recommendation_scores');
        $this->forge->addKey(['portal_user_id', 'rank_position'], false, false, 'idx_portal_user_recommendation_scores_user_rank');
        $this->forge->addKey('calculated_at', false, false, 'idx_portal_user_recommendation_scores_calculated_at');
        $this->forge->addKey('expires_at', false, false, 'idx_portal_user_recommendation_scores_expires_at');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_recommendation_scores_user');
        $this->forge->addForeignKey('news_id', 'news', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_recommendation_scores_news');
        $this->forge->createTable('portal_user_recommendation_scores');

        $this->forge->addField([
            'id'             => ['type' => 'CHAR', 'constraint' => 36],
            'portal_user_id' => ['type' => 'CHAR', 'constraint' => 36],
            'token_hash'     => ['type' => 'VARCHAR', 'constraint' => 128],
            'expires_at'     => ['type' => 'DATETIME'],
            'used_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash', 'uk_portal_user_password_resets_hash');
        $this->forge->addKey('portal_user_id', false, false, 'idx_portal_user_password_resets_user');
        $this->forge->addKey('expires_at', false, false, 'idx_portal_user_password_resets_expires_at');
        $this->forge->addForeignKey('portal_user_id', 'portal_users', 'id', 'CASCADE', 'CASCADE', 'fk_portal_user_password_resets_user');
        $this->forge->createTable('portal_user_password_resets');
    }

    public function down()
    {
        $this->forge->dropTable('portal_user_password_resets', true);
        $this->forge->dropTable('portal_user_recommendation_scores', true);
        $this->forge->dropTable('portal_user_interactions', true);
        $this->forge->dropTable('portal_user_saved_posts', true);
        $this->forge->dropTable('portal_user_favorite_authors', true);
        $this->forge->dropTable('portal_user_favorite_tags', true);
        $this->forge->dropTable('portal_user_favorite_categories', true);
        $this->forge->dropTable('portal_user_preferences', true);
        $this->forge->dropTable('portal_user_sessions', true);
        $this->forge->dropTable('portal_users', true);
    }
}
