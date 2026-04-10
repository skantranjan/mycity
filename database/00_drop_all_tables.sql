-- =============================================================================
-- My City Info — Drop All Tables
-- File: database/00_drop_all_tables.sql
--
-- Drops every application table in reverse dependency order so FK constraints
-- are never violated. Run this BEFORE re-creating the schema from scratch.
--
-- WARNING: ALL DATA WILL BE LOST. Do NOT run on production.
--
-- Usage:
--   mysql -u <user> -p <database> < database/00_drop_all_tables.sql
--
-- Full reset sequence:
--   1. mysql … < database/00_drop_all_tables.sql
--   2. mysql … < database/schema/01_core_tables.sql
--   3. mysql … < database/schema/02_business_tables.sql
--   4. mysql … < database/schema/03_user_features.sql
--   5. mysql … < database/schema/04_scraper_tables.sql
--   6. mysql … < database/schema/05_locations_leads.sql
--   7. mysql … < database/seed/master/01_roles_and_system_user.sql
--   8. mysql … < database/seed_categories.sql
--   9. mysql … < database/seed_tags.sql
--  10. mysql … < database/seed/master/04_locations.sql    (locations seed)
--  [DEV ONLY]
--  11. mysql … < database/seed/test/01_test_users.sql
--  12. mysql … < database/seed/test/02_test_businesses.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Scraper / Import ──────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mci_url_import_jobs`;
DROP TABLE IF EXISTS `mci_scraper_usage`;
DROP TABLE IF EXISTS `mci_scraped_businesses`;

-- ── Leads & Enquiries ─────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mci_leads`;

-- ── Locations lookup ──────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mci_locations`;

-- ── User feature tables ───────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mci_business_review_history`;
DROP TABLE IF EXISTS `mci_business_reviews`;
DROP TABLE IF EXISTS `mci_user_favourites`;

-- ── Business child tables (deepest dependents first) ─────────────────────────
DROP TABLE IF EXISTS `mci_business_review_replies`;
DROP TABLE IF EXISTS `mci_business_approvals`;
DROP TABLE IF EXISTS `mci_business_claims`;
DROP TABLE IF EXISTS `mci_business_social_links`;
DROP TABLE IF EXISTS `mci_business_images`;
DROP TABLE IF EXISTS `mci_business_faqs`;
DROP TABLE IF EXISTS `mci_business_services`;
DROP TABLE IF EXISTS `mci_business_products`;
DROP TABLE IF EXISTS `mci_business_tags`;
DROP TABLE IF EXISTS `mci_business_subcategories`;
DROP TABLE IF EXISTS `mci_business_branch_hours`;
DROP TABLE IF EXISTS `mci_business_branches`;
DROP TABLE IF EXISTS `mci_business_groups`;

-- ── Core tables ───────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `mci_anon_business_submissions`;
DROP TABLE IF EXISTS `mci_category_requests`;
DROP TABLE IF EXISTS `mci_password_reset_tokens`;
DROP TABLE IF EXISTS `mci_user_auth_providers`;
DROP TABLE IF EXISTS `mci_userprofiles`;
DROP TABLE IF EXISTS `mci_error_log`;
DROP TABLE IF EXISTS `mci_users`;
DROP TABLE IF EXISTS `mci_tags`;
DROP TABLE IF EXISTS `mci_categories`;
DROP TABLE IF EXISTS `mci_roles`;

SET FOREIGN_KEY_CHECKS = 1;
