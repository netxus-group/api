<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommunicationsModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'scope' => ['type' => 'VARCHAR', 'constraint' => 50],
            'public_config' => ['type' => 'JSON', 'null' => true],
            'secret_config_encrypted' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('scope', 'uk_communication_settings_scope');
        $this->forge->createTable('communication_settings');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'key' => ['type' => 'VARCHAR', 'constraint' => 80],
            'name' => ['type' => 'VARCHAR', 'constraint' => 160],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 255],
            'html_body' => ['type' => 'LONGTEXT'],
            'text_body' => ['type' => 'LONGTEXT', 'null' => true],
            'variables_json' => ['type' => 'JSON', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('key', 'uk_email_templates_key');
        $this->forge->createTable('email_templates');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'channel' => ['type' => 'ENUM', 'constraint' => ['email', 'push'], 'default' => 'email'],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 80],
            'template_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'recipient_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'recipient_user_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed', 'skipped'], 'default' => 'pending'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'metadata_json' => ['type' => 'JSON', 'null' => true],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('channel', false, false, 'idx_communication_logs_channel');
        $this->forge->addKey('status', false, false, 'idx_communication_logs_status');
        $this->forge->addKey('template_key', false, false, 'idx_communication_logs_template');
        $this->forge->addKey('recipient_email', false, false, 'idx_communication_logs_recipient_email');
        $this->forge->addKey('recipient_user_id', false, false, 'idx_communication_logs_recipient_user');
        $this->forge->createTable('communication_logs');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 255],
            'template_key' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'newsletter_news_digest'],
            'news_ids_json' => ['type' => 'JSON', 'null' => true],
            'audience' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'active_subscribers'],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'sending', 'sent', 'failed'], 'default' => 'draft'],
            'preview_html' => ['type' => 'LONGTEXT', 'null' => true],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'sent_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status', false, false, 'idx_newsletter_campaigns_status');
        $this->forge->addKey('created_at', false, false, 'idx_newsletter_campaigns_created_at');
        $this->forge->createTable('newsletter_campaigns');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'campaign_id' => ['type' => 'CHAR', 'constraint' => 36],
            'news_id' => ['type' => 'CHAR', 'constraint' => 36],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['campaign_id', 'news_id'], 'uk_newsletter_campaign_items');
        $this->forge->addKey('campaign_id', false, false, 'idx_newsletter_campaign_items_campaign');
        $this->forge->addKey('news_id', false, false, 'idx_newsletter_campaign_items_news');
        $this->forge->createTable('newsletter_campaign_items');

        $this->forge->addField([
            'id' => ['type' => 'CHAR', 'constraint' => 36],
            'survey_id' => ['type' => 'CHAR', 'constraint' => 36],
            'recipient_user_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => true],
            'recipient_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'notification_type' => ['type' => 'ENUM', 'constraint' => ['published', 'reminder', 'manual'], 'default' => 'published'],
            'sent_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'last_sent_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed', 'skipped'], 'default' => 'pending'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'metadata_json' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('survey_id', false, false, 'idx_survey_notification_logs_survey');
        $this->forge->addKey('recipient_user_id', false, false, 'idx_survey_notification_logs_user');
        $this->forge->addKey('notification_type', false, false, 'idx_survey_notification_logs_type');
        $this->forge->createTable('survey_notification_logs');

        if ($this->db->fieldExists('source', 'newsletter_subscribers') === false) {
            $this->forge->addColumn('newsletter_subscribers', [
                'source' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'name'],
            ]);
        }
        if ($this->db->fieldExists('metadata', 'newsletter_subscribers') === false) {
            $this->forge->addColumn('newsletter_subscribers', [
                'metadata' => ['type' => 'JSON', 'null' => true, 'after' => 'source'],
            ]);
        }
        if ($this->db->fieldExists('unsubscribe_token_hash', 'newsletter_subscribers') === false) {
            $this->forge->addColumn('newsletter_subscribers', [
                'unsubscribe_token_hash' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true, 'after' => 'confirmation_token'],
            ]);
        }
        if ($this->db->fieldExists('unsubscribe_token_expires_at', 'newsletter_subscribers') === false) {
            $this->forge->addColumn('newsletter_subscribers', [
                'unsubscribe_token_expires_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'unsubscribe_token_hash'],
            ]);
        }
        if ($this->db->fieldExists('notify_on_publish', 'surveys') === false) {
            $this->forge->addColumn('surveys', [
                'notify_on_publish' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'questions_per_view'],
            ]);
        }
        if ($this->db->fieldExists('notify_active_users', 'surveys') === false) {
            $this->forge->addColumn('surveys', [
                'notify_active_users' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'notify_on_publish'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('survey_notification_logs')) {
            $this->forge->dropTable('survey_notification_logs', true);
        }
        if ($this->db->tableExists('newsletter_campaign_items')) {
            $this->forge->dropTable('newsletter_campaign_items', true);
        }
        if ($this->db->tableExists('newsletter_campaigns')) {
            $this->forge->dropTable('newsletter_campaigns', true);
        }
        if ($this->db->tableExists('communication_logs')) {
            $this->forge->dropTable('communication_logs', true);
        }
        if ($this->db->tableExists('email_templates')) {
            $this->forge->dropTable('email_templates', true);
        }
        if ($this->db->tableExists('communication_settings')) {
            $this->forge->dropTable('communication_settings', true);
        }
    }
}
