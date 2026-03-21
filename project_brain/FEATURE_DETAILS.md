# Feature details (human-readable specification)

This document describes **what each major area of the application is for** and **how it is expected to behave**, based on the current codebase. Use it to align stakeholders and to validate that implementation matches intent.

**Scope note:** Much of the public site uses **demo/placeholder data** (e.g. home listing arrays, `mci_directory_listings`). Behavior below reflects **current UI and flows**; backend integration may evolve.

---

## Global layout (all public pages)

### Header navigation (`views/partials/header.php`)

- **Position:** Sticky top (`sticky-top`), Bootstrap navbar, collapses on small screens.
- **Brand:** Logo image left, links to `/`.
- **Primary nav (always visible when expanded):**
  - Listed Business → `/business-listing/`
  - Business Categories → `/business-category/`
  - Products → `/products/`
  - Services → `/services/`
  - Add Business → `/submit-business-listing/` (guest/cp) or `/subscriber/list-business/` when `$appArea === 'subscriber'`

**Guest (not logged in as subscriber/cp):**

- Register → `/register/`
- Login → `/login/`

**Subscriber (`$appArea === 'subscriber'`):**

- Dashboard → `/subscriber/dashboard/`
- Account dropdown: avatar or placeholder, display name, links to Update profile (`/subscriber/profile/`), Change password (`/subscriber/change-password/`), Logout (`/subscriber/logout/?perform=1`)

**Control panel (`$appArea === 'cp'`):**

- Dashboard → `/cp/dashboard/`
- Admin dropdown: profile, change password, logout (`/cp/logout/?perform=1`)

### Footer (`views/partials/footer.php`)

- **Layout:** Responsive grid (not strictly four equal columns on all breakpoints):
  - **Column 1 (wider on md+):** Logo, tagline “Explore local business, services and places of your city.”
  - **Column 2:** “Company” — About, Contact
  - **Column 3:** “Legal” — Privacy Policy, Terms of Use, Disclaimer, Cookies
  - **Column 4:** “Need help?” — short help text, Contact us button
- **Bottom bar:** Copyright (2020–current year), “Community-first”, “Fast listings”

---

## Home page (`/` — `index.php`)

**Purpose:** City discovery landing: hero, search, categories, recent and popular listings.

### Block 1 — Hero

- **Background:** Decorative blobs + optional hero image (right column on large screens).
- **Headline:** Dynamic city name via `home-city.js` (`#heroCityName`, `#heroTaglineCity`) — defaults to “your city” until JS/location updates.
- **CTAs (desktop):** “List your business” (`/submit-business-listing/`), “Browse all” (`/business-listing/`).
- **CTAs (mobile):** Same actions, stacked below hero area.

### Search (hero area)

- **Form:** GET to `/business-listing/` with fields:
  - `what` — keyword
  - `where` — city/area
- **Submit:** “Search” button.

### Quick value strip

- Three equal cards in a row (responsive): List or claim, Reach locals, Get enquiries — informational only.

### Browse categories

- **Section title:** “Browse categories” with short subtitle.
- **Actions:** “See all listings →”, “See all categories →”.
- **Grid:** Category tiles (emoji + name) linking to `/business-listing/?category=<name>`.
- **Current data:** **10** categories in PHP array (not 12) — demo set.

### Recent listings

- **Title:** “Recent listings” with subtitle.
- **CTA:** “+ List your business” → `/submit-business-listing/`.
- **Grid:** One card per listing via `views/components/listing-card.php` (`$variant = 'home'`).
- **Current data:** **8** demo listings — layout is responsive (multiple columns on wide screens; not fixed “4 per row x 2 rows” at all breakpoints).

### Popular in [city]

- **Title:** “Popular in” + `<span id="homePopularCity">` for dynamic city label.
- **CTA:** “See all in directory →” → `/business-listing/`.
- **Grid:** Same card component as recent; **8** demo listings.

### Scripts / assets

- `assets/css/home.css`, `assets/js/home-city.js` (city name behavior).

---

## Listed businesses (`/business-listing/`)

**Purpose:** Directory browse with filters.

**Expected behavior:**

- Accepts query params: `what`, `where`, `category`, `tag`, `price_range` (and passes through filters in UI).
- Listings sourced from `includes/mci_directory_listings.php` (placeholder catalog); filtering is client-side/UI logic as implemented.
- Page title and listing-specific CSS/JS.

---

## Business categories (`/business-category/`)

**Purpose:** Browse or drill into categories (implementation per `business-category/index.php`).

---

## Products (`/products/`) and Services (`/services/`)

**Purpose:** Dedicated listing or browse pages for products vs services (see respective `index.php` for layout and data).

---

## Add business (guest flow)

**Purpose:** Multi-step wizard to submit a business listing.

- **Guest path:** `/submit-business-listing/` — large form/wizard (`submit-business-listing/index.php`).
- **Subscriber path:** `/subscriber/list-business/` — same conceptual flow when logged in.

---

## Authentication (public)

- **Register:** `/register/` — posts to API for subscriber signup; terms/privacy checkboxes; sets session + JWT cookie.
- **Login:** `/login/` — subscriber login via API.
- **Logout:** `/logout/` or subscriber/cp logout routes as linked from header.

---

## Subscriber area (`/subscriber/...`)

**Expected areas (routes exist):**

- Dashboard, profile, change password, listings, list business, favourites, enquiries, reviews, listing delete, logout.

**Behavior:** Session + API JWT; header uses `$appArea === 'subscriber'`.

---

## Control panel (`/cp/...`)

**Expected areas (routes exist):**

- Dashboard, categories, tags, users, coadmins, listings, anonymous approvals, anonymous business, profile, change password, logout.

**Behavior:** Admin/cp roles; API-backed moderation for categories/queues where implemented.

---

## Business detail page (`/business/` or `business/index.php`)

**Purpose:** Single business profile: reviews, claim, etc. (see `business/index.php`).

---

## API v1 (`/api/v1/*`)

**Purpose:** JSON API for auth, categories, tags, category requests, anonymous submissions, co-admins, health.

**Expected behavior:**

- `GET /api/v1/health` — health check.
- Auth: subscriber register/login/logout, cp login/logout, JWT cookie `mci_api_token`.
- Protected routes use role checks (`subscriber`, `super_admin`, `co_admin`).
- CORS headers for browser clients.

**Schema:** `api/v1/migrations/001_create_core_tables.sql`.

---

## How to use this document

- **Product/QA:** Walk each section and compare to the live site or staging.
- **Development:** When changing behavior, update this file and [FEATURE_VALIDATION_CHECKLIST.md](FEATURE_VALIDATION_CHECKLIST.md).
