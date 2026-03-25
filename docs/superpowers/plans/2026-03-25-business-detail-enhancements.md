# Business Detail Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enhance the business detail page with a collapsible business hours widget in the right sidebar (above the map), a sticky section tab bar for in-page navigation, per-section empty states for products/services, and a universal "go to top" button across all pages.

**Architecture:** All changes are confined to `business/index.php` (PHP/HTML structure), `assets/css/business.css` (business-page styles), and `views/partials/footer.php` + `assets/css/theme.css` (global go-to-top). No new PHP files. Products and services are separated from their current merged array into two distinct PHP variables drawn from `$dbBiz['services']` and `$dbBiz['products']`. JavaScript for the hours toggle and go-to-top scroll behavior is inlined as small closures following existing patterns.

**Tech Stack:** PHP 8, Bootstrap 5.3, Bootstrap Icons 1.11, vanilla JS (no framework), CSS custom properties from `theme.css`

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `business/index.php` | Modify | Split services/products, add section anchors, section tab bar, collapsible hours widget in sidebar, empty states |
| `assets/css/business.css` | Modify | Styles for tab bar, collapsible hours widget, empty state messages |
| `assets/css/theme.css` | Modify | Global go-to-top button styles |
| `views/partials/footer.php` | Modify | Add go-to-top button HTML (rendered on every page via shared layout) |

---

## Task 1: Split services and products into separate PHP variables

**Files:**
- Modify: `business/index.php:116-123` (the data-mapping block)

The current code merges `$serviceNames` and `$productNames` into `$listing['services']`. We need them separate so we can render distinct sections with individual empty states.

- [ ] **Step 1: Update the `$listing` array construction**

In `business/index.php`, find the `$listing = [` block (around line 137). The key `'services'` currently reads `array_merge($serviceNames, $productNames)`. Split it:

```php
$serviceNames = array_map(
    static fn(array $s): string => (string)($s['name'] ?? ''),
    $dbBiz['services'] ?? []
);
$productNames = array_map(
    static fn(array $p): string => (string)($p['name'] ?? ''),
    $dbBiz['products'] ?? []
);
```

These two variables already exist (lines 116-123) — no change needed there. Update the `$listing` array to keep them separate:

```php
// Replace this line in $listing:
//   'services' => array_merge($serviceNames, $productNames),
// With:
    'services' => $serviceNames,
    'products' => $productNames,
```

- [ ] **Step 2: Verify the page loads without PHP errors**

Open `/business/?slug=any-slug` in the browser. The page should render. The "Services & highlights" section will be temporarily broken (it references `$listing['services']`) — that's fine, we fix it next.

- [ ] **Step 3: Commit**

```bash
git add business/index.php
git commit -m "refactor: split services and products into separate listing keys"
```

---

## Task 2: Add section anchor IDs and restructure main content cards

**Files:**
- Modify: `business/index.php` (~line 595 onward — the main card body)

The About, Services, Products, FAQs, and Ratings sections all live inside the main card. We need each section to have an `id` attribute so the tab bar can scroll to it. We also need to separate Services and Products into distinct sections.

- [ ] **Step 1: Add `id="section-about"` to the About section title**

Find the About `mci-business-section-title` div (around line 597):

```php
// Change:
<div class="card mci-business-card border-0 bg-white mb-4">
  <div class="card-body">
    <div class="mci-business-section-title mb-3">
      <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
      About

// To:
<div class="card mci-business-card border-0 bg-white mb-4">
  <div class="card-body">
    <div id="section-about" class="mci-business-section-title mb-3">
      <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
      About
```

- [ ] **Step 2: Replace the merged "Services & highlights" section with separate Services and Products sections**

Find the block starting at "Services & highlights" (~line 607) and replace through to the end of the chips loop. Replace the entire services block with:

```php
            <div id="section-services" class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-stars" aria-hidden="true"></i>
              Services
            </div>
            <?php if (!empty($listing['services'])): ?>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($listing['services'] as $svc): ?>
                  <span class="mci-business-chip"><?= htmlspecialchars($svc) ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="mci-business-empty-state">No services listed yet.</p>
            <?php endif; ?>

            <div id="section-products" class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-box-seam" aria-hidden="true"></i>
              Products
            </div>
            <?php if (!empty($listing['products'])): ?>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($listing['products'] as $prd): ?>
                  <span class="mci-business-chip"><?= htmlspecialchars($prd) ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="mci-business-empty-state">No products listed yet.</p>
            <?php endif; ?>
```

- [ ] **Step 3: Remove the old Business Hours section from the main card**

Find and delete this block inside the main card body (around line 617-636):

```php
            <div class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-clock-fill" aria-hidden="true"></i>
              Business hours <span class="text-muted fw-normal fs-6">(demo)</span>
            </div>
            <div class="mci-business-hours-wrap">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead>
                  <tr>
                    <th>Day</th>
                    <th>Morning</th>
                    <th>Evening</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <tr><td class="fw-semibold">Mon – Fri</td><td>9:00 – 13:00</td><td>15:00 – 19:30</td></tr>
                  <tr><td class="fw-semibold">Saturday</td><td>10:00 – 16:00</td><td>—</td></tr>
                  <tr><td class="fw-semibold">Sunday</td><td colspan="2" class="text-muted">Closed (demo)</td></tr>
                </tbody>
              </table>
            </div>
```

- [ ] **Step 4: Add `id="section-faq"` to the FAQs section title**

Find the FAQs section title (around line 669):

```php
// Change:
<div class="mci-business-section-title mt-4 mb-3">
  <i class="bi bi-question-circle-fill" aria-hidden="true"></i>
  Frequently asked questions

// To:
<div id="section-faq" class="mci-business-section-title mt-4 mb-3">
  <i class="bi bi-question-circle-fill" aria-hidden="true"></i>
  Frequently asked questions
```

- [ ] **Step 5: Add `id="section-reviews"` to the reviews card**

Find the reviews card (around line 721):

```php
// Change:
<div class="card mci-business-card border-0 bg-white mb-4" id="reviews">

// To:
<div class="card mci-business-card border-0 bg-white mb-4" id="section-reviews">
```

Note: The existing anchor `#reviews` in redirect URLs (POST handlers at line 44, 47) must remain. Update those two redirect URLs to use `#section-reviews` instead:

```php
// Line ~44:
header('Location: /business/' . rawurlencode($targetSlug) . '/?' . $param . '#section-reviews');
// Line ~47:
header('Location: /business/' . rawurlencode($targetSlug) . '/?review_err=' . rawurlencode($err) . '#section-reviews');
// Line ~29:
header('Location: /login/?return=' . rawurlencode('/business/' . $targetSlug . '/#section-reviews'));
// Line ~844:
<a class="small text-muted" href="/logout/?return=<?= rawurlencode('/business/' . $slug . '/#section-reviews') ?>">Sign out</a>
// Line ~844 login link:
href="/login/?return=<?= rawurlencode('/business/' . $slug . '/#section-reviews') ?>"
```

- [ ] **Step 6: Verify the page renders correctly — About, Services, Products, FAQs, Reviews all display with IDs**

Load the page in browser, inspect elements to confirm `id="section-about"` etc. are present. Services section should show chips or "No services listed yet." depending on data.

- [ ] **Step 7: Commit**

```bash
git add business/index.php
git commit -m "feat: add section anchor IDs and split services/products into separate sections with empty states"
```

---

## Task 3: Add the section tab bar (sticky navigation strip)

**Files:**
- Modify: `business/index.php` (insert tab bar HTML just before the `<div class="row g-4 align-items-start">`)
- Modify: `assets/css/business.css` (add tab bar styles)

The tab bar renders above the two-column layout. Clicking a tab smoothly scrolls to the corresponding section. The bar is sticky on scroll.

- [ ] **Step 1: Insert the tab bar HTML**

In `business/index.php`, find the line:

```php
  <div class="px-1 px-sm-2">
    <div class="row g-4 align-items-start">
```

Insert the tab bar immediately before `<div class="row g-4 align-items-start">`:

```php
    <!-- Section tab bar -->
    <nav class="mci-biz-tabs" aria-label="Jump to section">
      <a class="mci-biz-tab" href="#section-about">About</a>
      <a class="mci-biz-tab" href="#section-services">Services</a>
      <a class="mci-biz-tab" href="#section-products">Products</a>
      <a class="mci-biz-tab" href="#section-faq">FAQ</a>
      <a class="mci-biz-tab" href="#section-reviews">Ratings &amp; Reviews</a>
    </nav>
```

- [ ] **Step 2: Add tab bar CSS to `assets/css/business.css`**

Append to the end of the file:

```css
/* ——— Section tab bar ——— */
.mci-biz-tabs {
  display: flex;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  gap: 0;
  background: #fff;
  border-bottom: 2px solid var(--mci-border, #e2e8f0);
  border-radius: var(--mci-radius-md, 0.75rem) var(--mci-radius-md, 0.75rem) 0 0;
  margin-bottom: 1.5rem;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 8px rgba(15,23,42,0.06);
}

.mci-biz-tabs::-webkit-scrollbar {
  display: none;
}

.mci-biz-tab {
  flex-shrink: 0;
  padding: 0.7rem 1.1rem;
  font-size: var(--mci-text-sm, 0.875rem);
  font-weight: var(--mci-weight-semibold, 600);
  color: var(--mci-muted, #64748b);
  text-decoration: none !important;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: color 0.15s ease, border-color 0.15s ease;
  white-space: nowrap;
}

.mci-biz-tab:hover {
  color: var(--mci-color-primary-dark, #7c3aed);
  border-bottom-color: rgba(124,58,237,0.4);
}

.mci-biz-tab.is-active {
  color: var(--mci-color-primary-deep, #6d28d9);
  border-bottom-color: var(--mci-color-primary-dark, #7c3aed);
}
```

- [ ] **Step 3: Add smooth-scroll highlight JS (inline in `$extraJS` in `business/index.php`)**

In `business/index.php`, find the `$extraJS = <<<'HTML'` block and append a new `<script>` block before the closing `HTML;`:

```html
<script>
(function () {
  // Highlight active tab on scroll
  var tabs = document.querySelectorAll('.mci-biz-tab');
  var sections = ['section-about','section-services','section-products','section-faq','section-reviews']
    .map(function (id) { return document.getElementById(id); })
    .filter(Boolean);
  if (!tabs.length || !sections.length) return;

  function setActive(id) {
    tabs.forEach(function (t) {
      var href = t.getAttribute('href');
      t.classList.toggle('is-active', href === '#' + id);
    });
  }

  // Smooth scroll with offset for sticky bar
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function (e) {
      var target = document.querySelector(tab.getAttribute('href'));
      if (!target) return;
      e.preventDefault();
      var barH = document.querySelector('.mci-biz-tabs');
      var offset = barH ? barH.offsetHeight + 8 : 8;
      var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
      window.scrollTo({ top: top, behavior: 'smooth' });
    });
  });

  // IntersectionObserver to highlight tab as sections scroll into view
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) setActive(entry.target.id);
    });
  }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

  sections.forEach(function (s) { observer.observe(s); });
  setActive(sections[0].id); // default first tab active
}());
</script>
```

- [ ] **Step 4: Verify tab bar appears, scrolls, and highlights correctly**

Load the business detail page in browser. Confirm:
- Tab bar appears sticky below the header
- Clicking each tab smoothly scrolls to the section
- Active tab highlights as the section enters the viewport

- [ ] **Step 5: Commit**

```bash
git add business/index.php assets/css/business.css
git commit -m "feat: add sticky section tab bar with smooth scroll and active highlight"
```

---

## Task 4: Add collapsible Business Hours widget in the right sidebar (above the map)

**Files:**
- Modify: `business/index.php` (~line 854 — the right sidebar column)
- Modify: `assets/css/business.css` (hours widget styles)

The hours widget sits at the top of the right sidebar card, before the map. It shows open/closed status collapsed. Clicking expands it to show the full hours table.

> **Note:** Business hours are currently demo/hardcoded. The widget uses the same hardcoded demo hours for now (matching the removed section). When real hours data is wired to `$dbBiz['hours']`, replace the hardcoded table rows.

- [ ] **Step 1: Add PHP helper to compute open/closed status**

In `business/index.php`, just before `$extraHead = <<<'HTML'` (around line 442), insert:

```php
// ── Business hours open/closed status ────────────────────────────────────────
// Demo data — replace with $dbBiz['hours'] when available
$demoHoursRows = [
    ['day' => 'Mon – Fri', 'morning' => '9:00 – 13:00', 'evening' => '15:00 – 19:30'],
    ['day' => 'Saturday',  'morning' => '10:00 – 16:00', 'evening' => '—'],
    ['day' => 'Sunday',    'morning' => '—',             'evening' => '—', 'closed' => true],
];
// Simple open/closed check: Mon-Fri open 9-13 and 15-19:30, Sat 10-16
$bizNow       = new DateTimeImmutable('now');
$bizDow       = (int) $bizNow->format('N'); // 1=Mon … 7=Sun
$bizHour      = (int) $bizNow->format('G');
$bizMin       = (int) $bizNow->format('i');
$bizTimeVal   = $bizHour * 60 + $bizMin;
$bizIsOpen    = false;
if ($bizDow >= 1 && $bizDow <= 5) {
    $bizIsOpen = ($bizTimeVal >= 540 && $bizTimeVal < 780)
              || ($bizTimeVal >= 900 && $bizTimeVal < 1170);
} elseif ($bizDow === 6) {
    $bizIsOpen = ($bizTimeVal >= 600 && $bizTimeVal < 960);
}
$bizStatusLabel = $bizIsOpen ? 'Open now' : 'Closed';
$bizStatusClass = $bizIsOpen ? 'mci-biz-hours__status--open' : 'mci-biz-hours__status--closed';
```

- [ ] **Step 2: Insert the hours widget HTML at the top of the right sidebar card**

In `business/index.php`, find the right sidebar card opening (around line 855):

```php
        <div class="card mci-business-side-card border-0 bg-white mb-4">
          <div class="mci-business-map-wrap">
```

Insert the hours widget between the card opening and the map:

```php
        <div class="card mci-business-side-card border-0 bg-white mb-4">
          <!-- Business hours: collapsed by default -->
          <div class="mci-biz-hours" id="mciBizHours">
            <button
              type="button"
              class="mci-biz-hours__toggle"
              aria-expanded="false"
              aria-controls="mciBizHoursBody"
            >
              <span class="d-flex align-items-center gap-2 min-w-0">
                <i class="bi bi-clock" aria-hidden="true"></i>
                <span class="fw-semibold">Business Hours</span>
                <span class="mci-biz-hours__status <?= $bizStatusClass ?>">
                  <?= htmlspecialchars($bizStatusLabel) ?>
                </span>
              </span>
              <i class="bi bi-chevron-down mci-biz-hours__chevron" aria-hidden="true"></i>
            </button>
            <div class="mci-biz-hours__body" id="mciBizHoursBody" hidden>
              <table class="table table-sm table-borderless align-middle mb-0">
                <tbody class="small">
                  <?php foreach ($demoHoursRows as $hr): ?>
                    <tr>
                      <td class="fw-semibold ps-0"><?= htmlspecialchars($hr['day']) ?></td>
                      <?php if (!empty($hr['closed'])): ?>
                        <td colspan="2" class="text-muted">Closed</td>
                      <?php else: ?>
                        <td><?= htmlspecialchars($hr['morning']) ?></td>
                        <td><?= htmlspecialchars($hr['evening']) ?></td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="mci-business-map-wrap">
```

- [ ] **Step 3: Add JS for hours toggle (append to `$extraJS` block)**

In the `$extraJS` block in `business/index.php`, append before the closing `HTML;`:

```html
<script>
(function () {
  var btn = document.querySelector('.mci-biz-hours__toggle');
  var body = document.getElementById('mciBizHoursBody');
  if (!btn || !body) return;
  btn.addEventListener('click', function () {
    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    if (expanded) {
      body.setAttribute('hidden', '');
    } else {
      body.removeAttribute('hidden');
    }
  });
}());
</script>
```

- [ ] **Step 4: Add hours widget CSS to `assets/css/business.css`**

Append to the file:

```css
/* ——— Collapsible business hours widget (sidebar) ——— */
.mci-biz-hours {
  border-bottom: 1.5px solid var(--mci-border, #e2e8f0);
}

.mci-biz-hours__toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.85rem 1.15rem;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  font-size: var(--mci-text-sm, 0.875rem);
  color: var(--mci-body-color, #0f172a);
  transition: background 0.15s ease;
}

.mci-biz-hours__toggle:hover {
  background: var(--mci-color-primary-soft, #f5f3ff);
}

.mci-biz-hours__chevron {
  flex-shrink: 0;
  transition: transform 0.2s ease;
  font-size: var(--mci-text-sm, 0.875rem);
  color: var(--mci-muted, #64748b);
}

.mci-biz-hours__toggle[aria-expanded="true"] .mci-biz-hours__chevron {
  transform: rotate(180deg);
}

.mci-biz-hours__status {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-size: var(--mci-text-micro, 0.68rem);
  font-weight: var(--mci-weight-extrabold, 800);
  letter-spacing: 0.04em;
  flex-shrink: 0;
}

.mci-biz-hours__status--open {
  background: #dcfce7;
  color: #15803d;
}

.mci-biz-hours__status--closed {
  background: #fee2e2;
  color: #b91c1c;
}

.mci-biz-hours__body {
  padding: 0.25rem 1.15rem 0.85rem;
}
```

- [ ] **Step 5: Verify the hours widget**

Load the business detail page. Right sidebar should show a "Business Hours" row with Open/Closed badge. Clicking it should expand the table showing Mon-Fri, Saturday, Sunday rows. Clicking again should collapse.

- [ ] **Step 6: Commit**

```bash
git add business/index.php assets/css/business.css
git commit -m "feat: add collapsible business hours widget in sidebar with open/closed status"
```

---

## Task 5: Add empty state CSS class

**Files:**
- Modify: `assets/css/business.css`

The empty state message class `.mci-business-empty-state` was used in Task 2 — add its style now.

- [ ] **Step 1: Append to `assets/css/business.css`**

```css
/* ——— Empty state message for sections with no data ——— */
.mci-business-empty-state {
  color: var(--mci-muted, #64748b);
  font-size: var(--mci-text-sm, 0.875rem);
  font-style: italic;
  margin: 0;
  padding: 0.25rem 0;
}
```

- [ ] **Step 2: Commit**

```bash
git add assets/css/business.css
git commit -m "feat: add empty state style for sections with no data"
```

---

## Task 6: Add universal "Go to top" button

**Files:**
- Modify: `views/partials/footer.php` (add button HTML just before closing `</footer>`)
- Modify: `assets/css/theme.css` (append go-to-top styles)
- Modify: `views/layout.php` (append go-to-top JS inline script)

The button is a fixed-position circle in the bottom-right corner. It appears only after scrolling down 300px. Clicking it smooth-scrolls to the top.

- [ ] **Step 1: Add the button HTML to `views/partials/footer.php`**

Find the closing `</footer>` tag and insert the button before it:

```php
  <button
    type="button"
    id="mciGoTop"
    class="mci-go-top"
    aria-label="Go to top"
    hidden
  >
    <i class="bi bi-arrow-up" aria-hidden="true"></i>
  </button>
</footer>
```

- [ ] **Step 2: Add go-to-top CSS to `assets/css/theme.css`**

Append to the end of `assets/css/theme.css`:

```css
/* ——— Go to top button (universal) ——— */
.mci-go-top {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 1050;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  border: none;
  background: var(--mci-color-primary-dark, #7c3aed);
  color: #fff;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(124,58,237,0.35);
  cursor: pointer;
  opacity: 0;
  transform: translateY(0.5rem);
  transition: opacity 0.2s ease, transform 0.2s ease;
  pointer-events: none;
}

.mci-go-top:not([hidden]) {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

.mci-go-top:hover {
  background: var(--mci-color-primary-deep, #6d28d9);
  box-shadow: 0 6px 20px rgba(124,58,237,0.45);
}

@media (max-width: 575px) {
  .mci-go-top {
    bottom: 1rem;
    right: 1rem;
    width: 2.4rem;
    height: 2.4rem;
    font-size: 1rem;
  }
}
```

- [ ] **Step 3: Add go-to-top JS to `views/layout.php`**

In `views/layout.php`, find the closing inline `<script>` block (the one that closes the Bootstrap navbar, around line 96). Append the go-to-top logic inside that same script block just before the closing `}());`:

Actually, to avoid touching the existing closure, add a separate new `<script>` block before `</body>`:

Find `</body>` at the end of `views/layout.php` and insert before it:

```html
    <script>
(function () {
  var btn = document.getElementById('mciGoTop');
  if (!btn) return;
  function syncBtn() {
    if (window.pageYOffset > 300) {
      btn.removeAttribute('hidden');
    } else {
      btn.setAttribute('hidden', '');
    }
  }
  window.addEventListener('scroll', syncBtn, { passive: true });
  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  syncBtn();
}());
    </script>
```

- [ ] **Step 4: Verify the go-to-top button works on multiple pages**

Open the home page, business listing page, and business detail page. Scroll down more than 300px — the purple circle button should appear bottom-right. Click it — should smooth-scroll to top.

- [ ] **Step 5: Commit**

```bash
git add views/partials/footer.php assets/css/theme.css views/layout.php
git commit -m "feat: add universal go-to-top button on all pages"
```

---

## Task 7: Final integration check

- [ ] **Step 1: Verify all four features together on business detail page**

1. Business detail page loads without PHP errors
2. Section tab bar is visible, sticky, scrolls horizontally on small screens
3. Clicking tabs scrolls to correct sections (About, Services, Products, FAQ, Ratings & Reviews)
4. Active tab highlight updates while scrolling
5. Right sidebar shows Business Hours widget above the map
6. Hours widget shows "Open now" or "Closed" badge correctly based on time
7. Clicking hours widget expands/collapses the table
8. Services section shows chips or "No services listed yet."
9. Products section shows chips or "No products listed yet."
10. Go-to-top button appears after scrolling 300px and smooth-scrolls to top

- [ ] **Step 2: Verify go-to-top on a non-business page (e.g. `/business-listing/`)**

Open the listings page, scroll down — go-to-top button should appear.

- [ ] **Step 3: Commit any remaining fixes, then done**

```bash
git add -p
git commit -m "fix: integration fixes for business detail enhancements"
```
