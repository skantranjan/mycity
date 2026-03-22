-- =============================================================================
-- My City Info — User Feature Tables
-- File: database/schema/03_user_features.sql
--
-- Creates user-interaction tables:
--   mci_user_favourites       — saved/favourite business listings per user
--   mci_business_reviews      — current review per user per business group
--   mci_business_review_history — full edit history of every review
--
-- Run order: after 02_business_tables.sql
--
-- NOTE: mci_business_reviews here is the GROUP-LEVEL review table (migration 013).
-- It is separate from the BRANCH-LEVEL mci_business_reviews in 02_business_tables.sql.
-- If you run 02_business_tables.sql first, this file will overwrite that table
-- definition with the simpler group-level version. Run both files; the last
-- CREATE TABLE IF NOT EXISTS wins only if the table doesn't already exist.
-- To avoid conflicts, see README for the recommended run order.
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- =============================================================================
-- 1. mci_user_favourites
--    Saved/favourite business listings per user.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_user_favourites` (
  `id`                char(36)    NOT NULL                        COMMENT 'UUID v4',
  `user_id`           char(36)    NOT NULL                        COMMENT 'FK → mci_users.id',
  `business_group_id` char(36)    NOT NULL                        COMMENT 'FK → mci_business_groups.id',
  `created_at`        datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_business`          (`user_id`, `business_group_id`),
  KEY        `idx_user_favourites_user`    (`user_id`),
  KEY        `idx_user_favourites_business`(`business_group_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Businesses saved as favourites by subscribers';


-- =============================================================================
-- 2. mci_business_reviews  (group-level — from migration 013)
--    Current/active review per user per business group.
--    One row per user+business pair. Edits update this row in-place;
--    full history is preserved in mci_business_review_history.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_reviews` (
  `id`                char(36)     NOT NULL                        COMMENT 'UUID v4',
  `business_group_id` char(36)     NOT NULL                        COMMENT 'FK → mci_business_groups.id',
  `user_id`           char(36)     NOT NULL                        COMMENT 'FK → mci_users.id',
  `rating`            tinyint      NOT NULL                        COMMENT '1–5 stars',
  `review_text`       text         NOT NULL,
  `created_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_business_review`  (`user_id`, `business_group_id`),
  KEY        `idx_reviews_business`       (`business_group_id`),
  KEY        `idx_reviews_user`           (`user_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Current review (latest edit) per user per business group';


-- =============================================================================
-- 3. mci_business_review_history
--    Full edit history: every add or update appends a row here.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_review_history` (
  `id`                char(36)     NOT NULL                        COMMENT 'UUID v4',
  `review_id`         char(36)     NOT NULL                        COMMENT 'FK → mci_business_reviews.id',
  `business_group_id` char(36)     NOT NULL,
  `user_id`           char(36)     NOT NULL,
  `rating`            tinyint      NOT NULL,
  `review_text`       text         NOT NULL,
  `created_at`        datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_review_history_review`   (`review_id`),
  KEY `idx_review_history_business` (`business_group_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Full history of all review edits (append-only)';
