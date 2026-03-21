-- =============================================================================
-- Subcategories for mci_categories — run ONCE on DBs created from older 001.
-- Fresh installs: use updated 001 (already includes parent_id / sort_order).
--
-- MySQL 8+ (uses ROW_NUMBER for slug dedupe).
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `mci_categories`
  ADD COLUMN `parent_id` int unsigned DEFAULT NULL COMMENT 'NULL = top-level category' AFTER `id`,
  ADD COLUMN `sort_order` int unsigned NOT NULL DEFAULT 0 COMMENT 'Order among siblings' AFTER `slug`;

UPDATE `mci_categories` SET `slug` = CONCAT('category-', `id`) WHERE `slug` IS NULL OR TRIM(`slug`) = '';

-- Resolve duplicate slugs (keep smallest id per slug)
UPDATE `mci_categories` c
JOIN (
  SELECT `id`,
         ROW_NUMBER() OVER (PARTITION BY `slug` ORDER BY `id`) AS rn
  FROM `mci_categories`
) t ON t.`id` = c.`id` AND t.rn > 1
SET c.`slug` = CONCAT(c.`slug`, '-', c.`id`);

ALTER TABLE `mci_categories` DROP INDEX `uniq_mci_categories_name`;

-- Old schema had a non-unique index on slug; drop before adding UNIQUE (ignore error if name differs).
ALTER TABLE `mci_categories` DROP INDEX `idx_mci_categories_slug`;

ALTER TABLE `mci_categories` MODIFY COLUMN `slug` varchar(255) NOT NULL;

ALTER TABLE `mci_categories` ADD UNIQUE KEY `uniq_mci_categories_slug` (`slug`);

ALTER TABLE `mci_categories`
  ADD KEY `idx_mci_categories_parent` (`parent_id`),
  ADD KEY `idx_mci_categories_name` (`name`),
  ADD CONSTRAINT `fk_mci_categories_parent`
    FOREIGN KEY (`parent_id`) REFERENCES `mci_categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;
