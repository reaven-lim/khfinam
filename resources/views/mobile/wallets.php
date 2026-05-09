<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$wallets = $wallets ?? [];
$currencies = $currencies ?? [];
$walletTypes = $walletTypes ?? [];
$balances = $balances ?? [];
$message = $message ?? null;
$error = $error ?? null;

$walletsTransfer = array_values(array_filter($wallets, static fn (array $w): bool => ! empty($w['is_active'])));
?>

<?php if ($message): ?><div class="mb-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 text-emerald-900 dark:text-emerald-200 text-sm px-4 py-2.5"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-3 rounded-xl bg-rose-50 dark:bg-rose-950/35 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-200 text-sm px-4 py-2.5"><?= Str::e((string) $error) ?></div><?php endif; ?>

<ul class="space-y-4 mb-6">
<?php foreach ($wallets as $w): ?>
    <?php
    $wid = (int) $w['id'];
$b = $balances[$wid] ?? null;
$activeWallet = ! empty($w['is_active']);
    ?>
    <li class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-4 flex gap-4">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500/20 to-slate-200 dark:from-teal-500/25 dark:to-slate-800 flex items-center justify-center shrink-0 ring-1 ring-teal-500/25">
                <i data-lucide="<?= Str::e((string) ($w['type_icon'] ?? 'wallet')) ?>" class="w-5 h-5 text-teal-700 dark:text-teal-300"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <p class="font-semibold text-slate-900 dark:text-slate-100"><?= Str::e((string) $w['name']) ?></p>
                    <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/70 text-teal-800 dark:text-teal-200 ring-1 ring-teal-200/70"><?= Str::e((string) ($w['type_label'] ?? '')) ?></span>
                    <?php if (! $activeWallet): ?>
                        <span class="text-[10px] font-bold uppercase text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 rounded-full">Inactive</span>
                    <?php endif; ?>
                    <?php if (! empty($w['is_default'])): ?>
                        <span class="text-[10px] font-bold uppercase text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/50 px-2 rounded-full ring-1 ring-violet-200/80">Default</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500"><?= Str::e((string) ($w['currency_code'] ?? '')) ?> · <?= $activeWallet ? 'Available in transaction forms' : 'Hidden until reactivated' ?></p>
                <?php if ($b): ?>
                    <p class="text-sm mt-1 <?= $b['below'] ? 'text-amber-700 dark:text-amber-400 font-semibold' : 'text-slate-700 dark:text-slate-200 tabular-nums font-medium' ?>">
                        Est. balance (base): RM <?= number_format((float) $b['balance_base'], 2) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/60 px-4 py-4">
            <form method="post" action="<?= Str::e(Url::to('/app/wallets/update')) ?>" class="space-y-2">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <input name="name" required value="<?= Str::e((string) $w['name']) ?>" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
                    <select name="wallet_type_id" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"><?php foreach ($walletTypes as $wt): ?>
                        <option value="<?= (int) $wt['id'] ?>" <?= (int) $w['wallet_type_id'] === (int) $wt['id'] ? 'selected' : '' ?>><?= Str::e((string) $wt['label']) ?></option>
                    <?php endforeach; ?></select>
                    <select name="currency_id" class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm"><?php foreach ($currencies as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $w['currency_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= Str::e((string) $c['code']) ?></option>
                    <?php endforeach; ?></select>
                    <input name="opening_balance" type="number" step="0.01" value="<?= Str::e((string) $w['opening_balance']) ?>" class="rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm tabular-nums" />
                </div>
                <input name="min_balance_threshold" type="number" step="0.01" value="<?= $w['min_balance_threshold'] !== null && $w['min_balance_threshold'] !== '' ? Str::e((string) $w['min_balance_threshold']) : '' ?>" placeholder="Low balance alert" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm" />
                <input name="sort_order" type="number" value="<?= (int) $w['sort_order'] ?>" class="w-28 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm" />
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950" placeholder="Notes"><?= Str::e((string) ($w['notes'] ?? '')) ?></textarea>
                <div class="flex flex-wrap gap-4 items-center">
                    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="is_active" value="1" <?= $activeWallet ? 'checked' : '' ?> /> Active</label>
                    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="is_default" value="1" <?= ! empty($w['is_default']) ? 'checked' : '' ?> /> Default wallet</label>
                    <button type="submit" class="ml-auto rounded-xl bg-teal-600 text-white font-semibold text-sm px-4 py-2">Save changes</button>
                </div>
            </form>
            <form method="post" action="<?= Str::e(Url::to('/app/wallets/delete')) ?>" class="mt-3" onsubmit="return confirm('Delete this wallet permanently? Allowed only when it has zero transactions and no recurring rules.');">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                <button type="submit" class="text-xs font-semibold text-rose-600 underline">Remove empty wallet</button>
            </form>
        </div>
    </li>
<?php endforeach; ?>
</ul>

<?php if (count($walletsTransfer) >= 2): ?>
<h2 class="text-sm font-semibold mb-2 text-slate-800 dark:text-slate-200">Transfer <span class="text-xs font-normal text-slate-500">· same currency</span></h2>
<form method="post" action="<?= Str::e(Url::to('/app/wallets/transfer')) ?>" class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-2 mb-6 bg-white dark:bg-slate-950/50">
    <?= Csrf::field() ?>
    <select name="from_wallet_id" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950"><?php foreach ($walletsTransfer as $w): ?>
        <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?></option>
    <?php endforeach; ?></select>
    <select name="to_wallet_id" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950"><?php foreach ($walletsTransfer as $w): ?>
        <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?></option>
    <?php endforeach; ?></select>
    <input name="amount" type="number" step="0.01" required placeholder="Amount" class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-slate-950" />
    <input name="transfer_date" type="date" value="<?= Str::e(date('Y-m-d')) ?>" class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-slate-950 [color-scheme:light_dark]" />
    <input name="notes" placeholder="Notes" class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-slate-950" />
    <button type="submit" class="w-full rounded-xl bg-teal-600 text-white py-3 text-sm font-semibold shadow-sm">Record transfer</button>
</form>
<?php elseif (count($wallets) >= 2): ?>
<p class="text-xs text-slate-500 dark:text-slate-400 mb-6">Transfers need at least two <strong class="font-semibold">active</strong> wallets — reactivate accounts or create another pocket.</p>
<?php endif; ?>

<h2 class="text-sm font-semibold mb-2 text-slate-800 dark:text-slate-200">New wallet</h2>
<form method="post" action="<?= Str::e(Url::to('/app/wallets')) ?>" class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 mb-16 bg-white dark:bg-slate-950/40">
    <?= Csrf::field() ?>
    <input name="name" required placeholder="Name · e.g. Maybank Savings" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950" />
    <select name="wallet_type_id" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950"><?php foreach ($walletTypes as $wt): ?>
        <option value="<?= (int) $wt['id'] ?>"><?= Str::e((string) $wt['label']) ?></option>
    <?php endforeach; ?></select>
    <select name="currency_id" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm bg-white dark:bg-slate-950"><?php foreach ($currencies as $c): ?>
        <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
    <?php endforeach; ?></select>
    <input name="opening_balance" type="number" step="0.01" value="0" class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-slate-950" />
    <input name="min_balance_threshold" type="number" step="0.01" placeholder="Low balance alert (optional)" class="w-full rounded-lg border px-3 py-2 text-sm bg-white dark:bg-slate-950" />
    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="is_default" value="1" /> Default wallet</label>
    <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="is_active" value="1" checked /> Active · show in Spend/Add flows</label>
    <button type="submit" class="w-full rounded-xl bg-slate-900 dark:bg-teal-600 text-white py-3 text-sm font-semibold shadow-sm">Create wallet</button>
</form>
