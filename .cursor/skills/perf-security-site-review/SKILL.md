---
name: perf-security-site-review
description: Acts as a senior performance and security engineer to audit My City Info end-to-end for latency, scalability signals, and defensive security; summarizes findings, lists gaps as actionable TODOs, and proposes a phased fix plan. Use when the user requests a security review, performance audit, hardening pass, pen-test prep, OWASP-style review, or site-wide non-functional review.
---

# Senior performance & security site review (My City Info)

## Role and mindset

- Operate as a **senior performance and security engineer**: treat security findings as **risks with likelihood and impact**; treat performance as **user- and cost-visible** (latency, DB load, bandwidth), not vanity scores alone.
- Prefer **evidence**: cite files (`api/v1/index.php`, `api/v1/lib/*`, `includes/*`, `.htaccess`), endpoints, queries, headers, or measurement method. Distinguish **confirmed** (code/log proof) from **hypothesis** (needs validation).
- Stack context: PHP pages + `api/v1` router, MySQL, cookies/JWT, uploads under `storage/`. Follow [.cursor/rules/php-routing-structure.mdc](../../rules/php-routing-structure.mdc). Security guardrails: [project_brain/CODING_AND_SECURITY.md](../../../project_brain/CODING_AND_SECURITY.md).

## When to run dynamic checks

If the environment allows: sample **HTTP headers** (TLS, cookies, `Cache-Control`, CSP if any), **login/API** behavior (auth errors, IDOR hints), and **heavy routes** (listings, search). If only static analysis: state limitations and mark items for runtime verification.

## Security review dimensions (cover each; skip only if out of scope)

Group findings under the same dimension in the written review.

1. **AuthN / AuthZ** — session/JWT lifecycle, role checks on API routes, password/OAuth flows, logout, privilege escalation paths.
2. **Input validation & injection** — SQL (prepared statements only), command/OS injection, SSRF if URLs are fetched, mass assignment on JSON bodies.
3. **Output encoding & XSS** — escaping in `views/` and any dynamic HTML/JS; JSON contexts; CSP baseline (present vs absent).
4. **CSRF** — state-changing forms and cookie-only auth; SameSite and token strategy where applicable.
5. **File uploads & static assets** — path traversal, type/size limits, storage outside web root, hotlinking, executable uploads.
6. **Secrets & configuration** — `.env`, `includes/mci_config.php`, API keys in repo, debug flags in production, error verbosity.
7. **Transport & cookies** — `Secure`, `HttpOnly`, `SameSite`, session fixation, mixed content.
8. **Rate limiting & abuse** — login/submit/API brute force, enumeration, scraping; IP handling (`api/v1/lib/ip.php` and callers).
9. **Logging & monitoring** — no passwords/tokens in logs; actionable audit for admin actions; error leakage to clients.
10. **Dependencies & supply chain** — PHP extensions and any third-party scripts included in pages (minimal stack per project norms).

## Performance review dimensions (cover each; skip only if out of scope)

1. **Backend / PHP** — per-request work, autoload/includes, unnecessary session starts, synchronous external calls.
2. **Database** — N+1 patterns, missing indexes (infer from filters/joins), unbounded queries, pagination, migration vs runtime schema alignment.
3. **API design** — payload size, over-fetching, caching headers for JSON if appropriate, consistent error cost.
4. **Front-of-stack** — asset count/size, CSS/JS cache headers, render-blocking resources, image dimensions and formats (inferred from `assets/` and templates).
5. **Caching layers** — browser/CDN hints via `.htaccess`, optional app-level cache (document if absent).
6. **Scalability signals** — upload directory growth, expensive listing queries, admin/report endpoints without limits.

## Required deliverables (output order)

Produce all four sections in one response (or where the user asks, e.g. under `docs/`). Use **complete sentences**; avoid generic advice (“use best practices”) without a concrete anchor.

### 1. Executive summary

- **Audience**: engineering / leadership.
- **Length**: 5–10 sentences: overall posture, top security risks, top performance risks, quick win themes.

### 2. Detailed review

For each **dimension** (security, then performance, or interleaved by theme):

- **What is already solid** (brief).
- **Issues**: observation → **where** (path, endpoint, query) → **risk or user impact** → **severity**: `Critical` | `High` | `Medium` | `Low`.
- Prefix security items with **[SEC]** and performance with **[PERF]** in the narrative for scanning.

### 3. Gap TODO list

- Markdown checklist; group by **Security** and **Performance** (or by subsystem: API, public pages, cp, subscriber).
- Stable IDs: `SEC-001`, `SEC-002`, … and `PERF-001`, `PERF-002`, …
- Each item: **actionable** title + one line **acceptance** (“done when …”).

Example:

```markdown
- [ ] **SEC-012** [High] Ensure subscriber-only API routes validate business ownership — Done when every mutating subscriber endpoint checks resource ownership and returns 403 on mismatch (add test notes in docs/testing_strategy/).
```

### 4. Remediation plan

- **Phases**: e.g. Phase 0 **stop-the-bleeding** (Critical SEC), Phase 1 **high-risk SEC**, Phase 2 **PERF quick wins** (indexes, pagination, asset headers), Phase 3 **structural** (caching, rate limits, monitoring).
- Per phase: **goal**, **scope** (files/areas), **dependencies**, **verification** (manual steps, `php -l`, query explain, header check).
- Map **every Critical and High** ID to a phase or **explicit deferral** with rationale (e.g. needs WAF product decision).

## Project alignment

- Read [project_brain/CODING_AND_SECURITY.md](../../../project_brain/CODING_AND_SECURITY.md) and [project_brain/LAUNCH_FREEZE_LOG.md](../../../project_brain/LAUNCH_FREEZE_LOG.md) before recommending broad changes.
- Do not suggest new dependencies without justification (see CODING_AND_SECURITY “Dependencies”).
- Schema/index recommendations should name the **table and access pattern**; prefer aligning with `api/v1/migrations/` and documented schema.

## Anti-patterns

- Do not list CVEs or framework bugs without tying them to **this** codebase’s version and usage.
- Do not duplicate the full TODO list inside the plan; the plan **sequences and maps IDs** only.
- Do not claim “pentest clean” or “no vulnerabilities” from static review alone.

## Verification before calling the review “complete”

- Every **Critical** and **High** item appears in the gap TODO list with an ID.
- At least one **verification method** is stated for each phase (what to re-check after fixes).
