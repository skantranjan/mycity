-- =============================================================================
-- My City Info — Master Seed: Roles & System User
-- File: database/seed/master/01_roles_and_system_user.sql
--
-- Inserts the four application roles and the system anonymous sentinel user.
-- Idempotent: safe to run multiple times (ON DUPLICATE KEY UPDATE / WHERE NOT EXISTS).
--
-- Run order: after all schema files have been applied.
-- =============================================================================

SET NAMES utf8mb4;


-- =============================================================================
-- 1. Application roles
-- =============================================================================
INSERT INTO `mci_roles` (`name`, `short_name`) VALUES
  ('Subscriber',        'subscriber'),
  ('Super Admin',       'super_admin'),
  ('Co Admin',          'co_admin'),
  ('System Anonymous',  'system_anonymous')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);


-- =============================================================================
-- 2. System anonymous sentinel user
--    UUID  : 00000000-0000-0000-0000-000000000000  (nil UUID — well-known)
--    Email : anonymous@system.internal              (internal, never receives mail)
--    Status: inactive                               (cannot log in)
--
--    Usage: set mci_business_groups.added_by_user_id to this UUID when
--    added_by_role = 'anonymous'. Never use this UUID for real user accounts.
--    Filter it out in user-facing lists with:
--      WHERE id != '00000000-0000-0000-0000-000000000000'
-- =============================================================================
INSERT INTO `mci_users` (
  `id`,
  `email`,
  `role_id`,
  `password_hash`,
  `display_name`,
  `status`,
  `created_at`
)
SELECT
  '00000000-0000-0000-0000-000000000000',
  'anonymous@system.internal',
  `id`,
  -- bcrypt hash of a random unguessable string; account is inactive and cannot log in
  '$2y$12$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
  'Anonymous',
  'inactive',
  CURRENT_TIMESTAMP(6)
FROM `mci_roles`
WHERE `short_name` = 'system_anonymous'
LIMIT 1
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);
