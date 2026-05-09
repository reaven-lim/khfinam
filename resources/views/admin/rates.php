<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$currencies = $currencies ?? [];
?>
<form method="post" action="<?= Str::e(Url::to('/admin/rates')) ?>" class="mb-6 flex flex-wrap gap-3 items-end rounded-2xl border border-slate-200 p-4">
    <?= Csrf::field() ?>
    <label class="text-sm">From
        <select name="from_currency_id" class="block rounded border px-2 py-1 mt-1"><?php foreach ($currencies as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
        <?php endforeach; ?></select>
    </label>
    <label class="text-sm">To
        <select name="to_currency_id" class="block rounded border px-2 py-1 mt-1"><?php foreach ($currencies as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
        <?php endforeach; ?></select>
    </label>
    <label class="text-sm">Rate<input name="rate" type="number" step="0.000000000001" required class="block rounded border px-2 py-1 mt-1 w-40" /></label>
    <label class="text-sm">Date<input name="effective_date" type="date" value="<?= Str::e(date('Y-m-d')) ?>" class="block rounded border px-2 py-1 mt-1" /></label>
    <button type="submit" class="rounded bg-teal-600 text-white px-4 py-2 text-sm">Add rate</button>
</form>
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-3 text-left">Date</th><th class="p-3">Pair</th><th class="p-3">Rate</th><th class="p-3"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-3"><?= Str::e((string) $r['effective_date']) ?></td>
                <td class="p-3"><?= Str::e((string) $r['from_c'] . ' → ' . $r['to_c']) ?></td>
                <td class="p-3 font-mono text-xs"><?= Str::e((string) $r['rate']) ?></td>
                <td class="p-3">
                    <form method="post" action="<?= Str::e(Url::to('/admin/rates/delete')) ?>" class="inline" onsubmit="return confirm('Remove this rate row?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="rate_id" value="<?= (int) ($r['id'] ?? 0) ?>" />
                        <button type="submit" class="text-rose-600 text-xs">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
