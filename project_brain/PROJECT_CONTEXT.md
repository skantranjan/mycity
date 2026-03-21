# Project context (ground truth)

## Project purpose

My City Info is a PHP-based web application where people can discover local businesses, browse reviews/listings, and (optionally) submit or claim content.

## Business objective

Create a trusted local directory with moderated contributions and clear user responsibilities.

## Target users

- People looking for local businesses and reviews
- Local contributors who may submit business information
- Moderators/admins who manage categories/queues and approvals

## Product specification and validation

- [FEATURE_DETAILS.md](FEATURE_DETAILS.md) — what each major feature/screen is expected to do.
- [FEATURE_VALIDATION_CHECKLIST.md](FEATURE_VALIDATION_CHECKLIST.md) — checklist to confirm implementation matches expectations.

## Key capabilities (current)

- Server-rendered frontend (PHP/HTML + some JS UI helpers)
- API v1 with JWT cookie authentication
- Role-based access: `subscriber`, `super_admin`, `co_admin`
- Core moderation/queue workflows: category requests, anonymous business submissions

## Current development focus (high level)

- API v1 under `api/v1/` exposes JSON endpoints.
- Authentication via JWT cookie (`mci_api_token`) and role-based access control.
- MySQL schema: `api/v1/migrations/001_create_core_tables.sql` (single source of truth).
- Frontend: PHP/HTML with form posts and server-side rendering, plus JS for wizards/UX.

## Database (single source of truth)

- `api/v1/migrations/001_create_core_tables.sql`
  - `mci_users` / `mci_roles`
  - `mci_userprofiles`
  - `mci_categories` (`parent_id`, `slug`, SEO fields) / `mci_tags` (`slug`, SEO: `page_title`, `meta_keywords`, `meta_description`, optional `description`)
  - `mci_category_requests`
  - `mci_anon_business_submissions`
  - `mci_user_auth_providers` (social login mapping; schema created, API wiring pending)

## Auth (current)

- JWT cookie based auth for API calls.
- Roles enforced per endpoint (e.g. `super_admin`, `co_admin`, `subscriber`).

## AI integration

AI integration is planned/available; model/provider wiring per feature should be documented under `docs/feature_docs/` when added.

When adding AI features, document: prompts used, safety/guardrails, data flow and privacy handling.
