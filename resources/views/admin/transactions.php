<?php

declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$users = $users ?? [];
$filterUserId = $filterUserId ?? 0;
$filterFrom = $filterFrom ?? '';
$filterTo = $filterTo ?? '';
$filterType = $filterType ?? '';
?>
<form method="get" action="<?= Str::e(Url::to('/admin/transactions')) ?>" class="mb-6 flex flex-wrap gap-3 items-end rounded-2xl border border-slate-200 dark:border-slate-800 p-4 text-sm">
    <label>User
        <select name="user_id" class="block mt-1 rounded border px-2 py-1">
            <option value="0">All</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= (int) $u['id'] ?>" <?= (int) $filterUserId === (int) $u['id'] ? 'selected' : '' ?>><?= Str::e((string) $u['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>From<input type="date" name="from" value="<?= Str::e((string) $filterFrom) ?>" class="block mt-1 rounded border px-2 py-1" /></label>
    <label>To<input type="date" name="to" value="<?= Str::e((string) $filterTo) ?>" class="block mt-1 rounded border px-2 py-1" /></label>
    <label>Type
        <select name="type" class="block mt-1 rounded border px-2 py-1">
            <option value="">All</option>
            <option value="income" <?= $filterType === 'income' ? 'selected' : '' ?>>income</option>
            <option value="expense" <?= $filterType === 'expense' ? 'selected' : '' ?>>expense</option>
        </select>
    </label>
    <button type="submit" class="rounded bg-slate-800 text-white px-4 py-2">Apply</button>
</form>
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800 text-left">
            <tr>
                <th class="p-3">Date</th>
                <th class="p-3">User</th>
                <th class="p-3">Title</th>
                <th class="p-3">Wallet</th>
                <th class="p-3">Type</th>
                <th class="p-3 text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-3 whitespace-nowrap"><?= Str::e((string) $r['transaction_date']) ?></td>
                <td class="p-3"><?= Str::e((string) $r['username']) ?></td>
                <td class="p-3"><?= Str::e((string) $r['title']) ?></td>
                <td class="p-3"><?= Str::e((string) $r['wallet_name']) ?></td>
                <td class="p-3"><?= Str::e((string) $r['type']) ?></td>
                <td class="p-3 text-right font-medium">RM <?= number_format((float) $r['amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
