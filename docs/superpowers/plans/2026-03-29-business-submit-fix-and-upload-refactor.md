# Business Submit Bug Fix + Deferred Upload Refactor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix anonymous business submission server error, refactor image uploads to per-business folder structure, and defer uploads until after business creation.

**Architecture:** Three independent tasks executed in order: (1) one-line PHP bug fix + latent column name fix, (2) extend the PHP upload endpoint and add a new PATCH endpoint + extend the create response, (3) refactor the JS submit flow to defer uploads. Each task is independently committable and testable.

**Tech Stack:** PHP 8 (no framework), MySQL (PDO), vanilla JS + jQuery 3, Bootstrap 5, Cropper.js. No test runner — verification is done via browser/curl/manual.

**Spec:** `docs/superpowers/specs/2026-03-29-business-submit-fix-and-upload-refactor-design.md`

---

## File Map

| File | Role | Change type |
|------|------|-------------|
| `api/v1/lib/business_service.php` | Business create + patch logic | Modify |
| `api/v1/index.php` | API router — upload + PATCH routes | Modify |
| `assets/js/subscriber-list-business.js` | Multi-step form submit flow | Modify |

---

## Task 1: Fix `city` NULL Bug + Latent `file_path` Column Bug

**Files:**
- Modify: `api/v1/lib/business_service.php:190` (city fix)
- Modify: `api/v1/lib/business_service.php:325` (file_path fix)

### Background

`mci_business_branches.city` is `NOT NULL` in the DB schema. The current code passes PHP `null` when city is empty, causing MySQL to throw an integrity violation → transaction rolls back → 500 returned.

Separately, `mci_business_images.file_path` is the correct DB column name (confirmed in `database/schema/02_business_tables.sql:383`), but the existing gallery INSERT at line 325 uses `image_path` — a latent bug that would fail whenever gallery images are submitted.

- [ ] **Step 1.1: Fix `city` null → empty string**

Open `api/v1/lib/business_service.php`. Find line 190 inside the `mci_business_branches` INSERT execute array. Change:

```php
$city !== '' ? $city : null,
```

to:

```php
$city !== '' ? $city : '',
```

Context — this is the `city` positional param, right after `address_line2`:

```php
])->execute([
    $branchId,
    $groupId,
    $branchSlug,
    trim((string)($branch['full_address']    ?? '')) ?: '',     // address_line1
    trim((string)($branch['address_line2']   ?? '')) ?: null,
    $city !== '' ? $city : '',                                   // ← fix: was null
    $branchState  !== '' ? $branchState  : null,
    $branchCountry,
    // ...
]);
```

- [ ] **Step 1.2: Fix latent `image_path` → `file_path` in gallery INSERT**

Still in `api/v1/lib/business_service.php`. Find the `mci_business_images` INSERT around line 323. Change `image_path` to `file_path`:

```php
// Before
$imgStmt = $pdo->prepare('
    INSERT INTO mci_business_images
      (id, business_group_id, image_path, sort_order,
       uploaded_by_user_id, created_by_user_id)
    VALUES (?, ?, ?, ?, ?, ?)
');

// After
$imgStmt = $pdo->prepare('
    INSERT INTO mci_business_images
      (id, business_group_id, file_path, sort_order,
       uploaded_by_user_id, created_by_user_id)
    VALUES (?, ?, ?, ?, ?, ?)
');
```

- [ ] **Step 1.3: Verify the fix manually**

Submit a test business as anonymous (pure guest) leaving the city field blank at `/submit-business-listing/`.

Expected: `{ ok: true, id: "...", slug: "..." }` — no 500.

If you have MySQL access:
```sql
SELECT city FROM mci_business_branches ORDER BY created_at DESC LIMIT 1;
-- Expected: '' (empty string), not NULL
```

- [ ] **Step 1.4: Commit**

```bash
git add api/v1/lib/business_service.php
git commit -m "fix: city null→empty string to fix anonymous submission 500; fix gallery INSERT column image_path→file_path"
```

---

## Task 2: Extend Upload Endpoint + Add PATCH Images Endpoint + Extend Create Response

**Files:**
- Modify: `api/v1/lib/business_service.php` — add `api_business_patch_images()`; extend `api_business_create()` to return `product_ids`/`service_ids`
- Modify: `api/v1/index.php` — extend upload endpoint; add PATCH route (insert after the existing `POST /api/v1/businesses` block at ~line 1113, before the CP block at ~line 1122)

### Step 2A — Extend `api_business_create()` to return product/service IDs

- [ ] **Step 2.1: Initialise accumulator arrays**

In `api/v1/lib/business_service.php`, find the line `$actorId = $ctx['added_by_user_id'];` (around line 86). Add two lines immediately after it:

```php
$actorId    = $ctx['added_by_user_id'];
$productIds = [];   // ← add
$serviceIds = [];   // ← add
```

These are declared outside the try block so they survive into the return even if the products/services loops run partially.

- [ ] **Step 2.2: Capture product UUIDs in the INSERT loop**

Find the `// --- mci_business_products ---` block (around line 240). Replace the loop to capture UUIDs. The key change is generating the UUID into a variable first, then collecting it:

```php
// --- mci_business_products ---
$products = $data['products'] ?? [];
if (is_array($products)) {
    $prodStmt = $pdo->prepare('
        INSERT INTO mci_business_products
          (id, business_group_id, name, description,
           price_min, price_max, price_unit, image_path,
           sort_order, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    foreach ($products as $i => $p) {
        $pName = trim((string)($p['name'] ?? ''));
        if ($pName === '') {
            continue;
        }
        $pid = api_uuid_v4();       // ← generate once
        $productIds[] = $pid;       // ← collect only rows that are actually inserted
        $prodStmt->execute([
            $pid, $groupId,         // ← use $pid instead of inline api_uuid_v4()
            $pName,
            trim((string)($p['description'] ?? '')) ?: null,
            is_numeric($p['price_min'] ?? '') ? (float)$p['price_min'] : null,
            is_numeric($p['price_max'] ?? '') ? (float)$p['price_max'] : null,
            trim((string)($p['price_unit'] ?? 'INR')) ?: 'INR',
            trim((string)($p['image_path'] ?? '')) ?: null,
            $i,
            $actorId,
        ]);
    }
}
```

- [ ] **Step 2.3: Capture service UUIDs in the INSERT loop**

Same pattern for `// --- mci_business_services ---` (around line 268):

```php
// --- mci_business_services ---
$services = $data['services'] ?? [];
if (is_array($services)) {
    $svcStmt = $pdo->prepare('
        INSERT INTO mci_business_services
          (id, business_group_id, name, description,
           price_min, price_max, price_unit, image_path,
           sort_order, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    foreach ($services as $i => $s) {
        $sName = trim((string)($s['name'] ?? ''));
        if ($sName === '') {
            continue;
        }
        $sid = api_uuid_v4();       // ← generate once
        $serviceIds[] = $sid;       // ← collect only rows that are actually inserted
        $svcStmt->execute([
            $sid, $groupId,         // ← use $sid
            $sName,
            trim((string)($s['description'] ?? '')) ?: null,
            is_numeric($s['price_min'] ?? '') ? (float)$s['price_min'] : null,
            is_numeric($s['price_max'] ?? '') ? (float)$s['price_max'] : null,
            trim((string)($s['price_unit'] ?? 'INR')) ?: 'INR',
            trim((string)($s['image_path'] ?? '')) ?: null,
            $i,
            $actorId,
        ]);
    }
}
```

- [ ] **Step 2.4: Include product_ids and service_ids in the return value**

Find the `$result = [` block near line 379. Replace it:

```php
$result = [
    'ok'          => true,
    'id'          => $groupId,
    'slug'        => $groupSlug,
    'branch_id'   => $branchId,
    'product_ids' => $productIds,   // ← add: insertion-order UUIDs
    'service_ids' => $serviceIds,   // ← add: insertion-order UUIDs
];
```

Also find the `$out = [...]` array in `api/v1/index.php` around line 1105 (inside the POST /api/v1/businesses handler) and add the new fields:

```php
$out = [
    'ok'          => true,
    'id'          => $res['id'],
    'slug'        => $res['slug'],
    'branch_id'   => $res['branch_id'],
    'product_ids' => $res['product_ids'] ?? [],   // ← add
    'service_ids' => $res['service_ids'] ?? [],   // ← add
];
```

### Step 2B — Add `api_business_patch_images()` to `business_service.php`

- [ ] **Step 2.5: Add the complete patch images function**

Append this full function at the end of `api/v1/lib/business_service.php`:

```php
// ---------------------------------------------------------------------------
// Patch images — save uploaded image paths after business creation
// ---------------------------------------------------------------------------

/**
 * Save uploaded image paths to a business group.
 *
 * $images keys (all optional):
 *   logo_path       string
 *   profile_path    string
 *   banner_path     string
 *   gallery_paths   string[]
 *   product_images  array of {id: string, path: string}
 *   service_images  array of {id: string, path: string}
 *
 * All paths validated to start with /storage/uploads/.
 * Returns ['ok'=>true] or ['ok'=>false, 'error'=>string, 'status'=>int]
 */
function api_business_patch_images(PDO $pdo, string $groupId, array $images, string $actorId): array
{
    // Verify group exists
    $stmt = $pdo->prepare('SELECT id FROM mci_business_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'error' => 'business_not_found', 'status' => 404];
    }

    // Path safety: all paths must start with /storage/uploads/
    $safePath = function (mixed $v): ?string {
        $s = trim((string)$v);
        if ($s === '' || !str_starts_with($s, '/storage/uploads/')) {
            return null;
        }
        return $s;
    };

    $logoPath    = $safePath($images['logo_path']    ?? '');
    $profilePath = $safePath($images['profile_path'] ?? '');
    $bannerPath  = $safePath($images['banner_path']  ?? '');
    $galleryPaths  = array_values(array_filter(
        array_map(fn($p) => $safePath($p), (array)($images['gallery_paths'] ?? []))
    ));
    $productImages = (array)($images['product_images'] ?? []);
    $serviceImages = (array)($images['service_images'] ?? []);

    $pdo->beginTransaction();
    try {
        // Update group: only set columns where a valid path was provided
        $setClauses = [];
        $setParams  = [];
        if ($logoPath !== null)    { $setClauses[] = 'logo_path = ?';    $setParams[] = $logoPath; }
        if ($profilePath !== null) { $setClauses[] = 'profile_path = ?'; $setParams[] = $profilePath; }
        if ($bannerPath !== null)  { $setClauses[] = 'banner_path = ?';  $setParams[] = $bannerPath; }
        if ($setClauses !== []) {
            $setParams[] = $groupId;
            $pdo->prepare('UPDATE mci_business_groups SET ' . implode(', ', $setClauses) . ' WHERE id = ?')
                ->execute($setParams);
        }

        // Gallery: INSERT using file_path column (NOT image_path — schema uses file_path)
        if ($galleryPaths !== []) {
            $imgStmt = $pdo->prepare('
                INSERT INTO mci_business_images
                  (id, business_group_id, file_path, sort_order,
                   uploaded_by_user_id, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($galleryPaths as $i => $path) {
                $imgStmt->execute([api_uuid_v4(), $groupId, $path, $i, $actorId, $actorId]);
            }
        }

        // Products: match by row id + business_group_id for safety
        // Note: mci_business_products uses image_path column (not file_path)
        foreach ($productImages as $entry) {
            $rowId = trim((string)($entry['id'] ?? ''));
            $path  = $safePath($entry['path'] ?? '');
            if ($rowId === '' || $path === null) {
                continue;
            }
            $pdo->prepare('UPDATE mci_business_products SET image_path = ? WHERE id = ? AND business_group_id = ?')
                ->execute([$path, $rowId, $groupId]);
        }

        // Services: same pattern
        foreach ($serviceImages as $entry) {
            $rowId = trim((string)($entry['id'] ?? ''));
            $path  = $safePath($entry['path'] ?? '');
            if ($rowId === '' || $path === null) {
                continue;
            }
            $pdo->prepare('UPDATE mci_business_services SET image_path = ? WHERE id = ? AND business_group_id = ?')
                ->execute([$path, $rowId, $groupId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('api_business_patch_images error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    return ['ok' => true];
}
```

### Step 2C — Extend upload endpoint in `index.php`

- [ ] **Step 2.6: Replace the directory-building + response section of the upload handler**

In `api/v1/index.php`, find the upload block (lines 953–992). Replace lines 980–991 (the dir-building and response lines) with the following extended version. Everything before line 980 (type validation, file checks, MIME checks, ext map) stays unchanged:

```php
    // ── Per-business folder routing ──────────────────────────────────
    $businessId = trim((string)($_POST['business_id'] ?? ''));
    $subtype    = trim((string)($_POST['subtype']     ?? ''));
    $uuidV4Re   = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    $docRoot    = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    if ($businessId !== '' && preg_match($uuidV4Re, $businessId)) {
        // Per-business path
        $folder = $type;
        if ($type === 'item_image') {
            $folder = in_array($subtype, ['services', 'products'], true) ? $subtype : 'products';
        }
        $dir      = $docRoot . '/storage/uploads/businesses/' . $businessId . '/' . $folder;
        $pathBase = '/storage/uploads/businesses/' . $businessId . '/' . $folder;
    } else {
        // Flat backward-compat path (item_image → /storage/uploads/item_image/)
        $dir      = $docRoot . '/storage/uploads/' . $type;
        $pathBase = '/storage/uploads/' . $type;
    }
    // ────────────────────────────────────────────────────────────────

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    require_once __DIR__ . '/lib/uuid.php';
    $filename = api_uuid_v4() . '.' . $ext;
    $dest     = $dir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $dest)) {
        api_error('upload_failed', 500);
    }
    $path = $pathBase . '/' . $filename;   // ← uses $pathBase, not hardcoded string
    api_json(['ok' => true, 'path' => $path]);
}
```

### Step 2D — Add PATCH route in `index.php`

- [ ] **Step 2.7: Add `PATCH /api/v1/businesses/{id}/images` route**

**Insertion point:** After the closing `}` of the `POST /api/v1/businesses` block (around line 1113), and **before** the CP block that starts with `if (($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'businesses')` (around line 1122).

```php
// =============================================================================
// Business images — save uploaded paths after creation
// PATCH /api/v1/businesses/{id}/images
// =============================================================================
if ($method === 'PATCH' && ($segments[0] ?? '') === 'businesses' && ($segments[2] ?? '') === 'images' && isset($segments[1])) {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $groupId = (string)$segments[1];
    $data    = api_request_data();

    // Optional auth — nil UUID for anonymous
    $auth    = api_business_try_auth();
    $actorId = $auth['user_id'] ?? '00000000-0000-0000-0000-000000000000';

    $pdo = api_db();
    $res = api_business_patch_images($pdo, $groupId, $data, $actorId);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json(['ok' => true]);
}
```

- [ ] **Step 2.8: Verify the upload endpoint via curl**

Test per-business routing (replace `<groupId>` with a real UUID from your DB):

```bash
curl -X POST http://localhost/api/v1/upload/image \
  -F "type=logo" \
  -F "business_id=<groupId>" \
  -F "file=@/path/to/test.jpg"
```

Expected: `{"ok":true,"path":"/storage/uploads/businesses/<groupId>/logo/<uuid>.jpg"}`

Test flat fallback:

```bash
curl -X POST http://localhost/api/v1/upload/image \
  -F "type=logo" \
  -F "file=@/path/to/test.jpg"
```

Expected: `{"ok":true,"path":"/storage/uploads/logo/<uuid>.jpg"}`

- [ ] **Step 2.9: Verify the PATCH endpoint via curl**

```bash
curl -X PATCH http://localhost/api/v1/businesses/<groupId>/images \
  -H "Content-Type: application/json" \
  -d '{"logo_path":"/storage/uploads/businesses/<groupId>/logo/<uuid>.jpg"}'
```

Expected: `{"ok":true}`

Verify in DB:
```sql
SELECT logo_path FROM mci_business_groups WHERE id = '<groupId>';
-- Expected: /storage/uploads/businesses/<groupId>/logo/<uuid>.jpg
```

- [ ] **Step 2.10: Commit**

```bash
git add api/v1/lib/business_service.php api/v1/index.php
git commit -m "feat: extend upload endpoint with per-business folder routing; add PATCH /businesses/{id}/images; return product_ids/service_ids from create"
```

---

## Task 3: Refactor JS Submit Flow — Deferred Uploads

**Files:**
- Modify: `assets/js/subscriber-list-business.js`

### Background

Currently the JS uploads images immediately when picked. Strategy:

1. Add `pendingUploads` Map — keys are slot strings, values are `{ file, type, subtype? }`
2. Product/service items use **DOM-order index as temporary key** during form fill (e.g. `product_0`, `product_1`). After `POST /api/v1/businesses` returns `product_ids`, remap by position to `product_<uuid>`.
3. Gallery items use `gallery_0`, `gallery_1`, etc.
4. Cropped images for logo/banner/profile use the type name directly (`'logo'`, `'banner'`, `'profile'`).
5. On submit: create → upload all pending files (per-business folder) → PATCH paths → redirect.
6. Upload errors are non-fatal: continue uploading remaining files, PATCH with whatever succeeded.
7. Add `beforeunload` warning; clear `pendingUploads` and remove the warning trigger before redirect.

### Step 3A — Add `pendingUploads` Map and `beforeunload` guard

- [ ] **Step 3.1: Add the Map and beforeunload listener**

Near the top of the `$(function() { ... })` block, right after the line `var cropModal = new bootstrap.Modal(...)` (around line 451), add:

```js
// ── Deferred upload slot map ──────────────────────────────────────
// Holds File/Blob objects to be uploaded after business creation.
// Key: 'logo' | 'banner' | 'profile' | 'gallery_N' | 'product_N' | 'product_<uuid>' | 'service_N' | 'service_<uuid>'
// Value: { file: File|Blob, type: string, subtype?: string }
var pendingUploads = new Map();

window.addEventListener('beforeunload', function (e) {
  if (pendingUploads.size > 0) {
    e.preventDefault();
    e.returnValue = 'You have images selected that haven\'t been uploaded yet. Leave anyway?';
  }
});
```

### Step 3B — Replace image-pick handlers with slot-map storage

- [ ] **Step 3.2: Replace the cropper `applyCropBtn` handler**

Find `$('#applyCropBtn').on('click', ...)` (around line 489). Replace the `canvas.toBlob(...)` section inside it so it stores the blob instead of uploading:

```js
$('#applyCropBtn').on('click', function () {
  if (!cropperInstance || !cropTarget) return;
  var isSquare = cropTarget.aspect === 1;
  var outW = isSquare ? 600 : 1280;
  var outH = isSquare ? 600 : Math.round(1280 / cropTarget.aspect);
  var canvas = cropperInstance.getCroppedCanvas({ width: outW, height: outH, imageSmoothingQuality: 'high' });
  var type = cropTarget.type || 'logo';

  canvas.toBlob(function (blob) {
    // Store in slot map — upload happens at submit time
    pendingUploads.set(type, { file: blob, type: type });

    // Show local preview (no server call)
    var dataUrl = canvas.toDataURL('image/jpeg', 0.88);
    var $preview = cropTarget.$tile.find('.mci-img-upload-tile__preview').first();
    $preview.find('.mci-img-upload-tile__placeholder').hide();
    $preview.find('.mci-img-tile-result').remove();
    $('<img class="mci-img-tile-result" alt="preview" />').attr('src', dataUrl).appendTo($preview);
    cropTarget.$tile.addClass('has-image');
    cropTarget.hiddenInput.value = '';  // clear any stale server path
    if (cropTarget.isLogo) {
      $('#previewPhotoImg').attr('src', dataUrl).removeClass('d-none');
      $('#previewPhotoPlaceholder').addClass('d-none');
    }
  }, 'image/jpeg', 0.88);
  cropModal.hide();
});
```

- [ ] **Step 3.3: Replace the item image upload handler**

Find `$(document).on('change', '.mci-item-file-input', ...)` (around line 532). Replace the handler body so it stores the file using a DOM-order index key:

```js
$(document).on('change', '.mci-item-file-input', function () {
  var file = this.files[0];
  if (!file || !file.type.startsWith('image/')) return;
  var $row    = $(this).closest('.mci-item-row');
  var $input  = $row.find('input[name$="_image_path[]"]');
  var $label  = $row.find('.mci-item-img-name');
  var isProduct = $row.closest('#productItems').length > 0;
  var subtype   = isProduct ? 'products' : 'services';
  // Key by current DOM index — will be remapped to server UUID after create
  var domIndex  = $row.closest('#productItems, #serviceItems').find('.mci-item-row').index($row);
  var slotKey   = (isProduct ? 'product_' : 'service_') + domIndex;
  pendingUploads.set(slotKey, { file: file, type: 'item_image', subtype: subtype });
  $input.val('');
  $label.text(file.name);
  this.value = '';
});
```

- [ ] **Step 3.4: Replace the gallery upload function with local-preview-only version**

Find `function uploadGalleryFiles(files)` (around line 560). Replace the entire function body:

```js
function uploadGalleryFiles(files) {
  if (!files || !files.length) return;
  // Clear any previous gallery slots
  Array.from(pendingUploads.keys()).filter(function (k) {
    return k.startsWith('gallery_');
  }).forEach(function (k) { pendingUploads.delete(k); });

  preview.innerHTML = '';
  for (var i = 0; i < Math.min(files.length, 12); i++) {
    (function (f, idx) {
      if (!f.type || !f.type.startsWith('image/')) return;
      pendingUploads.set('gallery_' + idx, { file: f, type: 'gallery' });
      // Show local preview
      var url = URL.createObjectURL(f);
      var wrap = document.createElement('div');
      wrap.className = 'mci-photo-thumb';
      var img = document.createElement('img');
      img.src = url; img.alt = f.name;
      wrap.appendChild(img);
      preview.appendChild(wrap);
      if (idx === 0) {
        $('#previewPhotoImg').attr('src', url).attr('alt', f.name).removeClass('d-none');
        $('#previewPhotoPlaceholder').addClass('d-none');
      }
    })(files[i], i);
  }
  $('#galleryPathsHidden').val('');  // clear hidden field — paths set via PATCH after submit
}
```

### Step 3C — Update `buildPayload()` to strip image fields

- [ ] **Step 3.5: Remove image paths from `buildPayload()`**

In `buildPayload()` (around line 801), make these changes:

In the `group` object, replace the three image path lines:
```js
// Before
logo_path:    $('input[name="img_logo"]').val()    || '',
profile_path: $('input[name="img_profile"]').val() || '',
banner_path:  $('input[name="img_banner"]').val()  || ''

// After — always empty; paths saved via PATCH after create
logo_path:    '',
profile_path: '',
banner_path:  ''
```

In the `products` loop, change the `image_path` line:
```js
// Before
image_path: $(this).find('input[name="product_image_path[]"]').val() || ''

// After
image_path: ''   // deferred — set via PATCH after create
```

In the `services` loop:
```js
// Before
image_path: $(this).find('input[name="service_image_path[]"]').val() || ''

// After
image_path: ''   // deferred — set via PATCH after create
```

Change the `gallery_paths` line:
```js
// Before
gallery_paths: galleryPaths   // (parsed from #galleryPathsHidden)

// After
gallery_paths: []   // deferred — set via PATCH after create
```

You can also remove the `var galleryPaths = []` and `try { galleryPaths = JSON.parse(...) }` lines above it since they are no longer used.

### Step 3D — Replace the submit handler

- [ ] **Step 3.6: Replace the full submit handler**

Find `$('#mciSubmitForm').on('submit', ...)` (around line 926). Replace the entire handler:

```js
$('#mciSubmitForm').on('submit', function (e) {
  e.preventDefault();

  var $btn     = $('#submitBtn');
  var btnLabel = '<i class="bi bi-check2-circle me-2" aria-hidden="true"></i>' + (window._mciSubmitBtnText || 'Submit');

  function setPhase(msg) {
    $btn.prop('disabled', true).html(
      '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + msg
    );
  }
  function resetBtn() {
    $btn.prop('disabled', false).html(btnLabel);
  }

  setPhase('Submitting\u2026');
  var payload = buildPayload();

  // ── Phase 1: Create business ─────────────────────────────────────
  fetch('/api/v1/businesses', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
  .then(function (res) {
    if (!res.ok || !res.data.ok) {
      var msg = (res.data && res.data.error) ? res.data.error : 'Submission failed. Please try again.';
      alert(msg);
      resetBtn();
      return;
    }

    var groupId    = res.data.id;
    var productIds = res.data.product_ids || [];   // insertion-order UUIDs
    var serviceIds = res.data.service_ids || [];

    try { localStorage.removeItem('mci_listing_preview'); } catch (e2) {}

    // ── Remap temp product/service keys → server UUIDs ───────────────
    // Products: collect slot keys in Map insertion order (matches DOM order)
    var productKeys = Array.from(pendingUploads.keys()).filter(function (k) {
      return /^product_\d+$/.test(k);
    });
    productKeys.forEach(function (tempKey, idx) {
      var realId = productIds[idx];
      if (!realId) return;
      var val = pendingUploads.get(tempKey);
      pendingUploads.delete(tempKey);
      pendingUploads.set('product_' + realId, val);
    });

    var serviceKeys = Array.from(pendingUploads.keys()).filter(function (k) {
      return /^service_\d+$/.test(k);
    });
    serviceKeys.forEach(function (tempKey, idx) {
      var realId = serviceIds[idx];
      if (!realId) return;
      var val = pendingUploads.get(tempKey);
      pendingUploads.delete(tempKey);
      pendingUploads.set('service_' + realId, val);
    });

    // ── Phase 2: Upload pending images ───────────────────────────────
    var uploadEntries = Array.from(pendingUploads.entries());
    var total = uploadEntries.length;

    if (total === 0) {
      pendingUploads.clear();
      window.location.href = submitRedirect;
      return;
    }

    var uploaded = 0;
    var collectedPaths = {};  // slotKey → server path

    function uploadNext(idx) {
      if (idx >= uploadEntries.length) {
        doPatch(collectedPaths);
        return;
      }
      var slotKey = uploadEntries[idx][0];
      var entry   = uploadEntries[idx][1];
      setPhase('Uploading images\u2026 (' + (uploaded + 1) + '/' + total + ')');

      var fd = new FormData();
      fd.append('type', entry.type);
      fd.append('business_id', groupId);
      if (entry.subtype) fd.append('subtype', entry.subtype);
      fd.append('file', entry.file, slotKey + '.jpg');

      fetch('/api/v1/upload/image', { method: 'POST', credentials: 'include', body: fd })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (data) {
          if (data.path) collectedPaths[slotKey] = data.path;
          uploaded++;
          uploadNext(idx + 1);
        })
        .catch(function () {
          // Non-fatal: skip failed upload, continue with rest
          uploaded++;
          uploadNext(idx + 1);
        });
    }

    uploadNext(0);

    // ── Phase 3: PATCH image paths ───────────────────────────────────
    function doPatch(paths) {
      setPhase('Finalising\u2026');

      var patchBody = {};
      var galleryPaths   = [];
      var productImages  = [];
      var serviceImages  = [];

      Object.keys(paths).forEach(function (key) {
        var p = paths[key];
        if (key === 'logo')              { patchBody.logo_path    = p; }
        else if (key === 'banner')       { patchBody.banner_path  = p; }
        else if (key === 'profile')      { patchBody.profile_path = p; }
        else if (key.startsWith('gallery_')) { galleryPaths.push(p); }
        else if (key.startsWith('product_')) {
          productImages.push({ id: key.replace('product_', ''), path: p });
        }
        else if (key.startsWith('service_')) {
          serviceImages.push({ id: key.replace('service_', ''), path: p });
        }
      });

      if (galleryPaths.length)  patchBody.gallery_paths  = galleryPaths;
      if (productImages.length) patchBody.product_images = productImages;
      if (serviceImages.length) patchBody.service_images = serviceImages;

      // Clear pending map before redirect so beforeunload doesn't fire
      pendingUploads.clear();

      if (Object.keys(patchBody).length === 0) {
        // All uploads failed or no paths to save
        window.location.href = submitRedirect;
        return;
      }

      fetch('/api/v1/businesses/' + groupId + '/images', {
        method: 'PATCH',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(patchBody)
      })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
      .then(function (patchRes) {
        var redirect = submitRedirect;
        if (!patchRes.ok || !patchRes.data.ok) {
          redirect += (redirect.indexOf('?') >= 0 ? '&' : '?') + 'images_failed=1';
        }
        window.location.href = redirect;
      })
      .catch(function () {
        window.location.href = submitRedirect +
          (submitRedirect.indexOf('?') >= 0 ? '&' : '?') + 'images_failed=1';
      });
    }
  })
  .catch(function () {
    alert('Network error. Please check your connection and try again.');
    resetBtn();
  });
});
```

- [ ] **Step 3.7: Manual end-to-end test**

1. Open `/submit-business-listing/`
2. Fill required fields — leave city blank
3. Pick a logo image — confirm NO network request fires to `/api/v1/upload/image` (check browser DevTools Network tab). Preview should appear from a blob URL.
4. Add a product with a name, pick an item image — confirm no upload fires
5. Add 2 gallery images — confirm local previews appear, no upload fires
6. Hit Submit
7. Watch DevTools Network: should see in order:
   - `POST /api/v1/businesses` → 201
   - `POST /api/v1/upload/image` (logo) → 200
   - `POST /api/v1/upload/image` (product image) → 200
   - `POST /api/v1/upload/image` (gallery ×2) → 200
   - `PATCH /api/v1/businesses/{id}/images` → 200
8. Confirm redirect to success page (no `?images_failed=1`)
9. Verify in DB:
   ```sql
   SELECT logo_path FROM mci_business_groups ORDER BY created_at DESC LIMIT 1;
   -- Expected: /storage/uploads/businesses/<id>/logo/<uuid>.jpg

   SELECT file_path FROM mci_business_images WHERE business_group_id = '<id>';
   -- Expected: gallery paths under businesses/<id>/gallery/
   ```
10. Verify files exist on disk in `/storage/uploads/businesses/{id}/`

- [ ] **Step 3.8: Test `beforeunload` warning**

1. Pick an image without submitting
2. Try to navigate away or refresh
3. Confirm browser shows a leave-page dialog

- [ ] **Step 3.9: Commit**

```bash
git add assets/js/subscriber-list-business.js
git commit -m "feat: defer image uploads to after business creation; deferred slot map; beforeunload warning; per-business folder routing"
```

---

## Final Verification Checklist

- [ ] Anonymous user submits with no city → no 500, listing created
- [ ] Anonymous user submits with images → files stored in `/storage/uploads/businesses/{id}/`
- [ ] Existing flat-dir uploads still work (old business pages unaffected)
- [ ] Upload endpoint without `business_id` routes to flat dirs (backward compat)
- [ ] `item_image` + `subtype=services` → `businesses/{id}/services/`
- [ ] `item_image` + `subtype=products` → `businesses/{id}/products/`
- [ ] Gallery DB column is `file_path` — no SQL error on gallery submit
- [ ] `beforeunload` fires when images are pending
- [ ] Partial upload failure (one image fails) → other images still uploaded + redirect with `?images_failed=1`

---

## Rollback Notes

All changes are additive or backward-compatible:
- Task 1: `null` → `''` one-char change + column name fix, safe to revert
- Task 2: new route + extended upload logic; removing PATCH route and reverting upload handler restores prior behaviour with no DB impact
- Task 3: JS only; revert `subscriber-list-business.js` from git to restore immediate-upload behaviour
