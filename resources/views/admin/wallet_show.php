<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$wallet = $wallet ?? [];
$balanceBase = (float) ($balanceBase ?? 0);
$belowMin = ! empty($belowMin);
$monthlySeries = $monthlySeries ?? [];
$transferStats = $transferStats ?? ['count' => 0, 'volume_base' => 0.0];
$recentTx = $recentTx ?? [];
$ledgerCount = (int) ($ledgerCount ?? 0);
$recurringCount = (int) ($recurringCount ?? 0);
$walletTypes = $walletTypes ?? [];
$currencies = $currencies ?? [];
$baseCurrency = $baseCurrency ?? 'MYR';
$message = $message ?? null;
$error = $error ?? null;

$wid = (int) ($wallet['id'] ?? 0);
$ownerId = (int) ($wallet['user_id'] ?? 0);
$wname = (string) ($wallet['name'] ?? '');
$uname = (string) ($wallet['username'] ?? '');
$email = (string) ($wallet['email'] ?? '');
$active = ! empty($wallet['is_active']);
$isDefault = ! empty($wallet['is_default']);
$inAn = ! empty($wallet['owner_include_in_analytics']);
$ccy = (string) ($wallet['currency_code'] ?? '');
$open = (float) ($wallet['opening_balance'] ?? 0);
$minTh = $wallet['min_balance_threshold'];
$notes = (string) ($wallet['notes'] ?? '');
$sortOrder = (int) ($wallet['sort_order'] ?? 0);
$wtypeId = (int) ($wallet['wallet_type_id'] ?? 0);
$curId = (int) ($wallet['currency_id'] ?? 0);

$months = array_map(static fn (array $r): string => (string) ($r['ym'] ?? ''), $monthlySeries);
$incM = array_map(static fn (array $r): float => round((float) ($r['inc'] ?? 0), 2), $monthlySeries);
$expM = array_map(static fn (array $r): float => round((float) ($r['exp'] ?? 0), 2), $monthlySeries);
$xferNet = [];
foreach ($monthlySeries as $r) {
    $xferNet[] = round((float) ($r['xfer_in'] ?? 0) - (float) ($r['xfer_out'] ?? 0), 2);
}

$manageShell = 'rounded-2xl border-2 border-teal-500/35 dark:border-teal-500/25 bg-gradient-to-br from-white via-white to-teal-50/40 dark:from-[#0d1424] dark:via-[#0c1426] dark:to-teal-950/20 shadow-xl ring-1 ring-slate-900/[0.04] dark:ring-white/[0.06]';
$innerCard = 'rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-white/95 dark:bg-slate-900/50 p-4 sm:p-5';
$chartCard = 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-700/65 p-5 sm:p-6 shadow-lg ring-1 ring-slate-900/[0.07] dark:ring-white/[0.06]';
$secKicker = 'text-[10px] font-extrabold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400 mb-1';
?>

<?php if ($message): ?>
<div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<div class="mb-5 flex flex-wrap items-center gap-3 text-sm">
    <a href="<?= Str::e(Url::to('/admin/wallets')) ?>" class="inline-flex items-center gap-1.5 font-bold text-teal-700 dark:text-teal-300 hover:underline">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Wallet operations center
    </a>
    <span class="text-slate-300 dark:text-slate-600">/</span>
    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= Str::e($wname) ?></span>
</div>

<section class="mb-6 rounded-2xl border border-slate-200/95 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] px-5 py-5 sm:px-6 shadow-lg ring-1 ring-slate-900/[0.05] dark:ring-white/[0.05]">
    <div class="flex flex-col lg:flex-row lg:items-start gap-5">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 via-emerald-600 to-slate-900 flex items-center justify-center text-white shrink-0 shadow-md">
            <i data-lucide="<?= Str::e((string) ($wallet['type_icon'] ?? 'wallet')) ?>" class="w-8 h-8"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white"><?= Str::e($wname) ?></h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase"><?= Str::e($ccy) ?></span>
                <?php if ($active): ?>
                    <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Active</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-300/80 dark:bg-slate-700 text-slate-800 dark:text-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Inactive</span>
                <?php endif; ?>
                <?php if ($isDefault): ?>
                    <span class="inline-flex rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-800 dark:text-violet-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Default</span>
                <?php endif; ?>
                <?php if ($inAn): ?>
                    <span class="inline-flex rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-900 dark:text-teal-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Owner in analytics</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-950 dark:text-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase">Owner excluded from analytics</span>
                <?php endif; ?>
                <?php if ($belowMin): ?>
                    <span class="inline-flex rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-800 dark:text-rose-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Below minimum</span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Owner: <a href="<?= Str::e(Url::to('/admin/users/' . $ownerId)) ?>" class="font-bold text-teal-700 dark:text-teal-300 hover:underline"><?= Str::e($uname) ?></a>
                <span class="text-slate-400">·</span> <?= Str::e($email) ?>
            </p>
            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase text-slate-500">Balance (<?= Str::e($baseCurrency) ?>)</dt>
                    <dd class="text-sm font-extrabold tabular-nums text-slate-900 dark:text-white mt-0.5"><?= number_format($balanceBase, 2) ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase text-slate-500">Opening (native)</dt>
                    <dd class="text-sm font-bold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= number_format($open, 2) ?> <?= Str::e($ccy) ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase text-slate-500">Min threshold</dt>
                    <dd class="text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= $minTh !== null && $minTh !== '' ? number_format((float) $minTh, 2) . ' ' . Str::e($ccy) : '—' ?></dd>
                </div>
                <div class="rounded-lg border border-slate-200/90 dark:border-slate-700/70 bg-slate-50/80 dark:bg-slate-900/40 px-2.5 py-2">
                    <dt class="text-[10px] font-bold uppercase text-slate-500">Ledger rows</dt>
                    <dd class="text-sm font-bold tabular-nums text-slate-800 dark:text-slate-100 mt-0.5"><?= number_format($ledgerCount) ?></dd>
                </div>
            </dl>
        </div>
        <div class="flex flex-wrap gap-2 lg:flex-col lg:items-stretch shrink-0">
            <a href="<?= Str::e(Url::to('/admin/transactions?' . http_build_query(['user_id' => $ownerId]))) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-300/90 dark:border-slate-600 bg-white dark:bg-slate-900 px-4 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-100 hover:border-teal-500/50">
                <i data-lucide="arrow-left-right" class="w-4 h-4 text-teal-600"></i> Owner ledger
            </a>
            <a href="<?= Str::e(Url::to('/admin/users/' . $ownerId)) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-4 py-2.5 text-xs font-bold shadow-md">
                <i data-lucide="user" class="w-4 h-4"></i> Owner profile
            </a>
        </div>
    </div>
</section>

<section class="mb-6 grid lg:grid-cols-2 gap-4">
    <div class="<?= Str::e($chartCard) ?>">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Balance &amp; flow activity</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Income &amp; expense on-wallet · transfer net (<?= Str::e($baseCurrency) ?> base)</p>
        <div id="adminWalletDetailFlow" class="mt-3 min-h-[220px]"></div>
    </div>
    <div class="<?= Str::e($chartCard) ?>">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Transfer pulse (90d)</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Rows involving this wallet as source or sink</p>
        <div class="mt-6 grid grid-cols-2 gap-4">
            <div class="rounded-xl border border-sky-200/80 dark:border-sky-900/55 bg-sky-50/80 dark:bg-sky-950/25 p-4">
                <p class="text-[10px] font-bold uppercase text-sky-700 dark:text-sky-300">Transfers</p>
                <p class="text-2xl font-black tabular-nums text-sky-900 dark:text-sky-100 mt-1"><?= number_format((int) ($transferStats['count'] ?? 0)) ?></p>
            </div>
            <div class="rounded-xl border border-indigo-200/80 dark:border-indigo-900/55 bg-indigo-50/80 dark:bg-indigo-950/25 p-4">
                <p class="text-[10px] font-bold uppercase text-indigo-700 dark:text-indigo-300">Volume (base)</p>
                <p class="text-2xl font-black tabular-nums text-indigo-900 dark:text-indigo-100 mt-1"><?= Str::e($baseCurrency) ?> <?= number_format((float) ($transferStats['volume_base'] ?? 0), 0) ?></p>
            </div>
        </div>
    </div>
</section>

<section class="mb-8" id="edit-wallet">
    <p class="<?= Str::e($secKicker) ?>">Configuration</p>
    <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-3">Edit wallet</h2>
    <div class="<?= Str::e($manageShell) ?> p-4 sm:p-6">
        <div class="grid lg:grid-cols-12 gap-5">
            <div class="lg:col-span-8">
                <div class="<?= Str::e($innerCard) ?>">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Wallet fields</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4 leading-relaxed"><strong class="text-slate-700 dark:text-slate-300">Owner</strong> is fixed here to protect ledger integrity — use data tools if you ever need a migration.</p>
                    <form method="post" action="<?= Str::e(Url::to('/admin/wallets/update')) ?>" class="space-y-3">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                        <input type="hidden" name="wallet_user_id" value="<?= $ownerId ?>" />
                        <input type="hidden" name="_redirect" value="detail" />
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Name</label>
                                <input name="name" required value="<?= Str::e($wname) ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Wallet type</label>
                                <select name="wallet_type_id" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold"><?php foreach ($walletTypes as $wt): ?>
                                    <option value="<?= (int) $wt['id'] ?>" <?= $wtypeId === (int) $wt['id'] ? 'selected' : '' ?>><?= Str::e((string) $wt['label']) ?><?= empty($wt['is_active']) ? ' (inactive type)' : '' ?></option>
                                <?php endforeach; ?></select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Currency</label>
                                <select name="currency_id" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-semibold"><?php foreach ($currencies as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= $curId === (int) $c['id'] ? 'selected' : '' ?>><?= Str::e((string) $c['code']) ?></option>
                                <?php endforeach; ?></select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Opening balance</label>
                                <input name="opening_balance" type="number" step="0.01" value="<?= Str::e((string) $open) ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Min threshold (native)</label>
                                <input name="min_balance_threshold" type="number" step="0.01" value="<?= $minTh !== null && $minTh !== '' ? Str::e((string) $minTh) : '' ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" placeholder="Optional" />
                            </div>
                            <div>
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Sort order</label>
                                <input name="sort_order" type="number" value="<?= $sortOrder ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Notes</label>
                                <textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?= Str::e($notes) ?></textarea>
                            </div>
                        </div>
                        <input type="hidden" name="is_active" value="0" />
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="is_active" value="1" <?= $active ? 'checked' : '' ?> class="rounded border-slate-300" /> Active wallet
                        </label>
                        <input type="hidden" name="is_default" value="0" />
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" name="is_default" value="1" <?= $isDefault ? 'checked' : '' ?> class="rounded border-slate-300" /> Default wallet for owner
                        </label>
                        <button type="submit" class="w-full sm:w-auto rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-6 py-2.5 text-sm font-bold shadow-md">Save changes</button>
                    </form>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="<?= Str::e($innerCard) ?>">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Lifecycle</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Ledger: <?= number_format($ledgerCount) ?> · Recurring hooks: <?= number_format($recurringCount) ?></p>
                    <?php if ($active): ?>
                    <form method="post" action="<?= Str::e(Url::to('/admin/wallets/status')) ?>" class="mb-3" onsubmit="return confirm('Deactivate this wallet?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                        <input type="hidden" name="wallet_user_id" value="<?= $ownerId ?>" />
                        <input type="hidden" name="is_active" value="0" />
                        <input type="hidden" name="_redirect" value="detail" />
                        <button type="submit" class="w-full rounded-xl border border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/35 px-4 py-2.5 text-xs font-bold text-amber-950 dark:text-amber-100">Deactivate wallet</button>
                    </form>
                    <?php else: ?>
                    <form method="post" action="<?= Str::e(Url::to('/admin/wallets/status')) ?>" class="mb-3">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                        <input type="hidden" name="wallet_user_id" value="<?= $ownerId ?>" />
                        <input type="hidden" name="is_active" value="1" />
                        <input type="hidden" name="_redirect" value="detail" />
                        <button type="submit" class="w-full rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/35 px-4 py-2.5 text-xs font-bold text-emerald-900 dark:text-emerald-100">Activate wallet</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" action="<?= Str::e(Url::to('/admin/wallets/delete')) ?>" onsubmit="return confirm('Delete permanently? Requires empty ledger and no recurring rules.');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                        <input type="hidden" name="wallet_user_id" value="<?= $ownerId ?>" />
                        <button type="submit" class="w-full rounded-xl border border-rose-300 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/35 px-4 py-2.5 text-xs font-bold text-rose-900 dark:text-rose-100">Delete if safe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-10">
    <p class="<?= Str::e($secKicker) ?>">Activity</p>
    <h2 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-4">Recent transactions</h2>
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/65 bg-white dark:bg-[#0d1424] p-4 sm:p-5">
        <?php if ($recentTx === []): ?>
            <p class="text-sm text-slate-500 dark:text-slate-400 py-8 text-center">No activity yet.</p>
        <?php else: ?>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($recentTx as $t):
                $tt = (string) ($t['type'] ?? '');
                $amt = (float) ($t['amount_base'] ?? 0);
                $td = (string) ($t['transaction_date'] ?? '');
                $title = (string) ($t['title'] ?? '');
                $typeCls = $tt === 'income' ? 'text-emerald-600 dark:text-emerald-400' : ($tt === 'expense' ? 'text-rose-600 dark:text-rose-400' : 'text-sky-600 dark:text-sky-300');
                $sign = $tt === 'expense' ? '−' : ($tt === 'income' ? '+' : '');
                ?>
            <li class="flex items-center gap-3 py-2.5">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate"><?= Str::e($title) ?></p>
                    <p class="text-[11px] text-slate-500"><?= Str::e(substr($td, 0, 10)) ?> · <?= Str::e($tt) ?> · <?= Str::e((string) ($t['wallet_name'] ?? '')) ?></p>
                </div>
                <span class="text-sm font-bold tabular-nums shrink-0 <?= Str::e($typeCls) ?>"><?= Str::e($sign . $baseCurrency . ' ' . number_format($amt, 2)) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var cur = <?= json_encode($baseCurrency, JSON_THROW_ON_ERROR) ?>;
    var months = <?= json_encode($months, JSON_THROW_ON_ERROR) ?>;
    var inc = <?= json_encode($incM, JSON_THROW_ON_ERROR) ?>;
    var exp = <?= json_encode($expM, JSON_THROW_ON_ERROR) ?>;
    var xn = <?= json_encode($xferNet, JSON_THROW_ON_ERROR) ?>;

    var ch = null;
    function mount() {
        if (typeof ApexCharts === 'undefined' || typeof KhfApexTheme === 'undefined') return;
        var el = document.getElementById('adminWalletDetailFlow');
        if (!el) return;
        try { if (ch) ch.destroy(); } catch (e) {}
        ch = null;
        var tt = KhfApexTheme.tokens();
        if (!months.length) {
            el.innerHTML = KhfApexTheme.emptyStateHtml('Not enough history yet.');
            return;
        }
        ch = new ApexCharts(el, Object.assign({}, KhfApexTheme.chart({ type: 'area', height: 240 }), {
            stroke: { curve: 'smooth', width: [2, 2, 2] },
            colors: ['#10b981', '#f43f5e', '#0ea5e9'],
            series: [
                { name: 'Income', data: inc },
                { name: 'Expense', data: exp },
                { name: 'Transfer net', data: xn }
            ],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: tt.incomeExpenseFillShade,
                    type: 'vertical',
                    opacityFrom: KhfApexTheme.isDark() ? 0.28 : 0.22,
                    opacityTo: 0.02
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: 600 } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: tt.axisLabel, fontSize: '11px', fontWeight: 600 }
                }
            },
            grid: KhfApexTheme.grid({ padding: { top: 4, left: 0, right: 8 } }),
            legend: KhfApexTheme.legendTopRight({ fontSize: '11px' }),
            tooltip: Object.assign(KhfApexTheme.tooltip({ shared: true }), {})
        }));
        ch.render();
    }
    if (typeof KhfApexTheme !== 'undefined' && KhfApexTheme.mountOnTheme) {
        KhfApexTheme.mountOnTheme(mount);
    } else if (window.ApexCharts) {
        mount();
    }
})();
</script>
