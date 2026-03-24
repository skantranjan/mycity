-- =============================================================================
-- My City Info — Migration 014: Scraper tables + data_source column
--
-- Changes:
--   1. ALTER mci_business_groups: add data_source enum column
--   2. CREATE mci_scraped_businesses (staging area for scraped data)
--   3. CREATE mci_scraper_usage (monthly API usage tracking per source)
--
-- Run order: after 013_business_reviews.sql
-- =============================================================================

SET NAMES utf8mb4;

-- =============================================================================
-- 1. Add data_source column to mci_business_groups
--    Tracks HOW the listing was created (complements added_by_role = WHO)
-- =============================================================================
ALTER TABLE `mci_business_groups`
  ADD COLUMN `data_source` enum(
    'manual_cp',            -- admin typed it manually via /cp/anonymous-business/
    'scrape_osm',           -- imported from OpenStreetMap via scraper
    'scrape_google',        -- imported from Google Places via scraper
    'scrape_tomtom',        -- imported from TomTom Places via scraper
    'scrape_here',          -- imported from HERE Places via scraper
    'scrape_html',          -- imported via cURL/HTML scraping
    'user_submission',      -- submitted by a registered subscriber
    'anonymous_submission'  -- submitted via public anonymous form
  ) DEFAULT NULL
    COMMENT 'Origin method — HOW the listing was created. Complements added_by_role (WHO).'
  AFTER `added_by_role`;

-- Backfill existing rows based on added_by_role
UPDATE `mci_business_groups` SET `data_source` = 'manual_cp'           WHERE `added_by_role` = 'cp_admin'   AND `data_source` IS NULL;
UPDATE `mci_business_groups` SET `data_source` = 'user_submission'     WHERE `added_by_role` = 'subscriber'  AND `data_source` IS NULL;
UPDATE `mci_business_groups` SET `data_source` = 'anonymous_submission' WHERE `added_by_role` = 'anonymous'  AND `data_source` IS NULL;


-- =============================================================================
-- 2. mci_scraped_businesses
--    Staging area for scraped business data. Completely isolated from main
--    business tables until an admin explicitly imports a record.
--    Rows are DELETED after successful import (no accumulation).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_scraped_businesses` (
  `id`                  char(36)      NOT NULL                             COMMENT 'UUID v4',
  `source`              varchar(32)   NOT NULL                             COMMENT 'osm | tomtom | here | google_places | curl_scrape',
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

  -- Full payload shaped for import (uses actual DB column names from migration 008)
  `payload_json`        longtext      NOT NULL                             COMMENT 'JSON: all data needed to import into main tables',

  -- Workflow status
  `status`              enum('pending_review','rejected','imported')
                                      NOT NULL DEFAULT 'pending_review',
  `rejection_reason`    varchar(512)  DEFAULT NULL,
  `reviewed_by_user_id` char(36)      DEFAULT NULL                         COMMENT 'FK → mci_users.id',
  `reviewed_at`         datetime(6)   DEFAULT NULL,

  -- Set after successful import (row is deleted immediately after, but logged here first)
  `imported_group_id`   char(36)      DEFAULT NULL                         COMMENT 'FK → mci_business_groups.id (set before deletion)',
  `imported_at`         datetime(6)   DEFAULT NULL,

  -- Audit
  `scraped_by_user_id`  char(36)      DEFAULT NULL                         COMMENT 'FK → mci_users.id; CP admin who triggered the scrape',
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  KEY `idx_mci_scraped_status`    (`status`),
  KEY `idx_mci_scraped_source`    (`source`),
  KEY `idx_mci_scraped_city`      (`city`),
  KEY `idx_mci_scraped_name`      (`name`),
  UNIQUE KEY `uniq_mci_scraped_source_id` (`source`, `source_id`),

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
--    Tracks monthly API call counts per scraping source.
--    Used to enforce rate limits and show usage on the scraper dashboard.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_scraper_usage` (
  `id`             int unsigned  NOT NULL AUTO_INCREMENT,
  `source`         varchar(32)   NOT NULL                                  COMMENT 'osm | tomtom | here | google_places | curl_scrape',
  `year_month`     char(7)       NOT NULL                                  COMMENT 'Format: YYYY-MM e.g. 2026-03',
  `call_count`     int unsigned  NOT NULL DEFAULT 0                        COMMENT 'Number of API/search calls made this month',
  `results_count`  int unsigned  NOT NULL DEFAULT 0                        COMMENT 'Total business records fetched (including duplicates) this month',
  `created_at`     datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`     datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_scraper_usage_source_month` (`source`, `year_month`),
  KEY `idx_mci_scraper_usage_source` (`source`),
  KEY `idx_mci_scraper_usage_month`  (`year_month`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Monthly API usage counters per scraping source. Used for limit enforcement and display.';
