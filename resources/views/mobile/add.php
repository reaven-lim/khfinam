<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$wallets = $wallets ?? [];
$categoriesIncome = $categoriesIncome ?? [];
$categoriesExpense = $categoriesExpense ?? [];
$error = $error ?? null;
$oldTx = is_array($oldTx ?? null) ? $oldTx : [];

$oRaw = (string) ($oldTx['type'] ?? 'expense');
$otype = ($oRaw === 'income') ? 'income' : (($oRaw === 'transfer') ? 'transfer' : 'expense');
$widSel = isset($oldTx['wallet_id']) ? (int) $oldTx['wallet_id'] : 0;
$fromSel = isset($oldTx['from_wallet_id']) ? (int) $oldTx['from_wallet_id'] : 0;
$toSel = isset($oldTx['to_wallet_id']) ? (int) $oldTx['to_wallet_id'] : 0;
$catPost = isset($oldTx['category_id']) ? (int) $oldTx['category_id'] : 0;
$canTransfer = count($wallets) >= 2;
$tVal = isset($oldTx['title']) ? (string) $oldTx['title'] : '';
$aVal = isset($oldTx['amount']) ? (string) $oldTx['amount'] : '';
$dVal = isset($oldTx['transaction_date']) && is_string($oldTx['transaction_date'])
    ? (string) $oldTx['transaction_date'] : date('Y-m-d');
$nVal = isset($oldTx['notes']) ? (string) $oldTx['notes'] : '';
$tagsVal = isset($oldTx['tags']) ? (string) $oldTx['tags'] : '';
$consolidated = ! empty($oldTx['is_consolidated_parent']);
?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-200 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<?php if ($wallets === []): ?>
    <div class="rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 px-6 py-12 shadow-inner text-center mb-16">
        <?php View::partial('components/ui/empty-state-muted', [
            'icon' => 'wallet',
            'title' => 'Create a wallet to post transactions',
            'subtitle' => 'Accounts must exist in KHFinaM before ledger entries.',
        ]); ?>
        <div class="flex flex-col sm:flex-row gap-3 justify-center mt-6">
            <a href="<?= Str::e(Url::to('/app/wallets')) ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm px-5 py-3 shadow-sm transition-colors">
                Create wallet · Mobile hub
                <i data-lucide="arrow-right" class="w-4 h-4 opacity-90"></i>
            </a>
            <a href="<?= Str::e(Url::to('/dashboard/wallets')) ?>" class="inline-flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-600 px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                Web dashboard wallets
            </a>
        </div>
    </div>
<?php else: ?>
<form method="post" action="<?= Str::e(Url::to('/app/add')) ?>" class="space-y-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 p-4 shadow-sm" onsubmit="if (window.__khFinamSyncCats) window.__khFinamSyncCats(); return true;">
    <?= Csrf::field() ?>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
        <select name="type" id="txType" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
            <option value="expense" <?= $otype === 'expense' ? 'selected' : '' ?>>Expense</option>
            <option value="income" <?= $otype === 'income' ? 'selected' : '' ?>>Income</option>
            <option value="transfer" <?= $otype === 'transfer' ? 'selected' : '' ?> <?= ! $canTransfer ? 'disabled' : '' ?>>Transfer</option>
        </select>
        <?php if (! $canTransfer): ?>
            <p class="text-[11px] text-slate-500 mt-1">Add at least two wallets to use transfers.</p>
        <?php endif; ?>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Title</label>
        <input name="title" id="txTitle" value="<?= Str::e($tVal) ?>" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500" placeholder="e.g. Coffee or TnG top-up" />
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Amount</label>
            <input name="amount" type="number" step="0.01" min="0.01" required value="<?= Str::e($aVal !== '' ? $aVal : '') ?>" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 tabular-nums" />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Date</label>
            <input name="transaction_date" type="date" value="<?= Str::e($dVal) ?>" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 [color-scheme:light_dark]" />
        </div>
    </div>
    <div id="walletRow" class="<?= $otype === 'transfer' ? 'hidden' : '' ?>">
        <label class="block text-xs font-medium text-slate-500 mb-1">Wallet</label>
        <select name="wallet_id" id="wallet_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
            <?php foreach ($wallets as $w):
                $wopt = (int) $w['id'];
                $lbl = (string) $w['name'];
                $tln = isset($w['type_label']) ? ' · ' . (string) $w['type_label'] : ''; ?>
                <option value="<?= $wopt ?>" <?= $widSel === $wopt ? 'selected' : '' ?>><?= Str::e($lbl . $tln) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="transferRow" class="<?= $otype === 'transfer' ? '' : 'hidden' ?> space-y-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">From wallet</label>
            <select name="from_wallet_id" id="from_wallet_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
                <?php foreach ($wallets as $w):
                    $wopt = (int) $w['id'];
                    $lbl = (string) $w['name'];
                    $tln = isset($w['type_label']) ? ' · ' . (string) $w['type_label'] : ''; ?>
                    <option value="<?= $wopt ?>" <?= $fromSel === $wopt ? 'selected' : '' ?>><?= Str::e($lbl . $tln) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">To wallet</label>
            <select name="to_wallet_id" id="to_wallet_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100">
                <?php foreach ($wallets as $w):
                    $wopt = (int) $w['id'];
                    $lbl = (string) $w['name'];
                    $tln = isset($w['type_label']) ? ' · ' . (string) $w['type_label'] : ''; ?>
                    <option value="<?= $wopt ?>" <?= $toSel === $wopt ? 'selected' : '' ?>><?= Str::e($lbl . $tln) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">Same currency required. Does not count as income or expense in reports.</p>
    </div>
    <div id="catExpense" class="<?= $otype === 'income' || $otype === 'transfer' ? 'hidden' : '' ?>">
        <label class="block text-xs font-medium text-slate-500 mb-1">Category</label>
        <select name="category_id_exp" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 category-select">
            <?php foreach ($categoriesExpense as $c):
                $cid = (int) $c['id'];
                ?>
                <option value="<?= $cid ?>" <?= ($otype === 'expense' && $catPost === $cid) ? 'selected' : '' ?>><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="catIncome" class="<?= $otype === 'income' ? '' : 'hidden' ?>">
        <label class="block text-xs font-medium text-slate-500 mb-1">Category</label>
        <select name="category_id_inc" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 category-select-inc">
            <?php foreach ($categoriesIncome as $c):
                $cid = (int) $c['id'];
                ?>
                <option value="<?= $cid ?>" <?= ($otype === 'income' && $catPost === $cid) ? 'selected' : '' ?>><?= Str::e((string) $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" name="category_id" id="category_id" value="<?= $catPost ?: '' ?>" />
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Notes</label>
        <textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500"><?= Str::e($nVal) ?></textarea>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Tags (comma-separated)</label>
        <input name="tags" value="<?= Str::e($tagsVal) ?>" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500" placeholder="e.g. trip, business" />
    </div>
    <label id="consolidatedRow" class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 <?= $otype === 'transfer' ? 'hidden' : '' ?>">
        <input type="checkbox" name="is_consolidated_parent" value="1" <?= $consolidated ? 'checked' : '' ?> />
        Consolidated envelope (add line items later under this transaction)
    </label>
    <button type="submit" class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 active:scale-[0.98] transition-transform text-white font-semibold py-3 text-sm shadow-sm">Save transaction</button>
</form>
<script>
(function(){
  const type = document.getElementById('txType');
  const ce = document.getElementById('catExpense');
  const ci = document.getElementById('catIncome');
  const hid = document.getElementById('category_id');
  const walletRow = document.getElementById('walletRow');
  const transferRow = document.getElementById('transferRow');
  const walletSel = document.getElementById('wallet_id');
  const fromSel = document.getElementById('from_wallet_id');
  const toSel = document.getElementById('to_wallet_id');
  const consolidatedRow = document.getElementById('consolidatedRow');
  const titleIn = document.getElementById('txTitle');
  function sync(){
    const tf = type.value === 'transfer';
    const inc = type.value === 'income';
    if (walletRow) walletRow.classList.toggle('hidden', tf);
    if (transferRow) transferRow.classList.toggle('hidden', !tf);
    if (consolidatedRow) consolidatedRow.classList.toggle('hidden', tf);
    if (walletSel) walletSel.required = !tf;
    if (fromSel) fromSel.required = tf;
    if (toSel) toSel.required = tf;
    ce.classList.toggle('hidden', inc || tf);
    ci.classList.toggle('hidden', !inc || tf);
    if (tf) {
      hid.value = '';
      if (titleIn) titleIn.removeAttribute('required');
    } else {
      hid.value = inc
        ? (document.querySelector('.category-select-inc')?.value || '')
        : (document.querySelector('.category-select')?.value || '');
      if (titleIn) titleIn.setAttribute('required', 'required');
    }
  }
  window.__khFinamSyncCats = sync;
  type.addEventListener('change', sync);
  document.querySelector('.category-select')?.addEventListener('change', sync);
  document.querySelector('.category-select-inc')?.addEventListener('change', sync);
  sync();
})();
</script>
<?php endif; ?>
