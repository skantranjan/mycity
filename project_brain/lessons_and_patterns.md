# Lessons and patterns (project memory)

## Recurring bug patterns

- SQL schema drift: API assumes columns that were removed or renamed.
- Role logic mismatch: code still references old `users.role` instead of `users.role_id` / `roles`.
- Schema updated in code but not in `api/v1/migrations/001_create_core_tables.sql`.
- Client IP: trusting unvalidated `X-Forwarded-For` without checks.

## Architecture patterns (this repo)

- Router: `api/v1/index.php` selects endpoints by path/method.
- Shared code: `api/v1/lib/*` (DB, JWT, auth middleware, request/response, UUID, IP).
- Roles: `api_require_auth([...])` per endpoint.

## Lessons learned

- Keep one SQL file as source of truth: `api/v1/migrations/001_create_core_tables.sql`.
- When changing auth/roles: update schema, all API SQL, and any frontend payloads.
- Centralize helpers once (IP, request parsing) under `api/v1/lib/`.

## Past failures (avoid repeating)

- Duplicate migration files caused schema drift.
- Endpoints referenced removed columns (`users.guid`, `users.role`).
- Split vs monolithic migration naming confused which file to edit.
