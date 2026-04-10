-- Migration 019: Add video_url column to mci_business_groups
-- Adds a YouTube/video embed URL field that was referenced in business_service.php
-- but missing from the original table definition.

ALTER TABLE `mci_business_groups`
  ADD COLUMN `video_url` varchar(512) DEFAULT NULL
    COMMENT 'Optional YouTube or video embed URL'
    AFTER `price_range`;
