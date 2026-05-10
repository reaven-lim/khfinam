<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$rows = $rows ?? [];
$typeKpis = $typeKpis ?? [
    'total' => 0,
    'active' => 0,
    'custom' => 0,
    'core' => 0,
    'total_wallets' => 0,
    'unused_types' => 0,
    'disabled' => 0,
];
$mostUsedRow = $mostUsedRow ?? null;
$recentTypeRow = $recentTypeRow ?? null;
$taxonomyWalletLabels = $taxonomyWalletLabels ?? ['Core taxonomy types', 'Custom taxonomy types'];
$taxonomyWalletSeries = $taxonomyWalletSeries ?? [0, 0];
$baseCurrency = $baseCurrency ?? 'MYR';
$message = $message ?? null;
$error = $error ?? null;

$rowsBuiltIn = [];
$rowsCustom = [];
foreach ($rows as $_wtRow) {
    if (! empty($_wtRow['is_system'])) {
        $rowsBuiltIn[] = $_wtRow;
    } else {
        $rowsCustom[] = $_wtRow;
    }
}

$kTotal = (int) ($typeKpis['total'] ?? 0);
$kActive = (int) ($typeKpis['active'] ?? 0);
$kCustom = (int) ($typeKpis['custom'] ?? 0);
$kCore = (int) ($typeKpis['core'] ?? 0);
$kWallets = (int) ($typeKpis['total_wallets'] ?? 0);
$kUnused = (int) ($typeKpis['unused_types'] ?? 0);
$kDisabled = (int) ($typeKpis['disabled'] ?? 0);

$heroShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50 to-indigo-50/50 dark:from-[#0c1426] dark:via-[#0d1629] dark:to-indigo-950/30 px-5 py-4 sm:px-6 sm:py-5 shadow-[0_20px_54px_-28px_rgba(15,23,42,0.16)] ring-1 ring-slate-900/[0.07] dark:ring-white/[0.05]';
$govCard = 'rounded-2xl border border-slate-200/95 dark:border-slate-700/65 bg-white/90 dark:bg-[#0d1424]/90 p-4 sm:p-5 shadow-sm ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05]';
$chartCard = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-lg ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06]';
$tableShell = 'rounded-2xl border border-slate-300/88 dark:border-slate-700/55 bg-white/95 dark:bg-[#0d1424] shadow-xl ring-1 ring-slate-900/[0.06] dark:ring-white/[0.05] overflow-hidden';
?>

<?php if ($message): ?>
<div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<div class="mb-6 <?= Str::e($heroShell) ?>">
    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-indigo-600 dark:text-indigo-400">Wallet setup</p>
            <h1 class="mt-1 text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">Wallet types</h1>
            <p class="mt-1 text-xs sm:text-[13px] text-slate-600 dark:text-slate-400 max-w-3xl leading-relaxed">
                Wallet types group accounts for reporting, filtering, and dashboards.
                <strong class="text-slate-700 dark:text-slate-200">Built-in types</strong> are provided by KHFinaM;
                <strong class="text-slate-700 dark:text-slate-200">custom types</strong> are ones you create when the defaults are not enough.
                Prefer turning a type off instead of deleting while wallets still use it.
                Totals in <?= Str::e($baseCurrency) ?> match the admin wallets overview.
            </p>
        </div>
        <button type="button" id="openCreateWalletTypeModal" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white px-5 py-3 text-sm font-bold shadow-lg shadow-indigo-900/25 hover:opacity-95 ring-1 ring-indigo-950/20 shrink-0">
            <i data-lucide="plus-circle" class="w-5 h-5"></i> New wallet type
        </button>
    </div>
</div>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
<?php
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(79,70,229,0.42)] relative overflow-hidden ring-1 ring-indigo-950/18 dark:ring-white/15',
    'label' => 'Wallet types',
    'value' => number_format($kTotal),
    'footnote' => number_format($kActive) . ' active · ' . number_format($kDisabled) . ' disabled',
    'icon' => 'layers',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-slate-600 via-slate-700 to-slate-950 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(15,23,42,0.45)] relative overflow-hidden ring-1 ring-slate-950/20 dark:ring-white/12',
    'label' => 'Built-in · Custom',
    'value' => number_format($kCore) . ' / ' . number_format($kCustom),
    'footnote' => 'Provided by the app vs created by you',
    'icon' => 'shield',
    'valueClass' => 'text-2xl sm:text-3xl font-extrabold mt-1 tabular-nums tracking-tight',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-teal-500 via-emerald-600 to-emerald-950 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(5,122,85,0.4)] relative overflow-hidden ring-1 ring-emerald-950/20 dark:ring-white/15',
    'label' => 'Wallets attached',
    'value' => number_format($kWallets),
    'footnote' => 'All custody rows · every type',
    'icon' => 'wallet',
]);
View::partial('components/admin/hero-kpi-gradient-card', [
    'gradientShell' => 'rounded-2xl bg-gradient-to-br from-amber-500 via-orange-600 to-rose-900 p-5 sm:p-6 text-white shadow-[0_24px_48px_-12px_rgba(194,65,12,0.38)] relative overflow-hidden ring-1 ring-orange-950/18 dark:ring-white/15',
    'label' => 'Unused types',
    'value' => number_format($kUnused),
    'footnote' => 'Zero wallets · candidates to retire',
    'icon' => 'package-open',
]);
?>
</div>

<?php
$mostLabel = $mostUsedRow ? (string) ($mostUsedRow['label'] ?? '') . ' · ' . number_format((int) ($mostUsedRow['wallet_count'] ?? 0)) . ' wallets' : '—';
$recentLabel = $recentTypeRow ? (string) ($recentTypeRow['label'] ?? '') . ' · ' . Str::e(substr((string) ($recentTypeRow['created_at'] ?? ''), 0, 10)) : '—';
?>
<div class="grid sm:grid-cols-2 gap-4 mb-7">
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-gradient-to-br from-violet-50/90 to-white dark:from-violet-950/25 dark:to-[#0d1424] p-4 sm:p-5 ring-1 ring-violet-200/60 dark:ring-violet-900/40">
        <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300">Most used wallet type</p>
        <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white"><?= Str::e($mostLabel) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-gradient-to-br from-sky-50/90 to-white dark:from-sky-950/25 dark:to-[#0d1424] p-4 sm:p-5 ring-1 ring-sky-200/60 dark:ring-sky-900/40">
        <p class="text-[10px] font-bold uppercase tracking-wider text-sky-700 dark:text-sky-300">Latest custom or built-in addition</p>
        <p class="mt-1 text-sm font-bold text-slate-900 dark:text-white"><?= Str::e($recentLabel) ?></p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-8">
    <div class="lg:col-span-2 grid sm:grid-cols-2 gap-4">
        <div class="<?= Str::e($govCard) ?> border-indigo-200/80 dark:border-indigo-900/50 bg-indigo-50/40 dark:bg-indigo-950/20">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shrink-0"><i data-lucide="shield-check" class="w-5 h-5"></i></span>
                <div>
                    <p class="text-sm font-bold text-indigo-950 dark:text-indigo-100">Built-in types stay protected</p>
                    <p class="text-xs text-indigo-900/85 dark:text-indigo-200/80 mt-1 leading-relaxed">
                        These are default system types used by KHFinaM — they cannot be removed. You can turn them off for <em>new</em> wallets when that fits your process.
                    </p>
                </div>
            </div>
        </div>
        <div class="<?= Str::e($govCard) ?>">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white shrink-0"><i data-lucide="link" class="w-5 h-5"></i></span>
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Referenced by wallets</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        Columns show live wallet counts per type. A custom wallet type can only be removed when no wallet uses it anymore.
                    </p>
                </div>
            </div>
        </div>
        <div class="<?= Str::e($govCard) ?>">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500 text-white shrink-0"><i data-lucide="toggle-left" class="w-5 h-5"></i></span>
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Prefer deactivating</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        Deactivated types stay on old records but cannot be selected for new wallets.
                    </p>
                </div>
            </div>
        </div>
        <div class="<?= Str::e($govCard) ?>">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 text-white shrink-0"><i data-lucide="pie-chart" class="w-5 h-5"></i></span>
                <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Reporting &amp; analytics</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
                        Reports and dashboards use wallet type to group balances and activity. The analytics column counts wallets for users who opted into analytics.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="min-w-0"><?php View::partial('components/analytics/chart-shell-card', [
        'title' => 'Wallets by type',
        'subtitle' => 'Built-in vs custom assignments',
        'chartId' => 'walletTypeTaxDonut',
        'badgeText' => 'Mix',
        'badgeClass' => 'text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 px-2.5 py-1 rounded-full uppercase tracking-wider ring-1 ring-indigo-300/75 dark:ring-indigo-800/50',
        'cardClass' => $chartCard,
        'headerSimple' => true,
        'chartContainerClass' => 'mt-2 min-h-[200px]',
    ]); ?></div>
</div>

<?php
$tableThead = <<<'HTML'
<thead>
    <tr class="bg-slate-50/95 dark:bg-slate-900/60 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
        <th class="px-4 py-3">Wallet type</th>
        <th class="px-4 py-3">Kind</th>
        <th class="px-4 py-3 text-right">Wallets</th>
        <th class="px-4 py-3 text-right">Users</th>
        <th class="px-4 py-3 text-right">Balance (BASE)</th>
        <th class="px-4 py-3 text-right">Analytics cohort</th>
        <th class="px-4 py-3 text-right">Sort</th>
        <th class="px-4 py-3">Status</th>
        <th class="px-4 py-3">Updated</th>
        <th class="px-4 py-3 text-right">Actions</th>
    </tr>
</thead>
HTML;
$tableThead = str_replace('BASE', Str::e((string) $baseCurrency), $tableThead);
?>

<div class="<?= Str::e($tableShell) ?> mb-10">
    <div class="px-4 sm:px-6 py-4 border-b border-slate-200/90 dark:border-slate-800 bg-gradient-to-r from-indigo-50/90 via-white to-slate-50 dark:from-indigo-950/25 dark:via-[#0d1424] dark:to-[#0f172a]">
        <h2 class="text-sm font-black text-slate-900 dark:text-white">All wallet types</h2>
        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed"><?= number_format(count($rows)) ?> total · built-in types listed first, then your custom types.</p>
    </div>

    <?php if ($rows === []): ?>
        <p class="px-6 py-12 text-center text-sm text-slate-500 dark:text-slate-400">No wallet types configured.</p>
    <?php else: ?>
        <div class="border-b border-slate-200/90 dark:border-slate-800 bg-indigo-50/50 dark:bg-indigo-950/20 px-4 sm:px-6 py-3">
            <h3 class="text-xs font-black uppercase tracking-wide text-indigo-800 dark:text-indigo-200">Built-in wallet types</h3>
            <p class="text-[11px] text-indigo-900/80 dark:text-indigo-300/85 mt-1">Examples: Cash, Bank, E-wallet, Credit card — shipped with the app and protected from deletion.</p>
        </div>
        <?php if ($rowsBuiltIn !== []): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <?= $tableThead ?>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/90">
                    <?php View::partial('admin/partials/wallet_type_table_rows', ['rowsFrag' => $rowsBuiltIn]); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="px-6 py-6 text-sm text-slate-500 dark:text-slate-400">No built-in types loaded (unexpected).</p>
        <?php endif; ?>

        <div class="border-b border-t border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 px-4 sm:px-6 py-3">
            <h3 class="text-xs font-black uppercase tracking-wide text-slate-800 dark:text-slate-100">Custom wallet types</h3>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">Create custom types when an account does not fit the default choices (for example brokerage or a business account).</p>
        </div>
        <?php if ($rowsCustom !== []): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <?= $tableThead ?>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/90">
                    <?php View::partial('admin/partials/wallet_type_table_rows', ['rowsFrag' => $rowsCustom]); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="px-6 py-10 text-center rounded-b-2xl bg-slate-50/40 dark:bg-slate-900/25">
            <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">No custom wallet types yet</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">Create one when the built-in types are not enough. Custom types work the same way in reports — they are simply labels you define.</p>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div id="createWalletTypeModal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/55 dark:bg-black/70 backdrop-blur-[2px]" data-close-wt-modal></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-4 sm:p-6 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-lg rounded-2xl border border-slate-200/95 dark:border-slate-700 bg-white dark:bg-[#0f172a] shadow-2xl ring-1 ring-slate-900/10 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between gap-3 bg-gradient-to-r from-indigo-50/90 to-white dark:from-indigo-950/30 dark:to-[#0f172a]">
                <div class="min-w-0 pt-0.5">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Create</p>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">New wallet type</h3>
                </div>
                <button type="button" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0 ring-1 ring-transparent hover:ring-slate-200 dark:hover:ring-slate-700/80" data-close-wt-modal aria-label="Close"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/store')) ?>" id="createWalletTypeForm" class="px-6 py-6 sm:py-7">
                <?= Csrf::field() ?>
                <div class="space-y-8">
                    <p class="text-[13px] text-slate-600 dark:text-slate-400 leading-relaxed border-l-4 border-indigo-300/70 dark:border-indigo-700/70 pl-3.5 -ml-0.5">
                        Add a name people will recognize. Icons and ordering are optional polish — the app handles technical IDs unless you expand advanced settings.
                    </p>

                    <div class="space-y-2">
                        <label for="wt_create_label" class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Wallet type name</label>
                        <input id="wt_create_label" name="label" required maxlength="128" placeholder="e.g. Brokerage account" autocomplete="off" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm placeholder:text-slate-400 shadow-sm ring-1 ring-transparent focus:border-indigo-400 focus:outline-none focus:ring-indigo-500/25 dark:focus:border-indigo-500/65" />
                    </div>

                    <hr class="border-0 border-t border-slate-200/95 dark:border-slate-700/85" />

                    <div class="space-y-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Icon</span>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Shown in menus and lists</span>
                        </div>
                        <div class="rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-gradient-to-br from-slate-50/90 to-transparent dark:from-slate-900/55 dark:to-transparent p-4 sm:p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-stretch">
                                <div id="wt_modal_icon_shell" class="mx-auto flex h-[3rem] w-[3rem] shrink-0 items-center justify-center rounded-xl bg-white dark:bg-[#162032] shadow-sm ring-1 ring-slate-200/95 dark:ring-slate-600/60 opacity-95 sm:mx-0 dark:opacity-[0.98]" aria-hidden="true">
                                    <i data-lucide="wallet" class="w-6 h-6 text-indigo-500/90 dark:text-indigo-300/90" id="wt_modal_icon_i"></i>
                                </div>
                                <div class="flex min-h-[3rem] flex-1 min-w-0 flex-col justify-center">
                                    <label for="modal_wt_icon" class="sr-only">Choose icon style</label>
                                    <?php View::partial('components/admin/wallet-type-icon-select', [
                                        'selectId' => 'modal_wt_icon',
                                        'selected' => 'wallet',
                                        'required' => true,
                                        'selectClass' => 'block w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-3 text-sm shadow-sm hover:border-slate-400/90 dark:hover:border-slate-500 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:bg-slate-900 dark:focus:border-indigo-500 min-h-[3rem]',
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-0 border-t border-slate-200/95 dark:border-slate-700/85" />

                    <div class="space-y-2">
                        <label for="wt_create_sort_order" class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Sort order</label>
                        <input id="wt_create_sort_order" name="sort_order" type="number" value="50" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-3 text-sm tabular-nums shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-indigo-500/20 dark:focus:border-indigo-500 max-w-[10rem]" />
                        <p class="text-[11px] text-slate-500 dark:text-slate-500 pt-1">Lower numbers surface earlier in picker lists alongside built-in types.</p>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Status</label>
                        <label for="wt_create_active_switch" class="flex cursor-pointer flex-col gap-4 rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-white/85 dark:bg-slate-900/35 px-4 py-4 shadow-sm transition-colors hover:border-indigo-200/80 hover:bg-indigo-50/35 dark:hover:border-indigo-900/55 dark:hover:bg-indigo-950/25 focus-within:ring-2 focus-within:ring-indigo-500/30 focus-within:ring-offset-2 focus-within:ring-offset-white dark:focus-within:ring-offset-[#0f172a] sm:flex-row sm:items-center sm:justify-between">
                            <span class="min-w-0 flex-1 pr-4">
                                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">Active for new wallets</span>
                                <span class="mt-2 block text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                    Members can choose this category when adding a wallet. Turn off to pause new use while keeping historical data untouched.
                                </span>
                            </span>
                            <input type="checkbox" name="is_active" value="1" checked class="peer sr-only" aria-label="Active for new wallets" id="wt_create_active_switch" />
                            <span class="relative mx-auto h-10 w-[3.5rem] shrink-0 rounded-full bg-slate-300/95 shadow-inner ring-1 ring-slate-400/30 transition-colors after:pointer-events-none after:absolute after:top-1 after:left-1 after:block after:h-8 after:w-8 after:rounded-full after:bg-white after:shadow-md after:ring-1 after:ring-black/10 after:transition-transform after:duration-200 after:content-[''] dark:bg-slate-600 dark:ring-white/10 dark:after:ring-white/10 dark:peer-checked:bg-indigo-500 peer-checked:bg-indigo-600 peer-checked:after:translate-x-4 sm:mx-0" aria-hidden="true"></span>
                        </label>
                    </div>

                    <details class="rounded-xl border border-dashed border-slate-300/90 dark:border-slate-600/70 bg-slate-50/50 dark:bg-slate-900/25 px-4 py-3.5 text-slate-600 dark:text-slate-400 open:[&_summary_.wt-modal-adv-chevron]:rotate-180">
                        <summary class="cursor-pointer list-none select-none text-sm font-semibold text-slate-600 dark:text-slate-300 outline-none marker:content-none [&::-webkit-details-marker]:hidden flex items-center justify-between gap-2">
                            <span>Advanced settings</span>
                            <span class="wt-modal-adv-chevron inline-flex shrink-0 text-slate-400 transition-transform duration-200" aria-hidden="true"><i data-lucide="chevron-down" class="w-4 h-4"></i></span>
                        </summary>
                        <p class="text-[11px] text-slate-500 dark:text-slate-500 mt-3 leading-relaxed">Internal technical identifiers used by the app. Most teams never need to change these.</p>
                        <p class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 mt-4">Suggested from name (preview only until you create):</p>
                        <input id="wt_internal_id_preview" type="text" readonly class="mt-2 w-full rounded-lg border border-dashed border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-mono text-slate-700 dark:text-slate-300" value="" />
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-4 mb-1.5">Override internal ID <span class="font-normal normal-case text-slate-400">(optional)</span></label>
                        <input name="internal_id_manual" maxlength="64" pattern="[a-z0-9_]*" placeholder="only_if_you_need_a_specific_id" autocomplete="off" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-xs font-mono" />
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 pb-1">Lowercase letters, numbers, underscores. Leave empty to auto-generate from the name.</p>
                    </details>
                </div>

                <div class="mt-8 flex flex-col gap-3 border-t border-slate-200/90 dark:border-slate-700/85 pt-6 sm:flex-row sm:justify-end">
                    <button type="button" class="w-full shrink-0 rounded-xl border border-slate-300/95 bg-white px-5 py-3.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:bg-slate-800 sm:w-auto sm:min-w-[7.25rem]" data-close-wt-modal>Cancel</button>
                    <button type="submit" class="w-full shrink-0 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 ring-1 ring-indigo-950/20 hover:opacity-[0.97] dark:shadow-indigo-950/40 sm:min-w-[11rem]">Create type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var lbl = <?= json_encode($taxonomyWalletLabels, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    var ser = <?= json_encode(array_map(static fn ($n): float => round((float) $n, 2), $taxonomyWalletSeries), JSON_THROW_ON_ERROR) ?>;
    var chart = null;
    function mountTax() {
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') return;
        var el = document.querySelector('#walletTypeTaxDonut');
        if (!el) return;
        try { if (chart) chart.destroy(); } catch (e) {}
        chart = null;
        var tt = KhfApexTheme.tokens();
        var sum = ser.reduce(function (a,b) { return a+b; }, 0);
        if (!lbl.length || sum <= 0) {
            el.innerHTML = KhfApexTheme.emptyStateHtml('No wallet assignments yet.');
            return;
        }
        chart = new ApexCharts(el, Object.assign({}, KhfApexTheme.chart({ type: 'donut', height: 200 }), {
            labels: lbl,
            series: ser,
            colors: ['#6366f1', '#94a3b8'],
            stroke: { show: true, width: KhfApexTheme.isDark() ? 2 : 1.25, colors: [tt.donutRingStroke] },
            legend: KhfApexTheme.legendBottom({ fontSize: '11px' }),
            plotOptions: { pie: { donut: { size: '72%' } } },
            dataLabels: { enabled: false },
            tooltip: KhfApexTheme.tooltip()
        }));
        chart.render();
    }
    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(mountTax);
    } else if (window.ApexCharts) {
        mountTax();
    }

    function slugifyLabel(s) {
        s = String(s || '').toLowerCase().trim();
        s = s.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').replace(/_+/g, '_');
        if (s.length > 64) s = s.slice(0, 64).replace(/_+$/g, '');
        return s || 'wallet_type';
    }
    function refreshInternalIdPreview() {
        var inp = document.getElementById('wt_create_label');
        var prev = document.getElementById('wt_internal_id_preview');
        if (inp && prev) prev.value = slugifyLabel(inp.value);
    }
    function refreshModalIconPreview() {
        var sel = document.getElementById('modal_wt_icon');
        var ic = document.getElementById('wt_modal_icon_i');
        if (!sel || !ic) return;
        ic.setAttribute('data-lucide', sel.value || 'wallet');
        if (window.lucide) lucide.createIcons();
    }
    var modal = document.getElementById('createWalletTypeModal');
    var lblEl = document.getElementById('wt_create_label');
    var iconSel = document.getElementById('modal_wt_icon');
    if (lblEl) lblEl.addEventListener('input', refreshInternalIdPreview);
    if (iconSel) iconSel.addEventListener('change', refreshModalIconPreview);
    function openM() {
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            refreshInternalIdPreview();
            refreshModalIconPreview();
            if (window.lucide) lucide.createIcons();
        }
    }
    function closeM() {
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }
    }
    var ob = document.getElementById('openCreateWalletTypeModal');
    if (ob) ob.addEventListener('click', openM);
    if (modal) modal.querySelectorAll('[data-close-wt-modal]').forEach(function (n) { n.addEventListener('click', closeM); });
})();
</script>
