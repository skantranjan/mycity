# Database migrations (MySQL)

Apply `api/v1/migrations/001_create_core_tables.sql` against your MySQL database (same DB as `MCI_*` env vars in `api/v1/lib/config.php`).

## Run (examples)

**mysql CLI** (replace user/db/host):

```bash
mysql -h "$MCI_DB_HOST" -u "$MCI_DB_USER" -p"$MCI_DB_PASS" "$MCI_DB_NAME" < api/v1/migrations/001_create_core_tables.sql
```

**Windows PowerShell** (from project root):

```powershell
Get-Content api\v1\migrations\001_create_core_tables.sql | mysql -h localhost -u USER -p DBNAME
```

## Notes

- `001_create_core_tables.sql` uses `CREATE TABLE IF NOT EXISTS`, so it is safe to run on an initialized DB.
- For existing DBs created before `role_id` + `roles`/`userprofiles`, you will need a schema migration plan (recreate on dev is recommended first).
- **`is_logged_in`** is updated on API login/logout; JWT sessions can outlive this flag—treat as best-effort.
