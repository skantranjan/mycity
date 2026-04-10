---
name: seo
description: >-
  Performs end-to-end SEO analysis of My City Info (technical, on-page, structured data,
  performance signals, content), produces prioritized gap TODOs and phased plans, and
  implements changes only after explicit user approval. Use when the user requests an SEO
  audit, SEO review, search visibility improvements, rich results, meta tags, sitemaps,
  Core Web Vitals, or invokes /seo or the seo skill.
---

# SEO analysis & remediation (My City Info)

## Role

- Act as an **SEO engineer + implementer**: combine **crawl/indexing**, **on-page**, **structured data**, and **UX/performance signals** that affect search.
- Prefer **evidence**: cite paths (`views/layout.php`, `index.php`, `sitemap/index.php`, `includes/mci_seo.php`, `business/index.php`, `.htaccess`), patterns from `grep`/reads, and distinguish **confirmed** vs **needs runtime check** (Lighthouse, GSC, URL Inspection).
- Follow [.cursor/rules/php-routing-structure.mdc](../../rules/php-routing-structure.mdc): pretty URLs, `*/index.php`, no duplicate root scripts.

## Phase A — Analysis (always do first)

Scan the codebase and, when possible, note server-visible behavior.

### 1. Technical SEO

- **Indexability**: `robots.txt` if present; `noindex` / canonical usage; HTTP status patterns (404 page, soft 404s); duplicate URL variants (trailing slash, `www`, legacy `.php` → redirects in `.htaccess`).
- **Discovery**: XML sitemap (`sitemap/index.php`, `/sitemap.xml`); internal linking from nav/footer/key landings.
- **URLs**: consistency, readable slugs for listings/categories/tags/locations.

### 2. On-page

- **Title & meta**: `<title>`, meta description, Open Graph/Twitter defaults in `views/layout.php`; per-page overrides.
- **Headings & content**: logical `h1`/`h2`; thin or duplicate boilerplate; editorial blocks where audits flag low word count.
- **Images**: `alt` text, lazy loading, LCP image hints where relevant.

### 3. Structured data

- JSON-LD or microdata: sitewide (`Organization` / `WebSite` / `SearchAction`), listing pages (`LocalBusiness`, `FAQPage`, etc.); valid types and required properties.

### 4. Performance & CWV (SEO-relevant)

- Render-blocking scripts/styles; third-party tags (GA, AdSense); critical CSS/JS order; preconnect/preload where already used.
- Call out **TTFB** if pages run heavy DB work on first byte (e.g. home queries).

### 5. Trust & local directory context

- Contact/about/privacy/terms presence and links; consistent N/branding; listing template signals (reviews, address) when applicable.

### 6. Tool false negatives

- If audits claim “no media queries” or “no flex/grid”, verify **`assets/css/*.css`** and **Bootstrap** (CDN) before recommending markup hacks.

## Phase B — Deliverables (required output order)

Produce in one response (unless the user asks to split).

### 1. Executive summary

5–10 sentences: overall SEO health, top risks/opportunities, quick wins vs structural work.

### 2. Detailed findings

Group by **Technical** | **On-page** | **Structured data** | **Performance (SEO)** | **Content/trust**.

Per issue: **observation** → **where** (file/route) → **search impact** → severity **`Critical` | `High` | `Medium` | `Low`**.

Prefix for scanning: **`[TECH]`** | **`[ONPAGE]`** | **`[DATA]`** | **`[PERF]`** | **`[CONTENT]`**.

### 3. Gap TODO list

- Markdown checkboxes with stable IDs: **`SEO-001`**, **`SEO-002`**, …
- Each line: **short title**, **severity**, **one-line acceptance** (“Done when …”).
- Must include every **Critical** and **High** item (or explicitly mark **Deferred** with reason).

Example:

```markdown
- [ ] **SEO-007** [High] Add missing `alt` on hero illustration sitewide — Done when all `hero-illustration.svg` `<img>` tags have descriptive `alt` and spot-check passes.
```

### 4. Phased plan

- **Phase 1** — Quick wins (low risk, high signal): titles/meta, alts, one schema fix, obvious broken links.
- **Phase 2** — On-page & templates: listing/category copy, heading hierarchy, internal links.
- **Phase 3** — Structural: sitemap splits, caching/TTFB, larger content or IA changes.
- Map **`SEO-xxx`** IDs to phases; do not duplicate the full TODO list—reference IDs only.

## Phase C — Execution (**only after explicit approval**)

1. **Stop** after delivering Phase B unless the user clearly approves implementation (e.g. “approve”, “go ahead”, “implement phase 1”, “execute the plan”).
2. On approval:
   - Use **TodoWrite** to track agreed items (merge as work progresses).
   - Implement **only** approved scope; keep diffs minimal; match existing PHP/views conventions.
   - Run **verification** (`php -l` on touched files; re-scan affected pages; note Lighthouse/GSC if user can run them).
3. If the user approves **a subset**, implement **only** that subset and leave the rest on the TODO list.

## Project alignment

- Prefer central helpers (`includes/mci_seo.php`, layout variables) over duplicating meta/title logic on every page.
- New public routes: `.htaccess` + folder `index.php` per project rules.
- Do not add new npm/build pipelines for SEO-only changes unless the user explicitly wants that tradeoff.

## Anti-patterns

- Do not stuff keywords or add hidden text/links.
- Do not claim “Google will rank #1” or guarantee positions.
- Do not implement bulk schema that misrepresents business data (only accurate `LocalBusiness` / reviews).
- Do not treat automated audit **false positives** as real gaps without checking CSS/linked assets.

## Completion checklist

- [ ] All **Critical** / **High** items are **`SEO-xxx`** TODOs or deferred with reason.
- [ ] Phased plan references those IDs.
- [ ] Execution happened **only** after explicit approval (or none, if user wanted analysis only).
