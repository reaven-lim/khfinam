# KHFinaM (KH Finance Management)

Production-oriented multi-user personal and small-business finance app: incomes, expenses, wallets, recurring rules, reporting, audit trail, backups, and a mobile-first PWA shell.

## Requirements

- PHP **8.2+** with extensions: `pdo_mysql`, `json`, `openssl`, `mbstring`, `fileinfo`
- MySQL **8+** (utf8mb4 / InnoDB)
- Apache with `mod_rewrite` (or nginx equivalent) for the `public/` front controller
- **Composer** (for dependency install on the server)

## Quick start (XAMPP / local)

1. Clone or copy the project into your web root (e.g. `htdocs/khfinam`).
2. `composer install`
3. Copy `.env.example` to `.env` and set `APP_URL`, database credentials, and `APP_DEBUG` as needed.
4. Create database: `CREATE DATABASE khfinam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
5. Import schema and seed:

   ```bash
   mysql -u root -p khfinam < database/migrations/001_initial_schema.sql
   mysql -u root -p khfinam < database/seeders/002_demo_seed.sql
   mysql -u root -p khfinam < database/migrations/002_features.sql
   ```

6. *(Optional demo volume)* Generate extra sample transactions for charts:

   ```bash
   php database/tools/bulk_transaction_seed.php
   ```

7. Point the web server document root to **`public/`**, or open  
   `http://localhost/khfinam/public/` (adjust path to match your install).

### Demo accounts

| Role        | Username    | Password   |
|------------|-------------|------------|
| Super admin | `superadmin` | `Admin@123` |
| User       | `demo`      | `Demo@123` |

Change passwords immediately in any non-demo deployment.

## Features (high level)

- MVC-style PHP (no framework), PDO-only SQL, CSRF and rate limiting on auth
- Mobile UI (`/app/…`) with dashboard, transaction entry, recurring list, stats, notifications, profile
- Admin UI (`/admin/…`) for overview, users, transactions, categories, rates, audit, reports, backups settings
- Cron: `cron/recurring.php`, `cron/backup.php` (see INSTALLATION.md)
- PWA: `manifest.json`, `sw.js`, offline fallback `offline.html`
- CSV export: `GET /api/reports/csv` (super admin session)

## Documentation

- **INSTALLATION.md** — XAMPP, AMPPS, cPanel, SSL, cron, permissions, SMTP
- **API_DOCS.md** — Internal HTTP endpoints
- **SECURITY.md** — Threat model and controls

## Troubleshooting

- **404 on all routes:** enable `mod_rewrite`; ensure `.htaccess` in `public/` is allowed (`AllowOverride All`).
- **Database connection errors:** verify `.env` `DB_*` and that MySQL listens on the configured host/port.
- **Blank page:** set `APP_DEBUG=true` temporarily in `.env` and check `logs/` and the PHP error log.
- **mysqldump backup fails:** install client tools or run backups via hosting control panel; see `cron/backup.php`.

## License

Proprietary / your organization — adjust as needed.
