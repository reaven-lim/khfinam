<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\View;
use App\Core\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WalletTypeRepository;
use App\Services\RecurringService;
use App\Services\TransactionIntelligenceService;
use App\Services\WalletService;
use PDO;

final class AdminDashboardController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        $pdo = Database::pdo();
        $an = '(' . UserRepository::analyticsIncludedUserIdsSubquery() . ')';
        $counts = [
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE include_in_analytics = 1')->fetchColumn(),
            'users_all' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'transactions' => (int) $pdo->query(
                "SELECT COUNT(*) FROM transactions WHERE deleted_at IS NULL AND user_id IN {$an}"
            )->fetchColumn(),
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
        Auth::requireSuperAdmin();
        $userId = (int) (Request::query('user_id', '0') ?? '0');
        $from = trim((string) (Request::query('from', '') ?? ''));
        $to = trim((string) (Request::query('to', '') ?? ''));
        $type = trim((string) (Request::query('type', '') ?? ''));

        $intel = new TransactionIntelligenceService();
        $rows = $intel->filteredTransactionListing($userId, $from, $to, $type);

        View::renderLayout('admin', 'admin/transactions', array_merge([
            'title' => 'Transaction intelligence',
            'rows' => $rows,
            'users' => (new UserRepository())->all(500, 0),
            'filterUserId' => $userId,
            'filterFrom' => $from,
            'filterTo' => $to,
            'filterType' => $type,
            'user' => Auth::user(),
        ], $intel->buildFilteredAnalyticsPayload($userId, $from, $to, $type)));
    }

    public function notifications(): void
    {
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
        $userRepo = new UserRepository();
        $kpis = $userRepo->adminUserManagementKpis();
        $walletSvc = new WalletService();
        $pdo = Database::pdo();
        $lowBalanceUsers = 0;
        $activeIds = $pdo->query('SELECT id FROM users WHERE is_active = 1 AND include_in_analytics = 1')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($activeIds as $rawId) {
            if ($walletSvc->hasLowBalanceWallet((int) $rawId)) {
                $lowBalanceUsers++;
            }
        }
        $analyticsFilter = trim((string) (Request::query('analytics', 'all') ?? 'all'));
        if (! in_array($analyticsFilter, ['all', 'included', 'excluded'], true)) {
            $analyticsFilter = 'all';
        }
        $rows = $userRepo->adminUserDirectoryRows(500, 0, $analyticsFilter);
        foreach ($rows as $i => $r) {
            $uid = (int) $r['id'];
            $rows[$i]['total_balance_base'] = $walletSvc->totalBalanceBaseForUser($uid);
            $rows[$i]['has_low_balance'] = $walletSvc->hasLowBalanceWallet($uid);
        }
        View::renderLayout('admin', 'admin/users', [
            'title' => 'User management',
            'rows' => $rows,
            'userKpis' => array_merge($kpis, ['low_balance_users' => $lowBalanceUsers]),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'adminSelfId' => Auth::id(),
            'analyticsFilter' => $analyticsFilter,
            'user' => Auth::user(),
        ]);
    }

    public function userShow(string $id): void
    {
        Auth::requireSuperAdmin();
        $userId = (int) $id;
        if ($userId <= 0) {
            Response::abort(404);
        }
        $userRepo = new UserRepository();
        $profile = $userRepo->findByIdAny($userId);
        if ($profile === null) {
            Response::abort(404);
        }
        $txnRepo = new TransactionRepository();
        $walletRepo = new WalletRepository();
        $walletSvc = new WalletService();
        $balances = $walletSvc->walletBalancesForUser($userId);
        $wallets = $walletRepo->forUser($userId, false);
        $walletRows = [];
        foreach ($wallets as $w) {
            $wid = (int) $w['id'];
            $bal = null;
            foreach ($balances as $b) {
                if ((int) $b['wallet_id'] === $wid) {
                    $bal = $b;
                    break;
                }
            }
            $walletRows[] = array_merge($w, [
                'balance_base' => $bal !== null ? (float) $bal['balance_base'] : 0.0,
                'below_threshold' => $bal !== null && ! empty($bal['below']),
            ]);
        }
        $totalsAll = $txnRepo->totalsForUser($userId, null, null, true);
        $monthly = $txnRepo->monthlyIncomeExpenseSeriesForUser($userId, 6);
        $transferStats = $txnRepo->transferStatsForUser($userId, 90);
        $expenseCats = $txnRepo->expenseCategoryTotalsForUser($userId, 8);
        $recentTx = $txnRepo->recentForUser($userId, 12);
        $auditRows = $userRepo->auditLogsRelatedToUser($userId, (string) ($profile['username'] ?? ''), 45);
        $recurringCount = $walletRepo->recurringActiveCountForUser($userId);
        $incTotal = (float) $totalsAll['income'];
        $savingsRate = $incTotal > 0
            ? round(($incTotal - (float) $totalsAll['expense']) / $incTotal * 100, 1)
            : 0.0;
        $donutWalletLabels = [];
        $donutWalletSeries = [];
        foreach ($walletRows as $wr) {
            $donutWalletLabels[] = (string) $wr['name'];
            $donutWalletSeries[] = round(max(0.0, (float) $wr['balance_base']), 2);
        }
        $donutCatLabels = array_column($expenseCats, 'name');
        $donutCatSeries = array_map(static fn (array $r): float => round((float) $r['total'], 2), $expenseCats);

        View::renderLayout('admin', 'admin/user_show', [
            'title' => 'User · ' . (string) ($profile['username'] ?? ''),
            'profile' => $profile,
            'walletRows' => $walletRows,
            'totalBalanceBase' => $walletSvc->totalBalanceBaseForUser($userId),
            'recurringCount' => $recurringCount,
            'totalsAll' => $totalsAll,
            'monthlySeries' => $monthly,
            'transferStats' => $transferStats,
            'recentTx' => $recentTx,
            'auditRows' => $auditRows,
            'savingsRate' => $savingsRate,
            'donutWalletLabels' => $donutWalletLabels,
            'donutWalletSeries' => $donutWalletSeries,
            'donutCatLabels' => $donutCatLabels,
            'donutCatSeries' => $donutCatSeries,
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'adminSelfId' => Auth::id(),
            'user' => Auth::user(),
        ]);
    }

    public function wallets(): void
    {
        Auth::requireSuperAdmin();
        $filters = [
            'user_id' => (int) (Request::query('user_id', '0') ?? '0'),
            'wallet_type_id' => (int) (Request::query('wallet_type_id', '0') ?? '0'),
            'currency_id' => (int) (Request::query('currency_id', '0') ?? '0'),
            'status' => trim((string) (Request::query('status', 'all') ?? 'all')),
            'owner_analytics' => trim((string) (Request::query('owner_analytics', 'all') ?? 'all')),
            'search' => trim((string) (Request::query('q', '') ?? '')),
            'low_balance' => trim((string) (Request::query('low_balance', '0') ?? '0')),
        ];
        if (! in_array($filters['status'], ['all', 'active', 'inactive'], true)) {
            $filters['status'] = 'all';
        }
        if (! in_array($filters['owner_analytics'], ['all', 'included', 'excluded'], true)) {
            $filters['owner_analytics'] = 'all';
        }
        $repo = new WalletRepository();
        $rows = $repo->adminListFiltered($filters);
        $walletSvc = new WalletService();
        $rows = self::adminEnrichWalletRows($rows, $walletSvc);
        if ($filters['low_balance'] === '1') {
            $rows = array_values(array_filter($rows, static fn (array $r): bool => ! empty($r['below_threshold'])));
        }

        $typeTotals = [];
        foreach ($rows as $r) {
            $label = (string) ($r['type_label'] ?? 'Other');
            $typeTotals[$label] = ($typeTotals[$label] ?? 0) + 1;
        }
        arsort($typeTotals);
        $walletTypeChartLabels = array_keys($typeTotals);
        $walletTypeChartSeries = array_values($typeTotals);

        $buckets = [
            'Negative' => 0,
            '0 – 1k' => 0,
            '1k – 10k' => 0,
            '10k – 50k' => 0,
            '50k +' => 0,
        ];
        foreach ($rows as $r) {
            $v = (float) ($r['balance_base'] ?? 0);
            if ($v < 0) {
                ++$buckets['Negative'];
            } elseif ($v < 1000) {
                ++$buckets['0 – 1k'];
            } elseif ($v < 10000) {
                ++$buckets['1k – 10k'];
            } elseif ($v < 50000) {
                ++$buckets['10k – 50k'];
            } else {
                ++$buckets['50k +'];
            }
        }
        $balanceBucketLabels = array_keys($buckets);
        $balanceBucketSeries = array_values($buckets);

        $lowList = array_values(array_filter($rows, static fn (array $r): bool => ! empty($r['below_threshold'])));
        usort($lowList, static fn (array $a, array $b): int => ((float) ($a['balance_base'] ?? 0) <=> (float) ($b['balance_base'] ?? 0)));
        $lowBalanceRows = array_slice($lowList, 0, 12);

        $topSorted = $rows;
        usort($topSorted, static fn (array $a, array $b): int => ((float) ($b['balance_base'] ?? 0) <=> (float) ($a['balance_base'] ?? 0)));
        $topBalanceRows = array_slice($topSorted, 0, 8);

        $nActive = count(array_filter($rows, static fn (array $w): bool => ! empty($w['is_active'])));
        $nLow = count(array_filter($rows, static fn (array $w): bool => ! empty($w['below_threshold'])));
        $nUsers = count(array_unique(array_map(static fn (array $w): int => (int) $w['user_id'], $rows)));
        $totalBal = round(array_sum(array_map(static fn (array $w): float => (float) ($w['balance_base'] ?? 0), $rows)), 2);
        $distinctTypes = count(array_unique(array_map(static fn (array $w): int => (int) ($w['wallet_type_id'] ?? 0), $rows)));

        $users = (new UserRepository())->all(500, 0);
        $types = (new WalletTypeRepository())->allOrdered(false);
        $currencies = (new CurrencyRepository())->allActive();

        View::renderLayout('admin', 'admin/wallets', [
            'title' => 'Wallet ops center',
            'rows' => $rows,
            'users' => $users,
            'walletTypes' => $types,
            'currencies' => $currencies,
            'filters' => $filters,
            'walletKpis' => [
                'total' => count($rows),
                'active' => $nActive,
                'inactive' => count($rows) - $nActive,
                'low_balance' => $nLow,
                'total_balance' => $totalBal,
                'type_count' => $distinctTypes,
                'owners' => $nUsers,
            ],
            'walletTypeChartLabels' => $walletTypeChartLabels,
            'walletTypeChartSeries' => $walletTypeChartSeries,
            'balanceBucketLabels' => $balanceBucketLabels,
            'balanceBucketSeries' => $balanceBucketSeries,
            'lowBalanceRows' => $lowBalanceRows,
            'topBalanceRows' => $topBalanceRows,
            'filterQueryString' => self::buildWalletFilterQueryString($filters),
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    /** @param array<string, mixed> $filters */
    private static function buildWalletFilterQueryString(array $filters): string
    {
        $q = [];
        if ((int) ($filters['user_id'] ?? 0) > 0) {
            $q['user_id'] = (string) (int) $filters['user_id'];
        }
        if ((int) ($filters['wallet_type_id'] ?? 0) > 0) {
            $q['wallet_type_id'] = (string) (int) $filters['wallet_type_id'];
        }
        if ((int) ($filters['currency_id'] ?? 0) > 0) {
            $q['currency_id'] = (string) (int) $filters['currency_id'];
        }
        if (($filters['status'] ?? 'all') !== 'all') {
            $q['status'] = (string) $filters['status'];
        }
        if (($filters['owner_analytics'] ?? 'all') !== 'all') {
            $q['owner_analytics'] = (string) $filters['owner_analytics'];
        }
        if (($filters['low_balance'] ?? '0') === '1') {
            $q['low_balance'] = '1';
        }
        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $q['q'] = (string) $filters['search'];
        }

        return http_build_query($q);
    }

    public function walletShow(string $id): void
    {
        Auth::requireSuperAdmin();
        $walletId = (int) $id;
        if ($walletId <= 0) {
            Response::abort(404);
        }
        $repo = new WalletRepository();
        $w = $repo->findByIdForAdmin($walletId);
        if ($w === null) {
            Response::abort(404);
        }
        $ownerId = (int) $w['user_id'];
        $walletSvc = new WalletService();
        $balRow = $walletSvc->balanceRowForWallet($ownerId, $walletId);
        $balanceBase = $balRow !== null ? (float) $balRow['balance_base'] : 0.0;
        $belowMin = $balRow !== null && ! empty($balRow['below']);

        $txnRepo = new TransactionRepository();
        $monthlySeries = $txnRepo->walletMonthlyFlowSeries($walletId, $ownerId, 8);
        $transferStats = $txnRepo->walletTransferStats($walletId, $ownerId, 90);
        $recentTx = $txnRepo->recentForWalletParticipation($walletId, $ownerId, 16);

        $types = (new WalletTypeRepository())->allOrdered(false);
        $currencies = (new CurrencyRepository())->allActive();
        $ledgerCount = $repo->countTransactions($walletId);
        $recurringCount = $repo->countRecurring($walletId);

        View::renderLayout('admin', 'admin/wallet_show', [
            'title' => 'Wallet · ' . (string) ($w['name'] ?? ''),
            'wallet' => $w,
            'balanceBase' => $balanceBase,
            'belowMin' => $belowMin,
            'monthlySeries' => $monthlySeries,
            'transferStats' => $transferStats,
            'recentTx' => $recentTx,
            'ledgerCount' => $ledgerCount,
            'recurringCount' => $recurringCount,
            'walletTypes' => $types,
            'currencies' => $currencies,
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private static function adminEnrichWalletRows(array $rows, WalletService $svc): array
    {
        $userIds = array_values(array_unique(array_map(static fn (array $r): int => (int) $r['user_id'], $rows)));
        $balCache = [];
        foreach ($userIds as $uid) {
            $balCache[$uid] = [];
            foreach ($svc->walletBalancesForUser($uid) as $b) {
                $balCache[$uid][(int) $b['wallet_id']] = $b;
            }
        }
        $out = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            $wid = (int) $r['id'];
            $b = $balCache[$uid][$wid] ?? null;
            $r['balance_base'] = $b !== null ? (float) $b['balance_base'] : 0.0;
            $r['below_threshold'] = $b !== null && ! empty($b['below']);
            $out[] = $r;
        }

        return $out;
    }

    public function walletTypes(): void
    {
        Auth::requireSuperAdmin();
        $typeRepo = new WalletTypeRepository();
        $walletRepo = new WalletRepository();
        $walletSvc = new WalletService();
        $types = $typeRepo->allOrdered(false);
        $usageByType = $walletRepo->usageAggregateByWalletType();
        $balanceByType = self::balanceBaseSumByWalletType($usageByType, $walletSvc);

        $rows = [];
        foreach ($types as $t) {
            $tid = (int) $t['id'];
            $u = $usageByType[$tid] ?? ['wallets' => [], 'users' => [], 'analytics_wallet_count' => 0];
            $wc = count($u['wallets']);
            $rows[] = array_merge($t, [
                'wallet_count' => $wc,
                'users_count' => count($u['users']),
                'balance_base_total' => $balanceByType[$tid] ?? 0.0,
                'analytics_wallet_count' => (int) $u['analytics_wallet_count'],
            ]);
        }

        $totalTypes = count($types);
        $activeTypes = count(array_filter($types, static fn (array $t): bool => ! empty($t['is_active'])));
        $customTypes = count(array_filter($types, static fn (array $t): bool => empty($t['is_system'])));
        $coreTypes = $totalTypes - $customTypes;
        $totalWallets = (int) Database::pdo()->query('SELECT COUNT(*) FROM wallets')->fetchColumn();
        $unusedTypes = count(array_filter($rows, static fn (array $r): bool => (int) ($r['wallet_count'] ?? 0) === 0));
        $disabledTypes = count(array_filter($types, static fn (array $t): bool => empty($t['is_active'])));

        $mostUsed = null;
        foreach ($rows as $r) {
            if ($mostUsed === null || (int) $r['wallet_count'] > (int) $mostUsed['wallet_count']) {
                $mostUsed = $r;
            }
        }
        $recentType = null;
        foreach ($types as $t) {
            if ($recentType === null || (string) ($t['created_at'] ?? '') > (string) ($recentType['created_at'] ?? '')) {
                $recentType = $t;
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $sa = ! empty($a['is_system']);
            $sb = ! empty($b['is_system']);
            if ($sa !== $sb) {
                return $sa ? -1 : 1;
            }

            return ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
        });

        $coreWalletAssignments = 0;
        $customWalletAssignments = 0;
        foreach ($rows as $r) {
            $n = (int) ($r['wallet_count'] ?? 0);
            if (! empty($r['is_system'])) {
                $coreWalletAssignments += $n;
            } else {
                $customWalletAssignments += $n;
            }
        }

        View::renderLayout('admin', 'admin/wallet_types', [
            'title' => 'Wallet types',
            'rows' => $rows,
            'typeKpis' => [
                'total' => $totalTypes,
                'active' => $activeTypes,
                'custom' => $customTypes,
                'core' => $coreTypes,
                'total_wallets' => $totalWallets,
                'unused_types' => $unusedTypes,
                'disabled' => $disabledTypes,
            ],
            'mostUsedRow' => $mostUsed,
            'recentTypeRow' => $recentType,
            'taxonomyWalletLabels' => ['Built-in types', 'Custom types'],
            'taxonomyWalletSeries' => [$coreWalletAssignments, $customWalletAssignments],
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    public function walletTypeShow(string $id): void
    {
        Auth::requireSuperAdmin();
        $tid = (int) $id;
        if ($tid <= 0) {
            Response::abort(404);
        }
        $typeRepo = new WalletTypeRepository();
        $t = $typeRepo->findById($tid);
        if ($t === null) {
            Response::abort(404);
        }
        $walletRepo = new WalletRepository();
        $walletSvc = new WalletService();
        $usageByType = $walletRepo->usageAggregateByWalletType();
        $u = $usageByType[$tid] ?? ['wallets' => [], 'users' => [], 'analytics_wallet_count' => 0];
        $balanceByType = self::balanceBaseSumByWalletType($usageByType, $walletSvc);
        $stats = [
            'wallet_count' => count($u['wallets']),
            'users_count' => count($u['users']),
            'balance_base_total' => $balanceByType[$tid] ?? 0.0,
            'analytics_wallet_count' => (int) $u['analytics_wallet_count'],
        ];
        $recentWallets = $walletRepo->recentWalletsForType($tid, 14);

        View::renderLayout('admin', 'admin/wallet_type_show', [
            'title' => 'Wallet type · ' . (string) ($t['label'] ?? ''),
            'typeRow' => $t,
            'stats' => $stats,
            'recentWallets' => $recentWallets,
            'baseCurrency' => (string) Config::get('app.base_currency', 'MYR'),
            'message' => Session::getFlash('message'),
            'error' => Session::getFlash('error'),
            'user' => Auth::user(),
        ]);
    }

    /**
     * @param array<int, array{wallets: list<array{id:int, user_id:int}>, users: array<int, true>, analytics_wallet_count: int}> $usageByType
     *
     * @return array<int, float>
     */
    private static function balanceBaseSumByWalletType(array $usageByType, WalletService $walletSvc): array
    {
        $userIds = [];
        foreach ($usageByType as $bundle) {
            foreach ($bundle['wallets'] as $w) {
                $userIds[(int) $w['user_id']] = true;
            }
        }
        $balanceByUserWallet = [];
        foreach (array_keys($userIds) as $uid) {
            $balanceByUserWallet[$uid] = [];
            foreach ($walletSvc->walletBalancesForUser((int) $uid) as $b) {
                $balanceByUserWallet[$uid][(int) $b['wallet_id']] = (float) $b['balance_base'];
            }
        }
        $sums = [];
        foreach ($usageByType as $tid => $bundle) {
            $sum = 0.0;
            foreach ($bundle['wallets'] as $w) {
                $uid = (int) $w['user_id'];
                $wid = (int) $w['id'];
                $sum += $balanceByUserWallet[$uid][$wid] ?? 0.0;
            }
            $sums[(int) $tid] = round($sum, 2);
        }

        return $sums;
    }

    public function settings(): void
    {
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
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
        Auth::requireSuperAdmin();
        $pdo = Database::pdo();
        $rows = $pdo->query(
            'SELECT r.*, u.username, w.name AS wallet_name FROM recurring_schedules r
             JOIN users u ON u.id = r.user_id AND u.include_in_analytics = 1
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
        Auth::requireSuperAdmin();
        View::renderLayout('admin', 'admin/reports', [
            'title' => 'Reports',
            'user' => Auth::user(),
        ]);
    }

}
