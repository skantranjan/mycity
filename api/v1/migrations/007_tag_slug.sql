-- =============================================================================
-- Unique slug on mci_tags (like mci_categories). Run ONCE on existing DBs.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_tags`
  ADD COLUMN `slug` varchar(255) NULL COMMENT 'Globally unique (URLs, APIs)' AFTER `name`;

UPDATE `mci_tags` SET `slug` = CONCAT('tag-', `id`) WHERE `slug` IS NULL OR TRIM(`slug`) = '';

ALTER TABLE `mci_tags`
  MODIFY COLUMN `slug` varchar(255) NOT NULL;

ALTER TABLE `mci_tags`
  ADD UNIQUE KEY `uniq_mci_tags_slug` (`slug`);
