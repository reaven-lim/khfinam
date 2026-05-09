<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$wallets = $wallets ?? [];
$catsIn = $categoriesIncome ?? [];
$catsEx = $categoriesExpense ?? [];
$currencies = $currencies ?? [];
$error = $error ?? null;
?>
<?php if ($error): ?>
    <div class="mb-3 rounded-lg bg-rose-50 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<form method="post" action="<?= Str::e(Url::to('/app/recurring/new')) ?>" class="space-y-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
    <?= Csrf::field() ?>
    <div>
        <label class="text-xs text-slate-500">Type</label>
        <select name="type" id="tx_type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            <option value="expense">Expense</option>
            <option value="income">Income</option>
        </select>
    </div>
    <div>
        <label class="text-xs text-slate-500">Wallet</label>
        <select name="wallet_id" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            <?php foreach ($wallets as $w): ?>
                <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?> (<?= Str::e((string) ($w['currency_code'] ?? '')) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="text-xs text-slate-500">Category</label>
        <select name="category_id" id="cat_exp" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            <?php foreach ($catsEx as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="cat_inc" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm hidden" disabled>
            <?php foreach ($catsIn as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="text-xs text-slate-500">Title</label>
        <input name="title" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-slate-500">Amount</label>
            <input name="amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
        </div>
        <div>
            <label class="text-xs text-slate-500">Currency</label>
            <select name="currency_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                <?php foreach ($currencies as $cur): ?>
                    <option value="<?= (int) $cur['id'] ?>"><?= Str::e((string) ($cur['code'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-slate-500">Frequency</label>
            <select name="frequency" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly" selected>Monthly</option>
                <option value="yearly">Yearly</option>
                <option value="custom">Custom (days)</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Every N (days/weeks/etc.)</label>
            <input name="interval_value" type="number" min="1" value="1" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-xs text-slate-500">Start</label>
            <input name="start_date" type="date" required value="<?= Str::e(date('Y-m-d')) ?>" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
        </div>
        <div>
            <label class="text-xs text-slate-500">End (optional)</label>
            <input name="end_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" />
        </div>
    </div>
    <div>
        <label class="text-xs text-slate-500">Notes</label>
        <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></textarea>
    </div>
    <button type="submit" class="w-full rounded-xl bg-teal-600 text-white font-semibold py-3">Create schedule</button>
</form>
<script>
(function(){
  var t = document.getElementById('tx_type');
  var ce = document.getElementById('cat_exp');
  var ci = document.getElementById('cat_inc');
  function sync(){
    var inc = t && t.value === 'income';
    if (ce) { ce.classList.toggle('hidden', inc); ce.disabled = inc; ce.removeAttribute('name'); }
    if (ci) { ci.classList.toggle('hidden', !inc); ci.disabled = !inc; if(inc) ci.setAttribute('name','category_id'); else ci.removeAttribute('name'); }
    if (ce && !inc) ce.setAttribute('name','category_id');
  }
  if (t) t.addEventListener('change', sync);
  sync();
})();
</script>
