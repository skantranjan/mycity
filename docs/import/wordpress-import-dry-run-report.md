# WordPress Import Dry-Run Report

- Date: 2026-04-09
- Source: `C:\Users\ravi\Downloads\u931223790_mycityinfo_pr.sql`

## Execution summary

1. SQL restore to separate DB failed (permission): user cannot create new databases.
2. Source DB direct access failed: no privilege on `u931223790_mycityinfo_pr`.
3. Fallback succeeded:
   - SQL imported into existing dev DB with table-prefix rewrite: `mci_` -> `wpimp_`.
   - Core WordPress data available (`wpimp_posts`, `wpimp_postmeta`, `wpimp_terms`, `wpimp_term_taxonomy`, `wpimp_term_relationships`, `wpimp_users`, `wpimp_usermeta`).
4. Discovery completed successfully (`tools/wp_import/discover_wordpress.php`).
5. Initial dry-run timed out; importer was optimized for batched meta/usermeta loading.
6. Dry-run completed successfully after optimization.

## Discovery highlights

- Listing post type: `listing` (256)
- Attachments: 132
- Users: 3699
- Key taxonomies:
  - `listing-category` (81)
  - `location` (165)
  - `list-tags` (782)
- Key meta keys confirm ListingPro structure:
  - `lp_listingpro_options`
  - `gallery_image_ids`
  - `listingproc_bhours`
  - `_thumbnail_id`
  - `_yoast_wpseo_*`

## Dry-run result (completed)

- Run ID: `7180dc3d-a1b9-4e24-a9bb-cbb02139baa9`
- Duration: ~66 seconds
- Users:
  - inserted: `3696`
  - updated: `1`
  - skipped: `2`
- Categories:
  - inserted: `5`
  - updated: `0`
- Businesses:
  - inserted: `255`
  - updated: `1`
  - skipped: `0`

## Remaining notes

- This was a dry-run (`--apply` not used), so no target production-facing listing data was written by this run.
- Next action is to execute one final apply run during approved cutover.


