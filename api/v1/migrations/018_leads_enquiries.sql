-- =============================================================================
-- 018 — Leads & Enquiries
-- Stores contact form submissions sent to a business listing.
-- type = 'lead'     → from the /business/ contact form (phone + email + message)
-- type = 'enquiry'  → from a quick-contact form (email + message, no phone required)
-- Both are owned by the business_group they belong to; the subscribing owner
-- sees them in /subscriber/leads/ and /subscriber/enquiries/.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_leads` (
  `id`                char(36)      NOT NULL                         COMMENT 'UUID v4',
  `business_group_id` char(36)      NOT NULL                         COMMENT 'FK → mci_business_groups.id',
  `type`              enum('lead','enquiry') NOT NULL DEFAULT 'lead'  COMMENT 'lead = full contact; enquiry = quick message',
  `sender_name`       varchar(160)  NOT NULL DEFAULT '',
  `sender_phone`      varchar(40)   NOT NULL DEFAULT '',
  `sender_email`      varchar(254)  NOT NULL DEFAULT '',
  `message`           text          NOT NULL,
  `status`            enum('new','contacted','converted','closed','replied') NOT NULL DEFAULT 'new',
  `created_at`        datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_leads_business`    (`business_group_id`),
  KEY `idx_leads_type_status` (`type`, `status`),
  KEY `idx_leads_created`     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Contact form submissions (leads + enquiries) per business listing';
