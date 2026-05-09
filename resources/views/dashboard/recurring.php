<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$activeCount = $activeCount ?? 0;
$pausedCount = $pausedCount ?? 0;

$nextLabels = [];
$nextAmounts = [];
foreach ($rows as $idx => $rr) {
    if ((int) ($rr['is_paused'] ?? 0)) {
        continue;
    }
    if (count($nextLabels) >= 10) {
        break;
    }
    $nextLabels[] = '#' . ($idx + 1) . ' ' . mb_substr((string) $rr['title'], 0, 18);
    $nextAmounts[] = round((float) $rr['amount'], 2);
}
?>

<section class="mb-8 rounded-3xl border border-slate-200/80 dark:border-slate-700/70 bg-white dark:bg-gradient-to-br dark:from-[#111827] dark:via-[#0f1629] dark:to-[#0d1424] p-6 md:p-8 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400 mb-2">Recurring</p>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Schedule intelligence</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-xl"><?= Str::e((string) $activeCount) ?> active · <?= Str::e((string) $pausedCount) ?> paused · based on rules you created</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= Str::e(Url::to('/app/recurring')) ?>" class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 dark:border-slate-600 px-5 py-3 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/90">
                <i data-lucide="smartphone" class="w-4 h-4"></i> Manage in mobile hub
            </a>
            <a href="<?= Str::e(Url::to('/app/recurring/new')) ?>" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-800 text-white px-5 py-3 text-sm font-bold shadow-lg shadow-teal-500/25">
                <i data-lucide="plus" class="w-4 h-4"></i> New rule
            </a>
        </div>
    </div>
</section>

<section class="grid lg:grid-cols-5 gap-6 mb-10">
    <div class="lg:col-span-2 rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white dark:bg-[#0d1424] p-6 shadow-sm flex flex-col min-h-[220px]">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Next commitments</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Active rules by sequence</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-2 py-1 rounded-full">Forecast</span>
        </div>
        <div id="dashRecurringSpark" class="flex-1 min-h-[140px]"></div>
    </div>
    <div class="lg:col-span-3 rounded-3xl border border-slate-200/70 dark:border-slate-700/50 bg-white dark:bg-[#0d1424] p-6 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-left text-[10px] uppercase tracking-[0.12em] text-slate-400 border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="pb-3 font-bold">Schedule</th>
                    <th class="pb-3 font-bold">Wallet</th>
                    <th class="pb-3 font-bold">Next</th>
                    <th class="pb-3 font-bold text-right">Amt</th>
                    <th class="pb-3 font-bold text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($rows as $r): ?>
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-3 pr-3 font-semibold text-slate-900 dark:text-slate-100"><?= Str::e((string) $r['title']) ?></td>
                    <td class="py-3 pr-3 text-slate-600 dark:text-slate-300"><?= Str::e((string) ($r['wallet_name'] ?? '')) ?></td>
                    <td class="py-3 pr-3 text-xs text-slate-500 whitespace-nowrap"><?= Str::e((string) ($r['next_occurrence'] ?? '')) ?></td>
                    <td class="py-3 pr-3 text-right font-bold tabular-nums <?= (($r['type'] ?? '') === 'income') ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">RM <?= number_format((float) $r['amount'], 2) ?></td>
                    <td class="py-3 text-center">
                        <?php if ((int) ($r['is_paused'] ?? 0)): ?>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300">Paused</span>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300">Active</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?>
                <tr><td colspan="5" class="py-10 text-center text-slate-500">No recurring rules yet · create one from Mobile hub.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;
    var el = document.getElementById('dashRecurringSpark');
    if (!el || !<?= json_encode($nextLabels) ?>.length) return;
    var dark = document.documentElement.classList.contains('dark');
    new ApexCharts(el, {
        chart: { type: 'bar', height: 210, toolbar: { show: false }, background: 'transparent' },
        theme: { mode: dark ? 'dark' : 'light' },
        plotOptions: { bar: { borderRadius: 8, horizontal: false, columnWidth: '62%' } },
        series: [{ name: 'Amount (RM)', data: <?= json_encode($nextAmounts, JSON_HEX_TAG) ?> }],
        xaxis: { categories: <?= json_encode($nextLabels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?> },
        colors: ['#0f766e'],
        grid: { borderColor: dark ? '#1e293b' : '#e2e8f0' },
        dataLabels: { enabled: false }
    }).render();
})();
</script>
