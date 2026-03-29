# Dynamic Browse by City & Location — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the hardcoded city/region list on the home page with a dynamic `mci_locations` table, and add country/state fields to the business submission form so the table grows organically.

**Architecture:** A new `mci_locations` soft-reference table stores `(country, state, city)` combinations. Two public API routes serve dropdown data to the form. The business service is fixed to save `state`/`country` (and corrected column names) and upserts into `mci_locations` after each successful listing. The home page accordion queries the table instead of using a hardcoded array.

**Tech Stack:** PHP 8+, MySQL/MariaDB, vanilla JS (jQuery), Bootstrap 5

---

## File Map

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `api/v1/migrations/017_locations_table.sql` | Create `mci_locations` table + seed data |
| Create | `api/v1/lib/location_service.php` | `api_locations_upsert()` function |
| Modify | `api/v1/index.php` | Add `GET /api/v1/locations/countries` and `GET /api/v1/locations/states` routes |
| Modify | `api/v1/lib/business_service.php` | Fix branch INSERT column names; add state/country; call upsert post-commit |
| Modify | `views/partials/subscriber-list-business-inner.php` | Add country select + state hybrid control to Step 4 (both static and `<template>`) |
| Modify | `assets/js/subscriber-list-business.js` | Add `mciSyncLocationFields()`, AJAX cascade, extend `buildPayload()` |
| Modify | `index.php` | Replace hardcoded `$siteIndexLocations` with DB query; update accordion render + badge |

---

## Task 1: Migration — create `mci_locations` table with seed data

**Files:**
- Create: `api/v1/migrations/017_locations_table.sql`

- [ ] **Step 1: Create the migration file**

```sql
-- api/v1/migrations/017_locations_table.sql
-- Creates mci_locations lookup table and seeds initial city data.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mci_locations` (
  `id`       int unsigned  NOT NULL AUTO_INCREMENT,
  `country`  varchar(100)  NOT NULL,
  `state`    varchar(100)  NOT NULL DEFAULT '',
  `city`     varchar(100)  NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mci_locations_country_state_city` (`country`, `state`, `city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Soft-reference lookup table for country/state/city. No FK to branches.';

-- Seed: India
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('India','Maharashtra','Mumbai'),
('India','Delhi','Delhi'),
('India','Karnataka','Bangalore'),
('India','Telangana','Hyderabad'),
('India','Tamil Nadu','Chennai'),
('India','West Bengal','Kolkata'),
('India','Maharashtra','Pune'),
('India','Gujarat','Ahmedabad'),
('India','Rajasthan','Jaipur'),
('India','Uttar Pradesh','Lucknow'),
('India','Madhya Pradesh','Bhopal'),
('India','Bihar','Patna'),
('India','Gujarat','Surat'),
('India','Madhya Pradesh','Indore'),
('India','Maharashtra','Nagpur'),
('India','Gujarat','Vadodara'),
('India','Uttar Pradesh','Agra'),
('India','Uttar Pradesh','Varanasi'),
('India','Assam','Guwahati'),
('India','Punjab','Chandigarh');

-- Seed: UK
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('UK','England','London'),
('UK','England','Manchester'),
('UK','England','Birmingham'),
('UK','England','Leeds'),
('UK','England','Sheffield'),
('UK','England','Bristol'),
('UK','England','Liverpool'),
('UK','Scotland','Glasgow'),
('UK','Scotland','Edinburgh'),
('UK','England','Chester'),
('UK','Wales','Cardiff'),
('UK','England','Leicester'),
('UK','England','Nottingham'),
('UK','England','Southampton'),
('UK','England','Oxford'),
('UK','England','Cambridge'),
('UK','England','Bath'),
('UK','England','York'),
('UK','England','Brighton'),
('UK','England','Newcastle');

-- Seed: Australia
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Australia','New South Wales','Sydney'),
('Australia','Victoria','Melbourne'),
('Australia','Queensland','Brisbane'),
('Australia','Western Australia','Perth'),
('Australia','South Australia','Adelaide'),
('Australia','Queensland','Gold Coast'),
('Australia','Australian Capital Territory','Canberra'),
('Australia','Northern Territory','Darwin'),
('Australia','Tasmania','Hobart'),
('Australia','New South Wales','Newcastle NSW'),
('Australia','New South Wales','Wollongong'),
('Australia','Victoria','Geelong'),
('Australia','Queensland','Townsville'),
('Australia','Queensland','Cairns'),
('Australia','Queensland','Toowoomba'),
('Australia','Victoria','Ballarat'),
('Australia','Victoria','Bendigo'),
('Australia','New South Wales','Albury'),
('Australia','Tasmania','Launceston'),
('Australia','Queensland','Mackay');

-- Seed: Canada
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Canada','Ontario','Toronto'),
('Canada','British Columbia','Vancouver'),
('Canada','Quebec','Montreal'),
('Canada','Alberta','Calgary'),
('Canada','Ontario','Ottawa'),
('Canada','Alberta','Edmonton'),
('Canada','Manitoba','Winnipeg'),
('Canada','Quebec','Quebec City'),
('Canada','Ontario','Hamilton'),
('Canada','Ontario','Kitchener'),
('Canada','Ontario','London ON'),
('Canada','Nova Scotia','Halifax'),
('Canada','British Columbia','Victoria BC'),
('Canada','Saskatchewan','Saskatoon'),
('Canada','Saskatchewan','Regina'),
('Canada','Ontario','Windsor'),
('Canada','Ontario','Oshawa'),
('Canada','Ontario','Barrie'),
('Canada','British Columbia','Kelowna'),
('Canada','British Columbia','Abbotsford');

-- Seed: USA
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('USA','New York','New York'),
('USA','California','Los Angeles'),
('USA','Illinois','Chicago'),
('USA','Texas','Houston'),
('USA','Arizona','Phoenix'),
('USA','Pennsylvania','Philadelphia'),
('USA','Texas','San Antonio'),
('USA','California','San Diego'),
('USA','Texas','Dallas'),
('USA','Texas','Austin'),
('USA','California','San Jose'),
('USA','Florida','Jacksonville'),
('USA','Texas','Fort Worth'),
('USA','Ohio','Columbus'),
('USA','North Carolina','Charlotte'),
('USA','Indiana','Indianapolis'),
('USA','Washington','Seattle'),
('USA','Colorado','Denver'),
('USA','Massachusetts','Boston'),
('USA','Florida','Miami');

-- Seed: Asia Pacific
INSERT IGNORE INTO `mci_locations` (`country`, `state`, `city`) VALUES
('Singapore','','Singapore'),
('Hong Kong','','Hong Kong'),
('Malaysia','Kuala Lumpur','Kuala Lumpur'),
('Thailand','Bangkok','Bangkok'),
('Indonesia','Jakarta','Jakarta'),
('Philippines','Metro Manila','Manila'),
('Japan','Tokyo','Tokyo'),
('South Korea','Seoul','Seoul'),
('China','Shanghai','Shanghai'),
('China','Beijing','Beijing'),
('UAE','Dubai','Dubai'),
('UAE','Abu Dhabi','Abu Dhabi'),
('Qatar','Doha','Doha'),
('Sri Lanka','Western Province','Colombo'),
('Bangladesh','Dhaka','Dhaka'),
('Nepal','Bagmati','Kathmandu'),
('Pakistan','Sindh','Karachi'),
('Pakistan','Punjab','Lahore'),
('Kenya','Nairobi','Nairobi'),
('South Africa','Western Cape','Cape Town');
```

- [ ] **Step 2: Run the migration**

```bash
cd c:/Projects/apps/MyCityInfo/mycity
php migrate.php
```

Expected: output includes `017_locations_table.sql ... OK`

- [ ] **Step 3: Verify the table and seed data**

```bash
# In your MySQL client:
SELECT COUNT(*) FROM mci_locations;
-- Expected: 120 rows
SELECT country, COUNT(*) as c FROM mci_locations GROUP BY country ORDER BY country;
```

- [ ] **Step 4: Commit**

```bash
git add api/v1/migrations/017_locations_table.sql
git commit -m "feat: add mci_locations migration and seed data"
```

---

## Task 2: Location service library

**Files:**
- Create: `api/v1/lib/location_service.php`

- [ ] **Step 1: Create the file**

```php
<?php
declare(strict_types=1);

/**
 * Upsert a (country, state, city) combination into mci_locations.
 *
 * Called after a successful business branch insert (outside the transaction).
 * Failures are silently swallowed — this must never block listing creation.
 *
 * @param PDO    $pdo
 * @param string $country  — max 100 chars, defaults to 'India' if blank
 * @param string $state    — max 100 chars, empty string means "no state"
 * @param string $city     — max 100 chars, must be non-empty to be stored
 */
function api_locations_upsert(PDO $pdo, string $country, string $state, string $city): void
{
    $city    = mb_substr(trim($city),    0, 100);
    $state   = mb_substr(trim($state),   0, 100);
    $country = mb_substr(trim($country), 0, 100);

    if ($city === '') {
        return;
    }
    if ($country === '') {
        $country = 'India';
    }

    $pdo->prepare(
        'INSERT INTO mci_locations (country, state, city)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE country = country'
    )->execute([$country, $state, $city]);
}
```

- [ ] **Step 2: Verify the file syntax is valid**

```bash
php -l api/v1/lib/location_service.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add api/v1/lib/location_service.php
git commit -m "feat: add api_locations_upsert() in location_service.php"
```

---

## Task 3: API routes — countries and states endpoints

**Files:**
- Modify: `api/v1/index.php`

The two new routes go just before the `// 404 fallthrough` comment at the bottom of the file.
Also add `require_once __DIR__ . '/lib/location_service.php';` to the existing `require_once` block near the top (after the last existing `require_once` line in that block, around line 52).

- [ ] **Step 1: Add the two routes before the 404 fallthrough**

Find `// =============================================================================` followed by `// 404 fallthrough` near the bottom of `api/v1/index.php`. Insert the following block immediately before it:

```php
// =============================================================================
// Locations — public lookup endpoints (no auth)
// GET  /api/v1/locations/countries
// GET  /api/v1/locations/states?country=India
// =============================================================================

if ($method === 'GET' && ($segments[0] ?? '') === 'locations' && ($segments[1] ?? '') === 'countries') {
    require_once __DIR__ . '/lib/location_service.php';
    $pdo = api_db();
    $rows = $pdo->query(
        "SELECT DISTINCT country FROM mci_locations ORDER BY country"
    )->fetchAll(PDO::FETCH_COLUMN);
    api_json(['ok' => true, 'countries' => array_values($rows)]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'locations' && ($segments[1] ?? '') === 'states') {
    require_once __DIR__ . '/lib/location_service.php';
    $country = mb_substr(trim((string)($_GET['country'] ?? '')), 0, 100);
    if ($country === '') {
        api_json(['ok' => true, 'states' => []]);
    }
    $pdo  = api_db();
    $stmt = $pdo->prepare(
        "SELECT DISTINCT state FROM mci_locations
         WHERE country = ? AND state != ''
         ORDER BY state"
    );
    $stmt->execute([$country]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    api_json(['ok' => true, 'states' => array_values($rows)]);
}
```

- [ ] **Step 2: Test the countries endpoint**

```bash
curl -s "http://localhost/api/v1/locations/countries" | php -r "echo json_encode(json_decode(file_get_contents('php://stdin')), JSON_PRETTY_PRINT);"
```

Expected: `{ "ok": true, "countries": ["Australia", "Bangladesh", ...] }` (120 total cities across 6+ countries)

- [ ] **Step 3: Test the states endpoint**

```bash
curl -s "http://localhost/api/v1/locations/states?country=India" | php -r "echo json_encode(json_decode(file_get_contents('php://stdin')), JSON_PRETTY_PRINT);"
```

Expected: `{ "ok": true, "states": ["Assam", "Bihar", "Delhi", ...] }`

- [ ] **Step 4: Commit**

```bash
git add api/v1/index.php
git commit -m "feat: add GET /api/v1/locations/countries and /states endpoints"
```

---

## Task 4: Fix branch INSERT + add state/country + upsert call

**Files:**
- Modify: `api/v1/lib/business_service.php`

The existing branch INSERT (around line 165) uses wrong column names. This task fixes them all and adds `state`/`country`.

- [ ] **Step 1: Replace the branch INSERT block**

Find the `// --- mci_business_branches ---` comment and its INSERT (lines ~165–191). Replace the entire prepare/execute block with:

> **Important:** The current INSERT uses wrong column names that don't match the schema. This replacement fixes all of them:
> - `full_address` → `address_line1` (DB column name)
> - `phone` → `phone_primary`
> - `whatsapp` → `whatsapp_number`
> - `email_contact` is removed entirely — this column does not exist on `mci_business_branches`; the existing INSERT is broken because of it
> - `pincode` is added (it was missing from the existing INSERT but is in the schema and sent by the form)
> - `state` and `country` are added (new fields)

```php
        // --- mci_business_branches ---
        $branchState   = mb_substr(trim((string)($branch['state']   ?? '')), 0, 100) ?: '';
        $branchCountry = mb_substr(trim((string)($branch['country'] ?? 'India')), 0, 100);
        if ($branchCountry === '') { $branchCountry = 'India'; }

        $pdo->prepare('
            INSERT INTO mci_business_branches
              (id, business_group_id, slug, address_line1, address_line2, city, state, country,
               pincode, latitude, longitude,
               phone_primary, phone_secondary, whatsapp_number, website,
               status, created_by_user_id)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?,
               ?, ?, ?,
               ?, ?, ?, ?,
               \'active\', ?)
        ')->execute([
            $branchId,
            $groupId,
            $branchSlug,
            trim((string)($branch['full_address']    ?? '')) ?: null,  // JS form field name; maps to address_line1
            trim((string)($branch['address_line2']   ?? '')) ?: null,
            $city !== '' ? $city : null,
            $branchState  !== '' ? $branchState  : null,
            $branchCountry,
            trim((string)($branch['pincode']         ?? '')) ?: null,
            trim((string)($branch['latitude']        ?? '')) ?: null,
            trim((string)($branch['longitude']       ?? '')) ?: null,
            trim((string)($branch['phone']           ?? '')) ?: null,  // JS sends as 'phone'; maps to phone_primary
            trim((string)($branch['phone_secondary'] ?? '')) ?: null,
            trim((string)($branch['whatsapp']        ?? '')) ?: null,  // JS sends as 'whatsapp'; maps to whatsapp_number
            trim((string)($branch['website']         ?? '')) ?: null,
            $actorId,
        ]);
```

Note: The JS `buildPayload()` uses keys `full_address`, `phone`, and `whatsapp` — those PHP array keys are preserved here. The mapping to correct DB column names happens in the SQL column list above.

- [ ] **Step 2: Add the `require_once` for location_service at the top of the function file**

At the top of `api/v1/lib/business_service.php`, after the existing `require_once` lines, add:

```php
require_once __DIR__ . '/location_service.php';
```

- [ ] **Step 3: Add the location upsert call after `$pdo->commit()`**

Find the `$pdo->commit();` line (around line 356). The code after it looks like:

```php
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('api_business_create error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    $result = [
        'ok'        => true,
        'id'        => $groupId,
        'slug'      => $groupSlug,
        'branch_id' => $branchId,
    ];
    if (isset($newUserJwt)) { ... }
    return $result;
```

Add the upsert call between the closing `}` of the try/catch and `$result = [...]`:

```php
    // Sync to mci_locations — outside transaction; failure must never block listing
    try {
        api_locations_upsert($pdo, $branchCountry, $branchState, $city);
    } catch (Throwable $ignored) {}

    $result = [ ...
```

**Important scoping note:** `$city` is already declared at line ~91 (`$city = trim(...)`) **before** `$pdo->beginTransaction()` — do NOT move it; it is already in scope here. Only `$branchState` and `$branchCountry` are declared inside the try block and need pre-declaration before `$pdo->beginTransaction()` so they are in scope after the catch. Add these two lines immediately before `$pdo->beginTransaction();`:

```php
    $branchState   = '';
    $branchCountry = 'India';
```

The full assignments inside the try block (from Step 1) will overwrite these defaults at runtime.

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l api/v1/lib/business_service.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add api/v1/lib/business_service.php
git commit -m "fix: correct branch INSERT column names; add state/country; upsert mci_locations"
```

---

## Task 5: Submission form — add country field and upgrade state field (HTML)

**Files:**
- Modify: `views/partials/subscriber-list-business-inner.php`

There are two places to update: the static primary branch block and the `<template id="branchTemplate">`. Both need identical HTML added.

The HTML block to add (for the **primary branch**, IDs included):

```html
<div class="col-12 col-md-6">
  <label class="form-label mci-field-label" for="country_select_0">Country</label>
  <select class="form-select mci-country-select" id="country_select_0" name="country_select[]" data-branch-index="0">
    <option value="">Loading…</option>
  </select>
  <input class="form-control mt-2 d-none" id="country_other_0" name="country_other[]" type="text"
    placeholder="Type your country" maxlength="100" data-branch-index="0" />
  <input type="hidden" id="country_0" name="country[]" value="India" data-branch-index="0" />
</div>
```

The HTML block to add (for the **`<template>`**, no IDs — use `data-branch-index="__INDEX__"`):

```html
<div class="col-12 col-md-6">
  <label class="form-label mci-field-label">Country</label>
  <select class="form-select mci-country-select" name="country_select[]" data-branch-index="__INDEX__">
    <option value="">Loading…</option>
  </select>
  <input class="form-control mt-2 d-none" name="country_other[]" type="text"
    placeholder="Type your country" maxlength="100" data-branch-index="__INDEX__" />
  <input type="hidden" name="country[]" value="India" data-branch-index="__INDEX__" />
</div>
```

For the **state field**, replace the existing free-text state input in both locations with this hybrid control.

Primary branch (has IDs):

```html
<div class="col-12 col-md-4">
  <label class="form-label mci-field-label" for="state_select_0">State / Province</label>
  <select class="form-select mci-state-select" id="state_select_0" name="state_select[]" data-branch-index="0">
    <option value="">Select or type below</option>
  </select>
  <input class="form-control mt-2" id="state_0" type="text" name="state[]"
    placeholder="e.g. Maharashtra" maxlength="100" data-branch-index="0" />
  <div class="form-text">Select from the list or type manually.</div>
</div>
```

Template (no IDs):

```html
<div class="col-12 col-md-4">
  <label class="form-label mci-field-label">State / Province</label>
  <select class="form-select mci-state-select" name="state_select[]" data-branch-index="__INDEX__">
    <option value="">Select or type below</option>
  </select>
  <input class="form-control mt-2" type="text" name="state[]"
    placeholder="e.g. Maharashtra" maxlength="100" data-branch-index="__INDEX__" />
  <div class="form-text">Select from the list or type manually.</div>
</div>
```

- [ ] **Step 1: Add country + upgrade state in the primary branch block**

In `views/partials/subscriber-list-business-inner.php`, find the primary branch block (around line 375). The current state input is:

```html
                <div class="col-12 col-md-4">
                  <label class="form-label mci-field-label" for="state_0">State / Province</label>
                  <input class="form-control" id="state_0" type="text" name="state[]" placeholder="e.g. Maharashtra" />
                </div>
```

Replace it with the hybrid state control (primary branch version above), and add the country field block **before** it.

- [ ] **Step 2: Add country + upgrade state in the `<template id="branchTemplate">`**

Find the template block (around line 455). The current state input in the template is:

```html
                  <label class="form-label mci-field-label">State / Province</label>
                  <input class="form-control" type="text" name="state[]" placeholder="e.g. Maharashtra" />
```

Replace it with the hybrid state control (template version) and add the country field block before it.

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l views/partials/subscriber-list-business-inner.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add views/partials/subscriber-list-business-inner.php
git commit -m "feat: add country field and hybrid state control to listing form Step 4"
```

---

## Task 6: JS — AJAX cascade, payload sync, buildPayload country key

**Files:**
- Modify: `assets/js/subscriber-list-business.js`

- [ ] **Step 1: Add `mciSyncLocationFields()` and AJAX helpers**

Find the `// ── Multi-branch: add / remove ────` section (around line 625). Insert the following block **before** it:

```javascript
  // ── Location cascade: country → state ────────────────────────────
  var _mciCountryOptions = []; // cache from API

  function mciLoadCountries() {
    fetch('/api/v1/locations/countries')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        _mciCountryOptions = data.countries || [];
        $('.mci-country-select').each(function () {
          mciPopulateCountrySelect($(this));
        });
      })
      .catch(function () { /* silent — user can type */ });
  }

  function mciPopulateCountrySelect($sel) {
    var current = $sel.val() || 'India';
    $sel.empty().append('<option value="">Select country</option>');
    _mciCountryOptions.forEach(function (c) {
      $sel.append($('<option>').val(c).text(c));
    });
    $sel.append('<option value="other">Other (type below)</option>');
    if (current) { $sel.val(current); }
  }

  function mciLoadStates($branchBlock, country) {
    var $stateSel = $branchBlock.find('.mci-state-select');
    var $stateInput = $branchBlock.find('input[name="state[]"]');
    $stateSel.empty().append('<option value="">Loading…</option>');
    fetch('/api/v1/locations/states?country=' + encodeURIComponent(country))
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var states = data.states || [];
        $stateSel.empty().append('<option value="">Select or type below</option>');
        states.forEach(function (s) {
          $stateSel.append($('<option>').val(s).text(s));
        });
        if (states.length > 0) {
          $stateSel.append('<option value="other">Other (type below)</option>');
        }
      })
      .catch(function () {
        $stateSel.empty().append('<option value="">Select or type below</option>');
      });
  }

  /**
   * Merge country_other / state_select values into the canonical country[] / state[]
   * hidden inputs before buildPayload() reads them.
   */
  function mciSyncLocationFields() {
    $('#branchList .mci-branch-block').each(function () {
      var $b = $(this);

      // Country
      var $countrySel   = $b.find('.mci-country-select');
      var $countryOther = $b.find('input[name="country_other[]"]');
      var $countryHidden = $b.find('input[name="country[]"]');
      var countryVal = $countrySel.val();
      if (countryVal === 'other' || countryVal === '') {
        var typed = $countryOther.val().trim();
        $countryHidden.val(typed !== '' ? typed : 'India');
      } else {
        $countryHidden.val(countryVal || 'India');
      }

      // State
      var $stateSel   = $b.find('.mci-state-select');
      var $stateInput = $b.find('input[name="state[]"]');
      var stateSelVal = $stateSel.val();
      if (stateSelVal && stateSelVal !== 'other') {
        $stateInput.val(stateSelVal);
      }
      // If "other" or empty, leave $stateInput as whatever the user typed
    });
  }

  // Wire country→state cascade on country select change
  $(document).on('change', '.mci-country-select', function () {
    var $sel = $(this);
    var $block = $sel.closest('.mci-branch-block');
    var $countryOther = $block.find('input[name="country_other[]"]');
    var val = $sel.val();

    if (val === 'other') {
      $countryOther.removeClass('d-none');
      $block.find('.mci-state-select').empty()
        .append('<option value="">Select or type below</option>');
    } else {
      $countryOther.addClass('d-none').val('');
      if (val) { mciLoadStates($block, val); }
    }
  });

  // Wire state select → state text input sync
  $(document).on('change', '.mci-state-select', function () {
    var $sel = $(this);
    var $block = $sel.closest('.mci-branch-block');
    var $stateInput = $block.find('input[name="state[]"]');
    var val = $sel.val();
    if (val && val !== 'other') {
      $stateInput.val(val);
    } else if (val === 'other') {
      $stateInput.val('').focus();
    }
  });

  // Load countries on page load
  mciLoadCountries();
```

- [ ] **Step 2: Wire up cascade for newly cloned branches**

Find the `$('#addBranchBtn').on('click', ...)` handler (around line 626). After `$('#branchList').append(clone);`, add:

```javascript
    // Wire up country/state cascade for the new branch
    var $newBlock = $('#branchList .mci-branch-block').last();
    if (_mciCountryOptions.length > 0) {
      mciPopulateCountrySelect($newBlock.find('.mci-country-select'));
    }
```

- [ ] **Step 3: Add country to `buildPayload()` and call `mciSyncLocationFields()` before it**

Find `function buildPayload()` (around line 689). Add a call to `mciSyncLocationFields()` as the very first line inside the function:

```javascript
  function buildPayload() {
    mciSyncLocationFields();
    // ... rest of existing function
```

Then find the `branches.push({...})` object inside `buildPayload()` (around line 737). Add `country:` after `state:`:

```javascript
        city:            $b.find('input[name="city[]"]').val().trim(),
        state:           $b.find('input[name="state[]"]').val().trim(),
        country:         $b.find('input[name="country[]"]').val().trim() || 'India',
        pincode:         $b.find('input[name="pincode[]"]').val().trim(),
```

- [ ] **Step 4: Smoke test in browser**

Open the submission form at `/submit-business-listing/`. Open DevTools Network tab. Verify:
- On page load: `GET /api/v1/locations/countries` returns 200 with country list; country dropdowns are populated
- Select "India" from country dropdown: `GET /api/v1/locations/states?country=India` fires and state dropdown fills
- Select a state: state text input updates to match
- Select "Other" for country: free-text country input appears
- Add a second branch: country dropdown is populated in the new block

- [ ] **Step 5: Commit**

```bash
git add assets/js/subscriber-list-business.js
git commit -m "feat: add country/state AJAX cascade and mciSyncLocationFields to submission form JS"
```

---

## Task 7: Home page — dynamic accordion

**Files:**
- Modify: `index.php`

- [ ] **Step 1: Replace the hardcoded `$siteIndexLocations` array**

In `index.php`, find the `$siteIndexLocations = [` array (around line 280, just before the `<div class="container px-3..."` block). Remove the entire array (all ~40 lines ending at `];`). Replace it with:

```php
$siteIndexLocations = [];
try {
    if ($pdo instanceof PDO) {
        $locRows = $pdo->query(
            "SELECT country, state, city FROM mci_locations ORDER BY country, state, city"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($locRows as $locRow) {
            $siteIndexLocations[$locRow['country']][$locRow['state']][] = $locRow['city'];
        }
    }
} catch (Throwable $ignored) {
    // Graceful degradation — accordion renders with no locations
}
```

- [ ] **Step 2: Fix the badge count**

Find the `<?= array_sum(array_map('count', $siteIndexLocations)) ?> cities` badge (inside the locations accordion button). Replace with:

```php
<?php
$totalCityCount = 0;
foreach ($siteIndexLocations as $_states) {
    foreach ($_states as $_cities) {
        $totalCityCount += count($_cities);
    }
}
?>
<?= $totalCityCount ?> cities
```

- [ ] **Step 3: Update the accordion body render**

Find the `<?php foreach ($siteIndexLocations as $region => $cities): ?>` loop inside the locations accordion body. Replace the entire loop with:

```php
<?php if (empty($siteIndexLocations)): ?>
  <p class="text-muted small mb-0">No locations yet — they'll appear here as listings are added.</p>
<?php else: ?>
  <?php foreach ($siteIndexLocations as $country => $stateMap): ?>
    <div class="mb-4">
      <div class="fw-semibold small mb-2 text-uppercase"
           style="font-size:var(--mci-text-micro);letter-spacing:0.08em;color:var(--mci-color-primary-deep);">
        <?= htmlspecialchars($country) ?>
      </div>
      <?php foreach ($stateMap as $state => $cities): ?>
        <?php if ($state !== ''): ?>
          <div class="text-muted mb-1 mt-2" style="font-size:var(--mci-text-xs);">
            <?= htmlspecialchars($state) ?>
          </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mb-2">
          <?php foreach ($cities as $city): ?>
            <a href="/business-listing/?where=<?= urlencode($city) ?>" class="mci-site-index-tag">
              <?= htmlspecialchars($city) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
```

- [ ] **Step 4: Verify PHP syntax**

```bash
php -l index.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Smoke test in browser**

Load the home page. Expand the "Browse by city & location" accordion. Verify:
- Cities appear grouped by country and state
- Badge shows the correct count (120)
- City tag links work (`/business-listing/?where=Mumbai`)

- [ ] **Step 6: Commit**

```bash
git add index.php
git commit -m "feat: dynamic Browse by City accordion from mci_locations DB query"
```

---

## Task 8: End-to-end verification

- [ ] **Step 1: Submit a test listing with a new city**

Fill out the submission form with a unique city not in the seed data (e.g. country: India, state: Goa, city: Panaji). Submit the listing.

- [ ] **Step 2: Verify mci_locations was updated**

```sql
SELECT * FROM mci_locations WHERE city = 'Panaji';
-- Expected: 1 row: country=India, state=Goa, city=Panaji
```

- [ ] **Step 3: Verify the home page shows the new city**

Reload the home page, expand the locations accordion, confirm "Panaji" appears under India → Goa.

- [ ] **Step 4: Verify states AJAX returns Goa**

```bash
curl -s "http://localhost/api/v1/locations/states?country=India" | grep Goa
```

Expected: `"Goa"` appears in the response

- [ ] **Step 5: Final commit (if any loose ends)**

```bash
git status
# If clean, no commit needed
```

---

## Summary of all commits

1. `feat: add mci_locations migration and seed data`
2. `feat: add api_locations_upsert() in location_service.php`
3. `feat: add GET /api/v1/locations/countries and /states endpoints`
4. `fix: correct branch INSERT column names; add state/country; upsert mci_locations`
5. `feat: add country field and hybrid state control to listing form Step 4`
6. `feat: add country/state AJAX cascade and mciSyncLocationFields to submission form JS`
7. `feat: dynamic Browse by City accordion from mci_locations DB query`
