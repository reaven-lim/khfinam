<?php

declare(strict_types=1);

use App\Helpers\Url;

$totals         = $totals ?? ['income' => 0, 'expense' => 0, 'savings' => 0];
$monthly        = $monthly ?? [];
$topCats        = $topCats ?? [];
$walletBalances = $walletBalances ?? [];
$trend          = $trend ?? [];

$monthLabels = array_column($monthly, 'ym');
$incData     = array_map(fn(array $r): float => round((float) $r['inc'], 2), $monthly);
$expData     = array_map(fn(array $r): float => round((float) $r['exp'], 2), $monthly);
$savData     = array_map(fn(array $r): float => round((float) $r['inc'] - (float) $r['exp'], 2), $monthly);
$catLabels   = array_column($topCats, 'name');
$catData     = array_map(fn(array $r): float => round((float) $r['total'], 2), $topCats);
$wLabels     = array_column($walletBalances, 'name');
$wData       = array_map(fn(array $b): float => round((float) $b['balance_base'], 2), $walletBalances);
$tLabels     = array_column($trend, 'd');
$tData       = array_map(fn(array $r): float => round((float) $r['s'], 2), $trend);
$heatmapUrl  = Url::to('/api/reports/heatmap') . '?year=' . (int) date('Y');

$totalIncome  = (float) $totals['income'];
$totalExpense = (float) $totals['expense'];
$savingsRate  = $totalIncome > 0 ? round(($totalIncome - $totalExpense) / $totalIncome * 100, 1) : 0.0;
?>

<!-- Summary cards -->
<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Income</p>
        <p class="text-lg font-semibold text-emerald-600">RM <?= number_format($totalIncome, 2) ?></p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Expense</p>
        <p class="text-lg font-semibold text-rose-600">RM <?= number_format($totalExpense, 2) ?></p>
    </div>
    <div class="col-span-2 rounded-2xl bg-gradient-to-br from-teal-600 to-teal-800 text-white p-3">
        <p class="text-xs opacity-80">Savings rate</p>
        <p class="text-2xl font-bold"><?= $savingsRate ?>% of income</p>
    </div>
</div>

<!-- Income vs Expense bar -->
<?php if ($monthly !== []): ?>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Income vs Expense (12 months)</h2>
    <canvas id="chartMonthly" height="160"></canvas>
</div>

<!-- Savings trend -->
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Monthly savings trend</h2>
    <canvas id="chartSavings" height="140"></canvas>
</div>
<?php endif; ?>

<!-- Top expense categories -->
<?php if ($topCats !== []): ?>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Top expense categories</h2>
    <canvas id="chartCats" height="180"></canvas>
</div>
<?php endif; ?>

<!-- Spending trend (last 30 days) -->
<?php if ($trend !== []): ?>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Daily spending (last 30 days)</h2>
    <canvas id="chartTrend" height="140"></canvas>
</div>
<?php endif; ?>

<!-- Wallet performance -->
<?php if ($walletBalances !== []): ?>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Wallet balances</h2>
    <canvas id="chartWallets" height="160"></canvas>
</div>
<?php endif; ?>

<!-- Expense heatmap -->
<p class="text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide mb-2">Expense heatmap (<?= (int) date('Y') ?>)</p>
<div id="heatWrap" class="rounded-xl border border-slate-200 dark:border-slate-800 p-3 text-xs overflow-x-auto min-h-[80px] text-slate-500">Loading…</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const d = {
    months:  <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>,
    inc:     <?= json_encode($incData, JSON_HEX_TAG) ?>,
    exp:     <?= json_encode($expData, JSON_HEX_TAG) ?>,
    sav:     <?= json_encode($savData, JSON_HEX_TAG) ?>,
    catLbl:  <?= json_encode($catLabels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>,
    catData: <?= json_encode($catData, JSON_HEX_TAG) ?>,
    wLbl:    <?= json_encode($wLabels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>,
    wData:   <?= json_encode($wData, JSON_HEX_TAG) ?>,
    tLbl:    <?= json_encode($tLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>,
    tData:   <?= json_encode($tData, JSON_HEX_TAG) ?>
  };

  const cfg = { responsive: true, plugins: { legend: { display: false } } };

  if (document.getElementById('chartMonthly')) {
    new Chart(document.getElementById('chartMonthly'), {
      type: 'bar',
      data: {
        labels: d.months,
        datasets: [
          { label: 'Income',  data: d.inc, backgroundColor: '#059669' },
          { label: 'Expense', data: d.exp, backgroundColor: '#e11d48' }
        ]
      },
      options: { ...cfg, plugins: { legend: { display: true, position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
  }

  if (document.getElementById('chartSavings')) {
    new Chart(document.getElementById('chartSavings'), {
      type: 'line',
      data: {
        labels: d.months,
        datasets: [{
          label: 'Savings', data: d.sav,
          borderColor: '#0f766e', backgroundColor: 'rgba(15,118,110,0.1)',
          fill: true, tension: 0.3, pointRadius: 3
        }]
      },
      options: { ...cfg, scales: { y: { beginAtZero: false } } }
    });
  }

  if (document.getElementById('chartCats') && d.catLbl.length) {
    new Chart(document.getElementById('chartCats'), {
      type: 'doughnut',
      data: {
        labels: d.catLbl,
        datasets: [{
          data: d.catData,
          backgroundColor: ['#e11d48','#f97316','#eab308','#22c55e','#0ea5e9','#8b5cf6']
        }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
    });
  }

  if (document.getElementById('chartTrend') && d.tLbl.length) {
    new Chart(document.getElementById('chartTrend'), {
      type: 'line',
      data: {
        labels: d.tLbl,
        datasets: [{
          label: 'Expense', data: d.tData,
          borderColor: '#e11d48', backgroundColor: 'rgba(225,29,72,0.08)',
          fill: true, tension: 0.2, pointRadius: 2
        }]
      },
      options: { ...cfg, scales: { y: { beginAtZero: true } } }
    });
  }

  if (document.getElementById('chartWallets') && d.wLbl.length) {
    const wColors = d.wData.map(v => v >= 0 ? '#0f766e' : '#e11d48');
    new Chart(document.getElementById('chartWallets'), {
      type: 'bar',
      data: {
        labels: d.wLbl,
        datasets: [{ label: 'Balance', data: d.wData, backgroundColor: wColors }]
      },
      options: { ...cfg, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
    });
  }

  // Heatmap
  fetch(<?= json_encode($heatmapUrl, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>, { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('heatWrap');
      if (!el || !data.expenses_by_date) return;
      const entries = Object.entries(data.expenses_by_date).sort((a, b) => a[0].localeCompare(b[0]));
      if (!entries.length) { el.textContent = 'No expense data for this year.'; return; }
      const max = Math.max(...entries.map(e => e[1]), 1);
      el.innerHTML = entries.slice(-90).map(([day, v]) => {
        const intensity = Math.min(1, v / max);
        const bg = 'rgba(220,38,38,' + (0.12 + intensity * 0.7) + ')';
        return '<span title="' + day + ': RM ' + v.toFixed(2) + '" style="display:inline-block;width:10px;height:10px;margin:1px;border-radius:2px;background:' + bg + '"></span>';
      }).join('');
    })
    .catch(() => { const el = document.getElementById('heatWrap'); if (el) el.textContent = 'Could not load heatmap.'; });
})();
</script>
