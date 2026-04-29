-- ============================================================
-- Netxus Portal News - Schema SQL
-- MySQL / MariaDB compatible
-- Generated for CodeIgniter 4 migration
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- 1. Role Profiles
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_profiles` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `capabilities` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_profiles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 2. Users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` CHAR(36) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(200) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 3. User Roles (pivot)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `role_profile_id` CHAR(36) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_roles` (`user_id`, `role_profile_id`),
  KEY `idx_user_roles_user` (`user_id`),
  KEY `idx_user_roles_role` (`role_profile_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_profile_id`) REFERENCES `role_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 4. User Special Permissions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_special_permissions` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `permission` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_perm` (`user_id`, `permission`),
  CONSTRAINT `fk_user_perms_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 5. Refresh Tokens
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_refresh_tokens_hash` (`token_hash`),
  KEY `idx_refresh_tokens_user` (`user_id`),
  CONSTRAINT `fk_refresh_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 6. Authors
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `authors` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(250) NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `social` JSON DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_authors_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 7. Categories
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT NULL,
  `parent_id` CHAR(36) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 8. Tags
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 9. News
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(550) NOT NULL,
  `subtitle` VARCHAR(500) DEFAULT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `body` LONGTEXT DEFAULT NULL,
  `cover_image_url` VARCHAR(500) DEFAULT NULL,
  `author_id` CHAR(36) DEFAULT NULL,
  `status` ENUM('draft','in_review','approved','scheduled','published','archived') NOT NULL DEFAULT 'draft',
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `breaking` TINYINT(1) NOT NULL DEFAULT 0,
  `source_url` VARCHAR(500) DEFAULT NULL,
  `source_name` VARCHAR(200) DEFAULT NULL,
  `seo_title` VARCHAR(200) DEFAULT NULL,
  `seo_description` VARCHAR(500) DEFAULT NULL,
  `seo_keywords` VARCHAR(500) DEFAULT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `scheduled_at` DATETIME DEFAULT NULL,
  `created_by` CHAR(36) DEFAULT NULL,
  `reviewed_by` CHAR(36) DEFAULT NULL,
  `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `share_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_status` (`status`),
  KEY `idx_news_author` (`author_id`),
  KEY `idx_news_created_by` (`created_by`),
  KEY `idx_news_published_at` (`published_at`),
  KEY `idx_news_scheduled_at` (`scheduled_at`),
  CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_news_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_news_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 10. News Categories (pivot)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news_categories` (
  `id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `category_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_categories` (`news_id`, `category_id`),
  CONSTRAINT `fk_nc_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 11. News Tags (pivot)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news_tags` (
  `id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `tag_id` CHAR(36) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_tags` (`news_id`, `tag_id`),
  CONSTRAINT `fk_nt_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nt_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 12. Post Status History
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_status_history` (
  `id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `from_status` VARCHAR(30) DEFAULT NULL,
  `to_status` VARCHAR(30) NOT NULL,
  `changed_by` CHAR(36) DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_psh_news` (`news_id`),
  CONSTRAINT `fk_psh_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 13. Media Images
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `media_images` (
  `id` CHAR(36) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `size` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED DEFAULT NULL,
  `height` INT UNSIGNED DEFAULT NULL,
  `url` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(300) DEFAULT NULL,
  `caption` TEXT DEFAULT NULL,
  `folder` VARCHAR(100) NOT NULL DEFAULT 'general',
  `uploaded_by` CHAR(36) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_media_uploader` (`uploaded_by`),
  CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 14. Ad Slots
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ad_slots` (
  `id` CHAR(36) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `placement` VARCHAR(50) NOT NULL,
  `type` ENUM('image','html','adsense','script') NOT NULL DEFAULT 'image',
  `content` JSON DEFAULT NULL,
  `target_url` VARCHAR(500) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ad_slots_placement` (`placement`),
  KEY `idx_ad_slots_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 15. Ad Impressions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ad_impressions` (
  `id` CHAR(36) NOT NULL,
  `ad_slot_id` CHAR(36) NOT NULL,
  `event_type` ENUM('impression','click') NOT NULL DEFAULT 'impression',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ad_imp_slot` (`ad_slot_id`),
  KEY `idx_ad_imp_created` (`created_at`),
  CONSTRAINT `fk_ad_imp_slot` FOREIGN KEY (`ad_slot_id`) REFERENCES `ad_slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 16. Newsletter Subscribers
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` CHAR(36) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(200) DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `status` ENUM('pending','active','unsubscribed') NOT NULL DEFAULT 'pending',
  `confirmation_token` VARCHAR(500) DEFAULT NULL,
  `unsubscribe_token_hash` VARCHAR(128) DEFAULT NULL,
  `unsubscribe_token_expires_at` DATETIME DEFAULT NULL,
  `confirmed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_newsletter_email` (`email`),
  KEY `idx_newsletter_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 17. Polls
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `polls` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_by` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_polls_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_questions` (
  `id` CHAR(36) NOT NULL,
  `poll_id` CHAR(36) NOT NULL,
  `text` VARCHAR(500) NOT NULL,
  `type` ENUM('single','multiple','text') NOT NULL DEFAULT 'single',
  `sort_order` INT NOT NULL DEFAULT 0,
  `required` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pq_poll` (`poll_id`),
  CONSTRAINT `fk_pq_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_options` (
  `id` CHAR(36) NOT NULL,
  `question_id` CHAR(36) NOT NULL,
  `text` VARCHAR(300) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_question` (`question_id`),
  CONSTRAINT `fk_po_question` FOREIGN KEY (`question_id`) REFERENCES `poll_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_responses` (
  `id` CHAR(36) NOT NULL,
  `poll_id` CHAR(36) NOT NULL,
  `respondent_id` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_poll_respondent` (`poll_id`, `respondent_id`),
  CONSTRAINT `fk_pr_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `poll_response_details` (
  `id` CHAR(36) NOT NULL,
  `response_id` CHAR(36) NOT NULL,
  `question_id` CHAR(36) NOT NULL,
  `option_id` CHAR(36) DEFAULT NULL,
  `text_answer` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prd_response` (`response_id`),
  KEY `idx_prd_question` (`question_id`),
  CONSTRAINT `fk_prd_response` FOREIGN KEY (`response_id`) REFERENCES `poll_responses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prd_question` FOREIGN KEY (`question_id`) REFERENCES `poll_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prd_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 18. Engagement Events
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `engagement_events` (
  `id` CHAR(36) NOT NULL,
  `entity_id` CHAR(36) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `event_type` VARCHAR(30) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_engage_entity` (`entity_id`, `entity_type`),
  KEY `idx_engage_type` (`event_type`),
  KEY `idx_engage_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 19. Integration Config
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_configs` (
  `id` CHAR(36) NOT NULL,
  `provider` VARCHAR(50) NOT NULL,
  `api_key` VARCHAR(500) DEFAULT NULL,
  `endpoint` VARCHAR(500) DEFAULT NULL,
  `ttl` VARCHAR(20) NOT NULL DEFAULT '1h',
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `extra_config` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_integ_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 20. Integration Snapshots
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `integration_snapshots` (
  `id` CHAR(36) NOT NULL,
  `provider` VARCHAR(50) NOT NULL,
  `integration_key` VARCHAR(50) DEFAULT NULL,
  `payload` JSON DEFAULT NULL,
  `data` JSON DEFAULT NULL,
  `fetched_at` DATETIME NOT NULL,
  `ttl_seconds` INT NOT NULL DEFAULT 3600,
  `expires_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'ok',
  `error_message` TEXT DEFAULT NULL,
  `refresh_lock_until` DATETIME DEFAULT NULL,
  `is_fallback` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_snap_provider` (`provider`),
  KEY `idx_integration_key` (`integration_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 21. Home Layout Config
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `home_layout_config` (
  `id` CHAR(36) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` JSON DEFAULT NULL,
  `updated_by` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hlc_key` (`key`),
  CONSTRAINT `fk_hlc_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 22. Settings (global key-value)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` CHAR(36) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 23. User Settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_settings` (`user_id`, `key`),
  CONSTRAINT `fk_usettings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 24. Audit Log
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` CHAR(36) DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------
-- 25. Portal Users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_users` (
  `id` CHAR(36) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(120) DEFAULT NULL,
  `last_name` VARCHAR(120) DEFAULT NULL,
  `display_name` VARCHAR(200) DEFAULT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_users_email` (`email`),
  KEY `idx_portal_users_active` (`active`),
  KEY `idx_portal_users_last_login_at` (`last_login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 26. Portal User Sessions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_sessions` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `refresh_token_hash` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_sessions_hash` (`refresh_token_hash`),
  KEY `idx_portal_user_sessions_user` (`portal_user_id`),
  KEY `idx_portal_user_sessions_expires` (`expires_at`),
  KEY `idx_portal_user_sessions_revoked` (`revoked_at`),
  CONSTRAINT `fk_portal_user_sessions_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 27. Portal User Preferences
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_preferences` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `timezone` VARCHAR(80) DEFAULT NULL,
  `language` VARCHAR(10) NOT NULL DEFAULT 'es',
  `digest_frequency` ENUM('none','daily','weekly') NOT NULL DEFAULT 'none',
  `personalization_opt_in` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_preferences_user` (`portal_user_id`),
  CONSTRAINT `fk_portal_user_preferences_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 28. Portal User Favorite Categories
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_favorite_categories` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `category_id` CHAR(36) NOT NULL,
  `weight` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_favorite_categories` (`portal_user_id`,`category_id`),
  KEY `idx_portal_user_favorite_categories_user` (`portal_user_id`),
  KEY `idx_portal_user_favorite_categories_category` (`category_id`),
  CONSTRAINT `fk_portal_user_favorite_categories_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_favorite_categories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 29. Portal User Favorite Tags
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_favorite_tags` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `tag_id` CHAR(36) NOT NULL,
  `weight` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_favorite_tags` (`portal_user_id`,`tag_id`),
  KEY `idx_portal_user_favorite_tags_user` (`portal_user_id`),
  KEY `idx_portal_user_favorite_tags_tag` (`tag_id`),
  CONSTRAINT `fk_portal_user_favorite_tags_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_favorite_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 30. Portal User Favorite Authors
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_favorite_authors` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `author_id` CHAR(36) NOT NULL,
  `weight` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_favorite_authors` (`portal_user_id`,`author_id`),
  KEY `idx_portal_user_favorite_authors_user` (`portal_user_id`),
  KEY `idx_portal_user_favorite_authors_author` (`author_id`),
  CONSTRAINT `fk_portal_user_favorite_authors_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_favorite_authors_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 31. Portal User Saved Posts
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_saved_posts` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `note` VARCHAR(500) DEFAULT NULL,
  `saved_at` DATETIME DEFAULT NULL,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_saved_posts` (`portal_user_id`,`news_id`),
  KEY `idx_portal_user_saved_posts_user` (`portal_user_id`),
  KEY `idx_portal_user_saved_posts_news` (`news_id`),
  KEY `idx_portal_user_saved_posts_saved_at` (`saved_at`),
  KEY `idx_portal_user_saved_posts_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_portal_user_saved_posts_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_saved_posts_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 32. Portal User Interactions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_interactions` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) DEFAULT NULL,
  `category_id` CHAR(36) DEFAULT NULL,
  `tag_id` CHAR(36) DEFAULT NULL,
  `author_id` CHAR(36) DEFAULT NULL,
  `action` VARCHAR(40) NOT NULL,
  `context` VARCHAR(80) DEFAULT NULL,
  `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `score_delta` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `metadata` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_portal_user_interactions_user` (`portal_user_id`),
  KEY `idx_portal_user_interactions_action` (`action`),
  KEY `idx_portal_user_interactions_created_at` (`created_at`),
  KEY `idx_portal_user_interactions_news` (`news_id`),
  KEY `idx_portal_user_interactions_category` (`category_id`),
  KEY `idx_portal_user_interactions_tag` (`tag_id`),
  KEY `idx_portal_user_interactions_author` (`author_id`),
  CONSTRAINT `fk_portal_user_interactions_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_interactions_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_portal_user_interactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_portal_user_interactions_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_portal_user_interactions_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 33. Portal User Recommendation Scores
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_recommendation_scores` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `score` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `rank_position` INT UNSIGNED DEFAULT NULL,
  `components` JSON DEFAULT NULL,
  `calculated_at` DATETIME NOT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_recommendation_scores` (`portal_user_id`,`news_id`),
  KEY `idx_portal_user_recommendation_scores_user_rank` (`portal_user_id`,`rank_position`),
  KEY `idx_portal_user_recommendation_scores_calculated_at` (`calculated_at`),
  KEY `idx_portal_user_recommendation_scores_expires_at` (`expires_at`),
  CONSTRAINT `fk_portal_user_recommendation_scores_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_portal_user_recommendation_scores_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 34. Portal User Password Resets
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_password_resets` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_password_resets_hash` (`token_hash`),
  KEY `idx_portal_user_password_resets_user` (`portal_user_id`),
  KEY `idx_portal_user_password_resets_expires_at` (`expires_at`),
  CONSTRAINT `fk_portal_user_password_resets_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 35. Surveys
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `surveys` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `initial_message` TEXT DEFAULT NULL,
  `final_message` TEXT DEFAULT NULL,
  `status` ENUM('draft','published','paused','closed') NOT NULL DEFAULT 'draft',
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `requires_login` TINYINT(1) NOT NULL DEFAULT 0,
  `allow_back_navigation` TINYINT(1) NOT NULL DEFAULT 0,
  `questions_per_view` INT UNSIGNED DEFAULT NULL,
  `notify_on_publish` TINYINT(1) NOT NULL DEFAULT 0,
  `notify_active_users` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` CHAR(36) DEFAULT NULL,
  `updated_by` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_surveys_slug` (`slug`),
  KEY `idx_surveys_status` (`status`),
  KEY `idx_surveys_starts_at` (`starts_at`),
  KEY `idx_surveys_ends_at` (`ends_at`),
  KEY `idx_surveys_deleted_at` (`deleted_at`),
  CONSTRAINT `fk_surveys_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_surveys_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 36. Survey Sections
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_sections` (
  `id` CHAR(36) NOT NULL,
  `survey_id` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_survey_sections_survey` (`survey_id`),
  KEY `idx_survey_sections_order` (`survey_id`,`sort_order`),
  CONSTRAINT `fk_survey_sections_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 37. Survey Questions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_questions` (
  `id` CHAR(36) NOT NULL,
  `survey_id` CHAR(36) NOT NULL,
  `section_id` CHAR(36) NOT NULL,
  `question_text` VARCHAR(500) NOT NULL,
  `help_text` TEXT DEFAULT NULL,
  `type` ENUM('short_text','long_text','single_choice','multiple_choice','dropdown','numeric_scale','date') NOT NULL DEFAULT 'short_text',
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `config` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_survey_questions_survey` (`survey_id`),
  KEY `idx_survey_questions_section` (`section_id`),
  KEY `idx_survey_questions_order` (`section_id`,`sort_order`),
  CONSTRAINT `fk_survey_questions_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_questions_section` FOREIGN KEY (`section_id`) REFERENCES `survey_sections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 38. Survey Question Options
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_question_options` (
  `id` CHAR(36) NOT NULL,
  `question_id` CHAR(36) NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_survey_question_options_question` (`question_id`),
  KEY `idx_survey_question_options_order` (`question_id`,`sort_order`),
  CONSTRAINT `fk_survey_question_options_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 39. Survey Responses
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_responses` (
  `id` CHAR(36) NOT NULL,
  `survey_id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) DEFAULT NULL,
  `anonymous_key` VARCHAR(128) DEFAULT NULL,
  `status` ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
  `current_section_id` CHAR(36) DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `ip_hash` VARCHAR(128) DEFAULT NULL,
  `user_agent_hash` VARCHAR(128) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_responses_user` (`survey_id`,`user_id`),
  UNIQUE KEY `uk_survey_responses_anon` (`survey_id`,`anonymous_key`),
  KEY `idx_survey_responses_survey` (`survey_id`),
  KEY `idx_survey_responses_status` (`status`),
  KEY `idx_survey_responses_completed_at` (`completed_at`),
  KEY `idx_survey_responses_current_section` (`current_section_id`),
  KEY `idx_survey_responses_ip_hash` (`ip_hash`),
  KEY `idx_survey_responses_user_agent_hash` (`user_agent_hash`),
  CONSTRAINT `fk_survey_responses_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_responses_user` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_responses_current_section` FOREIGN KEY (`current_section_id`) REFERENCES `survey_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 40. Survey Answers
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_answers` (
  `id` CHAR(36) NOT NULL,
  `survey_response_id` CHAR(36) NOT NULL,
  `survey_id` CHAR(36) NOT NULL,
  `section_id` CHAR(36) NOT NULL,
  `question_id` CHAR(36) NOT NULL,
  `value_text` TEXT DEFAULT NULL,
  `value_json` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_survey_answers_response_question` (`survey_response_id`,`question_id`),
  KEY `idx_survey_answers_response` (`survey_response_id`),
  KEY `idx_survey_answers_survey` (`survey_id`),
  KEY `idx_survey_answers_section` (`section_id`),
  KEY `idx_survey_answers_question` (`question_id`),
  CONSTRAINT `fk_survey_answers_response` FOREIGN KEY (`survey_response_id`) REFERENCES `survey_responses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_answers_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_answers_section` FOREIGN KEY (`section_id`) REFERENCES `survey_sections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_survey_answers_question` FOREIGN KEY (`question_id`) REFERENCES `survey_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 41. Communication Settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `communication_settings` (
  `id` CHAR(36) NOT NULL,
  `scope` VARCHAR(50) NOT NULL,
  `public_config` JSON DEFAULT NULL,
  `secret_config_encrypted` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_communication_settings_scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 42. Email Templates
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` CHAR(36) NOT NULL,
  `key` VARCHAR(80) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `html_body` LONGTEXT NOT NULL,
  `text_body` LONGTEXT DEFAULT NULL,
  `variables_json` JSON DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email_templates_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 43. Communication Logs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `communication_logs` (
  `id` CHAR(36) NOT NULL,
  `channel` ENUM('email','push') NOT NULL DEFAULT 'email',
  `provider` VARCHAR(80) NOT NULL,
  `template_key` VARCHAR(80) DEFAULT NULL,
  `recipient_email` VARCHAR(255) DEFAULT NULL,
  `recipient_user_id` CHAR(36) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `metadata_json` JSON DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_communication_logs_channel` (`channel`),
  KEY `idx_communication_logs_status` (`status`),
  KEY `idx_communication_logs_template` (`template_key`),
  KEY `idx_communication_logs_recipient_email` (`recipient_email`),
  KEY `idx_communication_logs_recipient_user` (`recipient_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 44. Newsletter Campaigns
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_campaigns` (
  `id` CHAR(36) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `template_key` VARCHAR(80) NOT NULL DEFAULT 'newsletter_news_digest',
  `news_ids_json` JSON DEFAULT NULL,
  `audience` VARCHAR(80) NOT NULL DEFAULT 'active_subscribers',
  `status` ENUM('draft','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `preview_html` LONGTEXT DEFAULT NULL,
  `scheduled_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_by` CHAR(36) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_newsletter_campaigns_status` (`status`),
  KEY `idx_newsletter_campaigns_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 45. Newsletter Campaign Items
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `newsletter_campaign_items` (
  `id` CHAR(36) NOT NULL,
  `campaign_id` CHAR(36) NOT NULL,
  `news_id` CHAR(36) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_newsletter_campaign_items` (`campaign_id`,`news_id`),
  KEY `idx_newsletter_campaign_items_campaign` (`campaign_id`),
  KEY `idx_newsletter_campaign_items_news` (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 46. Survey Notification Logs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `survey_notification_logs` (
  `id` CHAR(36) NOT NULL,
  `survey_id` CHAR(36) NOT NULL,
  `recipient_user_id` CHAR(36) DEFAULT NULL,
  `recipient_email` VARCHAR(255) DEFAULT NULL,
  `notification_type` ENUM('published','reminder','manual') NOT NULL DEFAULT 'published',
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_sent_at` DATETIME DEFAULT NULL,
  `status` ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `metadata_json` JSON DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_survey_notification_logs_survey` (`survey_id`),
  KEY `idx_survey_notification_logs_user` (`recipient_user_id`),
  KEY `idx_survey_notification_logs_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 47. Portal User Notifications
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portal_user_notifications` (
  `id` CHAR(36) NOT NULL,
  `portal_user_id` CHAR(36) NOT NULL,
  `event_key` VARCHAR(190) NOT NULL,
  `type` VARCHAR(60) NOT NULL DEFAULT 'general',
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `metadata_json` JSON DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portal_user_notifications_event` (`portal_user_id`,`event_key`),
  KEY `idx_portal_user_notifications_user` (`portal_user_id`),
  KEY `idx_portal_user_notifications_read` (`is_read`),
  KEY `idx_portal_user_notifications_created_at` (`created_at`),
  CONSTRAINT `fk_portal_user_notifications_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- 48. User Password Resets (editorial users)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_password_resets` (
  `id` CHAR(36) NOT NULL,
  `user_id` CHAR(36) NOT NULL,
  `token_hash` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_password_resets_hash` (`token_hash`),
  KEY `idx_user_password_resets_user` (`user_id`),
  KEY `idx_user_password_resets_expires_at` (`expires_at`),
  CONSTRAINT `fk_user_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

