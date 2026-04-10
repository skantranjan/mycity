# WordPress to MyCityInfo Mapping

This mapping is used by `tools/wp_import/import_wordpress.php`.

## Source assumptions

- WordPress source tables use configurable prefix (`WP_TABLE_PREFIX`, auto-detected from `*posts`).
- Listing content comes from `wp_posts` rows filtered by `post_type` and `post_status='publish'`.
- Listing metadata comes from `wp_postmeta`.
- Category assignments come from `wp_term_relationships` + `wp_term_taxonomy` + `wp_terms`.
- Users come from `wp_users` and optional profile values from `wp_usermeta`.

## User mapping

- `wp_users.user_email` -> `mci_users.email` (unique key for upsert)
- `wp_users.display_name` -> `mci_users.display_name`
- `wp_users.user_registered` -> `mci_users.created_at`
- `wp_users.user_status` -> `mci_users.status` (`0 => active`, otherwise `inactive`)
- Password behavior: imported users receive random temporary hash and are required to reset password.
- `wp_usermeta.first_name` / `last_name` -> `mci_userprofiles.first_name` / `last_name`

## Category mapping

- `wp_terms.name` -> `mci_categories.name`
- `wp_terms.slug` -> `mci_categories.slug` (with suffix if conflict)
- `wp_term_taxonomy.parent` relationship -> `mci_categories.parent_id`
- SEO fields for terms are loaded from serialized options when available (best effort), else null.

## Business mapping

- `wp_posts.post_title` -> `mci_business_groups.name`
- `wp_posts.post_name` -> `mci_business_groups.slug` (deduplicated)
- `wp_posts.post_content` -> `mci_business_groups.description`
- Post meta `_yoast_wpseo_title` -> `mci_business_groups.page_title`
- Post meta `_yoast_wpseo_focuskw` -> `mci_business_groups.meta_keywords`
- Post meta `_yoast_wpseo_metadesc` -> `mci_business_groups.meta_description`

Business contact/location meta keys are selected by first non-empty value in these candidate lists:

- Address line 1: `address`, `_address`, `business_address`, `street_address`, `address_line_1`, `address_line1`, `listing_address`, `location_address`, `_location_address`
- City: `city`, `_city`, `business_city`, `town`, `location_city`, `_location_city`
- State: `state`, `_state`, `business_state`, `province`, `region`, `location_state`
- Country: `country`, `_country`, `business_country`, `location_country` (default `India`)
- Pincode: `pincode`, `postal_code`, `zip`, `_zip`, `zipcode`, `zip_code`, `location_pincode`
- Phone: `phone`, `_phone`, `mobile`, `contact_number`, `telephone`, `phone_number`, `_phone_number`
- Website: `website`, `_website`, `url`, `business_website`, `company_website`, `contact_website`
- Latitude: `lat`, `latitude`, `_latitude`, `geo_latitude`, `map_lat`
- Longitude: `lng`, `lon`, `longitude`, `_longitude`, `geo_longitude`, `map_lng`
- Hours JSON: `business_hours`, `_business_hours`, `opening_hours_json`

## Branch mapping

- One branch is created per imported business listing.
- `mci_business_branches.slug` is generated from group slug + city.
- `mci_business_branch_hours` is parsed from supported JSON hour payloads.

## Category + subcategory assignment

- Importer selects a primary category from mapped WP terms.
- If the mapped term points to a child category (`parent_id` not null), importer stores:
  - parent as `mci_business_groups.parent_category_id`
  - child category in `mci_business_subcategories`
- If no term mapping is found, importer falls back to the first root category.

## Media mapping

- Featured image and gallery attachment IDs from listing meta are resolved against attachment posts.
- Attachment URLs are mapped to local file paths under `assets/uploads/wp-import/`.
- Inserted paths:
  - First image -> `mci_business_groups.logo_path` (best effort)
  - All images -> `mci_business_images.file_path`

## Import traceability

Importer stores source references in:

- `mci_wp_import_map` (`source_type`, `source_id`, `target_type`, `target_id`)
- `mci_wp_import_runs` and `mci_wp_import_logs` for run-level and row-level diagnostics

