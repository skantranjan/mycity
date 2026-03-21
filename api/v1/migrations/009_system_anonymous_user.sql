-- =============================================================================
-- Migration 009: System anonymous user — My City Info (API v1)
-- Run after: 008_business_group_branch_schema.sql
--
-- Adds a 'system_anonymous' role and inserts a fixed sentinel user row that
-- represents all anonymous business submissions. This allows mci_business_groups
-- .added_by_user_id to be NOT NULL at all times, while still distinguishing
-- anonymous submissions via added_by_role = 'anonymous'.
--
-- The anonymous user:
--   UUID  : 00000000-0000-0000-0000-000000000000  (well-known nil UUID)
--   Email : anonymous@system.internal              (internal, never receives mail)
--   Role  : system_anonymous                       (short_name used in app logic)
--   Status: inactive                               (cannot log in)
--
-- Application rules:
--   • When added_by_role = 'anonymous', set added_by_user_id to the nil UUID above.
--   • When added_by_role = 'cp_admin' or 'subscriber', set added_by_user_id to the
--     actual authenticated user's UUID. Never use the nil UUID for real users.
--   • The system_anonymous user must never appear in user-facing lists or counts.
--     Filter with: WHERE id != '00000000-0000-0000-0000-000000000000'
--     or:          WHERE role_id != (SELECT id FROM mci_roles WHERE short_name = 'system_anonymous')
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- -----------------------------------------------------------------------------
-- 1) Add system_anonymous role
-- -----------------------------------------------------------------------------
INSERT INTO `mci_roles` (`name`, `short_name`) VALUES
  ('System Anonymous', 'system_anonymous')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);


-- -----------------------------------------------------------------------------
-- 2) Insert the system anonymous sentinel user
--    password_hash is a bcrypt hash of a random unguessable string — this
--    account can never be logged into (status = inactive, no reset allowed).
-- -----------------------------------------------------------------------------
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
  -- bcrypt hash of a random 64-char string; account is inactive and cannot log in
  '$2y$12$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
  'Anonymous',
  'inactive',
  CURRENT_TIMESTAMP(6)
FROM `mci_roles`
WHERE `short_name` = 'system_anonymous'
LIMIT 1
-- Skip silently if already seeded (re-runnable)
ON DUPLICATE KEY UPDATE `display_name` = VALUES(`display_name`);
