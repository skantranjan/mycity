# Setup, run, deploy

## Local setup

1. Configure PHP for the project (PHP 7.4+; 8+ recommended).
2. **Database + JWT:** copy `.env.example` to **`.env`** in the project root (same folder as `index.php`). Set:
   - `MCI_DB_HOST` — e.g. `srv1518.hstgr.io` or your MySQL host IP
   - `MCI_DB_NAME`, `MCI_DB_USER`, `MCI_DB_PASS`
   - `MCI_JWT_SECRET` — long random string (used to sign API JWT cookies)

   On each request, `api/v1/lib/config.php` loads `.env` via `includes/mci_load_env.php` (if the file exists). You can instead set the same variables in the web server / hosting panel without using a file.

   **Never commit `.env`** — it is listed in `.gitignore`.

### Local MySQL (no Hostinger / no remote DB)

You **do not** need Hostinger, hPanel, or a public IP to build and test the API. Remote MySQL often blocks connections except from allowed IPs—that’s a hosting restriction, not a limitation of this project.

**Recommended:** run MySQL **on your machine** (or in Docker):

1. Install **MySQL** or **MariaDB** (e.g. [XAMPP](https://www.apachefriends.org/), [Laragon](https://laragon.org/), [WAMP](https://www.wampserver.com/), official MySQL installer, or `docker run` a MariaDB image).
2. Create a database (e.g. `mycityinfo_local`) and a user with a password, and grant that user access to that database.
3. Import **`api/v1/migrations/001_create_core_tables.sql`** (phpMyAdmin, MySQL Workbench, or `mysql -u ... < .../001_create_core_tables.sql`).
4. Point **`.env`** at localhost, for example:

   ```env
   MCI_DB_HOST=127.0.0.1
   MCI_DB_NAME=mycityinfo_local
   MCI_DB_USER=your_local_user
   MCI_DB_PASS='your_local_password'
   MCI_JWT_SECRET=your_long_random_secret
   ```

Then use **`/login/`**, **`/api/v1/...`**, etc. as usual. When you deploy, change **`.env`** (or server env vars) to Hostinger’s host/user/pass—no code changes required.

   The migration creates all `mci_*` tables in order; the file **ends with optional dev test users**—see `project_brain/DEV_TEST_ACCOUNTS.md`. For **production** imports, omit or delete that last section. If you ever had old unprefixed tables (`users`, `roles`, …), use a fresh DB or drop them—this app expects **`mci_*` only**.

## Run

- Web server serves PHP frontend pages.
- API: `/api/v1/*`.

### VS Code / Cursor — PHP built-in server

From the **project root** (folder that contains `index.php`), in the integrated terminal:

```bash
php -S localhost:8000
```

Then open **http://localhost:8000/** . Stop with **Ctrl+C**.

This does **not** apply `.htaccess` rewrites; use Apache/nginx (or Laragon/XAMPP) if you need identical pretty URLs locally.

## Deploy (e.g. Hostinger)

- Deploy project files.
- Ensure environment variables are set (DB host/name/user/pass, JWT secret, etc.).
