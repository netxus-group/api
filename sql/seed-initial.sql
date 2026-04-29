-- ============================================================
-- Netxus Portal News - Initial Seed Data (required)
-- Run after schema.sql to insert base system data
-- ============================================================
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

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
 NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `display_name` = VALUES(`display_name`),
  `capabilities` = VALUES(`capabilities`),
  `updated_at` = VALUES(`updated_at`);

-- -----------------------------------------------------------
-- Default Super Admin User
-- Password: Admin123!
-- BCrypt hash for "Admin123!" (cost 10, generated from seeder source of truth)
-- -----------------------------------------------------------
INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `display_name`, `active`, `email_verified`, `created_at`, `updated_at`) VALUES
('10000000-0000-0000-0000-000000000001', 'admin@netxus.com', '$2y$10$qxUJkMjpuqlTW12N8SC23uz0UKcjjNDunR2LQoJtS2WmNC2WMna5a', 'Admin', 'Netxus', 'Admin Netxus', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `first_name` = VALUES(`first_name`),
  `last_name` = VALUES(`last_name`),
  `display_name` = VALUES(`display_name`),
  `active` = VALUES(`active`),
  `email_verified` = VALUES(`email_verified`),
  `updated_at` = VALUES(`updated_at`);

INSERT INTO `user_roles` (`id`, `user_id`, `role_profile_id`, `created_at`) VALUES
('20000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000001', NOW())
ON DUPLICATE KEY UPDATE
  `created_at` = VALUES(`created_at`);

-- -----------------------------------------------------------
-- Default Integration Configs
-- -----------------------------------------------------------
INSERT INTO `integration_configs` (`id`, `provider`, `endpoint`, `ttl`, `active`, `extra_config`, `created_at`, `updated_at`) VALUES
('30000000-0000-0000-0000-000000000001', 'weather', 'https://api.open-meteo.com/v1/forecast', '1h', 1, '{"latitude":-34.6037,"longitude":-58.3816}', NOW(), NOW()),
('30000000-0000-0000-0000-000000000002', 'dollar', 'https://criptoya.com/api/dolar', '1h', 1, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `endpoint` = VALUES(`endpoint`),
  `ttl` = VALUES(`ttl`),
  `active` = VALUES(`active`),
  `extra_config` = VALUES(`extra_config`),
  `updated_at` = VALUES(`updated_at`);

-- -----------------------------------------------------------
-- Default Global Settings
-- -----------------------------------------------------------
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(UUID(), 'site_name', '"Netxus Portal News"', NOW(), NOW()),
(UUID(), 'site_description', '"Portal de noticias profesional"', NOW(), NOW()),
(UUID(), 'articles_per_page', '12', NOW(), NOW()),
(UUID(), 'allow_newsletter_signup', 'true', NOW(), NOW()),
 (UUID(), 'default_news_status', '"draft"', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `value` = VALUES(`value`),
  `updated_at` = VALUES(`updated_at`);

-- -----------------------------------------------------------
-- Communications module defaults (from CommunicationsSeeder)
-- -----------------------------------------------------------
INSERT INTO `email_templates` (`id`, `key`, `name`, `subject`, `html_body`, `text_body`, `variables_json`, `is_active`, `created_at`, `updated_at`) VALUES
(UUID(), 'welcome_user', 'Bienvenida', 'Bienvenido a {{site_name}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Bienvenido</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Hola {{user_name}}, gracias por sumarte a {{site_name}}.</p><p>Podes empezar en <a href="{{site_url}}">{{site_url}}</a>.</p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Hola {{user_name}}, gracias por sumarte a {{site_name}}.\n\nEmpezar en {{site_url}}',
'["user_name","user_email","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'password_reset', 'Recuperacion de contraseña', 'Recuperar acceso a {{site_name}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Recuperar contraseña</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Hola {{user_name}}, recibimos una solicitud para cambiar tu contraseña.</p><p><a href="{{reset_url}}">Crear una nueva contraseña</a></p><p>Este enlace vence el {{expires_at}}.</p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Hola {{user_name}},\n\nUsa este enlace para crear una nueva contraseña:\n{{reset_url}}\n\nVence el {{expires_at}}.',
'["user_name","user_email","reset_url","expires_at","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'newsletter_subscription', 'Suscripcion newsletter', 'Tu suscripcion a {{site_name}} esta activa',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Suscripcion confirmada</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Gracias por suscribirte, {{user_name}}.</p><p>Vas a recibir novedades de {{site_name}} en tu correo.</p><p><a href="{{unsubscribe_url}}">Gestionar baja segura</a></p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Gracias por suscribirte, {{user_name}}.\n\nVas a recibir novedades de {{site_name}} en tu correo.\nBaja segura: {{unsubscribe_url}}',
'["user_name","user_email","unsubscribe_url","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'newsletter_news_digest', 'Digest de noticias', 'Las noticias destacadas de {{site_name}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Resumen de noticias</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Hola {{user_name}}, estas son las noticias seleccionadas.</p>{{news_list}}<p><a href="{{unsubscribe_url}}">Cancelar suscripcion</a></p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Hola {{user_name}}, estas son las noticias seleccionadas.\n\n{{news_list}}\n\nBaja segura: {{unsubscribe_url}}',
'["user_name","user_email","newsletter_title","news_list","unsubscribe_url","portal_url","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'new_survey_available', 'Nueva encuesta disponible', 'Nueva encuesta disponible en {{site_name}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Nueva encuesta</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Hola {{user_name}}, ya esta disponible la encuesta <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Responder encuesta</a></p><p>{{survey_initial_message}}</p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Hola {{user_name}}, ya esta disponible la encuesta {{survey_title}}.\n\nResponder encuesta: {{survey_url}}',
'["user_name","user_email","survey_title","survey_url","survey_initial_message","survey_ends_at","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'survey_incomplete_reminder', 'Recordatorio encuesta incompleta', 'No dejes incompleta la encuesta {{survey_title}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Recordatorio</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>Hola {{user_name}}, retoma tu avance en <strong>{{survey_title}}</strong>.</p><p><a href="{{survey_url}}">Continuar encuesta</a></p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'Hola {{user_name}}, retoma tu avance en {{survey_title}}.\n\nContinuar encuesta: {{survey_url}}',
'["user_name","user_email","survey_title","survey_url","survey_initial_message","survey_ends_at","site_name","site_url","current_year"]', 1, NOW(), NOW()),
(UUID(), 'generic_notification', 'Notificacion generica', '{{site_name}}',
'<div style="margin:0;padding:32px;background:#f5f1ea;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:0 auto;padding:32px;background:#ffffff;border:1px solid #ece8df;border-radius:20px"><h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#111827">Notificacion</h1><div style="font-size:16px;line-height:1.8;color:#374151"><p>{{message}}</p></div><p style="margin:28px 0 0;font-size:12px;color:#6b7280">© {{current_year}} {{site_name}}</p></div></div>',
'{{message}}',
'["message","site_name","site_url","current_year"]', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `subject` = VALUES(`subject`),
  `html_body` = VALUES(`html_body`),
  `text_body` = VALUES(`text_body`),
  `variables_json` = VALUES(`variables_json`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = VALUES(`updated_at`);

INSERT INTO `communication_settings` (`id`, `scope`, `public_config`, `secret_config_encrypted`, `created_at`, `updated_at`) VALUES
(UUID(), 'default',
'{"email":{"provider":"smtp","fromAddress":"no-reply@netxus.com","fromName":"Netxus","replyTo":"soporte@netxus.com","sendEnabled":false,"testMode":false,"testEmail":"","smtp":{"host":"","port":587,"encryption":"tls"},"envialoSimple":{"accountId":""}},"push":{"provider":"none","sendEnabled":false,"testMode":false}}',
NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `public_config` = VALUES(`public_config`),
  `updated_at` = VALUES(`updated_at`);
