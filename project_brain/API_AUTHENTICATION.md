# API v1 — Authentication

Base URL: **`/api/v1`** (e.g. `http://localhost:8000/api/v1` when using `php -S`).

All auth responses are JSON. Successful login sets an HTTP-only cookie **`mci_api_token`** (JWT) and returns the same token in the JSON body for clients that prefer `Authorization: Bearer`.

---

## 1. Login (subscriber or control panel) — unified

**`POST /api/v1/auth/login`**

Body (JSON):

```json
{
  "email": "subscriber1.dev@mycityinfo.local",
  "password": "DevSubscriber123!",
  "audience": "subscriber"
}
```

- **`audience`**: `"subscriber"` (or `"sub"`) for public accounts, or **`"cp"`** for control panel (**super_admin** and **co_admin**). You can also send `"type"` instead of `"audience"` with the same values.

Success **`200`**:

```json
{
  "ok": true,
  "user": { "id": "…", "email": "…", "role": "subscriber" },
  "token": "<jwt>"
}
```

Errors: **`400`** (`email_and_password_required`, `audience_required`, `invalid_audience`), **`401`** (`invalid_credentials`), **`500`** (`server_config` if DB/JWT env missing).

---

## 2. Login — legacy paths (same logic)

| Method | Path | Use |
|--------|------|-----|
| `POST` | `/api/v1/subscriber/login` | Subscriber only (body: `email`, `password`) |
| `POST` | `/api/v1/cp/login` | Super admin / co-admin (body: `email`, `password`) |

---

## 3. Current user (JWT)

**`GET /api/v1/auth/me`**

Send the session cookie **`mci_api_token`**, or header:

```http
Authorization: Bearer <jwt>
```

Success **`200`**:

```json
{
  "ok": true,
  "user": { "id": "<uuid>", "role": "subscriber" }
}
```

**`401`** if missing/invalid/expired token.

---

## 4. Logout

**`POST /api/v1/auth/logout`**

Clears **`mci_api_token`** and sets `is_logged_in = 0` on the user when a valid token is present. Cookie and/or `Authorization: Bearer` are accepted.

Also: `POST /api/v1/subscriber/logout`, `POST /api/v1/cp/logout` (role-checked).

---

## 5. cURL examples

**Subscriber login**

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"subscriber1.dev@mycityinfo.local\",\"password\":\"DevSubscriber123!\",\"audience\":\"subscriber\"}" \
  -c cookies.txt
```

**Super admin login**

```bash
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"superadmin.dev@mycityinfo.local\",\"password\":\"DevSuperAdmin123!\",\"audience\":\"cp\"}" \
  -c cookies.txt
```

**Who am I**

```bash
curl -s http://localhost:8000/api/v1/auth/me -b cookies.txt
```

---

## 6. Web UI (PHP pages)

These screens call the **same** auth code as the JSON API (via `api_direct_auth_login()` in PHP), not a separate HTTP request:

| Page | Equivalent API |
|------|------------------|
| **`/login/`** | `POST /api/v1/auth/login` with `"audience": "subscriber"` |
| **`/cp/login/`** | `POST /api/v1/auth/login` with `"audience": "cp"` |

---

## 7. Environment

- **`MCI_DB_*`**, **`MCI_JWT_SECRET`** in project root **`.env`** (see `SETUP_AND_DEPLOY.md`).
- Dev accounts: `DEV_TEST_ACCOUNTS.md`.
- Optional flags (see `.env.example`):
  - **`MCI_DEBUG_PASSWORD_RESET=1`** — forgot-password UI shows a reset URL + raw token (dev only; no email transport yet).
  - **`MCI_ALLOW_DEV_SOCIAL_LINK=1`** — profile pages expose a manual “link provider” form (dev only; production should use OAuth).

---

## 8. Password reset (JSON API)

| Method | Path | Body (JSON) | Notes |
|--------|------|-------------|--------|
| `POST` | `/api/v1/auth/forgot-password` | `{ "email": "…" }` | Always **`200`** with a generic message (no email enumeration). |
| `POST` | `/api/v1/auth/reset-password` | `{ "token": "…", "password": "…" }` | **`400`** if token invalid/expired (`invalid_or_expired_token`). |

Web UI: **`/forgot-password/`**, **`/reset-password/?token=…`** (same backend as `mci_account_*` helpers).

---

## 9. Profile & password (authenticated)

Requires JWT (**`mci_api_token`** cookie or `Authorization: Bearer`).

**Subscriber**

| Method | Path | Notes |
|--------|------|--------|
| `GET` / `POST` | `/api/v1/subscriber/profile` | `GET` returns `user`, `profile`, `auth_providers`. `POST` JSON patch: `display_name`, `phone`, `first_name`, `last_name`, `profile_image`, `gender`, `date_of_birth`, `timezone`. |
| `POST` | `/api/v1/subscriber/change-password` | `{ "current_password", "new_password" }` |
| `GET` | `/api/v1/subscriber/auth-providers` | Linked rows from `mci_user_auth_providers`. |
| `DELETE` | `/api/v1/subscriber/auth-providers/{provider}` | `google`, `facebook`, `apple`, `linkedin`. |
| `POST` | `/api/v1/subscriber/auth-providers/unlink` | Body `{ "provider": "google" }` (form-friendly). |
| `POST` | `/api/v1/subscriber/auth-providers/link` | Dev only if **`MCI_ALLOW_DEV_SOCIAL_LINK`**: `{ "provider", "provider_user_id", "provider_email?" }`. |

**Control panel** — same shapes under **`/api/v1/cp/...`** (`profile`, `change-password`, `auth-providers`, …) for roles **`super_admin`** and **`co_admin`**.

Web UI: **`/subscriber/profile/`**, **`/subscriber/change-password/`**, **`/cp/profile/`**, **`/cp/change-password/`**.

---

## 10. Super-admin user & taxonomy APIs

All require JWT; **`super_admin`** only unless noted.

### Users (`mci_users`)

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/api/v1/cp/users` | Query: `page`, `per_page`, `q` (search), `role`, `include_deleted` (`1`/`true`). Lists users with `role`, `status`, `deleted_at`, etc. |
| `POST` | `/api/v1/cp/users` | `action`: **`create`** — `email`, `password`, `role` (`subscriber` \| `co_admin` \| `super_admin`), `display_name`, `phone`, `status`. **`update`** — `id` + optional fields (email not editable via this endpoint if omitted; UI locks email on edit). **`delete`** — `id` soft-deletes (`deleted_at`, `status=deleted`). Cannot delete self or last **super_admin**. |

Web UI: **`/cp/users/`** (co-admins are redirected to dashboard).

### Co-admins

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/api/v1/cp/co-admins` | Rows with role `co_admin`. |
| `POST` | `/api/v1/cp/co-admins` | `action`: **`add`** `{ email, password, display_name }`; **`edit`** `{ id, display_name?, email?, password? }`. |
| `POST` | `/api/v1/cp/co-admins/{id}/revoke` | Demotes user to **subscriber**. |

Web UI: **`/cp/coadmins/`** (super admin only).

### Categories & tags

| Method | Path | Notes |
|--------|------|--------|
| `GET` | `/api/v1/cp/categories` | `super_admin` **or** `co_admin`. Returns `parent_id`, `parent_name`, `sort_order`. |
| `POST` | `/api/v1/cp/categories` | `action`: `create` \| `update` \| `delete`. Body includes `name`; optional `parent_id`, `sort_order`, **`page_title`**, **`meta_keywords`**, **`meta_description`** (SEO; empty = clear). Slug is auto-generated. Cannot delete if children exist. |
| `GET` | `/api/v1/cp/tags` | Same roles. Returns `page_title`, `meta_keywords`, `meta_description`. |
| `POST` | `/api/v1/cp/tags` | `action`: `create` \| `update` \| `delete`. Optional **`page_title`**, **`meta_keywords`**, **`meta_description`** (same rules as categories). |

Web UI: **`/cp/categories/`** (both CP roles).
