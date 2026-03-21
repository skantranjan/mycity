-- =============================================================================
-- SEO fields on mci_categories — run ONCE when upgrading an existing database.
-- Skip if you already applied the updated 001_create_core_tables.sql that
-- includes page_title, meta_keywords, meta_description (duplicate column error).
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_categories`
  ADD COLUMN `page_title` varchar(255) DEFAULT NULL COMMENT 'SEO: HTML <title>' AFTER `sort_order`,
  ADD COLUMN `meta_keywords` varchar(512) DEFAULT NULL COMMENT 'SEO: meta keywords' AFTER `page_title`,
  ADD COLUMN `meta_description` varchar(512) DEFAULT NULL COMMENT 'SEO: meta description' AFTER `meta_keywords`;
