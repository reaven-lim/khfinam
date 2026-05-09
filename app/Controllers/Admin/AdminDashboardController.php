<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Helpers\Auth;
use App\Helpers\Url;
use App\Helpers\View;
use App\Core\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use App\Services\RecurringService;
use PDO;

final class AdminDashboardController
{
    public function index(): void
    {
        $this->requireAdmin();
        $pdo = Database::pdo();
        $counts = [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'transactions' => (int) $pdo->query('SELECT COUNT(*) FROM transactions WHERE deleted_at IS NULL')->fetchColumn(),
        ];
        $totals = (new TransactionRepository())->globalTotals();

        View::renderLayout('admin', 'admin/dashboard', [
            'title' => 'Overview',
            'counts' => $counts,
            'totals' => $totals,
            'user' => Auth::user(),
        ]);
    }

    public function transactions(): void
    {
        $this->requireAdmin();
        $pdo = Database::pdo();
        $userId = (int) (Request::query('user_id', '0') ?? '0');
        $from = trim((string) (Request::query('from', '') ?? ''));
        $to = trim((string) (Request::query('to', '') ?? ''));
        $type = trim((string) (Request::query('type', '') ?? ''));

        $sql = 'SELECT t.*, u.username, w.name AS wallet_name FROM transactions t
            JOIN users u ON u.id = t.user_id JOIN wallets w ON w.id = t.wallet_id
            WHERE t.deleted_at IS NULL';
        $params = [];
        if ($userId > 0) {
            $sql .= ' AND t.user_id = ?';
            $params[] = $userId;
        }
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $sql .= ' AND t.transaction_date >= ?';
            $params[] = $from;
        }
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $sql .= ' AND t.transaction_date <= ?';
            $params[] = $to;
        }
        if ($type === 'income' || $type === 'expense') {
            $sql .= ' AND t.type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY t.transaction_date DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        View::renderLayout('admin', 'admin/transactions', [
            'title' => 'Transactions',
            'rows' => $rows,
            'users' => (new UserRepository())->all(500, 0),
            'filterUserId' => $userId,
            'filterFrom' => $from,
            'filterTo' => $to,
            'filterType' => $type,
            'user' => Auth::user(),
        ]);
    }

    public function notifications(): void
    {
        $this->requireAdmin();
        $stmt = Database::pdo()->query(
            'SELECT n.*, u.username, u.email FROM notifications n JOIN users u ON u.id = n.user_id ORDER BY n.id DESC LIMIT 150'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        View::renderLayout('admin', 'admin/notifications', [
            'title' => 'Notifications',
            'rows' => $rows,
            'users' => (new UserRepository())->all(500, 0),
            'message' => \App\Core\Session::getFlash('message'),
            'error' => \App\Core\Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function users(): void
    {
        $this->requireAdmin();
        $rows = (new UserRepository())->all(200, 0);
        View::renderLayout('admin', 'admin/users', [
            'title' => 'Users',
            'rows' => $rows,
            'user' => Auth::user(),
        ]);
    }

    public function settings(): void
    {
        $this->requireAdmin();
        $settings = (new SettingsRepository())->getGlobal();
        View::renderLayout('admin', 'admin/settings', [
            'title' => 'System settings',
            'settings' => $settings,
            'message' => \App\Core\Session::getFlash('message'),
            'error' => \App\Core\Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function categories(): void
    {
        $this->requireAdmin();
        $rows = Database::pdo()->query('SELECT * FROM categories ORDER BY type, sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
        View::renderLayout('admin', 'admin/categories', [
            'title' => 'Categories',
            'rows' => $rows,
            'message' => \App\Core\Session::getFlash('message'),
            'user' => Auth::user(),
        ]);
    }

    public function rates(): void
    {
        $this->requireAdmin();
        $pdo = Database::pdo();
        $rows = $pdo->query(
            'SELECT e.effective_date, e.rate, cf.code AS from_c, ct.code AS to_c, e.id
             FROM exchange_rates e
             JOIN currencies cf ON cf.id = e.from_currency_id
             JOIN currencies ct ON ct.id = e.to_currency_id
             ORDER BY e.effective_date DESC LIMIT 50'
        )->fetchAll(PDO::FETCH_ASSOC);
        $currencies = (new CurrencyRepository())->allActive();
        View::renderLayout('admin', 'admin/rates', [
            'title' => 'Exchange rates',
            'rows' => $rows,
            'currencies' => $currencies,
            'user' => Auth::user(),
        ]);
    }

    public function audit(): void
    {
        $this->requireAdmin();
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 200');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        View::renderLayout('admin', 'admin/audit', [
            'title' => 'Audit log',
            'rows' => $rows,
            'user' => Auth::user(),
        ]);
    }

    public function backups(): void
    {
        $this->requireAdmin();
        $rows = Database::pdo()->query('SELECT * FROM backups ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
        View::renderLayout('admin', 'admin/backups', [
            'title' => 'Backups',
            'rows' => $rows,
            'message' => \App\Core\Session::getFlash('message'),
            'error' => \App\Core\Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function recurring(): void
    {
        $this->requireAdmin();
        $pdo = Database::pdo();
        $rows = $pdo->query(
            'SELECT r.*, u.username, w.name AS wallet_name FROM recurring_schedules r
             JOIN users u ON u.id = r.user_id
             JOIN wallets w ON w.id = r.wallet_id
             ORDER BY r.next_occurrence ASC LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);
        $svc = new RecurringService();
        $preview = [];
        foreach ($rows as $rr) {
            $preview[$rr['id']] = $svc->previewOccurrences($rr, 3);
        }
        $forUid = (int) (Request::query('for_user', '0') ?? '0');
        $walletOptions = [];
        $categoriesIncome = [];
        $categoriesExpense = [];
        if ($forUid > 0) {
            $walletOptions = (new WalletRepository())->forUser($forUid);
            $categoriesIncome = (new CategoryRepository())->forUserIncludingGlobal($forUid, 'income');
            $categoriesExpense = (new CategoryRepository())->forUserIncludingGlobal($forUid, 'expense');
        }
        View::renderLayout('admin', 'admin/recurring', [
            'title' => 'Recurring schedules',
            'rows' => $rows,
            'preview' => $preview,
            'message' => \App\Core\Session::getFlash('message'),
            'error' => \App\Core\Session::getFlash('error'),
            'users' => (new UserRepository())->all(500, 0),
            'forUserId' => $forUid,
            'walletOptions' => $walletOptions,
            'categoriesIncome' => $categoriesIncome,
            'categoriesExpense' => $categoriesExpense,
            'currencies' => (new CurrencyRepository())->allActive(),
            'user' => Auth::user(),
        ]);
    }

    public function reports(): void
    {
        $this->requireAdmin();
        View::renderLayout('admin', 'admin/reports', [
            'title' => 'Reports',
            'user' => Auth::user(),
        ]);
    }

    private function requireAdmin(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
        }
        if (! Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/app'));
        }
    }
}
