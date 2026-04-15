-- ============================================================
-- Netxus Portal News - Initial Seed Data (required)
-- Run after schema.sql to insert base system data
-- ============================================================

-- -----------------------------------------------------------
-- Default Roles
-- -----------------------------------------------------------
INSERT INTO `role_profiles` (`id`, `name`, `display_name`, `capabilities`, `created_at`, `updated_at`) VALUES
('00000000-0000-0000-0000-000000000001', 'super_admin', 'Super Administrador',
 '["manage_users","manage_roles","manage_news","publish_news","manage_categories","manage_tags","manage_authors","manage_media","manage_ads","manage_polls","manage_newsletter","manage_integrations","manage_settings","view_metrics","export_data","manage_home_layout"]',
 NOW(), NOW()),
('00000000-0000-0000-0000-000000000002', 'editor', 'Editor',
 '["manage_news","publish_news","manage_categories","manage_tags","manage_authors","manage_media","manage_polls","view_metrics"]',
 NOW(), NOW()),
('00000000-0000-0000-0000-000000000003', 'writer', 'Redactor',
 '["manage_news","manage_media"]',
 NOW(), NOW());

-- -----------------------------------------------------------
-- Default Super Admin User
-- Password: Admin123!
-- BCrypt hash for "Admin123!" (cost 10)
-- -----------------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `email_verified`, `created_at`, `updated_at`) VALUES
('10000000-0000-0000-0000-000000000001', 'admin@netxus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Netxus', 'Admin Netxus', 1, 1, NOW(), NOW());

INSERT INTO `user_roles` (`id`, `user_id`, `role_profile_id`, `created_at`) VALUES
('20000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000001', NOW());

-- -----------------------------------------------------------
-- Default Integration Configs
-- -----------------------------------------------------------
INSERT INTO `integration_configs` (`id`, `provider`, `endpoint`, `ttl`, `active`, `extra_config`, `created_at`, `updated_at`) VALUES
('30000000-0000-0000-0000-000000000001', 'weather', 'https://api.open-meteo.com/v1/forecast', '30m', 1, '{"latitude":-34.6037,"longitude":-58.3816}', NOW(), NOW()),
('30000000-0000-0000-0000-000000000002', 'dollar', 'https://open.er-api.com/v6/latest/USD', '1h', 1, NULL, NOW(), NOW());

-- -----------------------------------------------------------
-- Default Global Settings
-- -----------------------------------------------------------
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(UUID(), 'site_name', '"Netxus Portal News"', NOW(), NOW()),
(UUID(), 'site_description', '"Portal de noticias profesional"', NOW(), NOW()),
(UUID(), 'articles_per_page', '12', NOW(), NOW()),
(UUID(), 'allow_newsletter_signup', 'true', NOW(), NOW()),
(UUID(), 'default_news_status', '"draft"', NOW(), NOW());
