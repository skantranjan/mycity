-- =============================================================================
-- 013 — Business Reviews
-- Stores star ratings + written reviews per user per business.
-- History is preserved: every edit inserts a new row in mci_business_review_history.
-- =============================================================================

SET NAMES utf8mb4;

-- Active/current review per user per business (one row per user+business pair)
CREATE TABLE IF NOT EXISTS `mci_business_reviews` (
  `id`                char(36)     NOT NULL                        COMMENT 'UUID v4',
  `business_group_id` char(36)     NOT NULL                        COMMENT 'FK → mci_business_groups.id',
  `user_id`           char(36)     NOT NULL                        COMMENT 'FK → mci_users.id',
  `rating`            tinyint      NOT NULL                        COMMENT '1–5 stars',
  `review_text`       text         NOT NULL,
  `created_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_business_review` (`user_id`, `business_group_id`),
  KEY `idx_reviews_business` (`business_group_id`),
  KEY `idx_reviews_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Current review (latest edit) per user per business';

-- Full edit history: every add or update appends a row here
CREATE TABLE IF NOT EXISTS `mci_business_review_history` (
  `id`                char(36)     NOT NULL                        COMMENT 'UUID v4',
  `review_id`         char(36)     NOT NULL                        COMMENT 'FK → mci_business_reviews.id',
  `business_group_id` char(36)     NOT NULL,
  `user_id`           char(36)     NOT NULL,
  `rating`            tinyint      NOT NULL,
  `review_text`       text         NOT NULL,
  `created_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_review_history_review` (`review_id`),
  KEY `idx_review_history_business` (`business_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Full history of all review edits';
