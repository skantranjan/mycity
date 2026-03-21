-- =============================================================================
-- Core schema — My City Info (API v1)
-- Run this file as ONE script (copy/paste entire file). Order is dependency-safe.
-- All application tables use prefix: mci_
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- -----------------------------------------------------------------------------
-- 1) Roles (no FK to other MCI tables)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_roles_short_name` (`short_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User roles (subscriber, super_admin, co_admin)';

INSERT INTO `mci_roles` (`name`, `short_name`) VALUES
  ('Subscriber', 'subscriber'),
  ('Super admin', 'super_admin'),
  ('Co admin', 'co_admin')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);

-- -----------------------------------------------------------------------------
-- 2) Users (FK -> mci_roles)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_users` (
  `id` char(36) NOT NULL COMMENT 'UUID v4 (primary identifier)',
  `email` varchar(255) NOT NULL,
  `role_id` int unsigned NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL COMMENT 'Optional; used when verifying SMS etc.',
  `terms_accepted_at` datetime(6) DEFAULT NULL COMMENT 'When user accepted Terms of Use (e.g. at registration)',
  `privacy_policy_accepted_at` datetime(6) DEFAULT NULL COMMENT 'When user accepted Privacy Policy',
  `registration_ip` varchar(45) DEFAULT NULL COMMENT 'Client IP at registration',
  `last_update_ip` varchar(45) DEFAULT NULL COMMENT 'Client IP on last profile/account update',
  `email_verified_at` datetime(6) DEFAULT NULL COMMENT 'NULL = not verified',
  `phone_verified_at` datetime(6) DEFAULT NULL COMMENT 'NULL = not verified',
  `last_login_at` datetime(6) DEFAULT NULL,
  `is_logged_in` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Best-effort login flag (login/logout)',
  `password_changed_at` datetime(6) DEFAULT NULL COMMENT 'Last password set/change',
  `status` enum('active','inactive','blocked','deleted') NOT NULL DEFAULT 'active',
  `failed_login_attempts` int unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime(6) DEFAULT NULL,
  `deleted_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT 'Registration / account creation time',
  `updated_at` datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6) COMMENT 'Last modification of this row',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_users_email` (`email`),
  KEY `idx_mci_users_role_id` (`role_id`),
  KEY `idx_mci_users_status` (`status`),
  KEY `idx_mci_users_created_at` (`created_at`),
  KEY `idx_mci_users_last_login` (`last_login_at`),
  KEY `idx_mci_users_email_verified` (`email_verified_at`),
  CONSTRAINT `fk_mci_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `mci_roles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Accounts for subscribers and control-panel staff';

-- -----------------------------------------------------------------------------
-- 3) User profiles (FK -> mci_users)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_userprofiles` (
  `id` char(36) NOT NULL COMMENT 'UUID v4 profile id',
  `userid` char(36) NOT NULL COMMENT 'Reference to mci_users.id',
  `first_name` varchar(64) DEFAULT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `profile_image` varchar(512) DEFAULT NULL,
  `gender` varchar(16) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `timezone` varchar(64) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `created_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_userprofiles_userid` (`userid`),
  KEY `idx_mci_userprofiles_timezone` (`timezone`),
  CONSTRAINT `fk_mci_userprofiles_userid`
    FOREIGN KEY (`userid`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-user profile data (name, avatar, demographics)';

-- -----------------------------------------------------------------------------
-- 4) Categories (standalone)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_categories_name` (`name`),
  KEY `idx_mci_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5) Tags (standalone)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_tags_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6) Category requests (FK -> mci_users)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_category_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `requester_id` char(36) NOT NULL,
  `requested_category_name` varchar(255) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `resolved_by_id` char(36) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `resolved_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mci_catreq_requester` (`requester_id`),
  KEY `idx_mci_catreq_status` (`status`),
  CONSTRAINT `fk_mci_catreq_requester`
    FOREIGN KEY (`requester_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7) Anonymous business submissions (FK -> mci_users, nullable)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_anon_business_submissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by_role` varchar(32) NOT NULL,
  `submitted_by_user_id` char(36) DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `resolved_by_id` char(36) DEFAULT NULL,
  `resolved_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mci_anon_status` (`status`),
  KEY `idx_mci_anon_category` (`category`),
  CONSTRAINT `fk_mci_anon_submitter`
    FOREIGN KEY (`submitted_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8) Social login provider mappings (FK -> mci_users)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mci_user_auth_providers` (
  `id` char(36) NOT NULL COMMENT 'UUID v4 (provider mapping identifier)',
  `user_id` char(36) NOT NULL COMMENT 'Reference to mci_users.id',

  `provider` enum('google','facebook','apple','linkedin') NOT NULL,
  `provider_user_id` varchar(255) NOT NULL COMMENT 'Provider-specific user id',

  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime(6) DEFAULT NULL,

  `provider_email` varchar(255) DEFAULT NULL,
  `provider_profile_image` varchar(500) DEFAULT NULL,

  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_user_auth_providers_provider_user` (`provider`, `provider_user_id`),
  KEY `idx_mci_user_auth_providers_user_id` (`user_id`),

  CONSTRAINT `fk_mci_user_auth_providers_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Maps local users to social providers (for social login / token refresh).';

-- =============================================================================
-- 9) Dev / staging seed users (local testing only — remove on production DB)
-- Passwords: subscribers = DevSubscriber123!  |  super admins = DevSuperAdmin123!
-- Idempotent: each row inserts only if that email is missing.
-- =============================================================================

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000001',
  'subscriber1.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 1',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber1.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000002',
  'subscriber2.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 2',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber2.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000003',
  'subscriber3.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 3',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber3.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000010',
  'superadmin.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'super_admin' LIMIT 1),
  '$2y$10$9bk6i8pYBDNETWVAA5l5gOX4SL7rXHQ/PdVazBdRQYiV/i2YM7x3O',
  'Dev Super Admin',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'superadmin.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000011',
  'superadmin2.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'super_admin' LIMIT 1),
  '$2y$10$9bk6i8pYBDNETWVAA5l5gOX4SL7rXHQ/PdVazBdRQYiV/i2YM7x3O',
  'Dev Super Admin 2',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'superadmin2.dev@mycityinfo.local');
