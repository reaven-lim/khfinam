# CLAUDE.md — KHFinaM

KHFinaM (KH Finance Management) is a production-oriented, multi-user personal and small-business finance app built with **vanilla PHP 8.2+ MVC** (no framework), PDO-only MySQL, and a mobile-first PWA shell.

---

## Project structure

```
khfinam/
├── app/
│   ├── bootstrap.php           # Bootstraps env, config, autoloader, timezone
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── Admin/              # AdminDashboardController, AdminFormController
│   │   ├── Api/                # ReportApiController (CSV/PDF/heatmap)
│   │   └── Mobile/             # MobileAppController, TxnController, WalletActionController,
│   │                           #   RecurringMobileController, NotificationController, ProfileController
│   ├── Core/
│   │   ├── Router.php          # Regex-based route dispatcher (METHOD /path => [Controller, action])
│   │   ├── Database.php        # Singleton PDO connection
│   │   ├── Csrf.php            # CSRF token generation + verification
│   │   ├── RateLimiter.php     # Login and API rate limiting (DB-backed buckets)
│   │   ├── Request.php         # HTTP method, URI, input helpers
│   │   ├── Response.php        # Redirects, aborts, JSON helpers
│   │   ├── Session.php         # Session management wrapper
│   │   └── Env.php             # .env file loader
│   ├── Helpers/
│   │   ├── Auth.php            # Session-based auth helpers
│   │   ├── Config.php          # Flat key-path config accessor
│   │   ├── Str.php             # String utilities
│   │   ├── Url.php             # URL generation helpers
│   │   └── View.php            # PHP view renderer with layout support
│   ├── Repositories/           # PDO data-access layer (no ORM)
│   │   ├── CategoryRepository.php
│   │   ├── CurrencyRepository.php
│   │   ├── SettingsRepository.php
│   │   ├── TransactionRepository.php
│   │   ├── UserRepository.php
│   │   └── WalletRepository.php
│   └── Services/               # Business logic
│       ├── AttachmentService.php
│       ├── AuditLogger.php
│       ├── BackupService.php
│       ├── DatabaseRestoreService.php
│       ├── LowBalanceNotifier.php
│       ├── MailService.php
│       ├── RecurringService.php
│       ├── ReportPdfService.php
│       ├── TransactionService.php
│       └── WalletService.php
├── config/
│   ├── app.php                 # App-level config (env-driven)
│   └── database.php            # DB connection config (env-driven)
├── cron/
│   ├── recurring.php           # Processes recurring transaction rules
│   ├── backup.php              # Runs mysqldump backups
│   ├── cleanup.php             # Purges old logs/temp files
│   ├── low_balance.php         # Sends low-balance notifications
│   └── reminder_email.php      # Sends reminder emails
├── database/
│   ├── migrations/
│   │   ├── 001_initial_schema.sql
│   │   └── 002_features.sql
│   ├── seeders/
│   │   └── 002_demo_seed.sql
│   └── tools/
│       └── bulk_transaction_seed.php
├── public/                     # Web root — only this directory is publicly accessible
│   ├── index.php               # Front controller
│   ├── .htaccess               # mod_rewrite front controller rules
│   ├── manifest.json           # PWA manifest
│   ├── sw.js                   # Service worker
│   ├── offline.html            # PWA offline fallback
│   └── uploads/                # User file uploads (.htaccess denies PHP execution)
├── resources/views/
│   ├── layouts/                # admin.php, mobile.php, guest.php
│   ├── auth/                   # login.php, forgot.php, reset.php
│   ├── admin/                  # dashboard, users, transactions, categories, rates, audit, backups, reports, recurring, settings, notifications
│   └── mobile/                 # dashboard, add, wallets, stats, recurring, recurring_new, notifications, profile, transaction_show
├── routes/web.php              # Full route table (returned array)
├── storage/backups/            # mysqldump output files
├── logs/                       # PHP application logs
├── .env                        # Local secrets (never commit)
├── .env.example                # Template for .env
└── composer.json               # Only external dep: tecnickcom/tcpdf ^6.7
```

---

## Architecture overview

### Request lifecycle

```
public/index.php
  → app/bootstrap.php          (env, config, autoloader)
  → Session::start()
  → routes/web.php             (returns route array)
  → Router::dispatch()         (regex match → Controller::action($params))
  → Controller calls Repositories / Services
  → View::render($template, $data, $layout)
```

### Routing

Routes are defined in `routes/web.php` as `"METHOD /path" => [ControllerClass::class, 'method']`. Path segments like `{id}` become named capture groups. The router instantiates controllers directly (no DI container).

### Database access

All SQL is in **Repositories** using `Database::pdo()` (singleton PDO). Always use prepared statements with named or positional placeholders. Never build raw SQL with user input.

### Views

Views are plain PHP files under `resources/views/`. The `View::render($view, $data, $layout)` helper extracts `$data` into local variables and wraps the view with a layout file from `resources/views/layouts/`. Use `htmlspecialchars()` for all user-supplied output.

### Services

Business logic (wallet transfers, recurring transaction processing, PDF/CSV generation, backup, email) lives in `app/Services/`. Controllers should be thin — delegate to services for anything beyond input validation and redirects.

---

## Environment variables (`.env`)

| Variable | Purpose |
|---|---|
| `APP_NAME` | App display name |
| `APP_ENV` | `production` or `local` |
| `APP_DEBUG` | `true`/`false` — enables error display |
| `APP_URL` | Base URL (no trailing slash) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection |
| `SESSION_LIFETIME` | Session TTL in minutes |
| `SESSION_SECURE_COOKIE` | `true` in HTTPS environments |
| `CSRF_TOKEN_KEY` | Session key name for CSRF token |
| `UPLOAD_MAX_MB` | Max upload size |
| `ALLOWED_UPLOAD_MIME` | Comma-separated MIME types |
| `RATE_LIMIT_LOGIN_MAX` / `RATE_LIMIT_LOGIN_WINDOW` | Login rate limit |
| `RATE_LIMIT_API_MAX` / `RATE_LIMIT_API_WINDOW` | API rate limit |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP sender identity |
| `CRON_KEY` | Secret key for HTTP-triggered cron endpoints |
| `BASE_CURRENCY` | Default currency code (e.g. `MYR`) |
| `DEFAULT_LOCALE` | Default locale |

---

## Local development setup

**Requirements:** PHP 8.2+, MySQL 8+, Apache with `mod_rewrite`, Composer.

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env: APP_URL, DB_*, APP_DEBUG=true

# 3. Create database
mysql -u root -p -e "CREATE DATABASE khfinam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Run migrations and seed
mysql -u root -p khfinam < database/migrations/001_initial_schema.sql
mysql -u root -p khfinam < database/seeders/002_demo_seed.sql
mysql -u root -p khfinam < database/migrations/002_features.sql

# 5. (Optional) Generate bulk sample data
php database/tools/bulk_transaction_seed.php

# 6. Open in browser (use APP_URL from .env — see README / INSTALLATION.md)
# Typical: http://localhost/khfinam/login
#          http://localhost/khfinam/dashboard   (user: demo)
#          http://localhost/khfinam/app         (user: demo)
#          http://localhost/khfinam/admin       (user: superadmin)
# If document root is not rewritten: prefix with /public/, e.g. http://localhost/khfinam/public/login
```

**Demo credentials:** (`superadmin` → admin; `demo` → user dashboard + mobile shell)

| Role | Username | Password |
|---|---|---|
| Super admin | `superadmin` | `Admin@123` |
| User | `demo` | `Demo@123` |

---

## Coding conventions

- **PHP 8.2+**, `declare(strict_types=1)` at the top of every file.
- **PSR-4 autoloading** under the `App\` namespace (root: `app/`).
- **Repositories** handle all data access. No SQL outside `app/Repositories/` and `app/Services/`.
- **Prepared statements only** — no raw string interpolation with user input into SQL.
- **CSRF tokens** are required on all state-mutating POST forms. Use `Csrf::field()` in views and `Csrf::verify()` in the controller before processing.
- **Output escaping** — use `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` in views for user-supplied data.
- **Thin controllers** — delegate business logic to Services. Controllers handle input reading, auth checks, CSRF, and redirect/render.
- **No framework magic** — no annotations, no DI container, no ORMs. Keep it explicit.
- **Timezone** is fixed to `Asia/Kuala_Lumpur` via `config/app.php`.
- **Security headers** (`X-Frame-Options`, CSP, etc.) are set in the front controller or layout — do not remove them.

---

## Security rules — never bypass

- Always call `Csrf::verify()` at the top of POST handlers.
- Always check authentication/authorization before acting (use `Auth::requireLogin()` or role checks).
- Never echo user input without `htmlspecialchars()`.
- Never concatenate user input into SQL strings.
- File uploads must be saved to `public/uploads/` only, with MIME and size validation before saving.
- `logs/`, `storage/`, `.env` must never be served directly — verify `.htaccess` rules are intact.

---

## Cron jobs

| Script | Purpose | Recommended schedule |
|---|---|---|
| `cron/recurring.php` | Fires due recurring transaction rules | Every hour or daily |
| `cron/backup.php` | mysqldump to `storage/backups/` | Daily |
| `cron/cleanup.php` | Purge old temp files and logs | Weekly |
| `cron/low_balance.php` | Email users below balance threshold | Daily |
| `cron/reminder_email.php` | Reminder emails | Daily |

Run as CLI: `php /path/to/khfinam/cron/recurring.php`

---

## API endpoints (super admin session required)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/reports/csv` | CSV transaction export |
| `GET` | `/api/reports/pdf` | Monthly PDF report (TCPDF) |
| `GET` | `/api/reports/heatmap` | Spending heatmap data (JSON) |

---

## Key dependencies

| Package | Version | Purpose |
|---|---|---|
| `tecnickcom/tcpdf` | `^6.7` | PDF report generation |

All other functionality is vanilla PHP — no Laravel, Symfony, or similar.

---

## Useful references

- `DESIGN_SYSTEM.md` — Frontend design system and UI implementation guide
- `docs/UI_COMPONENTS.md` — Reusable `resources/views/components/*` partials (dashboard/admin shells, analytics snippets)
- `README.md` — Quick start and feature overview
- `INSTALLATION.md` — Detailed XAMPP, cPanel, SSL, cron, permissions setup
- `API_DOCS.md` — Internal HTTP endpoint documentation
- `SECURITY.md` — Threat model, security controls, and hardening checklist
