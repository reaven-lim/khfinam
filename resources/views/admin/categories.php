<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
?>
<form method="post" action="<?= Str::e(Url::to('/admin/categories')) ?>" class="mb-6 flex flex-wrap gap-2 items-end rounded-2xl border border-slate-200 p-4">
    <?= Csrf::field() ?>
    <label class="text-sm">Name<input name="name" required class="block rounded border px-2 py-1 ml-1" /></label>
    <label class="text-sm">Slug<input name="slug" class="block rounded border px-2 py-1 ml-1" placeholder="optional" /></label>
    <select name="type" class="rounded border px-2 py-1 text-sm"><option value="expense">expense</option><option value="income">income</option></select>
    <input name="color" type="text" value="#6366f1" class="w-24 rounded border px-1 py-1 text-sm" />
    <input name="icon" placeholder="icon" class="w-24 rounded border px-1 py-1 text-sm" />
    <input name="sort_order" type="number" value="0" class="w-16 rounded border px-1 py-1 text-sm" />
    <button type="submit" class="rounded bg-teal-600 text-white px-3 py-1 text-sm">Add</button>
</form>
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800 text-left"><tr><th class="p-3">Name</th><th class="p-3">Type</th><th class="p-3">System</th><th class="p-3">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800 align-top">
                <td class="p-3"><?= Str::e((string) $r['name']) ?> <?php if (empty($r['user_id']) && ! empty($r['is_system'])): ?><span class="text-xs text-slate-400">(global)</span><?php endif; ?></td>
                <td class="p-3"><?= Str::e((string) $r['type']) ?></td>
                <td class="p-3"><?= ! empty($r['is_system']) ? 'Yes' : 'No' ?></td>
                <td class="p-3">
                    <?php if (empty($r['user_id'])): ?>
                    <form method="post" action="<?= Str::e(Url::to('/admin/categories/update')) ?>" class="space-y-1 text-xs">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="category_id" value="<?= (int) $r['id'] ?>" />
                        <input name="name" value="<?= Str::e((string) $r['name']) ?>" class="w-full rounded border px-1 py-0.5" />
                        <input name="slug" value="<?= Str::e((string) ($r['slug'] ?? '')) ?>" placeholder="slug" class="w-full rounded border px-1 py-0.5" />
                        <select name="type" class="rounded border px-1 py-0.5"><option value="expense" <?= ($r['type'] ?? '') === 'expense' ? 'selected' : '' ?>>expense</option><option value="income" <?= ($r['type'] ?? '') === 'income' ? 'selected' : '' ?>>income</option></select>
                        <input name="icon" value="<?= Str::e((string) ($r['icon'] ?? 'category')) ?>" placeholder="icon" class="w-24 rounded border px-1 py-0.5" />
                        <input name="color" type="text" value="<?= Str::e((string) ($r['color'] ?? '#6366f1')) ?>" class="w-20 rounded border px-1" />
                        <input name="sort_order" type="number" value="<?= (int) ($r['sort_order'] ?? 0) ?>" class="w-16 rounded border px-1" />
                        <button type="submit" class="text-teal-700 font-medium">Save</button>
                    </form>
                    <form method="post" action="<?= Str::e(Url::to('/admin/categories/delete')) ?>" class="mt-2" onsubmit="return confirm('Delete this global category?');">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="category_id" value="<?= (int) $r['id'] ?>" />
                        <button type="submit" class="text-rose-600 text-xs">Delete</button>
                    </form>
                    <?php else: ?>
                        <span class="text-slate-400 text-xs">User-owned</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
