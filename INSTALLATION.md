# Installation & deployment

## XAMPP (Windows)

1. Install XAMPP with Apache + MySQL + PHP 8.2+.
2. Place the project under `htdocs/khfinam`.
3. `composer install` in the project root.
4. Create DB and import SQL (see README).
5. Copy `.env.example` to `.env`.

### URL options (choose one)

**Option A — root `.htaccess` rewrite (recommended, no `/public` in URL)**

`AllowOverride All` (or at minimum `AllowOverride FileInfo Options`) must be enabled for the `htdocs` directory in `httpd.conf`. The project ships a root `.htaccess` that forwards every request into `public/` transparently.

Set in `.env`:
```
APP_URL=http://localhost/khfinam
```

Visit: `http://localhost/khfinam/`  
Demo entry points on the same origin: `/login`, `/dashboard`, `/app`, `/admin` (prepend this host + path; see README **Demo accounts**).

**Option B — point DocumentRoot directly at `public/`**

In `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:
```apache
DocumentRoot "C:/xampp/htdocs/khfinam/public"
ServerName khfinam.local
```

Set in `.env`:
```
APP_URL=http://khfinam.local
```

Then open e.g. `http://khfinam.local/login`, `http://khfinam.local/dashboard`, `http://khfinam.local/app`, `http://khfinam.local/admin`.

### Cron on Windows

Use Task Scheduler to run:

```bat
cd /d C:\xampp\htdocs\khfinam
C:\xampp\php\php.exe cron\recurring.php
```

Use hourly or daily schedule as needed.

For **tomorrow’s recurring reminder** (email + in-app notification), run once daily:

```bat
C:\xampp\php\php.exe cron\reminder_email.php
```

## AMPPS / macOS

Same as XAMPP: document root → `public/`, Composer install, MySQL import, `.env`.

## cPanel shared hosting

1. Upload files (excluding `node_modules` if any; this project does not require Node).
2. In cPanel **MySQL® Databases**, create database and user; assign all privileges.
3. **phpMyAdmin** → Import `001_initial_schema.sql` then `002_demo_seed.sql`.
4. Edit `.env` with remote DB credentials and `APP_URL` (https recommended).
5. In **Domains**, map the domain or subdomain to `public/` (some hosts call this “document root” under **Domains** → **Manage**).
6. Ensure PHP version ≥ 8.2 and enable extensions listed in README.

### Cron jobs (example)

```text
* * * * * /usr/bin/php /home/USER/khfinam/cron/recurring.php
0 8 * * * /usr/bin/php /home/USER/khfinam/cron/low_balance.php
0 3 * * * /usr/bin/php /home/USER/khfinam/cron/backup.php
0 7 * * * /usr/bin/php /home/USER/khfinam/cron/reminder_email.php
0 2 * * * /usr/bin/php /home/USER/khfinam/cron/cleanup.php
```

Adjust paths to PHP and the project.

## SSL

Use Let’s Encrypt or your host’s certificate tool. Set:

```env
APP_URL=https://your-domain.tld
SESSION_SECURE_COOKIE=true
```

## Permissions

- `storage/backups/`, `logs/`, `public/uploads/` must be writable by the web server user.
- Never expose `app/`, `config/`, `.env` via the web server (document root must be `public/` only).

## SMTP

Store SMTP parameters in `settings` (`scope=global`, keys `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_encryption`) or rely on PHP `mail()` for simple hosts. Use “forgot password” and server mail logs to verify delivery.

## Backup script

`cron/backup.php` expects `mysqldump` on the server. On Windows XAMPP, add MySQL `bin` to PATH or adapt the script to the full path of `mysqldump.exe`. Backups land in `storage/backups/` and are logged in the `backups` table.
