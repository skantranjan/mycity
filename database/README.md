# My City Info — Database Setup

This directory contains all SQL files to create and populate the MyCityInfo database from scratch.

---

## Directory Layout

```
database/
├── README.md                          ← you are here
│
├── schema/                            ← CREATE TABLE statements (run once, in order)
│   ├── 01_core_tables.sql             ← roles, users, profiles, categories, tags, auth, error log
│   ├── 02_business_tables.sql         ← 14 business tables (groups, branches, products, services …)
│   └── 03_user_features.sql           ← user favourites, group-level reviews + review history
│
├── seed/
│   ├── master/                        ← reference data required in every environment
│   │   ├── 01_roles_and_system_user.sql  ← 4 roles + system anonymous sentinel user
│   │   ├── 02_categories.sql             ← see database/seed_categories.sql (pointer file)
│   │   └── 03_tags.sql                   ← see database/seed_tags.sql (pointer file)
│   │
│   └── test/                          ← sample data for local/staging only (NOT production)
│       ├── 01_test_users.sql          ← dev subscriber + super-admin accounts
│       └── 02_test_businesses.sql     ← 12 sample businesses (pointer to migration 011)
│
├── seed_categories.sql                ← canonical: 22 parent + 163 subcategories
└── seed_tags.sql                      ← canonical: 90+ cross-category tags
```

> **Note on pointer files:** `seed/master/02_categories.sql` and `seed/master/03_tags.sql` are
> documentation stubs — the actual data lives in `database/seed_categories.sql` and
> `database/seed_tags.sql`. Similarly, `seed/test/02_test_businesses.sql` points to
> `api/v1/migrations/011_seed_businesses.sql`. Run those canonical files as described below.

---

## Prerequisites

- MySQL 8.0+ or MariaDB 10.5+
- A database already created: `CREATE DATABASE mycityinfo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- A user with full privileges on that database

---

## Step 1 — Create all tables

Run the schema files **in order**. Each file uses `CREATE TABLE IF NOT EXISTS` so it is safe to re-run.

```bash
mysql -u <user> -p <database> < database/schema/01_core_tables.sql
mysql -u <user> -p <database> < database/schema/02_business_tables.sql
mysql -u <user> -p <database> < database/schema/03_user_features.sql
```

Or from within the MySQL client:

```sql
SOURCE database/schema/01_core_tables.sql;
SOURCE database/schema/02_business_tables.sql;
SOURCE database/schema/03_user_features.sql;
```

---

## Step 2 — Seed master / reference data

Run in this order (categories have no dependency on roles, but keep consistent):

```bash
mysql -u <user> -p <database> < database/seed/master/01_roles_and_system_user.sql
mysql -u <user> -p <database> < database/seed_categories.sql
mysql -u <user> -p <database> < database/seed_tags.sql
```

All three files are idempotent — safe to run multiple times.

---

## Step 3 — Seed test / development data (local and staging only)

> **Do not run these on a production database.**

```bash
mysql -u <user> -p <database> < database/seed/test/01_test_users.sql
mysql -u <user> -p <database> < api/v1/migrations/011_seed_businesses.sql
```

### Test accounts created

| Role        | Email                              | Password           |
|-------------|------------------------------------|--------------------|
| Subscriber  | subscriber1.dev@mycityinfo.local   | DevSubscriber123!  |
| Subscriber  | subscriber2.dev@mycityinfo.local   | DevSubscriber123!  |
| Subscriber  | subscriber3.dev@mycityinfo.local   | DevSubscriber123!  |
| Super Admin | superadmin.dev@mycityinfo.local    | DevSuperAdmin123!  |
| Super Admin | superadmin2.dev@mycityinfo.local   | DevSuperAdmin123!  |

---

## Full setup — single command sequence

```bash
DB=<database>
USER=<user>

mysql -u $USER -p $DB < database/schema/01_core_tables.sql
mysql -u $USER -p $DB < database/schema/02_business_tables.sql
mysql -u $USER -p $DB < database/schema/03_user_features.sql
mysql -u $USER -p $DB < database/seed/master/01_roles_and_system_user.sql
mysql -u $USER -p $DB < database/seed_categories.sql
mysql -u $USER -p $DB < database/seed_tags.sql

# Test data (local/staging only):
mysql -u $USER -p $DB < database/seed/test/01_test_users.sql
mysql -u $USER -p $DB < api/v1/migrations/011_seed_businesses.sql
```

---

## Re-running / resetting

All schema files and seed files are idempotent and can be re-run safely without dropping data.

To do a **full reset** of a specific table (e.g. after taxonomy changes):

```sql
SET foreign_key_checks = 0;
TRUNCATE TABLE mci_categories;
SET foreign_key_checks = 1;
-- then re-run: mysql ... < database/seed_categories.sql
```

To reset **all business data** (keeps users and categories):

```sql
SET foreign_key_checks = 0;
TRUNCATE TABLE mci_business_review_replies;
TRUNCATE TABLE mci_business_reviews;
TRUNCATE TABLE mci_business_review_history;
TRUNCATE TABLE mci_user_favourites;
TRUNCATE TABLE mci_business_approvals;
TRUNCATE TABLE mci_business_claims;
TRUNCATE TABLE mci_business_social_links;
TRUNCATE TABLE mci_business_images;
TRUNCATE TABLE mci_business_faqs;
TRUNCATE TABLE mci_business_services;
TRUNCATE TABLE mci_business_products;
TRUNCATE TABLE mci_business_tags;
TRUNCATE TABLE mci_business_subcategories;
TRUNCATE TABLE mci_business_branch_hours;
TRUNCATE TABLE mci_business_branches;
TRUNCATE TABLE mci_business_groups;
SET foreign_key_checks = 1;
-- then re-run: mysql ... < api/v1/migrations/011_seed_businesses.sql
```

---

## Existing incremental migrations

If you are working on an **existing database** (not a fresh install), use the migration runner instead:

```bash
php migrate.php
```

This script (at the project root) reads `api/v1/migrations/[0-9]*.sql` in order and applies any
that have not been recorded in the `mci_migrations` tracking table. It is safe to run repeatedly.

The schema files in `database/schema/` are the **canonical clean reference** — they are not run by
the migration runner. Use them for new installs or to understand the full table structure.

---

## Table summary

| Group | Tables |
|-------|--------|
| Core | `mci_roles`, `mci_users`, `mci_userprofiles`, `mci_categories`, `mci_tags`, `mci_user_auth_providers`, `mci_subscription_packages`, `mci_user_subscriptions`, `mci_password_reset_tokens`, `mci_category_requests`, `mci_anon_business_submissions`, `mci_error_log` |
| Business | `mci_business_groups`, `mci_business_branches`, `mci_business_branch_hours`, `mci_business_subcategories`, `mci_business_tags`, `mci_business_products`, `mci_business_services`, `mci_business_faqs`, `mci_business_images`, `mci_business_social_links`, `mci_business_approvals`, `mci_business_claims`, `mci_business_reviews`, `mci_business_review_replies` |
| User Features | `mci_user_favourites`, `mci_business_reviews` (group-level), `mci_business_review_history` |
