<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$rows = $rows ?? [];
$users = $users ?? [];
$walletTypes = $walletTypes ?? [];
$currencies = $currencies ?? [];
$filters = $filters ?? [
    'user_id' => 0,
    'wallet_type_id' => 0,
    'currency_id' => 0,
    'status' => 'all',
    'owner_analytics' => 'all',
    'search' => '',
    'low_balance' => '0',
];
$walletKpis = $walletKpis ?? [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'low_balance' => 0,
    'total_balance' => 0.0,
    'type_count' => 0,
    'owners' => 0,
];
$walletTypeChartLabels = $walletTypeChartLabels ?? [];
$walletTypeChartSeries = $walletTypeChartSeries ?? [];
$balanceBucketLabels = $balanceBucketLabels ?? [];
$balanceBucketSeries = $balanceBucketSeries ?? [];
$lowBalanceRows = $lowBalanceRows ?? [];
$topBalanceRows = $topBalanceRows ?? [];
$filterQueryString = $filterQueryString ?? '';
$baseCurrency = $baseCurrency ?? 'MYR';
$message = $message ?? null;
$error = $error ?? null;

$kTotal = (int) ($walletKpis['total'] ?? 0);
$kActive = (int) ($walletKpis['active'] ?? 0);
$kInactive = (int) ($walletKpis['inactive'] ?? 0);
$kLow = (int) ($walletKpis['low_balance'] ?? 0);
$kBal = (float) ($walletKpis['total_balance'] ?? 0);
$kTypes = (int) ($walletKpis['type_count'] ?? 0);
$kOwners = (int) ($walletKpis['owners'] ?? 0);

$intelShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50 to-teal-50/65 dark:from-[#0c1426] dark:via-[#0d1629] dark:to-teal-950/25 px-5 py-4 sm:px-6 sm:py-5 shadow-[0_20px_54px_-28px_rgba(15,23,42,0.16)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.05]';
$chartCard = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';
$tableShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-white/95 dark:bg-[#0d1424] shadow-xl ring-1 ring-slate-900/[0.06] dark:ring-white/[0.05] overflow-hidden';
$filterPanel = 'rounded-2xl border-2 border-teal-500/35 dark:border-teal-600/30 bg-gradient-to-br from-white via-teal-50/40 to-white dark:from-[#0d1424] dark:via-teal-950/20 dark:to-[#0c1426] p-5 sm:p-6 shadow-lg ring-1 ring-teal-900/[0.06] dark:ring-teal-500/10';
?>

<?php if ($message): ?>
    <div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="mb-6 <?= Str::e($intelShell) ?>">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-teal-600 dark:text-teal-400">Accounts</p>
            <h1 class="mt-1 text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">Wallet / account operations center</h1>
            <p class="mt-1 text-xs sm:text-[13px] text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                Summary-first overview, distribution analytics, and a searchable ledger of custody — edits live on each wallet&rsquo;s detail page.
                Balances shown in <strong class="text-slate-700 dark:text-slate-200"><?= Str::e($baseCurrency) ?></strong> base for comparability.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <button type="button" id="openCreateWalletModal" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-5 py-3 text-sm font-bold shadow-lg shadow-teal-900/25 hover:opacity-95 ring-1 ring-teal-950/20">
                <i data-lucide="plus-circle" class="w-5 h-5"></i> New wallet
            </button>
            <a href="<?= Str::e(Url::to('/admin/wallet-types')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/70 px-4 py-3 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                <i data-lucide="layers" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i> Wallet types
            </a>
        </div>
    </div>
</div>

<!-- KPI -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-7">
<?php
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(13,148,136,0.42)] relative overflow-hidden ring-1 ring-teal-950/18 dark:ring-white/15',
    'label' => 'Total wallets',
    'value' => number_format($kTotal),
    'footnote' => $kActive . ' active · ' . $kInactive . ' inactive · matches filters',
    'icon' => 'layers',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-sky-500 via-indigo-600 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(2,132,199,0.38)] relative overflow-hidden ring-1 ring-sky-950/18 dark:ring-white/15',
    'label' => 'Total balance (base)',
    'value' => $baseCurrency . ' ' . number_format($kBal, 0),
    'footnote' => 'Across visible wallets · FX via platform rates',
    'icon' => 'landmark',
    'valueClass' => 'text-2xl sm:text-3xl font-extrabold mt-1 tabular-nums tracking-tight',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-rose-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(194,65,12,0.36)] relative overflow-hidden ring-1 ring-orange-950/18 dark:ring-white/15',
    'label' => 'Low balance alerts',
    'value' => number_format($kLow),
    'footnote' => 'Below per-wallet minimum (est. base)',
    'icon' => 'bell-ring',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-violet-500 via-fuchsia-700 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(147,51,234,0.38)] relative overflow-hidden ring-1 ring-violet-950/18 dark:ring-white/15',
    'label' => 'Members & types',
    'value' => number_format($kOwners) . ' · ' . number_format($kTypes),
    'footnote' => 'Distinct owners · Distinct types (in view)',
    'icon' => 'users',
    'valueClass' => 'text-2xl font-extrabold mt-1 tabular-nums tracking-tight',
]);
?>
</div>

<!-- Analytics -->
<div class="grid lg:grid-cols-12 gap-5 mb-8">
    <div class="lg:col-span-5"><?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Wallet type mix',
        'subtitle' => 'Count by account type · filtered cohort',
        'chartId' => 'adminWalletTypeDonut',
        'badgeText' => 'Types',
        'badgeClass' => 'text-[10px] font-bold bg-teal-50 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-teal-300/75 dark:ring-teal-800/50',
        'cardClass' => $chartCard,
        'chartContainerClass' => 'mt-3 min-h-[220px]',
    ]); ?></div>
    <div class="lg:col-span-7"><?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Balance distribution',
        'subtitle' => 'Buckets in ' . $baseCurrency . ' base · filtered cohort',
        'chartId' => 'adminWalletBalBucket',
        'badgeText' => 'Liquidity',
        'badgeClass' => 'text-[10px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-800 dark:text-sky-300 px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-sky-300/75 dark:ring-sky-800/50',
        'cardClass' => $chartCard,
        'chartContainerClass' => 'mt-3 min-h-[220px]',
    ]); ?></div>
    <div class="lg:col-span-6 <?= Str::e($chartCard) ?>">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 tracking-tight">Low balance watchlist</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Wallets under minimum threshold · up to 12</p>
            </div>
        </div>
        <?php if ($lowBalanceRows === []): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 py-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">None in this view.</p>
        <?php else: ?>
        <ul class="space-y-2 max-h-[280px] overflow-y-auto scrollbar-none pr-1">
            <?php foreach ($lowBalanceRows as $lw):
                $lid = (int) ($lw['id'] ?? 0);
                ?>
            <li>
                <a href="<?= Str::e(Url::to('/admin/wallets/' . $lid)) ?>" class="flex items-center gap-3 rounded-xl border border-amber-200/90 dark:border-amber-900/55 bg-amber-50/70 dark:bg-amber-950/25 px-3 py-2.5 hover:border-teal-400/60 transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500 to-rose-600 flex items-center justify-center text-white shrink-0"><i data-lucide="alert-triangle" class="w-4 h-4"></i></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= Str::e((string) ($lw['name'] ?? '')) ?></p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= Str::e((string) ($lw['username'] ?? '')) ?> · <?= Str::e((string) ($lw['currency_code'] ?? '')) ?></p>
                    </div>
                    <span class="text-xs font-bold tabular-nums text-amber-900 dark:text-amber-200 shrink-0"><?= Str::e($baseCurrency) ?> <?= number_format((float) ($lw['balance_base'] ?? 0), 0) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="lg:col-span-6 <?= Str::e($chartCard) ?>">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 tracking-tight">Top wallets by balance</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5"><?= Str::e($baseCurrency) ?> base · up to 8</p>
            </div>
        </div>
        <?php if ($topBalanceRows === []): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 py-8 text-center border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">No data.</p>
        <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($topBalanceRows as $tw):
                $tid = (int) ($tw['id'] ?? 0);
                ?>
            <li>
                <a href="<?= Str::e(Url::to('/admin/wallets/' . $tid)) ?>" class="flex items-center gap-3 rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2.5 hover:border-teal-400/60 transition-colors">
                    <span class="text-sm font-bold text-teal-700 dark:text-teal-300 w-6 shrink-0 text-center">#</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= Str::e((string) ($tw['name'] ?? '')) ?></p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= Str::e((string) ($tw['username'] ?? '')) ?></p>
                    </div>
                    <span class="text-sm font-extrabold tabular-nums text-emerald-700 dark:text-emerald-400 shrink-0"><?= Str::e($baseCurrency) ?> <?= number_format((float) ($tw['balance_base'] ?? 0), 0) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="mb-7 <?= Str::e($filterPanel) ?>">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div class="flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white shadow-md shadow-teal-900/20"><i data-lucide="filter" class="w-5 h-5"></i></span>
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white tracking-tight">Filter &amp; search</h2>
                <p class="text-[11px] text-slate-600 dark:text-slate-400">Refine the cohort below — KPIs and charts match these rules.</p>
            </div>
        </div>
        <a href="<?= Str::e(Url::to('/admin/wallets')) ?>" class="text-xs font-bold text-teal-700 dark:text-teal-300 hover:underline shrink-0">Reset all</a>
    </div>
    <form method="get" action="<?= Str::e(Url::to('/admin/wallets')) ?>" class="grid sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3 sm:gap-4">
        <div class="lg:col-span-2">
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Search</label>
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" name="q" value="<?= Str::e((string) ($filters['search'] ?? '')) ?>" placeholder="Wallet name, username, email…" class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm" />
            </div>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Owner</label>
            <select name="user_id" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="0">All users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>><?= Str::e((string) $u['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Wallet type</label>
            <select name="wallet_type_id" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="0">All types</option>
                <?php foreach ($walletTypes as $wt): ?>
                    <option value="<?= (int) $wt['id'] ?>" <?= (int) ($filters['wallet_type_id'] ?? 0) === (int) $wt['id'] ? 'selected' : '' ?>><?= Str::e((string) $wt['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Currency</label>
            <select name="currency_id" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="0">All</option>
                <?php foreach ($currencies as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) ($filters['currency_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= Str::e((string) $c['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="all" <?= ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>Active + inactive</option>
                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active only</option>
                <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive only</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Owner analytics</label>
            <select name="owner_analytics" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="all" <?= ($filters['owner_analytics'] ?? 'all') === 'all' ? 'selected' : '' ?>>All owners</option>
                <option value="included" <?= ($filters['owner_analytics'] ?? '') === 'included' ? 'selected' : '' ?>>Included in reports</option>
                <option value="excluded" <?= ($filters['owner_analytics'] ?? '') === 'excluded' ? 'selected' : '' ?>>Excluded from reports</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Alerts</label>
            <select name="low_balance" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                <option value="0" <?= ($filters['low_balance'] ?? '0') !== '1' ? 'selected' : '' ?>>All wallets</option>
                <option value="1" <?= ($filters['low_balance'] ?? '') === '1' ? 'selected' : '' ?>>Low balance only</option>
            </select>
        </div>
        <div class="sm:col-span-2 lg:col-span-4 xl:col-span-2 flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-slate-900 dark:bg-teal-700 text-white py-3 text-sm font-bold shadow-md hover:opacity-95">Apply filters</button>
        </div>
    </form>
</div>

<!-- Listing -->
<div class="<?= Str::e($tableShell) ?>">
    <div class="px-4 sm:px-6 py-4 border-b border-slate-200/90 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 bg-gradient-to-r from-slate-50 to-white dark:from-[#0f172a] dark:to-[#0d1424]">
        <div>
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Wallet directory</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400"><?= number_format(count($rows)) ?> in view</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50/95 dark:bg-slate-900/60 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="px-4 py-3">Wallet</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Balance (<?= Str::e($baseCurrency) ?>)</th>
                    <th class="px-4 py-3 text-right">Opening</th>
                    <th class="px-4 py-3 text-right">Min threshold</th>
                    <th class="px-4 py-3">CCY</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Analytics</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/90">
            <?php foreach ($rows as $w):
                $wid = (int) $w['id'];
                $uid = (int) $w['user_id'];
                $wname = (string) ($w['name'] ?? '');
                $active = ! empty($w['is_active']);
                $inAn = ! empty($w['owner_include_in_analytics']);
                $balB = (float) ($w['balance_base'] ?? 0);
                $low = ! empty($w['below_threshold']);
                ?>
                <tr class="hover:bg-slate-50/90 dark:hover:bg-slate-800/30 transition-colors">
                    <td class="px-4 py-3">
                        <p class="font-bold text-slate-900 dark:text-white"><?= Str::e($wname) ?></p>
                        <?php if (! empty($w['is_default'])): ?><span class="text-[10px] font-bold text-violet-600 dark:text-violet-400">Default</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <a href="<?= Str::e(Url::to('/admin/users/' . $uid)) ?>" class="font-semibold text-teal-700 dark:text-teal-300 hover:underline"><?= Str::e((string) ($w['username'] ?? '')) ?></a>
                        <p class="text-[11px] text-slate-500 truncate max-w-[180px]"><?= Str::e((string) ($w['email'] ?? '')) ?></p>
                    </td>
                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold"><i data-lucide="<?= Str::e((string) ($w['type_icon'] ?? 'wallet')) ?>" class="w-3.5 h-3.5"></i><?= Str::e((string) ($w['type_label'] ?? '')) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums font-bold <?= $low ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white' ?>">
                        <?= number_format($balB, 2) ?>
                        <?php if ($low): ?><span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500 ml-1 align-middle" title="Below minimum"></span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300"><?= number_format((float) ($w['opening_balance'] ?? 0), 2) ?></td>
                    <td class="px-4 py-3 text-right tabular-nums text-slate-600 dark:text-slate-400"><?= isset($w['min_balance_threshold']) && $w['min_balance_threshold'] !== null && $w['min_balance_threshold'] !== '' ? number_format((float) $w['min_balance_threshold'], 2) : '—' ?></td>
                    <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></td>
                    <td class="px-4 py-3">
                        <?php if ($active): ?>
                            <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 px-2 py-0.5 text-[10px] font-bold">Active</span>
                        <?php else: ?>
                            <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2 py-0.5 text-[10px] font-bold">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($inAn): ?>
                            <span class="text-[10px] font-bold text-teal-700 dark:text-teal-300">Included</span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold text-amber-800 dark:text-amber-200">Excluded</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex flex-wrap gap-1 justify-end">
                            <a href="<?= Str::e(Url::to('/admin/wallets/' . $wid)) ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-teal-500/60" title="View / edit"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                            <?php if ($active): ?>
                            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/status')) ?>" class="inline" onsubmit="return confirm('Deactivate this wallet?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                                <input type="hidden" name="wallet_user_id" value="<?= $uid ?>" />
                                <input type="hidden" name="is_active" value="0" />
                                <input type="hidden" name="_return_query" value="<?= Str::e($filterQueryString) ?>" />
                                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-200" title="Deactivate"><i data-lucide="pause-circle" class="w-4 h-4"></i></button>
                            </form>
                            <?php else: ?>
                            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/status')) ?>" class="inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                                <input type="hidden" name="wallet_user_id" value="<?= $uid ?>" />
                                <input type="hidden" name="is_active" value="1" />
                                <input type="hidden" name="_return_query" value="<?= Str::e($filterQueryString) ?>" />
                                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-200" title="Activate"><i data-lucide="play-circle" class="w-4 h-4"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/delete')) ?>" class="inline" onsubmit="return confirm('Delete this wallet permanently? Only succeeds when ledger is empty.');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                                <input type="hidden" name="wallet_user_id" value="<?= $uid ?>" />
                                <input type="hidden" name="_return_query" value="<?= Str::e($filterQueryString) ?>" />
                                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-rose-200 dark:border-rose-900/55 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300" title="Delete if safe"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($rows === []): ?>
        <p class="px-6 py-14 text-center text-sm text-slate-500 dark:text-slate-400">No wallets match these filters.</p>
    <?php endif; ?>
</div>

<!-- Create modal -->
<div id="createWalletModal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/55 dark:bg-black/70 backdrop-blur-[2px]" data-close-wallet-modal></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl border border-slate-200/95 dark:border-slate-700 bg-white dark:bg-[#0f172a] shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-teal-50/90 to-white dark:from-teal-950/30 dark:to-[#0f172a]">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-teal-600 dark:text-teal-400">Provision</p>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">New wallet</h3>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" data-close-wallet-modal aria-label="Close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/store')) ?>" class="p-5 space-y-3 max-h-[80vh] overflow-y-auto">
                <?= Csrf::field() ?>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Owner</label>
                    <select name="user_id" required class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold"><?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>"><?= Str::e((string) $u['username']) ?> · <?= Str::e((string) $u['email']) ?></option>
                    <?php endforeach; ?></select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Wallet name</label>
                    <input name="name" required class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 px-3 py-2.5 text-sm" placeholder="e.g. Main checking" />
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Type</label>
                        <select name="wallet_type_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($walletTypes as $wt): if (empty($wt['is_active'])) {
                            continue;
                        } ?>
                            <option value="<?= (int) $wt['id'] ?>"><?= Str::e((string) $wt['label']) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Currency</label>
                        <select name="currency_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($currencies as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
                        <?php endforeach; ?></select>
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Opening balance</label>
                        <input name="opening_balance" type="number" step="0.01" value="0" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Min alert (native)</label>
                        <input name="min_balance_threshold" type="number" step="0.01" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" placeholder="Optional" />
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm" placeholder="Optional"></textarea>
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300" /> Active
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300" /> Default for owner
                    </label>
                </div>
                <input type="hidden" name="sort_order" value="0" />
                <div class="flex gap-2 pt-2">
                    <button type="button" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 py-3 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800" data-close-wallet-modal>Cancel</button>
                    <button type="submit" class="flex-1 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white py-3 text-sm font-bold shadow-md">Create wallet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    var typeLbl = <?= json_encode($walletTypeChartLabels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    var typeSer = <?= json_encode(array_map(static fn ($n): float => round((float) $n, 2), $walletTypeChartSeries), JSON_THROW_ON_ERROR) ?>;
    var buckLbl = <?= json_encode($balanceBucketLabels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    var buckSer = <?= json_encode(array_map(static fn ($n): float => round((float) $n, 2), $balanceBucketSeries), JSON_THROW_ON_ERROR) ?>;
    var typeChart = null;
    var barChart = null;
    function mountCharts() {
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') return;
        var tt = KhfApexTheme.tokens();
        var tEl = document.querySelector('#adminWalletTypeDonut');
        if (tEl) {
            try { if (typeChart) typeChart.destroy(); } catch (e) {}
            typeChart = null;
            var tSum = typeSer.reduce(function(a,b){ return a+b; }, 0);
            if (!typeLbl.length || tSum <= 0) {
                tEl.innerHTML = KhfApexTheme.emptyStateHtml('No wallets in view.');
            } else {
                typeChart = new ApexCharts(tEl, Object.assign({}, KhfApexTheme.chart({ type: 'donut', height: 240 }), {
                    labels: typeLbl,
                    series: typeSer,
                    colors: ['#0d9488', '#6366f1', '#f59e0b', '#ec4899', '#0ea5e9', '#8b5cf6', '#22c55e', '#f43f5e'],
                    stroke: { show: true, width: KhfApexTheme.isDark() ? 2 : 1.25, colors: [tt.donutRingStroke] },
                    legend: KhfApexTheme.legendBottom({ fontSize: '11px' }),
                    plotOptions: { pie: { donut: { size: '68%' } } },
                    dataLabels: { enabled: false },
                    tooltip: KhfApexTheme.tooltip()
                }));
                typeChart.render();
            }
        }
        var bEl = document.querySelector('#adminWalletBalBucket');
        if (bEl) {
            try { if (barChart) barChart.destroy(); } catch (e) {}
            barChart = null;
            var bSum = buckSer.reduce(function(a,b){ return a+b; }, 0);
            if (!buckLbl.length || bSum <= 0) {
                bEl.innerHTML = KhfApexTheme.emptyStateHtml('No wallets in view.');
            } else {
                barChart = new ApexCharts(bEl, Object.assign({}, KhfApexTheme.chart({ type: 'bar', height: 240 }), {
                    plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '62%' } },
                    colors: ['#38bdf8'],
                    series: [{ name: 'Wallets', data: buckSer }],
                    xaxis: { categories: buckLbl, labels: { style: { colors: tt.axisLabel, fontWeight: 600, fontSize: '11px' } } },
                    yaxis: { labels: { style: { colors: tt.axisLabel, fontWeight: 600 } } },
                    grid: KhfApexTheme.grid({ padding: { left: 0, right: 8 } }),
                    dataLabels: { enabled: false },
                    tooltip: KhfApexTheme.tooltip()
                }));
                barChart.render();
            }
        }
    }
    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(mountCharts);
    } else if (window.ApexCharts) {
        mountCharts();
    }

    var modal = document.getElementById('createWalletModal');
    function openM() { if (modal) { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('overflow-hidden'); if (window.lucide) lucide.createIcons(); } }
    function closeM() { if (modal) { modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('overflow-hidden'); } }
    var ob = document.getElementById('openCreateWalletModal');
    if (ob) ob.addEventListener('click', openM);
    if (modal) modal.querySelectorAll('[data-close-wallet-modal]').forEach(function (n) { n.addEventListener('click', closeM); });
})();
</script>
