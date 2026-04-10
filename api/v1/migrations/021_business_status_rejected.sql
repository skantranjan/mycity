-- =============================================================================
-- Migration 021: Add 'rejected' to business group status enum
--
-- The mci_business_groups.status enum was missing 'rejected', causing the
-- reject action to fail silently. Also extends mci_business_approvals
-- previous_status / new_status enums to include 'rejected'.
-- =============================================================================

ALTER TABLE `mci_business_groups`
  MODIFY COLUMN `status`
    enum('live','draft','suspended','deleted','rejected')
    NOT NULL DEFAULT 'live';

ALTER TABLE `mci_business_approvals`
  MODIFY COLUMN `previous_status`
    enum('live','draft','suspended','deleted','rejected')
    DEFAULT NULL,
  MODIFY COLUMN `new_status`
    enum('live','draft','suspended','deleted','rejected')
    NOT NULL;
