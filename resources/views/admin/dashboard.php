<?php

declare(strict_types=1);

use App\Helpers\Str;

$counts = $counts ?? ['users' => 0, 'transactions' => 0];
$totals = $totals ?? ['income' => 0, 'expense' => 0, 'savings' => 0];
?>
<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <p class="text-xs text-slate-500 uppercase">Users</p>
        <p class="text-3xl font-semibold"><?= (int) $counts['users'] ?></p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <p class="text-xs text-slate-500 uppercase">Transactions</p>
        <p class="text-3xl font-semibold"><?= (int) $counts['transactions'] ?></p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
        <p class="text-xs text-slate-500 uppercase">Global savings (sum)</p>
        <p class="text-3xl font-semibold text-teal-700 dark:text-teal-300">RM <?= number_format((float) $totals['savings'], 2) ?></p>
    </div>
</div>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6">
    <canvas id="a1" height="100"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('a1'), {
  type: 'line',
  data: {
    labels: ['Income', 'Expense'],
    datasets: [{ label: 'MYR (base)', data: [<?= json_encode((float) $totals['income']) ?>, <?= json_encode((float) $totals['expense']) ?>], borderColor: '#0f766e', tension: 0.2 }]
  },
  options: { plugins: { legend: { display: false } } }
});
</script>
