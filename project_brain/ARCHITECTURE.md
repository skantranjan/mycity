# Architecture

## Tech stack

**Frontend:** PHP + HTML (server-rendered), JS, jQuery, CSS.

**Backend:** PHP. API v1 under `api/v1/`.

**Database:** MySQL (InnoDB, utf8mb4). Schema: `api/v1/migrations/001_create_core_tables.sql`.

**Authentication:** Custom JWT in HTTP-only cookie (`mci_api_token`). Role checks via `api_require_auth([...])`.

**Hosting:** Hostinger shared server.

**AI:** Enabled/available; document each integration in `docs/feature_docs/`.

## System architecture (high level)

- Frontend pages at project root (PHP + HTML + JS)
- API v1 at `api/v1/` for JSON endpoints
- Persistence in MySQL via the single migration file above

## Service layers / responsibilities

| Area | Responsibility |
|------|----------------|
| `api/v1/index.php` | Routes requests; endpoint validation and JSON responses |
| `api/v1/lib/db.php` | PDO / DB access |
| `api/v1/lib/jwt.php` | JWT signing/verification |
| `api/v1/lib/auth_middleware.php` | Role enforcement from JWT claims |
| `api/v1/lib/auth_cookie.php` | Cookie read/write for JWT |
| `api/v1/lib/request.php` | JSON/body parsing and typed getters |
| `api/v1/lib/ip.php` | Client IP (best-effort) |
| `api/v1/lib/response.php` | JSON response helpers |
| `api/v1/migrations/001_create_core_tables.sql` | Canonical DB schema for API v1 |

## Data flow (typical)

1. User submits a form (register, login, CP actions).
2. Frontend calls `api/v1/*` (often JSON).
3. API uses `api_request_data()` for input.
4. API reads/writes MySQL with PDO.
5. API responds with JSON via `api/v1/lib/response.php`.

## Authorization

- JWT cookie holds a short-lived token (`mci_api_token`).
- `api_require_auth([...])` enforces allowed roles per endpoint.

## State

- **Frontend:** PHP sessions (`includes/mci_session.php`); wizards may use JS/localStorage for drafts.
- **API auth:** JWT in HTTP-only cookie; claims include `sub` (user id) and `role`.
- **Server persistent state:** MySQL via `001_create_core_tables.sql`.

## API integrations (current)

- **API v1:** `api/v1/index.php` + `api/v1/lib/*` + schema in `001_create_core_tables.sql`.
- **Auth:** `jwt.php`, `auth_cookie.php`, `auth_middleware.php`.

**Social login (planned):** `user_auth_providers` table exists; next steps: provider endpoints, map to local user, issue JWT like credential login.

## Folder structure

- **Root:** `api/` (backend), `register/`, `login/`, `submit-business-listing/`, etc. (pages), `includes/` (sessions, error handling).
- **API v1:** `api/v1/index.php`, `api/v1/lib/`, `api/v1/migrations/001_create_core_tables.sql`.
- **This brain:** `project_brain/` — single place for project understanding (see `README.md`).

## Design decisions (see also [ROADMAP_AND_ISSUES.md](ROADMAP_AND_ISSUES.md))

- **Single SQL source of truth:** full schema in `001_create_core_tables.sql` to avoid drift vs split migrations.
- **Role authorization:** endpoint-level `api_require_auth([...])` rather than global role middleware only.
