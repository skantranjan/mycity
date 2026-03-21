-- =============================================================================
-- Password reset tokens + one social provider per user per platform
-- Run after: 001_create_core_tables.sql
-- If ALTER fails due to duplicate (user_id, provider), dedupe rows first.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_password_reset_tokens` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 hex of raw token',
  `expires_at` datetime(6) NOT NULL,
  `used_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_pwd_reset_token_hash` (`token_hash`),
  KEY `idx_mci_pwd_reset_user` (`user_id`),
  KEY `idx_mci_pwd_reset_expires` (`expires_at`),
  CONSTRAINT `fk_mci_pwd_reset_user`
    FOREIGN KEY (`user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='One-time password reset tokens (email link flow)';

ALTER TABLE `mci_user_auth_providers`
  ADD UNIQUE KEY `uniq_mci_uap_user_provider` (`user_id`, `provider`);
