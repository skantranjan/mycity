# Feature validation checklist

Use this checklist to verify that **what we built** matches **what we expect** (see [FEATURE_DETAILS.md](FEATURE_DETAILS.md)). Mark each row **Pass**, **Fail**, or **N/A**, and add notes.

**How to use:** For each feature, exercise the UI (or API) and confirm the expected behavior. Update [FEATURE_DETAILS.md](FEATURE_DETAILS.md) when requirements change.

---

## Global chrome

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| G1 | Header sticky | Header stays visible while scrolling (sticky-top) | | |
| G2 | Mobile nav | Hamburger toggles collapse; all primary links reachable | | |
| G3 | Logo | Links to home `/` | | |
| G4 | Guest nav | Listed Business, Categories, Products, Services, Add Business, Register, Login visible | | |
| G5 | Subscriber nav | Same primary links + Dashboard + profile dropdown (name, profile, password, logout) | | |
| G6 | CP nav | Same pattern for cp area with cp dashboard and logout | | |
| G7 | Add Business routing | Guest → `/submit-business-listing/`; subscriber → `/subscriber/list-business/` | | |
| F1 | Footer columns | Logo/tagline; Company; Legal; Need help + CTA | | |
| F2 | Footer links | About, Contact, Privacy, Terms, Disclaimer, Cookies work | | |
| F3 | Copyright footer | Year range displays | | |

---

## Home page (`/`)

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| H1 | Hero | Headline, city animation or placeholder, hero image on large screens | | |
| H2 | Hero search | What/Where fields; submit goes to `/business-listing/` with query string | | |
| H3 | Hero CTAs | List business + Browse all (desktop and mobile variants) | | |
| H4 | Value strip | Three info cards visible | | |
| H5 | Categories | Section title + tiles; each tile links to filtered listings | | |
| H6 | Category count | **10** demo categories (verify if product requirement is 10 vs 12) | | |
| H7 | Recent listings | Section + CTA to add business; cards render with title, category, address | | |
| H8 | Recent count | **8** listings in current demo data; grid responsive | | |
| H9 | Popular section | Title includes “Popular in” + city span; “See all” link | | |
| H10 | Popular count | **8** listings in demo data | | |
| H11 | city.js | City name updates in hero/popular if JS enabled | | |

---

## Directory and listing

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| L1 | Business listing page | Loads with filters; listings from directory data | | |
| L2 | Search params | `what`, `where`, `category`, `tag`, `price_range` affect filter as implemented | | |
| L3 | Business category page | Browse/filter by category works per implementation | | |
| L4 | Business detail | Business page shows profile, reviews, etc. per `business/index.php` | | |

---

## Products and services

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| P1 | Products page | Renders per `products/index.php` | | |
| S1 | Services page | Renders per `services/index.php` | | |

---

## Submit / add business

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| B1 | Submit wizard (guest) | `/submit-business-listing/` steps complete without errors | | |
| B2 | Subscriber list business | `/subscriber/list-business/` available when logged in | | |

---

## Auth

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| A1 | Register | Validation, terms/privacy, API success, redirect/session | | |
| A2 | Login | API success, cookie, redirect | | |
| A3 | Logout | Session cleared, cookie cleared as implemented | | |

---

## Subscriber area

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| Sub1 | Dashboard | Loads for logged-in subscriber | | |
| Sub2 | Profile | Update profile page reachable | | |
| Sub3 | Change password | Page reachable | | |

---

## Control panel

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| CP1 | Dashboard | Loads for authorized cp user | | |
| CP2 | Categories/tags | CRUD via API as implemented | | |
| CP3 | Queues | Category requests / anonymous approvals as implemented | | |

---

## API v1

| ID | Feature | Expected | Result | Notes |
|----|---------|----------|--------|-------|
| API1 | Health | `GET /api/v1/health` returns JSON ok | | |
| API2 | Auth endpoints | Register/login return JWT cookie on success | | |
| API3 | Protected routes | 401/403 without valid role | | |

---

## Sign-off

| Role | Name | Date | Notes |
|------|------|------|-------|
| Tester | | | |
| Product owner | | | |
