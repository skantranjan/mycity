# To-Do Backlog Manual Validation

## Migration data quality
- Run `php tools/wp_import/audit_migrated_businesses.php`.
- Confirm report exists at `docs/import/wordpress-import-data-audit-report.md`.
- Verify missing city/address/subcategory counts are lower after import rerun/remediation.

## Favicon
- Open `/favicon.ico` in browser and confirm no 404.
- Confirm local router (`php -S ... router.php`) no longer logs favicon 404.

## Infinite scroll
- Open `/business-listing/` and `/business-category/?slug=<slug>`.
- Scroll to bottom and confirm additional records append without full page refresh.
- Verify loading, retry/end messages and fallback pagination for non-JS users.

## Inappropriate flags
- Submit a flag while logged in.
- Submit as guest with name/email.
- Submit as anonymous without name/email.
- Confirm records appear in `GET /api/v1/cp/business-flags`.
- Resolve and dismiss a flag via CP API routes.

## Business authoring
- In create/edit wizard, confirm rich text controls modify description content.
- Verify description preview still updates.
- Confirm SEO fields are hidden on create flows and visible on edit flow.
- Save edits and verify `page_title`, `meta_description`, and `meta_keywords` persist.

## Ratings and reviews
- Confirm 1 through 5 star selection works in review form.
- Submit/update a review and verify selected rating persists.

## CP users lifecycle
- In `/cp/users/`, click display name and verify detail panel opens.
- Delete a subscriber and confirm typed `DELETE` confirmation is required.
- Verify subscriber soft-delete and associated businesses become `deleted`.

## Error log operations
- Delete one log row from `/cp/error-log/`.
- Run prune-30d action and verify older rows removed.
- Confirm clear-all still works.

