<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$preview = $preview ?? [];
$users = $users ?? [];
$forUserId = (int) ($forUserId ?? 0);
$walletOptions = $walletOptions ?? [];
$categoriesIncome = $categoriesIncome ?? [];
$categoriesExpense = $categoriesExpense ?? [];
$currencies = $currencies ?? [];
?>
<?php if (! empty($message)): ?><div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-2 text-sm"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if (! empty($error)): ?><div class="mb-4 rounded-lg bg-rose-50 text-rose-800 px-4 py-2 text-sm"><?= Str::e((string) $error) ?></div><?php endif; ?>

<section class="mb-8 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
    <h2 class="text-sm font-semibold mb-3">Create schedule for user</h2>
    <form method="get" action="<?= Str::e(Url::to('/admin/recurring')) ?>" class="flex flex-wrap gap-2 items-end mb-4">
        <label class="text-sm">User
            <select name="for_user" class="block mt-1 rounded border px-2 py-2 text-sm">
                <option value="0">— Select user —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= $forUserId === (int) $u['id'] ? 'selected' : '' ?>><?= Str::e((string) $u['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="rounded bg-slate-800 text-white px-4 py-2 text-sm">Load wallets &amp; categories</button>
    </form>
    <?php if ($forUserId > 0 && $walletOptions === []): ?>
        <p class="text-sm text-amber-700">No active wallets for this user. Add a wallet from the mobile app or extend user management.</p>
    <?php elseif ($forUserId > 0): ?>
    <form method="post" action="<?= Str::e(Url::to('/admin/recurring/create')) ?>" class="space-y-3 max-w-xl text-sm">
        <?= Csrf::field() ?>
        <input type="hidden" name="user_id" value="<?= $forUserId ?>" />
        <div>
            <label class="text-xs text-slate-500">Type</label>
            <select name="type" id="adm_tx_type" class="mt-1 w-full rounded border px-2 py-2">
                <option value="expense">Expense</option>
                <option value="income">Income</option>
            </select>
        </div>
        <label class="block">Wallet
            <select name="wallet_id" required class="mt-1 w-full rounded border px-2 py-2">
                <?php foreach ($walletOptions as $w): ?>
                    <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?> (<?= Str::e((string) ($w['currency_code'] ?? '')) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <div>
            <label class="text-xs text-slate-500">Category</label>
            <select name="category_id" id="adm_cat_exp" required class="mt-1 w-full rounded border px-2 py-2">
                <?php foreach ($categoriesExpense as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="adm_cat_inc" required class="mt-1 w-full rounded border px-2 py-2 hidden" disabled>
                <?php foreach ($categoriesIncome as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <label class="block">Title<input name="title" required class="mt-1 w-full rounded border px-2 py-2" /></label>
        <div class="grid grid-cols-2 gap-2">
            <label class="block">Amount<input name="amount" type="number" step="0.01" min="0.01" required class="mt-1 w-full rounded border px-2 py-2" /></label>
            <label class="block">Currency
                <select name="currency_id" class="mt-1 w-full rounded border px-2 py-2">
                    <?php foreach ($currencies as $cur): ?>
                        <option value="<?= (int) $cur['id'] ?>"><?= Str::e((string) ($cur['code'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <label class="block">Frequency
                <select name="frequency" class="mt-1 w-full rounded border px-2 py-2">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom (days)</option>
                </select>
            </label>
            <label class="block">Interval<input name="interval_value" type="number" min="1" value="1" class="mt-1 w-full rounded border px-2 py-2" /></label>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <label class="block">Start<input name="start_date" type="date" required value="<?= Str::e(date('Y-m-d')) ?>" class="mt-1 w-full rounded border px-2 py-2" /></label>
            <label class="block">End (opt)<input name="end_date" type="date" class="mt-1 w-full rounded border px-2 py-2" /></label>
        </div>
        <label class="block">Notes<textarea name="notes" rows="2" class="mt-1 w-full rounded border px-2 py-2"></textarea></label>
        <button type="submit" class="rounded-lg bg-teal-600 text-white px-4 py-2 font-semibold">Create schedule</button>
    </form>
    <script>
    (function(){
      var t = document.getElementById('adm_tx_type');
      var ce = document.getElementById('adm_cat_exp');
      var ci = document.getElementById('adm_cat_inc');
      function sync(){
        var inc = t && t.value === 'income';
        if (ce) { ce.classList.toggle('hidden', inc); ce.disabled = inc; if(!inc) ce.setAttribute('name','category_id'); else ce.removeAttribute('name'); }
        if (ci) { ci.classList.toggle('hidden', !inc); ci.disabled = !inc; if(inc) ci.setAttribute('name','category_id'); else ci.removeAttribute('name'); }
      }
      if (t) t.addEventListener('change', sync);
      sync();
    })();
    </script>
    <?php endif; ?>
</section>

<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800"><tr>
            <th class="p-2 text-left">User</th>
            <th class="p-2 text-left">Title</th>
            <th class="p-2">Next</th>
            <th class="p-2">Preview</th>
            <th class="p-2">Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800 align-top">
                <td class="p-2"><?= Str::e((string) $r['username']) ?></td>
                <td class="p-2"><?= Str::e((string) $r['title']) ?> <span class="text-slate-400">(<?= Str::e((string) $r['frequency']) ?>)</span></td>
                <td class="p-2 whitespace-nowrap"><?= Str::e((string) $r['next_occurrence']) ?></td>
                <td class="p-2 text-xs text-slate-500"><?php
                    $pv = $preview[$r['id']] ?? [];
                echo Str::e(implode(', ', $pv));
                ?></td>
                <td class="p-2">
                    <form method="post" action="<?= Str::e(Url::to('/admin/recurring/run')) ?>" class="inline">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="schedule_id" value="<?= (int) $r['id'] ?>" />
                        <input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>" />
                        <button type="submit" class="text-xs text-teal-700 font-medium">Run now</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
