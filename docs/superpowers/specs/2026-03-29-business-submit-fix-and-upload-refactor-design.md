# Design: Business Submit Bug Fix + Deferred Upload Refactor

**Date:** 2026-03-29
**Branch:** mci-13
**Status:** Approved

---

## Problem Statement

1. **Server error on anonymous business submission** — `POST /api/v1/businesses` returns 500 for anonymous (guest) users. Root cause: `mci_business_branches.city` is `NOT NULL` in the schema, but `business_service.php` passes `null` when city is blank, causing MySQL to reject the INSERT.

2. **Unorganised image storage** — images are stored in flat global dirs (`/storage/uploads/logo/`, `/storage/uploads/banner/`, etc.) with no association to the business they belong to.

3. **Premature uploads** — images are uploaded to the server immediately when the user picks them during the multi-step form, before any business record exists. This means image files have no `businessId`-scoped path and cannot be organised per-business.

---

## Goals

- Fix the anonymous submission server error.
- Refactor image uploads to be per-business (`/storage/uploads/businesses/{groupId}/{type}/`).
- Defer image uploads until after the business record is created (Variant 1: upload at submit time).
- Keep backward compatibility for existing flat-path uploads.
- No data migration required for existing records.

---

## Out of Scope

- Cloud storage (S3 / GCS) — future concern.
- Image editing/management post-submission.
- Migration of existing uploaded files to new structure.

---

## Section 1: Bug Fix — `city` NOT NULL Violation

### Root Cause

`mci_business_branches.city` is defined `NOT NULL` (migration `008`). In `api_business_create()` ([business_service.php:190](../../../api/v1/lib/business_service.php#L190)):

```php
// current — sends NULL when city is blank → MySQL rejects INSERT
$city !== '' ? $city : null,
```

MySQL throws an integrity constraint violation → transaction rolls back → catch block returns `server_error` / 500.

### Fix

Store empty string instead of NULL, matching the pattern already used for `address_line1`:

```php
// fixed
$city !== '' ? $city : '',
```

### Additional Hardening

Add a frontend validation check and a backend `city_required` validation error in `api_business_validate_minimum()` if city is empty, so the user gets a meaningful message instead of a generic 500. This is a UX improvement, not strictly required for the fix.

Also audit all other `NOT NULL` branch columns to confirm no similar issues exist:
- `address_line1` — already fixed (previous commit)
- `city` — fixed by this task
- `country` — has `DEFAULT 'India'`, safe
- `slug` — generated server-side, safe
- `status` — hardcoded `'active'`, safe

---

## Section 2: Frontend — Deferred Image Upload

### Current Behaviour

Image picked → immediately `POST /api/v1/upload/image` → server path stored in hidden input → path sent as part of final JSON payload.

### New Behaviour

**During form fill:**
- Image picked → stored as a `File` object (or cropped canvas `Blob`) in a JS slot map keyed by type: `logo`, `profile`, `banner`, `gallery[n]`, `product[n]`, `service[n]`
- Preview shown using `URL.createObjectURL(file)` — no server call
- Hidden path inputs remain empty (no server path yet)

**On Submit:**
1. Collect all pending `File`/`Blob` entries from the slot map
2. `POST /api/v1/businesses` → receive `{ groupId, slug, branch_id }`
3. Upload each pending file sequentially (or in small parallel batches) to `POST /api/v1/upload/image` with `business_id = groupId`
4. Collect returned paths
5. `PATCH /api/v1/businesses/{groupId}/images` with all paths
6. Redirect to success page

**Cropper integration:** The cropped canvas blob replaces the raw `File` in the slot map. No change to the cropper UI logic — only the upload trigger moves from "after crop confirm" to "on submit".

**Submit button state:** Shows a multi-phase progress indicator:
- "Submitting listing…"
- "Uploading images… (2/5)"
- "Finalising…"

**Error handling:** If image upload or PATCH fails after business creation, the listing still exists (without images). The error message tells the user their listing was saved but images failed, and they can re-upload later (future feature). This avoids data loss.

### Slot Map Structure (JS)

```js
// pendingUploads: Map<slotKey, { file: File|Blob, type: string, subtype?: string, index?: number }>
// slotKey examples: 'logo', 'banner', 'profile', 'gallery_0', 'product_2', 'service_1'
```

---

## Section 3: Upload Endpoint — Per-Business Folder Structure

### New Folder Structure

```
/storage/uploads/
  businesses/
    {groupId}/
      logo/
      banner/
      profile/
      gallery/
      services/
      products/
  logo/          ← existing flat dirs kept for backward compat
  banner/
  gallery/
  item_image/
  profile/
```

### Upload Endpoint Changes

**Endpoint:** `POST /api/v1/upload/image` (same URL, extended params)

**New params:**
- `business_id` (optional) — UUID of the business group. If provided, files go into per-business dirs.
- `subtype` (optional) — `'services'` or `'products'` when `type=item_image`. Determines subfolder under `businesses/{id}/`.

**Routing logic:**
```
if business_id provided and valid UUID format:
    dir = /storage/uploads/businesses/{business_id}/{type}/
    (for item_image: use subtype if provided, else 'products')
else:
    dir = /storage/uploads/{type}/    ← backward compat
```

**Security:**
- `business_id` validated as UUID v4 format (regex) — no DB lookup required
- File type, size, MIME validation unchanged (2MB limit, jpeg/png/webp/gif only)
- Filename: UUID v4 + extension (no change)
- Directory created with `mkdir($dir, 0755, true)` if not exists

**Response:** unchanged — `{ ok: true, path: "/storage/uploads/businesses/{id}/{type}/{uuid}.ext" }`

---

## Section 4: New API Endpoint — PATCH Business Images

**Endpoint:** `PATCH /api/v1/businesses/{groupId}/images`

**Auth:** Optional — same as `POST /api/v1/businesses` (anonymous allowed). No ownership check needed at this stage (anonymous users don't have accounts to verify against). Can be tightened later.

**Request body:**
```json
{
  "logo_path":     "/storage/uploads/businesses/{id}/logo/uuid.jpg",
  "profile_path":  "/storage/uploads/businesses/{id}/profile/uuid.jpg",
  "banner_path":   "/storage/uploads/businesses/{id}/banner/uuid.jpg",
  "gallery_paths": [
    "/storage/uploads/businesses/{id}/gallery/uuid1.jpg",
    "/storage/uploads/businesses/{id}/gallery/uuid2.jpg"
  ],
  "product_images": [
    { "index": 0, "path": "/storage/uploads/businesses/{id}/products/uuid.jpg" }
  ],
  "service_images": [
    { "index": 0, "path": "/storage/uploads/businesses/{id}/services/uuid.jpg" }
  ]
}
```

**DB operations (all in a transaction):**
1. `UPDATE mci_business_groups SET logo_path=?, profile_path=?, banner_path=? WHERE id=?` — only updates fields that are non-null in the payload
2. `INSERT INTO mci_business_images (id, business_group_id, image_path, sort_order, ...)` for each gallery path
3. For each `product_images` entry: `UPDATE mci_business_products SET image_path=? WHERE business_group_id=? AND sort_order=?`
4. For each `service_images` entry: `UPDATE mci_business_services SET image_path=? WHERE business_group_id=? AND sort_order=?`

**Response:** `{ ok: true }`

**Path validation:** All paths must match the pattern `/storage/uploads/...` — reject anything else to prevent path injection.

---

## Files to Change

| File | Change |
|------|--------|
| `api/v1/lib/business_service.php` | Fix `city` null → `''`; add `api_business_patch_images()` function |
| `api/v1/lib/business_helpers.php` | Add `city_required` validation to `api_business_validate_minimum()` |
| `api/v1/index.php` | Extend upload endpoint for `business_id`/`subtype`; add `PATCH /businesses/{id}/images` route |
| `assets/js/subscriber-list-business.js` | Refactor image pickers to deferred slot map; update submit flow |

---

## Backward Compatibility

- Existing flat-dir uploads (`/storage/uploads/logo/`, etc.) continue to work — paths stored in DB are not changed.
- Upload endpoint still works without `business_id` — falls back to flat structure.
- No DB schema changes required.

---

## Non-Goals / Future Work

- Re-uploading / replacing images after submission
- Image CDN / cloud storage
- Migrating existing flat-dir files to per-business dirs
- Image compression / resizing server-side
