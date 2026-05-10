<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$rows = $rows ?? [];
$userKpis = $userKpis ?? [];
$baseCurrency = $baseCurrency ?? 'MYR';
$adminSelfId = $adminSelfId ?? null;
$message = $message ?? null;
$error = $error ?? null;
$analyticsFilter = $analyticsFilter ?? 'all';

$kTotal = (int) ($userKpis['total'] ?? 0);
$kActive = (int) ($userKpis['active'] ?? 0);
$kInactive = (int) ($userKpis['inactive'] ?? 0);
$kNewMo = (int) ($userKpis['new_this_month'] ?? 0);
$kNew7 = (int) ($userKpis['new_last_7d'] ?? 0);
$kWallets = (int) ($userKpis['wallet_count'] ?? 0);
$kRecurringUsers = (int) ($userKpis['recurring_users'] ?? 0);
$kLowBal = (int) ($userKpis['low_balance_users'] ?? 0);
$recentRegs = $userKpis['recent_registrations'] ?? [];
$signupByMonth = $userKpis['signup_by_month'] ?? [];
$kAnIn = (int) ($userKpis['analytics_included'] ?? 0);
$kAnEx = (int) ($userKpis['analytics_excluded'] ?? 0);

$signupLabels = array_map(static fn (array $r): string => (string) ($r['ym'] ?? ''), $signupByMonth);
$signupCounts = array_map(static fn (array $r): int => (int) ($r['c'] ?? 0), $signupByMonth);

$intelStripShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50 to-teal-50/65 dark:from-[#0c1426] dark:via-[#0d1629] dark:to-teal-950/25 px-5 py-4 sm:px-6 sm:py-5 shadow-[0_20px_54px_-28px_rgba(15,23,42,0.16),0_6px_18px_-6px_rgba(15,23,42,0.085)] dark:shadow-[0_20px_50px_-32px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.05]';
$chartCardShell = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15),0_8px_24px_-10px_rgba(15,23,42,0.08)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.65)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06] relative overflow-hidden';
$tableShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-white/95 dark:bg-[#0d1424] shadow-[0_20px_50px_-28px_rgba(15,23,42,0.12)] dark:shadow-[0_24px_56px_-36px_rgba(0,0,0,0.55)] ring-1 ring-slate-900/[0.06] dark:ring-white/[0.05] overflow-hidden';
?>

<?php if (! empty($message)): ?>
<div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 dark:border-emerald-800/60 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if (! empty($error)): ?>
<div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 dark:border-rose-800/60 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<!-- Overview frame -->
<div class="mb-6 <?= Str::e($intelStripShell) ?>">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-teal-600 dark:text-teal-400">User management</p>
            <h2 class="mt-1 text-lg sm:text-xl font-bold tracking-tight text-slate-900 dark:text-white">Directory & governance</h2>
            <p class="mt-1 text-xs sm:text-[13px] text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">Searchable roster with liquidity signals, automation footprint, and lifecycle controls — without inline schema editing.</p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <button type="button" id="openCreateUserModal" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2.5 text-xs font-bold shadow-lg shadow-teal-900/25 hover:opacity-95 transition-opacity ring-1 ring-teal-950/20">
                <i data-lucide="user-plus" class="w-4 h-4"></i> New user
            </button>
            <a href="<?= Str::e(Url::to('/admin/wallets')) ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/70 px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                <i data-lucide="wallet" class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400"></i> Wallets
            </a>
        </div>
    </div>
</div>

<!-- KPI grid -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6 sm:mb-7">
<?php
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-violet-500 via-violet-600 to-violet-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(91,33,182,0.42)] dark:shadow-xl dark:shadow-violet-500/28 relative overflow-hidden ring-1 ring-violet-950/18 dark:ring-white/15',
    'label' => 'Total users',
    'value' => number_format($kTotal),
    'footnote' => $kAnEx > 0
        ? (number_format($kAnIn) . ' included in analytics · ' . number_format($kAnEx) . ' excluded · all roles')
        : 'Platform directory · all roles · all in analytics',
    'icon' => 'users',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-950 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(5,122,85,0.44)] dark:shadow-xl dark:shadow-emerald-500/25 relative overflow-hidden ring-1 ring-emerald-950/20 dark:ring-white/15',
    'label' => 'Active',
    'value' => number_format($kActive),
    'footnote' => $kInactive > 0 ? (number_format($kInactive) . ' suspended / inactive') : 'Full platform access',
    'icon' => 'user-check',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-sky-500 via-sky-600 to-indigo-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(2,132,199,0.4)] dark:shadow-xl dark:shadow-sky-500/25 relative overflow-hidden ring-1 ring-sky-950/18 dark:ring-white/15',
    'label' => 'New this month',
    'value' => number_format($kNewMo),
    'footnote' => $kNew7 > 0 ? (number_format($kNew7) . ' in the last 7 days') : 'Acquisition cadence',
    'icon' => 'sparkles',
    'trendChip' => $kNewMo > 0 ? 'MTD' : null,
    'trendChipClass' => 'bg-white/14 text-white/95 ring-1 ring-white/25',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-rose-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(194,65,12,0.38)] dark:shadow-xl dark:shadow-orange-500/22 relative overflow-hidden ring-1 ring-orange-950/20 dark:ring-white/15',
    'label' => 'Low balance alerts',
    'value' => number_format($kLowBal),
    'footnote' => 'Users with a wallet under minimum (est. base)',
    'icon' => 'bell-ring',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-fuchsia-500 via-purple-600 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(147,51,234,0.38)] dark:shadow-xl dark:shadow-fuchsia-500/22 relative overflow-hidden ring-1 ring-purple-950/18 dark:ring-white/15',
    'label' => 'Recurring users',
    'value' => number_format($kRecurringUsers),
    'footnote' => 'Distinct members with an active schedule',
    'icon' => 'repeat',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(29,78,216,0.4)] dark:shadow-xl dark:shadow-blue-500/25 relative overflow-hidden ring-1 ring-blue-950/18 dark:ring-white/15',
    'label' => 'Wallets',
    'value' => number_format($kWallets),
    'footnote' => 'Linked accounts · custody surface',
    'icon' => 'layers',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-slate-600 via-slate-700 to-slate-950 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(15,23,42,0.45)] dark:shadow-xl relative overflow-hidden ring-1 ring-slate-950/25 dark:ring-white/12',
    'label' => 'Inactive',
    'value' => number_format($kInactive),
    'footnote' => 'Suspended or deactivated identities',
    'icon' => 'user-x',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-teal-500 via-teal-600 to-slate-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(13,148,136,0.42)] dark:shadow-xl dark:shadow-teal-500/25 relative overflow-hidden ring-1 ring-teal-950/20 dark:ring-white/15',
    'label' => 'Net cohort (7d)',
    'value' => number_format($kNew7),
    'footnote' => 'Fresh registrations · velocity',
    'icon' => 'trending-up',
]);
?>
</div>

<!-- Analytics row -->
<div class="grid lg:grid-cols-5 gap-5 mb-6 sm:mb-8">
    <div class="lg:col-span-3"><?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Registration pulse',
        'subtitle' => 'Trailing months · sign-ups · analytics-included members only',
        'chartId' => 'adminUserSignupChart',
        'badgeText' => 'Cohort',
        'badgeClass' => 'text-[10px] font-bold bg-violet-50 dark:bg-violet-950/60 text-violet-800 dark:text-violet-300 px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-violet-300/75 dark:ring-violet-800/50 shadow-sm',
        'cardClass' => $chartCardShell,
        'chartContainerClass' => 'mt-3 min-h-[200px]',
    ]); ?></div>
    <div class="lg:col-span-2 <?= Str::e($chartCardShell) ?>">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 tracking-tight">Recent registrations</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Latest identities entering the ledger</p>
            </div>
        </div>
        <?php if ($recentRegs === []): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 py-6">No users yet.</p>
        <?php else: ?>
        <ul class="space-y-2 max-h-[240px] overflow-y-auto scrollbar-none pr-1">
            <?php foreach ($recentRegs as $ru):
                $ini = strtoupper(substr((string) ($ru['username'] ?? '?'), 0, 2));
                $rid = (int) ($ru['id'] ?? 0);
                ?>
            <li>
                <a href="<?= Str::e(Url::to('/admin/users/' . $rid)) ?>" class="flex items-center gap-3 rounded-xl border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2.5 hover:border-teal-300/80 dark:hover:border-teal-700/50 hover:bg-white dark:hover:bg-slate-800/50 transition-colors group">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-teal-500 to-emerald-700 flex items-center justify-center text-white text-[11px] font-bold shrink-0 ring-2 ring-white/30 dark:ring-slate-900/80"><?= Str::e($ini) ?></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate group-hover:text-teal-700 dark:group-hover:text-teal-300"><?= Str::e((string) ($ru['username'] ?? '')) ?></p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= Str::e((string) ($ru['email'] ?? '')) ?></p>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-teal-600 shrink-0"></i>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Directory -->
<div class="<?= Str::e($tableShell) ?>">
    <div class="px-4 sm:px-6 py-4 border-b border-slate-200/90 dark:border-slate-800/80 bg-gradient-to-r from-slate-50/95 to-white dark:from-[#0f172a] dark:to-[#0d1424] flex flex-col lg:flex-row lg:items-end gap-4 justify-between">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">User directory</h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5"><?= number_format(count($rows)) ?> loaded · refine below ·
                <?php if ($analyticsFilter === 'included'): ?>showing analytics-included accounts only<?php elseif ($analyticsFilter === 'excluded'): ?>showing analytics-excluded accounts only<?php else: ?>full directory<?php endif; ?>
            </p>
        </div>
        <div class="flex flex-col sm:flex-row flex-wrap gap-2 sm:items-center w-full lg:w-auto">
            <form method="get" action="<?= Str::e(Url::to('/admin/users')) ?>" class="shrink-0">
                <label for="analyticsFilter" class="sr-only">Analytics inclusion</label>
                <select name="analytics" id="analyticsFilter" onchange="this.form.submit()" class="rounded-xl border border-teal-300/80 dark:border-teal-800/55 bg-teal-50/90 dark:bg-teal-950/35 text-xs font-bold px-3 py-2.5 text-teal-900 dark:text-teal-100 outline-none focus:ring-2 focus:ring-teal-500/40 min-w-[12.5rem]">
                    <option value="all" <?= $analyticsFilter === 'all' ? 'selected' : '' ?>>Directory: All users</option>
                    <option value="included" <?= $analyticsFilter === 'included' ? 'selected' : '' ?>>Included in analytics</option>
                    <option value="excluded" <?= $analyticsFilter === 'excluded' ? 'selected' : '' ?>>Excluded from analytics</option>
                </select>
                <noscript><button type="submit" class="ml-2 text-xs underline">Apply</button></noscript>
            </form>
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="userDirSearch" placeholder="Search name, email, username…" autocomplete="off" class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500/60 outline-none transition-shadow" />
            </div>
            <select id="userDirRole" class="rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-xs font-bold px-3 py-2.5 text-slate-700 dark:text-slate-200">
                <option value="">All roles</option>
                <option value="user">User</option>
                <option value="super_admin">Super admin</option>
            </select>
            <select id="userDirStatus" class="rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-xs font-bold px-3 py-2.5 text-slate-700 dark:text-slate-200">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select id="userDirSort" class="rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-xs font-bold px-3 py-2.5 text-slate-700 dark:text-slate-200">
                <option value="id_desc">Newest join</option>
                <option value="id_asc">Oldest join</option>
                <option value="activity_desc">Last activity</option>
                <option value="balance_desc">Balance (high)</option>
                <option value="balance_asc">Balance (low)</option>
            </select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50/95 dark:bg-slate-900/60 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <th class="px-4 py-3 whitespace-nowrap">Member</th>
                    <th class="px-4 py-3 whitespace-nowrap">Role</th>
                    <th class="px-4 py-3 whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 whitespace-nowrap">Analytics</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">Wallets</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">Balance (<?= Str::e($baseCurrency) ?>)</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">Recurring</th>
                    <th class="px-4 py-3 whitespace-nowrap">Last activity</th>
                    <th class="px-4 py-3 whitespace-nowrap">Joined</th>
                    <th class="px-4 py-3 whitespace-nowrap text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="userDirTbody" class="divide-y divide-slate-100 dark:divide-slate-800/90">
            <?php foreach ($rows as $r):
                $uid = (int) $r['id'];
                $uname = (string) ($r['username'] ?? '');
                $email = (string) ($r['email'] ?? '');
                $full = trim((string) ($r['full_name'] ?? ''));
                $role = (string) ($r['role'] ?? 'user');
                $active = ! empty($r['is_active']);
                $inAnalytics = filter_var($r['include_in_analytics'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $ini = strtoupper(substr($uname, 0, 2));
                $wc = (int) ($r['wallet_count'] ?? 0);
                $rc = (int) ($r['recurring_active_count'] ?? 0);
                $bal = (float) ($r['total_balance_base'] ?? 0);
                $low = ! empty($r['has_low_balance']);
                $lastLogin = (string) ($r['last_login_at'] ?? '');
                $lastTxn = (string) ($r['last_transaction_date'] ?? '');
                $joined = (string) ($r['created_at'] ?? '');
                $joinedTs = strtotime($joined) ?: 0;
                $actStr = $lastLogin !== '' && $lastLogin !== '0000-00-00 00:00:00' ? $lastLogin : ($lastTxn !== '' ? $lastTxn : '');
                $actTs = ($actStr !== '' && $actStr !== '0000-00-00 00:00:00') ? (strtotime($actStr) ?: 0) : 0;
                $searchBlob = strtolower($uname . ' ' . $email . ' ' . $full);
                $isSelf = $adminSelfId !== null && $uid === $adminSelfId;
                ?>
                <tr class="user-dir-row hover:bg-slate-50/90 dark:hover:bg-slate-800/35 transition-colors group"
                    data-user-row
                    data-search="<?= Str::e($searchBlob) ?>"
                    data-role="<?= Str::e($role) ?>"
                    data-status="<?= $active ? 'active' : 'inactive' ?>"
                    data-joined-ts="<?= $joinedTs ?>"
                    data-activity-ts="<?= $actTs ?>"
                    data-balance="<?= Str::e((string) $bal) ?>"
                    data-user-id="<?= $uid ?>">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-500 to-indigo-700 flex items-center justify-center text-white text-xs font-bold shrink-0 ring-2 ring-white/25 dark:ring-slate-900/80 shadow-md"><?= Str::e($ini) ?></div>
                            <div class="min-w-0">
                                <a href="<?= Str::e(Url::to('/admin/users/' . $uid)) ?>" class="font-bold text-slate-900 dark:text-slate-100 hover:text-teal-700 dark:hover:text-teal-300 truncate block max-w-[200px] sm:max-w-[240px]"><?= Str::e($uname) ?></a>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[200px] sm:max-w-[260px]"><?= Str::e($email) ?></p>
                                <?php if ($full !== ''): ?><p class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[200px]"><?= Str::e($full) ?></p><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($role === 'super_admin'): ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-violet-100 dark:bg-violet-950/70 text-violet-800 dark:text-violet-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-violet-300/60 dark:ring-violet-800/50">Super admin</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-slate-300/50 dark:ring-slate-600/50">User</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($active): ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-emerald-300/60 dark:ring-emerald-800/50">Active</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-slate-300/70 dark:ring-slate-600/50">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($inAnalytics): ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-900 dark:text-teal-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-teal-300/70 dark:ring-teal-800/50">Included</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-950 dark:text-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-amber-300/70 dark:ring-amber-900/45" title="Not counted in admin dashboards or exports">Excluded from analytics</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-800 dark:text-slate-200"><?= number_format($wc) ?></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <span class="tabular-nums font-bold text-slate-900 dark:text-slate-100"><?= number_format($bal, 2) ?></span>
                        <?php if ($low): ?><span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500 ring-2 ring-amber-200/80 dark:ring-amber-900/50 align-middle" title="Below wallet minimum"></span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300"><?= number_format($rc) ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 tabular-nums"><?= $actStr !== '' ? Str::e(substr($actStr, 0, 16)) : '—' ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 tabular-nums"><?= Str::e(substr($joined, 0, 10)) ?></td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1 justify-end opacity-90 group-hover:opacity-100">
                            <a href="<?= Str::e(Url::to('/admin/users/' . $uid)) ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-teal-400/70 hover:text-teal-700 dark:hover:text-teal-300 transition-colors" title="View"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            <a href="<?= Str::e(Url::to('/admin/users/' . $uid)) ?>#manage-profile" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-teal-400/70 hover:text-teal-700 dark:hover:text-teal-300 transition-colors" title="Edit"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                            <?php if (! $isSelf): ?>
                                <?php if ($active): ?>
                                <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>" class="inline" onsubmit="return confirm('Suspend this user? They will not be able to sign in.');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="user_id" value="<?= $uid ?>" />
                                    <input type="hidden" name="is_active" value="0" />
                                    <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-amber-200 dark:border-amber-900/60 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-200 hover:bg-amber-100 dark:hover:bg-amber-950/70 transition-colors" title="Suspend"><i data-lucide="pause-circle" class="w-4 h-4"></i></button>
                                </form>
                                <?php else: ?>
                                <form method="post" action="<?= Str::e(Url::to('/admin/users/status')) ?>" class="inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="user_id" value="<?= $uid ?>" />
                                    <input type="hidden" name="is_active" value="1" />
                                    <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-200 hover:bg-emerald-100 dark:hover:bg-emerald-950/70 transition-colors" title="Activate"><i data-lucide="play-circle" class="w-4 h-4"></i></button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p id="userDirEmpty" class="hidden px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No users match your filters.</p>
</div>

<!-- Create user modal -->
<div id="createUserModal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/55 dark:bg-black/70 backdrop-blur-[2px]" data-close-modal></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-md rounded-2xl border border-slate-200/95 dark:border-slate-700 bg-white dark:bg-[#0f172a] shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 overflow-hidden transform transition-all">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-teal-50/90 to-white dark:from-teal-950/30 dark:to-[#0f172a]">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-teal-600 dark:text-teal-400">Provision</p>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Create user</h3>
                </div>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" data-close-modal aria-label="Close">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="post" action="<?= Str::e(Url::to('/admin/users')) ?>" class="p-5 space-y-3">
                <?= Csrf::field() ?>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Username</label>
                    <input name="username" required class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm" autocomplete="off" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" name="email" required class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Full name</label>
                    <input name="full_name" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm" placeholder="8+ characters" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Role</label>
                    <select name="role" class="w-full rounded-xl border border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm font-semibold">
                        <option value="user">User</option>
                        <option value="super_admin">Super admin</option>
                    </select>
                </div>
                <input type="hidden" name="include_in_analytics" value="0" />
                <label class="flex items-start gap-2.5 rounded-xl border border-slate-200/95 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-3">
                    <input type="checkbox" name="include_in_analytics" value="1" checked class="mt-0.5 rounded border-slate-300 shrink-0" />
                    <span class="text-sm text-slate-700 dark:text-slate-200">
                        <span class="font-semibold block">Include in analytics &amp; reports</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal leading-relaxed mt-0.5 block">Excluded users remain active, but their financial records will not affect global analytics, reports, or platform dashboards.</span>
                    </span>
                </label>
                <div class="flex gap-2 pt-2">
                    <button type="button" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-600 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800" data-close-modal>Cancel</button>
                    <button type="submit" class="flex-1 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white py-2.5 text-sm font-bold shadow-md shadow-teal-900/20 hover:opacity-95">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var labels = <?= json_encode($signupLabels, JSON_THROW_ON_ERROR) ?>;
    var counts = <?= json_encode($signupCounts, JSON_THROW_ON_ERROR) ?>;
    var signupChart = null;
    function mountSignupChart() {
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') return;
        var el = document.querySelector('#adminUserSignupChart');
        if (!el || !labels.length) return;
        try {
            if (signupChart && typeof signupChart.destroy === 'function') signupChart.destroy();
        } catch (e) {}
        signupChart = null;
        var tt = KhfApexTheme.tokens();
        signupChart = new ApexCharts(el, Object.assign({}, KhfApexTheme.chart({ type: 'area', height: 200 }), {
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: tt.incomeExpenseFillShade,
                    type: 'vertical',
                    shadeIntensity: KhfApexTheme.isDark() ? 0.35 : 0.28,
                    opacityFrom: KhfApexTheme.isDark() ? 0.35 : 0.32,
                    opacityTo: KhfApexTheme.isDark() ? 0.03 : 0.06,
                    stops: [0, 92, 100]
                }
            },
            dataLabels: { enabled: false },
            colors: ['#8b5cf6'],
            series: [{ name: 'Sign-ups', data: counts }],
            xaxis: { categories: labels, labels: { style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: 600 } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: 600 } } },
            grid: KhfApexTheme.grid({ padding: { top: 4, left: 0, right: 4 } }),
            tooltip: KhfApexTheme.tooltip({ shared: true })
        }));
        signupChart.render();
    }
    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(mountSignupChart);
    } else if (window.ApexCharts) {
        mountSignupChart();
    }

    var tbody = document.getElementById('userDirTbody');
    var search = document.getElementById('userDirSearch');
    var roleF = document.getElementById('userDirRole');
    var statusF = document.getElementById('userDirStatus');
    var sortF = document.getElementById('userDirSort');
    var empty = document.getElementById('userDirEmpty');
    var modal = document.getElementById('createUserModal');

    function applyFilters() {
        var q = (search && search.value || '').trim().toLowerCase();
        var rf = roleF && roleF.value || '';
        var sf = statusF && statusF.value || '';
        var rows = tbody ? Array.prototype.slice.call(tbody.querySelectorAll('tr[data-user-row]')) : [];
        var visible = 0;
        rows.forEach(function (tr) {
            var ok = true;
            if (q && (tr.getAttribute('data-search') || '').indexOf(q) === -1) ok = false;
            if (rf && tr.getAttribute('data-role') !== rf) ok = false;
            if (sf && tr.getAttribute('data-status') !== sf) ok = false;
            tr.style.display = ok ? '' : 'none';
            if (ok) visible++;
        });
        if (empty) empty.classList.toggle('hidden', visible !== 0);
    }

    function applySort() {
        if (!tbody || !sortF) return;
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-user-row]'));
        var mode = sortF.value || 'id_desc';
        rows.sort(function (a, b) {
            if (mode === 'balance_desc' || mode === 'balance_asc') {
                var ba = parseFloat(a.getAttribute('data-balance') || '0');
                var bb = parseFloat(b.getAttribute('data-balance') || '0');
                return mode === 'balance_desc' ? bb - ba : ba - bb;
            }
            if (mode === 'activity_desc') {
                return (parseInt(b.getAttribute('data-activity-ts') || '0', 10)) - (parseInt(a.getAttribute('data-activity-ts') || '0', 10));
            }
            var ja = parseInt(a.getAttribute('data-joined-ts') || '0', 10);
            var jb = parseInt(b.getAttribute('data-joined-ts') || '0', 10);
            return mode === 'id_asc' ? ja - jb : jb - ja;
        });
        rows.forEach(function (tr) { tbody.appendChild(tr); });
    }

    function wire() {
        if (search) search.addEventListener('input', applyFilters);
        if (roleF) roleF.addEventListener('change', applyFilters);
        if (statusF) statusF.addEventListener('change', applyFilters);
        if (sortF) sortF.addEventListener('change', function () { applySort(); applyFilters(); });
    }
    wire();

    function openM() { if (modal) { modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('overflow-hidden'); if (window.lucide) lucide.createIcons(); } }
    function closeM() { if (modal) { modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('overflow-hidden'); } }
    var ob = document.getElementById('openCreateUserModal');
    if (ob) ob.addEventListener('click', openM);
    if (modal) modal.querySelectorAll('[data-close-modal]').forEach(function (n) { n.addEventListener('click', closeM); });

    applySort();
    applyFilters();
})();
</script>
