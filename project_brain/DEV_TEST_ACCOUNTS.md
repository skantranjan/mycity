# Dev test accounts (local / staging only)

**Do not use these credentials in production.** They are inserted by the core migration (see below).

## Apply schema + seed users

Import **`api/v1/migrations/001_create_core_tables.sql`** once — it creates tables **and** appends dev users at the end (idempotent per email).

```text
mysql -u YOUR_USER -p YOUR_DB < api/v1/migrations/001_create_core_tables.sql
```

`dev_seed_test_users.sql` is deprecated (empty pointer); seeds live in `001_create_core_tables.sql`.

## Logins

All subscribers share **`DevSubscriber123!`**. All super admins share **`DevSuperAdmin123!`**.

| Role | Email | Password |
|------|--------|----------|
| Subscriber | `subscriber1.dev@mycityinfo.local` | `DevSubscriber123!` |
| Subscriber | `subscriber2.dev@mycityinfo.local` | `DevSubscriber123!` |
| Subscriber | `subscriber3.dev@mycityinfo.local` | `DevSubscriber123!` |
| Super admin | `superadmin.dev@mycityinfo.local` | `DevSuperAdmin123!` |
| Super admin | `superadmin2.dev@mycityinfo.local` | `DevSuperAdmin123!` |

## Where to sign in

- **Subscriber:** **`/login/`** → default redirect **`/subscriber/dashboard/`**.
- **Super admin:** **`/cp/login/`** → **`/cp/dashboard/`** (create more users from CP as implemented).

Auth uses **`mci_api_token`** (HTTP-only cookie) for API calls.

## Alternative: bootstrap first super admin (empty `mci_users`)

If **`mci_users` is empty** and env vars are set, first login via **`POST /api/v1/cp/login`** or **`/cp/login/`** can create the first super admin:

- `MCI_DEFAULT_SUPERADMIN_EMAIL`
- `MCI_DEFAULT_SUPERADMIN_PASSWORD`

After any user row exists, this bootstrap path no longer runs.

## Changing dev passwords

```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

Update `password_hash` in `001_create_core_tables.sql` (dev seed section) or run `UPDATE users …`.
