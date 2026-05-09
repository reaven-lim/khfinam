<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;

$wallets   = $wallets ?? [];
$balances  = $balances ?? [];
$flowRows  = $flowRows ?? [];

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
            <a class="inline-block mt-2 text-xs font-semibold text-teal-700 dark:text-teal-400 hover:underline" href="<?= Str::e(Url::to('/app/wallets')) ?>">Manage wallets on mobile hub →</a>
        </div>
    </div>
</section>

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
                <td class="px-6 py-4 text-slate-600 dark:text-slate-300 capitalize"><?= Str::e(str_replace('_', ' ', (string) ($w['wallet_type'] ?? ''))) ?></td>
                <td class="px-6 py-4 text-slate-500"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></td>
                <td class="px-6 py-4 text-right font-bold tabular-nums <?= $bal >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600' ?>">RM <?= number_format($bal, 2) ?></td>
                <td class="px-6 py-4 text-right font-semibold tabular-nums text-rose-600"><?= $frMatch !== null ? 'RM ' . number_format((float) $frMatch['exp'], 2) : '—' ?></td>
                <td class="px-6 py-4 text-right font-semibold tabular-nums text-emerald-600"><?= $frMatch !== null ? 'RM ' . number_format((float) $frMatch['inc'], 2) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($wallets === []): ?>
        <p class="p-12 text-center text-slate-500 dark:text-slate-400 text-sm font-medium">No wallets yet · open Mobile hub → Wallets.</p>
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
