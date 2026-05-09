# Security architecture

## Goals

- Protect sessions, credentials, and personal financial data on shared hosting.
- Reduce risk from XSS, CSRF, SQL injection, file upload abuse, and brute-force logins.

## Measures implemented

| Area | Implementation |
|------|------------------|
| **SQL** | PDO with prepared statements only (see repositories and services). |
| **Passwords** | `password_hash()` / `password_verify()` (bcrypt). |
| **Sessions** | PHP sessions; ID regeneration on login; HTTP-only cookies; `SameSite=Lax`; optional secure flag from `.env`. |
| **CSRF** | Token in session; `Csrf::field()` on POST forms; verification in controllers. |
| **Brute force** | `rate_limit_buckets` table + `RateLimiter` on login by IP. |
| **Lockout** | Failed attempts increment; account lock after threshold (`users.locked_until`). |
| **Audit** | `AuditLogger` writes to `audit_logs` for logins, transaction create, and cron actions. |
| **Headers** | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, CSP allowing required CDNs. |
| **Uploads** | `public/uploads/.htaccess` denies PHP execution; validate MIME/size in future upload handlers. |
| **Configuration** | Secrets in `.env` (not under document root); `public/` is the only web root. |

## Operational checklist

1. Use HTTPS in production; set `SESSION_SECURE_COOKIE=true`.
2. Set a strong `CRON_KEY` if cron scripts are ever exposed via HTTP wrappers.
3. Restrict DB user privileges to a single schema.
4. Review `audit_logs` regularly for unexpected logins or spikes in failures.
5. Keep PHP and MySQL patched on the host.

## Known limitations / next hardening

- CSP includes `unsafe-inline` for Tailwind/Chart CDN usage; tightening would require self-hosted assets.
- Full file upload pipeline should add virus scanning / MIME deep checks for production finance workloads.
- Push notifications are stubbed in the PWA; integrate with a push provider when needed.
