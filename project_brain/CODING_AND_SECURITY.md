# Coding rules and security

Tailored to: PHP frontend + PHP API v1 + MySQL.

## General engineering

- Prefer small, incremental changes.
- Keep functions small and purpose-driven.
- Clear naming consistent with existing code.
- Avoid magic strings; centralize repeated constants.

## Documentation-first (guardrails)

Before implementing, update when behavior or contracts change:

- [PROJECT_CONTEXT.md](PROJECT_CONTEXT.md)
- [ARCHITECTURE.md](ARCHITECTURE.md)
- This file (rules/security)
- `docs/feature_docs/<feature>.md` when a feature is touched

## Architecture boundaries

- Router + endpoint selection: `api/v1/index.php`.
- Shared logic: `api/v1/lib/`.
- No DB access from UI pages directly; call API or use existing patterns.
- Do not mix business logic with routing when it can live in `api/v1/lib/`.
- Schema changes: always update `api/v1/migrations/001_create_core_tables.sql` and matching API queries.

## PHP

- Use `declare(strict_types=1);` in entrypoints and libraries.
- Validate inputs: trim strings, validate emails, required fields, explicit booleans.
- Always prepared statements for SQL with user data.

## Database

- Single source of truth: `api/v1/migrations/001_create_core_tables.sql`.
- On schema change: update inserts/selects/joins (including `role_id` / `roles`).
- Add indexes for lookups and FK columns where appropriate.

## API design

- Consistent JSON response shape (existing helpers).
- HTTP status codes: `400` bad request, `401` unauthorized, `403` forbidden, `404` not found, `409` conflict, `500` server error.

## Security

- Validate all input server-side.
- Parameterized queries only; no string concatenation of user data into SQL.
- JWT/cookies: `httponly`; `secure` when HTTPS.
- Do not log passwords, tokens, or secrets.
- Escape output in HTML templates (XSS).

## Dependencies

- New dependencies only with justification; prefer minimal changes to the PHP stack.

## Testing

- If no automated test runner: add manual test notes under `docs/testing_strategy/`.
- Every new endpoint should have: success path, auth failure, invalid input.

## Pre-merge review checklist

**Correctness:** Schema matches API; FKs correct; role checks on endpoints.

**Security:** Input validated; SQL parameterized; cookies `httponly`/`secure` as appropriate; no secrets in logs.

**Documentation:** Relevant `docs/` updated; `001_create_core_tables.sql` updated when schema changes.

**Maintainability:** Small, reviewable changes; consistent naming.
