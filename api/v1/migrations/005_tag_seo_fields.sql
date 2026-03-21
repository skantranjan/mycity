-- =============================================================================
-- SEO fields on mci_tags — run ONCE when upgrading an existing database.
-- Skip if 001 already includes page_title, meta_keywords, meta_description on mci_tags.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_tags`
  ADD COLUMN `page_title` varchar(255) DEFAULT NULL COMMENT 'SEO: HTML <title>' AFTER `name`,
  ADD COLUMN `meta_keywords` varchar(512) DEFAULT NULL COMMENT 'SEO: meta keywords' AFTER `page_title`,
  ADD COLUMN `meta_description` varchar(512) DEFAULT NULL COMMENT 'SEO: meta description' AFTER `meta_keywords`;
