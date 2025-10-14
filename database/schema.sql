-- Migrations tracking table
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  `migrated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=user, 1=admin',
  `avatar` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=must change password on next login',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Password reset tokens table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `token` (`token`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`)
VALUES (
    'contact_info',
    '{"company_name":"Your Company Name","address":"123 Main Street","postal_code":"12345","city":"Your City","email":"info@example.com","phone":"+1 (555) 123-4567"}'
  ),
  ('copyright_text', '© 2006-{year} @nunes.net'),
  ('registration_enabled', '1'),
  ('site_logo', '/assets/img/logo.png') ON DUPLICATE KEY
UPDATE `setting_key` = `setting_key`;
-- Default admin user
-- Email: admin@an.com
-- Password: admin (CHANGE THIS IMMEDIATELY after first login!)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `active`)
VALUES (
    'Administrator',
    'admin@an.com',
    '$2y$12$omG.eXGrl3GXxXDHlhB8o.7lSYXMFe5q1uIoxohXUAwNofOEPhGRu',
    1,
    1
  ) ON DUPLICATE KEY
UPDATE `name` = `name`;