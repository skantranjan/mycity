-- =============================================================================
-- Optional description text on categories (incl. subcategories) and tags.
-- Run ONCE when upgrading a DB created before 001 included `description`.
-- Safe to run if columns already exist (MySQL 8+): use separate checks or ignore duplicate column errors.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_categories`
  ADD COLUMN `description` text DEFAULT NULL COMMENT 'Optional editorial / display description (not the SEO meta)' AFTER `meta_description`;

ALTER TABLE `mci_tags`
  ADD COLUMN `description` text DEFAULT NULL COMMENT 'Optional editorial / display description' AFTER `meta_description`;
