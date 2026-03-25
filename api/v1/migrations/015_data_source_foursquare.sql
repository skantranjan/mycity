-- =============================================================================
-- My City Info — Migration 015: Add scrape_foursquare to data_source enum
--
-- Changes:
--   1. ALTER mci_business_groups.data_source enum to include 'scrape_foursquare'
--
-- Run order: after 014_scraper_tables.sql
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_business_groups`
  MODIFY COLUMN `data_source` enum(
    'manual_cp',            -- admin typed it manually via /cp/anonymous-business/
    'scrape_osm',           -- imported from OpenStreetMap via scraper
    'scrape_google',        -- imported from Google Places via scraper
    'scrape_tomtom',        -- imported from TomTom Places via scraper
    'scrape_here',          -- imported from HERE Places via scraper
    'scrape_foursquare',    -- imported from Foursquare Places via scraper
    'scrape_html',          -- imported via cURL/HTML scraping
    'user_submission',      -- submitted by a registered subscriber
    'anonymous_submission'  -- submitted via public anonymous form
  ) DEFAULT NULL
    COMMENT 'Origin method — HOW the listing was created. Complements added_by_role (WHO).';
