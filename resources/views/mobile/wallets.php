<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$wallets = $wallets ?? [];
$balances = $balances ?? [];
$currencies = $currencies ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<?php if ($message): ?><div class="mb-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-3 py-2"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-3 rounded-lg bg-rose-50 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div><?php endif; ?>

<ul class="space-y-3 mb-6">
<?php foreach ($wallets as $w): ?>
    <?php $b = $balances[(int) $w['id']] ?? null; ?>
    <li class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 flex justify-between gap-3">
        <div>
            <p class="font-semibold"><?= Str::e((string) $w['name']) ?></p>
            <p class="text-xs text-slate-500"><?= Str::e((string) $w['wallet_type']) ?></p>
            <?php if ($b): ?>
                <p class="text-sm mt-1 <?= $b['below'] ? 'text-amber-700 font-medium' : 'text-slate-700' ?>">Est. base: RM <?= number_format($b['balance_base'], 2) ?></p>
            <?php endif; ?>
        </div>
        <span class="text-sm text-teal-700"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></span>
    </li>
<?php endforeach; ?>
</ul>

<h2 class="text-sm font-semibold mb-2">Transfer (same currency)</h2>
<form method="post" action="<?= Str::e(Url::to('/app/wallets/transfer')) ?>" class="rounded-2xl border border-slate-200 p-4 space-y-2 mb-6">
    <?= Csrf::field() ?>
    <select name="from_wallet_id" required class="w-full rounded border px-2 py-2 text-sm"><?php foreach ($wallets as $w): ?>
        <option value="<?= (int) $w['id'] ?>">From: <?= Str::e((string) $w['name']) ?></option>
    <?php endforeach; ?></select>
    <select name="to_wallet_id" required class="w-full rounded border px-2 py-2 text-sm"><?php foreach ($wallets as $w): ?>
        <option value="<?= (int) $w['id'] ?>">To: <?= Str::e((string) $w['name']) ?></option>
    <?php endforeach; ?></select>
    <input name="amount" type="number" step="0.01" required placeholder="Amount" class="w-full rounded border px-2 py-2 text-sm" />
    <input name="transfer_date" type="date" value="<?= Str::e(date('Y-m-d')) ?>" class="w-full rounded border px-2 py-2 text-sm" />
    <input name="notes" placeholder="Notes" class="w-full rounded border px-2 py-2 text-sm" />
    <button type="submit" class="w-full rounded-xl bg-teal-600 text-white py-2 text-sm font-semibold">Transfer</button>
</form>

<h2 class="text-sm font-semibold mb-2">New wallet</h2>
<form method="post" action="<?= Str::e(Url::to('/app/wallets')) ?>" class="rounded-2xl border border-slate-200 p-4 space-y-2">
    <?= Csrf::field() ?>
    <input name="name" required placeholder="Name" class="w-full rounded border px-2 py-2 text-sm" />
    <select name="wallet_type" class="w-full rounded border px-2 py-2 text-sm">
        <option value="cash">Cash</option>
        <option value="bank">Bank</option>
        <option value="ewallet">E-wallet</option>
        <option value="credit_card">Credit card</option>
        <option value="other">Other</option>
    </select>
    <select name="currency_id" class="w-full rounded border px-2 py-2 text-sm"><?php foreach ($currencies as $c): ?>
        <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
    <?php endforeach; ?></select>
    <input name="opening_balance" type="number" step="0.01" value="0" class="w-full rounded border px-2 py-2 text-sm" />
    <input name="min_balance_threshold" type="number" step="0.01" placeholder="Low balance alert (optional)" class="w-full rounded border px-2 py-2 text-sm" />
    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_default" value="1" /> Default wallet</label>
    <button type="submit" class="w-full rounded-xl bg-slate-800 text-white py-2 text-sm">Create wallet</button>
</form>
