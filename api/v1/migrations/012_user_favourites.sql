-- =============================================================================
-- 012 — User Favourites
-- Stores saved/favourite business listings per user.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_user_favourites` (
  `id`                char(36)    NOT NULL                        COMMENT 'UUID v4',
  `user_id`           char(36)    NOT NULL                        COMMENT 'FK → mci_users.id',
  `business_group_id` char(36)    NOT NULL                        COMMENT 'FK → mci_business_groups.id',
  `created_at`        datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_business` (`user_id`, `business_group_id`),
  KEY `idx_user_favourites_user` (`user_id`),
  KEY `idx_user_favourites_business` (`business_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Businesses saved as favourites by subscribers';
