# Products & Services Search Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign `/products/` and `/services/` as full search-and-browse pages where users can find products/services offered by local businesses, with keyword search, city, category and price filters, and a quick-view modal.

**Architecture:** New `api_items_search()` function in its own service file handles DB queries for both product and service types. Both pages are server-side rendered PHP using GET form submission for filters (same pattern as `business-listing/index.php`). A shared CSS file and small JS file handle card styles and the quick-view modal.

**Tech Stack:** PHP 8, MySQL/PDO, Bootstrap 5.3, jQuery 3.7, Bootstrap Icons 1.11, vanilla JS (no framework).

**Spec:** `docs/superpowers/specs/2026-03-30-products-services-search-redesign.md`

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `api/v1/lib/item_search_service.php` | **Create** | `api_items_search()` — validated query, pagination, both types |
| `api/v1/index.php` | **Modify** | Add `GET /api/v1/public/items` route after the `/public/cities` block |
| `assets/css/item-search.css` | **Create** | Hero, filter bar, cards, modal — site-matched styles |
| `assets/js/item-search.js` | **Create** | Modal open/close, active-tag dismiss, filter pill auto-submit |
| `products/index.php` | **Rewrite** | Products search page |
| `services/index.php` | **Rewrite** | Services search page (same structure, `type=services`) |

---

## Task 1: `item_search_service.php` — Search Function

**Files:**
- Create: `api/v1/lib/item_search_service.php`

- [ ] **Step 1.1: Create the file with the complete `api_items_search()` function**

```php
<?php

declare(strict_types=1);

/**
 * Search products or services with pagination and filters.
 *
 * $params keys:
 *   type      string  'products' | 'services'   (required)
 *   q         string  keyword (name + desc)      (optional)
 *   city      string  branch city, case-insensitive (optional)
 *   category  string  parent category slug       (optional)
 *   price_min float   starting-price lower bound (optional)
 *   price_max float   starting-price upper bound (optional)
 *   sort      string  relevance|newest|price_asc|price_desc (default: relevance)
 *   page      int     1-based page number        (default: 1)
 *   per_page  int     results per page, max 48   (default: 12)
 *
 * Returns:
 *   ['ok'=>true, 'total'=>int, 'page'=>int, 'per_page'=>int, 'items'=>array]
 *   ['ok'=>false, 'error'=>string, 'status'=>int]
 */
function api_items_search(PDO $pdo, array $params): array
{
    // ── Validate & sanitise inputs ────────────────────────────────────────────
    $type = trim((string)($params['type'] ?? ''));
    if (!in_array($type, ['products', 'services'], true)) {
        return ['ok' => false, 'error' => 'invalid_type', 'status' => 400];
    }
    $table = $type === 'products' ? 'mci_business_products' : 'mci_business_services';

    $q        = substr(strip_tags(trim((string)($params['q']       ?? ''))), 0, 120);
    $city     = substr(strip_tags(trim((string)($params['city']    ?? ''))), 0, 80);
    $category = trim((string)($params['category'] ?? ''));
    if ($category !== '' && !preg_match('/^[a-z0-9-]+$/i', $category)) {
        $category = '';
    }
    $category = substr($category, 0, 80);

    $priceMin = $params['price_min'] !== '' ? max(0.0, (float)($params['price_min'] ?? 0)) : null;
    $priceMax = $params['price_max'] !== '' ? max(0.0, (float)($params['price_max'] ?? 0)) : null;
    if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
        [$priceMin, $priceMax] = [$priceMax, $priceMin];
    }

    $sortWhitelist = ['relevance', 'newest', 'price_asc', 'price_desc'];
    $sort = in_array($params['sort'] ?? '', $sortWhitelist, true) ? $params['sort'] : 'relevance';

    $page    = max(1, min(999, (int)($params['page']     ?? 1)));
    $perPage = max(1, min(48,  (int)($params['per_page'] ?? 12)));
    $offset  = ($page - 1) * $perPage;

    // ── Build WHERE clause ────────────────────────────────────────────────────
    $where  = ['p.is_active = 1', "g.status = 'live'"];
    $binds  = [];  // positional bind values for the main query

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
        $binds[]  = $like;
        $binds[]  = $like;
    }

    if ($city !== '') {
        $where[]  = 'EXISTS (SELECT 1 FROM mci_business_branches WHERE business_group_id = g.id AND status = \'active\' AND LOWER(city) = LOWER(?))';
        $binds[]  = $city;
    }

    if ($category !== '') {
        $where[]  = 'c.slug = ?';
        $binds[]  = $category;
    }

    if ($priceMax !== null) {
        $where[]  = '(p.price_min <= ? OR p.price_min IS NULL)';
        $binds[]  = $priceMax;
    }

    if ($priceMin !== null) {
        $where[]  = '(p.price_min >= ? OR p.price_min IS NULL)';
        $binds[]  = $priceMin;
    }

    $whereSql = implode(' AND ', $where);

    // ── Count total (for pagination) ──────────────────────────────────────────
    $countSql = "
        SELECT COUNT(*) AS total
        FROM {$table} p
        INNER JOIN mci_business_groups g ON g.id = p.business_group_id
        LEFT  JOIN mci_categories c      ON c.id = g.parent_category_id
        WHERE {$whereSql}
    ";
    try {
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($binds);
        $total = (int)($countStmt->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        error_log('api_items_search count error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    // ── Build ORDER BY ────────────────────────────────────────────────────────
    $orderBinds = [];
    switch ($sort) {
        case 'price_asc':
            $orderSql = 'ORDER BY ISNULL(p.price_min), p.price_min ASC';
            break;
        case 'price_desc':
            $orderSql = 'ORDER BY ISNULL(p.price_min), p.price_min DESC';
            break;
        case 'newest':
            $orderSql = 'ORDER BY p.created_at DESC';
            break;
        default: // relevance
            if ($q !== '') {
                $orderSql    = 'ORDER BY CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END, p.created_at DESC';
                $orderBinds[] = '%' . $q . '%';
            } else {
                $orderSql = 'ORDER BY p.created_at DESC';
            }
            break;
    }

    // ── Main SELECT ───────────────────────────────────────────────────────────
    $selectSql = "
        SELECT
            p.id, p.name, p.description,
            p.price_min, p.price_max, p.price_unit,
            p.image_path,
            g.id   AS business_group_id,
            g.name AS business_name,
            g.slug AS business_slug,
            g.logo_path AS business_logo,
            c.name AS business_category,
            COALESCE(
                (SELECT city FROM mci_business_branches
                 WHERE business_group_id = g.id AND status = 'active' AND is_primary = 1 LIMIT 1),
                (SELECT city FROM mci_business_branches
                 WHERE business_group_id = g.id AND status = 'active' LIMIT 1)
            ) AS city
        FROM {$table} p
        INNER JOIN mci_business_groups g ON g.id = p.business_group_id
        LEFT  JOIN mci_categories c      ON c.id = g.parent_category_id
        WHERE {$whereSql}
        {$orderSql}
        LIMIT ? OFFSET ?
    ";

    $mainBinds = array_merge($binds, $orderBinds, [$perPage, $offset]);

    try {
        $stmt = $pdo->prepare($selectSql);
        $stmt->execute($mainBinds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('api_items_search select error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    // ── Normalise output ──────────────────────────────────────────────────────
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id'                => (string)$row['id'],
            'name'              => (string)$row['name'],
            'description'       => (string)($row['description'] ?? ''),
            'price_min'         => $row['price_min'] !== null ? (float)$row['price_min'] : null,
            'price_max'         => $row['price_max'] !== null ? (float)$row['price_max'] : null,
            'price_unit'        => (string)($row['price_unit'] ?? ''),
            'image_path'        => (string)($row['image_path'] ?? ''),
            'business_group_id' => (string)$row['business_group_id'],
            'business_name'     => (string)$row['business_name'],
            'business_slug'     => (string)$row['business_slug'],
            'business_logo'     => (string)($row['business_logo'] ?? ''),
            'business_category' => (string)($row['business_category'] ?? ''),
            'city'              => (string)($row['city'] ?? ''),
        ];
    }

    return [
        'ok'       => true,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
        'items'    => $items,
    ];
}
```

- [ ] **Step 1.2: Manual smoke test via PHP CLI (optional but recommended)**

```bash
cd c:\Projects\apps\MyCityInfo\mycity
php -r "
  require 'api/v1/lib/db.php';
  require 'api/v1/lib/item_search_service.php';
  \$pdo = api_db();
  \$r = api_items_search(\$pdo, ['type'=>'products','q'=>'','city'=>'','category'=>'','price_min'=>'','price_max'=>'','sort'=>'relevance','page'=>1,'per_page'=>5]);
  print_r(\$r);
"
```

Expected: `['ok' => true, 'total' => <N>, 'items' => [...]]` — no PHP errors.

- [ ] **Step 1.3: Commit**

```bash
git add api/v1/lib/item_search_service.php
git commit -m "feat: add api_items_search() — paginated product/service search with keyword, city, category, price filters"
```

---

## Task 2: API Route — `GET /api/v1/public/items`

**Files:**
- Modify: `api/v1/index.php` — insert after the `/public/cities` block (around line 1085)

- [ ] **Step 2.1: Add the route**

Find the comment block that starts `// Public — city / area autocomplete` and ends with `api_json(...)` closing brace. Insert the following block **after** it:

```php
// =============================================================================
// Public — items search (products or services) — no auth
// GET /api/v1/public/items?type=products&q=...&city=...&category=...&page=1
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'items') {
    require_once __DIR__ . '/lib/item_search_service.php';
    $params = [
        'type'      => trim((string)($_GET['type']      ?? 'products')),
        'q'         => trim((string)($_GET['q']         ?? '')),
        'city'      => trim((string)($_GET['city']      ?? '')),
        'category'  => trim((string)($_GET['category']  ?? '')),
        'price_min' => trim((string)($_GET['price_min'] ?? '')),
        'price_max' => trim((string)($_GET['price_max'] ?? '')),
        'sort'      => trim((string)($_GET['sort']      ?? 'relevance')),
        'page'      => max(1, (int)($_GET['page']     ?? 1)),
        'per_page'  => min(48, max(1, (int)($_GET['per_page'] ?? 12))),
    ];
    $pdo = api_db();
    $res = api_items_search($pdo, $params);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json($res);
}
```

- [ ] **Step 2.2: Test the endpoint in a browser or curl**

```bash
curl "http://localhost/api/v1/public/items?type=products&per_page=2"
```

Expected: `{"ok":true,"total":<N>,"page":1,"per_page":2,"pages":<N>,"items":[...]}`

```bash
curl "http://localhost/api/v1/public/items?type=invalid"
```

Expected: `{"ok":false,"error":"invalid_type"}` with HTTP 400.

- [ ] **Step 2.3: Commit**

```bash
git add api/v1/index.php
git commit -m "feat: add GET /api/v1/public/items route for product and service search"
```

---

## Task 3: CSS — `assets/css/item-search.css`

**Files:**
- Create: `assets/css/item-search.css`

All CSS custom properties (`--mci-*`) are defined in `assets/css/theme.css` — use them as-is.

- [ ] **Step 3.1: Create the CSS file**

```css
/* ============================================================
   item-search.css — Products & Services search pages
   Depends on: theme.css (--mci-* custom properties)
   ============================================================ */

/* ── Hero ───────────────────────────────────────────────────── */
.mci-items-hero {
  position: relative;
  overflow: hidden;
  background: var(--mci-gradient-hero);
  padding: 3rem 1.5rem 2.5rem;
  text-align: center;
}
.mci-items-hero::before {
  content: '';
  position: absolute;
  width: 320px; height: 320px;
  border-radius: 50%;
  background: rgba(124, 58, 237, 0.28);
  filter: blur(80px);
  top: -80px; right: -40px;
  pointer-events: none;
}
.mci-items-hero::after {
  content: '';
  position: absolute;
  width: 240px; height: 240px;
  border-radius: 50%;
  background: rgba(217, 70, 239, 0.22);
  filter: blur(60px);
  bottom: -60px; left: -30px;
  pointer-events: none;
}
.mci-items-hero h1 {
  font-size: clamp(1.5rem, 4vw, 2.2rem);
  font-weight: 800;
  color: #fff;
  margin-bottom: 0.4rem;
  position: relative;
  z-index: 1;
}
.mci-items-hero .mci-items-hero__sub {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  position: relative;
  z-index: 1;
}

/* ── Search box ─────────────────────────────────────────────── */
.mci-items-search-wrap {
  max-width: 660px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}
.mci-items-search-box {
  background: #fff;
  border-radius: 12px;
  padding: 6px 6px 6px 16px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.22);
}
.mci-items-search-box input[type="text"] {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.9rem;
  color: #212529;
  background: transparent;
  min-width: 0;
}
.mci-items-search-box .mci-items-search-divider {
  width: 1px;
  height: 26px;
  background: var(--mci-border);
  flex-shrink: 0;
}
.mci-items-search-box .mci-items-city-wrap {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  color: var(--mci-muted);
  padding: 0 8px;
  white-space: nowrap;
}
.mci-items-search-box .mci-items-city-wrap input[type="text"] {
  font-size: 0.82rem;
  width: 110px;
  color: #334155;
  font-weight: 500;
}
.mci-items-search-box button[type="submit"] {
  background: var(--mci-gradient-cta);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 22px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

/* ── Filter bar ─────────────────────────────────────────────── */
.mci-items-filter-bar {
  background: #fff;
  border-bottom: 1px solid var(--mci-border);
  padding: 0.6rem 0;
  position: sticky;
  top: 3.75rem;   /* below sticky site-header (~60px) */
  z-index: 10;
}
.mci-items-filter-bar .container-fluid,
.mci-items-filter-bar .container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.mci-items-filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--mci-surface-2);
  border: 1.5px solid var(--mci-border);
  border-radius: 9999px;
  padding: 5px 14px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  color: #495057;
  white-space: nowrap;
  transition: border-color 0.15s, color 0.15s;
  /* hide native select arrow */
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}
.mci-items-filter-pill:hover,
.mci-items-filter-pill:focus {
  border-color: var(--mci-color-primary);
  color: var(--mci-color-primary);
  outline: none;
}
.mci-items-filter-pill.active {
  background: var(--mci-color-primary-soft);
  border-color: var(--mci-color-primary);
  color: #6d28d9;
}
.mci-items-active-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--mci-gradient-cta);
  color: #fff;
  border-radius: 9999px;
  padding: 5px 11px;
  font-size: 0.75rem;
  font-weight: 600;
}
.mci-items-active-tag__x {
  cursor: pointer;
  opacity: 0.8;
  font-size: 0.7rem;
  line-height: 1;
  border: none;
  background: transparent;
  color: inherit;
  padding: 0;
}
.mci-items-active-tag__x:hover { opacity: 1; }
.mci-items-result-count {
  margin-left: auto;
  font-size: 0.78rem;
  color: var(--mci-muted);
  white-space: nowrap;
}

/* ── Item cards grid ────────────────────────────────────────── */
.mci-item-card {
  background: #fff;
  border: 1.5px solid var(--mci-border);
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
  text-decoration: none;
  display: block;
  color: inherit;
}
.mci-item-card:hover {
  box-shadow: var(--mci-shadow-card-hover);
  transform: translateY(-4px);
  border-color: rgba(124, 58, 237, 0.2);
}
.mci-item-card__img {
  height: 140px;
  background: var(--mci-surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.2rem;
  position: relative;
  overflow: hidden;
}
.mci-item-card__img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.mci-item-card__cat-badge {
  position: absolute;
  top: 8px; left: 8px;
  background: rgba(10, 15, 30, 0.58);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 600;
  border-radius: 5px;
  padding: 2px 7px;
  backdrop-filter: blur(4px);
  letter-spacing: 0.2px;
}
.mci-item-card__body {
  padding: 12px 14px 14px;
}
.mci-item-card__name {
  font-size: 0.82rem;
  font-weight: 700;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #1e293b;
}
.mci-item-card__desc {
  font-size: 0.72rem;
  color: var(--mci-muted);
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
}
.mci-item-card__price {
  font-size: 0.82rem;
  font-weight: 700;
  color: #059669;
  margin-bottom: 10px;
}
.mci-item-card__biz-strip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding-top: 8px;
  border-top: 1px solid var(--mci-border);
}
.mci-item-card__biz-logo {
  width: 22px; height: 22px;
  border-radius: 5px;
  background: var(--mci-surface-2);
  border: 1px solid var(--mci-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  flex-shrink: 0;
  overflow: hidden;
}
.mci-item-card__biz-logo img {
  width: 100%; height: 100%; object-fit: cover;
}
.mci-item-card__biz-name {
  font-size: 0.7rem;
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}
.mci-item-card__city {
  font-size: 0.65rem;
  color: var(--mci-muted);
  white-space: nowrap;
  margin-left: auto;
}

/* ── Quick-view modal ───────────────────────────────────────── */
.mci-item-modal {
  position: fixed;
  inset: 0;
  background: rgba(10, 15, 30, 0.62);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1055;
  padding: 16px;
  backdrop-filter: blur(3px);
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s, visibility 0.2s;
}
.mci-item-modal.is-open {
  opacity: 1;
  visibility: visible;
}
.mci-item-modal__box {
  background: #fff;
  border-radius: 18px;
  max-width: 520px;
  width: 100%;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(0, 0, 0, 0.32);
  transform: translateY(16px);
  transition: transform 0.2s;
  max-height: 90vh;
  overflow-y: auto;
}
.mci-item-modal.is-open .mci-item-modal__box {
  transform: translateY(0);
}
.mci-item-modal__img {
  height: 200px;
  background: var(--mci-surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3.5rem;
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
}
.mci-item-modal__img img {
  width: 100%; height: 100%; object-fit: cover;
}
.mci-item-modal__close {
  position: absolute;
  top: 12px; right: 12px;
  background: rgba(10, 15, 30, 0.42);
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 32px; height: 32px;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  line-height: 1;
}
.mci-item-modal__body {
  padding: 20px 22px 24px;
}
.mci-item-modal__name {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 3px;
}
.mci-item-modal__price {
  font-size: 1.3rem;
  font-weight: 800;
  color: #059669;
}
.mci-item-modal__unit {
  font-size: 0.78rem;
  color: var(--mci-muted);
  margin-left: 5px;
}
.mci-item-modal__desc {
  font-size: 0.85rem;
  color: #475569;
  line-height: 1.65;
  margin: 12px 0 16px;
}
.mci-item-modal__biz {
  background: var(--mci-surface-2);
  border: 1.5px solid var(--mci-border);
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.mci-item-modal__biz-logo {
  width: 44px; height: 44px;
  border-radius: 9px;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
  overflow: hidden;
}
.mci-item-modal__biz-logo img {
  width: 100%; height: 100%; object-fit: cover;
}
.mci-item-modal__biz-name {
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
  display: block;
  margin-bottom: 2px;
}
.mci-item-modal__biz-meta {
  font-size: 0.75rem;
  color: var(--mci-muted);
}
.mci-item-modal__biz-link {
  margin-left: auto;
  font-size: 0.75rem;
  color: var(--mci-color-primary);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}
.mci-item-modal__biz-link:hover { text-decoration: underline; }
.mci-item-modal__actions {
  display: flex;
  gap: 10px;
}
.mci-item-modal__cta {
  background: var(--mci-gradient-cta);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 11px 18px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  flex: 1;
  box-shadow: 0 4px 14px rgba(124, 58, 237, 0.32);
  text-align: center;
  text-decoration: none;
  display: inline-block;
}
.mci-item-modal__cta:hover { color: #fff; opacity: 0.92; }
.mci-item-modal__contact {
  background: #fff;
  color: var(--mci-color-primary);
  border: 1.5px solid var(--mci-color-primary);
  border-radius: 10px;
  padding: 11px 18px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
}
.mci-item-modal__contact:hover { background: var(--mci-color-primary-soft); color: var(--mci-color-primary); }

/* ── Empty state ────────────────────────────────────────────── */
.mci-items-empty {
  text-align: center;
  padding: 4rem 1rem;
  color: var(--mci-muted);
}
.mci-items-empty__icon { font-size: 3rem; margin-bottom: 1rem; }
.mci-items-empty__title { font-size: 1rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem; }

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 991.98px) {
  .mci-items-filter-bar { top: 0; }
  /* mciFiltersPanel visibility on mobile is JS-controlled (item-search.js).
     When revealed, it wraps filter pills into a column layout. */
  #mciFiltersPanel { flex-wrap: wrap; width: 100%; padding-top: 0.5rem; }
}
@media (max-width: 575.98px) {
  .mci-items-search-box { flex-wrap: wrap; padding: 10px 12px; border-radius: 10px; }
  .mci-items-search-box .mci-items-search-divider { display: none; }
  .mci-items-search-box .mci-items-city-wrap { padding: 0; }
  .mci-items-search-box button[type="submit"] { width: 100%; justify-content: center; }
}
```

- [ ] **Step 3.2: Verify the file exists at the right path**

```bash
ls c:\Projects\apps\MyCityInfo\mycity\assets\css\item-search.css
```

- [ ] **Step 3.3: Commit**

```bash
git add assets/css/item-search.css
git commit -m "feat: add item-search.css for products/services search page styles"
```

---

## Task 4: JS — `assets/js/item-search.js`

**Files:**
- Create: `assets/js/item-search.js`

- [ ] **Step 4.1: Create the JS file**

```js
/**
 * item-search.js
 * Handles: quick-view modal, active-tag dismiss, filter-pill auto-submit.
 * Loaded with `defer` on /products/ and /services/ pages.
 */
(function () {
  'use strict';

  // ── Quick-view modal ──────────────────────────────────────────────────────
  var modal    = document.getElementById('mciItemModal');
  var modalImg = document.getElementById('mciModalImg');
  var modalImgEl = document.getElementById('mciModalImgEl');
  var modalName  = document.getElementById('mciModalName');
  var modalPrice = document.getElementById('mciModalPrice');
  var modalUnit  = document.getElementById('mciModalUnit');
  var modalDesc  = document.getElementById('mciModalDesc');
  var modalBizLogo = document.getElementById('mciModalBizLogo');
  var modalBizLogoImg = document.getElementById('mciModalBizLogoImg');
  var modalBizName = document.getElementById('mciModalBizName');
  var modalBizMeta = document.getElementById('mciModalBizMeta');
  var modalBizLink = document.getElementById('mciModalBizLink');
  var modalCta     = document.getElementById('mciModalCta');
  var modalContact = document.getElementById('mciModalContact');

  function openModal(card) {
    if (!modal) return;
    var d = card.dataset;

    // Image
    var imgPath = d.image || '';
    if (imgPath && modalImgEl) {
      modalImgEl.src = imgPath;
      modalImgEl.style.display = '';
      if (modalImg) modalImg.style.fontSize = '0';
    } else {
      if (modalImgEl) modalImgEl.style.display = 'none';
      if (modalImg)   modalImg.style.fontSize = '';
    }

    // Text fields
    if (modalName)  modalName.textContent  = d.name  || '';
    if (modalPrice) modalPrice.textContent = d.price || '';
    if (modalUnit)  modalUnit.textContent  = d.unit  ? '/ ' + d.unit : '';
    if (modalDesc)  modalDesc.textContent  = d.desc  || '';

    // Business panel
    var bizSlug = d.bizSlug || '';
    var bizUrl  = bizSlug ? '/business/' + bizSlug + '/' : '#';
    var bizLogoPath = d.bizLogo || '';

    if (modalBizLogoImg) {
      if (bizLogoPath) {
        modalBizLogoImg.src = bizLogoPath;
        modalBizLogoImg.style.display = '';
        if (modalBizLogo) modalBizLogo.style.fontSize = '0';
      } else {
        modalBizLogoImg.style.display = 'none';
        if (modalBizLogo) modalBizLogo.style.fontSize = '';
      }
    }

    if (modalBizName) modalBizName.textContent = d.bizName || '';
    if (modalBizMeta) {
      var meta = [];
      if (d.city)        meta.push('📍 ' + d.city);
      if (d.bizCategory) meta.push(d.bizCategory);
      modalBizMeta.textContent = meta.join(' · ');
    }
    if (modalBizLink) { modalBizLink.href = bizUrl; }
    if (modalCta)     { modalCta.href = bizUrl; }
    if (modalContact) { modalContact.href = bizUrl + '#contact'; }

    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // Open modal on card click
  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-item-card]');
    if (card) { e.preventDefault(); openModal(card); return; }

    // Close on overlay click
    if (e.target === modal) { closeModal(); return; }

    // Close button
    if (e.target.closest('[data-modal-close]')) { closeModal(); return; }

    // Active tag dismiss
    var dismissBtn = e.target.closest('[data-dismiss-param]');
    if (dismissBtn) {
      var param = dismissBtn.dataset.dismissParam;
      var form  = document.getElementById('mciItemsFilterForm');
      if (form && param) {
        // Clear the param's hidden/select/text input within the form
        var inputs = form.querySelectorAll('[name="' + param + '"], [name="price_min"], [name="price_max"]');
        if (param === 'price') {
          // Remove both price params
          form.querySelectorAll('[name="price_min"], [name="price_max"]').forEach(function (el) { el.value = ''; });
        } else {
          form.querySelectorAll('[name="' + param + '"]').forEach(function (el) { el.value = ''; });
        }
        // Reset page to 1
        var pageInput = form.querySelector('[name="page"]');
        if (pageInput) pageInput.value = '1';
        form.submit();
      }
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  // ── Filter pill auto-submit on change ─────────────────────────────────────
  var form = document.getElementById('mciItemsFilterForm');
  if (form) {
    form.querySelectorAll('.mci-items-filter-pill').forEach(function (el) {
      el.addEventListener('change', function () {
        // Reset to page 1 when filter changes
        var pageInput = form.querySelector('[name="page"]');
        if (pageInput) pageInput.value = '1';
        form.submit();
      });
    });
  }

  // ── Mobile filter toggle (mciFilterToggle / mciFiltersPanel) ──────────────
  var filterToggle = document.getElementById('mciFilterToggle');
  var filtersPanel = document.getElementById('mciFiltersPanel');
  if (filterToggle && filtersPanel) {
    // On large screens the panel is always visible; on small it starts hidden
    function syncPanelVisibility() {
      if (window.innerWidth >= 992) {
        filtersPanel.style.display = '';
      }
    }
    syncPanelVisibility();
    window.addEventListener('resize', syncPanelVisibility);

    filterToggle.addEventListener('click', function () {
      var expanded = filterToggle.getAttribute('aria-expanded') === 'true';
      filterToggle.setAttribute('aria-expanded', String(!expanded));
      filtersPanel.style.display = expanded ? 'none' : '';
    });

    // Hide panel by default on mobile
    if (window.innerWidth < 992) {
      filtersPanel.style.display = 'none';
    }
  }

}());
```

- [ ] **Step 4.2: Commit**

```bash
git add assets/js/item-search.js
git commit -m "feat: add item-search.js — quick-view modal, active-tag dismiss, filter pill auto-submit"
```

---

## Task 5: Products Page — `products/index.php`

**Files:**
- Rewrite: `products/index.php`

This is a full rewrite of the existing file. The page reads GET params, calls `api_items_search()` directly, and renders the results.

- [ ] **Step 5.1: Rewrite `products/index.php`**

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/item_search_service.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';

$pageTitle       = 'Products - My City Info';
$activePage      = 'products';
$metaDescription = 'Search products offered by local businesses on My City Info. Filter by city, category and price to find what you need.';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/item-search.css" />
<script src="/assets/js/item-search.js" defer></script>
HTML;

// ── Read filter params ────────────────────────────────────────────────────────
$q        = trim((string)($_GET['q']        ?? ''));
$city     = trim((string)($_GET['city']     ?? ''));
if ($city === '') {
    $city = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
$category = trim((string)($_GET['category'] ?? ''));
$priceMin = trim((string)($_GET['price_min'] ?? ''));
$priceMax = trim((string)($_GET['price_max'] ?? ''));
$sort     = trim((string)($_GET['sort']     ?? 'relevance'));
$curPage  = max(1, (int)($_GET['page']      ?? 1));

// Derive active price preset label for the chip
$priceLabel = '';
if ($priceMin === '' && $priceMax === '500')    { $priceLabel = 'Under ₹500'; }
elseif ($priceMin === '500' && $priceMax === '2000')  { $priceLabel = '₹500 – ₹2,000'; }
elseif ($priceMin === '2000' && $priceMax === '10000') { $priceLabel = '₹2,000 – ₹10,000'; }
elseif ($priceMin === '10000' && $priceMax === '')   { $priceLabel = 'Above ₹10,000'; }

// ── Load categories for the filter dropdown (products-only) ───────────────────
$filterCategories = [];
try {
    $pdo      = api_db();
    $catStmt  = $pdo->query("
        SELECT DISTINCT c.name, c.slug
        FROM mci_categories c
        INNER JOIN mci_business_groups g  ON g.parent_category_id = c.id AND g.status = 'live'
        INNER JOIN mci_business_products p ON p.business_group_id = g.id AND p.is_active = 1
        WHERE c.parent_id IS NULL
        ORDER BY c.name
    ");
    $filterCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $ignored) {}

// ── Run search ────────────────────────────────────────────────────────────────
$searchResult = ['ok' => false, 'total' => 0, 'pages' => 1, 'items' => []];
try {
    $pdo          = isset($pdo) ? $pdo : api_db();
    $searchResult = api_items_search($pdo, [
        'type'      => 'products',
        'q'         => $q,
        'city'      => $city,
        'category'  => $category,
        'price_min' => $priceMin,
        'price_max' => $priceMax,
        'sort'      => $sort,
        'page'      => $curPage,
        'per_page'  => 12,
    ]);
} catch (Throwable $ignored) {}

$items      = $searchResult['items']  ?? [];
$total      = (int)($searchResult['total'] ?? 0);
$totalPages = (int)($searchResult['pages'] ?? 1);

// ── Pagination URL helper ─────────────────────────────────────────────────────
function mci_products_page_url(int $page, string $q, string $city, string $category, string $priceMin, string $priceMax, string $sort): string {
    $p = [];
    if ($q !== '')        $p['q']         = $q;
    if ($city !== '')     $p['city']       = $city;
    if ($category !== '') $p['category']   = $category;
    if ($priceMin !== '') $p['price_min']  = $priceMin;
    if ($priceMax !== '') $p['price_max']  = $priceMax;
    if ($sort !== 'relevance') $p['sort'] = $sort;
    if ($page > 1)        $p['page']       = $page;
    return '/products/?' . http_build_query($p);
}

ob_start();
?>

<!-- HERO -->
<div class="mci-items-hero">
  <h1>Find Products Near You</h1>
  <p class="mci-items-hero__sub">Discover products offered by local businesses across your city</p>
  <div class="mci-items-search-wrap">
    <form method="get" action="/products/" id="mciItemsSearchForm">
      <div class="mci-items-search-box">
        <i class="bi bi-search text-muted" aria-hidden="true"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search products…" aria-label="Search products" />
        <div class="mci-items-search-divider"></div>
        <div class="mci-items-city-wrap">
          <i class="bi bi-geo-alt-fill" style="color:var(--mci-color-primary);" aria-hidden="true"></i>
          <input type="text" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="City" aria-label="City" />
        </div>
        <button type="submit">Search</button>
      </div>
      <!-- Hidden filter state carried through the search box form -->
      <?php if ($category !== ''): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>" />
      <?php endif; ?>
      <?php if ($priceMin !== ''): ?>
        <input type="hidden" name="price_min" value="<?= htmlspecialchars($priceMin) ?>" />
      <?php endif; ?>
      <?php if ($priceMax !== ''): ?>
        <input type="hidden" name="price_max" value="<?= htmlspecialchars($priceMax) ?>" />
      <?php endif; ?>
      <?php if ($sort !== 'relevance'): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />
      <?php endif; ?>
      <input type="hidden" name="page" value="1" />
    </form>
  </div>
</div>

<!-- FILTER BAR -->
<div class="mci-items-filter-bar">
  <div class="container">
    <!-- Mobile toggle (hidden on desktop) -->
    <button class="btn btn-sm btn-outline-dark d-lg-none mb-2" type="button"
      id="mciFilterToggle" aria-expanded="false" aria-controls="mciFiltersPanel">
      <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filters
      <?php
        $activeFilterCount = ($city !== '' ? 1 : 0) + ($category !== '' ? 1 : 0) + ($priceLabel !== '' ? 1 : 0);
        if ($activeFilterCount > 0): ?>
        <span class="badge text-bg-dark ms-1"><?= $activeFilterCount ?></span>
      <?php endif; ?>
    </button>
    <div id="mciFiltersPanel">
    <form method="get" action="/products/" id="mciItemsFilterForm" style="display:contents;">

      <!-- Category pill -->
      <select name="category" class="mci-items-filter-pill <?= $category !== '' ? 'active' : '' ?>" aria-label="Filter by category">
        <option value="">🏷️ Category</option>
        <?php foreach ($filterCategories as $fc): ?>
          <option value="<?= htmlspecialchars($fc['slug']) ?>" <?= $fc['slug'] === $category ? 'selected' : '' ?>>
            <?= htmlspecialchars($fc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Price pill -->
      <?php
      $priceValue = '';
      if ($priceMin === '' && $priceMax === '500')          $priceValue = 'under500';
      elseif ($priceMin === '500' && $priceMax === '2000')  $priceValue = '500-2000';
      elseif ($priceMin === '2000' && $priceMax === '10000') $priceValue = '2000-10000';
      elseif ($priceMin === '10000' && $priceMax === '')    $priceValue = 'above10000';
      ?>
      <select name="_price_preset" class="mci-items-filter-pill <?= $priceValue !== '' ? 'active' : '' ?>" aria-label="Filter by price" id="mciPricePreset">
        <option value="">💰 Price</option>
        <option value="under500"    <?= $priceValue === 'under500'    ? 'selected' : '' ?>>Under ₹500</option>
        <option value="500-2000"    <?= $priceValue === '500-2000'    ? 'selected' : '' ?>>₹500 – ₹2,000</option>
        <option value="2000-10000"  <?= $priceValue === '2000-10000'  ? 'selected' : '' ?>>₹2,000 – ₹10,000</option>
        <option value="above10000"  <?= $priceValue === 'above10000'  ? 'selected' : '' ?>>Above ₹10,000</option>
      </select>
      <!-- Hidden price_min / price_max resolved by JS -->
      <input type="hidden" name="price_min" id="mciPriceMin" value="<?= htmlspecialchars($priceMin) ?>" />
      <input type="hidden" name="price_max" id="mciPriceMax" value="<?= htmlspecialchars($priceMax) ?>" />

      <!-- Sort pill -->
      <select name="sort" class="mci-items-filter-pill <?= $sort !== 'relevance' ? 'active' : '' ?>" aria-label="Sort results">
        <option value="relevance"  <?= $sort === 'relevance'  ? 'selected' : '' ?>>↕️ Relevance</option>
        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>🕐 Newest</option>
        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>💰 Price: Low–High</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💰 Price: High–Low</option>
      </select>

      <!-- Carry q and city through pill form -->
      <input type="hidden" name="q"    value="<?= htmlspecialchars($q) ?>" />
      <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>" />
      <input type="hidden" name="page" value="1" />

    </form>

    <!-- Active filter chips -->
    <?php if ($city !== ''): ?>
      <span class="mci-items-active-tag">
        📍 <?= htmlspecialchars($city) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="city" aria-label="Remove city filter">✕</button>
      </span>
    <?php endif; ?>
    <?php if ($category !== ''): ?>
      <?php $catName = ''; foreach ($filterCategories as $fc) { if ($fc['slug'] === $category) { $catName = $fc['name']; break; } } ?>
      <span class="mci-items-active-tag">
        <?= htmlspecialchars($catName ?: $category) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="category" aria-label="Remove category filter">✕</button>
      </span>
    <?php endif; ?>
    <?php if ($priceLabel !== ''): ?>
      <span class="mci-items-active-tag">
        <?= htmlspecialchars($priceLabel) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="price" aria-label="Remove price filter">✕</button>
      </span>
    <?php endif; ?>

    <span class="mci-items-result-count"><?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?></span>
    </div><!-- /#mciFiltersPanel -->
  </div>
</div>

<!-- RESULTS -->
<div class="container py-4">

  <?php if (empty($items)): ?>
    <div class="mci-items-empty">
      <div class="mci-items-empty__icon">🔍</div>
      <div class="mci-items-empty__title">No products found</div>
      <p class="small">Try different keywords or remove some filters.</p>
      <a href="/products/" class="btn btn-sm btn-dark mt-2">Clear all filters</a>
    </div>

  <?php else: ?>

    <!-- Results header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div class="fw-semibold">
        Products
        <?php if ($q !== ''): ?>
          <span class="fw-normal text-muted">— "<?= htmlspecialchars($q) ?>"</span>
        <?php endif; ?>
        <?php if ($city !== ''): ?>
          <span class="fw-normal text-muted"> in <?= htmlspecialchars($city) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Card grid -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
      <?php foreach ($items as $item):
        $bizUrl     = '/business/' . htmlspecialchars(urlencode($item['business_slug'])) . '/';
        $priceStr   = '';
        if ($item['price_min'] !== null && $item['price_max'] !== null) {
            $priceStr = '₹' . number_format((int)$item['price_min']) . ' – ₹' . number_format((int)$item['price_max']);
        } elseif ($item['price_min'] !== null) {
            $priceStr = 'From ₹' . number_format((int)$item['price_min']);
        } elseif ($item['price_max'] !== null) {
            $priceStr = 'Up to ₹' . number_format((int)$item['price_max']);
        }
      ?>
        <div class="col">
          <div class="mci-item-card h-100"
            data-item-card="1"
            data-name="<?= htmlspecialchars($item['name']) ?>"
            data-desc="<?= htmlspecialchars($item['description']) ?>"
            data-price="<?= htmlspecialchars($priceStr) ?>"
            data-unit="<?= htmlspecialchars($item['price_unit']) ?>"
            data-image="<?= htmlspecialchars($item['image_path']) ?>"
            data-biz-name="<?= htmlspecialchars($item['business_name']) ?>"
            data-biz-slug="<?= htmlspecialchars($item['business_slug']) ?>"
            data-biz-logo="<?= htmlspecialchars($item['business_logo']) ?>"
            data-biz-category="<?= htmlspecialchars($item['business_category']) ?>"
            data-city="<?= htmlspecialchars($item['city']) ?>"
          >
            <div class="mci-item-card__img">
              <?php if ($item['image_path'] !== ''): ?>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" />
              <?php else: ?>
                <i class="bi bi-box-seam" style="font-size:2rem; color:var(--mci-muted);" aria-hidden="true"></i>
              <?php endif; ?>
              <?php if ($item['business_category'] !== ''): ?>
                <span class="mci-item-card__cat-badge"><?= htmlspecialchars($item['business_category']) ?></span>
              <?php endif; ?>
            </div>
            <div class="mci-item-card__body">
              <div class="mci-item-card__name"><?= htmlspecialchars($item['name']) ?></div>
              <?php if ($item['description'] !== ''): ?>
                <div class="mci-item-card__desc"><?= htmlspecialchars($item['description']) ?></div>
              <?php endif; ?>
              <?php if ($priceStr !== ''): ?>
                <div class="mci-item-card__price"><?= $priceStr ?></div>
              <?php endif; ?>
              <div class="mci-item-card__biz-strip">
                <div class="mci-item-card__biz-logo">
                  <?php if ($item['business_logo'] !== ''): ?>
                    <img src="<?= htmlspecialchars($item['business_logo']) ?>" alt="" loading="lazy" />
                  <?php else: ?>
                    <i class="bi bi-shop" aria-hidden="true"></i>
                  <?php endif; ?>
                </div>
                <span class="mci-item-card__biz-name"><?= htmlspecialchars($item['business_name']) ?></span>
                <?php if ($item['city'] !== ''): ?>
                  <span class="mci-item-card__city">📍 <?= htmlspecialchars($item['city']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="d-flex justify-content-center mt-2 mb-4" aria-label="Product pages">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars(mci_products_page_url($curPage - 1, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
          </li>
          <?php for ($p = max(1, $curPage - 2); $p <= min($totalPages, $curPage + 2); $p++): ?>
            <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
              <a class="page-link" href="<?= htmlspecialchars(mci_products_page_url($p, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $curPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars(mci_products_page_url($curPage + 1, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- QUICK-VIEW MODAL -->
<div class="mci-item-modal" id="mciItemModal" role="dialog" aria-modal="true" aria-labelledby="mciModalName">
  <div class="mci-item-modal__box">
    <div class="mci-item-modal__img" id="mciModalImg">
      <img id="mciModalImgEl" src="" alt="" style="display:none;" />
      <button class="mci-item-modal__close" data-modal-close aria-label="Close">✕</button>
    </div>
    <div class="mci-item-modal__body">
      <div class="mci-item-modal__name" id="mciModalName"></div>
      <div>
        <span class="mci-item-modal__price" id="mciModalPrice"></span>
        <span class="mci-item-modal__unit" id="mciModalUnit"></span>
      </div>
      <p class="mci-item-modal__desc" id="mciModalDesc"></p>
      <div class="mci-item-modal__biz">
        <div class="mci-item-modal__biz-logo" id="mciModalBizLogo">
          <img id="mciModalBizLogoImg" src="" alt="" style="display:none;" />
          <i class="bi bi-shop" aria-hidden="true"></i>
        </div>
        <div>
          <span class="mci-item-modal__biz-name" id="mciModalBizName"></span>
          <span class="mci-item-modal__biz-meta" id="mciModalBizMeta"></span>
        </div>
        <a class="mci-item-modal__biz-link" id="mciModalBizLink" href="#">View profile →</a>
      </div>
      <div class="mci-item-modal__actions">
        <a class="mci-item-modal__cta" id="mciModalCta" href="#">View Business Profile</a>
        <a class="mci-item-modal__contact" id="mciModalContact" href="#">📞 Contact</a>
      </div>
    </div>
  </div>
</div>

<!-- Price preset → hidden inputs wiring (inline, minimal) -->
<script>
(function () {
  var preset = document.getElementById('mciPricePreset');
  var pMin   = document.getElementById('mciPriceMin');
  var pMax   = document.getElementById('mciPriceMax');
  if (!preset) return;
  var map = {
    'under500':   ['', '500'],
    '500-2000':   ['500', '2000'],
    '2000-10000': ['2000', '10000'],
    'above10000': ['10000', ''],
    '':           ['', '']
  };
  preset.addEventListener('change', function () {
    var vals = map[preset.value] || ['', ''];
    pMin.value = vals[0];
    pMax.value = vals[1];
    // form submit handled by item-search.js pill auto-submit
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
```

- [ ] **Step 5.2: Open `http://localhost/products/` in a browser**

Verify:
- Dark gradient hero with search bar renders correctly
- Filter bar appears below hero (category, price, sort pills)
- Product cards show with image, name, description, price, business name + city
- Clicking a card opens the quick-view modal with all fields populated
- Closing the modal via ✕ or overlay click works
- Submitting the search form with a keyword reloads with filtered results

- [ ] **Step 5.3: Commit**

```bash
git add products/index.php
git commit -m "feat: redesign products page — keyword search, city/category/price filters, card grid, quick-view modal"
```

---

## Task 6: Services Page — `services/index.php`

**Files:**
- Rewrite: `services/index.php`

Identical structure to `products/index.php` with these differences:
- `$pageTitle` → `'Services - My City Info'`
- `$activePage` → `'services'`
- `$metaDescription` → services copy
- Category query uses `mci_business_services` not `mci_business_products`
- `api_items_search()` called with `'type' => 'services'`
- H1 → "Find Services Near You"
- Subtitle → "Discover services offered by local businesses across your city"
- Placeholder → "Search services…"
- `action="/services/"` on both forms
- `mci_products_page_url` renamed to `mci_services_page_url` with `/services/` base
- Empty state text → "No services found matching your search."
- Card placeholder icon → `bi-stars` instead of `bi-box-seam`

- [ ] **Step 6.1: Rewrite `services/index.php`**

Copy the products page and apply the above differences. Key changes — change the following lines:

```php
// Line 1-5: meta
$pageTitle       = 'Services - My City Info';
$activePage      = 'services';
$metaDescription = 'Find local services on My City Info. Filter by city, category and price to connect with service providers near you.';

// Category query: change mci_business_products → mci_business_services
INNER JOIN mci_business_services p ON p.business_group_id = g.id AND p.is_active = 1

// api_items_search call:
'type' => 'services',

// Page URL helper:
function mci_services_page_url(...): string {
    ...
    return '/services/?' . http_build_query($p);
}

// Hero H1:
<h1>Find Services Near You</h1>

// Hero subtitle:
<p class="mci-items-hero__sub">Discover services offered by local businesses across your city</p>

// Search input placeholder:
placeholder="Search services…"

// Both form action attributes:
action="/services/"

// Pagination:
aria-label="Service pages"

// Results header:
Services

// Empty state title:
No services found

// Empty state clear link:
href="/services/"

// Card placeholder icon:
<i class="bi bi-stars" ...></i>
```

- [ ] **Step 6.2: Open `http://localhost/services/` in a browser**

Verify same checklist as Step 5.2 but for services.

- [ ] **Step 6.3: Commit**

```bash
git add services/index.php
git commit -m "feat: redesign services page — keyword search, city/category/price filters, card grid, quick-view modal"
```

---

## Final Verification Checklist

- [ ] `GET /api/v1/public/items?type=products` returns paginated items
- [ ] `GET /api/v1/public/items?type=invalid` returns 400
- [ ] Keyword search filters by name and description
- [ ] City filter uses `EXISTS` — no duplicate rows
- [ ] Category filter shows only categories with live items
- [ ] Price filter: "Under ₹500" returns only items with `price_min <= 500` or `price_min IS NULL`
- [ ] Price: Low–High sort — NULL price_min items appear last
- [ ] Relevance sort with keyword — name-match items rank above description-only
- [ ] `/products/` page loads without PHP errors
- [ ] `/services/` page loads without PHP errors
- [ ] Card quick-view modal opens, shows all fields, closes on ✕ and overlay
- [ ] Active filter chips appear; clicking ✕ removes that filter
- [ ] Pagination links carry all active filter params
- [ ] Mobile: page renders in 2 columns, search box stacks vertically
- [ ] Mobile: "Filters" toggle button visible on small screens; tapping it reveals filter pills
