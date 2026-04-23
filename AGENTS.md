# MyCityInfo.com Engineering Standards (AGENTS)

Version: 1.0  
Scope: Entire `mycity` repository and all production infrastructure supporting MyCityInfo.com  
Applies to: Employees, contractors, vendors, and all AI coding assistants

This is the single authoritative standards file for the project.

---

## Required Before Any Change

- Confirm the proposed change preserves production stability, security, and backward compatibility.
- Confirm route/page structure follows project policy (`<segment>/index.php` for routable pages).
- Confirm no secrets (`.env`, keys, tokens, credentials) are introduced or exposed.
- Confirm migration/data-impact risk and rollback plan for schema-affecting changes.

---

## PR Compliance Checklist (Required)

Every PR must include:

1. Scope and rationale
2. Security impact statement (SQLi/XSS/CSRF/auth)
3. Data integrity and migration impact
4. Testing evidence (commands/manual steps + outcomes)
5. Backward compatibility notes (URLs/API/schema)
6. SEO/performance impact notes (if public pages touched)
7. Rollback plan

PRs missing checklist evidence are not merge-ready.

---

## 1) Project Overview and Engineering Principles

MyCityInfo.com is a production PHP + MySQL local business directory platform with server-rendered pages, an `/api/v1/` JSON API, public discovery pages, subscriber workflows, and control-panel operations.

### Engineering principles (mandatory)

1. **Production safety first**: no change may risk data integrity, auth integrity, or public uptime.
2. **Secure by default**: all inputs are untrusted until validated.
3. **Backward compatibility by default**: existing URLs, schema semantics, and API behavior must continue working unless a breaking change is explicitly approved.
4. **Single source of truth**: route ownership, schema ownership, and config ownership must remain centralized.
5. **Observable behavior**: failures must be diagnosable via structured logs and safe error handling.
6. **Deterministic deployments**: every release must have validation and rollback readiness.

---

## 2) Mandatory Coding Standards

### 2.1 Naming conventions

- Route entrypoints follow folder-per-route format: `feature/index.php`.
- Project helpers use `mci_` or `api_` prefixes.
- Database tables use `mci_` prefix only.
- Names must be descriptive; ambiguous identifiers are prohibited.

### 2.2 Folder structure discipline

- Routable pages must be in `<segment>/index.php` (including `subscriber/*` and `cp/*`).
- Root `index.php` is home page only.
- API behavior belongs under `api/v1/` (router + `lib/*`), not page controllers.
- Shared rendering belongs in `views/components` or `views/partials`.
- Shared bootstrap/cross-cutting code belongs in `includes/`.

### 2.3 Modular architecture and separation of concerns

- Keep UI orchestration in page controllers; put reusable business/data logic in shared helpers/services.
- Use centralized DB helpers (`api/v1/lib/db.php`) and service functions; no ad-hoc connection logic.
- Avoid mixing heavy SQL/business logic and large HTML blocks in the same function when extraction is possible.

### 2.4 DRY, SOLID, and reuse

- Duplicate logic across public/subscriber/CP paths is prohibited.
- If similar logic appears in two or more places, extract shared code before merge.
- New services/functions should have single responsibility and explicit boundaries.

### 2.5 Readability and maintainability

- `declare(strict_types=1);` is required for all new PHP files.
- Save PHP as UTF-8 without BOM.
- Prefer guard clauses and predictable control flow.
- Avoid magic literals in business rules; use named constants/config.

### 2.6 Error handling and logging

- Never expose secrets, stack traces, SQL text, or internals in end-user responses.
- Route unhandled failures through central handlers (for example `includes/mci_error_handler.php`).
- Log operationally relevant failures with enough context to debug.

---

## 3) PHP Best Practices

### 3.1 Secure coding

- Treat `$_GET`, `$_POST`, `$_REQUEST`, headers, cookies, and uploads as untrusted.
- Apply context-appropriate output encoding (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for HTML contexts).
- Never directly render unsanitized user data in HTML/JS/URL contexts.

### 3.2 Database safety

- Prepared statements are mandatory for dynamic SQL.
- `PDO::ATTR_EMULATE_PREPARES` must remain disabled.
- Raw interpolated SQL is forbidden.

### 3.3 Validation and sanitization

- Validate type, shape, ranges, and allowed values before processing/persisting.
- Sanitize for output context; do not destroy canonical source data.
- Reject invalid input with explicit safe messages.

### 3.4 Session and auth security

- Maintain secure session cookie options (`httponly`, `samesite`, and secure in HTTPS contexts).
- CSRF protection is required for state-changing operations (reuse `includes/mci_csrf.php` patterns).
- Sensitive auth endpoints must be abuse-protected (rate limits/lockout behaviors where applicable).

### 3.5 Authentication and authorization

- Authentication does not imply authorization.
- Role checks must be server-side and explicit for all CP/admin operations.
- Never rely on client-supplied role/state flags.

### 3.6 Environment and configuration

- Secrets/config must come from env vars or `.env`; never hardcode secrets.
- `.env` must never be committed.
- New env requirements must be documented in `.env.example`.

### 3.7 Dependencies and compatibility

- New dependencies require security and maintenance justification.
- Preserve PHP baseline compatibility (`>=8.1` currently).
- Preserve route/API backward compatibility unless explicitly approved otherwise.

### 3.8 Exception handling

- Handle expected failure branches explicitly (DB/network/provider failures).
- Do not silently swallow exceptions; log or handle with explicit fallback.

---

## 4) MySQL Best Practices

### 4.1 Schema and migrations

- Canonical full schema: `database/schema/*.sql`.
- Incremental changes: `api/v1/migrations/[0-9]*.sql`.
- Apply migrations via `php migrate.php`; no undocumented ad-hoc production schema edits.

### 4.2 Indexing and query optimization

- Index high-cardinality filters, join keys, and sort-critical fields.
- Review query plans (`EXPLAIN`) for new/modified heavy queries.
- `SELECT *` is prohibited in performance-critical paths.

### 4.3 Normalization and relational discipline

- Use normalized structures (minimum 3NF) unless denormalization is approved and documented.
- Foreign keys are mandatory unless exception is explicitly documented.
- FK actions must match lifecycle intent.

### 4.4 Transactions and consistency

- Multi-table write workflows must use transactions.
- Partial write outcomes are unacceptable in critical flows.

### 4.5 Backup and destructive change controls

- Data-loss-risk migrations require tested backup and rollback runbooks before deploy.
- Destructive SQL (`DROP`, destructive `ALTER`, bulk `DELETE/UPDATE`) needs approval and dry-run evidence.
- No destructive production change without staging validation.

### 4.6 Audit fields

- New mutable entities must include lifecycle metadata (`created_at`, `updated_at`, plus delete/status metadata where needed).
- Prefer soft delete for user-generated and compliance-relevant records.

---

## 5) Frontend Standards

### 5.1 Semantic HTML and accessibility

- Use semantic landmarks and heading hierarchy.
- Ensure keyboard accessibility and visible focus for interactive controls.
- Require meaningful `alt` text (or empty `alt` for decorative images).

### 5.2 Responsive behavior

- Build mobile-first; verify at common mobile and desktop breakpoints.
- No unintended horizontal overflow at 320px widths.

### 5.3 CSS maintainability

- Reuse shared styling systems (`assets/css/theme.css`, `assets/css/app-areas.css`) before adding one-off styles.
- Avoid excessive inline styles.

### 5.4 JavaScript discipline

- Keep core UX progressively enhanced where possible.
- Avoid global namespace pollution and tightly coupled scripts.
- Use jQuery only when aligned with surrounding existing patterns.

### 5.5 Browser support and page speed

- Provide fallback handling for unsupported browser APIs.
- Minimize render-blocking assets; defer/async non-critical JS.

---

## 6) Security Standards (OWASP-Aligned)

### 6.1 Injection and request protection

- SQL injection prevention: prepared statements only.
- XSS prevention: context-aware output encoding.
- CSRF prevention: token validation on state-changing operations.

### 6.2 Authentication hardening

- Passwords must use secure hashing (`password_hash`/`password_verify`).
- Brute-force mitigation required for auth-sensitive flows.
- Password reset must be time-bound and single-use.

### 6.3 Authorization and admin controls

- All CP/admin actions require strict server-side authorization checks.
- Hidden links/UI are not authorization.
- Enforce least privilege in API responses and operations.

### 6.4 Secrets and transport security

- HTTPS-only in production.
- Preserve baseline security headers in API responses (see `api/v1/lib/http_security.php`).
- Never log or expose secrets (JWT, SMTP, OAuth, DB creds).

### 6.5 Upload and API abuse protection

- Validate upload type/size/extension and block executable upload abuse.
- Use rate limiting/throttling plus auth checks for sensitive endpoints.

### 6.6 Production access restrictions

- Restrict production DB/SSH/admin access to least privilege.
- Ensure access grants are auditable and revocable.

---

## 7) Performance Rules

### 7.1 Database performance

- Eliminate N+1 patterns on listings/categories/tags pages.
- Cache expensive read models where consistency permits.
- Enforce pagination for large datasets.

### 7.2 Asset/media optimization

- Optimize images during/after upload; use project batch tools where needed.
- Preserve stable URLs while improving asset weight.
- Lazy-load non-critical media.

### 7.3 Server and request discipline

- Keep request handlers focused and fast.
- Avoid long blocking operations in request/response paths where asynchronous alternatives exist.

### 7.4 Monitoring expectations

- Track latency, error rates, and slow-query indicators as release quality signals.
- Block release on unexplained regressions in critical user journeys.

---

## 8) SEO Standards

### 8.1 URL and crawlability

- Preserve clean pretty URLs and legacy redirects.
- Keep rewrite/router mappings in sync for new public routes.
- Maintain valid `robots.txt` + sitemap behavior across host/base-path environments.

### 8.2 Metadata and schema

- Every indexable page requires meaningful title/meta description.
- Reuse shared SEO helpers (`includes/mci_seo.php`) instead of scattered ad-hoc logic.
- Structured data must be valid JSON-LD and reflect real page content.

### 8.3 Canonical and duplicate prevention

- Use canonical URL discipline for query/filter variants.
- Prevent multiple indexable URLs representing the same content.

### 8.4 Internal linking

- Maintain crawlable links between businesses, categories, tags, and locations.
- Avoid orphaned indexable pages.

### 8.5 SEO and performance

- Treat major public-page performance regressions as SEO blockers.

---

## 9) Testing Standards

### 9.1 Coverage and evidence expectations

- Every change requires verification evidence (automated output and/or documented manual checks).
- High-risk areas (auth, migrations, security, listing lifecycle, CP moderation) require explicit regression validation.

### 9.2 Staging validation

- Validate changed flows in staging before production deployment.
- Keep manual checklists updated in `docs/testing_strategy/`.

### 9.3 Bug fix discipline

- Every bug fix must include:
  - Root cause summary
  - Repro steps
  - Verification steps confirming fix and regression safety

### 9.4 Release readiness

- Pre-release checks must include migration success, critical path smoke tests, error-log review, and rollback readiness.

---

## 10) Cross-Browser Compatibility Rules

- Required support baseline: latest stable Chrome, Safari, Firefox, Edge, and modern Android/iOS browsers.
- Validate new frontend behavior across Chromium, Gecko, and WebKit engines.
- Add feature-detection/fallback where browser support is not universal.
- Do not ship regressions in core flows (search, listing, login, dashboard, submit) on required browsers.

---

## 11) Commenting and Documentation Rules

### 11.1 Where comments are required

- Complex business/security logic, migration caveats, non-obvious tradeoffs, and workaround rationale.

### 11.2 Where comments should be avoided

- Obvious line-by-line commentary and stale comments that diverge from behavior.

### 11.3 Documentation requirements

- Update docs when changing routes, API behavior, DB schema, deployment process, or operational workflows.
- DB changes require both migration and documentation update.

---

## 12) Git and Deployment Rules

### 12.1 Branch and PR discipline

- No direct commits to protected production branches.
- All non-trivial changes require PR review with risk/test notes.

### 12.2 Review requirements

- Security-sensitive changes require explicit security review.
- Schema changes require DB-aware review.
- Routing/auth changes require backward-compatibility impact review.

### 12.3 Deployment and rollback

- Production deployment requires approved PR, migration review, and rollback plan.
- Hotfixes must be minimal, validated, and back-merged.

### 12.4 Production deployment controls

- Deploy from version-controlled changes only.
- No undocumented manual production fixes for persistent behavior changes.

---

## 13) Monitoring and Maintenance Standards

- Review application/system error logs regularly and triage recurring issues.
- Monitor uptime, latency, DB health, and storage growth.
- Monitor cron/scheduled jobs and alerting signals.
- Enforce scheduled backups and periodic restore drills.

---

## 14) AI Agent Rules

All AI coding assistants must:

1. Never bypass security controls.
2. Never generate unsafe SQL.
3. Never remove validations without safe replacement.
4. Never break backward compatibility without explicit flagging.
5. Always document major architecture/schema/auth changes.
6. Always preserve production stability.
7. Always prefer maintainable implementations over shortcuts.
8. Always follow project route architecture and API separation.
9. Never expose secrets.
10. Never propose destructive DB actions without rollback safety.

---

## 15) Final Non-Negotiable Rules (MUST NEVER)

1. **MUST NEVER** commit `.env`, secrets, tokens, credentials, or private dumps.
2. **MUST NEVER** ship known SQLi/XSS/CSRF/auth bypass risks.
3. **MUST NEVER** run destructive production SQL without approval, backups, and rollback readiness.
4. **MUST NEVER** weaken or remove authorization checks for CP/admin operations.
5. **MUST NEVER** expose stack traces/SQL errors to end users in production.
6. **MUST NEVER** bypass migration discipline with undocumented schema edits.
7. **MUST NEVER** break canonical URLs/redirect paths without an approved migration strategy.
8. **MUST NEVER** deploy without critical-flow validation and operational checks.
9. **MUST NEVER** merge high-risk changes without review.
10. **MUST NEVER** prioritize speed over security, integrity, and uptime.

---

## Enforcement

Non-compliant changes can be blocked, reverted, escalated, or access-restricted based on severity.
# AGENTS — Quick Compliance Guide

Primary policy document: `agents.md`  
Status: Mandatory for all contributors and AI agents

If any content in this file appears to conflict with `agents.md`, the stricter rule applies.

---

## Required Before Any Change

- Read `agents.md` and confirm the change scope is compliant.
- Confirm route/page structure follows project routing policy (`<segment>/index.php` for routable pages).
- Confirm no secret, credential, token, or `.env` content is touched or exposed.
- Confirm backward compatibility impact (URLs, API behavior, schema, auth flows) is evaluated.

---

## PR Compliance Checklist (Required)

Every PR must include all items below:

1. **Scope**: concise summary of what changed and why.
2. **Security**: explicit confirmation that SQLi/XSS/CSRF/auth controls were preserved or improved.
3. **Data Integrity**: confirmation that migrations and write-path changes are safe and reversible.
4. **Testing Evidence**: commands and/or manual validation steps executed with outcomes.
5. **Backward Compatibility**: note whether legacy URLs/API/schema behavior is affected.
6. **SEO/Performance Impact**: note if public pages, metadata, routing, caching, or query behavior changed.
7. **Rollback Plan**: clear rollback steps for production-facing changes.

PRs missing these items are not merge-ready.

---

## AI Agent Guardrails (Non-Optional)

AI coding assistants must:

- Never generate interpolated SQL with user input.
- Never remove validation/sanitization/authorization checks without approved replacement.
- Never make destructive DB changes without documented rollback readiness.
- Never expose stack traces, secrets, or sensitive internals to users.
- Always describe major architectural, migration, or auth changes in PR notes.
- Always prefer maintainable, testable solutions over shortcuts.

---

## Immediate Reject Conditions

Reject the change immediately if any of the following is true:

- Includes secrets, credentials, private keys, or `.env` values.
- Introduces unsafe SQL patterns or bypasses auth/role checks.
- Breaks canonical routing/redirect behavior without approved migration plan.
- Applies unreviewed destructive SQL to production paths.
- Lacks verification evidence for high-risk changes.

---

## Enforcement

This file is a fast gate, not a substitute for policy details.  
Full engineering standards remain in `agents.md` and are enforceable for all project work.
# MyCityInfo.com Engineering Standards (AGENTS)

Version: 1.0  
Scope: Entire `mycity` repository and all production infrastructure supporting MyCityInfo.com  
Applies to: Employees, contractors, vendors, and all AI coding assistants

---

## 1) Project Overview and Engineering Principles

MyCityInfo.com is a production PHP + MySQL local business directory platform with server-rendered pages, an `/api/v1/` JSON API, public discovery pages, subscriber flows, and control-panel operations.

### Engineering principles (mandatory)

1. **Production safety first**: no change may risk data integrity, auth integrity, or public uptime.
2. **Secure by default**: all code paths must assume hostile input.
3. **Backward compatibility by default**: existing URLs, DB semantics, and integration behavior must continue working unless a planned breaking change is approved.
4. **Single source of truth**: route ownership, schema ownership, and config ownership must remain centralized (defined below).
5. **Observable behavior**: errors must be diagnosable via structured logging and controlled failure paths.
6. **Deterministic deployments**: every release must be reversible with a documented rollback plan.

---

## 2) Mandatory Coding Standards

### 2.1 Naming conventions

- PHP files and route entrypoints use folder-per-route format, e.g. `feature/index.php`.
- Helper functions use `mci_` or `api_` prefixes for project-level clarity.
- Database tables use `mci_` prefix only.
- SQL identifiers must be explicit and descriptive; avoid ambiguous names like `data`, `temp`, `value`.

### 2.2 Folder structure discipline

- Routable pages must live in `<segment>/index.php` (including `subscriber/*` and `cp/*`).
- Root `index.php` is home only.
- API logic belongs under `api/v1/` (router + `lib/*` services), not in page controllers.
- Shared rendering components belong under `views/components` and `views/partials`; avoid page-local duplication.
- Shared bootstrapping and cross-cutting concerns belong in `includes/`.

### 2.3 Architecture and separation of concerns

- UI/page orchestration in page controllers; reusable business/data logic in shared includes/libs.
- DB access must be encapsulated through common DB helpers (`api/v1/lib/db.php`) and service functions; no ad-hoc connections.
- No mixing HTML markup generation with raw SQL blocks in the same function unless there is no viable extraction.

### 2.4 DRY, SOLID, reusability

- Duplicate logic across public, subscriber, and CP areas is prohibited.
- If similar logic appears in 2+ files, extract a shared function/module before merging.
- New services must have single responsibility and narrow interfaces.

### 2.5 Readability and maintainability

- `declare(strict_types=1);` is required for all new PHP files.
- UTF-8 without BOM is mandatory.
- Use guard clauses for invalid states; avoid deep nesting.
- Magic numbers/strings in business rules are prohibited; define constants or config values.

### 2.6 Error handling and logging

- Errors must fail safely, never expose secrets, stack traces, or SQL details to users.
- Unhandled exceptions/fatals must flow through centralized error handling (see `includes/mci_error_handler.php`).
- Operationally relevant failures must be recorded in `mci_error_log` or `error_log` with enough context to reproduce.

---

## 3) PHP Best Practices (Mandatory)

### 3.1 Secure coding requirements

- Treat all `$_GET`, `$_POST`, `$_REQUEST`, headers, cookies, and uploaded files as untrusted.
- Escape all output for HTML contexts (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- Never directly echo unsanitized user input into HTML, JS, URL, or attributes.

### 3.2 Database access requirements

- Prepared statements are mandatory for all SQL with dynamic values.
- `PDO::ATTR_EMULATE_PREPARES` must remain disabled.
- Raw interpolated SQL is forbidden.

### 3.3 Validation and sanitization

- Validate shape/type/allowed ranges before persistence.
- Sanitize only for target context; do not mutate canonical data blindly.
- Reject invalid input with explicit user-safe messages and machine-usable API responses.

### 3.4 Session and auth security

- Session initialization must preserve secure options (`httponly`, `samesite` at minimum).
- CSRF protection is required on all state-changing form/API operations (reuse `includes/mci_csrf.php` patterns).
- Login/logout/account mutation flows require rate limiting and anti-abuse checks.

### 3.5 Authentication and authorization

- Authentication and authorization are separate checks; authenticated does not imply authorized.
- Role checks must be explicit for CP-only operations.
- Never trust client-side role flags.

### 3.6 Environment/config management

- Secrets and environment-specific values must come from environment variables or `.env` (never hardcoded).
- `.env` must never be committed.
- New required env vars must be documented in `.env.example` with clear comments.

### 3.7 Dependencies and compatibility

- New dependency introduction requires justification (security, maintenance, and operational footprint).
- Keep PHP compatibility with the project baseline (`>=8.1` currently).
- Backward compatibility for existing endpoints/routes must be preserved unless approved as a breaking change.

### 3.8 Exception/fatal prevention

- Handle expected failure branches explicitly (DB down, remote provider timeout, malformed payload).
- Never swallow exceptions silently unless fallback behavior is explicit and logged.

---

## 4) MySQL Best Practices (Mandatory)

### 4.1 Schema ownership and migration discipline

- Canonical full schema is maintained in `database/schema/*.sql`.
- Incremental production-safe changes are delivered via `api/v1/migrations/[0-9]*.sql`.
- Apply migrations via `php migrate.php`; do not perform ad-hoc production schema edits.

### 4.2 Indexing and query optimization

- Every high-cardinality filter, join FK, and sort-critical column must be indexed.
- New queries must be reviewed for index usage (`EXPLAIN`) before production release.
- `SELECT *` is prohibited in performance-sensitive paths.

### 4.3 Normalization and data integrity

- At minimum 3NF for transactional entities unless denormalization is approved for performance.
- Foreign keys are mandatory for relational integrity unless there is a documented exception.
- FK actions (`CASCADE`, `RESTRICT`, `SET NULL`) must match business lifecycle semantics.

### 4.4 Transactions and consistency

- Multi-statement write operations spanning related tables must use transactions.
- Partial writes are unacceptable for user/account/payment-like flows.

### 4.5 Backup, rollback, destructive change rules

- Any migration with data-loss risk requires tested backup and rollback runbook before deployment.
- `DROP`, destructive `ALTER`, and mass `UPDATE/DELETE` in production require explicit approval and dry-run evidence.
- No destructive production SQL changes without staging validation on representative data.

### 4.6 Audit fields and lifecycle

- New mutable entities must include lifecycle metadata (`created_at`, `updated_at`, and where relevant `deleted_at`/actor fields).
- Soft delete is preferred for user-generated or compliance-relevant records.

---

## 5) Frontend Standards (Mandatory)

### 5.1 Semantic HTML and accessibility

- Use semantic landmarks (`header`, `main`, `nav`, `footer`) and meaningful headings.
- All interactive controls require keyboard operability and visible focus states.
- Images require useful `alt` text (or empty alt when decorative).

### 5.2 Responsive design

- All new UI must be mobile-first and verified at common mobile and desktop breakpoints.
- No horizontal overflow at 320px width except intentionally scrollable data tables.

### 5.3 CSS maintainability

- Reuse design tokens and shared styles (`assets/css/theme.css`, `assets/css/app-areas.css`) before adding new ad-hoc styles.
- Avoid one-off inline styles unless absolutely necessary for dynamic runtime values.

### 5.4 JavaScript discipline

- Keep behavior progressively enhanced; core content/actions must remain usable when JS is unavailable where feasible.
- Avoid global namespace pollution; isolate scripts per feature.
- jQuery usage is allowed only when already aligned with surrounding code; no unnecessary mixed paradigms.

### 5.5 Browser compatibility and performance

- Implement graceful fallbacks for unsupported browser APIs.
- Minimize render-blocking assets; defer or async non-critical JS.

---

## 6) Security Standards (OWASP-Aligned, Mandatory)

### 6.1 Input/output and injection defenses

- SQLi prevention: prepared statements only.
- XSS prevention: context-appropriate encoding on every output sink.
- CSRF prevention: anti-CSRF token validation on all state-changing operations.

### 6.2 Auth hardening

- Passwords must be hashed with modern password APIs (`password_hash` / `password_verify`).
- Enforce brute-force mitigations (rate limits, lockouts where applicable).
- Reset-password flows must be time-bound, single-use, and auditable.

### 6.3 Access control

- Admin/CP routes and actions require explicit server-side role checks.
- “Hidden UI” is not access control.
- Sensitive API responses must enforce least-privilege data exposure.

### 6.4 Transport, headers, and secrets

- HTTPS-only in production.
- Maintain security headers for API/browser responses (see `api/v1/lib/http_security.php` baseline).
- Secrets (JWT, SMTP, OAuth credentials) must never appear in logs, commits, screenshots, or client-visible payloads.

### 6.5 File upload/API security

- Validate MIME type, extension, and size before accepting uploads.
- Store uploads outside executable paths or ensure strict execution prevention.
- Apply anti-abuse controls to API endpoints (throttling/rate limit + auth checks).

### 6.6 Production access restrictions

- Restrict production DB, SSH, and admin console access by principle of least privilege.
- Use auditable credentials and revocation workflows for vendors and temporary contributors.

---

## 7) Performance Rules (Mandatory)

### 7.1 Database performance

- Eliminate N+1 query patterns on listing/category/tag pages.
- Cache expensive read models where correctness allows.
- Use pagination for large result sets; never unbounded queries in request paths.

### 7.2 Asset and media optimization

- Optimize images before/at upload and for legacy assets via `scripts/optimize_storage_images.py` where needed.
- Serve modern formats where practical and preserve URL stability for existing content.
- Lazy-load non-critical media below the fold.

### 7.3 App/server discipline

- Keep request handlers lean; long-running operations must be asynchronous/offline where possible.
- Default timeouts and retry behavior must avoid cascading failures.

### 7.4 Monitoring expectations

- Track API latency, error rates, and slow DB query signals as release criteria.
- Any performance regression in critical flows (home, business listing/detail, login, CP core screens) blocks release until assessed.

---

## 8) SEO Standards (Mandatory)

### 8.1 URL and crawlability discipline

- Use clean, canonical pretty URLs (`/segment/` and slug paths) and preserve legacy redirects.
- New public routes must be reflected in rewrite/router behavior and canonical linking.
- `robots.txt` and sitemap behavior must remain correct for host + base-path environments.

### 8.2 Metadata and schema

- Every indexable page requires meaningful title and meta description.
- Use and extend project SEO helpers (`includes/mci_seo.php`) rather than ad-hoc scattered meta generation.
- Structured data must be valid JSON-LD and reflect page truth.

### 8.3 Duplicate content prevention

- Canonical URLs are required for pages with filter/query permutations.
- Avoid generating multiple indexable URLs for the same content without canonical normalization.

### 8.4 Internal linking and content integrity

- Public pages must keep crawlable internal link paths between listings, categories, tags, and locations.
- Orphaned indexable pages are not allowed.

### 8.5 Performance and SEO

- Core public pages must meet reasonable web performance thresholds; severe performance regressions are treated as SEO regressions.

---

## 9) Testing Standards (Mandatory)

### 9.1 Coverage expectations

- Every change requires at least one verification artifact: automated check, API test script, or documented manual test steps.
- High-risk changes (auth, migrations, security, listing lifecycle, CP moderation) require explicit regression checklist evidence.

### 9.2 Regression and staging validation

- Validate modified flows in staging before production.
- Use/update manual validation checklists under `docs/testing_strategy/` for UI and operational regressions.

### 9.3 Bug fix discipline

- Every bug fix must include:
  - Root cause summary
  - Repro steps
  - Verification steps proving the fix and no obvious regressions

### 9.4 UAT and release checklist

- Pre-release must confirm:
  - Migrations applied successfully
  - Critical paths smoke-tested
  - Error logs reviewed for new severe entries
  - Rollback plan documented

---

## 10) Cross-Browser Compatibility Rules (Mandatory)

- Minimum required support: latest stable Chrome, Safari, Firefox, Edge, plus modern Android/iOS mobile browsers.
- For new frontend features, verify behavior on at least one Chromium browser, one Gecko browser, and one WebKit browser.
- If using unsupported APIs, provide functional fallback or feature-detection guard.
- Do not ship a change that breaks primary user journeys (search, listing view, submit, login, dashboard) in any required browser.

---

## 11) Commenting and Documentation Rules

### 11.1 Where comments are required

- Non-obvious business rules, security decisions, migration caveats, and workaround rationale.
- Public helper functions with side effects or unusual constraints.
- Deployment-sensitive scripts and runbooks.

### 11.2 Where comments should be avoided

- Trivial line-by-line narration of obvious code.
- Outdated comments that diverge from behavior.

### 11.3 Documentation requirements

- New/changed route behavior: update relevant docs/readmes.
- DB changes: include migration + documentation update (`database/README.md` or related schema notes).
- API behavior changes: document request/response contract changes in repo docs.
- Operational changes: include runbook notes for deploy/rollback implications.

---

## 12) Git and Deployment Rules (Mandatory)

### 12.1 Branch and PR discipline

- No direct pushes to protected production branch.
- All non-trivial changes go through PR review with clear risk notes.
- PRs must include test/validation evidence and migration notes when applicable.

### 12.2 Review expectations

- Security-sensitive changes require explicit security review.
- Schema/migration changes require DB-aware review.
- Routing/auth changes require reviewer confirmation of backward compatibility impact.

### 12.3 Deployment approvals and rollback

- Production deploys require:
  - Approved PR
  - Known rollback strategy
  - Migration impact review
- Hotfixes must be minimal, reviewed post-deploy, and back-merged into main development branch.

### 12.4 Production deployment rules

- Run migrations with controlled sequencing and verify success logs.
- Never perform risky manual prod edits not represented in version control.

---

## 13) Monitoring and Maintenance Standards (Mandatory)

- Review `mci_error_log` and server logs regularly; triage recurring errors with priority.
- Monitor uptime, latency, DB connection health, and storage growth trends.
- Validate scheduled jobs/cron tasks and alerting paths.
- Ensure backups are scheduled, tested, and restore drills are performed periodically.
- Maintain operational dashboards/runbooks for incident response and postmortems.

---

## 14) AI Agent Rules (Mandatory)

All AI coding assistants working on this repository must comply with these rules:

1. Never bypass security controls for convenience.
2. Never generate raw interpolated SQL with user input.
3. Never remove validation/sanitization without approved replacement.
4. Never break backward compatibility without explicit flagging and approval.
5. Always explain significant architectural or schema changes in PR notes/docs.
6. Always preserve production stability over velocity.
7. Always prioritize maintainability and readability over shortcuts.
8. Always follow existing route architecture (`<segment>/index.php`, pretty URLs, and API separation).
9. Never expose secrets in generated code, logs, docs, or test fixtures.
10. Never apply destructive DB operations without explicit safety checks and rollback planning.

---

## 15) Final Non-Negotiable Rules (MUST NEVER)

The following are strictly prohibited:

1. **MUST NEVER** commit `.env`, secrets, tokens, API keys, DB passwords, OAuth credentials, or private dumps.
2. **MUST NEVER** ship code with known SQL injection, XSS, CSRF, or auth bypass risks.
3. **MUST NEVER** use unreviewed/destructive production SQL (`DROP`, mass delete/update) without backup and rollback readiness.
4. **MUST NEVER** remove or weaken authorization checks for CP/admin actions.
5. **MUST NEVER** return raw exception traces or SQL errors to end users in production.
6. **MUST NEVER** bypass migration discipline by making undocumented production schema changes.
7. **MUST NEVER** break canonical URLs/redirects/SEO-critical routing without approved migration plan.
8. **MUST NEVER** deploy without validating critical user journeys and operational health checks.
9. **MUST NEVER** merge high-risk changes without peer review.
10. **MUST NEVER** prioritize speed over data integrity, security, or uptime.

---

## Enforcement

Non-compliant changes may be rejected, reverted, or escalated. Repeated or severe violations can result in removal of repository or production access.

This document is mandatory engineering policy for MyCityInfo.com and supersedes informal practices.
