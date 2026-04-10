# WordPress Import Quickstart

## 1) Configure environment

Update `.env` with both target and source database values:

- `MCI_DB_HOST`, `MCI_DB_NAME`, `MCI_DB_USER`, `MCI_DB_PASS`
- `WP_DB_HOST`, `WP_DB_PORT`, `WP_DB_NAME`, `WP_DB_USER`, `WP_DB_PASS`
- Optional: `WP_TABLE_PREFIX`, `WP_LISTING_POST_TYPES`, `WP_CATEGORY_TAXONOMIES`
- Optional for media copy: `WP_UPLOADS_PATH`

## 2) Run schema discovery

```bash
php tools/wp_import/discover_wordpress.php
```

Output file: `docs/import/wordpress-discovery.json`

## 3) Run dry import

```bash
php tools/wp_import/import_wordpress.php
```

Dry-run is default. It records discovery and transformation metrics without writing business/users/categories.

Output file: `docs/import/wordpress-import-report.md`

## 4) Run actual import

```bash
php tools/wp_import/import_wordpress.php --apply
```

## 5) Validate

- Compare source/target counts (users, categories, listings, images)
- Spot-check 20 random listings in UI
- Trigger password reset for imported users
- Confirm media paths resolve in listing detail pages

## Import internals

- Run registry: `mci_wp_import_runs`
- Row logs: `mci_wp_import_logs`
- Source-target map: `mci_wp_import_map`

