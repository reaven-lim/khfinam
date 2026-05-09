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
5. Import schema and seed (in this order):

   ```bash
   mysql -u root -p khfinam < database/migrations/001_initial_schema.sql
   mysql -u root -p khfinam < database/seeders/002_demo_seed.sql
   mysql -u root -p khfinam < database/migrations/002_features.sql
   ```

   **Upgrading an existing database:** if `wallets` still has an old **`wallet_type` enum** column (no `wallet_types` table / no `wallet_type_id` on `wallets`), run the one-time migration **once** after pulling the latest code:

   ```bash
   mysql -u root -p khfinam < database/migrations/003_wallet_account_types.sql
   ```

   Fresh installs that use the **current** `001_initial_schema.sql` already include `wallet_types` and `wallet_type_id`; you do **not** need `003` in that case.

   **Transfers (`type = transfer`):** databases created from an older `001` before native transfers need **`database/migrations/004_transaction_transfer_type.sql`** once. The **current** `001` ships with transfer columns already; skip `004` only if those columns exist.

6. *(Optional demo volume)* Generate extra sample transactions for charts:

   ```bash
   php database/tools/bulk_transaction_seed.php
   ```

7. Point the web server document root to **`public/`**, or open  
   `http://localhost/khfinam/public/` (adjust path to match your install).

### Demo accounts

| Role        | Username     | Password    |
|-------------|--------------|-------------|
| Super admin | `superadmin` | `Admin@123` |
| User        | `demo`       | `Demo@123` |

Paths below are relative to **`APP_URL`** in `.env` (no trailing slash). Combine them for the full link, e.g. `{APP_URL}/login`.

| Surface | Path | Use this account |
|---------|------|------------------|
| **Login** | `/login` | Either account |
| **User dashboard** (analytics web UI) | `/dashboard` | `demo` |
| **Mobile app shell** (PWA-friendly) | `/app` | `demo` |
| **Admin console** | `/admin` | `superadmin` |
| **Admin — all users’ wallets** | `/admin/wallets` | `superadmin` |
| **Admin — wallet/account type labels** | `/admin/wallet-types` | `superadmin` |

**Examples (typical XAMPP):** if `APP_URL=http://localhost/khfinam` with the [root rewrite](INSTALLATION.md#url-options-choose-one) from INSTALLATION.md:

- `http://localhost/khfinam/login`
- `http://localhost/khfinam/dashboard`
- `http://localhost/khfinam/app`
- `http://localhost/khfinam/admin`

If you open the app via **`/public/`** (document root pointed at the project folder instead of `public/` only), prefix the same paths, e.g. `http://localhost/khfinam/public/login`, `.../dashboard`, `.../app`, `.../admin`.

Change passwords immediately in any non-demo deployment.

## Features (high level)

- MVC-style PHP (no framework), PDO-only SQL, CSRF and rate limiting on auth
- **User analytics dashboard** (`/dashboard/…`) — charts, wallets, recurring, reports (browser; uses `demo`)
- **Mobile app shell** (`/app/…`) — dashboard, **add expense / income / transfer** (`/app/add`), wallets, stats, notifications, profile (`demo`)
- **Admin console** (`/admin/…`) — overview, users, transactions, categories, rates, **wallets** (`/admin/wallets`), **wallet types** (`/admin/wallet-types`), audit, reports, backups, settings (`superadmin`)
- Cron: `cron/recurring.php`, `cron/backup.php` (see INSTALLATION.md)
- PWA: `manifest.json`, `sw.js`, offline fallback `offline.html`
- CSV export: `GET /api/reports/csv` (super admin session)

## Documentation

- **INSTALLATION.md** — XAMPP, AMPPS, cPanel, SSL, cron, permissions, SMTP
- **DESIGN_SYSTEM.md** — Frontend tokens, layouts, charts/KPI patterns, light/dark behavior
- **docs/UI_COMPONENTS.md** — Reusable `resources/views/components/*` partials
- **API_DOCS.md** — Internal HTTP endpoints
- **SECURITY.md** — Threat model and controls

## Troubleshooting

- **`Table 'wallet_types' doesn't exist` (admin wallet types / wallets pages):** the database predates customizable wallet types. Import **`database/migrations/003_wallet_account_types.sql`** once (see upgrade note under **Quick start** step 5), e.g. `mysql -u root -p khfinam < database/migrations/003_wallet_account_types.sql` or paste the file into phpMyAdmin.
- **`Unknown column 'from_wallet_id'` / `transfer` type errors:** run **`database/migrations/004_transaction_transfer_type.sql`** once (see **Quick start** step 5).
- **404 on all routes:** enable `mod_rewrite`; ensure `.htaccess` in `public/` is allowed (`AllowOverride All`).
- **Database connection errors:** verify `.env` `DB_*` and that MySQL listens on the configured host/port.
- **Blank page:** set `APP_DEBUG=true` temporarily in `.env` and check `logs/` and the PHP error log.
- **mysqldump backup fails:** install client tools or run backups via hosting control panel; see `cron/backup.php`.

## License

Proprietary / your organization — adjust as needed.
