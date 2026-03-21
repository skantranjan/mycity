# Design decisions, known issues, roadmap

## Design decisions

- **Single source of truth for schema:** full DB schema in `api/v1/migrations/001_create_core_tables.sql` — avoids drift vs multiple migration files.
- **Role authorization:** endpoint-level `api_require_auth([...])` using JWT claims — simple and explicit per route.

## Known issues

- Social login not implemented end-to-end; `user_auth_providers` exists but API wiring is pending.
- `is_logged_in` is best-effort and may not reflect multi-device reality (JWT expiry, cookie rotation).

## Future roadmap

- Social login: provider endpoints/callbacks, map `user_auth_providers` to local user, issue JWT like password login.
- Email / phone verification flows (`email_verified_at`, `phone_verified_at`).
- Password change endpoint (`password_hash`, `password_changed_at`).
- Expand automated tests if/when a test runner is added.
