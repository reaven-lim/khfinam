<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$txLensBasePath   = $txLensBasePath ?? '/admin/transactions';
$txReportsPath    = $txReportsPath ?? '/admin/reports';
$txShowUserLens   = $txShowUserLens ?? true;
$txShowUserColumn = $txShowUserColumn ?? true;
$ledgerColSpan    = $txShowUserColumn ? 6 : 5;

$rows                         = $rows ?? [];
$users                        = $users ?? [];
$filterUserId                 = $filterUserId ?? 0;
$filterFrom                   = $filterFrom ?? '';
$filterTo                     = $filterTo ?? '';
$filterType                   = $filterType ?? '';
$analyticsSummary             = $analyticsSummary ?? ['count' => 0, 'income' => 0.0, 'expense' => 0.0, 'avg_abs' => 0.0];
$analyticsSeriesFrom          = $analyticsSeriesFrom ?? '';
$analyticsSeriesTo            = $analyticsSeriesTo ?? '';
$analyticsDaily               = $analyticsDaily ?? [];
$analyticsCategoryBreakdown   = $analyticsCategoryBreakdown ?? [];
$analyticsWalletBreakdown     = $analyticsWalletBreakdown ?? [];
$analyticsTopCategory         = $analyticsTopCategory ?? null;
$analyticsTopWallet           = $analyticsTopWallet ?? null;
$analyticsRecurringRatio      = $analyticsRecurringRatio ?? 0.0;
$analyticsSpendWeekDeltaPct   = $analyticsSpendWeekDeltaPct;
$analyticsExpense7d           = $analyticsExpense7d ?? 0.0;

$inc    = (float) $analyticsSummary['income'];
$exp    = (float) $analyticsSummary['expense'];
$net    = $inc - $exp;
$cnt    = (int) $analyticsSummary['count'];
$avg    = (float) $analyticsSummary['avg_abs'];
$saving = $analyticsTopCategory;

$dailyLabels = array_map(static fn (array $r): string => (string) ($r['d'] ?? ''), $analyticsDaily);
$dailyInc    = array_map(static fn (array $r): float => round((float) ($r['income'] ?? 0), 2), $analyticsDaily);
$dailyExp    = array_map(static fn (array $r): float => round((float) ($r['expense'] ?? 0), 2), $analyticsDaily);
$dailyVol = array_map(static fn (array $r): int => (int) ($r['volume'] ?? 0), $analyticsDaily);

$pTopCatLabel = ($filterType === 'income') ? 'Top income category' : 'Highest spend category';

$catIconFn = static function (string $name): string {
    $n = mb_strtolower($name);
    $map = [
        'food' => 'utensils', 'coffee' => 'coffee', 'transport' => 'car', 'travel' => 'plane',
        'shop' => 'shopping-bag', 'health' => 'heart-pulse', 'medic' => 'heart-pulse',
        'bill' => 'receipt', 'util' => 'zap', 'rent' => 'home', 'salary' => 'banknote',
        'bonus' => 'gift', 'save' => 'piggy-bank', 'invest' => 'trending-up', 'edu' => 'book-open',
        'sport' => 'trophy', 'entertain' => 'tv',
    ];
    foreach ($map as $k => $ico) {
        if (str_contains($n, $k)) {
            return $ico;
        }
    }

    return 'circle-dollar-sign';
};

$sparkInc = array_slice($dailyInc, -8);
$sparkExp = array_slice($dailyExp, -8);
$sparkVol = array_slice($dailyVol, -8);
$sparkNetTail = [];
foreach (array_slice($analyticsDaily, -8) as $dr) {
    $sparkNetTail[] = round((float) ($dr['income'] ?? 0) - (float) ($dr['expense'] ?? 0), 2);
}
$scopeNote = ($filterFrom === '' && $filterTo === '')
    ? 'Charts: last 90 days · KPIs & table: all time (matching filters)'
    : 'Charts: ' . htmlspecialchars($analyticsSeriesFrom, ENT_QUOTES, 'UTF-8') . ' → ' . htmlspecialchars($analyticsSeriesTo, ENT_QUOTES, 'UTF-8');
?>

<!-- Smart insights -->
<section class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-10">
<?php
ob_start();
if ($filterType === 'income'): ?>
                <p class="mt-3 text-[15px] font-semibold leading-relaxed text-slate-900 dark:text-slate-50">Expense comparison is paused while filtering income-only.</p>
                <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600 dark:text-slate-300">Switch type to “All” or “Expense” to restore the weekly pulse.</p>
<?php elseif ($analyticsSpendWeekDeltaPct === null): ?>
                <p class="mt-3 text-[15px] font-semibold leading-relaxed text-slate-900 dark:text-slate-50">Insufficient prior-week expense data.</p>
                <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600 dark:text-slate-300">Broader filters or more history unlock this signal.</p>
<?php else: ?>
                <p class="mt-3 text-[15px] font-bold leading-snug text-slate-900 dark:text-slate-50">
                    <?php if ($analyticsSpendWeekDeltaPct > 0): ?>
                        Expense WoW shifted <span class="whitespace-nowrap tabular-nums text-rose-700 dark:text-rose-300">+<?= Str::e((string) $analyticsSpendWeekDeltaPct) ?>%</span>
                    <?php elseif ($analyticsSpendWeekDeltaPct < 0): ?>
                        Expense WoW improved <span class="whitespace-nowrap tabular-nums text-emerald-700 dark:text-emerald-300">−<?= Str::e((string) abs($analyticsSpendWeekDeltaPct)) ?>%</span>
                    <?php else: ?>
                        Expense WoW <span class="text-slate-700 dark:text-slate-200">stable</span> week over week
                    <?php endif; ?>
                </p>
                <?php if ($analyticsSpendWeekDeltaPct != 0): ?>
                    <p class="mt-2 text-[13px] font-semibold leading-relaxed text-slate-700 dark:text-slate-100 tabular-nums">Rolling 7d · RM <?= number_format((float) $analyticsExpense7d, 0) ?></p>
                <?php else: ?>
                    <p class="mt-2 text-[13px] font-semibold leading-relaxed text-slate-700 dark:text-slate-100 tabular-nums">7d total · RM <?= number_format((float) $analyticsExpense7d, 0) ?></p>
                <?php endif; ?>
                <p class="mt-2 text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Vs previous 7 calendar days · same lens</p>
<?php endif;
$txInsightSpendBody = ob_get_clean();
View::partial('components/analytics/insight-glass-card', [
    'icon' => 'activity',
    'iconClass' => 'text-rose-700 dark:text-rose-200',
    'iconBoxClass' => 'bg-gradient-to-br from-rose-100 to-rose-200/80 shadow-md shadow-rose-500/15 ring-2 ring-rose-200/90 dark:from-rose-950 dark:via-rose-900/90 dark:to-rose-950/60 dark:shadow-[0_0_28px_-6px_rgba(244,63,94,0.55)] dark:ring-rose-500/35',
    'orbClass' => 'bg-gradient-to-bl from-rose-400/25 to-transparent opacity-90 dark:from-rose-500/25 dark:opacity-100 dark:blur-2xl',
    'eyebrow' => 'Spending pulse',
    'contentHtml' => $txInsightSpendBody,
]);
ob_start();
if ($analyticsTopWallet): ?>
                <p class="mt-3 text-[18px] font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white truncate"><?= Str::e($analyticsTopWallet['name']) ?></p>
                <p class="mt-2 text-[17px] font-bold tabular-nums text-teal-800 dark:text-teal-300">RM <?= number_format((float) $analyticsTopWallet['total'], 2) ?></p>
                <p class="mt-2 text-[12px] font-semibold leading-relaxed text-slate-600 dark:text-slate-300">Flow in current chart window</p>
<?php else: ?>
                <p class="mt-3 text-[15px] font-bold text-slate-900 dark:text-slate-50">No wallet attribution</p>
                <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600 dark:text-slate-300">Nothing matched this lens inside the visualization window.</p>
<?php endif;
$txInsightWalletBody = ob_get_clean();
View::partial('components/analytics/insight-glass-card', [
    'icon' => 'wallet',
    'iconClass' => 'text-teal-800 dark:text-teal-200',
    'iconBoxClass' => 'bg-gradient-to-br from-teal-100 to-teal-200/80 shadow-md shadow-teal-500/15 ring-2 ring-teal-200/90 dark:from-teal-950 dark:via-teal-900/90 dark:to-teal-950/60 dark:shadow-[0_0_28px_-6px_rgba(45,212,191,0.45)] dark:ring-teal-400/35',
    'orbClass' => 'bg-gradient-to-bl from-teal-400/20 to-transparent opacity-95 dark:from-teal-400/22 dark:opacity-100 dark:blur-2xl',
    'eyebrow' => 'Most active wallet',
    'contentHtml' => $txInsightWalletBody,
]);
ob_start(); ?>
            <p class="mt-3 text-[clamp(1.375rem,3.5vw,1.875rem)] font-extrabold tabular-nums leading-none tracking-tight text-slate-900 dark:text-white">
                <?= round($analyticsRecurringRatio * 100, 1) ?><span class="text-[0.62em] font-bold text-teal-800 dark:text-teal-300">%</span>
            </p>
            <p class="mt-3 text-[15px] font-bold leading-snug text-slate-800 dark:text-slate-50">Scheduled-linked rows vs filter</p>
            <p class="mt-2 text-[13px] font-medium leading-relaxed text-slate-600 dark:text-slate-300">Parent ledger rows tied to recurring schedules · same analytic lens</p>
<?php $txInsightRecurringBody = ob_get_clean();
View::partial('components/analytics/insight-glass-card', [
    'icon' => 'repeat-2',
    'iconClass' => 'text-violet-800 dark:text-violet-200',
    'iconBoxClass' => 'bg-gradient-to-br from-violet-100 to-violet-200/80 shadow-md shadow-violet-500/15 ring-2 ring-violet-200/90 dark:from-violet-950 dark:via-violet-900/90 dark:to-violet-950/60 dark:shadow-[0_0_28px_-6px_rgba(167,139,250,0.45)] dark:ring-violet-400/35',
    'orbClass' => 'bg-gradient-to-bl from-violet-400/22 to-transparent opacity-95 dark:from-violet-500/26 dark:opacity-100 dark:blur-2xl',
    'eyebrow' => 'Recurring signal',
    'contentHtml' => $txInsightRecurringBody,
    'articleClass' => 'group relative isolate overflow-hidden rounded-3xl border border-slate-200/95 bg-white shadow-[0_10px_40px_-14px_rgba(15,23,42,0.18)] ring-1 ring-slate-900/[0.05]
        dark:border-slate-600/40 dark:bg-gradient-to-br dark:from-[#161f34] dark:via-[#10192b] dark:to-[#0a0f18]
        dark:shadow-[0_14px_48px_-14px_rgba(0,0,0,0.85),inset_0_1px_0_0_rgba(255,255,255,0.08)] dark:ring-white/[0.09]
        backdrop-blur-sm p-5 md:p-6 flex gap-4 md:gap-5 items-start sm:col-span-2 md:col-span-1',
]);
?>
</section>

<!-- Hero KPIs -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4 sm:gap-4 lg:gap-5 mb-8 md:mb-10">
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-400 via-emerald-600 to-emerald-950 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(16,185,129,0.45)] ring-1 ring-white/20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.22),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/25 to-transparent"></div>
        <div class="relative z-10 pr-1 pb-14">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85">Income</p>
            <p class="text-2xl sm:text-[1.65rem] font-extrabold tabular-nums tracking-tight mt-2 leading-none kpi-counter" data-kpi-target="<?= Str::e((string) round($inc, 2)) ?>" data-kpi-prefix="RM " data-kpi-decimals="2">RM 0.00</p>
            <?php if ($filterType === '' && $analyticsSpendWeekDeltaPct !== null): ?>
                <p class="text-[11px] font-semibold mt-3 pt-2 border-t border-white/20 text-white/90">Expense WoW · <?= $analyticsSpendWeekDeltaPct >= 0 ? '↑' : '↓' ?> <?= Str::e((string) abs((float) $analyticsSpendWeekDeltaPct)) ?>%</p>
            <?php endif; ?>
        </div>
        <div id="txSparkInc" class="absolute bottom-1 left-0 right-0 h-[52px] opacity-55 pointer-events-none z-[5]"></div>
    </div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-rose-400 via-rose-600 to-rose-950 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(244,63,94,0.45)] ring-1 ring-white/20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.22),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/25 to-transparent"></div>
        <div class="relative z-10 pr-1 pb-14">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85">Expenses</p>
            <p class="text-2xl sm:text-[1.65rem] font-extrabold tabular-nums tracking-tight mt-2 leading-none kpi-counter" data-kpi-target="<?= Str::e((string) round($exp, 2)) ?>" data-kpi-prefix="RM " data-kpi-decimals="2">RM 0.00</p>
            <?php if ($filterType !== 'income' && $analyticsSpendWeekDeltaPct !== null): ?>
                <p class="text-[11px] font-semibold mt-3 pt-2 border-t border-white/20 text-white/90"><?= ($analyticsSpendWeekDeltaPct ?? 0) >= 0 ? '↑' : '↓' ?> <?= Str::e((string) abs((float) ($analyticsSpendWeekDeltaPct ?? 0))) ?>% WoW</p>
            <?php endif; ?>
        </div>
        <div id="txSparkExp" class="absolute bottom-1 left-0 right-0 h-[52px] opacity-55 pointer-events-none z-[5]"></div>
    </div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-400 via-teal-600 to-slate-900 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(20,184,166,0.42)] ring-1 ring-white/20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.22),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/28 to-transparent"></div>
        <div class="relative z-10 pr-1 pb-14">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85">Net savings</p>
            <p class="text-2xl sm:text-[1.65rem] font-extrabold tabular-nums tracking-tight mt-2 leading-none kpi-counter" data-kpi-target="<?= Str::e((string) round($net, 2)) ?>" data-kpi-prefix="RM " data-kpi-decimals="2">RM 0.00</p>
            <p class="text-[11px] font-semibold mt-3 pt-2 border-t border-white/20 text-white/90"><?= $inc > 0 ? round($net / $inc * 100, 1) : 0 ?>% retained</p>
        </div>
        <div id="txSparkNet" class="absolute bottom-1 left-0 right-0 h-[52px] opacity-50 pointer-events-none z-[5]"></div>
    </div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-400 via-violet-700 to-indigo-950 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(139,92,246,0.42)] ring-1 ring-white/20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.22),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/28 to-transparent"></div>
        <div class="relative z-10 pr-1 pb-14">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85">Volume</p>
            <p class="text-2xl sm:text-[1.65rem] font-extrabold tabular-nums tracking-tight mt-2 leading-none kpi-counter" data-kpi-target="<?= Str::e((string) $cnt) ?>" data-kpi-prefix="" data-kpi-decimals="0">0</p>
            <p class="text-[11px] font-semibold mt-3 pt-2 border-t border-white/20 text-white/85">Against filters</p>
        </div>
        <div id="txSparkVol" class="absolute bottom-1 left-0 right-0 h-[52px] opacity-55 pointer-events-none z-[5]"></div>
    </div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-400 via-blue-700 to-slate-900 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(59,130,246,0.35)] ring-1 ring-white/20">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.2),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/25 to-transparent"></div>
        <div class="relative z-10">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85">Avg amount</p>
            <p class="text-2xl sm:text-[1.65rem] font-extrabold tabular-nums tracking-tight mt-2 leading-none kpi-counter" data-kpi-target="<?= Str::e((string) round($avg, 2)) ?>" data-kpi-prefix="RM " data-kpi-decimals="2">RM 0.00</p>
            <p class="text-[11px] font-semibold mt-4 pt-2 border-t border-white/20 text-white/85 tracking-tight">Mean |amt| · row basis</p>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-400 via-orange-600 to-orange-950 text-white p-5 min-h-[150px] sm:min-h-[158px]
        shadow-[0_14px_40px_-12px_rgba(245,158,11,0.4)] ring-1 ring-white/20 flex flex-col">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_100%_0%,rgba(255,255,255,0.22),transparent_55%)]"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/28 to-transparent"></div>
        <div class="relative z-10 flex-1 flex flex-col min-h-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-white/85 shrink-0"><?= Str::e($pTopCatLabel) ?></p>
            <?php if ($saving): ?>
            <div class="flex items-center gap-3 mt-3 pt-3 border-t border-white/25 min-w-0 flex-1">
                <div class="w-11 h-11 rounded-2xl bg-white/22 backdrop-blur-sm flex items-center justify-center shrink-0 ring-2 ring-white/35 shadow-inner">
                    <i data-lucide="<?= Str::e($catIconFn($saving['name'])) ?>" class="w-5 h-5 stroke-[2.25]"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[15px] font-bold truncate leading-snug"><?= Str::e((string) $saving['name']) ?></p>
                    <p class="text-sm font-extrabold tabular-nums mt-1 text-white tracking-tight">RM <?= number_format((float) $saving['total'], 2) ?></p>
                </div>
            </div>
        <?php else: ?>
            <p class="text-[13px] font-medium text-white/80 mt-auto pt-3">No qualifying category slice.</p>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- Filters -->
<form method="get" action="<?= Str::e(Url::to($txLensBasePath)) ?>" class="mb-8 md:mb-10 rounded-3xl border border-slate-200/80 dark:border-slate-700/70 bg-gradient-to-br from-white via-slate-50/90 to-white dark:from-[#111827] dark:via-[#0f1629] dark:to-[#0d1424] p-5 md:p-7 shadow-[0_12px_40px_-16px_rgba(15,23,42,0.15)] dark:shadow-[0_20px_50px_-24px_rgba(0,0,0,0.65)] ring-1 ring-slate-200/60 dark:ring-slate-600/30">
    <div class="flex flex-col lg:flex-row lg:items-end gap-6 lg:gap-8">
        <div class="flex-1 min-w-0">
            <?php View::partial('components/analytics/filter-lens-intro', [
                'eyebrow' => 'Lens',
                'title' => 'Analytics scope',
                'description' => $scopeNote,
            ]); ?>
            <div class="flex flex-wrap gap-x-5 gap-y-4 items-end">
                <?php if ($txShowUserLens): ?>
                <label class="text-slate-600 dark:text-slate-300 text-[11px] font-bold uppercase tracking-[0.12em]">User
                    <select name="user_id" class="block mt-2 min-h-[44px] w-full min-w-[160px] max-w-[220px] rounded-2xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-950/95 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 [color-scheme:light_dark] shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] focus:outline-none focus:ring-2 focus:ring-teal-500/50 dark:focus:ring-teal-400/40">
                        <option value="0">All users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= (int) $filterUserId === (int) $u['id'] ? 'selected' : '' ?>><?= Str::e((string) $u['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <label class="text-slate-600 dark:text-slate-300 text-[11px] font-bold uppercase tracking-[0.12em]">From
                    <input type="date" name="from" value="<?= Str::e((string) $filterFrom) ?>" class="block mt-2 min-h-[44px] rounded-2xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-950/95 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 [color-scheme:light_dark] shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] focus:outline-none focus:ring-2 focus:ring-teal-500/50" />
                </label>
                <label class="text-slate-600 dark:text-slate-300 text-[11px] font-bold uppercase tracking-[0.12em]">To
                    <input type="date" name="to" value="<?= Str::e((string) $filterTo) ?>" class="block mt-2 min-h-[44px] rounded-2xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-950/95 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 [color-scheme:light_dark] shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] focus:outline-none focus:ring-2 focus:ring-teal-500/50" />
                </label>
                <label class="text-slate-600 dark:text-slate-300 text-[11px] font-bold uppercase tracking-[0.12em]">Type
                    <select name="type" class="block mt-2 min-h-[44px] rounded-2xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-950/95 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 [color-scheme:light_dark] shadow-[inset_0_1px_2px_rgba(15,23,42,0.06)] focus:outline-none focus:ring-2 focus:ring-teal-500/50">
                        <option value="">All types</option>
                        <option value="income" <?= $filterType === 'income' ? 'selected' : '' ?>>Income</option>
                        <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>Expense</option>
                    </select>
                </label>
            </div>
        </div>
        <div class="flex flex-row lg:flex-col gap-3 shrink-0 lg:border-l lg:border-slate-200/80 lg:dark:border-slate-700/60 lg:pl-8">
            <button type="submit" class="flex-1 lg:flex-none rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-teal-800 hover:from-teal-400 hover:via-teal-600 hover:to-teal-950 text-white px-10 py-3.5 min-h-[48px] font-bold text-sm shadow-[0_10px_30px_-8px_rgba(20,184,166,0.55)] ring-1 ring-white/25 transition-all hover:brightness-105 flex items-center justify-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4 shrink-0"></i> Refresh
            </button>
            <a href="<?= Str::e(Url::to($txLensBasePath)) ?>" class="flex-1 lg:flex-none rounded-2xl border border-slate-300/90 dark:border-slate-600 text-center px-6 py-3.5 min-h-[48px] text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/90 transition-colors flex items-center justify-center">Clear</a>
        </div>
    </div>
</form>

<!-- Centerpiece chart -->
<section class="rounded-3xl border border-slate-200/75 dark:border-slate-700/55 bg-white dark:bg-gradient-to-br dark:from-[#10192c] dark:via-[#0d1424] dark:to-[#0d1424] p-6 md:p-8 mb-8 md:mb-10 shadow-[0_24px_50px_-28px_rgba(15,23,42,0.25)] dark:shadow-[0_28px_60px_-32px_rgba(0,0,0,0.75)] ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05] relative overflow-hidden">
    <div class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-gradient-to-bl from-teal-400/[0.12] dark:from-teal-400/[0.08] to-transparent"></div>
    <div class="relative flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-5 md:mb-6">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400 mb-2">Liquidity trajectory</p>
            <h2 class="text-xl md:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Cashflow runway</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed max-w-xl">Income vs expense with premium fills · window respects your filters</p>
        </div>
        <span class="inline-flex items-center gap-2 text-xs font-bold bg-slate-100/95 dark:bg-slate-800/95 text-slate-700 dark:text-slate-100 px-4 py-2 rounded-full self-start shrink-0 border border-slate-200/80 dark:border-slate-600/50 shadow-inner"><?= Str::e($analyticsSeriesFrom) ?> <span class="text-slate-400 dark:text-slate-500 font-normal">—</span> <?= Str::e($analyticsSeriesTo) ?></span>
    </div>
    <div id="txChartCashflow" class="-mx-1 sm:-mx-2 min-h-[260px] md:min-h-[300px] xl:min-h-[320px] w-full" style="min-height:clamp(260px,calc(28vh + 128px),480px);"></div>
</section>

<!-- Analytics grid -->
<section aria-label="Secondary analytics" class="grid grid-cols-1 xl:grid-cols-2 gap-6 md:gap-7 xl:gap-7 mb-10 md:mb-12">
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white/95 dark:bg-gradient-to-br dark:from-[#121a29] dark:to-[#0d1424] p-6 md:p-7 shadow-[0_14px_40px_-22px_rgba(15,23,42,0.18)] dark:shadow-[0_18px_45px_-24px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04] flex flex-col min-h-[300px] sm:min-h-[320px]">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base md:text-[1.07rem] font-bold text-slate-900 dark:text-white tracking-tight"><?= $filterType === 'income' ? 'Income categories' : 'Expense categories' ?></h3>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">Share of <?= $filterType === 'income' ? 'credit' : 'debit' ?> flow in chart window</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2.5 py-1 rounded-full">Mix</span>
        </div>
        <div id="txChartDonut" class="flex justify-center flex-1" style="min-height:clamp(220px, 28vw, 280px);"></div>
    </div>
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white/95 dark:bg-gradient-to-br dark:from-[#121a29] dark:to-[#0d1424] p-6 md:p-7 shadow-[0_14px_40px_-22px_rgba(15,23,42,0.18)] dark:shadow-[0_18px_45px_-24px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04] flex flex-col min-h-[300px] sm:min-h-[320px]">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base md:text-[1.07rem] font-bold text-slate-900 dark:text-white tracking-tight">Wallet distribution</h3>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">Base-currency attribution by wallet</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2.5 py-1 rounded-full">Wallets</span>
        </div>
        <div id="txChartWallet" class="flex-1" style="min-height:clamp(230px, 28vw, 290px);"></div>
    </div>
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white/95 dark:bg-gradient-to-br dark:from-[#121a29] dark:to-[#0d1424] p-6 md:p-7 shadow-[0_14px_40px_-22px_rgba(15,23,42,0.18)] dark:shadow-[0_18px_45px_-24px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04] flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base md:text-[1.07rem] font-bold text-slate-900 dark:text-white tracking-tight"><?= $filterType === 'income' ? 'Daily income' : 'Daily spending' ?></h3>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 leading-snug"><?= $filterType === 'income' ? 'Momentum of inflows' : 'Expense cadence · smoothed fills' ?></p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2.5 py-1 rounded-full">Momentum</span>
        </div>
        <div id="txChartSpendLine" style="min-height:clamp(200px, 26vw, 260px);"></div>
    </div>
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white/95 dark:bg-gradient-to-br dark:from-[#121a29] dark:to-[#0d1424] p-6 md:p-7 shadow-[0_14px_40px_-22px_rgba(15,23,42,0.18)] dark:shadow-[0_18px_45px_-24px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.03] dark:ring-white/[0.04] flex flex-col">
        <div class="flex items-start justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base md:text-[1.07rem] font-bold text-slate-900 dark:text-white tracking-tight">Transaction volume</h3>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">Operations density per calendar day</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2.5 py-1 rounded-full">Density</span>
        </div>
        <div id="txChartVolume" style="min-height:clamp(200px, 26vw, 260px);"></div>
    </div>
</section>

<!-- Supporting ledger -->
<section class="mb-8 md:mb-12 pb-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-400 mb-2">Supporting data</p>
            <h2 class="text-xl md:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Ledger preview</h2>
            <p class="text-sm md:text-[15px] text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">Showing newest <?= count($rows) ?> matches · CSV / PDF exports on <a class="font-bold text-teal-600 dark:text-teal-400 underline decoration-teal-500/35 hover:decoration-teal-500" href="<?= Str::e(Url::to($txReportsPath)) ?>">reports</a></p>
        </div>
    </div>

    <!-- Desktop -->
    <div class="hidden md:block rounded-3xl border border-slate-200/75 dark:border-slate-700/55 bg-white dark:bg-[#111a2a]/95 overflow-hidden shadow-[0_16px_45px_-28px_rgba(15,23,42,0.2)] dark:shadow-[0_20px_50px_-32px_rgba(0,0,0,0.6)] ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05]">
        <div class="max-h-[min(432px,calc(100vh-300px))] overflow-y-auto overflow-x-auto [scrollbar-width:thin] scroll-smooth">
            <table class="min-w-full text-[15px] leading-snug relative">
                <thead class="bg-gradient-to-b from-slate-50 via-slate-50 to-slate-100/80 dark:from-slate-800 dark:to-slate-900/95 sticky top-0 z-10 backdrop-blur-md border-b border-slate-200/90 dark:border-slate-700/80">
                    <tr class="text-left text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400 font-bold">
                        <th class="px-5 py-4 font-bold">When</th>
                        <?php if ($txShowUserColumn): ?><th class="px-5 py-4 font-bold">User</th><?php endif; ?>
                        <th class="px-5 py-4 font-bold">Details</th>
                        <th class="px-5 py-4 font-bold">Wallet</th>
                        <th class="px-5 py-4 font-bold">Type</th>
                        <th class="px-5 py-4 font-bold text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/95">
                    <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="<?= (int) $ledgerColSpan ?>" class="px-5 py-20 text-center">
                            <div class="flex flex-col items-center text-slate-400 gap-3">
                                <i data-lucide="database" class="w-12 h-12 opacity-30"></i>
                                <p class="font-medium text-slate-500 dark:text-slate-400">No transactions for this lens</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r):
                        $isInc = (($r['type'] ?? '') === 'income');
                        $cat   = (string) ($r['category_name'] ?? '');
                        ?>
                    <tr class="group hover:bg-teal-50/[0.42] dark:hover:bg-slate-800/65 transition-[background-color,box-shadow] duration-200 ease-out hover:shadow-[inset_4px_0_0_#0f766e] dark:hover:shadow-[inset_4px_0_0_#2dd4bf]">
                        <td class="px-5 py-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-[13px] font-medium tabular-nums"><?= Str::e((string) $r['transaction_date']) ?></td>
                        <?php if ($txShowUserColumn): ?><td class="px-5 py-4 font-semibold text-slate-700 dark:text-slate-200"><?= Str::e((string) ($r['username'] ?? '')) ?></td><?php endif; ?>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3 min-w-[172px] max-w-[380px]">
                                <span class="w-10 h-10 rounded-xl <?= $isInc ? 'bg-gradient-to-br from-emerald-100 to-emerald-200/70 dark:from-emerald-950/90 dark:to-emerald-950/40' : 'bg-gradient-to-br from-rose-100 to-rose-200/70 dark:from-rose-950/90 dark:to-rose-950/45' ?> flex items-center justify-center shrink-0 shadow-[inset_0_1px_0_rgba(255,255,255,0.45)] dark:shadow-inner ring-1 ring-black/[0.04] dark:ring-white/[0.07] transition-transform duration-200 group-hover:scale-105">
                                    <i data-lucide="<?= Str::e($catIconFn($cat)) ?>" class="w-[18px] h-[18px] stroke-[2.35] <?= $isInc ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' ?>"></i>
                                </span>
                                <div class="truncate min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-slate-50 truncate"><?= Str::e((string) $r['title']) ?></p>
                                    <?php if ($cat !== ''): ?>
                                        <p class="text-[12px] text-slate-500 dark:text-slate-400 truncate mt-0.5"><?= Str::e($cat) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-300 font-medium"><?= Str::e((string) $r['wallet_name']) ?></td>
                        <td class="px-5 py-4 align-middle space-y-1.5">
                            <?php if ($isInc): ?>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold tracking-wide uppercase bg-emerald-100 text-emerald-900 shadow-sm ring-1 ring-emerald-700/15 dark:bg-emerald-950/95 dark:text-emerald-200 dark:ring-emerald-500/20">Income</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold tracking-wide uppercase bg-rose-100 text-rose-900 shadow-sm ring-1 ring-rose-700/15 dark:bg-rose-950/95 dark:text-rose-200 dark:ring-rose-500/20">Expense</span>
                            <?php endif; ?>
                            <?php if (! empty($r['recurring_schedule_id'])): ?>
                                <span class="inline-flex align-middle rounded-md px-2 py-0.5 text-[10px] font-bold bg-violet-100 text-violet-900 dark:bg-violet-950/95 dark:text-violet-300 ring-1 ring-violet-700/15 dark:ring-violet-500/25" title="Recurring">Scheduled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-right font-extrabold tabular-nums text-[15px] tracking-tight <?= $isInc ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                            <?= $isInc ? '+' : '−' ?>RM <?= number_format((float) $r['amount'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile cards -->
    <div class="md:hidden space-y-4 pb-8">
        <?php foreach ($rows as $r):
            $isInc = (($r['type'] ?? '') === 'income');
            $cat   = (string) ($r['category_name'] ?? '');
            ?>
            <article class="rounded-3xl border border-slate-200/80 dark:border-slate-700/60 bg-white/95 dark:bg-gradient-to-br dark:from-[#121a29] dark:to-[#0d1424] p-5 shadow-[0_14px_40px_-26px_rgba(15,23,42,0.22)] dark:shadow-[0_18px_45px_-28px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.04] dark:ring-white/[0.06] active:scale-[0.99] transition-transform duration-150">
                <div class="flex justify-between gap-3">
                    <div class="flex gap-3 min-w-0">
                        <span class="w-11 h-11 rounded-2xl <?= $isInc ? 'bg-gradient-to-br from-emerald-100 to-emerald-200/70 dark:from-emerald-950/95 dark:to-emerald-950/40' : 'bg-gradient-to-br from-rose-100 to-rose-200/70 dark:from-rose-950/95 dark:to-rose-950/45' ?> flex items-center justify-center shrink-0 ring-1 ring-black/[0.05] dark:ring-white/[0.08] shadow-inner">
                            <i data-lucide="<?= Str::e($catIconFn($cat)) ?>" class="w-[20px] h-[20px] stroke-[2.35] <?= $isInc ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' ?>"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 dark:text-white truncate"><?= Str::e((string) $r['title']) ?></p>
                            <p class="text-xs text-slate-500"><?php if ($txShowUserColumn && isset($r['username'])): ?><?= Str::e((string) $r['username']) ?> · <?php endif; ?><?= Str::e((string) $r['transaction_date']) ?></p>
                            <?php if ($cat !== ''): ?>
                                <p class="text-xs text-slate-400 mt-0.5 truncate"><?= Str::e($cat) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-lg font-extrabold tabular-nums <?= $isInc ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $isInc ? '+' : '−' ?>RM <?= number_format((float) $r['amount'], 2) ?></p>
                        <?php if ($isInc): ?>
                            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400">INCOME</span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-rose-700 dark:text-rose-400">EXPENSE</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap gap-2 items-center justify-between">
                    <p class="text-xs text-slate-500 truncate flex items-center gap-1 max-w-[60%]">
                        <i data-lucide="wallet" class="w-3.5 h-3.5 shrink-0 opacity-70"></i>
                        <?= Str::e((string) $r['wallet_name']) ?>
                    </p>
                    <?php if (! empty($r['recurring_schedule_id'])): ?>
                        <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400">Recurring linked</span>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <p class="text-center text-sm text-slate-500 py-10">Nothing to show.</p>
            <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var dark = document.documentElement.classList.contains('dark');
    var fg = dark ? '#a8b3c9' : '#5b6475';
    var fgMuted = dark ? '#6b7589' : '#8b95a5';
    var gridClr = dark ? 'rgba(148,163,184,0.11)' : 'rgba(71,85,105,0.12)';
    var tipTheme = dark ? 'dark' : 'light';

    function fmtRm(v, dec) {
        var n = typeof dec === 'number' ? dec : 2;
        return 'RM ' + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: n, maximumFractionDigits: n });
    }
    function apexAxis(cat) {
        var many = !!(cat && cat.length > 16);
        return {
            axisBorder: { show: false },
            axisTicks: { show: false },
            categories: cat,
            labels: {
                style: {
                    colors: fgMuted,
                    fontSize: (typeof window !== 'undefined' && window.matchMedia('(min-width:1024px)').matches) ? '11px' : '10px',
                    fontWeight: 600
                },
                rotate: cat && cat.length > 22 ? -40 : (cat && cat.length > 14 ? -25 : 0),
                rotateAlways: cat && cat.length > 22,
                hideOverlappedLabels: true,
                trim: cat && cat.length > 26,
                maxHeight: cat && cat.length > 22 ? Math.min(64, cat.length > 34 ? 58 : 52) : (many ? 48 : undefined)
            }
        };
    }

    var labels = <?= json_encode($dailyLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
    var incSer = <?= json_encode($dailyInc, JSON_HEX_TAG) ?>;
    var expSer = <?= json_encode($dailyExp, JSON_HEX_TAG) ?>;
    var volSer = <?= json_encode($dailyVol, JSON_HEX_TAG) ?>;
    var spendLineSer = <?= json_encode(($filterType === 'income') ? $dailyInc : $dailyExp, JSON_HEX_TAG) ?>;
    var catLbl = <?= json_encode(array_column($analyticsCategoryBreakdown, 'name'), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    var catDat = <?= json_encode(array_map(static fn(array $r): float => round((float) $r['total'], 2), $analyticsCategoryBreakdown), JSON_HEX_TAG) ?>;
    var wlbl = <?= json_encode(array_column($analyticsWalletBreakdown, 'name'), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    var wdat = <?= json_encode(array_map(static fn(array $r): float => round((float) $r['total'], 2), $analyticsWalletBreakdown), JSON_HEX_TAG) ?>;

    var donutPalette = ['#f43f5e','#fb7185','#f97316','#eab308','#34d399','#2dd4bf','#38bdf8','#818cf8','#c084fc','#64748b'];
    var walletPalette = wlbl.map(function (_, i) {
        var hues = ['#0f766e','#0891b2','#6366f1','#9333ea','#db2777'];
        return hues[i % hues.length];
    });

    function sparkSparkline(el, data, color) {
        if (!el || typeof ApexCharts === 'undefined') return;
        var d = (data && data.length) ? data : [0, 1, 0];
        new ApexCharts(el, {
            chart: {
                type: 'area',
                sparkline: { enabled: true },
                animations: { enabled: true, speed: 600, easing: 'easeinout' }
            },
            series: [{ data: d }],
            stroke: { width: 2.5, curve: 'smooth' },
            colors: [color],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.35,
                    opacityFrom: 0.35,
                    opacityTo: 0.02,
                    stops: [0, 92, 100]
                }
            },
            tooltip: {
                enabled: false
            },
            theme: { mode: tipTheme }
        }).render();
    }

    sparkSparkline(document.querySelector('#txSparkInc'), <?= json_encode(count($sparkInc) ? $sparkInc : [0, 1, 0]) ?>, '#fff');
    sparkSparkline(document.querySelector('#txSparkExp'), <?= json_encode(count($sparkExp) ? $sparkExp : [0, 1, 0]) ?>, '#fff');
    sparkSparkline(document.querySelector('#txSparkNet'), <?= json_encode(count($sparkNetTail) ? $sparkNetTail : [0, 1, 0]) ?>, '#fff');
    sparkSparkline(document.querySelector('#txSparkVol'), <?= json_encode(count($sparkVol) ? array_map(static fn ($x): float => (float) $x, $sparkVol) : [0, 1, 0]) ?>, '#fff');

    var cashEl = document.querySelector('#txChartCashflow');
    if (cashEl && labels.length && typeof ApexCharts !== 'undefined') {
        new ApexCharts(cashEl, {
            chart: {
                type: 'area',
                fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif',
                foreColor: fg,
                height: Math.round(Math.min(476, Math.max(292, cashEl.offsetHeight || 372))),
                toolbar: {
                    show: true,
                    offsetY: -2,
                    tools: {
                        selection: false
                    },
                    export: {}
                },
                zoom: {
                    enabled: labels.length >= 36,
                    autoScaleAxis: true,
                    type: 'x'
                },
                dropShadow: { enabled: true, enabledOnSeries: [0], top: dark ? 3 : 2, blur: 14, opacity: dark ? 0.2 : 0.09, color: dark ? '#04785733' : '#10b98320' },
                redrawOnParentResize: true
            },
            theme: { mode: tipTheme },
            dataLabels: { enabled: false },
            series: [
                {
                    name: 'Income',
                    data: incSer
                },
                {
                    name: 'Expense',
                    data: expSer
                }
            ],
            markers: {
                size: labels.length <= 24 ? (labels.length <= 14 ? 3.25 : 0) : 0,
                hover: { sizeOffset: 4 },
                strokeWidth: 2,
                strokeColors: '#fff'
            },
            colors: ['#10b981', '#f43f5e'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: dark ? 'dark' : 'light',
                    type: 'vertical',
                    shadeIntensity: 0.55,
                    opacityFrom: dark ? 0.42 : 0.38,
                    opacityTo: 0.035,
                    stops: [0, 92, 100]
                }
            },
            stroke: { curve: 'smooth', width: [3, 3], dashArray: [0, 0] },
            xaxis: apexAxis(labels),
            yaxis: {
                labels: {
                    style: { colors: fgMuted, fontSize: '11px', fontWeight: 600 },
                    formatter: function (v) {
                        var nv = Number(v);
                        var av = Math.abs(nv);
                        if (av >= 1000000) return 'RM ' + (nv / 1000000).toFixed(1) + 'M';
                        if (av >= 1000) return 'RM ' + (nv / 1000).toFixed((Math.abs(Math.round(nv)) % 1000) === 0 ? 0 : 1) + 'k';
                        return 'RM ' + Math.round(nv).toLocaleString();
                    },
                    hideOverlappedLabels: true
                },
                tickAmount: 5
            },
            grid: {
                borderColor: gridClr,
                strokeDashArray: 5,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } },
                padding: { top: 10, left: 4, right: 10 }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontWeight: 600,
                markers: {
                    radius: 5,
                    width: 8,
                    height: 8
                },
                itemMargin: { horizontal: 16, vertical: 10 },
                labels: {
                    colors: fg,
                    useSeriesColors: false
                }
            },
            tooltip: {
                theme: tipTheme,
                shared: true,
                intersect: false,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif'
                },
                x: {},
                marker: {},
                y: {
                    formatter: function (value) {
                        return fmtRm(value);
                    }
                }
            },
            responsive: [{
                breakpoint: 1200,
                options: {
                    chart: {
                        height: Math.round(Math.min(432, Math.max(304, (cashEl && cashEl.offsetHeight) ? cashEl.offsetHeight : 356)))
                    },
                    legend: {
                        fontSize: '11px',
                        itemMargin: { horizontal: 10, vertical: 6 }
                    },
                    grid: { padding: { top: 6, left: 2, right: 8 } }
                }
            }, {
                breakpoint: 640,
                options: {
                    chart: {
                        height: Math.min(400, labels.length <= 31 ? 320 : 360),
                        zoom: {
                            enabled: false
                        }
                    },
                    legend: { horizontalAlign: 'center' },
                    markers: { size: 0 }
                }
            }]
        }).render();
    } else if (cashEl && typeof ApexCharts !== 'undefined') {
        cashEl.innerHTML = '<div class="flex flex-col items-center justify-center text-slate-400 dark:text-slate-500 py-24 px-6 text-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 mx-2"><p class="text-sm font-semibold">No dated activity</p><p class="text-xs mt-2 text-slate-400">Try widening filters or resetting scope</p></div>';
    }

    var donutEl = document.querySelector('#txChartDonut');
    if (donutEl && catDat.length && typeof ApexCharts !== 'undefined') {
        var donutTotalFmt = catDat.reduce(function (a, b) { return a + b; }, 0);
        new ApexCharts(donutEl, {
            chart: {
                type: 'donut',
                height: 310,
                background: 'transparent',
                animations: { speed: 700 }
            },
            theme: { mode: tipTheme },
            stroke: {
                colors: dark ? '#11182733' : '#ffffff',
                width: 2,
                dashArray: 0
            },
            labels: catLbl,
            series: catDat,
            colors: donutPalette,
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontWeight: 600,
                markers: {
                    radius: 4,
                    width: 7,
                    height: 7
                },
                itemMargin: { horizontal: 8, vertical: 5 },
                labels: {
                    colors: fg,
                    useSeriesColors: false
                },
                fontSize: '12px'
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
                                showAlways: true,
                                fontSize: '11px',
                                fontWeight: 700,
                                color: fgMuted,
                                label: 'Total',
                                formatter: function () {
                                    return fmtRm(donutTotalFmt, 0);
                                }
                            },
                            value: {
                                show: false
                            }
                        }
                    }
                }
            },
            tooltip: {
                theme: tipTheme,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif'
                },
                y: {
                    formatter: function (val) {
                        return fmtRm(val);
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            responsive: [{
                breakpoint: 1200,
                options: {
                    chart: { height: 292 },
                    plotOptions: { pie: { donut: { size: '64%' } } },
                    legend: { fontSize: '11px', itemMargin: { horizontal: 6, vertical: 4 } }
                }
            }, {
                breakpoint: 480,
                options: {
                    chart: { height: 285 },
                    legend: { offsetY: 4 }
                }
            }]
        }).render();
    } else if (donutEl) {
        donutEl.innerHTML = '<p class="text-sm text-center text-slate-400 dark:text-slate-500 font-medium py-20">Nothing to visualize</p>';
    }

    var wEl = document.querySelector('#txChartWallet');
    if (wlbl.length && wEl && typeof ApexCharts !== 'undefined') {
        new ApexCharts(wEl, {
            chart: {
                type: 'bar',
                toolbar: { show: false },
                height: typeof window !== 'undefined' && window.matchMedia('(min-width:1280px)').matches ? 292 : (window.matchMedia('(min-width:1024px)').matches ? 280 : 276),
                redrawOnParentResize: true
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: true,
                    distributed: true,
                    barHeight: '74%',
                    dataLabels: { position: 'top' }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: dark ? 'dark' : 'light',
                    shadeIntensity: 0.42,
                    type: 'horizontal',
                    opacityFrom: 0.95,
                    opacityTo: dark ? 0.65 : 0.78,
                    stops: [0, 100]
                }
            },
            colors: walletPalette,
            series: [{ name: 'Amount', data: wdat }],
            xaxis: {
                categories: wlbl,
                labels: {
                    formatter: function (v) {
                        return fmtRm(v, 0);
                    },
                    style: { colors: fgMuted, fontSize: '11px', fontWeight: 600 }
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: {
                    formatter: undefined
                }
            },
            yaxis: {
                labels: { style: { colors: fg, fontWeight: 600, fontSize: '12px' } }
            },
            theme: { mode: tipTheme },
            grid: { borderColor: gridClr, strokeDashArray: 4, padding: { top: 2, bottom: 0, left: 4, right: 22 } },
            dataLabels: { enabled: false },
            legend: { show: false },
            tooltip: {
                theme: tipTheme,
                intersect: false,
                style: { fontSize: '12px', fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif' },
                y: [{
                    formatter: function (val) {
                        return fmtRm(val);
                    }
                }]
            },
            responsive: [{
                breakpoint: 1200,
                options: {
                    chart: { height: 280 },
                    plotOptions: { bar: { barHeight: '70%' } },
                    grid: { padding: { right: 16 } },
                    xaxis: { labels: { style: { fontSize: '10px' } } }
                }
            }]
        }).render();
    } else if (wEl) {
        wEl.innerHTML = '<p class="text-sm text-center text-slate-400 dark:text-slate-500 font-medium py-20">No wallet mix</p>';
    }

    var spendColor = <?= json_encode($filterType === 'income' ? '#10b981' : '#f43f5e') ?>;
    var lineEl = document.querySelector('#txChartSpendLine');
    if (lineEl && labels.length && typeof ApexCharts !== 'undefined') {
        new ApexCharts(lineEl, {
            chart: { type: 'area', toolbar: { show: false }, height: 264, redrawOnParentResize: true },
            stroke: {
                curve: 'smooth',
                width: [2.8],
                lineCap: 'round'
            },
            series: [{
                name: <?= json_encode($filterType === 'income' ? 'Income' : 'Expense') ?>,
                data: spendLineSer
            }],
            colors: [spendColor],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.5,
                    type: 'vertical',
                    inverseColors: false,
                    opacityFrom: dark ? 0.38 : 0.42,
                    opacityTo: 0.02,
                    stops: [0, 94, 100]
                }
            },
            markers: {
                size: labels.length <= 18 ? (labels.length <= 10 ? 3 : 0) : 0,
                strokeWidth: 2,
                strokeColors: '#fff',
                hover: { sizeOffset: 4 }
            },
            theme: { mode: tipTheme },
            xaxis: apexAxis(labels),
            yaxis: {
                labels: {
                    style: { colors: fgMuted, fontWeight: 600, fontSize: '11px' },
                    formatter: function (v) {
                        return 'RM ' + Math.round(Number(v)).toLocaleString();
                    },
                    hideOverlappedLabels: true
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tickAmount: 5
            },
            grid: {
                borderColor: gridClr,
                strokeDashArray: 5,
                padding: { top: 6, bottom: labels.length > 18 ? 8 : 0 },
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            tooltip: {
                theme: tipTheme,
                shared: false,
                intersect: true,
                style: {
                    fontSize: '11px',
                    fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif'
                },
                y: {
                    formatter: function (val) {
                        return fmtRm(val);
                    }
                }
            },
            responsive: [{
                breakpoint: 1200,
                options: {
                    chart: { height: 244 },
                    grid: { padding: { bottom: labels.length > 22 ? 10 : 4 } },
                    markers: { size: labels.length <= 20 ? (labels.length <= 11 ? 3 : 0) : 0 }
                }
            }, {
                breakpoint: 480,
                options: {
                    markers: { size: labels.length <= 12 ? 2.75 : 0 },
                    legend: {},
                    chart: { height: 228 }
                }
            }]
        }).render();
    } else if (lineEl) {
        lineEl.innerHTML = '<p class="text-sm text-center text-slate-400 dark:text-slate-500 font-medium py-20">No daily series</p>';
    }

    var volEl = document.querySelector('#txChartVolume');
    if (volEl && labels.length && typeof ApexCharts !== 'undefined') {
        new ApexCharts(volEl, {
            chart: { type: 'bar', toolbar: { show: false }, height: 268, redrawOnParentResize: true },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '62%',
                    dataLabels: { position: 'top' }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: dark ? 'dark' : 'light',
                    shadeIntensity: 0.42,
                    type: 'vertical',
                    opacityFrom: dark ? 0.9 : 0.95,
                    opacityTo: dark ? 0.48 : 0.58,
                    stops: [0, 92, 100]
                },
                opacity: 1
            },
            series: [{
                name: 'Transactions',
                data: volSer
            }],
            colors: ['#6366f1'],
            theme: { mode: tipTheme },
            xaxis: apexAxis(labels),
            yaxis: {
                labels: {
                    style: { colors: fgMuted, fontWeight: 600, fontSize: '11px' },
                    formatter: function (v) {
                        return Math.round(Number(v)).toLocaleString();
                    },
                    hideOverlappedLabels: true
                },
                tickAmount: 5,
                forceNiceScale: true,
                decimalsInFloat: 0
            },
            grid: {
                borderColor: gridClr,
                strokeDashArray: 5,
                padding: { top: 14, bottom: labels.length > 18 ? 6 : 0 },
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            tooltip: {
                theme: tipTheme,
                shared: false,
                intersect: true,
                x: {},
                style: { fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif' },
                y: {
                    formatter: function (val) {
                        var n = Number(val);
                        return n === 1 ? '1 txn' : n.toLocaleString() + ' txns';
                    }
                }
            },
            responsive: [{
                breakpoint: 1200,
                options: {
                    chart: { height: 246 },
                    plotOptions: { bar: { columnWidth: '56%' } },
                    grid: { padding: { top: 10 } }
                }
            }, {
                breakpoint: 480,
                options: {
                    chart: { height: 228 },
                    plotOptions: { bar: { columnWidth: '70%' } }
                }
            }]
        }).render();
    } else if (volEl) {
        volEl.innerHTML = '<p class="text-sm text-center text-slate-400 dark:text-slate-500 font-medium py-20">No volume</p>';
    }

    document.querySelectorAll('.kpi-counter').forEach(function (el) {
        var target = parseFloat(String(el.getAttribute('data-kpi-target') ?? ''), 10);
        if (isNaN(target)) target = 0;
        var prefix = el.getAttribute('data-kpi-prefix') || '';
        var dec = parseInt(el.getAttribute('data-kpi-decimals'), 10);
        if (isNaN(dec)) dec = 2;
        var dur = 940, t0 = null;
        function fmt(v) {
            if (dec === 0) return prefix + Math.round(v).toLocaleString();
            return prefix + v.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }
        function ease(p) {
            return 1 - Math.pow(1 - p, 3);
        }
        function step(ts) {
            if (!t0) t0 = ts;
            var p = Math.min(1, (ts - t0) / dur);
            var cur = target * ease(p);
            el.textContent = fmt(cur);
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = fmt(target);
        }
        requestAnimationFrame(step);
    });

    if (typeof lucide !== 'undefined') lucide.createIcons();
})();
</script>
