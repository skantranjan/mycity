# UI Modernization — 9 Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement 9 UI/UX improvements identified in the site design review to elevate visual quality, accessibility, and user trust.

**Architecture:** Pure frontend changes — CSS edits and PHP template edits only. No backend logic, no new routes, no database changes. Each task is independently deployable.

**Tech Stack:** PHP templates, Bootstrap 5, custom CSS (theme.css, business.css, listings.css, auth-pages.css), Bootstrap Icons

---

## Pre-flight: Already Implemented (skip these)

These items from the original 11-item review are **already in the codebase**:
- Footer CSS classes (`.site-footer`, `.mci-footer__*`) — present in `theme.css` lines 617+
- Rating display on listing cards — present in `views/components/listing-card.php` lines 84–89, 108–114
- "Remember me" checkbox on login — present in `login/index.php` line 150

---

## File Map

| File | Tasks |
|------|-------|
| `assets/images/logo.png` | Task 1 — create by download |
| `views/partials/header.php` | Task 1 (logo src), Task 2 (skip link) |
| `views/partials/footer.php` | Task 1 (logo src), Task 6 (CTA button class) |
| `views/layout.php` | Task 2 (add `id` to `<main>`) |
| `assets/css/business.css` | Task 3 (taller hero) |
| `assets/css/theme.css` | Task 4 (breadcrumb CSS), Task 7 (btn-dark scope fix) |
| `assets/css/listings.css` | Task 5 (sticky filter) |
| `assets/css/auth-pages.css` | Task 9 (dark mode auth surface) |
| `business/index.php` | Task 4 (breadcrumb) |
| `business-listing/index.php` | Task 4 (breadcrumb), Task 5 (sticky filter class) |
| `business-category/detail.php` | Task 4 (breadcrumb) |
| `login/index.php` | Task 8 (social proof badge) |

---

## Task 1: Download Logo Locally

**Files:**
- Create: `assets/images/logo.png`
- Modify: `views/partials/header.php` — line 32 (img src)
- Modify: `views/partials/footer.php` — line 12 (img src)

**Context:** Both header and footer load the logo from an external WordPress URL (`mycityinfo.com/wp-content/uploads/2017/...`). Serving it locally eliminates an external dependency, improves reliability, and reduces latency.

- [ ] **Step 1: Create images directory and download logo**

```bash
cd c:/Projects/apps/MyCityInfo/mycity
mkdir -p assets/images
curl -L "https://www.mycityinfo.com/wp-content/uploads/2017/04/my-city-info-logo-t-3.png" \
  -o assets/images/logo.png
test -s assets/images/logo.png && echo "OK: logo downloaded" || echo "ERROR: download failed or empty"
```

Expected: `OK: logo downloaded`. Do NOT proceed to Step 2 if output is `ERROR`.

- [ ] **Step 2: Update header logo src**

In `views/partials/header.php`, change line ~32:

```php
// OLD:
src="https://www.mycityinfo.com/wp-content/uploads/2017/04/my-city-info-logo-t-3.png"

// NEW:
src="/assets/images/logo.png"
```

- [ ] **Step 3: Update footer logo src**

In `views/partials/footer.php`, change line ~12:

```php
// OLD:
src="https://www.mycityinfo.com/wp-content/uploads/2017/04/my-city-info-logo-t-3.png"

// NEW:
src="/assets/images/logo.png"
```

- [ ] **Step 4: Verify**

Load `/` and check header and footer logos render correctly. Open DevTools → Network tab, confirm logo loads from `localhost` not `mycityinfo.com`.

- [ ] **Step 5: Commit**

```bash
git add assets/images/logo.png views/partials/header.php views/partials/footer.php
git commit -m "feat: serve logo from local assets instead of external WordPress URL"
```

---

## Task 2: Add Skip-to-Content Link for Keyboard Accessibility

**Files:**
- Modify: `views/partials/header.php` — insert before line 26 (`<header ...>`)
- Modify: `views/layout.php` — add `id` to `<main>` at line 73

**Context:** No skip link exists. Keyboard users tab through all nav links on every page. Bootstrap ships `.visually-hidden-focusable` which shows the link only on keyboard focus. The `<main class="mci-main">` tag is at `views/layout.php` line 73 — this is the correct target.

- [ ] **Step 1: Add skip link to header partial**

In `views/partials/header.php`, insert this as the very first HTML output, before line 26 `<header class="site-header sticky-top">`:

```html
<!-- Skip to main content — keyboard / screen-reader shortcut -->
<a href="#mci-main-content" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 m-1 rounded-2 fw-bold" style="z-index:9999;background:var(--mci-gradient-cta);color:#fff;text-decoration:none;">
  Skip to main content
</a>
```

- [ ] **Step 2: Add target id to `<main>` in layout**

In `views/layout.php` line 73, update the `<main>` tag:

```php
// OLD (line 73):
<main class="mci-main">

// NEW:
<main class="mci-main" id="mci-main-content">
```

- [ ] **Step 3: Verify**

Open any page, press Tab once. A "Skip to main content" styled link should appear in the top-left corner. Press Enter — focus should jump past the navbar to the main content area.

- [ ] **Step 4: Commit**

```bash
git add views/partials/header.php views/layout.php
git commit -m "feat: add skip-to-content link for keyboard accessibility"
```

---

## Task 3: Taller Business Hero Banner

**Files:**
- Modify: `assets/css/business.css` — lines 73–92 (`.mci-business-hero` height values)

**Context:** Current heights are 120px / 148px / 180px. Modern business profile pages (Yelp, GMB) use 200–320px for visual impact. The profile photo overlap (`.mci-business-profile-wrap` uses `margin-top: -2.5rem` / `-3rem` / `-3.25rem`) will continue to work correctly with taller heights — the negative margin is relative to the profile wrap itself, not the hero height.

- [ ] **Step 1: Update hero heights in business.css**

In `assets/css/business.css`, find and replace the three height declarations for `.mci-business-hero`:

```css
/* OLD — find these exact rules: */
.mci-business-hero {
  /* ... other properties ... */
  height: 120px;
}
@media (min-width: 576px) {
  .mci-business-hero {
    height: 148px;
  }
}
@media (min-width: 992px) {
  .mci-business-hero {
    height: 180px;
  }
}

/* NEW — change only the height values: */
.mci-business-hero {
  height: 200px;
}
@media (min-width: 576px) {
  .mci-business-hero {
    height: 260px;
  }
}
@media (min-width: 992px) {
  .mci-business-hero {
    height: 320px;
  }
}
```

- [ ] **Step 2: Verify**

Open any business detail page (e.g. `/business/some-slug/`). The hero banner should be taller across all breakpoints. The profile photo should still overlap the bottom of the hero correctly.

- [ ] **Step 3: Commit**

```bash
git add assets/css/business.css
git commit -m "feat: increase business hero banner height for stronger visual impact"
```

---

## Task 4: Add Breadcrumb Navigation

**Files:**
- Modify: `assets/css/theme.css` — append breadcrumb styles
- Modify: `business/index.php` — add breadcrumb below hero/profile block (~line 521)
- Modify: `business-listing/index.php` — add breadcrumb before `<h1>` (~line 136)
- Modify: `business-category/detail.php` — add breadcrumb at top of content

**Context:**
- `business/index.php`: available variables are `$listing['title']`, `$listing['category']`. Insert breadcrumb **after** the profile block (around line 521, after the `</div>` that closes `.mci-business-profile-wrap`), NOT before the hero. This places it in the natural reading flow: hero → profile photo → breadcrumb → content.
- `business-listing/index.php`: available variables are `$category` (slug, may be empty), insert before `<h1 class="h4 fw-bold mb-1">Listings</h1>` (~line 136).
- `business-category/detail.php`: insert at the top of the main content output.

- [ ] **Step 1: Add breadcrumb CSS to theme.css**

Append to the end of `assets/css/theme.css`:

```css
/* ─── Breadcrumb ────────────────────────────────────── */
.mci-breadcrumb {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.25rem;
  font-size: var(--mci-text-xs, 0.75rem);
  font-weight: var(--mci-weight-semibold, 600);
  color: var(--mci-muted, #64748b);
  padding: 0.5rem 0 0.25rem;
}

.mci-breadcrumb a {
  color: var(--mci-muted, #64748b);
  text-decoration: none;
  transition: color 0.15s ease;
}

.mci-breadcrumb a:hover {
  color: var(--mci-color-primary-deep, #6d28d9);
}

.mci-breadcrumb__sep {
  color: var(--mci-border, #cbd5e1);
  font-size: 0.65rem;
  margin: 0 0.1rem;
}

.mci-breadcrumb__current {
  color: var(--mci-body-color, #0f172a);
  font-weight: var(--mci-weight-bold, 700);
}
```

- [ ] **Step 2: Add breadcrumb to business detail page**

In `business/index.php`, find the closing `</div>` of `.mci-business-profile-wrap` (around line 519) and insert the breadcrumb after it, before the `<div class="px-1 px-sm-2">` that wraps the main content columns:

```php
// Find this block (around line 519-521):
  </div><!-- /.mci-business-profile-wrap -->

  <div class="px-1 px-sm-2">

// Insert between them:
  </div><!-- /.mci-business-profile-wrap -->

  <!-- Breadcrumb -->
  <nav class="mci-breadcrumb px-1 mb-1" aria-label="Breadcrumb">
    <a href="/">Home</a>
    <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
    <a href="/business-listing/">Listings</a>
    <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
    <span class="mci-breadcrumb__current"><?= htmlspecialchars($listing['title']) ?></span>
  </nav>

  <div class="px-1 px-sm-2">
```

- [ ] **Step 3: Add breadcrumb to listings page**

In `business-listing/index.php`, find the `<h1 class="h4 fw-bold mb-1">Listings</h1>` line (~line 136) and insert the breadcrumb before the enclosing `<div>`:

```php
// Find (around line 133-136):
<div>
  <h1 class="h4 fw-bold mb-1">Listings</h1>

// Replace with:
<div>
  <!-- Breadcrumb -->
  <nav class="mci-breadcrumb mb-2" aria-label="Breadcrumb">
    <a href="/">Home</a>
    <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
    <span class="mci-breadcrumb__current">Listings<?php if ($category !== ''): ?> — <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $category))) ?><?php endif; ?></span>
  </nav>
  <h1 class="h4 fw-bold mb-1">Listings</h1>
```

- [ ] **Step 4: Add breadcrumb to category detail page**

In `business-category/detail.php`, find where `ob_start()` ends and the main HTML output begins. Add the breadcrumb at the very top. The available variable is `$categoryRow['name']` (category name string). Check the file for the exact variable name.

```php
<!-- Breadcrumb -->
<nav class="mci-breadcrumb mb-3" aria-label="Breadcrumb">
  <a href="/">Home</a>
  <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
  <a href="/business-category/">Categories</a>
  <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
  <span class="mci-breadcrumb__current"><?= htmlspecialchars($categoryRow['name'] ?? '') ?></span>
</nav>
```

- [ ] **Step 5: Verify**

Open a business detail page, the listings page (with and without a category filter), and a category detail page. Each should show a small breadcrumb trail above the content. Verify it wraps gracefully on 375px mobile. Verify all links are tappable (min 44px tap area satisfied by `padding: 0.5rem 0`).

- [ ] **Step 6: Commit**

```bash
git add assets/css/theme.css business/index.php business-listing/index.php business-category/detail.php
git commit -m "feat: add breadcrumb navigation to business, listings, and category pages"
```

---

## Task 5: Sticky Filter Panel on Listings Page

**Files:**
- Modify: `assets/css/listings.css` — append sticky filter CSS
- Modify: `business-listing/index.php` — line 167, add class to filter column

**Context:** The filter panel `#mciFiltersPanel` is in `.col-12.col-lg-4` (line 167). On desktop (≥992px) it should stick at the top. The business detail sidebar already uses this pattern (`.mci-business-sidebar-sticky` in `business.css`). The mobile JS toggle (`applyMobileState`) sets `panel.style.display` — on resize from mobile to desktop, Bootstrap's `display:block` via the `show` class takes over, so the sticky CSS will not conflict. The `max-height + overflow-y: auto` ensures the filter panel scrolls independently if it's taller than the viewport.

- [ ] **Step 1: Add sticky filter CSS to listings.css**

Append to `assets/css/listings.css`:

```css
/* ─── Sticky filter panel (desktop only) ─────────────── */
@media (min-width: 992px) {
  .mci-listings-filter-sticky {
    position: sticky;
    top: 5rem; /* clears the fixed navbar (~56px) with extra breathing room */
    max-height: calc(100vh - 6rem);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--mci-border, #e2e8f0) transparent;
  }
}
```

- [ ] **Step 2: Add class to filter column wrapper**

In `business-listing/index.php` line 167:

```php
// OLD:
<div class="col-12 col-lg-4" id="mciFiltersPanel">

// NEW:
<div class="col-12 col-lg-4 mci-listings-filter-sticky" id="mciFiltersPanel">
```

- [ ] **Step 3: Verify**

Open `/business-listing/` on a desktop viewport (≥992px) with enough listings to scroll. The filter panel should remain visible while results scroll. On mobile (< 992px), the filter panel should still collapse/expand normally via the "Filters" toggle button. Test resizing from mobile → desktop to ensure no stuck display state.

- [ ] **Step 4: Commit**

```bash
git add assets/css/listings.css business-listing/index.php
git commit -m "feat: make listings filter panel sticky on desktop"
```

---

## Task 6: Fix Footer CTA Button Styling

**Files:**
- Modify: `views/partials/footer.php` — line 41

**Context:** The "Contact us" button uses `btn btn-sm btn-outline-dark mci-footer__cta`. The `mci-footer__cta` class (defined in `theme.css` lines 676–687) already provides all needed styling for the dark footer: violet-tinted border, white text, hover glow. The `btn-outline-dark` class adds an unnecessary hover gradient override that fights `mci-footer__cta`. Removing `btn-outline-dark` leaves only Bootstrap's `btn` base (padding/sizing) + `btn-sm` + `mci-footer__cta` (border/color/hover).

Verify in `theme.css` that `mci-footer__cta` declares `border-width: 1.5px !important` — it does (line 677). Bootstrap's `btn` base resets border, and `btn-sm` sets padding/font-size. These three classes together are sufficient.

- [ ] **Step 1: Remove btn-outline-dark from footer CTA**

In `views/partials/footer.php` line ~41:

```php
// OLD:
<a href="/contact/" class="btn btn-sm btn-outline-dark mci-footer__cta">

// NEW:
<a href="/contact/" class="btn btn-sm mci-footer__cta">
```

- [ ] **Step 2: Verify**

In the footer, the "Contact us" button should be visible against the dark background with a subtle violet-tinted border and white text. In non-hover state, verify the border is visible (not hidden). On hover, it should show a gentle violet tint — NOT a full gradient. Verify the button has correct size/padding (same as before).

- [ ] **Step 3: Commit**

```bash
git add views/partials/footer.php
git commit -m "fix: remove btn-outline-dark from footer CTA, use mci-footer__cta only"
```

---

## Task 7: Refactor btn-dark Global Override

**Files:**
- Modify: `assets/css/theme.css` — narrow the `.btn-dark` gradient override scope
- Modify: multiple `.php` files — replace `btn-dark` CTAs with `mci-btn-gradient`

**Context:** `theme.css` line ~393 overrides ALL `.btn-dark` with a violet gradient via `body.mci-body .btn-dark`. **Important:** `mci-body` is set on ALL pages (layout.php line 14: `$__mciBodyClass = 'mci-body'`), including CP and subscriber pages. This means every `btn-dark` button on every page currently renders as a violet gradient — this is sitewide.

The fix: switch intentional gradient CTAs to `.mci-btn-gradient` explicitly, then either remove the `btn-dark` override entirely or keep it (since the current behavior may be intentional for all primary actions). The simplest safe approach: keep the override rule as-is (since it's working), but for any **CP/admin** pages where a neutral dark button is actually desired, switch those specific usages to `btn-secondary` or `btn-outline-secondary`.

Scope: 35 PHP files use `btn-dark`. Focus on CP pages where a neutral action (not a primary CTA) is being rendered as a gradient button unintentionally.

- [ ] **Step 1: Audit CP pages for non-CTA btn-dark usages**

```bash
grep -n "btn-dark" c:/Projects/apps/MyCityInfo/mycity/cp/**/*.php 2>/dev/null
grep -n "btn-dark" c:/Projects/apps/MyCityInfo/mycity/cp/*.php 2>/dev/null
```

Review each result. CTAs like "Save", "Submit", "Add" can keep the gradient. Neutral actions like "Cancel", "Back", pagination controls, table row actions should be changed to `btn-outline-secondary` or `btn-secondary`.

- [ ] **Step 2: Fix non-CTA usages in CP pages**

For each non-CTA `btn-dark` identified in Step 1, change to `btn-outline-secondary` (for outlined look) or `btn-secondary` (for solid neutral). Example:

```php
// A "Cancel" or "Back" button — should be neutral:
// OLD:
class="btn btn-sm btn-dark"
// NEW:
class="btn btn-sm btn-outline-secondary"
```

- [ ] **Step 3: Verify**

Load `/cp/dashboard/`, `/cp/listings/`, and a few other CP pages. Primary save/submit buttons should still be violet gradient. Neutral/secondary actions should now look clearly secondary (gray, not violet).

- [ ] **Step 4: Commit**

```bash
git add cp/  # add all changed CP files
git commit -m "refactor: fix non-CTA btn-dark usages in CP pages to use neutral styling"
```

---

## Task 8: Add Social Proof Badge to Login Form

**Files:**
- Modify: `login/index.php` — insert before "Don't have an account?" line (~line 169)

**Context:** The login form already has Remember me, Forgot password, and social login placeholders. A trust badge near the bottom of the form adds social proof. Use a static message (can be made dynamic later). The badge uses existing CSS variables — no new CSS needed.

- [ ] **Step 1: Add trust badge to login form**

In `login/index.php`, find line ~169:

```php
<div class="text-muted small text-center mt-auto pt-3">
  Don't have an account? ...
```

Insert immediately before it:

```php
<div class="text-center mt-3 mb-1">
  <span class="badge rounded-pill px-3 py-2" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);font-size:var(--mci-text-xs);font-weight:700;border:1px solid rgba(124,58,237,0.2);">
    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Trusted by thousands of local businesses
  </span>
</div>
```

- [ ] **Step 2: Verify**

Open `/login/`. Near the bottom of the sign-in form card, a small violet pill badge should appear reading "Trusted by thousands of local businesses" with a shield icon. It should appear above the "Don't have an account?" line.

- [ ] **Step 3: Commit**

```bash
git add login/index.php
git commit -m "feat: add social proof trust badge to login form"
```

---

## Task 9: Dark Mode Foundation

**Files:**
- Modify: `assets/css/theme.css` — append `@media (prefers-color-scheme: dark)` for global surfaces
- Modify: `assets/css/auth-pages.css` — append dark mode rules for auth-specific elements

**Context:** No dark mode exists. This task adds CSS token overrides activated by `prefers-color-scheme: dark`. Scope is minimal — only body background, surfaces (cards), text, borders. The header (`#0a0f1e`) and sidebar are already dark — no change needed for them.

Auth-specific rules (`.mci-auth-benefits`, `.mci-auth-form-card`) are defined in `auth-pages.css`, so their dark mode overrides must also go in `auth-pages.css` (not `theme.css`), because `auth-pages.css` is loaded conditionally on auth pages via `$extraHead`.

- [ ] **Step 1: Append global dark mode tokens to theme.css**

Append to the end of `assets/css/theme.css`:

```css
/* ─── Dark mode — global token overrides ───────────────
 * Activated automatically when OS/browser dark mode is on.
 * Header (#0a0f1e) and sidebars are already dark — unchanged.
 * ─────────────────────────────────────────────────────── */
@media (prefers-color-scheme: dark) {
  :root {
    --mci-body-bg:   #0a0f1e;
    --mci-body-color: #f1f5f9;
    --mci-surface:   #131929;
    --mci-surface-2: #1a2035;
    --mci-border:    rgba(255, 255, 255, 0.1);
    --mci-muted:     #94a3b8;

    /* Bootstrap variable passthrough */
    --bs-body-bg:      #0a0f1e;
    --bs-body-color:   #f1f5f9;
    --bs-border-color: rgba(255, 255, 255, 0.1);
  }

  /* Bootstrap cards and form controls */
  .mci-body .card {
    background-color: var(--mci-surface);
    border-color: var(--mci-border);
    color: var(--mci-body-color);
  }

  .mci-body .form-control,
  .mci-body .form-select {
    background-color: var(--mci-surface);
    color: var(--mci-body-color);
    border-color: var(--mci-border);
  }

  .mci-body .form-control::placeholder {
    color: var(--mci-muted);
  }

  /* Table header violet tint stays readable */
  .mci-body .table thead.table-light th {
    background: rgba(124, 58, 237, 0.2) !important;
    color: #c4b5fd !important;
  }

  /* bg-white utility overrides */
  .mci-body .bg-white {
    background-color: var(--mci-surface) !important;
  }
}
```

- [ ] **Step 2: Append auth-specific dark mode rules to auth-pages.css**

Append to the end of `assets/css/auth-pages.css`:

```css
/* ─── Dark mode — auth page surfaces ───────────────────── */
@media (prefers-color-scheme: dark) {
  .mci-auth-form-card {
    background-color: var(--mci-surface, #131929);
    border-color: var(--mci-border, rgba(255, 255, 255, 0.1)) !important;
  }

  .mci-auth-benefits {
    border-color: rgba(255, 255, 255, 0.08);
    /* gradient already dark — no change needed */
  }
}
```

- [ ] **Step 3: Verify**

Enable dark mode in your OS (macOS: System Preferences → Appearance → Dark; or in Chrome DevTools → Rendering → prefers-color-scheme: dark). Open:
- `/` — body and cards should be dark
- `/business-listing/` — filter panel and listing cards should be dark
- `/login/` — form card should be dark, benefits panel unchanged (already dark gradient)
- Header and footer should look identical to light mode (already dark)
- Violet gradient buttons and text should remain readable

- [ ] **Step 4: Commit**

```bash
git add assets/css/theme.css assets/css/auth-pages.css
git commit -m "feat: add CSS dark mode foundation via prefers-color-scheme media query"
```

---

## Execution Order

**Recommended:** 1 → 2 → 3 → 4 → 5 → 6 → 8 → 9 → 7

Task 7 (btn-dark refactor) is last because it requires the most manual audit work across 35 files. All other tasks are low-risk and mechanical.

## Summary Table

| # | Task | Files Changed | Risk |
|---|------|--------------|------|
| 1 | Local logo | header.php, footer.php, assets/images/logo.png | Low |
| 2 | Skip-to-content | header.php, layout.php | Low |
| 3 | Taller hero | business.css | Low |
| 4 | Breadcrumbs | theme.css, business/index.php, business-listing/index.php, business-category/detail.php | Low |
| 5 | Sticky filters | listings.css, business-listing/index.php | Low |
| 6 | Footer CTA button | footer.php | Low |
| 7 | btn-dark refactor | theme.css, cp/ pages | Medium |
| 8 | Login social proof | login/index.php | Low |
| 9 | Dark mode foundation | theme.css, auth-pages.css | Low–Medium |
