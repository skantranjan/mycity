-- Migration 020: Fix mci_business_branches and mci_business_branch_hours schema gaps
--
-- Problems fixed:
--   1. mci_business_branches: missing `website` column (PHP inserts branch['website'])
--   2. mci_business_branch_hours: missing `opens_at_2`/`closes_at_2` for split-shift support
--   3. mci_business_faqs: `answer` is NOT NULL but PHP may pass NULL — relax to DEFAULT ''

-- 1) Add website column to branches
ALTER TABLE `mci_business_branches`
  ADD COLUMN `website` varchar(512) DEFAULT NULL
    COMMENT 'Branch-specific website URL (overrides group website if set)'
    AFTER `whatsapp_number`;

-- 2) Add split-shift columns to branch hours
ALTER TABLE `mci_business_branch_hours`
  ADD COLUMN `opens_at_2`  time DEFAULT NULL COMMENT 'Second slot open time (split shift); NULL if no second slot'  AFTER `closes_at`,
  ADD COLUMN `closes_at_2` time DEFAULT NULL COMMENT 'Second slot close time (split shift); NULL if no second slot' AFTER `opens_at_2`;

-- 3) Relax FAQ answer to allow empty string default (PHP sends null when blank)
ALTER TABLE `mci_business_faqs`
  MODIFY COLUMN `answer` text NOT NULL DEFAULT '';
