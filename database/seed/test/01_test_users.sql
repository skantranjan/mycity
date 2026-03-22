-- =============================================================================
-- My City Info — Test Data: Users
-- File: database/seed/test/01_test_users.sql
--
-- Creates development/test user accounts.
-- DO NOT run on production databases.
-- Idempotent: uses WHERE NOT EXISTS so rows are skipped if they already exist.
--
-- Accounts created:
--   Subscribers (password: DevSubscriber123!)
--     subscriber1.dev@mycityinfo.local  (id: e0000000-0000-4000-8000-000000000001)
--     subscriber2.dev@mycityinfo.local  (id: e0000000-0000-4000-8000-000000000002)
--     subscriber3.dev@mycityinfo.local  (id: e0000000-0000-4000-8000-000000000003)
--
--   Super Admins (password: DevSuperAdmin123!)
--     superadmin.dev@mycityinfo.local   (id: e0000000-0000-4000-8000-000000000010)
--     superadmin2.dev@mycityinfo.local  (id: e0000000-0000-4000-8000-000000000011)
--
-- Run order: after master seed files (01_roles_and_system_user.sql must be run first)
-- =============================================================================

SET NAMES utf8mb4;


-- =============================================================================
-- Subscribers  (password: DevSubscriber123!)
-- =============================================================================

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000001',
  'subscriber1.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 1',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber1.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000002',
  'subscriber2.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 2',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber2.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000003',
  'subscriber3.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'subscriber' LIMIT 1),
  '$2y$10$Ge7LLLmgVH0KRwRDror9K.lkjnac0CPYOOJU1Cs1A01r/xPOphIvm',
  'Dev Subscriber 3',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'subscriber3.dev@mycityinfo.local');


-- =============================================================================
-- Super Admins  (password: DevSuperAdmin123!)
-- =============================================================================

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000010',
  'superadmin.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'super_admin' LIMIT 1),
  '$2y$10$9bk6i8pYBDNETWVAA5l5gOX4SL7rXHQ/PdVazBdRQYiV/i2YM7x3O',
  'Dev Super Admin',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'superadmin.dev@mycityinfo.local');

INSERT INTO `mci_users` (`id`, `email`, `role_id`, `password_hash`, `display_name`, `password_changed_at`)
SELECT
  'e0000000-0000-4000-8000-000000000011',
  'superadmin2.dev@mycityinfo.local',
  (SELECT id FROM mci_roles WHERE short_name = 'super_admin' LIMIT 1),
  '$2y$10$9bk6i8pYBDNETWVAA5l5gOX4SL7rXHQ/PdVazBdRQYiV/i2YM7x3O',
  'Dev Super Admin 2',
  NOW(6)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mci_users WHERE email = 'superadmin2.dev@mycityinfo.local');
