-- =============================================================================
-- My City Info — Scraper & Import Tables
-- File: database/schema/04_scraper_tables.sql
--
-- Creates scraper and import-related tables:
--   mci_scraped_businesses  — staging area for external data (deleted after import)
--   mci_scraper_usage       — monthly API call counters per source
--   mci_url_import_jobs     — URL-list and crawler import job tracking
--
-- Also adds the data_source column to mci_business_groups.
--
-- Run order: after 03_user_features.sql
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- =============================================================================
-- 1. Add data_source column to mci_business_groups
--    Tracks HOW the listing was created (complements added_by_role = WHO).
--    Uses IF NOT EXISTS via procedure to stay idempotent.
-- =============================================================================
ALTER TABLE `mci_business_groups`
  ADD COLUMN IF NOT EXISTS `data_source` enum(
    'manual_cp',
    'scrape_osm',
    'scrape_google',
    'scrape_tomtom',
    'scrape_here',
    'scrape_foursquare',
    'scrape_html',
    'user_submission',
    'anonymous_submission'
  ) DEFAULT NULL
    COMMENT 'Origin method — HOW the listing was created. Complements added_by_role (WHO).'
  AFTER `added_by_role`;


-- =============================================================================
-- 2. mci_scraped_businesses
--    Staging area for scraped business data. Completely isolated from main
--    business tables until a CP admin explicitly imports a record.
--    Rows are DELETED after successful import.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_scraped_businesses` (
  `id`                  char(36)      NOT NULL                             COMMENT 'UUID v4',
  `source`              varchar(32)   NOT NULL                             COMMENT 'osm | tomtom | here | google_places | foursquare | curl_scrape',
  `source_id`           varchar(255)  DEFAULT NULL                         COMMENT 'External ID for dedup; md5(name|city|address) for curl fallback',
  `source_url`          varchar(1024) DEFAULT NULL                         COMMENT 'URL of the external listing or API endpoint used',
  `query_params`        text          DEFAULT NULL                         COMMENT 'JSON: search params used to produce this result',

  -- Denormalised display columns (source of truth is payload_json)
  `name`                varchar(255)  NOT NULL,
  `category_hint`       varchar(255)  DEFAULT NULL                         COMMENT 'Raw category/type string from source (not yet mapped to mci_categories)',
  `types_raw`           text          DEFAULT NULL                         COMMENT 'JSON array: all source type tags (for tag auto-matching)',
  `city`                varchar(128)  DEFAULT NULL,
  `phone`               varchar(64)   DEFAULT NULL,
  `website`             varchar(512)  DEFAULT NULL,
  `address`             varchar(512)  DEFAULT NULL,
  `latitude`            decimal(10,7) DEFAULT NULL,
  `longitude`           decimal(10,7) DEFAULT NULL,

  -- Full payload shaped for import (uses actual DB column names)
  `payload_json`        longtext      NOT NULL                             COMMENT 'JSON: all data needed to import into main tables',

  -- Workflow
  `status`              enum('pending_review','rejected','imported')
                                      NOT NULL DEFAULT 'pending_review',
  `rejection_reason`    varchar(512)  DEFAULT NULL,
  `reviewed_by_user_id` char(36)      DEFAULT NULL                         COMMENT 'FK → mci_users.id',
  `reviewed_at`         datetime(6)   DEFAULT NULL,

  -- Set immediately before row is deleted on successful import
  `imported_group_id`   char(36)      DEFAULT NULL                         COMMENT 'FK → mci_business_groups.id',
  `imported_at`         datetime(6)   DEFAULT NULL,

  -- Audit
  `scraped_by_user_id`  char(36)      DEFAULT NULL                         COMMENT 'FK → mci_users.id; CP admin who triggered the scrape',
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_scraped_source_id` (`source`, `source_id`),
  KEY `idx_mci_scraped_status`    (`status`),
  KEY `idx_mci_scraped_source`    (`source`),
  KEY `idx_mci_scraped_city`      (`city`),
  KEY `idx_mci_scraped_name`      (`name`),

  CONSTRAINT `fk_mci_scraped_reviewer`
    FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_scraped_scraper`
    FOREIGN KEY (`scraped_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Staging area for scraped business data. Rows deleted after successful import.';


-- =============================================================================
-- 3. mci_scraper_usage
--    Monthly API call and result counters per scraping source.
--    Used for rate-limit enforcement and dashboard display.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_scraper_usage` (
  `id`             int unsigned  NOT NULL AUTO_INCREMENT,
  `source`         varchar(32)   NOT NULL                                  COMMENT 'osm | tomtom | here | google_places | foursquare | curl_scrape',
  `year_month`     char(7)       NOT NULL                                  COMMENT 'Format: YYYY-MM e.g. 2026-03',
  `call_count`     int unsigned  NOT NULL DEFAULT 0                        COMMENT 'API/search calls made this month',
  `results_count`  int unsigned  NOT NULL DEFAULT 0                        COMMENT 'Total records fetched (including duplicates) this month',
  `created_at`     datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`     datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_scraper_usage_source_month` (`source`, `year_month`),
  KEY `idx_mci_scraper_usage_source` (`source`),
  KEY `idx_mci_scraper_usage_month`  (`year_month`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Monthly API usage counters per scraping source.';


-- =============================================================================
-- 4. mci_url_import_jobs
--    Tracks URL-list and directory-crawler import jobs.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_url_import_jobs` (
  `id`                   char(36)      NOT NULL                      COMMENT 'UUID v4',
  `mode`                 enum('url_list','crawler') NOT NULL         COMMENT 'url_list = paste URLs; crawler = discover from index page',
  `created_by_user_id`   char(36)      NOT NULL                      COMMENT 'FK → mci_users.id',

  `config_json`          text          NOT NULL                      COMMENT 'JSON: {urls:[...]} or {index_url, pattern, limit}',

  `status`               enum('pending','running','done','failed') NOT NULL DEFAULT 'pending',
  `total_urls`           smallint unsigned NOT NULL DEFAULT 0,
  `processed_urls`       smallint unsigned NOT NULL DEFAULT 0,
  `inserted_count`       smallint unsigned NOT NULL DEFAULT 0,
  `skipped_count`        smallint unsigned NOT NULL DEFAULT 0,

  `log_json`             longtext      DEFAULT NULL                  COMMENT 'JSON array: [{url, status, names[], error}] — appended incrementally',
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
