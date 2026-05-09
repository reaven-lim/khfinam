<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$wallets      = $wallets ?? [];
$balances     = $balances ?? [];
$flowRows     = $flowRows ?? [];
$walletTypes  = $walletTypes ?? [];
$currencies   = $currencies ?? [];
$message      = $message ?? null;
$error        = $error ?? null;

$names = [];
$bals = [];
foreach ($wallets as $w) {
    $wid = (int) $w['id'];
    $b = $balances[$wid] ?? null;
    $names[] = (string) $w['name'];
    $bals[] = $b !== null ? round((float) $b['balance_base'], 2) : 0.0;
}

$flowNames = [];
$flowExp = [];
$flowInc = [];
foreach ($flowRows as $fr) {
    $flowNames[] = (string) $fr['name'];
    $flowExp[] = round((float) $fr['exp'], 2);
    $flowInc[] = round((float) $fr['inc'], 2);
}

$totalBal = array_sum($bals);
?>
<?php if ($message): ?>
    <div class="mb-4 rounded-xl border border-teal-200/80 bg-teal-50 dark:bg-teal-950/40 text-teal-900 dark:text-teal-200 text-sm px-4 py-3"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-xl border border-rose-200/80 bg-rose-50 dark:bg-rose-950/35 text-rose-800 dark:text-rose-200 text-sm px-4 py-3"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<section class="mb-8 rounded-3xl border border-slate-200/80 dark:border-slate-700/70 bg-gradient-to-br from-white via-slate-50/95 to-teal-50/30 dark:from-[#111827] dark:via-[#0f1629] dark:to-[#0d1424] p-6 md:p-8 shadow-sm">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400 mb-2">Wallets</p>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Liquidity posture</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Computed in base currency · 90‑day throughput below</p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-bold uppercase text-slate-400 tracking-[0.16em]">Aggregate balance</p>
            <p class="text-3xl font-extrabold tabular-nums text-teal-700 dark:text-teal-300">RM <?= number_format($totalBal, 2) ?></p>
            <span class="inline-block mt-2 text-xs font-medium text-slate-600 dark:text-slate-400">Edit accounts below · or use <a class="text-teal-700 dark:text-teal-400 font-semibold hover:underline" href="<?= Str::e(Url::to('/app/wallets')) ?>">mobile hub</a>.</span>
        </div>
    </div>
</section>

<section class="mb-10 rounded-3xl border border-slate-300/80 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] p-6 md:p-8 shadow-sm">
    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2"><i data-lucide="plus-circle" class="w-4 h-4 text-teal-600 dark:text-teal-400"></i> New wallet</h3>
    <form method="post" action="<?= Str::e(Url::to('/dashboard/wallets/store')) ?>" class="grid lg:grid-cols-12 gap-3 items-end">
        <?= Csrf::field() ?>
        <div class="lg:col-span-3">
            <label class="text-[10px] font-bold uppercase text-slate-500">Name</label>
            <input name="name" required class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
        </div>
        <div class="lg:col-span-3">
            <label class="text-[10px] font-bold uppercase text-slate-500">Type</label>
            <select name="wallet_type_id" required class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($walletTypes as $wt): ?>
                <option value="<?= (int) $wt['id'] ?>"><?= Str::e((string) $wt['label']) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="lg:col-span-2">
            <label class="text-[10px] font-bold uppercase text-slate-500">Currency</label>
            <select name="currency_id" required class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($currencies as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="lg:col-span-2">
            <label class="text-[10px] font-bold uppercase text-slate-500">Opening</label>
            <input type="number" step="0.01" name="opening_balance" value="0" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" />
        </div>
        <div class="lg:col-span-2 flex flex-wrap gap-3 items-center">
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400"><input type="checkbox" name="is_default" value="1" /> Default</label>
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400"><input type="checkbox" name="is_active" value="1" checked /> Active</label>
            <button type="submit" class="rounded-xl bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 text-sm font-bold shadow-sm shrink-0">Create</button>
        </div>
    </form>
</section>

<div class="grid lg:grid-cols-2 gap-5 mb-10">
    <?php foreach ($wallets as $wCard):
        $cwid = (int) $wCard['id'];
        ?>
        <article class="rounded-2xl border border-slate-300/80 dark:border-slate-700/55 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500/20 to-slate-200/50 dark:to-slate-800 flex items-center justify-center shrink-0">
                        <i data-lucide="<?= Str::e((string) ($wCard['type_icon'] ?? 'wallet')) ?>" class="w-5 h-5 text-teal-700 dark:text-teal-400"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-slate-900 dark:text-white truncate"><?= Str::e((string) $wCard['name']) ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?= Str::e((string) ($wCard['type_label'] ?? '')) ?></p>
                    </div>
                </div>
                <?php $bb = $balances[$cwid] ?? null;
        if ($bb): ?>
                    <span class="text-sm font-bold tabular-nums text-emerald-700 dark:text-emerald-400 shrink-0">RM <?= Str::e(number_format((float) $bb['balance_base'], 2)) ?></span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= Str::e(Url::to('/dashboard/wallets/update')) ?>" class="space-y-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $cwid ?>" />
                <div class="grid sm:grid-cols-2 gap-2">
                    <input name="name" required value="<?= Str::e((string) $wCard['name']) ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm" />
                    <select name="wallet_type_id" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm"><?php foreach ($walletTypes as $wt): ?>
                        <option value="<?= (int) $wt['id'] ?>" <?= (int) $wCard['wallet_type_id'] === (int) $wt['id'] ? 'selected' : '' ?>><?= Str::e((string) $wt['label']) ?></option>
                    <?php endforeach; ?></select>
                    <select name="currency_id" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm"><?php foreach ($currencies as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $wCard['currency_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= Str::e((string) $c['code']) ?></option>
                    <?php endforeach; ?></select>
                    <input name="opening_balance" type="number" step="0.01" value="<?= Str::e((string) $wCard['opening_balance']) ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm tabular-nums" />
                </div>
                <input name="min_balance_threshold" type="number" step="0.01" value="<?= isset($wCard['min_balance_threshold']) && $wCard['min_balance_threshold'] !== null ? Str::e((string) $wCard['min_balance_threshold']) : '' ?>" placeholder="Min threshold" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm" />
                <input name="sort_order" type="number" value="<?= (int) $wCard['sort_order'] ?>" class="w-24 rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" />
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm" placeholder="Notes"><?= Str::e((string) ($wCard['notes'] ?? '')) ?></textarea>
                <div class="flex flex-wrap gap-3 items-center">
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" <?= ! empty($wCard['is_active']) ? 'checked' : '' ?> /> Active</label>
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_default" value="1" <?= ! empty($wCard['is_default']) ? 'checked' : '' ?> /> Default</label>
                    <button type="submit" class="ml-auto rounded-lg bg-slate-900 dark:bg-teal-700 text-white text-xs font-bold px-4 py-2">Save</button>
                </div>
            </form>
            <form method="post" action="<?= Str::e(Url::to('/dashboard/wallets/delete')) ?>" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800" onsubmit="return confirm('Delete permanently? Only allowed for wallets with zero transactions and no recurring rules.');">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $cwid ?>" />
                <button type="submit" class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 underline">Delete empty wallet</button>
            </form>
        </article>
        <?php endforeach; ?>
</div>

<section class="grid lg:grid-cols-2 gap-6 mb-10">
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white dark:bg-[#0d1424] p-6 md:p-7 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Balance donut</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Relative weight across <?= count($wallets) ?> wallet<?= count($wallets) !== 1 ? 's' : '' ?></p>
            </div>
        </div>
        <div id="dashWalletDonut" class="flex justify-center" style="min-height:260px;"></div>
    </div>
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white dark:bg-[#0d1424] p-6 md:p-7 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Throughput (90d)</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Income vs expense by pocket</p>
            </div>
        </div>
        <div id="dashWalletFlow" style="min-height:280px;"></div>
    </div>
</section>

<section class="rounded-3xl border border-slate-200/75 dark:border-slate-700/55 bg-white dark:bg-[#0d1424] overflow-hidden mb-10 shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900 text-left">
            <tr class="text-[10px] uppercase tracking-[0.12em] text-slate-400">
                <th class="px-6 py-4 font-bold">Wallet</th>
                <th class="px-6 py-4 font-bold">Type</th>
                <th class="px-6 py-4 font-bold">Currency</th>
                <th class="px-6 py-4 font-bold text-right">Balance (base)</th>
                <th class="px-6 py-4 font-bold text-right">Expense 90d</th>
                <th class="px-6 py-4 font-bold text-right">Income 90d</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($wallets as $w):
                $wid = (int) $w['id'];
                $b = $balances[$wid] ?? null;
                $bal = $b !== null ? (float) $b['balance_base'] : 0.0;
                $frMatch = null;
                foreach ($flowRows as $fr) {
                    if ((int) $fr['id'] === $wid) {
                        $frMatch = $fr;
                        break;
                    }
                }
                ?>
            <tr class="hover:bg-teal-50/40 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-slate-100"><?= Str::e((string) $w['name']) ?></td>
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?= Str::e((string) ($w['type_label'] ?? '')) ?></td>
                <td class="px-6 py-4 text-slate-500"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></td>
                <td class="px-6 py-4 text-right font-bold tabular-nums <?= $bal >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600' ?>">RM <?= number_format($bal, 2) ?></td>
                <td class="px-6 py-4 text-right font-semibold tabular-nums text-rose-600"><?= $frMatch !== null ? 'RM ' . number_format((float) $frMatch['exp'], 2) : '—' ?></td>
                <td class="px-6 py-4 text-right font-semibold tabular-nums text-emerald-600"><?= $frMatch !== null ? 'RM ' . number_format((float) $frMatch['inc'], 2) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($wallets === []): ?>
        <p class="p-12 text-center text-slate-500 dark:text-slate-400 text-sm font-medium">No wallets yet · create one in the panel above.</p>
    <?php endif; ?>
</section>

<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    var dark = document.documentElement.classList.contains('dark');
    var names = <?= json_encode($names, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    var bals = <?= json_encode($bals, JSON_HEX_TAG) ?>;
    if (names.length) {
        new ApexCharts(document.querySelector('#dashWalletDonut'), {
            chart: { type: 'donut', height: 292, background: 'transparent' },
            theme: { mode: dark ? 'dark' : 'light' },
            labels: names,
            series: bals,
            legend: { position: 'bottom', fontWeight: 500 },
            colors: ['#0f766e', '#0891b2', '#6366f1', '#9333ea', '#f43f5e', '#f59e0b', '#14b8a6'],
            fill: { type: 'gradient', gradient: { shade: dark ? 'dark' : 'light', shadeIntensity: 0.4 } },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '68%', labels: {
                show: true, name: {},
                total: { show: true, label: 'Total', formatter: function () {
                    var t = bals.reduce(function (a, x) { return a + (+x || 0); }, 0);
                    return 'RM ' + Math.round(t).toLocaleString();
                }}
            } } } },
            tooltip: { y: { formatter: function (v) { return 'RM ' + Number(v || 0).toLocaleString(undefined, {minimumFractionDigits: 2}); } } }
        }).render();
    }

    var fnames = <?= json_encode($flowNames, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    if (fnames.length) {
        new ApexCharts(document.querySelector('#dashWalletFlow'), {
            chart: { type: 'bar', stacked: false, toolbar: { show: false }, background: 'transparent', height: 300 },
            theme: { mode: dark ? 'dark' : 'light' },
            series: [
                { name: 'Expense 90d', data: <?= json_encode($flowExp, JSON_HEX_TAG) ?> },
                { name: 'Income 90d', data: <?= json_encode($flowInc, JSON_HEX_TAG) ?> }
            ],
            xaxis: { categories: fnames, labels: { rotate: fnames.length > 5 ? -25 : 0 } },
            colors: ['#f43f5e', '#10b981'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '58%' }, dataLabels: { position: 'top' } },
            dataLabels: { enabled: false },
            grid: { borderColor: dark ? '#334155' : '#e2e8f0' },
            tooltip: {
                theme: dark ? 'dark' : 'light',
                shared: false,
                y: {
                    formatter: function (value) {
                        return 'RM ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
                    }
                }
            },
            legend: { position: 'top' }
        }).render();
    }
})();
</script>
