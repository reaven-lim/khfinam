<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\View;
use App\Repositories\CurrencyRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTypeRepository;
use App\Services\ReportPdfService;
use App\Services\TransactionIntelligenceService;
use App\Services\WalletService;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $tx = new TransactionRepository();
        $totals = $tx->totalsForUser($uid);
        $wallets = (new WalletRepository())->forUser($uid);
        $recent = $tx->recentForUser($uid, 15);
        $ws = new WalletService();
        $low = $ws->walletBalancesForUser($uid);
        $warnings = array_values(array_filter($low, static fn (array $b): bool => $b['below']));
        $totalBalanceBase = array_sum(array_column($low, 'balance_base'));

        $pdo = Database::pdo();
        $up = $pdo->prepare(
            'SELECT r.*, w.name AS wallet_name FROM recurring_schedules r
             JOIN wallets w ON w.id = r.wallet_id
             WHERE r.user_id = ? AND r.is_paused = 0
               AND r.next_occurrence <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
               AND (r.end_date IS NULL OR r.end_date >= CURDATE())
             ORDER BY r.next_occurrence ASC
             LIMIT 12'
        );
        $up->execute([$uid]);
        $upcomingRecurring = $up->fetchAll(\PDO::FETCH_ASSOC);

        View::renderLayout('dashboard', 'dashboard/overview', [
            'title' => 'Overview',
            'totals' => $totals,
            'wallets' => $wallets,
            'recent' => $recent,
            'lowWarnings' => $warnings,
            'totalBalanceBase' => $totalBalanceBase,
            'upcomingRecurring' => $upcomingRecurring,
            'message' => Session::getFlash('message'),
            'user' => Auth::user(),
        ]);
    }

    public function transactions(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $from = trim((string) (Request::query('from', '') ?? ''));
        $to = trim((string) (Request::query('to', '') ?? ''));
        $type = trim((string) (Request::query('type', '') ?? ''));

        $intel = new TransactionIntelligenceService();
        $rows = $intel->filteredTransactionListing($uid, $from, $to, $type);

        View::renderLayout('dashboard', 'admin/transactions', array_merge([
            'title' => 'Transaction intelligence',
            'rows' => $rows,
            'users' => [],
            'filterUserId' => $uid,
            'filterFrom' => $from,
            'filterTo' => $to,
            'filterType' => $type,
            'user' => Auth::user(),
            'txLensBasePath' => '/dashboard/transactions',
            'txReportsPath' => '/dashboard/reports',
            'txShowUserLens' => false,
            'txShowUserColumn' => false,
        ], $intel->buildFilteredAnalyticsPayload($uid, $from, $to, $type)));
    }

    public function wallets(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $wallets = (new WalletRepository())->forUser($uid, false);
        $balances = (new WalletService())->walletBalancesForUser($uid);
        $map = [];
        foreach ($balances as $b) {
            $map[$b['wallet_id']] = $b;
        }

        $pdo = Database::pdo();
        $flow = $pdo->prepare(
            "SELECT w.id, w.name,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount_base ELSE 0 END), 0) AS exp,
                COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount_base ELSE 0 END), 0) AS inc
             FROM wallets w
             LEFT JOIN transactions t ON t.wallet_id = w.id AND t.user_id = w.user_id
               AND t.deleted_at IS NULL AND t.parent_transaction_id IS NULL
               AND COALESCE(t.is_internal_transfer,0) = 0
               AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             WHERE w.user_id = ?
             GROUP BY w.id, w.name
             ORDER BY w.sort_order ASC, w.id ASC"
        );
        $flow->execute([$uid]);
        $flowRows = $flow->fetchAll(\PDO::FETCH_ASSOC);

        View::renderLayout('dashboard', 'dashboard/wallets', [
            'title' => 'Wallet analytics',
            'wallets' => $wallets,
            'balances' => $map,
            'flowRows' => $flowRows,
            'walletTypes' => (new WalletTypeRepository())->allOrdered(true),
            'currencies' => (new CurrencyRepository())->allActive(),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function recurring(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT r.*, w.name AS wallet_name FROM recurring_schedules r
             JOIN wallets w ON w.id = r.wallet_id
             WHERE r.user_id = ?
             ORDER BY r.is_paused ASC, r.next_occurrence ASC'
        );
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $active = count(array_filter($rows, static fn (array $r): bool => empty((int) $r['is_paused'])));
        $paused = count($rows) - $active;

        View::renderLayout('dashboard', 'dashboard/recurring', [
            'title' => 'Recurring analytics',
            'rows' => $rows,
            'activeCount' => $active,
            'pausedCount' => $paused,
            'user' => Auth::user(),
        ]);
    }

    public function reports(): void
    {
        Auth::requireLogin();
        View::renderLayout('dashboard', 'dashboard/reports', [
            'title' => 'Reports',
            'user' => Auth::user(),
        ]);
    }

    public function reportsCsv(): void
    {
        Auth::requireLogin();
        $uid = (int) Auth::id();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT transaction_date, type, title, amount_base FROM transactions
             WHERE user_id = ? AND deleted_at IS NULL AND parent_transaction_id IS NULL
             ORDER BY transaction_date DESC'
        );
        $stmt->execute([$uid]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="my-transactions.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['transaction_date', 'type', 'title', 'amount_base']);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    public function reportsPdf(): void
    {
        Auth::requireLogin();
        $from = Request::query('from');
        $to = Request::query('to');
        $pdf = (new ReportPdfService())->monthlySummaryPdfForUser((int) Auth::id(), $from ?: null, $to ?: null);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="khfinam-my-report.pdf"');
        echo $pdf;
        exit;
    }

    public function notifications(): void
    {
        Auth::requireLogin();
        $stmt = Database::pdo()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
        $stmt->execute([(int) Auth::id()]);
        View::renderLayout('dashboard', 'dashboard/notifications', [
            'title' => 'Notifications',
            'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'user' => Auth::user(),
        ]);
    }
}
