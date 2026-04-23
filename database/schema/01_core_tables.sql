-- =============================================================================
-- My City Info — Core Tables
-- File: database/schema/01_core_tables.sql
--
-- Creates all core tables: roles, users, profiles, categories, tags,
-- auth providers, category requests, anonymous submissions, password reset,
-- and the system error log.
--
-- Run order: this file first (no dependencies on other schema files).
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- =============================================================================
-- 1. mci_roles
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_roles` (
  `id`         int unsigned  NOT NULL AUTO_INCREMENT,
  `name`       varchar(255)  NOT NULL,
  `short_name` varchar(32)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_roles_short_name` (`short_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User roles: subscriber, super_admin, co_admin, system_anonymous';


-- =============================================================================
-- 2. mci_users
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_users` (
  `id`                         char(36)      NOT NULL COMMENT 'UUID v4',
  `email`                      varchar(255)  NOT NULL,
  `role_id`                    int unsigned  NOT NULL,
  `password_hash`              varchar(255)  NOT NULL,
  `display_name`               varchar(255)  DEFAULT NULL,
  `phone`                      varchar(32)   DEFAULT NULL,
  `terms_accepted_at`          datetime(6)   DEFAULT NULL,
  `privacy_policy_accepted_at` datetime(6)   DEFAULT NULL,
  `registration_ip`            varchar(45)   DEFAULT NULL,
  `last_update_ip`             varchar(45)   DEFAULT NULL,
  `email_verified_at`          datetime(6)   DEFAULT NULL,
  `phone_verified_at`          datetime(6)   DEFAULT NULL,
  `last_login_at`              datetime(6)   DEFAULT NULL,
  `is_logged_in`               tinyint(1)    NOT NULL DEFAULT 0,
  `password_changed_at`        datetime(6)   DEFAULT NULL,
  `status`                     enum('active','inactive','blocked','deleted') NOT NULL DEFAULT 'active',
  `failed_login_attempts`      int unsigned  NOT NULL DEFAULT 0,
  `locked_until`               datetime(6)   DEFAULT NULL,
  `deleted_at`                 datetime(6)   DEFAULT NULL,
  `created_at`                 datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`                 datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_users_email` (`email`),
  KEY `idx_mci_users_role_id`        (`role_id`),
  KEY `idx_mci_users_status`         (`status`),
  KEY `idx_mci_users_created_at`     (`created_at`),
  KEY `idx_mci_users_last_login`     (`last_login_at`),
  KEY `idx_mci_users_email_verified` (`email_verified_at`),
  CONSTRAINT `fk_mci_users_role_id`
    FOREIGN KEY (`role_id`) REFERENCES `mci_roles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Accounts for subscribers and control-panel staff';


-- =============================================================================
-- 3. mci_userprofiles
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_userprofiles` (
  `id`            char(36)     NOT NULL COMMENT 'UUID v4',
  `userid`        char(36)     NOT NULL COMMENT 'FK → mci_users.id',
  `first_name`    varchar(64)  DEFAULT NULL,
  `last_name`     varchar(64)  DEFAULT NULL,
  `profile_image` varchar(512) DEFAULT NULL,
  `gender`        varchar(16)  DEFAULT NULL,
  `date_of_birth` date         DEFAULT NULL,
  `timezone`      varchar(64)  DEFAULT NULL,
  `created_at`    datetime(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`    datetime(6)  DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `created_ip`    varchar(45)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_userprofiles_userid` (`userid`),
  KEY `idx_mci_userprofiles_timezone` (`timezone`),
  CONSTRAINT `fk_mci_userprofiles_userid`
    FOREIGN KEY (`userid`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-user profile data';


-- =============================================================================
-- 4. mci_categories  (self-referencing hierarchy: parent_id NULL = top-level)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_categories` (
  `id`               int unsigned  NOT NULL AUTO_INCREMENT,
  `parent_id`        int unsigned  DEFAULT NULL COMMENT 'NULL = top-level; FK → self',
  `name`             varchar(255)  NOT NULL,
  `slug`             varchar(255)  NOT NULL COMMENT 'Globally unique URL segment',
  `icon`             varchar(32)   DEFAULT NULL COMMENT 'Emoji or Bootstrap Icon class',
  `sort_order`       int unsigned  NOT NULL DEFAULT 0,
  `page_title`       varchar(255)  DEFAULT NULL,
  `meta_keywords`    varchar(512)  DEFAULT NULL,
  `meta_description` varchar(512)  DEFAULT NULL,
  `description`      text          DEFAULT NULL,
  `created_at`       datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_categories_slug` (`slug`),
  KEY `idx_mci_categories_parent` (`parent_id`),
  KEY `idx_mci_categories_name`   (`name`),
  CONSTRAINT `fk_mci_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `mci_categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Business categories with subcategory support (parent_id hierarchy)';


-- =============================================================================
-- 5. mci_tags
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_tags` (
  `id`               int unsigned  NOT NULL AUTO_INCREMENT,
  `name`             varchar(255)  NOT NULL,
  `slug`             varchar(255)  NOT NULL COMMENT 'Globally unique URL segment',
  `page_title`       varchar(255)  DEFAULT NULL,
  `meta_keywords`    varchar(512)  DEFAULT NULL,
  `meta_description` varchar(512)  DEFAULT NULL,
  `description`      text          DEFAULT NULL,
  `created_at`       datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_tags_name` (`name`),
  UNIQUE KEY `uniq_mci_tags_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cross-category descriptive tags (e.g. Open 24 Hours, Home Delivery)';


-- =============================================================================
-- 6. mci_user_auth_providers  (social login mappings)
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_user_auth_providers` (
  `id`                    char(36)      NOT NULL COMMENT 'UUID v4',
  `user_id`               char(36)      NOT NULL,
  `provider`              enum('google','facebook','apple','linkedin') NOT NULL,
  `provider_user_id`      varchar(255)  NOT NULL,
  `access_token`          text          DEFAULT NULL,
  `refresh_token`         text          DEFAULT NULL,
  `token_expires_at`      datetime(6)   DEFAULT NULL,
  `provider_email`        varchar(255)  DEFAULT NULL,
  `provider_profile_image` varchar(500) DEFAULT NULL,
  `created_at`            datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`            datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_user_auth_providers_provider_user` (`provider`, `provider_user_id`),
  UNIQUE KEY `uniq_mci_user_auth_providers_user_provider` (`user_id`, `provider`),
  KEY `idx_mci_user_auth_providers_user_id` (`user_id`),
  CONSTRAINT `fk_mci_user_auth_providers_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Maps local users to social login providers';


-- =============================================================================
-- 7. mci_subscription_packages
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_subscription_packages` (
  `id`              char(36)     NOT NULL COMMENT 'UUID v4',
  `package_name`    varchar(120) NOT NULL,
  `package_type`    enum('free','premium') NOT NULL DEFAULT 'free',
  `is_default`      tinyint(1)   NOT NULL DEFAULT 0,
  `status`          enum('active','coming_soon','disabled') NOT NULL DEFAULT 'active',
  `activation_date` datetime(6)  DEFAULT NULL,
  `expiry_date`     datetime(6)  DEFAULT NULL,
  `price`           decimal(10,2) NOT NULL DEFAULT 0.00,
  `features_json`   json         DEFAULT NULL,
  `created_at`      datetime(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`      datetime(6)  DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_subscription_packages_name` (`package_name`),
  KEY `idx_mci_subscription_packages_status_activation` (`status`, `activation_date`),
  KEY `idx_mci_subscription_packages_default_status` (`is_default`, `status`),
  KEY `idx_mci_subscription_packages_type` (`package_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subscription package definitions and feature policy';


-- =============================================================================
-- 8. mci_user_subscriptions
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_user_subscriptions` (
  `id`                      char(36)    NOT NULL COMMENT 'UUID v4',
  `user_id`                 char(36)    NOT NULL COMMENT 'FK → mci_users.id',
  `package_id`              char(36)    NOT NULL COMMENT 'FK → mci_subscription_packages.id',
  `subscription_start_date` datetime(6) NOT NULL,
  `subscription_end_date`   datetime(6) DEFAULT NULL,
  `subscription_status`     enum('active','inactive','expired','cancelled','pending_activation') NOT NULL DEFAULT 'active',
  `auto_assigned`           tinyint(1)  NOT NULL DEFAULT 1,
  `upgrade_source`          varchar(64) DEFAULT NULL,
  `created_at`              datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`              datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_user_subscriptions_user_package_status` (`user_id`, `package_id`, `subscription_status`),
  KEY `idx_mci_user_subscriptions_user_status` (`user_id`, `subscription_status`),
  KEY `idx_mci_user_subscriptions_package_id` (`package_id`),
  KEY `idx_mci_user_subscriptions_end_date` (`subscription_end_date`),
  CONSTRAINT `fk_mci_user_subscriptions_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_user_subscriptions_package_id`
    FOREIGN KEY (`package_id`) REFERENCES `mci_subscription_packages` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Current and historical package assignments for users';


-- =============================================================================
-- 9. mci_password_reset_tokens
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_password_reset_tokens` (
  `id`         char(36)     NOT NULL COMMENT 'UUID v4',
  `user_id`    char(36)     NOT NULL,
  `token_hash` varchar(64)  NOT NULL COMMENT 'SHA-256 hex of the one-time token',
  `expires_at` datetime(6)  NOT NULL,
  `used_at`    datetime(6)  DEFAULT NULL,
  `created_at` datetime(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_prt_token_hash` (`token_hash`),
  KEY `idx_mci_prt_user_id`    (`user_id`),
  KEY `idx_mci_prt_expires_at` (`expires_at`),
  CONSTRAINT `fk_mci_prt_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='One-time password reset tokens (email link flow)';


-- =============================================================================
-- 10. mci_category_requests
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_category_requests` (
  `id`                      int unsigned  NOT NULL AUTO_INCREMENT,
  `requester_id`            char(36)      NOT NULL,
  `requested_category_name` varchar(255)  NOT NULL,
  `reason`                  text          DEFAULT NULL,
  `status`                  varchar(16)   NOT NULL DEFAULT 'pending',
  `resolved_by_id`          char(36)      DEFAULT NULL,
  `created_at`              datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `resolved_at`             datetime(6)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mci_catreq_requester` (`requester_id`),
  KEY `idx_mci_catreq_status`    (`status`),
  CONSTRAINT `fk_mci_catreq_requester`
    FOREIGN KEY (`requester_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User-submitted requests for new categories';


-- =============================================================================
-- 11. mci_anon_business_submissions
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_anon_business_submissions` (
  `id`                  int unsigned  NOT NULL AUTO_INCREMENT,
  `submitted_by_role`   varchar(32)   NOT NULL,
  `submitted_by_user_id` char(36)     DEFAULT NULL,
  `payload_json`        longtext      NOT NULL,
  `title`               varchar(255)  DEFAULT NULL,
  `category`            varchar(255)  DEFAULT NULL,
  `status`              varchar(32)   NOT NULL DEFAULT 'pending',
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `resolved_by_id`      char(36)      DEFAULT NULL,
  `resolved_at`         datetime(6)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mci_anon_status`   (`status`),
  KEY `idx_mci_anon_category` (`category`),
  CONSTRAINT `fk_mci_anon_submitter`
    FOREIGN KEY (`submitted_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Anonymous business form submissions held for CP review';


-- =============================================================================
-- 12. mci_error_log
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_error_log` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `level`      enum('error','warning','exception','fatal') NOT NULL DEFAULT 'error',
  `message`    text            NOT NULL,
  `file`       varchar(512)    DEFAULT NULL,
  `line`       int unsigned    DEFAULT NULL,
  `uri`        varchar(1024)   DEFAULT NULL,
  `user_id`    char(36)        DEFAULT NULL COMMENT 'mci_users.id — null for anonymous',
  `ip`         varchar(45)     DEFAULT NULL,
  `context`    json            DEFAULT NULL,
  `created_at` datetime        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mci_error_log_level`      (`level`),
  KEY `idx_mci_error_log_created_at` (`created_at`),
  KEY `idx_mci_error_log_user_id`    (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Application-level error log';
