-- =============================================================================
-- My City Info — Migration 016: URL Import Jobs table
--
-- Changes:
--   1. CREATE mci_url_import_jobs — tracks URL-list and crawler import jobs
--
-- Run order: after 015_data_source_foursquare.sql
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_url_import_jobs` (
  `id`                   char(36)      NOT NULL                      COMMENT 'UUID v4',
  `mode`                 enum('url_list','crawler') NOT NULL         COMMENT 'url_list = paste URLs, crawler = discover from index page',
  `created_by_user_id`   char(36)      NOT NULL                      COMMENT 'FK → mci_users.id',

  -- Input config stored as JSON
  `config_json`          text          NOT NULL                      COMMENT 'JSON: {urls:[...]} or {index_url, pattern, limit}',

  -- Progress
  `status`               enum('pending','running','done','failed') NOT NULL DEFAULT 'pending',
  `total_urls`           smallint unsigned NOT NULL DEFAULT 0,
  `processed_urls`       smallint unsigned NOT NULL DEFAULT 0,
  `inserted_count`       smallint unsigned NOT NULL DEFAULT 0,
  `skipped_count`        smallint unsigned NOT NULL DEFAULT 0,

  -- Per-URL log (JSON array appended incrementally)
  `log_json`             longtext      DEFAULT NULL                  COMMENT 'JSON array: [{url, status, names[], error}]',
  `error_message`        varchar(512)  DEFAULT NULL,

  `started_at`           datetime(6)   DEFAULT NULL,
  `finished_at`          datetime(6)   DEFAULT NULL,
  `created_at`           datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  KEY `idx_url_import_status`  (`status`),
  KEY `idx_url_import_creator` (`created_by_user_id`),

  CONSTRAINT `fk_url_import_creator`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tracks URL-list and directory-crawler import jobs.';
