# Design: Business Submit Bug Fix + Deferred Upload Refactor

**Date:** 2026-03-29
**Branch:** mci-13
**Status:** Ready for Implementation (rev 2 — design complete, code changes pending)

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

This is the complete fix. City can legitimately be left blank by an anonymous user; we do not block submission on it.

### NOT NULL Column Audit

Confirm no other branch columns have the same issue:
- `address_line1` — already fixed (previous commit), stores `''`
- `city` — fixed by this task, stores `''`
- `country` — has `DEFAULT 'India'`, safe
- `slug` — generated server-side, safe
- `status` — hardcoded `'active'` in SQL, safe

No schema changes needed.

---

## Section 2: Frontend — Deferred Image Upload

### Current Behaviour

Image picked → immediately `POST /api/v1/upload/image` → server path stored in hidden input → path sent as part of final JSON payload.

### New Behaviour

**During form fill:**
- Image picked → stored as a `File` object (or cropped canvas `Blob`) in a JS `pendingUploads` Map, keyed by slot. No server call.
- Preview shown using `URL.createObjectURL(file)`.
- Hidden path inputs remain empty — no server path yet.

**On Submit:**
1. `POST /api/v1/businesses` (JSON payload, no image paths) → receive `{ ok, id, slug, branch_id }` — note: field is `id`, not `groupId`
2. Upload each pending file to `POST /api/v1/upload/image` with `business_id = id` from step 1, collecting returned paths
3. `PATCH /api/v1/businesses/{id}/images` with all collected paths + product/service row IDs (see Section 4)
4. Redirect to success page

**Cropper integration:** The cropped canvas blob replaces the raw `File` in the slot map. Cropper UI is unchanged — only the upload trigger moves from "after crop confirm" to "on submit".

**Submit button state:** Multi-phase progress indicator:
- "Submitting listing…"
- "Uploading images… (2/5)"
- "Finalising…"

**Error handling:** If image upload or PATCH fails after business creation, the listing still exists (without images). User sees: "Your listing was saved, but some images failed to upload. You can add them later." — then redirect still happens. This avoids listing data loss.

**Partial failure redirect:** Redirect to success page regardless. A `?images_failed=1` query param is appended if the image PATCH step fails, so the success page can show the appropriate notice.

**Page refresh / data loss:** `File` objects in the JS Map are volatile — lost on page refresh or navigation. This is a **known accepted regression** vs the current behaviour (which uploads immediately). To mitigate: add a `window.beforeunload` warning when `pendingUploads.size > 0`, warning the user they have unsaved image selections. Persisting files across page refreshes via IndexedDB is out of scope.

### Slot Map Structure (JS)

```js
// pendingUploads: Map<slotKey, { file: File|Blob, type: string, subtype?: string, rowId?: string }>
// slotKey examples: 'logo', 'banner', 'profile', 'gallery_0', 'product_<uuid>', 'service_<uuid>'
// rowId: the UUID of the product/service row, obtained from the POST /api/v1/businesses response
```

Products and services use `product_<uuid>` / `service_<uuid>` as slot keys so that the PATCH call can match images to the correct DB row by ID (not by sort_order — see Section 4).

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
  logo/          ← existing flat dirs, kept for backward compat
  banner/
  gallery/
  item_image/    ← existing flat dir for item_image type, kept as-is
  profile/
```

**Note on `item_image`:** For the flat-dir backward-compat path (no `business_id`), `type=item_image` continues to resolve to `/storage/uploads/item_image/` unchanged. When `business_id` is provided, the `subtype` param (`'services'` or `'products'`) selects the subfolder. If `subtype` is absent, defaults to `'products'`.

### Upload Endpoint Changes

**Endpoint:** `POST /api/v1/upload/image` (same URL, extended params)

**New params (multipart POST form fields):**
- `business_id` (optional) — UUID v4 of the business group. If provided, triggers per-business dir routing.
- `subtype` (optional) — `'services'` or `'products'`. Only relevant when `type=item_image` and `business_id` is set.

**Routing logic:**
```
if business_id provided AND matches UUID v4 regex:
    if type == 'item_image':
        folder = subtype ∈ ['services','products'] ? subtype : 'products'
    else:
        folder = type   (logo / banner / profile / gallery)
    dir = /storage/uploads/businesses/{business_id}/{folder}/
else:
    dir = /storage/uploads/{type}/    ← backward compat (item_image → item_image/)
```

**Security:**
- `business_id` validated against UUID v4 regex: `/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i`
- No DB lookup on `business_id` at upload time — the business was created seconds earlier in the same submit flow
- File type, size, MIME validation unchanged (2 MB limit, jpeg/png/webp/gif only)
- Filename: UUID v4 + extension (no change, no conflicts possible)
- Directories created on demand: `mkdir($dir, 0755, true)`
- No schema changes — filesystem only

**Known security trade-off:** Since `business_id` is not verified against the DB at upload time and the PATCH endpoint does not perform ownership verification (anonymous users have no account), a malicious actor who knows a victim's `groupId` (obtainable from public listing pages) could upload a file into that business's directory and then call PATCH to overwrite the victim's images. This is accepted as a low-risk gap for the current anonymous submission MVP. Mitigation (out of scope for this task): return a short-lived one-time `upload_token` from `POST /api/v1/businesses` and require it on both the upload and PATCH calls.

**Response:** unchanged — `{ ok: true, path: "/storage/uploads/businesses/{id}/{folder}/{uuid}.ext" }`

---

## Section 4: New API Endpoint — PATCH Business Images

**Endpoint:** `PATCH /api/v1/businesses/{id}/images`

**Auth:** Optional — same as `POST /api/v1/businesses`. Anonymous submissions allowed. No ownership check (see security trade-off note in Section 3).

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
    { "id": "<product-row-uuid>", "path": "/storage/uploads/businesses/{id}/products/uuid.jpg" }
  ],
  "service_images": [
    { "id": "<service-row-uuid>", "path": "/storage/uploads/businesses/{id}/services/uuid.jpg" }
  ]
}
```

All fields are optional. Only present fields are acted on.

**Why `id` not `sort_order` for products/services:** `sort_order` is not unique in `mci_business_products` / `mci_business_services`. Matching by `id` (UUID) is unambiguous. The `POST /api/v1/businesses` response must be extended to return the created product and service row IDs so the frontend can build the correct PATCH payload.

**Extended `POST /api/v1/businesses` response:**
```json
{
  "ok": true,
  "id": "<groupId>",
  "slug": "...",
  "branch_id": "<branchId>",
  "product_ids": ["<uuid>", "<uuid>"],
  "service_ids": ["<uuid>", "<uuid>"]
}
```
IDs are returned in insertion order (matching the `products[]` / `services[]` array order sent in the request).

**DB operations (all in a transaction):**
1. If any of `logo_path`, `profile_path`, `banner_path` present: `UPDATE mci_business_groups SET logo_path=?, profile_path=?, banner_path=? WHERE id=?` (only set non-null fields)
2. For each `gallery_paths` entry: `INSERT INTO mci_business_images (id, business_group_id, file_path, sort_order, uploaded_by_user_id, created_by_user_id) VALUES (...)` — column is `file_path` (not `image_path`). **Note:** the existing gallery INSERT in `api_business_create()` at `business_service.php:325` currently uses `image_path` — this is a latent bug that must also be fixed as part of this task (the DB schema column is `file_path`).
   - `sort_order` = array index
   - `uploaded_by_user_id` / `created_by_user_id` = nil UUID for anonymous, actual user ID for authenticated
3. For each `product_images` entry: `UPDATE mci_business_products SET image_path=? WHERE id=? AND business_group_id=?`
4. For each `service_images` entry: `UPDATE mci_business_services SET image_path=? WHERE id=? AND business_group_id=?`

**Path validation:** All paths must start with `/storage/uploads/` — reject anything that does not match to prevent path injection.

**Response:** `{ ok: true }`

---

## Files to Change

| File | Change |
|------|--------|
| `api/v1/lib/business_service.php` | Fix `city` null → `''`; fix latent `image_path` → `file_path` bug in gallery INSERT; add `api_business_patch_images()` function; extend `api_business_create()` response to include `product_ids` and `service_ids` |
| `api/v1/index.php` | Extend upload endpoint for `business_id`/`subtype` params; add `PATCH /businesses/{id}/images` route |
| `assets/js/subscriber-list-business.js` | Refactor image pickers to deferred slot map; add `beforeunload` warning; update submit flow to upload after create |

---

## Backward Compatibility

- Existing flat-dir uploads (`/storage/uploads/logo/`, etc.) continue to work — DB paths not changed.
- Upload endpoint without `business_id` falls back to flat structure.
- No DB schema changes required.
- No filesystem migration required.

---

## Non-Goals / Future Work

- Ownership verification on the PATCH endpoint (upload token mechanism)
- Re-uploading / replacing images after submission
- Image CDN / cloud storage
- Migrating existing flat-dir files to per-business dirs
- Image compression / resizing server-side
- Persisting pending image selections across page refreshes (IndexedDB)
