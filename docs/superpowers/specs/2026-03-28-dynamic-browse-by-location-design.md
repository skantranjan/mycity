# Design: Dynamic Browse by City & Location

**Date:** 2026-03-28
**Branch:** mci-11
**Status:** Approved

---

## Summary

Replace the hardcoded `$siteIndexLocations` PHP array on the home page with a dynamic system driven by a new `mci_locations` reference table. The table grows organically as listings are submitted, and also serves as the AJAX data source for country/state dropdowns on the submission form.

---

## 1. Database: `mci_locations` table + migration

### Table structure

```sql
CREATE TABLE IF NOT EXISTS `mci_locations` (
  `id`       int unsigned  NOT NULL AUTO_INCREMENT,
  `country`  varchar(100)  NOT NULL,
  `state`    varchar(100)  NOT NULL,
  `city`     varchar(100)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_locations_country_state_city` (`country`, `state`, `city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Authoritative lookup table for country/state/city combinations. Soft reference — no FK to branches.';
```

Note: The composite unique key `(country, state, city)` covers the `/states?country=X` query via InnoDB prefix matching — no additional index on `(country, state)` is needed.

### Migration

- File: `api/v1/migrations/017_locations_table.sql` (matches the existing migration runner in `migrate.php` which scans `api/v1/migrations/`)
- Creates the table
- Pre-populates with seed cities for: India, UK, Australia, Canada, USA, Asia Pacific (matching the current hardcoded list so the accordion is not empty on day one)

### Relationship to branches

- `mci_business_branches` retains its existing `country` (varchar, default `'India'`) and `state` (varchar, nullable) free-text columns
- No foreign key constraint — `mci_locations` is a suggestion/lookup source only
- The table grows when new listings are submitted with novel country/state/city combinations

---

## 2. API: Location endpoints

New routes added as `if` blocks inside `api/v1/index.php`, following the existing routing pattern. A `require_once` for the new `api/v1/lib/location_service.php` is added at the top of that file.

### `GET /api/v1/locations/countries`

- Returns distinct countries from `mci_locations`, sorted alphabetically
- Response: `{ "ok": true, "countries": ["Australia", "Canada", ...] }`
- Used by: submission form country dropdown on page load

### `GET /api/v1/locations/states?country={country}`

- Returns distinct non-empty states for the given country, sorted alphabetically
- Query must filter out empty-string states: `WHERE country = ? AND state != '' ORDER BY state`
- Response: `{ "ok": true, "states": ["Maharashtra", "Karnataka", ...] }`
- Returns empty array if country not found or all states are empty-string (form falls back to free-text)
- Used by: submission form state dropdown on country change (AJAX)

### Internal: `api_locations_upsert(PDO $pdo, string $country, string $state, string $city): void`

- A PHP function in `api/v1/lib/location_service.php` — not a public HTTP endpoint
- Called server-side from `api_business_create` **after** `$pdo->commit()` has succeeded and outside the transaction block, so a failure here cannot roll back the business creation
- Uses `INSERT INTO mci_locations (country, state, city) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE country = country` — explicit intent, does not suppress non-duplicate errors unlike `INSERT IGNORE`
- `state` is passed as `$state ?? ''` — empty string is a valid stored value meaning "no state provided". The states endpoint filters empty strings out (`WHERE state != ''`) so blank entries never appear in dropdowns
- Wrapped in try/catch — silently swallows errors; location sync failure must never block listing creation

---

## 3. Submission form — Step 4 (Location & contact)

File: `views/partials/subscriber-list-business-inner.php`

### Country field

- Added above the existing State field in both:
  - The primary branch block (static HTML)
  - The `<template id="branchTemplate">` for additional branches
- Renders as a `<select>` with `name="country[]"`
- Options populated via AJAX from `/api/v1/locations/countries` on page load
- Includes an "Other (type below)" option that reveals a free-text `<input name="country_other[]">` for unlisted countries
- Default selected option: empty/placeholder

### State field upgrade

- Currently: free-text `<input name="state[]">`
- After: hybrid control
  - A `<select name="state_select[]">` loaded via AJAX from `/api/v1/locations/states?country=X` when country changes
  - A free-text `<input name="state[]">` shown when: dropdown is empty, or user selects "Other"
  - When the select has options and user picks one, the `state[]` hidden input is synced to that value immediately via JS
- The actual submitted value always comes from `name="state[]"`

### JS contract — payload merging

Before `buildPayload()` runs (the existing function in `subscriber-list-business.js` that assembles the API payload), a `mciSyncLocationFields()` helper is called. It iterates all branch blocks and:

1. If `country_select` value is `"other"` or empty, reads the corresponding `country_other` free-text input and writes its value into the `country[]` hidden input
2. If `state_select` has a non-"other" selection, writes that value into `state[]`; otherwise leaves `state[]` as typed by the user

`mciSyncLocationFields()` is also called as part of the template-clone handler for newly added branch blocks, so additional branches get the same wiring.

### JS changes

- File: `assets/js/subscriber-list-business.js`
- On page load: fetch `/api/v1/locations/countries`, populate all country selects
- On country select change: fetch states, populate state select; show free-text fallback if empty
- On "Other" selected for country: show free-text country input
- Template cloning: call `mciSyncLocationFields()` wiring on newly cloned branch block
- Before `buildPayload()`: call `mciSyncLocationFields()` to merge free-text overrides into the named inputs
- `buildPayload()` branch object is extended to include a `country` key reading `input[name='country[]']` for the corresponding branch block — without this, `country` is never sent to the API and the service always defaults to `'India'`

---

## 4. Business service — saving country & state

File: `api/v1/lib/business_service.php` — `api_business_create()`

### Actual branch column names

The schema file (`database/schema/02_business_tables.sql`) and the existing INSERT at line 168 of `business_service.php` are out of sync. The schema defines `phone_primary` (line 110) but the INSERT uses `phone`. The same diff that adds `state`/`country` **must also fix** `phone` → `phone_primary` in both the column list and parameter binding — otherwise the entire INSERT will fail with an unknown column error.

Authoritative column names from the schema file (`database/schema/02_business_tables.sql`):
- `address_line1` (schema) — the existing INSERT uses `full_address` which is a pre-existing mismatch; fix `full_address` → `address_line1` in the same diff
- `address_line2`
- `city`, `state`, `country`
- `phone_primary` ← fix `phone` → `phone_primary` in the same diff
- `whatsapp_number` ← fix `whatsapp` → `whatsapp_number` in the same diff (pre-existing mismatch)

Note: `email_contact` does not exist as a column on `mci_business_branches` in the schema. Remove it from the INSERT or verify whether it was added in a migration not reflected in the schema file.

### Branch INSERT

Add `state` and `country` to the INSERT, with length caps to match column size:

```php
$state   = mb_substr(trim((string)($branch['state']   ?? '')), 0, 100) ?: null;
$country = mb_substr(trim((string)($branch['country'] ?? 'India')), 0, 100);
if ($country === '') { $country = 'India'; }
```

The INSERT column list and values are extended to include `state` and `country` after `city`.

### Location upsert placement

The `api_locations_upsert()` call is placed **after** `$pdo->commit()` completes and outside the transaction block:

```php
// ... $pdo->commit(); ...
$result = ['ok' => true, ...];

// Outside transaction — failure must not affect the committed listing
if ($city !== '') {
    try {
        api_locations_upsert($pdo, $country, $state ?? '', $city);
    } catch (Throwable $ignored) {}
}

return $result;
```

---

## 5. Home page — dynamic accordion

File: `index.php`

### Replace hardcoded array

Remove the `$siteIndexLocations` PHP array (~40 lines). Replace with:

```php
$siteIndexLocations = [];
try {
    if ($pdo instanceof PDO) {
        $rows = $pdo->query(
            "SELECT country, state, city
             FROM mci_locations
             ORDER BY country, state, city"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $siteIndexLocations[$row['country']][$row['state']][] = $row['city'];
        }
    }
} catch (Throwable $ignored) {
    // Graceful degradation — accordion renders empty
}
```

### Accordion render update

The accordion template changes from a flat `country → [cities]` structure to `country → state → [cities]`:

- Top-level label: country name
- Sub-heading per state (small, muted label)
- City tags link to `/business-listing/?where={city}` (unchanged)

### Badge count fix

The existing city-count badge uses `array_sum(array_map('count', $siteIndexLocations))`. With the new 3-level structure this would count states, not cities. Replace with a correct nested sum:

```php
$totalCityCount = 0;
foreach ($siteIndexLocations as $states) {
    foreach ($states as $cities) {
        $totalCityCount += count($cities);
    }
}
```

Then render `<?= $totalCityCount ?> cities` in the badge.

### Graceful empty state

If `$siteIndexLocations` is empty, the accordion item shows:

```
No locations yet — they'll appear here as listings are added.
```

---

## Data flow summary

```
User submits listing
  → form sends country[], state[], city[] (JS merges free-text overrides before payload)
  → business_service saves to mci_business_branches (country, state, city)
  → after $pdo->commit(), api_locations_upsert writes to mci_locations (upsert)

Home page loads
  → queries mci_locations ORDER BY country, state, city
  → renders accordion: country → state → city tags

Submission form loads
  → JS fetches /api/v1/locations/countries → populates country dropdowns
  → on country change → JS fetches /api/v1/locations/states?country=X → populates state dropdown
  → free-text fallback if states empty or "Other" selected
```

---

## Out of scope

- FK constraint between `mci_business_branches` and `mci_locations` — explicitly excluded (soft reference)
- City-level SEO pages
- Admin UI to manage `mci_locations` directly (operational risk: typos in country/state names will pollute the dropdown; a future CP task should add a simple manage/delete interface)
- Updating existing scraped listings to populate `mci_locations` retroactively (separate task)
