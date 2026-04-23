CREATE TABLE IF NOT EXISTS mci_subscription_packages (
  id                char(36) NOT NULL,
  package_name      varchar(120) NOT NULL,
  package_type      enum('free','premium') NOT NULL DEFAULT 'free',
  is_default        tinyint(1) NOT NULL DEFAULT 0,
  status            enum('active','coming_soon','disabled') NOT NULL DEFAULT 'active',
  activation_date   datetime(6) DEFAULT NULL,
  expiry_date       datetime(6) DEFAULT NULL,
  price             decimal(10,2) NOT NULL DEFAULT 0.00,
  features_json     json DEFAULT NULL,
  created_at        datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at        datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mci_subscription_packages_name (package_name),
  KEY idx_mci_subscription_packages_status_activation (status, activation_date),
  KEY idx_mci_subscription_packages_default_status (is_default, status),
  KEY idx_mci_subscription_packages_type (package_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mci_user_subscriptions (
  id                      char(36) NOT NULL,
  user_id                 char(36) NOT NULL,
  package_id              char(36) NOT NULL,
  subscription_start_date datetime(6) NOT NULL,
  subscription_end_date   datetime(6) DEFAULT NULL,
  subscription_status     enum('active','inactive','expired','cancelled','pending_activation') NOT NULL DEFAULT 'active',
  auto_assigned           tinyint(1) NOT NULL DEFAULT 1,
  upgrade_source          varchar(64) DEFAULT NULL,
  created_at              datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at              datetime(6) DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (id),
  UNIQUE KEY uniq_mci_user_subscriptions_user_package_status (user_id, package_id, subscription_status),
  KEY idx_mci_user_subscriptions_user_status (user_id, subscription_status),
  KEY idx_mci_user_subscriptions_package_id (package_id),
  KEY idx_mci_user_subscriptions_end_date (subscription_end_date),
  CONSTRAINT fk_mci_user_subscriptions_user_id
    FOREIGN KEY (user_id) REFERENCES mci_users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_mci_user_subscriptions_package_id
    FOREIGN KEY (package_id) REFERENCES mci_subscription_packages (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mci_subscription_packages (
  id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json
)
SELECT
  'a3fd2976-145c-4ec8-8d47-58fe05ea9b61',
  'FREE',
  'free',
  1,
  'active',
  NOW(6),
  '2028-03-31 23:59:59.000000',
  0.00,
  JSON_OBJECT(
    'send_enquiries', true,
    'manage_enquiries', true,
    'report_business', true,
    'favourites', true,
    'claim_business', true,
    'unlimited_business_listings', true,
    'business_profile_page', true,
    'accept_business_enquiries', true,
    'manage_products', true,
    'manage_services', true,
    'promo_free_until', '2028-03-31'
  )
WHERE NOT EXISTS (
  SELECT 1 FROM mci_subscription_packages WHERE package_name = 'FREE'
);

INSERT INTO mci_subscription_packages (
  id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json
)
SELECT
  'cb2d8b58-c31a-4ad9-b908-870ce0e4aefe',
  'PAID',
  'premium',
  0,
  'coming_soon',
  '2028-04-01 00:00:00.000000',
  NULL,
  0.00,
  JSON_OBJECT(
    'send_enquiries', true,
    'manage_enquiries', true,
    'report_business', true,
    'favourites', true,
    'claim_business', true,
    'unlimited_business_listings', true,
    'business_profile_page', true,
    'accept_business_enquiries', true,
    'manage_products', true,
    'manage_services', true,
    'coming_soon_until', '2028-04-01'
  )
WHERE NOT EXISTS (
  SELECT 1 FROM mci_subscription_packages WHERE package_name = 'PAID'
);

UPDATE mci_subscription_packages
SET is_default = CASE WHEN package_name = 'FREE' THEN 1 ELSE 0 END
WHERE package_name IN ('FREE', 'PAID');

INSERT INTO mci_user_subscriptions (
  id, user_id, package_id, subscription_start_date, subscription_end_date,
  subscription_status, auto_assigned, upgrade_source
)
SELECT
  UUID(),
  u.id,
  p.id,
  NOW(6),
  p.expiry_date,
  CASE
    WHEN p.activation_date IS NOT NULL AND p.activation_date > NOW(6) THEN 'pending_activation'
    WHEN p.expiry_date IS NOT NULL AND p.expiry_date < NOW(6) THEN 'expired'
    WHEN p.status = 'disabled' THEN 'inactive'
    ELSE 'active'
  END,
  1,
  'migration_backfill'
FROM mci_users u
JOIN mci_subscription_packages p ON p.package_name = 'FREE' AND p.is_default = 1
LEFT JOIN mci_user_subscriptions s ON s.user_id = u.id
WHERE u.deleted_at IS NULL
  AND s.id IS NULL;
