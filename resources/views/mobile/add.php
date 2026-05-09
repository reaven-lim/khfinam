<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;

$wallets = $wallets ?? [];
$categoriesIncome = $categoriesIncome ?? [];
$categoriesExpense = $categoriesExpense ?? [];
$error = $error ?? null;
$defDate = date('Y-m-d');
?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-200 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
<?php endif; ?>
<form method="post" action="<?= Str::e(Url::to('/app/add')) ?>" class="space-y-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 p-4 shadow-sm" onsubmit="if (window.__khFinamSyncCats) window.__khFinamSyncCats(); return true;">
    <?= Csrf::field() ?>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
        <select name="type" id="txType" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
            <option value="expense">Expense</option>
            <option value="income">Income</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Title</label>
        <input name="title" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500" placeholder="e.g. Coffee" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Amount</label>
            <input name="amount" type="number" step="0.01" min="0.01" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100" />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Date</label>
            <input name="transaction_date" type="date" value="<?= Str::e($defDate) ?>" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 [color-scheme:light_dark]" />
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Wallet</label>
        <select name="wallet_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
            <?php foreach ($wallets as $w): ?>
                <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="catExpense">
        <label class="block text-xs font-medium text-slate-500 mb-1">Category</label>
        <select name="category_id_exp" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 category-select">
            <?php foreach ($categoriesExpense as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="catIncome" class="hidden">
        <label class="block text-xs font-medium text-slate-500 mb-1">Category</label>
        <select name="category_id_inc" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 category-select-inc">
            <?php foreach ($categoriesIncome as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" name="category_id" id="category_id" value="" />
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500"></textarea>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Tags (comma-separated)</label>
        <input name="tags" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500" placeholder="e.g. trip, business" />
    </div>
    <label class="flex items-center gap-2 text-xs text-slate-600">
        <input type="checkbox" name="is_consolidated_parent" value="1" />
        Consolidated envelope (add line items later under this transaction)
    </label>
    <button type="submit" class="w-full rounded-xl bg-teal-600 text-white font-semibold py-3 text-sm">Save transaction</button>
</form>
<script>
(function(){
  const type = document.getElementById('txType');
  const ce = document.getElementById('catExpense');
  const ci = document.getElementById('catIncome');
  const hid = document.getElementById('category_id');
  function sync(){
    const inc = type.value === 'income';
    ce.classList.toggle('hidden', inc);
    ci.classList.toggle('hidden', !inc);
    hid.value = inc
      ? (document.querySelector('.category-select-inc')?.value || '')
      : (document.querySelector('.category-select')?.value || '');
  }
  window.__khFinamSyncCats = sync;
  type.addEventListener('change', sync);
  document.querySelector('.category-select')?.addEventListener('change', sync);
  document.querySelector('.category-select-inc')?.addEventListener('change', sync);
  sync();
})();
</script>
