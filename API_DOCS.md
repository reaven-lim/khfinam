# KHFinaM — Internal API & Route Reference

All routes are handled by `public/index.php`.  
Authentication is **session-based** (cookie `KHFINAMSSESSID`).  
All POST forms include a CSRF token field (`_csrf_token` by default, configurable via `APP_CSRF_KEY`).

---

## Authentication

| Method | Path | Description |
|--------|------|-------------|
| GET | `/login` | Login form |
| POST | `/login` | Login (`login`, `password`, optional `remember=1`) |
| GET/POST | `/logout` | Logout (POST form with CSRF in layouts; GET also works) |
| GET | `/forgot-password` | Forgot password form |
| POST | `/forgot-password` | Send password-reset email (`email`) |
| GET | `/reset-password?token=` | Reset password form |
| POST | `/reset-password` | Set new password (`token`, `password`, `password_confirm`) |

---

## Mobile App — role: `user`

### Dashboard & navigation

| Method | Path | Description |
|--------|------|-------------|
| GET | `/app` | Dashboard (balance, income/expense, upcoming recurring, recent transactions) |
| GET | `/app/add` | Add transaction form |
| POST | `/app/add` | Create transaction (`type`, `title`, `amount`, `wallet_id`, `category_id`, `transaction_date`, `notes`, optional `is_consolidated_parent`, `tags`) |
| GET | `/app/wallets` | Wallet list + balances |
| POST | `/app/wallets` | Create wallet (`name`, `wallet_type`, `currency_id`, `opening_balance`, `min_balance_threshold`, `is_default`, `notes`) |
| POST | `/app/wallets/transfer` | Internal wallet transfer (`from_wallet_id`, `to_wallet_id`, `amount`, `date`, `notes`) |
| GET | `/app/stats` | Statistics: bar chart, savings rate, expense heatmap |
| GET | `/app/notifications` | Notification list (unread highlighted) |
| POST | `/app/notifications/read` | Mark one notification read (`notification_id`) |
| POST | `/app/notifications/read-all` | Mark all notifications read |
| GET | `/app/profile` | Profile & preferences |
| POST | `/app/profile` | Save preferences (`preference_theme`, `preference_mute_low_balance`) |

### Recurring schedules

| Method | Path | Description |
|--------|------|-------------|
| GET | `/app/recurring` | List recurring schedules |
| GET | `/app/recurring/new` | New schedule form |
| POST | `/app/recurring/new` | Create schedule (`wallet_id`, `category_id`, `type`, `title`, `amount`, `currency_id`, `frequency`, `interval_value`, `start_date`, `end_date`, `notes`) |
| POST | `/app/recurring/pause` | Pause/resume schedule (`schedule_id`, `paused=1\|0`) |
| POST | `/app/recurring/skip` | Skip next occurrence (`schedule_id`) |
| POST | `/app/recurring/run` | Manually generate next occurrence now (`schedule_id`) |

### Transaction detail

| Method | Path | Description |
|--------|------|-------------|
| GET | `/app/transaction/{id}` | View/edit transaction detail |
| POST | `/app/transaction/{id}` | Update transaction (title, amount, notes, tags, wallet_id, category_id, date) |
| POST | `/app/transaction/{id}/delete` | Soft-delete transaction |
| POST | `/app/transaction/{id}/attach` | Upload attachment (multipart `attachment` file) |
| POST | `/app/transaction/{id}/attach-delete` | Delete attachment (`attachment_id`) |
| POST | `/app/transaction/{id}/child` | Add child transaction to a consolidated parent |

---

## Admin Panel — role: `super_admin`

### Overview & read-only pages

| Method | Path | Description |
|--------|------|-------------|
| GET | `/admin` | Overview: user count, transaction count, global savings, chart |
| GET | `/admin/transactions` | Transactions (filter: `user_id`, `from`, `to`, `type`) |
| GET | `/admin/users` | User list |
| GET | `/admin/categories` | Category list + inline edit/delete forms |
| GET | `/admin/rates` | Exchange rate list + delete form |
| GET | `/admin/reports` | Monthly summary table + CSV/PDF/heatmap links + charts |
| GET | `/admin/recurring` | All recurring schedules + create-for-user form (`?for_user={id}`) |
| GET | `/admin/notifications` | All notifications + broadcast form |
| GET | `/admin/audit` | Audit log (last 200 entries) |
| GET | `/admin/backups` | Backup list + run form + restore form |
| GET | `/admin/settings` | SMTP settings form + test email |

### User management

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/users` | Create user (`username`, `email`, `password`, `full_name`, `role`) |
| POST | `/admin/users/update` | Update user (`user_id`, `email`, `full_name`, `role`, `is_active`, `new_password`) |

### Category management

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/categories` | Create global category (`name`, `slug`, `type`, `color`, `icon`, `sort_order`) |
| POST | `/admin/categories/update` | Update global category (`category_id`, `name`, `slug`, `type`, `color`, `icon`, `sort_order`) |
| POST | `/admin/categories/delete` | Delete global category if not in use (`category_id`) |

### Exchange rate management

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/rates` | Add exchange rate (`from_currency_id`, `to_currency_id`, `rate`, `effective_date`) |
| POST | `/admin/rates/delete` | Delete an exchange rate row (`rate_id`) |

### Recurring (admin)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/recurring/run` | Generate next occurrence now (`schedule_id`, `user_id`) |
| POST | `/admin/recurring/create` | Create schedule for any user (`user_id` + same fields as `/app/recurring/new`) |

### Notifications (admin)

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/notifications/broadcast` | Send notification (`title`, `body`, `target_user_id`=0 for all users) |

### Backup & restore

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/backups/run` | Run `mysqldump` backup now; saves `.sql.gz` in `storage/backups/` |
| GET | `/admin/backups/download/{id}` | Download a backup archive |
| POST | `/admin/backups/restore` | Restore from existing backup or uploaded file (`backup_id` or `backup_file` upload, requires `confirm=RESTORE`) |

### System settings

| Method | Path | Description |
|--------|------|-------------|
| POST | `/admin/settings` | Save SMTP settings (`smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_encryption`) |
| POST | `/admin/settings/test-email` | Send a test email (`test_email`) |

---

## Export & Analytics APIs

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/reports/csv` | `super_admin` | CSV download of all non-deleted transactions |
| GET | `/api/reports/pdf` | `super_admin` | PDF monthly summary (`from`, `to` optional ISO date params) |
| GET | `/api/reports/heatmap` | Logged-in | JSON `{ year, expenses_by_date }` — super admin may pass `user_id` param |

---

## Cron Scripts (CLI only)

| Script | Schedule | Purpose |
|--------|----------|---------|
| `cron/recurring.php` | Hourly | Auto-generate due recurring transactions |
| `cron/low_balance.php` | Daily 08:00 | Email + in-app notifications for wallets below threshold |
| `cron/reminder_email.php` | Daily 07:00 | Email + in-app reminders for recurring due tomorrow |
| `cron/backup.php` | Daily 03:00 | Scheduled `mysqldump` backup |
| `cron/cleanup.php` | Daily 02:00 | Purge soft-deleted records, expired tokens, orphaned files, old audit rows |

---

## Notes

- All POST endpoints require a valid CSRF token in the form body (`_csrf_token` or configured key).
- Session timeout defaults to 120 minutes idle (configurable via `SESSION_LIFETIME` in `.env`).
- JSON `Accept: application/json` header returns JSON error bodies on future endpoints; current controllers return HTML redirects.
- File uploads (attachments) use `multipart/form-data`; max 10 MB per file; allowed: JPG, JPEG, PNG, PDF.
