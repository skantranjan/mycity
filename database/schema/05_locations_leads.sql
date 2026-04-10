-- =============================================================================
-- My City Info — Locations & Leads Tables
-- File: database/schema/05_locations_leads.sql
--
-- Creates:
--   mci_locations  — soft-reference city lookup (no FK to branches)
--   mci_leads      — contact form submissions (leads + enquiries) per business
--
-- Run order: after 04_scraper_tables.sql
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- =============================================================================
-- 1. mci_locations
--    Soft-reference lookup table for country/state/city.
--    Used by the city autocomplete and location_service.php.
--    No FK constraint to mci_business_branches — intentionally loose.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_locations` (
  `id`       int unsigned  NOT NULL AUTO_INCREMENT,
  `country`  varchar(100)  NOT NULL,
  `state`    varchar(100)  NOT NULL DEFAULT '',
  `city`     varchar(100)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_locations_country_state_city` (`country`, `state`, `city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Soft-reference lookup table for country/state/city. No FK to branches.';


-- =============================================================================
-- 2. mci_leads
--    Stores contact form submissions sent to a business listing.
--    type = 'lead'     → full contact form (phone + email + message)
--    type = 'enquiry'  → quick-contact form (email + message, no phone required)
--    Visible to the listing owner in /subscriber/leads/ and /subscriber/enquiries/.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_leads` (
  `id`                char(36)      NOT NULL                         COMMENT 'UUID v4',
  `business_group_id` char(36)      NOT NULL                         COMMENT 'FK → mci_business_groups.id',
  `type`              enum('lead','enquiry') NOT NULL DEFAULT 'lead'  COMMENT 'lead = full contact; enquiry = quick message',
  `sender_name`       varchar(160)  NOT NULL DEFAULT '',
  `sender_phone`      varchar(40)   NOT NULL DEFAULT '',
  `sender_email`      varchar(254)  NOT NULL DEFAULT '',
  `message`           text          NOT NULL,
  `status`            enum('new','contacted','converted','closed','replied') NOT NULL DEFAULT 'new',
  `created_at`        datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leads_business`    (`business_group_id`),
  KEY `idx_leads_type_status` (`type`, `status`),
  KEY `idx_leads_created`     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Contact form submissions (leads + enquiries) per business listing.';
