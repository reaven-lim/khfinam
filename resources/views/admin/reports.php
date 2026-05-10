<?php

declare(strict_types=1);

use App\Core\Database;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Repositories\UserRepository;

$pdo = Database::pdo();
$uAnalytics = '(' . UserRepository::analyticsIncludedUserIdsSubquery() . ')';

// 12-month income vs expense (analytics-scoped users only)
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
            SUM(CASE WHEN type='income'  AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
            SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
     FROM transactions
     WHERE deleted_at IS NULL AND parent_transaction_id IS NULL AND user_id IN {$uAnalytics}
     GROUP BY ym ORDER BY ym ASC LIMIT 12"
)->fetchAll(PDO::FETCH_ASSOC);

// Top 8 expense categories (all time)
$topCats = $pdo->query(
    "SELECT c.name, COALESCE(SUM(t.amount_base),0) AS total
     FROM transactions t
     JOIN categories c ON c.id = t.category_id
     WHERE t.deleted_at IS NULL AND t.type='expense'
       AND COALESCE(t.is_internal_transfer,0)=0
       AND t.parent_transaction_id IS NULL AND t.user_id IN {$uAnalytics}
     GROUP BY c.id, c.name
     ORDER BY total DESC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

// Per-wallet balance estimate (analytics-scoped members only)
$wallets = $pdo->query(
    "SELECT w.name,
            w.opening_balance +
            COALESCE(SUM(CASE WHEN t.type='income' AND t.parent_transaction_id IS NULL THEN t.amount_base
                              WHEN t.type='expense' AND t.parent_transaction_id IS NULL THEN -t.amount_base
                              ELSE 0 END), 0) AS balance
     FROM wallets w
     INNER JOIN users uw ON uw.id = w.user_id AND uw.include_in_analytics = 1
     LEFT JOIN transactions t ON t.wallet_id = w.id AND t.deleted_at IS NULL AND t.parent_transaction_id IS NULL
     GROUP BY w.id, w.name, w.opening_balance
     ORDER BY balance DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Savings per month (last 12)
$savingsMonthly = array_map(
    fn(array $r): float => round((float) $r['inc'] - (float) $r['exp'], 2),
    $monthly
);

$monthLabels = array_column($monthly, 'ym');
$incData     = array_map(fn(array $r): float => round((float) $r['inc'], 2), $monthly);
$expData     = array_map(fn(array $r): float => round((float) $r['exp'], 2), $monthly);
$catLabels   = array_column($topCats, 'name');
$catData     = array_map(fn(array $r): float => round((float) $r['total'], 2), $topCats);
$walletNames = array_column($wallets, 'name');
$walletBal   = array_map(fn(array $r): float => round((float) $r['balance'], 2), $wallets);
?>
<p class="mb-3 text-xs text-slate-500 dark:text-slate-400 max-w-3xl leading-relaxed border border-slate-200/90 dark:border-slate-700 rounded-xl px-4 py-3 bg-slate-50/80 dark:bg-slate-900/40">
    Figures below include only users with <strong class="font-semibold text-slate-700 dark:text-slate-200">Include in analytics</strong> enabled. Excluded accounts stay active; their data appears on that user&rsquo;s own admin profile and in their personal app, but not here.
</p>
<p class="mb-4 text-sm text-slate-600 dark:text-slate-400 flex flex-wrap gap-4">
    <a class="text-teal-700 dark:text-teal-300 underline font-medium" href="<?= Str::e(Url::to('/api/reports/csv')) ?>">Export CSV</a>
    <a class="text-teal-700 dark:text-teal-300 underline font-medium" href="<?= Str::e(Url::to('/api/reports/pdf')) ?>">Export PDF</a>
    <a class="text-teal-700 dark:text-teal-300 underline font-medium" target="_blank" rel="noopener" href="<?= Str::e(Url::to('/api/reports/heatmap')) ?>?year=<?= (int) date('Y') ?>">Heatmap JSON</a>
</p>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <!-- Income vs Expense trend -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Income vs Expense (last 12 months)</h2>
        <canvas id="rptMonthly" height="220"></canvas>
    </div>

    <!-- Savings trend -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Savings trend</h2>
        <canvas id="rptSavings" height="220"></canvas>
    </div>

    <!-- Top expense categories -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Top expense categories</h2>
        <canvas id="rptCats" height="220"></canvas>
    </div>

    <!-- Wallet balances -->
    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-3">Wallet balances (base currency)</h2>
        <canvas id="rptWallets" height="220"></canvas>
    </div>
</div>

<!-- Monthly summary table -->
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 mb-6">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800 text-left">
            <tr>
                <th class="p-3">Month</th>
                <th class="p-3 text-right">Income</th>
                <th class="p-3 text-right">Expense</th>
                <th class="p-3 text-right">Savings</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (array_reverse($monthly) as $i => $r): $sav = round((float)$r['inc'] - (float)$r['exp'], 2); ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-3"><?= Str::e((string) $r['ym']) ?></td>
                <td class="p-3 text-right text-emerald-600">RM <?= number_format((float) $r['inc'], 2) ?></td>
                <td class="p-3 text-right text-rose-600">RM <?= number_format((float) $r['exp'], 2) ?></td>
                <td class="p-3 text-right font-semibold <?= $sav >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-rose-600' ?>">RM <?= number_format($sav, 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const months  = <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
  const incData = <?= json_encode($incData, JSON_HEX_TAG) ?>;
  const expData = <?= json_encode($expData, JSON_HEX_TAG) ?>;
  const savData = <?= json_encode($savingsMonthly, JSON_HEX_TAG) ?>;
  const catLbl  = <?= json_encode($catLabels, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
  const catData = <?= json_encode($catData, JSON_HEX_TAG) ?>;
  const wLbl    = <?= json_encode($walletNames, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
  const wData   = <?= json_encode($walletBal, JSON_HEX_TAG) ?>;

  const defaults = { responsive: true, plugins: { legend: { position: 'bottom' } } };

  new Chart(document.getElementById('rptMonthly'), {
    type: 'bar',
    data: {
      labels: months,
      datasets: [
        { label: 'Income',  data: incData, backgroundColor: '#059669' },
        { label: 'Expense', data: expData, backgroundColor: '#e11d48' }
      ]
    },
    options: { ...defaults, scales: { y: { beginAtZero: true } } }
  });

  new Chart(document.getElementById('rptSavings'), {
    type: 'line',
    data: {
      labels: months,
      datasets: [{
        label: 'Savings (MYR base)',
        data: savData,
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15,118,110,0.1)',
        fill: true,
        tension: 0.3,
        pointRadius: 4
      }]
    },
    options: { ...defaults, scales: { y: { beginAtZero: false } } }
  });

  new Chart(document.getElementById('rptCats'), {
    type: 'doughnut',
    data: {
      labels: catLbl,
      datasets: [{
        data: catData,
        backgroundColor: ['#e11d48','#f97316','#eab308','#22c55e','#0ea5e9','#8b5cf6','#ec4899','#14b8a6']
      }]
    },
    options: defaults
  });

  const wColors = wData.map(v => v >= 0 ? '#0f766e' : '#e11d48');
  new Chart(document.getElementById('rptWallets'), {
    type: 'bar',
    data: {
      labels: wLbl,
      datasets: [{ label: 'Balance (base)', data: wData, backgroundColor: wColors }]
    },
    options: { ...defaults, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
  });
})();
</script>
