<?php

declare(strict_types=1);

namespace App\Controllers\Mobile;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\CategoryRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\TransactionService;
use App\Services\WalletService;

final class MobileAppController
{
    public function home(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));

            return;
        }
        Response::redirect(Url::to(Auth::isSuperAdmin() ? '/admin' : '/app'));
    }

    public function dashboard(): void
    {
        $this->requireUser();
        $uid = (int) Auth::id();
        $tx = new TransactionRepository();
        $totals = $tx->totalsForUser($uid);
        $wallets = (new WalletRepository())->forUser($uid);
        $recent = $tx->recentForUser($uid, 15);
        $ws = new WalletService();
        $low = $ws->walletBalancesForUser($uid);
        $warnings = array_values(array_filter($low, static fn (array $b): bool => $b['below']));
        $totalBalanceBase = array_sum(array_column($low, 'balance_base'));

        $pdo = \App\Core\Database::pdo();
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

        View::renderLayout('mobile', 'mobile/dashboard', [
            'title' => 'Dashboard',
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

    public function add(): void
    {
        $this->requireUser();
        if (Request::method() === 'POST') {
            $this->addSave();

            return;
        }
        $uid = (int) Auth::id();
        View::renderLayout('mobile', 'mobile/add', [
            'title' => 'Add transaction',
            'user' => Auth::user(),
            'wallets' => (new WalletRepository())->forUser($uid),
            'categoriesIncome' => (new CategoryRepository())->forUserIncludingGlobal($uid, 'income'),
            'categoriesExpense' => (new CategoryRepository())->forUserIncludingGlobal($uid, 'expense'),
            'error' => Session::getFlash('error'),
        ]);
    }

    private function addSave(): void
    {
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session. Try again.');
            Response::redirect(Url::to('/app/add'));
        }
        try {
            $svc = new TransactionService();
            $tagsRaw = trim((string) (Request::post()['tags'] ?? ''));
            $tags = $tagsRaw !== '' ? array_filter(array_map('trim', explode(',', $tagsRaw))) : [];
            $id = $svc->createForUser((int) Auth::id(), [
                'type' => (string) (Request::post()['type'] ?? 'expense'),
                'title' => trim((string) (Request::post()['title'] ?? '')),
                'amount' => (float) (Request::post()['amount'] ?? 0),
                'wallet_id' => (int) (Request::post()['wallet_id'] ?? 0),
                'category_id' => (int) (Request::post()['category_id'] ?? 0),
                'transaction_date' => (string) (Request::post()['transaction_date'] ?? date('Y-m-d')),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'is_consolidated_parent' => ! empty(Request::post()['is_consolidated_parent']),
                'tags' => $tags,
            ]);
            Response::redirect(Url::to('/app'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/add'));
        }
    }

    public function recurring(): void
    {
        $this->requireUser();
        $pdo = \App\Core\Database::pdo();
        $stmt = $pdo->prepare('SELECT r.*, w.name AS wallet_name FROM recurring_schedules r JOIN wallets w ON w.id = r.wallet_id WHERE r.user_id = ? ORDER BY r.next_occurrence ASC');
        $stmt->execute([(int) Auth::id()]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        View::renderLayout('mobile', 'mobile/recurring', [
            'title' => 'Recurring',
            'rows' => $rows,
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function wallets(): void
    {
        $this->requireUser();
        $uid = (int) Auth::id();
        $wallets = (new WalletRepository())->forUser($uid);
        $balances = (new WalletService())->walletBalancesForUser($uid);
        $map = [];
        foreach ($balances as $b) {
            $map[$b['wallet_id']] = $b;
        }
        View::renderLayout('mobile', 'mobile/wallets', [
            'title' => 'Wallets',
            'wallets' => $wallets,
            'balances' => $map,
            'currencies' => (new CurrencyRepository())->allActive(),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function stats(): void
    {
        $this->requireUser();
        $uid = (int) Auth::id();
        $pdo = \App\Core\Database::pdo();
        $totals = (new TransactionRepository())->totalsForUser($uid);

        // 12-month income vs expense
        $s = $pdo->prepare(
            "SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS ym,
                    SUM(CASE WHEN type='income'  AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
                    SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
             FROM transactions
             WHERE user_id=? AND deleted_at IS NULL AND parent_transaction_id IS NULL
             GROUP BY ym ORDER BY ym ASC LIMIT 12"
        );
        $s->execute([$uid]);
        $monthly = $s->fetchAll(\PDO::FETCH_ASSOC);

        // Top 6 expense categories
        $s2 = $pdo->prepare(
            "SELECT c.name, COALESCE(SUM(t.amount_base),0) AS total
             FROM transactions t JOIN categories c ON c.id=t.category_id
             WHERE t.user_id=? AND t.deleted_at IS NULL AND t.type='expense'
               AND COALESCE(t.is_internal_transfer,0)=0 AND t.parent_transaction_id IS NULL
             GROUP BY c.id, c.name ORDER BY total DESC LIMIT 6"
        );
        $s2->execute([$uid]);
        $topCats = $s2->fetchAll(\PDO::FETCH_ASSOC);

        // Wallet balances
        $walletBalances = (new WalletService())->walletBalancesForUser($uid);

        // Spending trend (last 30 days)
        $s3 = $pdo->prepare(
            "SELECT transaction_date AS d, COALESCE(SUM(amount_base),0) AS s
             FROM transactions
             WHERE user_id=? AND deleted_at IS NULL AND type='expense'
               AND COALESCE(is_internal_transfer,0)=0 AND parent_transaction_id IS NULL
               AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY transaction_date ORDER BY transaction_date ASC"
        );
        $s3->execute([$uid]);
        $trend = $s3->fetchAll(\PDO::FETCH_ASSOC);

        View::renderLayout('mobile', 'mobile/stats', [
            'title' => 'Statistics',
            'totals' => $totals,
            'monthly' => $monthly,
            'topCats' => $topCats,
            'walletBalances' => $walletBalances,
            'trend' => $trend,
            'user' => Auth::user(),
        ]);
    }

    public function notifications(): void
    {
        $this->requireUser();
        $stmt = \App\Core\Database::pdo()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 50');
        $stmt->execute([(int) Auth::id()]);
        View::renderLayout('mobile', 'mobile/notifications', [
            'title' => 'Notifications',
            'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'user' => Auth::user(),
        ]);
    }

    public function profile(): void
    {
        $this->requireUser();
        View::renderLayout('mobile', 'mobile/profile', [
            'title' => 'Profile',
            'user' => Auth::user(),
        ]);
    }

    private function requireUser(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
        }
        if (Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/admin'));
        }
    }
}
