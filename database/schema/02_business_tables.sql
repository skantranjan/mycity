-- =============================================================================
-- My City Info — Business Tables
-- File: database/schema/02_business_tables.sql
--
-- Creates all 14 business tables:
--   mci_business_groups, mci_business_branches, mci_business_branch_hours,
--   mci_business_subcategories, mci_business_tags, mci_business_products,
--   mci_business_services, mci_business_faqs, mci_business_images,
--   mci_business_social_links, mci_business_approvals, mci_business_claims,
--   mci_business_reviews (branch-level), mci_business_review_replies
--
-- Run order: after 01_core_tables.sql
-- =============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';


-- =============================================================================
-- 1. mci_business_groups
--    Shared data hub per business entity. All branches inherit from this record.
--    Categories, tags, products, services, FAQs, and images all belong here.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_groups` (
  `id`                  char(36)          NOT NULL                             COMMENT 'UUID v4',
  `name`                varchar(255)      NOT NULL                             COMMENT 'Business trading name',
  `slug`                varchar(255)      NOT NULL                             COMMENT 'SEO-friendly unique URL segment',
  `tagline`             varchar(255)      DEFAULT NULL,
  `description`         text              DEFAULT NULL,
  `established_year`    year              DEFAULT NULL,
  `website_url`         varchar(512)      DEFAULT NULL,
  `email`               varchar(255)      DEFAULT NULL,
  -- Images (logo 1:1, profile 1:1, banner 16:9)
  `logo_path`           varchar(512)      DEFAULT NULL,
  `logo_width`          smallint unsigned DEFAULT NULL,
  `logo_height`         smallint unsigned DEFAULT NULL,
  `profile_path`        varchar(512)      DEFAULT NULL,
  `profile_width`       smallint unsigned DEFAULT NULL,
  `profile_height`      smallint unsigned DEFAULT NULL,
  `banner_path`         varchar(512)      DEFAULT NULL,
  `banner_width`        smallint unsigned DEFAULT NULL,
  `banner_height`       smallint unsigned DEFAULT NULL,
  `parent_category_id`  int unsigned      NOT NULL                             COMMENT 'FK → mci_categories.id (top-level category only)',
  `price_range`         enum('free','moderate','pricey','ultra')
                                          DEFAULT NULL,
  `video_url`           varchar(512)      DEFAULT NULL                         COMMENT 'Optional YouTube or video embed URL',
  `status`              enum('live','draft','suspended','deleted','rejected')
                                          NOT NULL DEFAULT 'live',
  `added_by_role`       enum('cp_admin','subscriber','anonymous')
                                          NOT NULL,
  `added_by_user_id`    char(36)          NOT NULL                             COMMENT 'FK → mci_users.id; use system anonymous user UUID for anonymous submissions',
  `claimed_by_user_id`  char(36)          DEFAULT NULL                         COMMENT 'FK → mci_users.id; set when claim is approved',
  `claimed_at`          datetime(6)       DEFAULT NULL,
  `page_title`          varchar(255)      DEFAULT NULL,
  `meta_keywords`       varchar(512)      DEFAULT NULL,
  `meta_description`    varchar(512)      DEFAULT NULL,
  -- Audit
  `created_at`          datetime(6)       NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)          DEFAULT NULL,
  `updated_at`          datetime(6)       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)          DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_business_groups_slug`           (`slug`),
  KEY        `idx_mci_business_groups_status`          (`status`),
  KEY        `idx_mci_business_groups_parent_category` (`parent_category_id`),
  KEY        `idx_mci_business_groups_added_by_role`   (`added_by_role`),
  KEY        `idx_mci_business_groups_added_by_user`   (`added_by_user_id`),
  KEY        `idx_mci_business_groups_claimed_by`      (`claimed_by_user_id`),
  KEY        `idx_mci_business_groups_created_by`      (`created_by_user_id`),
  KEY        `idx_mci_business_groups_updated_by`      (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bg_parent_category`
    FOREIGN KEY (`parent_category_id`) REFERENCES `mci_categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bg_added_by_user`
    FOREIGN KEY (`added_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bg_claimed_by`
    FOREIGN KEY (`claimed_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bg_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bg_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Shared data hub per business. All branches inherit from this record.';


-- =============================================================================
-- 2. mci_business_branches
--    Individual physical locations. All peers (flat model — no enforced primary).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_branches` (
  `id`                  char(36)          NOT NULL                             COMMENT 'UUID v4',
  `business_group_id`   char(36)          NOT NULL                             COMMENT 'FK → mci_business_groups.id',
  `branch_label`        varchar(100)      DEFAULT NULL                         COMMENT 'e.g. "Koramangala Branch"; NULL if single location',
  `slug`                varchar(255)      NOT NULL                             COMMENT 'Unique URL segment for this branch page',
  `address_line1`       varchar(255)      NOT NULL,
  `address_line2`       varchar(255)      DEFAULT NULL,
  `city`                varchar(100)      NOT NULL,
  `state`               varchar(100)      DEFAULT NULL,
  `country`             varchar(100)      NOT NULL DEFAULT 'India',
  `pincode`             varchar(20)       DEFAULT NULL,
  `latitude`            decimal(10,7)     DEFAULT NULL,
  `longitude`           decimal(10,7)     DEFAULT NULL,
  `phone_primary`       varchar(30)       DEFAULT NULL,
  `phone_secondary`     varchar(30)       DEFAULT NULL,
  `whatsapp_number`     varchar(30)       DEFAULT NULL,
  `website`             varchar(512)      DEFAULT NULL                         COMMENT 'Branch-specific website URL',
  `is_primary`          tinyint(1)        NOT NULL DEFAULT 0                   COMMENT 'Soft display hint; not uniquely constrained',
  `status`              enum('active','inactive','deleted')
                                          NOT NULL DEFAULT 'active',
  -- Audit
  `created_at`          datetime(6)       NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)          DEFAULT NULL,
  `updated_at`          datetime(6)       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)          DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_business_branches_slug`      (`slug`),
  KEY        `idx_mci_business_branches_group`      (`business_group_id`),
  KEY        `idx_mci_business_branches_status`     (`status`),
  KEY        `idx_mci_business_branches_city`       (`city`),
  KEY        `idx_mci_business_branches_latlong`    (`latitude`, `longitude`),
  KEY        `idx_mci_business_branches_created_by` (`created_by_user_id`),
  KEY        `idx_mci_business_branches_updated_by` (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bb_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bb_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bb_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual branch/location records. All peers, linked by business_group_id.';


-- =============================================================================
-- 3. mci_business_branch_hours
--    Operating hours per weekday per branch (up to 7 rows per branch).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_branch_hours` (
  `id`                  char(36)      NOT NULL                                 COMMENT 'UUID v4',
  `branch_id`           char(36)      NOT NULL                                 COMMENT 'FK → mci_business_branches.id',
  `day_of_week`         enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday')
                                      NOT NULL,
  `opens_at`            time          DEFAULT NULL                             COMMENT 'NULL when is_closed = 1',
  `closes_at`           time          DEFAULT NULL                             COMMENT 'NULL when is_closed = 1',
  `opens_at_2`          time          DEFAULT NULL                             COMMENT 'Second slot open time (split shift)',
  `closes_at_2`         time          DEFAULT NULL                             COMMENT 'Second slot close time (split shift)',
  `is_closed`           tinyint(1)    NOT NULL DEFAULT 0,
  -- Audit
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)      DEFAULT NULL,
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)      DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_bbh_branch_day`  (`branch_id`, `day_of_week`),
  KEY        `idx_mci_bbh_branch`       (`branch_id`),
  KEY        `idx_mci_bbh_created_by`   (`created_by_user_id`),
  KEY        `idx_mci_bbh_updated_by`   (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bbh_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `mci_business_branches` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bbh_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bbh_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Operating hours per weekday per branch (max 7 rows per branch).';


-- =============================================================================
-- 4. mci_business_subcategories
--    Links a business group to child (sub-)categories within its parent category.
--    APP RULE: category_id must reference a row where parent_id IS NOT NULL.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_subcategories` (
  `id`                  char(36)      NOT NULL                                 COMMENT 'UUID v4',
  `business_group_id`   char(36)      NOT NULL,
  `category_id`         int unsigned  NOT NULL                                 COMMENT 'FK → mci_categories.id; app must verify parent_id IS NOT NULL',
  `sort_order`          int           NOT NULL DEFAULT 0,
  -- Audit
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)      DEFAULT NULL,
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)      DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_bsc_group_category`  (`business_group_id`, `category_id`),
  KEY        `idx_mci_bsc_group`            (`business_group_id`),
  KEY        `idx_mci_bsc_category`         (`category_id`),
  KEY        `idx_mci_bsc_created_by`       (`created_by_user_id`),
  KEY        `idx_mci_bsc_updated_by`       (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bsc_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bsc_category`
    FOREIGN KEY (`category_id`) REFERENCES `mci_categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bsc_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bsc_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subcategories assigned to a business group. App must verify category_id has parent_id IS NOT NULL.';


-- =============================================================================
-- 5. mci_business_tags
--    Many-to-many join: business group ↔ mci_tags.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_tags` (
  `id`                  char(36)      NOT NULL                                 COMMENT 'UUID v4',
  `business_group_id`   char(36)      NOT NULL,
  `tag_id`              int unsigned  NOT NULL                                 COMMENT 'FK → mci_tags.id',
  -- Audit
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)      DEFAULT NULL,
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)      DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_bt_group_tag`   (`business_group_id`, `tag_id`),
  KEY        `idx_mci_bt_group`        (`business_group_id`),
  KEY        `idx_mci_bt_tag`          (`tag_id`),
  KEY        `idx_mci_bt_created_by`   (`created_by_user_id`),
  KEY        `idx_mci_bt_updated_by`   (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bt_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bt_tag`
    FOREIGN KEY (`tag_id`) REFERENCES `mci_tags` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bt_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bt_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Many-to-many: business groups ↔ tags.';


-- =============================================================================
-- 6. mci_business_products
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_products` (
  `id`                  char(36)          NOT NULL                             COMMENT 'UUID v4',
  `business_group_id`   char(36)          NOT NULL,
  `name`                varchar(255)      NOT NULL,
  `description`         text              DEFAULT NULL,
  `price_min`           decimal(10,2)     DEFAULT NULL,
  `price_max`           decimal(10,2)     DEFAULT NULL,
  `price_unit`          varchar(50)       DEFAULT NULL                         COMMENT 'e.g. "per kg"',
  `image_path`          varchar(512)      DEFAULT NULL,
  `sort_order`          int               NOT NULL DEFAULT 0,
  `is_active`           tinyint(1)        NOT NULL DEFAULT 1,
  -- Audit
  `created_at`          datetime(6)       NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)          DEFAULT NULL,
  `updated_at`          datetime(6)       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)          DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bp_group`      (`business_group_id`),
  KEY `idx_mci_bp_is_active`  (`is_active`),
  KEY `idx_mci_bp_created_by` (`created_by_user_id`),
  KEY `idx_mci_bp_updated_by` (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bp_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bp_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bp_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Products offered by a business group.';


-- =============================================================================
-- 7. mci_business_services
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_services` (
  `id`                  char(36)          NOT NULL                             COMMENT 'UUID v4',
  `business_group_id`   char(36)          NOT NULL,
  `name`                varchar(255)      NOT NULL,
  `description`         text              DEFAULT NULL,
  `price_min`           decimal(10,2)     DEFAULT NULL,
  `price_max`           decimal(10,2)     DEFAULT NULL,
  `price_unit`          varchar(50)       DEFAULT NULL                         COMMENT 'e.g. "per hour"',
  `image_path`          varchar(512)      DEFAULT NULL,
  `sort_order`          int               NOT NULL DEFAULT 0,
  `is_active`           tinyint(1)        NOT NULL DEFAULT 1,
  -- Audit
  `created_at`          datetime(6)       NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)          DEFAULT NULL,
  `updated_at`          datetime(6)       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)          DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bs_group`      (`business_group_id`),
  KEY `idx_mci_bs_is_active`  (`is_active`),
  KEY `idx_mci_bs_created_by` (`created_by_user_id`),
  KEY `idx_mci_bs_updated_by` (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bs_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bs_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bs_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Services offered by a business group.';


-- =============================================================================
-- 8. mci_business_faqs
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_faqs` (
  `id`                  char(36)      NOT NULL                                 COMMENT 'UUID v4',
  `business_group_id`   char(36)      NOT NULL,
  `question`            varchar(512)  NOT NULL,
  `answer`              text          NOT NULL DEFAULT '',
  `sort_order`          int           NOT NULL DEFAULT 0,
  -- Audit
  `created_at`          datetime(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)      DEFAULT NULL,
  `updated_at`          datetime(6)   DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)      DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bfaq_group`      (`business_group_id`),
  KEY `idx_mci_bfaq_created_by` (`created_by_user_id`),
  KEY `idx_mci_bfaq_updated_by` (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bfaq_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bfaq_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bfaq_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='FAQs for a business group, ordered by sort_order.';


-- =============================================================================
-- 9. mci_business_images
--    Gallery attached to a business group.
--    APP RULES: max 20 images per group; max 2 MB per file.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_images` (
  `id`                    char(36)        NOT NULL                             COMMENT 'UUID v4',
  `business_group_id`     char(36)        NOT NULL,
  `file_path`             varchar(512)    NOT NULL,
  `caption`               varchar(255)    DEFAULT NULL,
  `alt_text`              varchar(255)    DEFAULT NULL,
  `is_cover`              tinyint(1)      NOT NULL DEFAULT 0,
  `file_size_bytes`       int unsigned    DEFAULT NULL                         COMMENT 'Audit only; 2 MB max enforced at app level',
  `sort_order`            int             NOT NULL DEFAULT 0,
  `uploaded_by_user_id`   char(36)        DEFAULT NULL,
  -- Audit
  `created_at`            datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`    char(36)        DEFAULT NULL,
  `updated_at`            datetime(6)     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`    char(36)        DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bimg_group`       (`business_group_id`),
  KEY `idx_mci_bimg_is_cover`    (`is_cover`),
  KEY `idx_mci_bimg_uploaded_by` (`uploaded_by_user_id`),
  KEY `idx_mci_bimg_created_by`  (`created_by_user_id`),
  KEY `idx_mci_bimg_updated_by`  (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bimg_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bimg_uploader`
    FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bimg_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bimg_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Image gallery per business group. Max 20 images and max 2 MB per file enforced at application level.';


-- =============================================================================
-- 10. mci_business_social_links
--     One row per social platform per business group.
--     platform='other' allows a custom label for unlisted platforms.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_social_links` (
  `id`                  char(36)        NOT NULL                               COMMENT 'UUID v4',
  `business_group_id`   char(36)        NOT NULL,
  `platform`            enum(
                          'facebook','instagram','twitter','youtube','linkedin',
                          'tiktok','pinterest','threads','snapchat',
                          'whatsapp_channel','telegram','other'
                        )               NOT NULL,
  `url`                 varchar(512)    NOT NULL,
  `label`               varchar(100)    DEFAULT NULL                           COMMENT 'Required when platform = ''other''',
  `sort_order`          int             NOT NULL DEFAULT 0,
  -- Audit
  `created_at`          datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)        DEFAULT NULL,
  `updated_at`          datetime(6)     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)        DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_bsl_group_platform`  (`business_group_id`, `platform`),
  KEY        `idx_mci_bsl_group`            (`business_group_id`),
  KEY        `idx_mci_bsl_platform`         (`platform`),
  KEY        `idx_mci_bsl_created_by`       (`created_by_user_id`),
  KEY        `idx_mci_bsl_updated_by`       (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bsl_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bsl_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bsl_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Social media profile links per business group. One row per platform; platform=other allows custom label.';


-- =============================================================================
-- 11. mci_business_approvals
--     Immutable audit log of every CP admin status transition on a business group.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_approvals` (
  `id`                  char(36)        NOT NULL                               COMMENT 'UUID v4 — stable approval reference',
  `business_group_id`   char(36)        NOT NULL,
  `reviewed_by_user_id` char(36)        NOT NULL                               COMMENT 'FK → mci_users.id; CP admin who performed this action',
  `action`              enum('approved','rejected','suspended','reinstated','draft')
                                        NOT NULL,
  `previous_status`     enum('live','draft','suspended','deleted','rejected')
                                        DEFAULT NULL,
  `new_status`          enum('live','draft','suspended','deleted','rejected')
                                        NOT NULL,
  `notes`               text            DEFAULT NULL,
  `reviewed_at`         datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

  PRIMARY KEY (`id`),
  KEY `idx_mci_bapprv_group`       (`business_group_id`),
  KEY `idx_mci_bapprv_reviewed_by` (`reviewed_by_user_id`),
  KEY `idx_mci_bapprv_action`      (`action`),
  KEY `idx_mci_bapprv_reviewed_at` (`reviewed_at`),

  CONSTRAINT `fk_mci_bapprv_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bapprv_reviewed_by`
    FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable audit log of every CP review action on a business group.';


-- =============================================================================
-- 12. mci_business_claims
--     Workflow: subscriber requests ownership of a listing; CP admin approves/rejects.
--     On approval: mci_business_groups.claimed_by_user_id + claimed_at are set.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_claims` (
  `id`                  char(36)        NOT NULL                               COMMENT 'UUID v4 — stable claim reference',
  `business_group_id`   char(36)        NOT NULL,
  `claimant_user_id`    char(36)        NOT NULL                               COMMENT 'FK → mci_users.id; subscriber raising the claim',
  `status`              enum('pending','under_review','approved','rejected','withdrawn')
                                        NOT NULL DEFAULT 'pending',
  `claim_message`       text            DEFAULT NULL,
  `supporting_doc_path` varchar(512)    DEFAULT NULL,
  `reviewed_by_user_id` char(36)        DEFAULT NULL,
  `reviewed_at`         datetime(6)     DEFAULT NULL,
  `reviewer_notes`      text            DEFAULT NULL,
  -- Audit
  `created_at`          datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)        DEFAULT NULL,
  `updated_at`          datetime(6)     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)        DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bclaim_group`       (`business_group_id`),
  KEY `idx_mci_bclaim_claimant`    (`claimant_user_id`),
  KEY `idx_mci_bclaim_status`      (`status`),
  KEY `idx_mci_bclaim_reviewed_by` (`reviewed_by_user_id`),
  KEY `idx_mci_bclaim_created_by`  (`created_by_user_id`),
  KEY `idx_mci_bclaim_updated_by`  (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bclaim_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bclaim_claimant`
    FOREIGN KEY (`claimant_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bclaim_reviewed_by`
    FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bclaim_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bclaim_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Claim workflow: subscriber requests ownership of a listing.';


-- =============================================================================
-- 13. mci_business_flags
--     Public reporting workflow for inappropriate/incorrect business listings.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_flags` (
  `id`                  char(36)        NOT NULL                               COMMENT 'UUID v4',
  `business_group_id`   char(36)        NOT NULL,
  `reporter_user_id`    char(36)        DEFAULT NULL                           COMMENT 'Set when logged-in reporter submits',
  `reporter_type`       enum('logged_in','guest','anonymous') NOT NULL DEFAULT 'anonymous',
  `reporter_name`       varchar(160)    DEFAULT NULL,
  `reporter_email`      varchar(254)    DEFAULT NULL,
  `reason`              text            NOT NULL,
  `status`              enum('open','resolved','dismissed') NOT NULL DEFAULT 'open',
  `admin_note`          text            DEFAULT NULL,
  `resolved_by_user_id` char(36)        DEFAULT NULL,
  `resolved_at`         datetime(6)     DEFAULT NULL,
  -- Audit
  `created_at`          datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `created_by_user_id`  char(36)        DEFAULT NULL,
  `updated_at`          datetime(6)     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)        DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_mci_bflag_group`        (`business_group_id`),
  KEY `idx_mci_bflag_reporter`     (`reporter_user_id`),
  KEY `idx_mci_bflag_status`       (`status`),
  KEY `idx_mci_bflag_resolved_by`  (`resolved_by_user_id`),
  KEY `idx_mci_bflag_created_by`   (`created_by_user_id`),
  KEY `idx_mci_bflag_updated_by`   (`updated_by_user_id`),

  CONSTRAINT `fk_mci_bflag_group`
    FOREIGN KEY (`business_group_id`) REFERENCES `mci_business_groups` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bflag_reporter`
    FOREIGN KEY (`reporter_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bflag_resolved_by`
    FOREIGN KEY (`resolved_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bflag_created_by`
    FOREIGN KEY (`created_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_bflag_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Public inappropriate listing reports with admin moderation status.';


-- =============================================================================
-- 14. mci_business_reviews  (branch-level — from migration 008)
--     Ratings and reviews per branch. One review per subscriber per branch.
--     NOTE: migration 013 adds a separate group-level review table of the same
--     name; this is the original branch-level version with full moderation support.
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_reviews` (
  `id`                   char(36)         NOT NULL                             COMMENT 'UUID v4',
  `branch_id`            char(36)         NOT NULL                             COMMENT 'FK → mci_business_branches.id',
  `reviewer_user_id`     char(36)         NOT NULL                             COMMENT 'FK → mci_users.id',
  `rating`               tinyint unsigned NOT NULL                             COMMENT '1–5 stars',
  `title`                varchar(255)     DEFAULT NULL,
  `body`                 text             DEFAULT NULL,
  `is_verified_visit`    tinyint(1)       NOT NULL DEFAULT 0,
  `status`               enum('published','hidden','flagged')
                                          NOT NULL DEFAULT 'published',
  `moderated_by_user_id` char(36)         DEFAULT NULL,
  `moderated_at`         datetime(6)      DEFAULT NULL,
  `moderation_notes`     text             DEFAULT NULL,
  -- Audit
  `created_at`           datetime(6)      NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`           datetime(6)      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`   char(36)         DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_brev_branch_user`  (`branch_id`, `reviewer_user_id`),
  KEY        `idx_mci_brev_branch`        (`branch_id`),
  KEY        `idx_mci_brev_reviewer`      (`reviewer_user_id`),
  KEY        `idx_mci_brev_status`        (`status`),
  KEY        `idx_mci_brev_rating`        (`rating`),
  KEY        `idx_mci_brev_moderated_by`  (`moderated_by_user_id`),
  KEY        `idx_mci_brev_updated_by`    (`updated_by_user_id`),

  CONSTRAINT `chk_mci_brev_rating` CHECK (`rating` BETWEEN 1 AND 5),

  CONSTRAINT `fk_mci_brev_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `mci_business_branches` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_brev_reviewer`
    FOREIGN KEY (`reviewer_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_brev_moderated_by`
    FOREIGN KEY (`moderated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_brev_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ratings and reviews per branch. One review per subscriber per branch. Rating 1–5 enforced by CHECK.';


-- =============================================================================
-- 14. mci_business_review_replies
--     Business owner or CP admin reply to a review (one reply per review).
-- =============================================================================
CREATE TABLE IF NOT EXISTS `mci_business_review_replies` (
  `id`                  char(36)        NOT NULL                               COMMENT 'UUID v4',
  `review_id`           char(36)        NOT NULL                               COMMENT 'FK → mci_business_reviews.id',
  `replied_by_user_id`  char(36)        NOT NULL,
  `replied_by_role`     enum('owner','cp_admin') NOT NULL,
  `body`                text            NOT NULL,
  `status`              enum('published','hidden') NOT NULL DEFAULT 'published',
  -- Audit
  `created_at`          datetime(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at`          datetime(6)     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  `updated_by_user_id`  char(36)        DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_brev_reply_review`   (`review_id`),
  KEY        `idx_mci_brevreply_replied_by` (`replied_by_user_id`),
  KEY        `idx_mci_brevreply_status`     (`status`),
  KEY        `idx_mci_brevreply_updated_by` (`updated_by_user_id`),

  CONSTRAINT `fk_mci_brevreply_review`
    FOREIGN KEY (`review_id`) REFERENCES `mci_business_reviews` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_brevreply_replied_by`
    FOREIGN KEY (`replied_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mci_brevreply_updated_by`
    FOREIGN KEY (`updated_by_user_id`) REFERENCES `mci_users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Business owner or CP admin reply to a review. One reply per review.';
