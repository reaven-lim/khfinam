<?php

declare(strict_types=1);

use App\Core\Database;
use App\Helpers\Auth;
use App\Helpers\Str;
use App\Helpers\Url;

$totals            = $totals ?? ['income' => 0, 'expense' => 0, 'savings' => 0];
$wallets           = $wallets ?? [];
$recent            = $recent ?? [];
$lowWarnings       = $lowWarnings ?? [];
$totalBalanceBase  = $totalBalanceBase ?? 0;
$upcomingRecurring = $upcomingRecurring ?? [];
$message           = $message ?? null;

// 6-month sparkline data
$pdo = Database::pdo();
$uid = (int) Auth::id();
$sparkStmt = $pdo->prepare(
    "SELECT DATE_FORMAT(transaction_date,'%Y-%m') AS ym,
            SUM(CASE WHEN type='income'  AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS inc,
            SUM(CASE WHEN type='expense' AND COALESCE(is_internal_transfer,0)=0 THEN amount_base ELSE 0 END) AS exp
     FROM transactions WHERE user_id=? AND deleted_at IS NULL AND parent_transaction_id IS NULL
     GROUP BY ym ORDER BY ym DESC LIMIT 6"
);
$sparkStmt->execute([$uid]);
$monthly6    = array_reverse($sparkStmt->fetchAll(PDO::FETCH_ASSOC));
$monthLabels = array_column($monthly6, 'ym');
$incData     = array_map(fn(array $r): float => round((float) $r['inc'], 2), $monthly6);
$expData     = array_map(fn(array $r): float => round((float) $r['exp'], 2), $monthly6);

$totalIncome  = (float) $totals['income'];
$totalExpense = (float) $totals['expense'];
$totalSavings = (float) $totals['savings'];
$savingsRate  = $totalIncome > 0 ? round($totalSavings / $totalIncome * 100, 1) : 0.0;

// Wallet type config (full class strings so Tailwind CDN can scan them)
$walletConfig = [
    'cash'        => ['icon' => 'wallet',      'bg' => 'bg-emerald-100 dark:bg-emerald-950/50', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bar' => 'bg-emerald-500'],
    'bank'        => ['icon' => 'building-2',  'bg' => 'bg-blue-100 dark:bg-blue-950/50',       'text' => 'text-blue-600 dark:text-blue-400',       'bar' => 'bg-blue-500'],
    'ewallet'     => ['icon' => 'smartphone',  'bg' => 'bg-violet-100 dark:bg-violet-950/50',   'text' => 'text-violet-600 dark:text-violet-400',   'bar' => 'bg-violet-500'],
    'credit_card' => ['icon' => 'credit-card', 'bg' => 'bg-rose-100 dark:bg-rose-950/50',       'text' => 'text-rose-600 dark:text-rose-400',       'bar' => 'bg-rose-500'],
    'other'       => ['icon' => 'briefcase',   'bg' => 'bg-slate-100 dark:bg-slate-800',        'text' => 'text-slate-600 dark:text-slate-400',     'bar' => 'bg-slate-500'],
];

// Simple category → icon mapping
$catIconMap = [
    'food' => 'utensils', 'dining' => 'utensils', 'restaurant' => 'utensils', 'meal' => 'utensils',
    'transport' => 'car', 'travel' => 'plane', 'petrol' => 'fuel', 'fuel' => 'fuel',
    'shopping' => 'shopping-bag', 'cloth' => 'shirt',
    'health' => 'heart-pulse', 'medical' => 'heart-pulse', 'doctor' => 'heart-pulse',
    'entertainment' => 'tv', 'movie' => 'film', 'sport' => 'trophy',
    'education' => 'book-open', 'book' => 'book-open',
    'utilities' => 'zap', 'electric' => 'zap', 'water' => 'droplets',
    'salary' => 'banknote', 'income' => 'trending-up', 'freelance' => 'briefcase',
    'savings' => 'piggy-bank', 'invest' => 'bar-chart-2',
    'rent' => 'home', 'house' => 'home',
    'insurance' => 'shield',
    'gift' => 'gift',
    'coffee' => 'coffee',
];

$getCatIcon = function (string $name) use ($catIconMap): string {
    $n = mb_strtolower($name);
    foreach ($catIconMap as $k => $icon) {
        if (str_contains($n, $k)) return $icon;
    }
    return 'circle-dollar-sign';
};
?>

<?php if ($message): ?>
<div class="mb-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-200 text-sm px-4 py-3 flex items-center gap-2">
    <i data-lucide="check-circle" class="w-4 h-4 shrink-0 text-emerald-500"></i>
    <?= Str::e((string) $message) ?>
</div>
<?php endif; ?>

<?php foreach ($lowWarnings as $lw): ?>
<div class="mb-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200 text-sm px-4 py-3 flex items-center gap-2">
    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 text-amber-500"></i>
    <span>Low balance: <strong><?= Str::e((string) $lw['name']) ?></strong> · RM <?= number_format((float) $lw['balance_base'], 2) ?></span>
</div>
<?php endforeach; ?>

<!-- ─── Hero Net Worth Card ───────────────────────────────── -->
<div class="rounded-2xl bg-gradient-to-br from-teal-600 via-teal-700 to-teal-900 text-white p-5 mb-4 shadow-xl shadow-teal-500/20 relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(255,255,255,0.08),transparent_65%)]"></div>
    <div class="absolute -bottom-8 -right-8 w-32 h-32 rounded-full bg-white/5"></div>
    <div class="relative">
        <div class="flex items-start justify-between mb-3">
            <div>
                <p class="text-[10px] font-bold opacity-65 uppercase tracking-[0.14em]">Total Balance</p>
                <p class="text-4xl font-extrabold tracking-tight mt-1 tabular-nums">
                    RM <?= number_format((float) $totalBalanceBase, 2) ?>
                </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center shrink-0">
                <i data-lucide="wallet" class="w-5 h-5 text-white"></i>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs opacity-70">across <?= count($wallets) ?> wallet<?= count($wallets) !== 1 ? 's' : '' ?></span>
            <?php if ($savingsRate >= 20): ?>
            <span class="bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded-full tracking-wide">✓ <?= $savingsRate ?>% saved</span>
            <?php elseif ($savingsRate > 0): ?>
            <span class="bg-white/15 text-white/80 text-[10px] px-2 py-0.5 rounded-full"><?= $savingsRate ?>% saved</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ─── KPI 2×2 Grid ──────────────────────────────────────── -->
<div class="grid grid-cols-2 gap-3 mb-4">

    <div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600"></i>
            </div>
            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Income</span>
        </div>
        <p class="text-xl font-extrabold text-emerald-600 tabular-nums">RM <?= number_format($totalIncome, 0) ?></p>
        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">All time total</p>
    </div>

    <div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2.5">
            <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center">
                <i data-lucide="trending-down" class="w-4 h-4 text-rose-600"></i>
            </div>
            <span class="text-[10px] font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/50 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Expense</span>
        </div>
        <p class="text-xl font-extrabold text-rose-600 tabular-nums">RM <?= number_format($totalExpense, 0) ?></p>
        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">All time total</p>
    </div>

    <div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2.5">
            <div class="w-8 h-8 rounded-xl bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center">
                <i data-lucide="piggy-bank" class="w-4 h-4 text-teal-600"></i>
            </div>
            <span class="text-[10px] font-bold text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/50 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Saved</span>
        </div>
        <p class="text-xl font-extrabold text-teal-700 dark:text-teal-400 tabular-nums">RM <?= number_format($totalSavings, 0) ?></p>
        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Net savings</p>
    </div>

    <div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2.5">
            <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center">
                <i data-lucide="percent" class="w-4 h-4 text-violet-600"></i>
            </div>
            <span class="text-[10px] font-bold text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/50 px-1.5 py-0.5 rounded-full uppercase tracking-wide">Rate</span>
        </div>
        <p class="text-xl font-extrabold text-violet-700 dark:text-violet-400 tabular-nums"><?= $savingsRate ?>%</p>
        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">of income saved</p>
    </div>
</div>

<!-- ─── Cashflow Mini Chart ───────────────────────────────── -->
<?php if (!empty($monthly6)): ?>
<div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 mb-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Cashflow</h3>
            <p class="text-[10px] text-slate-400 font-medium">6-month overview</p>
        </div>
        <a href="<?= Str::e(Url::to('/dashboard/transactions')) ?>" class="text-xs text-teal-700 dark:text-teal-400 font-semibold flex items-center gap-1 hover:underline">
            Intelligence hub <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
    </div>
    <div id="mobileCashflowChart"></div>
</div>
<?php endif; ?>

<!-- ─── Wallet Strip ──────────────────────────────────────── -->
<?php if (!empty($wallets)): ?>
<div class="mb-4">
    <div class="flex items-center justify-between mb-2.5">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Wallets</h3>
        <a href="<?= Str::e(Url::to('/app/wallets')) ?>" class="text-xs text-teal-700 dark:text-teal-400 font-semibold flex items-center gap-1 hover:underline">
            Manage <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
    </div>
    <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 snap-x snap-mandatory scrollbar-none">
        <?php foreach ($wallets as $w):
            $wType = (string) ($w['wallet_type'] ?? 'other');
            $wCfg  = $walletConfig[$wType] ?? $walletConfig['other'];
        ?>
        <a href="<?= Str::e(Url::to('/app/wallets')) ?>"
            class="snap-start shrink-0 w-44 rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 shadow-sm active:scale-95 transition-transform">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-xl <?= $wCfg['bg'] ?> flex items-center justify-center">
                    <i data-lucide="<?= $wCfg['icon'] ?>" class="w-4 h-4 <?= $wCfg['text'] ?>"></i>
                </div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?= Str::e(str_replace('_', ' ', $wType)) ?></span>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= Str::e((string) $w['name']) ?></p>
            <p class="text-[10px] text-slate-400 mt-0.5 font-medium"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ─── Upcoming Bills ────────────────────────────────────── -->
<?php if (!empty($upcomingRecurring)): ?>
<div class="rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-200/60 dark:border-slate-800 p-4 mb-4 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center">
                <i data-lucide="repeat-2" class="w-3.5 h-3.5 text-violet-600"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Upcoming Bills</h3>
        </div>
        <a href="<?= Str::e(Url::to('/app/recurring')) ?>" class="text-xs text-teal-700 dark:text-teal-400 font-semibold hover:underline">All →</a>
    </div>
    <ul class="space-y-1.5">
        <?php foreach (array_slice($upcomingRecurring, 0, 4) as $ur):
            $ts       = strtotime((string) $ur['next_occurrence']);
            $daysLeft = $ts !== false ? (int) ceil(($ts - time()) / 86400) : 0;
        ?>
        <li class="flex items-center gap-3 py-2 border-b border-slate-100 dark:border-slate-800/80 last:border-0">
            <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center shrink-0">
                <i data-lucide="repeat-2" class="w-4 h-4 text-violet-500"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate"><?= Str::e((string) $ur['title']) ?></p>
                <p class="text-[10px] text-slate-400"><?= Str::e((string) $ur['wallet_name']) ?></p>
            </div>
            <div class="text-right shrink-0">
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">RM <?= number_format((float) $ur['amount'], 2) ?></p>
                <p class="text-[10px] font-semibold <?= $daysLeft <= 1 ? 'text-rose-500' : ($daysLeft <= 3 ? 'text-amber-500' : 'text-slate-400') ?>">
                    <?= $daysLeft <= 0 ? 'Due today' : ($daysLeft === 1 ? 'Tomorrow' : 'In ' . $daysLeft . 'd') ?>
                </p>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- ─── Recent Activity ───────────────────────────────────── -->
<div class="mb-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Recent Activity</h3>
        <span class="text-[10px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full"><?= count($recent) ?> items</span>
    </div>

    <?php if (empty($recent)): ?>
    <div class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 p-8 text-center">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
            <i data-lucide="receipt" class="w-6 h-6 text-slate-400"></i>
        </div>
        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">No transactions yet</p>
        <p class="text-xs text-slate-400 mt-1">Start tracking your finances</p>
        <a href="<?= Str::e(Url::to('/app/add')) ?>"
            class="mt-4 inline-flex items-center gap-2 bg-teal-600 text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-teal-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add transaction
        </a>
    </div>

    <?php else: ?>
    <ul class="space-y-2">
        <?php foreach (array_slice($recent, 0, 10) as $r):
            $isIncome = ($r['type'] ?? '') === 'income';
            $catName  = mb_strtolower((string) ($r['category_name'] ?? ''));
            $icon     = $getCatIcon($catName);
        ?>
        <li>
            <details class="group rounded-xl border border-slate-200/60 dark:border-slate-800 bg-white dark:bg-[#0d1424] shadow-sm overflow-hidden">
                <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none list-none">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 <?= $isIncome ? 'bg-emerald-50 dark:bg-emerald-950/50' : 'bg-rose-50 dark:bg-rose-950/50' ?>">
                        <i data-lucide="<?= $icon ?>" class="w-4 h-4 <?= $isIncome ? 'text-emerald-600' : 'text-rose-500' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate"><?= Str::e((string) $r['title']) ?></p>
                        <p class="text-[10px] text-slate-400 font-medium"><?= Str::e((string) $r['category_name']) ?> · <?= Str::e((string) $r['transaction_date']) ?></p>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-sm font-extrabold <?= $isIncome ? 'text-emerald-600' : 'text-rose-600' ?>">
                            <?= $isIncome ? '+' : '−' ?>RM <?= number_format((float) $r['amount'], 2) ?>
                        </span>
                        <i data-lucide="chevron-down" class="chevron-icon w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                    </div>
                </summary>
                <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/50 dark:bg-slate-900/30">
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                            <i data-lucide="wallet" class="w-3 h-3"></i>
                            <?= Str::e((string) ($r['wallet_name'] ?? '—')) ?>
                        </span>
                        <a href="<?= Str::e(Url::to('/app/transaction/' . (int) $r['id'])) ?>"
                            class="flex items-center gap-1 text-teal-700 dark:text-teal-400 font-semibold hover:underline">
                            View details <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                    <?php if (!empty($r['notes'])): ?>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-2 italic leading-relaxed"><?= Str::e((string) $r['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </details>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<!-- ─── Insight Card ──────────────────────────────────────── -->
<?php if ($totalIncome > 0): ?>
<div class="rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-800 dark:to-[#0d1424] border border-slate-700/50 p-4 mb-2 shadow-sm">
    <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-teal-500/20 flex items-center justify-center shrink-0 mt-0.5">
            <i data-lucide="lightbulb" class="w-4 h-4 text-teal-400"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-slate-300 uppercase tracking-widest mb-1">Financial Insight</p>
            <?php if ($savingsRate >= 20): ?>
            <p class="text-sm text-slate-200 leading-relaxed">You're saving <strong class="text-teal-400"><?= $savingsRate ?>%</strong> of your income. Keep it up — you're building strong financial health.</p>
            <?php elseif ($savingsRate >= 10): ?>
            <p class="text-sm text-slate-200 leading-relaxed">You're saving <strong class="text-amber-400"><?= $savingsRate ?>%</strong> of your income. Try to reach 20% for optimal financial health.</p>
            <?php elseif ($savingsRate > 0): ?>
            <p class="text-sm text-slate-200 leading-relaxed">Your savings rate is <strong class="text-rose-400"><?= $savingsRate ?>%</strong>. Review your expenses to improve your financial position.</p>
            <?php else: ?>
            <p class="text-sm text-slate-200 leading-relaxed">Your expenses exceed your income. Review your spending to improve your financial position.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var isDark = document.documentElement.classList.contains('dark');
    var text   = isDark ? '#94a3b8' : '#64748b';
    var grid   = isDark ? 'rgba(148,163,184,0.06)' : 'rgba(100,116,139,0.06)';
    var months = <?= json_encode($monthLabels, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
    var inc    = <?= json_encode($incData, JSON_HEX_TAG) ?>;
    var exp    = <?= json_encode($expData, JSON_HEX_TAG) ?>;

    var chartEl = document.getElementById('mobileCashflowChart');
    if (chartEl && months.length) {
        new ApexCharts(chartEl, {
            chart: {
                type: 'area', height: 155,
                background: 'transparent',
                toolbar: { show: false },
                animations: { enabled: true, speed: 600 }
            },
            theme: { mode: isDark ? 'dark' : 'light' },
            series: [
                { name: 'Income',  data: inc },
                { name: 'Expense', data: exp }
            ],
            colors: ['#10b981', '#f43f5e'],
            fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.02, stops: [0, 95] } },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: {
                categories: months,
                labels: { style: { colors: text, fontSize: '10px' } },
                axisBorder: { show: false }, axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: text, fontSize: '10px' },
                    formatter: function (v) {
                        return v >= 1000 ? 'RM' + (v / 1000).toFixed(1) + 'k' : 'RM' + v.toFixed(0);
                    }
                }
            },
            grid: { borderColor: grid, strokeDashArray: 4 },
            tooltip: { theme: isDark ? 'dark' : 'light', shared: true, intersect: false },
            legend: { show: true, position: 'top', fontSize: '11px', labels: { colors: text } },
            markers: { size: 3, strokeWidth: 0, hover: { size: 5 } }
        }).render();
    }

    // Animate details chevrons via CSS class toggling
    document.querySelectorAll('details').forEach(function(d) {
        d.addEventListener('toggle', function() {
            var c = d.querySelector('.chevron-icon');
            if (c) c.style.transform = d.open ? 'rotate(180deg)' : '';
        });
    });
})();
</script>
