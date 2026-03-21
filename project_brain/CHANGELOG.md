# Changelog

## Unreleased

### API

- **`POST /api/v1/auth/login`** — unified login with `audience`: `subscriber` or `cp` (super_admin/co_admin). **`GET /api/v1/auth/me`**, **`POST /api/v1/auth/logout`**. **`Authorization: Bearer`** supported alongside `mci_api_token` cookie. Shared helper **`api_direct_auth_login()`**; **`/login/`** and **`/cp/login/`** use it (same behaviour as the JSON API). See [API_AUTHENTICATION.md](API_AUTHENTICATION.md).

### Configuration

- Project root `.env` support via `includes/mci_load_env.php`, loaded from `api/v1/lib/config.php` before reading `MCI_DB_*` and `MCI_JWT_SECRET`. Added `.env.example` and `.gitignore` entry for `.env`.

### Dev tooling

- Dev test users: appended at end of `001_create_core_tables.sql` (subscribers + super admins); `dev_seed_test_users.sql` is deprecated (pointer only). See [DEV_TEST_ACCOUNTS.md](DEV_TEST_ACCOUNTS.md).
- **Breaking:** all core DB tables use `mci_` prefix (e.g. `mci_users`, `mci_roles`). Re-import `001` on fresh DBs; existing DBs without prefix need a one-time rename/migration.
- `/cp/login/` — control panel sign-in page (API `POST /api/v1/cp/login`); `.htaccess` routes added.

### Documentation

- Added [FEATURE_DETAILS.md](FEATURE_DETAILS.md) (human-readable feature/screen specification) and [FEATURE_VALIDATION_CHECKLIST.md](FEATURE_VALIDATION_CHECKLIST.md) (QA validation).

### Schema / auth

- `api/v1/migrations/001_create_core_tables.sql` is the single source of truth for core schema.
- Added `roles` + `users.role_id` and `userprofiles`.
- Added `user_auth_providers` table (social login mapping).

### Code

- API v1 registration/login and co-admin management use `role_id` / `roles` joins.

### Docs

- `project_brain` simplified to canonical files; see [README.md](README.md).
