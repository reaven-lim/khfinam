<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$tx = $tx ?? [];
$children = $children ?? [];
$tags = $tags ?? [];
$attachments = $attachments ?? [];
$wallets = $wallets ?? [];
$categoriesIncome = $categoriesIncome ?? [];
$categoriesExpense = $categoriesExpense ?? [];
$error = $error ?? null;
$base = Url::basePath();
$walletsActiveTx = array_values(array_filter($wallets, static fn (array $x): bool => ! empty($x['is_active'])));
$isTransfer = ($tx['type'] ?? '') === 'transfer';
?>
<?php if ($error): ?>
    <div class="mb-3 rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-800 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<section class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 mb-4">
    <form method="post" action="<?= Str::e(Url::to('/app/transaction/' . (int) $tx['id'])) ?>">
        <?= Csrf::field() ?>
        <div class="space-y-2 text-sm">
            <?php if ($isTransfer): ?>
                <p class="text-xs font-semibold text-teal-700 dark:text-teal-400">Transfer <span class="text-slate-500 font-normal">· not counted as income or expense</span></p>
            <?php endif; ?>
            <input name="title" value="<?= Str::e((string) $tx['title']) ?>" class="w-full font-semibold rounded border border-slate-300 px-2 py-1 dark:border-slate-600 dark:bg-slate-950" />
            <div class="grid grid-cols-2 gap-2">
                <input name="amount" type="number" step="0.01" min="0.01" value="<?= Str::e((string) $tx['amount']) ?>" class="rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950" />
                <input name="transaction_date" type="date" value="<?= Str::e((string) $tx['transaction_date']) ?>" class="rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950" />
            </div>
            <?php if (! $isTransfer): ?>
                <select name="type" id="edType" class="w-full rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950">
                    <option value="expense" <?= ($tx['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
                    <option value="income" <?= ($tx['type'] ?? '') === 'income' ? 'selected' : '' ?>>Income</option>
                </select>
            <?php endif; ?>
            <?php if ($wallets === []): ?>
                <p class="text-xs text-amber-700 dark:text-amber-300 rounded border border-amber-200 dark:border-amber-900 px-2 py-2">No wallets on file — create a wallet before saving.</p>
            <?php elseif ($isTransfer): ?>
                <label class="block text-[11px] font-medium text-slate-500">From wallet</label>
                <select name="from_wallet_id" required class="w-full rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950"><?php foreach ($wallets as $w):
                    $inactive = empty($w['is_active']); ?>
                    <option value="<?= (int) $w['id'] ?>" <?= (int) $w['id'] === (int) ($tx['from_wallet_id'] ?? 0) ? 'selected' : '' ?>><?= Str::e((string) $w['name']) ?><?php if ($inactive): ?> (inactive)<?php endif; ?><?= isset($w['type_label']) ? ' · ' . Str::e((string) $w['type_label']) : '' ?></option>
                <?php endforeach; ?></select>
                <label class="block text-[11px] font-medium text-slate-500 mt-2">To wallet</label>
                <select name="to_wallet_id" required class="w-full rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950"><?php foreach ($wallets as $w):
                    $inactive = empty($w['is_active']); ?>
                    <option value="<?= (int) $w['id'] ?>" <?= (int) $w['id'] === (int) ($tx['to_wallet_id'] ?? 0) ? 'selected' : '' ?>><?= Str::e((string) $w['name']) ?><?php if ($inactive): ?> (inactive)<?php endif; ?><?= isset($w['type_label']) ? ' · ' . Str::e((string) $w['type_label']) : '' ?></option>
                <?php endforeach; ?></select>
                <p class="text-[11px] text-slate-500">Both wallets must share the same currency.</p>
            <?php else: ?>
                <select name="wallet_id" required class="w-full rounded border px-2 py-1"><?php foreach ($wallets as $w):
                    $inactive = empty($w['is_active']); ?>
                    <option value="<?= (int) $w['id'] ?>" <?= (int) $w['id'] === (int) ($tx['wallet_id'] ?? 0) ? 'selected' : '' ?>><?= Str::e((string) $w['name']) ?><?php if ($inactive): ?> <?= ' (inactive)'; endif; ?><?= isset($w['type_label']) ? ' · ' . Str::e((string) $w['type_label']) : '' ?></option>
                <?php endforeach; ?></select>
            <?php endif; ?>
            <?php if (! $isTransfer): ?>
            <select name="category_id" class="w-full rounded border px-2 py-1"><?php
                $cats = ($tx['type'] ?? '') === 'income' ? $categoriesIncome : $categoriesExpense;
                foreach ($cats as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) ($tx['category_id'] ?? 0) ? 'selected' : '' ?>><?= Str::e((string) $c['name']) ?></option>
                <?php endforeach; ?></select>
            <?php endif; ?>
            <textarea name="notes" rows="2" class="w-full rounded border px-2 py-1 dark:border-slate-600 dark:bg-slate-950" placeholder="Notes"><?= Str::e((string) ($tx['notes'] ?? '')) ?></textarea>
            <input name="tags" value="<?= Str::e(implode(', ', $tags)) ?>" class="w-full rounded border px-2 py-1 text-xs dark:border-slate-600 dark:bg-slate-950" placeholder="tags, comma" />
            <?php if ($wallets !== []): ?>
                <button type="submit" class="w-full rounded-xl bg-teal-600 text-white py-2 font-semibold">Save changes</button>
            <?php else: ?>
                <p class="text-xs text-rose-600 text-center py-2">Unable to save without at least one wallet.</p>
            <?php endif; ?>
        </div>
    </form>
    <form method="post" action="<?= Str::e(Url::to('/app/transaction/' . (int) $tx['id'] . '/delete')) ?>" class="mt-3" onsubmit="return confirm('Delete this transaction?');">
        <?= Csrf::field() ?>
        <button type="submit" class="text-sm text-rose-600">Delete</button>
    </form>
</section>

<?php if (! empty($tx['is_consolidated_parent']) && ! $isTransfer): ?>
<section class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4 mb-4">
    <h2 class="text-sm font-semibold mb-2">Line items (children)</h2>
    <ul class="space-y-2 text-sm mb-3">
        <?php foreach ($children as $ch): ?>
            <li class="flex justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <span><?= Str::e((string) $ch['title']) ?></span>
                <span>RM <?= number_format((float) $ch['amount'], 2) ?></span>
            </li>
        <?php endforeach; ?>
        <?php if ($children === []): ?>
            <li class="text-slate-500 text-xs">No line items yet.</li>
        <?php endif; ?>
    </ul>
    <form method="post" action="<?= Str::e(Url::to('/app/transaction/' . (int) $tx['id'] . '/child')) ?>" class="space-y-2 border-t border-slate-100 pt-3">
        <?= Csrf::field() ?>
        <p class="text-xs text-slate-500">Add a child line (sum must stay within parent amount).</p>
        <input name="title" required placeholder="Title" class="w-full rounded border px-2 py-1 text-sm" />
        <input name="amount" type="number" step="0.01" required class="w-full rounded border px-2 py-1 text-sm" />
        <select name="type" class="w-full rounded border px-2 py-1 text-sm"><option value="expense">Expense</option><option value="income">Income</option></select>
        <?php if ($walletsActiveTx !== []): ?>
        <select name="wallet_id" required class="w-full rounded border px-2 py-1 text-sm"><?php foreach ($walletsActiveTx as $w): ?>
            <option value="<?= (int) $w['id'] ?>"><?= Str::e((string) $w['name']) ?></option>
        <?php endforeach; ?></select>
        <?php else: ?>
            <p class="text-xs text-slate-500">Add an active wallet to record child lines.</p>
        <?php endif; ?>
        <select name="category_id" class="w-full rounded border px-2 py-1 text-sm"><?php foreach ($categoriesExpense as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['name']) ?></option>
        <?php endforeach; ?></select>
        <input name="transaction_date" type="date" value="<?= Str::e((string) $tx['transaction_date']) ?>" class="w-full rounded border px-2 py-1 text-sm" />
        <?php if ($walletsActiveTx !== []): ?>
            <button type="submit" class="w-full rounded bg-slate-800 dark:bg-teal-700 text-white py-2 text-sm font-semibold">Add child</button>
        <?php endif; ?>
    </form>
</section>
<?php endif; ?>

<section class="rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
    <h2 class="text-sm font-semibold mb-2">Attachments</h2>
    <div class="grid grid-cols-2 gap-2 mb-3">
        <?php foreach ($attachments as $a): ?>
            <?php
            $pub = $base . '/uploads/transactions/' . str_replace('\\', '/', (string) $a['stored_filename']);
            $isImg = str_starts_with((string) $a['mime_type'], 'image/');
            $thumb = $isImg ? (dirname($pub) . '/thumb_' . basename((string) $a['stored_filename'])) : $pub;
            ?>
            <div class="rounded-lg border border-slate-200 overflow-hidden">
                <?php if ($isImg): ?><a href="<?= Str::e($pub) ?>" target="_blank" rel="noopener"><img src="<?= Str::e($thumb) ?>" alt="" class="w-full h-24 object-cover" loading="lazy" /></a><?php else: ?>
                    <a href="<?= Str::e($pub) ?>" class="block p-4 text-center text-xs text-teal-700" target="_blank">PDF</a>
                <?php endif; ?>
                <form method="post" action="<?= Str::e(Url::to('/app/transaction/' . (int) $tx['id'] . '/attach-delete')) ?>" class="p-1">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="attachment_id" value="<?= (int) $a['id'] ?>" />
                    <button type="submit" class="text-xs text-rose-600">Remove</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="post" action="<?= Str::e(Url::to('/app/transaction/' . (int) $tx['id'] . '/attach')) ?>" enctype="multipart/form-data" class="flex flex-col gap-2">
        <?= Csrf::field() ?>
        <input type="file" name="file" accept="image/jpeg,image/png,application/pdf" required class="text-xs" />
        <button type="submit" class="rounded-lg border border-teal-600 text-teal-700 py-1 text-sm">Upload</button>
    </form>
</section>
