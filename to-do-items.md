# UX gap fixes (applied)

Aligned with the UI/UX site review: copy matches behavior, fewer misleading controls, clearer nav, local hero art, a11y and infinite-scroll recovery.

- [x] Nav label: “Browse listings” (`views/partials/header.php`)
- [x] Default meta / footer: plural “businesses” (`views/layout.php`, `views/partials/footer.php`)
- [x] Home: grammar, empty categories message, value-strip icons (Bootstrap Icons), hero + listing placeholders without Picsum (`index.php`, `assets/images/hero-illustration.svg`)
- [x] Home: “Established in …” section + honest subtitle; removed misleading “Popular / highly viewed” (`index.php`)
- [x] Home: removed `aria-live` from hero `<h1>` (`index.php`)
- [x] Remember me: 30-day JWT + session cookie + hint text (`login/index.php`, `api/v1/lib/auth_direct.php`, `api/v1/index.php`)
- [x] Login copy: favourites bullet accurate; local hero image (`login/index.php`)
- [x] Register: local hero image (`register/index.php`)
- [x] Listings: subcategory pills `aria-pressed` + `role="group"` (`business-listing/index.php`)
- [x] Listings infinite scroll: “Try again” button, location row without emoji (`assets/js/listings-view.js`)
- [x] Removed duplicate Bootstrap Icons `<link>` where `views/layout.php` already loads them

Deferred (optional / larger scope): defer jQuery in layout (needs audit of every `$extraJS` script), Subresource Integrity on CDNs, `business/` page further mobile IA tweaks (sticky tabs already exist).

---

# Security / performance gaps (audit follow-up — applied)

- [x] **Upload + PATCH images:** `POST /upload/image` and `PATCH /businesses/{id}/images` require JWT (owner or CP admin) **or** short-lived `image_upload_token` from `POST /businesses` (`api/v1/index.php`, `api/v1/lib/business_helpers.php`, `api/v1/lib/image_upload_token.php`, `assets/js/subscriber-list-business.js`).
- [x] **Path hardening:** reject `..`, NUL, `//` in `api_business_patch_images` paths (`api/v1/lib/business_service.php`).
- [x] **No stack traces in production:** `POST /businesses` 500 only includes `detail` when `MCI_DEBUG_API=1` (`.env.example`).
- [x] **Client IP:** `X-Forwarded-For` used only when `REMOTE_ADDR` is in `MCI_TRUSTED_PROXY_IPS` (`api/v1/lib/ip.php`, `.env.example`).
- [x] **CORS:** removed wildcard; reflect Origin when host matches `Host` or `MCI_ALLOWED_API_ORIGINS` (`api/v1/lib/http_security.php`).
- [x] **Baseline API headers:** `X-Content-Type-Options`, `Referrer-Policy` on API responses.
- [x] **Rate limits (file-backed):** login, register, forgot-password, upload image, patch images, public leads (`api/v1/lib/rate_limit.php`).
- [x] **Cache-Control:** `public, max-age=120` on `GET /public/categories` and `/public/tags`.
- [x] **Session fixation:** `session_regenerate_id(true)` on successful subscriber + CP login (`login/index.php`, `cp/login/index.php`).

Still optional: SRI on CDN assets, defer jQuery, Redis-style rate limits, stricter image re-validation beyond MIME, CSP on HTML pages.
