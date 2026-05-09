<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;

$totals = $totals ?? ['income' => 0, 'expense' => 0, 'savings' => 0];
$wallets = $wallets ?? [];
$recent = $recent ?? [];
$lowWarnings = $lowWarnings ?? [];
$totalBalanceBase = $totalBalanceBase ?? null;
$upcomingRecurring = $upcomingRecurring ?? [];
$message = $message ?? null;
?>
<?php if ($message): ?>
    <div class="mb-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-3 py-2"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php foreach ($lowWarnings as $lw): ?>
    <div class="mb-3 rounded-lg bg-amber-50 dark:bg-amber-950 border border-amber-200 text-amber-900 dark:text-amber-100 text-sm px-3 py-2">
        Low balance: <strong><?= Str::e($lw['name']) ?></strong> (base est. <?= number_format($lw['balance_base'], 2) ?>)
    </div>
<?php endforeach; ?>
<section class="space-y-4">
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-slate-200/80 dark:border-slate-800 p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Income</p>
            <p class="text-xl font-semibold text-emerald-600">RM <?= number_format((float) $totals['income'], 2) ?></p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-slate-200/80 dark:border-slate-800 p-4">
            <p class="text-xs text-slate-500 uppercase tracking-wide">Expense</p>
            <p class="text-xl font-semibold text-rose-600">RM <?= number_format((float) $totals['expense'], 2) ?></p>
        </div>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-teal-600 to-teal-800 text-white p-4 shadow-lg">
        <p class="text-sm opacity-90">Savings (income − expense)</p>
        <p class="text-3xl font-bold tracking-tight">RM <?= number_format((float) $totals['savings'], 2) ?></p>
    </div>
    <?php if ($totalBalanceBase !== null): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Total balance (base currency)</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-white">RM <?= number_format((float) $totalBalanceBase, 2) ?></p>
        <p class="text-xs text-slate-500 mt-1">Sum of estimated wallet balances.</p>
    </div>
    <?php endif; ?>
    <?php if ($upcomingRecurring !== []): ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">Upcoming recurring (7 days)</h2>
        <ul class="space-y-2 text-sm">
            <?php foreach ($upcomingRecurring as $ur): ?>
                <li class="flex justify-between gap-2">
                    <span class="truncate"><?= Str::e((string) $ur['title']) ?></span>
                    <span class="text-slate-500 shrink-0"><?= Str::e((string) $ur['next_occurrence']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <a href="<?= Str::e(Url::to('/app/recurring')) ?>" class="text-xs text-teal-700 dark:text-teal-300 font-medium mt-2 inline-block">All schedules →</a>
    </div>
    <?php endif; ?>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Wallets</h2>
        <ul class="space-y-2">
            <?php foreach ($wallets as $w): ?>
                <li class="flex justify-between text-sm">
                    <span><?= Str::e((string) $w['name']) ?></span>
                    <span class="text-slate-500"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if ($wallets === []): ?>
                <li class="text-sm text-slate-500">No wallets yet.</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Recent</h2>
        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
            <?php foreach ($recent as $r): ?>
                <li class="py-3 flex justify-between gap-3">
                    <div>
                        <a href="<?= Str::e(Url::to('/app/transaction/' . (int) $r['id'])) ?>" class="font-medium text-sm text-teal-800 dark:text-teal-200"><?= Str::e((string) $r['title']) ?></a>
                        <p class="text-xs text-slate-500"><?= Str::e((string) $r['category_name']) ?> · <?= Str::e((string) $r['transaction_date']) ?></p>
                    </div>
                    <div class="text-sm font-semibold <?= ($r['type'] ?? '') === 'income' ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= ($r['type'] ?? '') === 'income' ? '+' : '−' ?>RM <?= number_format((float) $r['amount'], 2) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<canvas id="miniChart" height="120" class="mt-4 w-full max-h-40"></canvas>
<script>
(function(){
  const ctx = document.getElementById('miniChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Income','Expense'],
      datasets: [{ data: [<?= json_encode((float) $totals['income']) ?>, <?= json_encode((float) $totals['expense']) ?>], backgroundColor: ['#059669','#e11d48'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
})();
</script>
