# My City Info (PHP) — Project Notes

This repository is a server-rendered PHP web app (“My City Info”) for discovering local businesses and submitting/updating listings.

## What’s implemented so far

### Public site basics
- PHP page templates (`*.php`) rendered server-side with shared layout in `views/layout.php`.
- Global design system / theme tokens in `assets/css/theme.css` (fonts, indigo/amber palette, shadows, radii).
- Reusable components (example locations):
  - `views/components/*` (listing rows/cards, business-hours grid, etc.)
  - `views/partials/*` (header, footer, navigation sidebars, CTAs)

### “List your business” submit flow (public)
- Page: `submit-business-listing/index.php` (pretty URL: `/submit-business-listing/`)
- UI: multi-step wizard with step indicators and a progressive form.
- Key behaviors implemented:
  - Dynamic **Services & products** add-more UI (FAQ-style rows).
  - **Gallery** upload drop zone.
  - **Branded images** (logo/profile/banner) with client-side cropping using Cropper.js:
    - shared crop modal: `#cropModal`
    - cropped output stored in hidden fields (`img_logo`, `img_profile`, `img_banner`) as data URLs (base64).
  - A local **preview page**:
    - Button `Preview listing` saves the current form state into `localStorage`
    - Opens `/listing-preview/` (`listing-preview/index.php`)
    - Local preview data is cleared on submit

### Business detail page layout
- “Nearby” section placement and compact listing rendering were updated previously:
  - `business/index.php` renders “Nearby” in the right sidebar below favorites.
  - `views/components/listing-card.php` supports a `compact` variant for sidebar usage.

## Post-login experiences (Subscriber + CP)

### Themed app shell (subscriber & control panel)
- Layout improvements:
  - `views/layout.php` supports an `$appArea` variable: `subscriber` or `cp`
  - When set, the layout loads `assets/css/app-areas.css` and applies appropriate body classes.
- Styling:
  - `assets/css/app-areas.css` provides deeper panel + sidebar styling aligned with the site theme.

### Header navigation + profile dropdown
- `views/partials/header.php` now hides `Login/Register` when `$appArea` indicates a logged-in shell.
- Instead, there is a profile dropdown with actions:
  - Update profile
  - Change password
  - Logout
- Profile icon is derived from session data and supports photo upload:
  - Helper: `includes/mci_app_profile.php`
  - Subscriber keys: `mci_sub_profile_name`, `mci_sub_profile_avatar`
  - CP keys: `mci_cp_profile_name`, `mci_cp_profile_avatar`

### Subscriber navigation (left sidebar)
- `views/partials/subscriber-sidebar.php` includes:
  - Dashboard
  - List your business
  - My listings
  - Enquiries
  - Comments & ratings
- Sidebars were slimmed down per the “top bar handles profile/logout” approach.

### Subscriber “List your business” (post-login wizard)
- Page: `subscriber/list-business.php`
- UI: 7-step wizard:
  1. Business (details + tags)
  2. Products & services (FAQ-style add-more + pricing section)
  3. Location & contact (includes email_contact)
  4. Business hours
  5. Photos (logo/profile/banner crop + gallery + video URL)
  6. FAQs
  7. Review & publish
- Image cropping is reused on this wizard (Cropper.js).

### Status filtering on reply UIs
- Subscriber screens now support filtering by status:
  - `subscriber/enquiries/index.php` filter options:
    - All, New, Awaiting response, Replied
  - `subscriber/reviews/index.php` filter options:
    - All, New, Awaiting response, Replied
- Replying in the demo updates the in-memory status so filtering reflects changes.

### Delete listing flow (2-step confirmation)
- `subscriber/listings/index.php` routes:
  - View: public `/business/?slug=...`
  - Edit: `/subscriber/list-business/?edit=1&slug=...`
  - Delete: `/subscriber/listing-delete/?slug=...`
- New delete UI:
  - `subscriber/listing-delete/index.php`
  - Step 1: choose delete permanently OR mark permanently closed
  - Step 2: require typing `delete` to confirm permanent deletion (UI demo)

## Theme toggle (light/dark)
- Header toggle added:
  - Implemented in `views/partials/header.php` and `views/layout.php` (uses `sessionStorage`).
- Theme tokens:
  - Dark mode overrides added to `assets/css/theme.css`.
- Additional dark-mode readability fixes were applied to improve visibility of inputs/buttons/cards.

## Key files (quick reference)
- `views/layout.php` — shared layout, includes theme shell logic and JS.
- `assets/css/theme.css` — global theme tokens + dark-mode overrides.
- `assets/css/app-areas.css` — subscriber + CP panel styling.
- `submit-business-listing/index.php` — public submit wizard (`/submit-business-listing/`).
- `listing-preview/index.php` — localStorage-based preview render (`/listing-preview/`).
- `subscriber/list-business/index.php` + `assets/js/subscriber-list-business.js` + `views/partials/subscriber-list-business-inner.php` — post-login wizard.
- `subscriber/enquiries/index.php` — enquiries list + reply + status filter.
- `subscriber/reviews/index.php` — comments/ratings reply + status filter.
- `subscriber/listing-delete/index.php` — 2-step delete confirmation.
- `.htaccess` — pretty URLs and legacy `*.php` rewrites to `*/index.php`.
- `includes/mci_app_profile.php` — session-backed profile avatar/name for header dropdown.

## Notes / assumptions
- Several screens are currently “UI demo” only (backend moderation/review persistence isn’t fully wired yet).
- Cropped images for the wizard preview are stored as data URLs in hidden fields and/or localStorage for preview purposes.

