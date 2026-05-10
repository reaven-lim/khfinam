<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;
use App\Repositories\SettingsRepository;
use App\Repositories\UserRepository;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\DatabaseRestoreService;
use App\Services\MailService;
use App\Services\RecurringService;
use App\Services\WalletService;
use App\Helpers\WalletTypeUi;
use App\Repositories\WalletTypeRepository;
use PDO;

final class AdminFormController
{
    public function saveSettings(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/settings'));
        }
        $repo = new SettingsRepository();
        $repo->setManyGlobal([
            'smtp_host' => trim((string) (Request::post()['smtp_host'] ?? '')),
            'smtp_port' => trim((string) (Request::post()['smtp_port'] ?? '587')),
            'smtp_user' => trim((string) (Request::post()['smtp_user'] ?? '')),
            'smtp_pass' => (string) (Request::post()['smtp_pass'] ?? ''),
            'smtp_encryption' => trim((string) (Request::post()['smtp_encryption'] ?? 'tls')),
        ]);
        AuditLogger::log('settings_update', Auth::id(), 'settings', 'global');
        Session::flash('message', 'Settings saved.');
        Response::redirect(Url::to('/admin/settings'));
    }

    public function testEmail(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/settings'));
        }
        $to = trim((string) (Request::post()['test_email'] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Valid test email required.');
            Response::redirect(Url::to('/admin/settings'));
        }
        try {
            (new MailService())->send($to, 'KHFinaM test email', 'SMTP configuration test OK.');
            Session::flash('message', 'Test email queued/sent.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Mail error: ' . $e->getMessage());
        }
        Response::redirect(Url::to('/admin/settings'));
    }

    public function userStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/users'));
        }
        $u = trim((string) (Request::post()['username'] ?? ''));
        $e = trim((string) (Request::post()['email'] ?? ''));
        $p = (string) (Request::post()['password'] ?? '');
        $role = (string) (Request::post()['role'] ?? 'user');
        if ($u === '' || $e === '' || strlen($p) < 8) {
            Session::flash('error', 'Username, email and password (8+ chars) required.');
            Response::redirect(Url::to('/admin/users'));
        }
        if (! in_array($role, ['user', 'super_admin'], true)) {
            $role = 'user';
        }
        $repo = new UserRepository();
        $newId = $repo->create([
            'username' => $u,
            'email' => $e,
            'password_hash' => password_hash($p, PASSWORD_DEFAULT),
            'full_name' => trim((string) (Request::post()['full_name'] ?? '')) ?: null,
            'role' => $role,
            'is_active' => 1,
            'include_in_analytics' => self::parseIncludeInAnalyticsPost(Request::post(), defaultIfMissing: true),
        ]);
        AuditLogger::log('user_create', Auth::id(), 'user', $u);
        Session::flash('message', 'User created.');
        Response::redirect(Url::to('/admin/users/' . $newId));
    }

    public function userUpdate(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/users'));
        }
        $id = (int) (Request::post()['user_id'] ?? 0);
        $repo = new UserRepository();
        $cur = $repo->findByIdAny($id);
        if (! $cur) {
            Session::flash('error', 'User not found.');
            Response::redirect(Url::to('/admin/users'));
        }
        $me = Auth::id();
        if ($me !== null && $id === $me) {
            if (empty(Request::post()['is_active'])) {
                Session::flash('error', 'You cannot deactivate your own account.');
                Response::redirect(Url::to('/admin/users/' . $id));
            }
            $roleProbe = (string) (Request::post()['role'] ?? $cur['role']);
            if ($roleProbe !== 'super_admin') {
                Session::flash('error', 'You cannot remove your own super admin role.');
                Response::redirect(Url::to('/admin/users/' . $id));
            }
        }
        $role = (string) (Request::post()['role'] ?? $cur['role']);
        if (! in_array($role, ['user', 'super_admin'], true)) {
            $role = 'user';
        }
        $curIncludeDefault = filter_var($cur['include_in_analytics'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $repo->update($id, [
            'email' => trim((string) (Request::post()['email'] ?? $cur['email'])),
            'full_name' => trim((string) (Request::post()['full_name'] ?? '')),
            'role' => $role,
            'is_active' => ! empty(Request::post()['is_active']),
            'include_in_analytics' => self::parseIncludeInAnalyticsPost(Request::post(), $curIncludeDefault),
        ]);
        $newPass = (string) (Request::post()['new_password'] ?? '');
        if ($newPass !== '' && strlen($newPass) >= 8) {
            $repo->updatePassword($id, password_hash($newPass, PASSWORD_DEFAULT));
        }
        AuditLogger::log('user_update', Auth::id(), 'user', (string) $id);
        Session::flash('message', 'User updated.');
        $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
        if ($redirect === 'detail') {
            Response::redirect(Url::to('/admin/users/' . $id));
        }
        Response::redirect(Url::to('/admin/users'));
    }

    public function userSetStatus(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/users'));
        }
        $id = (int) (Request::post()['user_id'] ?? 0);
        $wantActive = ! empty(Request::post()['is_active']);
        if ($id <= 0) {
            Session::flash('error', 'Invalid user.');
            Response::redirect(Url::to('/admin/users'));
        }
        $me = Auth::id();
        if ($me !== null && $id === $me && ! $wantActive) {
            Session::flash('error', 'You cannot suspend your own account.');
            $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
            Response::redirect($redirect === 'detail' ? Url::to('/admin/users/' . $id) : Url::to('/admin/users'));
        }
        $repo = new UserRepository();
        $cur = $repo->findByIdAny($id);
        if (! $cur) {
            Session::flash('error', 'User not found.');
            Response::redirect(Url::to('/admin/users'));
        }
        $repo->update($id, ['is_active' => $wantActive]);
        AuditLogger::log('user_update', Auth::id(), 'user', (string) $id);
        Session::flash('message', $wantActive ? 'User activated.' : 'User suspended.');
        $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
        if ($redirect === 'detail') {
            Response::redirect(Url::to('/admin/users/' . $id));
        }
        Response::redirect(Url::to('/admin/users'));
    }

    public function userPrefs(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/users'));
        }
        $id = (int) (Request::post()['user_id'] ?? 0);
        $repo = new UserRepository();
        if ($id <= 0 || ! $repo->findByIdAny($id)) {
            Session::flash('error', 'User not found.');
            Response::redirect(Url::to('/admin/users'));
        }
        $theme = trim((string) (Request::post()['preference_theme'] ?? 'system'));
        $mute = ! empty(Request::post()['preference_mute_low_balance']);
        $repo->updateNotificationThemePreferences($id, $theme, $mute);
        AuditLogger::log('user_update', Auth::id(), 'user', (string) $id);
        Session::flash('message', 'Notification preferences saved.');
        Response::redirect(Url::to('/admin/users/' . $id));
    }

    public function categoryStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/categories'));
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO categories (user_id, name, slug, type, color, icon, is_system, sort_order) VALUES (NULL,?,?,?,?,?,1,?)'
        )->execute([
            trim((string) (Request::post()['name'] ?? '')),
            trim((string) (Request::post()['slug'] ?? '')) ?: null,
            Request::post()['type'] === 'income' ? 'income' : 'expense',
            trim((string) (Request::post()['color'] ?? '#6366f1')),
            trim((string) (Request::post()['icon'] ?? 'category')),
            (int) (Request::post()['sort_order'] ?? 0),
        ]);
        AuditLogger::log('category_create', Auth::id(), 'category', (string) $pdo->lastInsertId());
        Session::flash('message', 'Category added.');
        Response::redirect(Url::to('/admin/categories'));
    }

    public function rateStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/rates'));
        }
        $from = (int) (Request::post()['from_currency_id'] ?? 0);
        $to = (int) (Request::post()['to_currency_id'] ?? 0);
        $rate = (float) (Request::post()['rate'] ?? 0);
        $date = (string) (Request::post()['effective_date'] ?? date('Y-m-d'));
        if ($from <= 0 || $to <= 0 || $rate <= 0) {
            Session::flash('error', 'Invalid rate data.');
            Response::redirect(Url::to('/admin/rates'));
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO exchange_rates (from_currency_id, to_currency_id, rate, effective_date) VALUES (?,?,?,?)'
        )->execute([$from, $to, $rate, $date]);
        AuditLogger::log('rate_create', Auth::id(), 'exchange_rate', (string) $pdo->lastInsertId());
        Session::flash('message', 'Rate added.');
        Response::redirect(Url::to('/admin/rates'));
    }

    public function backupRun(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/backups'));
        }
        try {
            (new BackupService())->createSqlGz(Auth::id());
            Session::flash('message', 'Backup created.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/backups'));
    }

    public function backupDownload(int $id): void
    {
        $this->guard();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT filename FROM backups WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $name = $stmt->fetchColumn();
        if (! $name || ! is_string($name)) {
            Response::abort(404);
        }
        $path = dirname(__DIR__, 3) . '/storage/backups/' . basename((string) $name);
        if (! is_file($path)) {
            Response::abort(404);
        }
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    public function recurringRun(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/recurring'));
        }
        $sid = (int) (Request::post()['schedule_id'] ?? 0);
        $uid = (int) (Request::post()['user_id'] ?? 0);
        try {
            (new RecurringService())->runOne($uid, $sid);
            Session::flash('message', 'Occurrence generated.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/recurring'));
    }

    public function recurringCreate(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/recurring'));
        }
        $targetUid = (int) (Request::post()['user_id'] ?? 0);
        $user = (new UserRepository())->findByIdAny($targetUid);
        if (! $user || empty($user['is_active'])) {
            Session::flash('error', 'Invalid user.');
            Response::redirect(Url::to('/admin/recurring'));
        }
        try {
            (new RecurringService())->createSchedule($targetUid, [
                'wallet_id' => (int) (Request::post()['wallet_id'] ?? 0),
                'category_id' => (int) (Request::post()['category_id'] ?? 0),
                'type' => (string) (Request::post()['type'] ?? 'expense'),
                'title' => trim((string) (Request::post()['title'] ?? '')),
                'amount' => (float) (Request::post()['amount'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'frequency' => (string) (Request::post()['frequency'] ?? 'monthly'),
                'interval_value' => (int) (Request::post()['interval_value'] ?? 1),
                'start_date' => (string) (Request::post()['start_date'] ?? date('Y-m-d')),
                'end_date' => (string) (Request::post()['end_date'] ?? ''),
                'notes' => trim((string) (Request::post()['notes'] ?? '')) ?: null,
            ]);
            Session::flash('message', 'Schedule created for user.');
            AuditLogger::log('recurring_create_admin', Auth::id(), 'recurring_schedule', (string) $targetUid);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/recurring') . '?for_user=' . $targetUid);
    }

    public function notificationBroadcast(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/notifications'));
        }
        $title = trim((string) (Request::post()['title'] ?? ''));
        $body = trim((string) (Request::post()['body'] ?? ''));
        $target = (int) (Request::post()['target_user_id'] ?? 0);
        if ($title === '') {
            Session::flash('error', 'Title required.');
            Response::redirect(Url::to('/admin/notifications'));
        }
        $pdo = Database::pdo();
        if ($target > 0) {
            $pdo->prepare('INSERT INTO notifications (user_id, type, title, body) VALUES (?,?,?,?)')
                ->execute([$target, 'info', $title, $body !== '' ? $body : null]);
        } else {
            $ids = $pdo->query('SELECT id FROM users WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
            $ins = $pdo->prepare('INSERT INTO notifications (user_id, type, title, body) VALUES (?,?,?,?)');
            foreach ($ids as $uid) {
                $ins->execute([(int) $uid, 'info', $title, $body !== '' ? $body : null]);
            }
        }
        AuditLogger::log('notification_broadcast', Auth::id(), 'notifications', $target > 0 ? (string) $target : 'all');
        Session::flash('message', 'Notification sent.');
        Response::redirect(Url::to('/admin/notifications'));
    }

    public function categoryUpdate(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/categories'));
        }
        $id = (int) (Request::post()['category_id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Invalid category.');
            Response::redirect(Url::to('/admin/categories'));
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'UPDATE categories SET name = ?, slug = ?, type = ?, color = ?, icon = ?, sort_order = ? WHERE id = ? AND user_id IS NULL'
        );
        $stmt->execute([
            trim((string) (Request::post()['name'] ?? '')),
            trim((string) (Request::post()['slug'] ?? '')) ?: null,
            Request::post()['type'] === 'income' ? 'income' : 'expense',
            trim((string) (Request::post()['color'] ?? '#6366f1')),
            trim((string) (Request::post()['icon'] ?? 'category')),
            (int) (Request::post()['sort_order'] ?? 0),
            $id,
        ]);
        if ($stmt->rowCount() === 0) {
            Session::flash('error', 'Only global categories can be edited here.');
        } else {
            AuditLogger::log('category_update', Auth::id(), 'category', (string) $id);
            Session::flash('message', 'Category updated.');
        }
        Response::redirect(Url::to('/admin/categories'));
    }

    public function categoryDelete(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/categories'));
        }
        $id = (int) (Request::post()['category_id'] ?? 0);
        $pdo = Database::pdo();
        $c = $pdo->prepare('SELECT COUNT(*) FROM transactions WHERE category_id = ? AND deleted_at IS NULL');
        $c->execute([$id]);
        if ((int) $c->fetchColumn() > 0) {
            Session::flash('error', 'Category is used by transactions; cannot delete.');
            Response::redirect(Url::to('/admin/categories'));
        }
        $del = $pdo->prepare('DELETE FROM categories WHERE id = ? AND user_id IS NULL');
        $del->execute([$id]);
        $removed = $del->rowCount() > 0;
        Session::flash('message', $removed ? 'Category deleted.' : 'Nothing deleted (not a global category?).');
        if ($removed) {
            AuditLogger::log('category_delete', Auth::id(), 'category', (string) $id);
        }
        Response::redirect(Url::to('/admin/categories'));
    }

    public function rateDelete(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/rates'));
        }
        $id = (int) (Request::post()['rate_id'] ?? 0);
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM exchange_rates WHERE id = ?')->execute([$id]);
        AuditLogger::log('rate_delete', Auth::id(), 'exchange_rate', (string) $id);
        Session::flash('message', 'Rate removed.');
        Response::redirect(Url::to('/admin/rates'));
    }

    public function backupRestore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/backups'));
        }
        if (trim((string) (Request::post()['confirm'] ?? '')) !== 'RESTORE') {
            Session::flash('error', 'Type RESTORE in the confirmation box to overwrite the database.');
            Response::redirect(Url::to('/admin/backups'));
        }
        $root = dirname(__DIR__, 3);
        $restore = new DatabaseRestoreService();
        $bid = (int) (Request::post()['backup_id'] ?? 0);
        $upload = $_FILES['backup_file'] ?? null;

        try {
            if ($bid > 0) {
                $pdo = Database::pdo();
                $stmt = $pdo->prepare('SELECT filename FROM backups WHERE id = ? LIMIT 1');
                $stmt->execute([$bid]);
                $name = $stmt->fetchColumn();
                if (! $name || ! is_string($name)) {
                    throw new \InvalidArgumentException('Backup record not found.');
                }
                $path = $root . '/storage/backups/' . basename($name);
                if (! is_file($path)) {
                    throw new \InvalidArgumentException('Backup file missing on disk.');
                }
                if (str_ends_with(strtolower($path), '.gz')) {
                    $restore->restoreFromGzFile($path);
                } else {
                    $restore->restoreFromFile($path);
                }
            } elseif (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tmp = (string) $upload['tmp_name'];
                $fn = strtolower((string) ($upload['name'] ?? ''));
                if (str_ends_with($fn, '.gz')) {
                    $restore->restoreFromGzFile($tmp);
                } else {
                    $restore->restoreFromFile($tmp);
                }
            } else {
                throw new \InvalidArgumentException('Select an existing backup or upload a .sql / .sql.gz file.');
            }
            Session::flash('message', 'Database restored. You may need to log in again.');
            AuditLogger::log('backup_restore', Auth::id(), 'backup', (string) $bid);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/backups'));
    }

    public function walletStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        $targetUser = (int) (Request::post()['user_id'] ?? 0);
        if ($targetUser <= 0) {
            Session::flash('error', 'Select a user.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        try {
            $newId = (new WalletService())->createWallet($targetUser, [
                'name' => trim((string) (Request::post()['name'] ?? '')),
                'wallet_type_id' => (int) (Request::post()['wallet_type_id'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'opening_balance' => (float) (Request::post()['opening_balance'] ?? 0),
                'min_balance_threshold' => Request::post()['min_balance_threshold'] ?? '',
                'is_default' => ! empty(Request::post()['is_default']),
                'is_active' => ! empty(Request::post()['is_active']),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
            ], true);
            AuditLogger::log('wallet_create_admin', Auth::id(), 'user', (string) $targetUser);
            Session::flash('message', 'Wallet created.');
            Response::redirect(Url::to('/admin/wallets/' . $newId));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/wallets'));
    }

    public function walletUpdate(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        $owner = (int) (Request::post()['wallet_user_id'] ?? 0);
        $wid = (int) (Request::post()['wallet_id'] ?? 0);
        if ($owner <= 0 || $wid <= 0) {
            Session::flash('error', 'Invalid wallet.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        try {
            (new WalletService())->updateWalletForOwner($owner, $wid, [
                'name' => trim((string) (Request::post()['name'] ?? '')),
                'wallet_type_id' => (int) (Request::post()['wallet_type_id'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'opening_balance' => (float) (Request::post()['opening_balance'] ?? 0),
                'min_balance_threshold' => Request::post()['min_balance_threshold'] ?? '',
                'is_default' => ! empty(Request::post()['is_default']),
                'is_active' => ! empty(Request::post()['is_active']),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
            ], true);
            AuditLogger::log('wallet_update_admin', Auth::id(), 'wallet', (string) $wid);
            Session::flash('message', 'Wallet saved.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
        $returnQuery = trim((string) (Request::post()['_return_query'] ?? ''));
        if ($redirect === 'detail') {
            Response::redirect(Url::to('/admin/wallets/' . $wid));
        }
        $suffix = ($returnQuery !== '' && $returnQuery !== '0') ? ('?' . $returnQuery) : '';
        Response::redirect(Url::to('/admin/wallets') . $suffix);
    }

    public function walletSetStatus(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        $owner = (int) (Request::post()['wallet_user_id'] ?? 0);
        $wid = (int) (Request::post()['wallet_id'] ?? 0);
        $wantActive = ! empty(Request::post()['is_active']);
        $svc = new WalletService();
        try {
            if ($wantActive) {
                $svc->activateWalletForOwner($owner, $wid);
            } else {
                $svc->deactivateWalletForOwner($owner, $wid);
            }
            AuditLogger::log('wallet_update_admin', Auth::id(), 'wallet', (string) $wid);
            Session::flash('message', $wantActive ? 'Wallet activated.' : 'Wallet deactivated.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
        $returnQuery = trim((string) (Request::post()['_return_query'] ?? ''));
        if ($redirect === 'detail') {
            Response::redirect(Url::to('/admin/wallets/' . $wid));
        }
        $suffix = ($returnQuery !== '' && $returnQuery !== '0') ? ('?' . $returnQuery) : '';
        Response::redirect(Url::to('/admin/wallets') . $suffix);
    }

    public function walletDelete(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallets'));
        }
        $owner = (int) (Request::post()['wallet_user_id'] ?? 0);
        $wid = (int) (Request::post()['wallet_id'] ?? 0);
        try {
            (new WalletService())->deleteWalletForOwner($owner, $wid);
            AuditLogger::log('wallet_delete_admin', Auth::id(), 'wallet', (string) $wid);
            Session::flash('message', 'Wallet removed (no ledger history was attached).');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $returnQuery = trim((string) (Request::post()['_return_query'] ?? ''));
        $suffix = ($returnQuery !== '' && $returnQuery !== '0') ? ('?' . $returnQuery) : '';
        Response::redirect(Url::to('/admin/wallets') . $suffix);
    }

    public function walletTypeStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $label = trim((string) (Request::post()['label'] ?? ''));
        $manualId = trim((string) (Request::post()['internal_id_manual'] ?? ''));
        $typeRepo = new WalletTypeRepository();

        if ($label === '') {
            Session::flash('error', 'Please enter a wallet type name.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        try {
            $slug = WalletTypeUi::resolveNewSlug($typeRepo, $label, $manualId !== '' ? $manualId : null);
        } catch (\InvalidArgumentException $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        try {
            $icon = WalletTypeUi::sanitizeIcon(Request::post()['icon'] ?? 'wallet', null);
            $newId = $typeRepo->create([
                'slug' => $slug,
                'label' => $label,
                'icon' => $icon,
                'sort_order' => (int) (Request::post()['sort_order'] ?? 50),
                'is_active' => ! empty(Request::post()['is_active']),
                'is_system' => false,
            ]);
            AuditLogger::log('wallet_type_create', Auth::id(), 'wallet_type', $slug);
            Session::flash('message', 'Wallet type added.');
            Response::redirect(Url::to('/admin/wallet-types/' . $newId));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/wallet-types'));
    }

    public function walletTypeUpdate(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $id = (int) (Request::post()['type_id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Invalid type.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $typeRepo = new WalletTypeRepository();
        $existing = $typeRepo->findById($id);
        if ($existing === null) {
            Session::flash('error', 'Wallet type not found.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $legacyIcon = (string) ($existing['icon'] ?? 'wallet');
        try {
            $typeRepo->update($id, [
                'label' => trim((string) (Request::post()['label'] ?? '')),
                'icon' => WalletTypeUi::sanitizeIcon(Request::post()['icon'] ?? 'wallet', $legacyIcon),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
                'is_active' => ! empty(Request::post()['is_active']),
            ]);
            AuditLogger::log('wallet_type_update', Auth::id(), 'wallet_type', (string) $id);
            Session::flash('message', 'Wallet type updated.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $redirect = trim((string) (Request::post()['_redirect'] ?? ''));
        if ($redirect === 'detail') {
            Response::redirect(Url::to('/admin/wallet-types/' . $id));
        }
        Response::redirect(Url::to('/admin/wallet-types'));
    }

    public function walletTypeSetStatus(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $id = (int) (Request::post()['type_id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Invalid type.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $wantActive = ! empty(Request::post()['is_active']);
        try {
            (new WalletTypeRepository())->setActive($id, $wantActive);
            AuditLogger::log('wallet_type_update', Auth::id(), 'wallet_type', (string) $id);
            Session::flash('message', $wantActive ? 'Wallet type enabled.' : 'Wallet type disabled.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $red = trim((string) (Request::post()['_redirect'] ?? ''));
        if ($red === 'detail') {
            Response::redirect(Url::to('/admin/wallet-types/' . $id));
        }
        Response::redirect(Url::to('/admin/wallet-types'));
    }

    public function walletTypeDelete(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/admin/wallet-types'));
        }
        $id = (int) (Request::post()['type_id'] ?? 0);
        try {
            (new WalletTypeRepository())->deleteCustom($id);
            AuditLogger::log('wallet_type_delete', Auth::id(), 'wallet_type', (string) $id);
            Session::flash('message', 'Wallet type removed.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/admin/wallet-types'));
    }

    private function guard(): void
    {
        Auth::requireSuperAdmin();
    }

    /** Reads `include_in_analytics` after optional hidden-field + checkbox pairing (never use empty() on "0"). */
    private static function parseIncludeInAnalyticsPost(array $post, bool $defaultIfMissing): bool
    {
        if (! array_key_exists('include_in_analytics', $post)) {
            return $defaultIfMissing;
        }
        $raw = $post['include_in_analytics'];
        if (is_array($raw)) {
            $raw = end($raw);
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
