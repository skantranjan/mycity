# WordPress to MyCityInfo Production Cutover Runbook

## Preconditions

- Local dry-run completed and report reviewed.
- At least one CP admin user exists in `mci_users`.
- Source WordPress DB credentials are validated.
- Maintenance window approved.

## 1. Freeze window

1. Put old WordPress site into maintenance mode.
2. Disable new user registrations and listing writes on target during cutover.
3. Confirm no active import process is running.

## 2. Backups

1. Take full backup of target MyCityInfo DB.
2. Take full backup of source WordPress DB.
3. Backup target media directory (`assets/uploads/`).

## 3. Preflight checks

1. Verify `.env` values for both source and target DB.
2. Run:
   - `php tools/wp_import/discover_wordpress.php`
   - `php tools/wp_import/import_wordpress.php` (dry-run)
3. Confirm `docs/import/wordpress-import-report.md` has expected counts.

## 4. Execute cutover import

Run:

```bash
php tools/wp_import/import_wordpress.php --apply
```

## 5. Post-import validation

- Query `mci_wp_import_runs` latest status should be `done`.
- Query `mci_wp_import_logs` for `level='error'` should be empty/minimal.
- Validate counts:
  - users imported/updated
  - categories imported/updated
  - businesses imported/updated
- Spot-check 20 listings:
  - title/slug
  - category
  - branch address/city
  - phone/website
  - hours
  - images

## 6. Authentication checks

- Pick imported user email and execute reset-password flow.
- Confirm successful login after reset.

## 7. Open traffic

1. Re-enable target writes.
2. Disable maintenance mode.
3. Monitor errors and import logs for 24 hours.

## Rollback plan

If severe data integrity issues are detected:

1. Stop writes immediately.
2. Restore target DB from pre-cutover backup.
3. Restore target media directory backup.
4. Re-enable old site (if fallback needed).

