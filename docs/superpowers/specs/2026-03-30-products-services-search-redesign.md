# Design: Products & Services Search Pages Redesign

**Date:** 2026-03-30
**Branch:** to be created
**Status:** Ready for Implementation

---

## Problem Statement

The current `/products/` and `/services/` pages are informational landing pages only — they show category tiles and "how it works" copy but provide no way to search or browse individual products or services. Users who want to find a specific product (e.g. "wooden chair") or service (e.g. "AC repair") have no dedicated discovery surface. The data is already in the DB (`mci_business_products`, `mci_business_services`), stored per-business — it just isn't exposed for public search.

---

## Goals

- Redesign `/products/` and `/services/` as full search-and-browse pages.
- Let users search by keyword, filter by city, category, and price range, and sort results.
- Display results as **product/service cards** (not business cards) — each card shows the item details and the owning business.
- Clicking a card opens a **quick-view modal** with full item details and a "View Business Profile" CTA.
- Match the existing site visual style exactly: dark header, violet/fuchsia brand colours, `Bricolage Grotesque` display font, `Plus Jakarta Sans` body font, Bootstrap 5 utilities.

---

## Out of Scope

- Dedicated product/service detail pages (URL-based, SEO).
- User reviews or ratings on individual products/services.
- Cart, checkout, or any e-commerce transactional flow.
- Product/service editing after submission (manage via subscriber dashboard — separate feature).
- Infinite scroll (pagination is sufficient).

---

## Architecture

Two separate pages sharing one new CSS file and one new API endpoint:

| Layer | What changes |
|---|---|
| `products/index.php` | Full rewrite — server-side filter handling + HTML |
| `services/index.php` | Full rewrite — identical structure, services variant |
| `api/v1/index.php` | New `GET /api/v1/public/items` endpoint |
| `api/v1/lib/item_search_service.php` | New file — query logic for products and services |
| `assets/css/item-search.css` | New file — page-specific styles |

No DB schema changes needed.

---

## Section 1: New API Endpoint — `GET /api/v1/public/items`

### Purpose

Returns a paginated list of active products or services, each with the owning business's name, slug, city, logo, and category. Used by the PHP pages (server-side rendered on first load, and optionally re-queried via JS for filter changes without full page reload).

### Request

```
GET /api/v1/public/items
  ?type=products          (required) 'products' | 'services'
  &q=wooden chair         (optional) keyword — searches item name + description
  &city=Mumbai            (optional) filter by branch city (case-insensitive)
  &category=home-decor    (optional) parent category slug
  &price_min=500          (optional) numeric, inclusive
  &price_max=5000         (optional) numeric, inclusive
  &sort=relevance         (optional) 'relevance' | 'newest' | 'price_asc' | 'price_desc' (default: relevance)
  &page=1                 (optional, default 1)
  &per_page=12            (optional, default 12, max 48)
```

### Response

```json
{
  "ok": true,
  "total": 138,
  "page": 1,
  "per_page": 12,
  "items": [
    {
      "id": "<uuid>",
      "name": "Teak Wood Chair",
      "description": "Handcrafted solid teak wood...",
      "price_min": 3500.00,
      "price_max": 6000.00,
      "price_unit": "per piece",
      "image_path": "/storage/uploads/businesses/.../products/uuid.jpg",
      "business_group_id": "<uuid>",
      "business_name": "Sharma Furniture",
      "business_slug": "sharma-furniture",
      "business_logo": "/storage/uploads/businesses/.../logo/uuid.jpg",
      "business_category": "Home & Decor",
      "city": "Mumbai"
    }
  ]
}
```

### Query Logic (in `item_search_service.php`)

Use a subquery to resolve the city from the primary branch (or the first active branch if no primary exists), then join on the item table. This avoids `ONLY_FULL_GROUP_BY` violations:

```sql
SELECT
  p.id, p.name, p.description, p.price_min, p.price_max, p.price_unit, p.image_path,
  g.id AS business_group_id, g.name AS business_name, g.slug AS business_slug,
  g.logo_path AS business_logo,
  c.name AS business_category,
  COALESCE(
    (SELECT city FROM mci_business_branches
     WHERE business_group_id = g.id AND status = 'active' AND is_primary = 1 LIMIT 1),
    (SELECT city FROM mci_business_branches
     WHERE business_group_id = g.id AND status = 'active' LIMIT 1)
  ) AS city
FROM mci_business_products p                          -- or mci_business_services
INNER JOIN mci_business_groups g ON g.id = p.business_group_id AND g.status = 'live'
LEFT  JOIN mci_categories c      ON c.id = g.parent_category_id
WHERE p.is_active = 1
  [AND (p.name LIKE ? OR p.description LIKE ?)]       -- if q provided; '%term%' wildcard both sides
  [AND EXISTS (                                        -- if city provided
      SELECT 1 FROM mci_business_branches
      WHERE business_group_id = g.id
        AND status = 'active'
        AND LOWER(city) = LOWER(?)
  )]
  [AND c.slug = ?]                                     -- if category provided
  [AND (p.price_min <= ? OR p.price_min IS NULL)]      -- if price_max provided (budget upper bound)
  [AND (p.price_min >= ? OR p.price_min IS NULL)]      -- if price_min provided (budget lower bound)
ORDER BY
  CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END,          -- relevance: name matches rank above desc-only
  p.created_at DESC                                    -- relevance tiebreak / newest default
  -- replaced by: p.price_min ASC   for price_asc
  -- replaced by: p.price_min DESC  for price_desc
  -- replaced by: p.created_at DESC for newest (no name-rank weighting)
LIMIT ? OFFSET ?
```

**Price filter semantics:** Filter on `price_min` only (the item's starting price). If a user sets a budget of ₹5,000, items with `price_min <= 5000` are shown. Items with `price_min IS NULL` are always included (price not disclosed). This avoids incorrectly excluding items whose range spans the user's budget.

**Deduplication:** Products belong to `mci_business_groups`, not branches, so there is no fan-out join. The city subquery resolves to exactly one city per item. No `GROUP BY` needed.

**Relevance sort:** When `q` is provided, items whose `name` matches the keyword rank above those that only match in `description`. When no keyword is provided, relevance falls back to `created_at DESC`.

**ORDER BY is fully conditional on `sort`:**
- `sort=relevance` AND `q` non-empty: `ORDER BY CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END, p.created_at DESC` — note `q` (as `%term%`) must be bound **twice**: once for the WHERE name-match clause and once for this CASE expression.
- `sort=relevance` AND `q` empty: `ORDER BY p.created_at DESC` — no CASE expression, no extra bind.
- `sort=newest`: `ORDER BY p.created_at DESC` — no CASE expression.
- `sort=price_asc`: `ORDER BY p.price_min ASC` — items with NULL price_min sort last (`ISNULL(p.price_min), p.price_min ASC`).
- `sort=price_desc`: `ORDER BY p.price_min DESC NULLS LAST` (MySQL: `ORDER BY ISNULL(p.price_min), p.price_min DESC`).

**Function signature:**

```php
// api/v1/lib/item_search_service.php
function api_items_search(PDO $pdo, array $params): array
// Returns ['ok'=>true, 'total'=>int, 'page'=>int, 'per_page'=>int, 'items'=>array]
// or ['ok'=>false, 'error'=>string, 'status'=>int]
```

**Route in `index.php`:**

```php
// GET /api/v1/public/items
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'items') {
    require_once __DIR__ . '/lib/item_search_service.php';
    $params = [
        'type'      => $_GET['type']      ?? 'products',
        'q'         => $_GET['q']         ?? '',
        'city'      => $_GET['city']      ?? '',
        'category'  => $_GET['category']  ?? '',
        'price_min' => $_GET['price_min'] ?? '',
        'price_max' => $_GET['price_max'] ?? '',
        'sort'      => $_GET['sort']      ?? 'relevance',
        'page'      => (int)($_GET['page'] ?? 1),
        'per_page'  => min((int)($_GET['per_page'] ?? 12), 48),
    ];
    $pdo = api_db();
    $res = api_items_search($pdo, $params);
    if (!$res['ok']) api_error($res['error'], $res['status'] ?? 500);
    api_json($res);
}
```

---

## Section 2: Page Structure — `products/index.php` and `services/index.php`

Both pages are **server-side rendered** on first load using the same API query logic (called directly via `api_items_search()`, not via HTTP). Filter changes submit a GET form, which reloads the page — same pattern as `business-listing/index.php`. No full JS SPA.

**Required includes** (same pattern as `business-listing/index.php`):
```php
require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/item_search_service.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';
```

**`$extraHead`** should load the new CSS and JS files:
```php
$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/item-search.css" />
<script src="/assets/js/item-search.js" defer></script>
HTML;
```

### URL / Query Params

```
/products/?q=wooden+chair&city=Mumbai&category=home-decor&price_min=500&price_max=5000&sort=relevance&page=2
/services/?q=AC+repair&city=Delhi&category=repairs&sort=newest&page=1
```

### Page Sections

#### 1. Hero

Matches the site hero gradient (`var(--mci-gradient-hero)` — deep slate to indigo to purple). Contains:
- Page `<h1>` ("Find Products Near You" / "Find Services Near You")
- Subtitle copy
- Search box: text input (`q`) + city input (`city`) + Search button
- The Search button submits the GET form

#### 2. Filter Bar (sticky below hero on scroll)

A horizontal row containing:
- **Category pill** — dropdown (`<select>` styled as a pill). Options are server-side rendered from a direct DB query (same query pattern used in the current `products/index.php` and `services/index.php`) that returns only categories which have at least one live business with active items of the relevant type. Does NOT call the `/api/v1/public/categories` endpoint (which returns all categories regardless of item type). Opens as a native select on mobile.
- **Price pill** — dropdown with preset ranges. Maps to `price_min`/`price_max` params as follows:

  | Label | `price_min` | `price_max` |
  |---|---|---|
  | Any | _(omit)_ | _(omit)_ |
  | Under ₹500 | _(omit)_ | `500` |
  | ₹500 – ₹2,000 | `500` | `2000` |
  | ₹2,000 – ₹10,000 | `2000` | `10000` |
  | Above ₹10,000 | `10000` | _(omit)_ |
- **Sort pill** — dropdown: Relevance / Newest / Price: Low to High / Price: High to Low.
- **Active filter chips** — for each active filter (city, category, price), show a dismissible chip. Clicking ✕ removes that param and resubmits.
- **Result count** — e.g. "138 results" (right-aligned).

On mobile: pills collapse into a single "Filters" toggle button that opens a bottom-sheet or off-canvas panel (reuse the same `mciFilterToggle` / `mciFiltersPanel` pattern from `business-listing/index.php`).

#### 3. Results Grid

- 4 columns desktop, 3 tablet, 2 mobile.
- Sorted/paginated per filter params.
- Each card: product/service image (or placeholder icon), category badge, item name, description snippet (2 lines), price range, business logo + name + city.
- Card hover: `translateY(-4px)` + violet shadow (matches `.mci-listing-card:hover`).
- Empty state: "No products found matching your search. Try different keywords or remove some filters."

#### 4. Pagination

Standard Bootstrap pagination. Same GET form params carried through page links.

#### 5. Quick-View Modal

Triggered by clicking a card. Implemented in JS — no page reload.

**Modal content:**
- Product/service image (full width, or placeholder emoji/icon if none)
- Item name + price range + price unit
- Full description
- Business card panel: logo, name, city + category, "View profile →" link (goes to `/business/{slug}/`)
- "View Business Profile" CTA button (primary, gradient) → `/business/{slug}/`
- "Contact" button (outline) → `/business/{slug}/#contact` (or triggers contact modal if one exists)

**Modal data source:** All data needed for the modal is embedded in `data-*` attributes on the card element. No separate API call needed for the modal open.

---

## Section 3: New CSS File — `assets/css/item-search.css`

New file. Loaded only on `/products/` and `/services/` pages. All CSS custom properties referenced below are defined in `assets/css/theme.css` and can be used as-is:

| Property | Value |
|---|---|
| `--mci-gradient-hero` | `linear-gradient(135deg, #0a0f1e 0%, #1e1b4b 50%, #2e1065 100%)` |
| `--mci-gradient-cta` | `linear-gradient(90deg, #7c3aed 0%, #d946ef 100%)` |
| `--mci-color-primary` | `#7c3aed` |
| `--mci-color-primary-soft` | `#f5f3ff` |
| `--mci-border` | `#e2e8f0` |
| `--mci-shadow-card-hover` | `0 20px 40px -8px rgba(124,58,237,.15)` |
| `--mci-surface-2` | `#f8fafc` |
| `--mci-muted` | `#64748b` |

Contains:

- `.mci-items-hero` — hero section: `background: var(--mci-gradient-hero)`, padding, blob pseudo-elements (matching `home-hero` blobs).
- `.mci-items-search-box` — white pill search bar with city divider and gradient search button.
- `.mci-items-filter-bar` — sticky filter row, white background, border-bottom.
- `.mci-items-filter-pill` — pill-shaped filter trigger. Active state: `background: var(--mci-color-primary-soft); border-color: var(--mci-color-primary)`.
- `.mci-items-active-tag` — dismissible chip, gradient background (violet→fuchsia), white text.
- `.mci-item-card` — product/service card. Border `1.5px solid var(--mci-border)`, radius `14px`. Hover: `translateY(-4px)` + `box-shadow: var(--mci-shadow-card-hover)` + `border-color: rgba(124,58,237,0.2)`.
- `.mci-item-card__img` — 140px height image area.
- `.mci-item-card__cat-badge` — top-left badge on image, dark backdrop blur.
- `.mci-item-card__price` — `color: #059669` (green).
- `.mci-item-card__biz-strip` — bottom strip: logo + business name + city.
- `.mci-item-modal` — modal overlay, backdrop blur.
- `.mci-item-modal__box` — modal card, radius `18px`, max-width `520px`.
- `.mci-item-modal__cta` — primary button, `background: var(--mci-gradient-cta)`.

---

## Section 4: `item_search_service.php` — Input Validation

- `type`: must be `'products'` or `'services'`; else return `['ok'=>false,'error'=>'invalid_type','status'=>400]`.
- `q`: strip tags, max 120 chars. Empty string = no keyword filter.
- `city`: strip tags, max 80 chars.
- `category`: alphanumeric + hyphens only, max 80 chars.
- `price_min` / `price_max`: cast to float, min 0. If `price_min > price_max` and both set, swap them.
- `sort`: whitelist `['relevance','newest','price_asc','price_desc']`; default to `'relevance'`.
- `page`: min 1, max 999.
- `per_page`: min 1, max 48.

---

## Section 5: JS Behaviour

A single `assets/js/item-search.js` file handles:

1. **Modal open/close** — on card click, read `data-*` attrs, populate modal, show it. Close on overlay click or ✕ button.
2. **Active tag dismiss** — clicking ✕ on an active filter chip removes the corresponding query param from the URL and submits the form (or navigates).
3. **Filter pills** — the Category/Price/Sort pills open their native `<select>` on click and auto-submit the form on `change`.
4. **No AJAX fetching** — all filtering is done via GET form submit / page reload. This keeps the implementation simple and server-rendered, consistent with `business-listing/`.

---

## Files to Create / Modify

| File | Action | Purpose |
|---|---|---|
| `products/index.php` | Rewrite | Full search page — products |
| `services/index.php` | Rewrite | Full search page — services |
| `api/v1/lib/item_search_service.php` | Create | `api_items_search()` query function |
| `api/v1/index.php` | Modify | Add `GET /api/v1/public/items` route |
| `assets/css/item-search.css` | Create | Page-specific styles |
| `assets/js/item-search.js` | Create | Modal + filter dismiss JS |

---

## Backward Compatibility

- Existing category sub-pages (`/products/{category}/`) are not changed by this task.
- The existing `business-listing/` search is unaffected.
- No DB changes.

---

## Non-Goals / Future Work

- Product/service detail pages with their own URLs and SEO metadata.
- Saved/favourited items.
- Geolocation-based "near me" filtering.
- Real-time AJAX search-as-you-type.
- Image lightbox on the quick-view modal.
