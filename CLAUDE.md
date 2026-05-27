# CLAUDE.md — KHFinaM

KHFinaM (KH Finance Management) is a production-oriented, multi-user personal and small-business finance app built with **vanilla PHP 8.2+ MVC** (no framework), PDO-only MySQL, and a mobile-first PWA shell.

The admin interface is evolving toward **production-grade SaaS admin quality** — high information density, dark-mode-first, operational dashboard aesthetic. Every admin page should answer a real operational question at a glance. Avoid basic CRUD forms; prefer summary → directory → detail flows.

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
│   │   ├── Dashboard/          # DashboardController, DashboardWalletController (user web wallets POST)
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
│   │   ├── View.php            # PHP view renderer (render + partial helpers)
│   │   └── WalletTypeUi.php    # Admin wallet-type copy: icon presets, slug helpers
│   ├── Repositories/           # PDO data-access layer (no ORM)
│   │   ├── CategoryRepository.php
│   │   ├── CurrencyRepository.php
│   │   ├── SettingsRepository.php
│   │   ├── TransactionRepository.php
│   │   ├── UserRepository.php
│   │   ├── WalletRepository.php
│   │   └── WalletTypeRepository.php
│   └── Services/               # Business logic
│       ├── AttachmentService.php
│       ├── AuditLogger.php
│       ├── BackupService.php
│       ├── DatabaseRestoreService.php
│       ├── LowBalanceNotifier.php
│       ├── MailService.php
│       ├── RecurringService.php
│       ├── ReportPdfService.php
│       ├── TransactionIntelligenceService.php   # Shared transaction listing + analytics KPIs (admin + dashboard)
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
│   │   ├── 001_initial_schema.sql              # Base schema (wallet_types, transfer-capable transactions)
│   │   ├── 002_features.sql                    # is_internal_transfer, transfer_group, indexes
│   │   ├── 003_wallet_account_types.sql         # Upgrade: legacy wallet_type enum → wallet_types + wallet_type_id
│   │   ├── 004_transaction_transfer_type.sql    # Upgrade: type includes transfer + from_wallet_id / to_wallet_id
│   │   └── 005_include_in_analytics.sql         # Adds users.include_in_analytics (analytics cohort flag)
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
│   ├── layouts/                # admin.php, dashboard.php, mobile.php, guest.php
│   ├── auth/                   # login.php, forgot.php, reset.php (+ _auth-shell-brand.php partial)
│   ├── admin/                  # dashboard, users, user_show, transactions, categories, rates,
│   │                           #   wallets, wallet_show, wallet_types, wallet_type_show,
│   │                           #   audit, backups, reports, recurring, settings, notifications
│   │                           #   + partials/wallet_type_table_rows.php
│   ├── dashboard/              # overview, wallets, reports, recurring, notifications
│   ├── mobile/                 # dashboard, add, wallets, stats, transaction_show,
│   │                           #   recurring, recurring_new, notifications, profile
│   └── components/             # Reusable partials — see docs/UI_COMPONENTS.md
│       ├── layout/             # Shell chrome: sidebar, header, script partials
│       ├── analytics/          # chart-shell-card, filter-lens-intro, insight-glass-card
│       ├── admin/              # hero-kpi-gradient-card, wallet-type-icon-select
│       ├── charts/             # apex-theme-css
│       └── ui/                 # empty-state-muted, message-banner-inline
├── docs/
│   └── UI_COMPONENTS.md        # Full component partial API (props, usage, design rules)
├── routes/web.php              # Full route table (returned array)
├── storage/backups/            # mysqldump output files
├── logs/                       # PHP application logs
├── .env                        # Local secrets (never commit)
├── .env.example                # Template for .env
└── composer.json               # Only external dep: tecnickcom/tcpdf ^6.7
```

---

## Application surfaces

The app exposes **three distinct surfaces**, each with its own layout and UX intent:

| Surface | URL prefix | Layout | Audience |
|---|---|---|---|
| **Admin** | `/admin/*` | `layouts/admin.php` | Super admin — governance, oversight, operations |
| **Dashboard** | `/dashboard/*` | `layouts/dashboard.php` | Authenticated users — personal finance overview |
| **Mobile / App** | `/app/*` | `layouts/mobile.php` | Mobile-first PWA shell for quick transaction entry |
| **Auth** | `/login`, `/forgot-password`, etc. | `layouts/guest.php` | Unauthenticated visitors |

Keep surface boundaries clear. Admin views use the admin layout and admin controllers. Never mix surfaces in the same controller.

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

**Ledger rows:** `transactions.type` is `income`, `expense`, or **`transfer`**. Transfers move value between wallets (`from_wallet_id` → `to_wallet_id`) without affecting income/expense KPI aggregates. Older data may still use paired rows with `is_internal_transfer = 1` instead of native `transfer` rows.

**Wallet metadata:** reusable types live in **`wallet_types`**; each **`wallets`** row references `wallet_type_id`.

**Analytics cohort:** `users.include_in_analytics` (added in migration 005) controls whether a user's data appears in admin-level aggregations. Admin can toggle this per-user via `POST /admin/users/prefs`.

### Views

Views are plain PHP files under `resources/views/`. Two rendering helpers:

- **`View::render($view, $data, $layout)`** — renders a full page: extracts `$data` into local variables, wraps with a layout from `resources/views/layouts/`.
- **`View::partial($path, $data)`** — includes a component partial from `resources/views/`. Path is rooted at `resources/views/`. Use for all reusable UI partials under `components/`.

Use `htmlspecialchars()` for all user-supplied output. See `docs/UI_COMPONENTS.md` for the full component API.

### Services

Business logic lives in `app/Services/`. Controllers should be thin — delegate to services for anything beyond input validation and redirects.

Key services:
- **`TransactionIntelligenceService`** — cross-cutting analytics: filtered transaction listings, KPI payloads (daily series, category breakdown, wallet breakdown, spend-delta), and chart window helpers. Shared between admin (`/admin/transactions`) and dashboard (`/dashboard`) to avoid duplication.
- **`WalletService`** — wallet CRUD, balance recalculation, transfer execution.
- **`TransactionService`** — transaction creation, update, deletion, child transactions.
- **`RecurringService`** — evaluates and fires due recurring rules.
- **`ReportPdfService`** — TCPDF-based monthly PDF generation.
- **`AuditLogger`** — structured audit log writes.
- **`BackupService`** / **`DatabaseRestoreService`** — mysqldump and restore.
- **`AttachmentService`** — file upload validation and storage.
- **`LowBalanceNotifier`** / **`MailService`** — email delivery.

---

## Frontend stack

No build step. All assets load via CDN or are inline in layout partials.

| Dependency | Source | Purpose |
|---|---|---|
| **Tailwind CSS** | CDN (`darkMode: 'class'`) | Utility-first styling — dark mode toggled via `.dark` class on `<html>` |
| **Inter** | Google Fonts | Primary typeface |
| **Lucide** | CDN | Icon set — used via `data-lucide` attributes + `lucide.createIcons()` |
| **ApexCharts 3.x** | CDN | All dashboard and analytics charts |

Theme persistence uses `localStorage`. The theme bootstrap snippet lives in `components/layout/analytics-shell-head.php` and runs inline to prevent FOUC. Key storage keys: `dash-sb-compact` (dashboard sidebar state), `adm-sb-compact` (admin sidebar state).

### Design direction

The visual language is **premium fintech / dark-mode-first SaaS admin**:

- Canvas: `bg-slate-100` (light) / `bg-[#090e1a]` (dark)
- Primary panels: `bg-white` (light) / `bg-[#0d1424]` (dark)
- Brand accent: teal family (`teal-600/700/800` light, `teal-400/300` dark)
- Income: `emerald-*` / `#10b981`; Expense: `rose-*` / `#f43f5e`; Recurring: `violet-*`
- Cards: light border `border-slate-300/85 ring-1 ring-slate-900/[0.06]`; dark `ring-1 ring-white/[0.05]`

See `DESIGN_SYSTEM.md` for the full color system, typography hierarchy, card patterns, and chart standards.

---

## Route map

### Auth

| Method | Path | Handler |
|---|---|---|
| `GET` | `/login` | `AuthController::showLogin` |
| `POST` | `/login` | `AuthController::login` |
| `GET/POST` | `/logout` | `AuthController::logout` |
| `GET/POST` | `/forgot-password` | `AuthController::showForgot / forgotSubmit` |
| `GET/POST` | `/reset-password` | `AuthController::showReset / resetSubmit` |

### Mobile / App (`/app/*`)

| Method | Path | Notes |
|---|---|---|
| `GET` | `/` | PWA home (redirects to app dashboard) |
| `GET` | `/app` | App dashboard |
| `GET/POST` | `/app/add` | Add transaction |
| `GET` | `/app/wallets` | Wallet list |
| `POST` | `/app/wallets` | Create wallet |
| `POST` | `/app/wallets/update` | Update wallet |
| `POST` | `/app/wallets/delete` | Delete wallet |
| `POST` | `/app/wallets/transfer` | Wallet-to-wallet transfer |
| `GET` | `/app/stats` | Stats view |
| `GET` | `/app/recurring` | Recurring rules list |
| `GET/POST` | `/app/recurring/new` | Create recurring rule |
| `POST` | `/app/recurring/pause` | Pause rule |
| `POST` | `/app/recurring/skip` | Skip next occurrence |
| `POST` | `/app/recurring/run` | Run rule now |
| `GET` | `/app/notifications` | Notifications |
| `POST` | `/app/notifications/read` | Mark one read |
| `POST` | `/app/notifications/read-all` | Mark all read |
| `GET` | `/app/profile` | Profile view |
| `POST` | `/app/profile` | Save profile |
| `GET` | `/app/transaction/{id}` | Transaction detail |
| `POST` | `/app/transaction/{id}` | Update transaction |
| `POST` | `/app/transaction/{id}/delete` | Delete transaction |
| `POST` | `/app/transaction/{id}/attach` | Upload attachment |
| `POST` | `/app/transaction/{id}/attach-delete` | Delete attachment |
| `POST` | `/app/transaction/{id}/child` | Add child transaction |

### Dashboard (`/dashboard/*`)

| Method | Path | Notes |
|---|---|---|
| `GET` | `/dashboard` | Overview |
| `GET` | `/dashboard/transactions` | Transaction ledger |
| `GET` | `/dashboard/wallets` | Wallet list |
| `POST` | `/dashboard/wallets/store` | Create wallet |
| `POST` | `/dashboard/wallets/update` | Update wallet |
| `POST` | `/dashboard/wallets/delete` | Delete wallet |
| `GET` | `/dashboard/recurring` | Recurring rules |
| `GET` | `/dashboard/reports` | Reports |
| `GET` | `/dashboard/reports/csv` | CSV export |
| `GET` | `/dashboard/reports/pdf` | PDF report |
| `GET` | `/dashboard/notifications` | Notifications |

### Admin (`/admin/*`)

| Method | Path | Notes |
|---|---|---|
| `GET` | `/admin` | Admin overview dashboard |
| `GET` | `/admin/transactions` | Transaction ledger (all users) |
| `GET` | `/admin/users` | User directory |
| `GET` | `/admin/users/{id}` | User detail page |
| `POST` | `/admin/users` | Create user |
| `POST` | `/admin/users/update` | Update user |
| `POST` | `/admin/users/status` | Set user status |
| `POST` | `/admin/users/prefs` | Toggle user analytics inclusion |
| `GET` | `/admin/wallets` | Wallet operations center |
| `GET` | `/admin/wallets/{id}` | Wallet detail page |
| `POST` | `/admin/wallets/store` | Create wallet |
| `POST` | `/admin/wallets/update` | Update wallet |
| `POST` | `/admin/wallets/status` | Set wallet status |
| `POST` | `/admin/wallets/delete` | Delete wallet |
| `GET` | `/admin/wallet-types` | Wallet type governance hub |
| `GET` | `/admin/wallet-types/{id}` | Wallet type detail page |
| `POST` | `/admin/wallet-types/store` | Create wallet type |
| `POST` | `/admin/wallet-types/update` | Update wallet type |
| `POST` | `/admin/wallet-types/status` | Set wallet type status |
| `POST` | `/admin/wallet-types/delete` | Delete wallet type |
| `GET` | `/admin/categories` | Category management |
| `POST` | `/admin/categories` | Create category |
| `POST` | `/admin/categories/update` | Update category |
| `POST` | `/admin/categories/delete` | Delete category |
| `GET` | `/admin/rates` | Exchange rate management |
| `POST` | `/admin/rates` | Create rate |
| `POST` | `/admin/rates/delete` | Delete rate |
| `GET` | `/admin/notifications` | Notifications |
| `POST` | `/admin/notifications/broadcast` | Broadcast notification |
| `GET` | `/admin/settings` | System settings |
| `POST` | `/admin/settings` | Save settings |
| `POST` | `/admin/settings/test-email` | Send test email |
| `GET` | `/admin/audit` | Audit log |
| `GET` | `/admin/backups` | Backup management |
| `POST` | `/admin/backups/run` | Run backup |
| `POST` | `/admin/backups/restore` | Restore backup |
| `GET` | `/admin/backups/download/{id}` | Download backup file |
| `GET` | `/admin/reports` | Reports |
| `GET` | `/admin/recurring` | Recurring rule management |
| `POST` | `/admin/recurring/run` | Run recurring job now |
| `POST` | `/admin/recurring/create` | Create recurring rule |

### API (super admin session required)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/reports/csv` | CSV transaction export |
| `GET` | `/api/reports/pdf` | Monthly PDF report (TCPDF) |
| `GET` | `/api/reports/heatmap` | Spending heatmap data (JSON) |

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

# 4. Run migrations (fresh install — run all in order)
mysql -u root -p khfinam < database/migrations/001_initial_schema.sql
mysql -u root -p khfinam < database/seeders/002_demo_seed.sql
mysql -u root -p khfinam < database/migrations/002_features.sql
mysql -u root -p khfinam < database/migrations/003_wallet_account_types.sql
mysql -u root -p khfinam < database/migrations/004_transaction_transfer_type.sql
mysql -u root -p khfinam < database/migrations/005_include_in_analytics.sql

# Note: 003, 004, 005 are one-time upgrades. On a brand-new DB built from the
# current 001, they may be partially applied already — check each script header
# before running on an existing database.

# 5. (Optional) Generate bulk sample data
php database/tools/bulk_transaction_seed.php

# 6. Open in browser (use APP_URL from .env)
# http://localhost/khfinam/login
# http://localhost/khfinam/dashboard   (user: demo)
# http://localhost/khfinam/app         (user: demo — mobile shell)
# http://localhost/khfinam/admin       (user: superadmin)
# If document root is not rewritten: prefix with /public/
```

**Demo credentials:**

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
- **Component partials** — use `View::partial('components/...', $data)` for all reusable UI. Never duplicate shell chrome inline. See `docs/UI_COMPONENTS.md` for the full API.
- **Surface boundaries** — keep admin/dashboard/mobile controllers and views strictly separated. No cross-surface controller reuse.

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

## Key dependencies

| Package | Version | Purpose |
|---|---|---|
| `tecnickcom/tcpdf` | `^6.7` | PDF report generation |

All other functionality is vanilla PHP — no Laravel, Symfony, or similar.

---

## Useful references

- `DESIGN_SYSTEM.md` — Color system, typography, card patterns, chart standards
- `docs/UI_COMPONENTS.md` — Full component partial API (`components/layout`, `components/analytics`, `components/admin`, `components/ui`)
- `README.md` — Quick start and feature overview
- `INSTALLATION.md` — Detailed XAMPP, cPanel, SSL, cron, permissions setup
- `API_DOCS.md` — Internal HTTP endpoint documentation
- `SECURITY.md` — Threat model, security controls, and hardening checklist
