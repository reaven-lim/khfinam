<?php

declare(strict_types=1);

use App\Core\Database;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;
use App\Repositories\UserRepository;

$counts = $counts ?? ['users' => 0, 'users_all' => 0, 'transactions' => 0];
$totals = $totals ?? ['income' => 0, 'expense' => 0, 'savings' => 0];

$pdo = Database::pdo();
$uAnalytics = '(' . UserRepository::analyticsIncludedUserIdsSubquery() . ')';

// 6-month cashflow trend (analytics-scoped users only)
$monthly6 = array_reverse($pdo->query(
    "SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS ym,
            SUM(CASE WHEN type='income'  AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
            SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
     FROM transactions WHERE deleted_at IS NULL AND parent_transaction_id IS NULL
       AND user_id IN {$uAnalytics}
     GROUP BY ym ORDER BY ym DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC));

// Top 6 expense categories
$topCats = $pdo->query(
    "SELECT c.name, COALESCE(SUM(t.amount_base),0) AS total
     FROM transactions t JOIN categories c ON c.id=t.category_id
     WHERE t.deleted_at IS NULL AND t.type='expense'
       AND COALESCE(t.is_internal_transfer,0)=0 AND t.parent_transaction_id IS NULL
       AND t.user_id IN {$uAnalytics}
     GROUP BY c.id, c.name ORDER BY total DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// Recent 5 users (included in analytics cohort)
$recentUsers = $pdo->query(
    "SELECT id, username, email, created_at FROM users WHERE include_in_analytics = 1 ORDER BY id DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// New users this month (analytics cohort)
$newUsersThisMonth = (int) $pdo->query(
    "SELECT COUNT(*) FROM users WHERE include_in_analytics = 1
     AND DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')"
)->fetchColumn();

// Transactions this month
$txnThisMonth = (int) $pdo->query(
    "SELECT COUNT(*) FROM transactions WHERE deleted_at IS NULL
     AND user_id IN {$uAnalytics}
     AND DATE_FORMAT(transaction_date,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')"
)->fetchColumn();

$txnPrevMonth = (int) $pdo->query(
    "SELECT COUNT(*) FROM transactions WHERE deleted_at IS NULL
     AND user_id IN {$uAnalytics}
     AND DATE_FORMAT(transaction_date,'%Y-%m')=DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH),'%Y-%m')"
)->fetchColumn();

$activeWallets = (int) $pdo->query(
    "SELECT COUNT(*) FROM wallets WHERE is_active = 1 AND user_id IN {$uAnalytics}"
)->fetchColumn();

$activeRecurring = (int) $pdo->query(
    "SELECT COUNT(*) FROM recurring_schedules WHERE is_paused = 0 AND (end_date IS NULL OR end_date >= CURDATE())
     AND user_id IN {$uAnalytics}"
)->fetchColumn();

$unreadNotifs = (int) $pdo->query(
    'SELECT COUNT(*) FROM notifications WHERE read_at IS NULL'
)->fetchColumn();

$audit24h = (int) $pdo->query(
    'SELECT COUNT(*) FROM audit_logs WHERE created_at >= (NOW() - INTERVAL 1 DAY)'
)->fetchColumn();

$recentAudit = $pdo->query(
    "SELECT action, entity_type, created_at FROM audit_logs ORDER BY id DESC LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

$totalIncome  = (float) $totals['income'];
$totalExpense = (float) $totals['expense'];
$totalSavings = (float) $totals['savings'];
$savingsRate  = $totalIncome > 0 ? round($totalSavings / $totalIncome * 100, 1) : 0.0;

$monthLabels = array_column($monthly6, 'ym');
$incData     = array_map(fn(array $r): float => round((float) $r['inc'], 2), $monthly6);
$expData     = array_map(fn(array $r): float => round((float) $r['exp'], 2), $monthly6);
$catLabels   = array_column($topCats, 'name');
$catData     = array_map(fn(array $r): float => round((float) $r['total'], 2), $topCats);

$nM = count($incData);
$incMomPct = null;
if ($nM >= 2) {
    $li = $incData[$nM - 1];
    $pi = $incData[$nM - 2];
    if ($pi > 0.01) {
        $incMomPct = round(($li - $pi) / $pi * 100, 1);
    }
}

$txnTrendChip = null;
$txnTrendChipClass = 'bg-white/14 text-white/95 ring-1 ring-white/25';
if ($txnPrevMonth > 0) {
    $tp = round((($txnThisMonth - $txnPrevMonth) / $txnPrevMonth) * 100, 1);
    $txnTrendChip = ($tp >= 0 ? '+' : '') . $tp . '% MoM';
    $txnTrendChipClass = $tp >= 0
        ? 'bg-emerald-400/30 text-emerald-50 ring-1 ring-emerald-300/35'
        : 'bg-rose-400/30 text-rose-50 ring-1 ring-rose-300/30';
} elseif ($txnThisMonth > 0) {
    $txnTrendChip = 'Ramping';
    $txnTrendChipClass = 'bg-sky-400/25 text-sky-50 ring-1 ring-sky-200/35';
}

$usersTrendChip = $newUsersThisMonth > 0 ? '+' . $newUsersThisMonth . ' MO' : null;
$usersTrendChipClass = 'bg-white/14 text-white/95 ring-1 ring-white/25';

$incTrendChip = null;
$incTrendChipClass = 'bg-white/14 text-white/95 ring-1 ring-white/25';
if ($incMomPct !== null) {
    $incTrendChip = ($incMomPct >= 0 ? '+' : '') . $incMomPct . '% MoM';
    $incTrendChipClass = $incMomPct >= 0
        ? 'bg-emerald-400/28 text-emerald-50 ring-1 ring-emerald-300/40'
        : 'bg-rose-400/28 text-rose-50 ring-1 ring-rose-300/35';
}

$saveTrendChip = null;
$saveTrendChipClass = 'bg-white/14 text-white/95 ring-1 ring-white/25';
if ($savingsRate >= 25) {
    $saveTrendChip = 'Healthy';
    $saveTrendChipClass = 'bg-emerald-400/28 text-emerald-50 ring-1 ring-emerald-300/35';
} elseif ($savingsRate > 0 && $savingsRate < 15) {
    $saveTrendChip = 'Optimize';
    $saveTrendChipClass = 'bg-amber-400/28 text-amber-950 ring-1 ring-amber-200/50';
}

$chartCardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15),0_8px_24px_-10px_rgba(15,23,42,0.08)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.65)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';

$gaugeCardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.14),0_8px_24px_-10px_rgba(15,23,42,0.07)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.6)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden flex flex-col';

$ledgerCardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.14),0_8px_24px_-10px_rgba(15,23,42,0.07)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.6)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';

$intelStripShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50 to-teal-50/65 dark:from-[#0c1426] dark:via-[#0d1629] dark:to-teal-950/25 px-5 py-4 sm:px-6 sm:py-5 shadow-[0_20px_54px_-28px_rgba(15,23,42,0.16),0_6px_18px_-6px_rgba(15,23,42,0.085)] dark:shadow-[0_20px_50px_-32px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.05]';
?>

<!-- ─── Platform intelligence frame ───────────────────────── -->
<div class="mb-6 <?= Str::e($intelStripShell) ?>">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-teal-600 dark:text-teal-400">Platform control</p>
            <h2 class="mt-1 text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">Financial intelligence overview</h2>
            <p class="mt-1 text-xs sm:text-[13px] text-slate-600 dark:text-slate-400 max-w-xl leading-relaxed">Financial signals include only accounts marked &ldquo;Include in analytics&rdquo;; excluded demo or test users stay active but do not move these charts.</p>
        </div>
        <div class="flex flex-wrap gap-2 sm:gap-2.5 shrink-0">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-300/90 dark:border-slate-600/70 bg-white/95 dark:bg-slate-900/60 px-3 py-1.5 text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-[0_6px_16px_-6px_rgba(15,23,42,0.12)] dark:shadow-sm">
                <i data-lucide="wallet" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i>
                <?= number_format($activeWallets) ?> wallets
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-300/90 dark:border-slate-600/70 bg-white/95 dark:bg-slate-900/60 px-3 py-1.5 text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-[0_6px_16px_-6px_rgba(15,23,42,0.12)] dark:shadow-sm">
                <i data-lucide="repeat" class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400"></i>
                <?= number_format($activeRecurring) ?> recurring
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-300/90 dark:border-slate-600/70 bg-white/95 dark:bg-slate-900/60 px-3 py-1.5 text-[11px] font-semibold text-slate-700 dark:text-slate-200 shadow-[0_6px_16px_-6px_rgba(15,23,42,0.12)] dark:shadow-sm">
                <i data-lucide="bell" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i>
                <?= number_format($unreadNotifs) ?> unread
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full border border-teal-300/85 dark:border-teal-800/60 bg-teal-50 dark:bg-teal-950/40 px-3 py-1.5 text-[11px] font-bold text-teal-800 dark:text-teal-200 shadow-[0_6px_18px_-6px_rgba(13,148,136,0.26)] dark:shadow-sm ring-1 ring-teal-200/65 dark:ring-teal-800/50">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                <?= number_format($audit24h) ?> audits · 24h
            </span>
        </div>
    </div>
</div>

<!-- ─── Hero KPI Grid ─────────────────────────────────────── -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6 sm:mb-7">
<?php
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-violet-500 via-violet-600 to-violet-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(91,33,182,0.42)] dark:shadow-xl dark:shadow-violet-500/28 relative overflow-hidden ring-1 ring-violet-950/18 dark:ring-white/15',
    'label' => 'Users in analytics',
    'value' => number_format((int) ($counts['users'] ?? 0)),
    'footnote' => ($newUsersThisMonth > 0 ? '+' . $newUsersThisMonth . ' new this month · ' : '')
        . number_format((int) ($counts['users_all'] ?? ($counts['users'] ?? 0))) . ' total directory accounts',
    'icon' => 'users',
    'trendChip' => $usersTrendChip,
    'trendChipClass' => $usersTrendChipClass,
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(29,78,216,0.42)] dark:shadow-xl dark:shadow-blue-500/25 relative overflow-hidden ring-1 ring-blue-950/18 dark:ring-white/15',
    'label' => 'Transactions',
    'value' => number_format((int) ($counts['transactions'] ?? 0)),
    'footnote' => number_format($txnThisMonth) . ' posted this calendar month · velocity signal',
    'icon' => 'arrow-left-right',
    'trendChip' => $txnTrendChip,
    'trendChipClass' => $txnTrendChipClass,
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-950 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(5,122,85,0.44)] dark:shadow-xl dark:shadow-emerald-500/25 relative overflow-hidden ring-1 ring-emerald-950/20 dark:ring-white/15',
    'label' => 'Platform Income',
    'value' => 'RM ' . number_format($totalIncome, 0),
    'footnote' => 'Base-currency rollup · treasury view',
    'icon' => 'trending-up',
    'valueClass' => 'text-2xl font-extrabold mt-1 tabular-nums tracking-tight',
    'trendChip' => $incTrendChip,
    'trendChipClass' => $incTrendChipClass,
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(13,148,136,0.42)] dark:shadow-xl dark:shadow-teal-500/25 relative overflow-hidden ring-1 ring-teal-950/20 dark:ring-white/15',
    'label' => 'Net Savings',
    'value' => 'RM ' . number_format($totalSavings, 0),
    'footnote' => $savingsRate . '% of income retained · efficiency lens',
    'icon' => 'piggy-bank',
    'valueClass' => 'text-2xl font-extrabold mt-1 tabular-nums tracking-tight',
    'trendChip' => $saveTrendChip,
    'trendChipClass' => $saveTrendChipClass,
]);
?>
</div>

<!-- ─── Charts Row ────────────────────────────────────────── -->
<div class="grid lg:grid-cols-3 gap-5 mb-6 sm:mb-7">

    <div class="lg:col-span-2"><?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Platform Cashflow',
        'subtitle' => 'Income vs expenses · trailing 6 months · consolidated view',
        'chartId' => 'adminCashflowChart',
        'badgeText' => 'Live',
        'badgeClass' => 'text-[10px] font-bold bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-teal-300/75 dark:ring-teal-800/50 shadow-sm shadow-teal-900/15 dark:shadow-none',
        'cardClass' => $chartCardShell,
        'chartContainerClass' => 'mt-3 min-h-[228px]',
    ]); ?></div>

    <?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Expense Categories',
        'subtitle' => 'Top spend concentration · mix signal',
        'chartId' => 'adminCatsChart',
        'headerSimple' => true,
        'cardClass' => $chartCardShell,
        'chartContainerClass' => 'mt-3 min-h-[228px]',
    ]); ?>
</div>

<!-- ─── Bottom Row ────────────────────────────────────────── -->
<div class="grid lg:grid-cols-3 gap-5 mb-2">

    <!-- Savings rate radial -->
    <div class="<?= Str::e($gaugeCardShell) ?>">
        <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-teal-400/10 dark:bg-teal-500/10 blur-2xl"></div>
        <div class="relative">
            <div class="flex items-start justify-between gap-2 mb-1">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 tracking-tight">Savings rate</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Platform-wide capital efficiency</p>
                </div>
                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2 py-0.5 rounded-full">Health</span>
            </div>
            <div id="adminSavingsGauge" class="flex-1 min-h-[200px]"></div>
            <div class="text-center mt-2">
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    <?php if ($savingsRate >= 30): ?>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Excellent savings health</span>
                    <?php elseif ($savingsRate >= 15): ?>
                        <span class="text-teal-600 dark:text-teal-400 font-semibold">Good savings discipline</span>
                    <?php elseif ($savingsRate > 0): ?>
                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Room for improvement</span>
                    <?php else: ?>
                        <span class="text-rose-600 dark:text-rose-400 font-semibold">Negative savings pressure</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Recent users -->
    <div class="lg:col-span-2 <?= Str::e($ledgerCardShell) ?>">
        <div class="pointer-events-none absolute left-0 top-0 h-full w-px bg-gradient-to-b from-teal-500/0 via-teal-500/15 to-teal-500/0 dark:via-teal-400/10"></div>
        <div class="relative flex items-center justify-between mb-4 gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 tracking-tight">Recent cohort</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Latest registrations · identity surface</p>
            </div>
            <a href="<?= Str::e(Url::to('/admin/users')) ?>" class="inline-flex items-center gap-1.5 rounded-full border border-slate-300/90 bg-white shadow-sm shadow-slate-900/12 dark:bg-slate-800/80 dark:border-slate-600 dark:shadow-none px-3 py-1.5 text-xs font-bold text-teal-800 dark:text-teal-300 hover:bg-teal-50 dark:hover:bg-teal-950/50 transition-colors">
                Directory <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
            </a>
        </div>
        <?php if (empty($recentUsers)): ?>
        <div class="rounded-xl border border-dashed border-slate-300/85 dark:border-slate-700/80 bg-gradient-to-br from-white to-slate-50/98 dark:from-slate-900/40 dark:to-[#0d1424] px-6 py-10 shadow-inner shadow-slate-900/14 dark:shadow-none">
            <?php View::partial('components/ui/empty-state-muted', [
                'icon' => 'user-plus',
                'title' => 'No registrations yet',
                'subtitle' => 'When users onboard, acquisition signals appear here.',
            ]); ?>
        </div>
        <?php else: ?>
        <ul class="divide-y divide-slate-200/90 dark:divide-slate-800/80">
            <?php foreach ($recentUsers as $ru):
                $ini = strtoupper(substr((string) $ru['username'], 0, 2));
                $colors = ['from-violet-500 to-violet-700', 'from-blue-500 to-blue-700', 'from-teal-500 to-teal-700', 'from-emerald-500 to-emerald-700', 'from-rose-500 to-rose-600'];
                $ci = crc32((string) $ru['username']) % count($colors);
            ?>
            <li class="flex items-center gap-3 py-3 sm:py-3.5 first:pt-0 group hover:bg-slate-50/50 dark:hover:bg-slate-800/25 -mx-2 px-2 rounded-xl transition-colors">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $colors[abs($ci)] ?> flex items-center justify-center text-white text-xs font-bold shrink-0 ring-2 ring-white/30 dark:ring-slate-900/80 shadow-md">
                    <?= Str::e($ini) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate group-hover:text-teal-700 dark:group-hover:text-teal-300 transition-colors"><?= Str::e((string) $ru['username']) ?></p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 truncate"><?= Str::e((string) $ru['email']) ?></p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 tabular-nums"><?= Str::e(substr((string) $ru['created_at'], 0, 10)) ?></p>
                    <span class="inline-block text-[10px] font-bold bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 px-2 py-0.5 rounded-full mt-1 ring-1 ring-teal-200/60 dark:ring-teal-800/40">Member</span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Governance pulse (audit) ─────────────────────────── -->
<div class="mt-5 rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50/70 to-white dark:from-[#0d1424] dark:to-[#0a101c] p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.14),0_8px_22px_-8px_rgba(15,23,42,0.07)] dark:shadow-[0_20px_55px_-34px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.065] dark:ring-white/[0.05]">
    <div class="flex flex-col lg:flex-row lg:items-start gap-6">
        <div class="lg:w-[38%] shrink-0 space-y-4">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-400 mb-2">Operational depth</p>
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug">Surface controls for automation and engagement layers without duplicating KPI noise.</p>
            </div>
            <div class="flex flex-col gap-2">
                <a href="<?= Str::e(Url::to('/admin/recurring')) ?>" class="group flex items-center justify-between rounded-xl border border-slate-300/88 bg-white shadow-sm shadow-slate-900/10 dark:shadow-none dark:border-slate-700/70 dark:bg-slate-900/40 px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-violet-400/80 dark:hover:border-violet-700/50 hover:shadow-[0_8px_20px_-8px_rgba(109,40,217,0.2)] transition-all">
                    <span class="inline-flex items-center gap-2"><i data-lucide="repeat" class="w-3.5 h-3.5 text-violet-600 dark:text-violet-400"></i> Recurring rules</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors"></i>
                </a>
                <a href="<?= Str::e(Url::to('/admin/notifications')) ?>" class="group flex items-center justify-between rounded-xl border border-slate-300/88 bg-white shadow-sm shadow-slate-900/10 dark:shadow-none dark:border-slate-700/70 dark:bg-slate-900/40 px-3.5 py-2.5 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-amber-400/80 dark:hover:border-amber-700/50 hover:shadow-[0_8px_20px_-8px_rgba(217,119,6,0.22)] transition-all">
                    <span class="inline-flex items-center gap-2"><i data-lucide="bell" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400"></i> Notification center</span>
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors"></i>
                </a>
            </div>
        </div>
        <div class="hidden lg:block w-px shrink-0 self-stretch min-h-[140px] bg-gradient-to-b from-transparent via-slate-300 dark:via-slate-700 to-transparent"></div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-3">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-600 dark:text-slate-500">Audit trail</p>
                <a href="<?= Str::e(Url::to('/admin/audit')) ?>" class="text-[11px] font-bold text-teal-700 dark:text-teal-400 hover:underline inline-flex items-center gap-1">Console <i data-lucide="external-link" class="w-3 h-3 opacity-70"></i></a>
            </div>
            <?php if (empty($recentAudit)): ?>
            <div class="rounded-xl border border-dashed border-slate-300/90 dark:border-slate-700 bg-gradient-to-br from-slate-50 to-white dark:from-slate-900/30 dark:to-slate-900/30 py-8 px-4 text-center shadow-inner shadow-slate-900/12 dark:shadow-none">
                <i data-lucide="radio" class="w-7 h-7 mx-auto text-slate-400 dark:text-slate-600 mb-2"></i>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400">System quiet</p>
                <p class="text-xs text-slate-600 dark:text-slate-500 mt-1">Governance events will stream here as activity grows.</p>
            </div>
            <?php else: ?>
            <ul class="space-y-1 max-h-[220px] overflow-y-auto pr-1 -mr-1">
                <?php foreach ($recentAudit as $al):
                    $ent = (string) ($al['entity_type'] ?? '');
                    $act = (string) $al['action'];
                    $pretty = ($ent !== '' ? $ent . ' · ' : '') . $act;
                ?>
                <li class="flex items-start gap-2.5 py-2 border-b border-slate-200/95 dark:border-slate-800/80 last:border-0">
                    <span class="mt-1.5 w-1.5 h-1.5 shrink-0 rounded-full bg-teal-500 shadow-[0_0_10px_-2px_rgba(20,184,166,0.85)] dark:bg-teal-400"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-200 leading-snug break-words"><?= Str::e($pretty) ?></p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-medium tabular-nums"><?= Str::e(substr((string) ($al['created_at'] ?? ''), 0, 19)) ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var charts = [];
    function teardown() {
        charts.forEach(function (c) {
            try {
                if (c && typeof c.destroy === 'function') {
                    c.destroy();
                }
            } catch (e) {}
        });
        charts = [];
    }
    function push(el, cfg) {
        if (!el) return;
        var ch = new ApexCharts(el, cfg);
        charts.push(ch);
        ch.render();
    }
    var months = <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
    var inc    = <?= json_encode($incData, JSON_HEX_TAG) ?>;
    var exp    = <?= json_encode($expData, JSON_HEX_TAG) ?>;
    var catLbl = <?= json_encode($catLabels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    var catDat = <?= json_encode($catData, JSON_HEX_TAG) ?>;
    var sRate  = <?= json_encode($savingsRate) ?>;

    function runDashboardCharts() {
        teardown();
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') return;
        var tt = KhfApexTheme.tokens();
        var isDark = KhfApexTheme.isDark();

    function emptyCashflowPremium() {
        return '<div class="flex flex-col items-center justify-center min-h-[200px] rounded-xl border border-dashed border-slate-300/92 dark:border-slate-700/80 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900/40 dark:to-[#0d1424] px-6 py-10 text-center shadow-inner shadow-slate-900/12 dark:shadow-none">' +
            '<div class="w-12 h-12 rounded-2xl bg-teal-500/14 dark:bg-teal-400/10 flex items-center justify-center mb-3 ring-1 ring-teal-500/30 dark:ring-teal-500/20">' +
            '<svg class="w-6 h-6 text-teal-600/70 dark:text-teal-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m12-2V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg></div>' +
            '<p class="text-sm font-bold text-slate-700 dark:text-slate-300">Awaiting ledger activity</p>' +
            '<p class="text-xs text-slate-600 dark:text-slate-500 mt-2 max-w-[240px] leading-relaxed">Once transactions land, runway and income/expense interplay render here automatically.</p></div>';
    }
    function emptyDonutPremium() {
        return '<div class="flex flex-col items-center justify-center min-h-[200px] rounded-xl border border-dashed border-slate-300/92 dark:border-slate-700/80 bg-gradient-to-b from-white to-slate-50 dark:from-slate-900/40 dark:to-[#0d1424] px-6 py-10 text-center shadow-inner shadow-slate-900/12 dark:shadow-none">' +
            '<div class="w-11 h-11 rounded-xl bg-rose-500/14 dark:bg-rose-400/10 flex items-center justify-center mb-3 ring-1 ring-rose-500/28 dark:ring-rose-500/18">' +
            '<svg class="w-5 h-5 text-rose-500/75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg></div>' +
            '<p class="text-sm font-bold text-slate-700 dark:text-slate-300">No expense concentration</p>' +
            '<p class="text-xs text-slate-600 dark:text-slate-500 mt-2 max-w-[220px]">Category mix appears when spend is recorded.</p></div>';
    }

    // Cashflow area chart
    var cashflowEl = document.getElementById('adminCashflowChart');
    if (cashflowEl) {
        if (months.length) {
            push(cashflowEl, Object.assign({}, KhfApexTheme.chart({
                    type: 'area',
                    height: 236,
                    animations: { enabled: true, speed: 760, easing: 'easeinout' },
                    dropShadow: { enabled: true, top: isDark ? 6 : 4, blur: isDark ? 18 : 16, opacity: isDark ? 0.42 : 0.2,
                        color: isDark ? '#10b983' : '#0f766e' }
                }), {
                series: [
                    { name: 'Income',  data: inc },
                    { name: 'Expense', data: exp }
                ],
                colors: ['#10b981', '#f43f5e'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: tt.incomeExpenseFillShade,
                        type: 'vertical',
                        shadeIntensity: isDark ? 0.42 : 0.36,
                        opacityFrom: isDark ? 0.52 : 0.42,
                        opacityTo: isDark ? 0.034 : 0.06,
                        stops: [0, 92, 100]
                    }
                },
                    stroke: { curve: 'smooth', width: [isDark ? 2.75 : 3, isDark ? 2.75 : 3], lineCap: 'round' },
                xaxis: {
                    categories: months,
                    labels: { style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: isDark ? 500 : 600 }, trim: months.length > 8 },
                    axisBorder: { show: false }, axisTicks: { show: false },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: isDark ? 500 : 600 },
                        formatter: function(v) {
                            var n = Number(v);
                            var a = Math.abs(n);
                            if (a >= 1000000) return 'RM' + (n / 1000000).toFixed(1) + 'M';
                            if (a >= 1000) return 'RM' + (n / 1000).toFixed(1) + 'k';
                            return 'RM' + Math.round(n);
                        },
                        hideOverlappedLabels: true
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                grid: Object.assign(KhfApexTheme.grid({ padding: { top: 8, left: 2, right: 8 }, xaxis: { lines: { show: false } } })),
                tooltip: Object.assign(KhfApexTheme.tooltip({ style: { fontSize: '12px' } }), {
                    shared: true,
                    intersect: false,
                    x: { show: true },
                    y: {
                        formatter: function (v) {
                            return 'RM ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                        }
                    }
                }),
                legend: Object.assign(KhfApexTheme.legendTopRight({ fontSize: '12px' }), {
                    markers: { width: 8, height: 8, radius: 3 },
                    labels: { colors: tt.legend }
                }),
                markers: { size: months.length <= 10 ? [3.75, 3.75] : [0, 0], strokeWidth: 0, hover: { size: 8 } },
                responsive: [{
                    breakpoint: 640,
                    options: {
                        legend: { position: 'bottom', horizontalAlign: 'center', offsetY: 4 },
                        chart: { height: 212 }
                    }
                }]
            }));
        } else {
            cashflowEl.innerHTML = emptyCashflowPremium();
        }
    }

    var catsEl = document.getElementById('adminCatsChart');
    if (catsEl) {
        if (catDat.length) {
            var donutPalette = ['#f43f5e','#f97316','#eab308','#22c55e','#06b6d4','#8b5cf6'];
            push(catsEl, Object.assign({}, KhfApexTheme.chart({
                    type: 'donut',
                    height: 232,
                    dropShadow: { enabled: true, blur: isDark ? 14 : 12, opacity: isDark ? 0.38 : 0.16 }
                }), {
                series: catDat,
                labels: catLbl,
                colors: donutPalette.slice(0, Math.max(catDat.length, 1)),
                stroke: {
                    show: true,
                    width: isDark ? 2 : 1.25,
                    colors: [tt.donutRingStroke]
                },
                plotOptions: {
                    pie: {
                        expandOnClick: false,
                        donut: {
                            size: '66%',
                            background: 'transparent',
                            labels: {
                                show: true,
                                name: { show: false },
                                total: {
                                    show: true,
                                    label: 'Spend',
                                    color: tt.donutCenterLabel,
                                    fontSize: '11px',
                                    fontWeight: 700,
                                    formatter: function(w) {
                                        var s = w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0);
                                        return 'RM ' + s.toLocaleString(undefined, { maximumFractionDigits: 0 });
                                    }
                                },
                                value: { show: false }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                legend: Object.assign(KhfApexTheme.legendBottom({ fontWeight: 600 }), {
                    fontSize: '11px',
                    itemMargin: { horizontal: 8, vertical: 4 }
                }),
                tooltip: Object.assign(KhfApexTheme.tooltip({ style: { fontSize: '12px' } }), {
                    y: {
                        formatter: function (v) {
                            return 'RM ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                        }
                    }
                })
            }));
        } else {
            catsEl.innerHTML = emptyDonutPremium();
        }
    }

        var gaugeEl = document.getElementById('adminSavingsGauge');
    if (gaugeEl) {
        push(gaugeEl, Object.assign({}, KhfApexTheme.chart({
            type: 'radialBar',
            height: 208,
                dropShadow: { enabled: true, blur: 16, opacity: isDark ? 0.35 : 0.2, top: 4 }
        }), {
            series: [Math.max(0, Math.min(100, sRate))],
            colors: [sRate >= 20 ? '#0d9488' : sRate >= 10 ? '#f59e0b' : '#f43f5e'],
            plotOptions: {
                radialBar: {
                    startAngle: -132,
                    endAngle: 132,
                    hollow: { size: '62%', background: 'transparent' },
                    track: { background: tt.radialTrack, strokeWidth: '94%', dropShadow: { enabled: isDark, top: 0, blur: 4, opacity: 0.25 } },
                    dataLabels: {
                        show: true,
                        name: { show: false },
                        value: {
                            offsetY: 6,
                            fontSize: '27px',
                            fontWeight: 800,
                            color: tt.donutCenterValue,
                            formatter: function(v) { return v + '%'; }
                        }
                    }
                }
            },
            stroke: { lineCap: 'round' }
        }));
    }
    }

    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(runDashboardCharts);
    } else {
        runDashboardCharts();
    }
})();
</script>
