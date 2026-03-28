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

### Migration

- File: `database/migrations/017_locations_table.sql`
- Creates the table
- Pre-populates with seed cities for: India, UK, Australia, Canada, USA, Asia Pacific (matching the current hardcoded list so the accordion is not empty on day one)

### Relationship to branches

- `mci_business_branches` retains its existing `country` (varchar, default `'India'`) and `state` (varchar, nullable) free-text columns
- No foreign key constraint — `mci_locations` is a suggestion/lookup source only
- The table grows when new listings are submitted with novel country/state/city combinations

---

## 2. API: Location endpoints

New file: `api/v1/locations/index.php` (or routed via `router.php`)

### `GET /api/v1/locations/countries`

- Returns distinct countries from `mci_locations`, sorted alphabetically
- Response: `{ "ok": true, "countries": ["Australia", "Canada", ...] }`
- Used by: submission form country dropdown on page load

### `GET /api/v1/locations/states?country={country}`

- Returns distinct states for the given country, sorted alphabetically
- Response: `{ "ok": true, "states": ["Maharashtra", "Karnataka", ...] }`
- Returns empty array if country not found (form falls back to free-text)
- Used by: submission form state dropdown on country change (AJAX)

### Internal: `api_locations_upsert(PDO, string $country, string $state, string $city): void`

- A PHP function (not a public HTTP endpoint) in a new `api/v1/lib/location_service.php`
- Called server-side from `api_business_create` after a successful branch insert
- Upserts `(country, state, city)` into `mci_locations` using `INSERT IGNORE` or `ON DUPLICATE KEY UPDATE`
- Silently swallows errors — location sync failure must never block listing creation

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
  - When the select has options and user picks one, the hidden input is synced to that value
- The actual submitted value always comes from `name="state[]"`

### JS changes

- File: `assets/js/subscriber-list-business.js`
- On page load: fetch `/api/v1/locations/countries`, populate all country selects
- On country select change: fetch states, populate state select; show free-text fallback if empty
- On "Other" selected for country: show free-text country input
- Template cloning: wire up the same cascade for newly added branch blocks

---

## 4. Business service — saving country & state

File: `api/v1/lib/business_service.php` — `api_business_create()`

### Branch INSERT

Add `state` and `country` to the INSERT:

```php
$state   = trim((string)($branch['state']   ?? '')) ?: null;
$country = trim((string)($branch['country'] ?? 'India'));

INSERT INTO mci_business_branches
  (id, business_group_id, slug, full_address, city, state, country,
   latitude, longitude, phone, whatsapp, email_contact, website,
   status, created_by_user_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
```

### Location upsert

After successful branch insert, call:

```php
if ($city !== '') {
    api_locations_upsert($pdo, $country, $state ?? '', $city);
}
```

Wrapped in try/catch — failure is silently swallowed.

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
- Sub-heading per state (small, muted)
- City tags link to `/business-listing/?where={city}` (unchanged)

### Graceful empty state

If `$siteIndexLocations` is empty, the accordion item shows:

```
No locations yet — they'll appear here as listings are added.
```

---

## Data flow summary

```
User submits listing
  → form sends country[], state[], city[]
  → business_service saves to mci_business_branches (country, state, city)
  → api_locations_upsert writes to mci_locations (upsert)

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
- Admin UI to manage `mci_locations` directly
- Updating existing scraped listings to populate `mci_locations` retroactively (separate task)
