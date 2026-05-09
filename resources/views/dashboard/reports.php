<?php

declare(strict_types=1);

use App\Core\Database;
use App\Helpers\Auth;
use App\Helpers\Str;
use App\Helpers\Url;
use PDO;

$uid = (int) Auth::id();
$pdo = Database::pdo();

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS ym,
            SUM(CASE WHEN type='income'  AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
            SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
     FROM transactions
     WHERE user_id = ? AND deleted_at IS NULL AND parent_transaction_id IS NULL
     GROUP BY ym ORDER BY ym ASC LIMIT 12"
);
$stmt->execute([$uid]);
$monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

$s2 = $pdo->prepare(
    "SELECT c.name, COALESCE(SUM(t.amount_base),0) AS total
     FROM transactions t JOIN categories c ON c.id=t.category_id
     WHERE t.user_id = ? AND t.deleted_at IS NULL AND t.type='expense'
       AND COALESCE(t.is_internal_transfer,0)=0 AND t.parent_transaction_id IS NULL
     GROUP BY c.id, c.name ORDER BY total DESC LIMIT 8"
);
$s2->execute([$uid]);
$topCats = $s2->fetchAll(PDO::FETCH_ASSOC);

$walletStmt = $pdo->prepare(
    "SELECT w.name,
            w.opening_balance +
            COALESCE(SUM(CASE WHEN t.type='income' THEN t.amount_base
                              WHEN t.type='expense' THEN -t.amount_base
                              ELSE 0 END), 0) AS balance
     FROM wallets w
     LEFT JOIN transactions t ON t.wallet_id = w.id AND t.user_id = w.user_id AND t.deleted_at IS NULL
     WHERE w.user_id = ?
     GROUP BY w.id, w.name, w.opening_balance
     ORDER BY balance DESC"
);
$walletStmt->execute([$uid]);
$wallets = $walletStmt->fetchAll(PDO::FETCH_ASSOC);

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
$y = (int) date('Y');
?>
<p class="mb-6 text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Exports contain <strong class="font-semibold text-slate-800 dark:text-slate-200">only your ledger</strong>. Platform-wide snapshots remain in the Administrator console under System reports.</p>

<p class="mb-8 text-sm text-slate-600 dark:text-slate-400 flex flex-wrap gap-x-6 gap-y-3">
    <a class="text-teal-700 dark:text-teal-300 underline font-semibold" href="<?= Str::e(Url::to('/dashboard/reports/csv')) ?>">Export my CSV</a>
    <a class="text-teal-700 dark:text-teal-300 underline font-semibold" href="<?= Str::e(Url::to('/dashboard/reports/pdf')) ?>">Export my PDF</a>
    <a class="text-teal-700 dark:text-teal-300 underline font-semibold" target="_blank" rel="noopener" href="<?= Str::e(Url::to('/api/reports/heatmap')) ?>?year=<?= $y ?>">Expense heatmap (JSON)</a>
</p>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">Income vs expense (recent months)</h2>
        <canvas id="dashRptMonthly" height="220"></canvas>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">Savings trend</h2>
        <canvas id="dashRptSavings" height="220"></canvas>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">Top expense categories</h2>
        <canvas id="dashRptCats" height="220"></canvas>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4">Wallet balances (base)</h2>
        <canvas id="dashRptWallets" height="220"></canvas>
    </div>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0d1424] mb-10 shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900 text-left">
            <tr>
                <th class="p-3 font-semibold">Month</th>
                <th class="p-3 font-semibold text-right">Income</th>
                <th class="p-3 font-semibold text-right">Expense</th>
                <th class="p-3 font-semibold text-right">Savings</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php foreach (array_reverse($monthly) as $r):
            $sav = round((float) $r['inc'] - (float) $r['exp'], 2); ?>
            <tr>
                <td class="p-3"><?= Str::e((string) $r['ym']) ?></td>
                <td class="p-3 text-right text-emerald-600 font-medium">RM <?= number_format((float) $r['inc'], 2) ?></td>
                <td class="p-3 text-right text-rose-600 font-medium">RM <?= number_format((float) $r['exp'], 2) ?></td>
                <td class="p-3 text-right font-bold <?= $sav >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-rose-600' ?>">RM <?= number_format($sav, 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($monthly === []): ?>
            <tr><td colspan="4" class="p-8 text-center text-slate-500">No activity yet · add transactions from Mobile hub.</td></tr>
        <?php endif; ?>
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

  const mCanvas = document.getElementById('dashRptMonthly');
  const sCanvas = document.getElementById('dashRptSavings');
  const cCanvas = document.getElementById('dashRptCats');
  const wCanvas = document.getElementById('dashRptWallets');

  if (mCanvas && months.length) new Chart(mCanvas, {
    type: 'bar',
    data: {
      labels: months,
      datasets: [
        { label: 'Income', data: incData, backgroundColor: '#059669' },
        { label: 'Expense', data: expData, backgroundColor: '#e11d48' }
      ]
    },
    options: { ...defaults, scales: { y: { beginAtZero: true } } }
  });

  if (sCanvas && months.length) new Chart(sCanvas, {
    type: 'line',
    data: {
      labels: months,
      datasets: [{
        label: 'Savings (base)',
        data: savData,
        borderColor: '#0f766e',
        backgroundColor: 'rgba(15,118,110,0.1)',
        fill: true,
        tension: 0.35,
        pointRadius: 4
      }]
    },
    options: { ...defaults, scales: { y: { beginAtZero: false } } }
  });

  if (cCanvas && catLbl.length) new Chart(cCanvas, {
    type: 'doughnut',
    data: {
      labels: catLbl,
      datasets: [{ data: catData, backgroundColor: ['#e11d48','#f97316','#eab308','#22c55e','#0ea5e9','#8b5cf6','#ec4899','#14b8a6'] }]
    },
    options: defaults
  });

  if (wCanvas && wLbl.length) new Chart(wCanvas, {
    type: 'bar',
    data: {
      labels: wLbl,
      datasets: [{ label: 'Balance (base)', data: wData, backgroundColor: wData.map(function (v){ return v >= 0 ? '#0f766e' : '#e11d48'; }) }]
    },
    options: { ...defaults, indexAxis: 'y', scales: { x: { beginAtZero: true } } }
  });
})();
</script>
