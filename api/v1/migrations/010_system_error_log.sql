-- =============================================================================
-- Migration 010: System error log table
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_error_log` (
  `id`           bigint unsigned NOT NULL AUTO_INCREMENT,
  `level`        enum('error','warning','exception','fatal') NOT NULL DEFAULT 'error',
  `message`      text NOT NULL,
  `file`         varchar(512) DEFAULT NULL,
  `line`         int unsigned DEFAULT NULL,
  `uri`          varchar(1024) DEFAULT NULL,
  `user_id`      char(36) DEFAULT NULL   COMMENT 'mci_users.id — null for anonymous / unauthenticated',
  `ip`           varchar(45) DEFAULT NULL,
  `context`      json DEFAULT NULL       COMMENT 'extra structured data (stack trace excerpt, etc.)',
  `created_at`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mci_error_log_level`      (`level`),
  KEY `idx_mci_error_log_created_at` (`created_at`),
  KEY `idx_mci_error_log_user_id`    (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Application-level error log written by mci_error_handler.php';
