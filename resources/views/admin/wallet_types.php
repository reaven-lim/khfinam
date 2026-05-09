<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<?php if ($message): ?>
    <div class="mb-4 rounded-xl border border-teal-200/80 bg-teal-50 dark:bg-teal-950/50 text-teal-900 dark:text-teal-200 text-sm px-4 py-3"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-xl border border-rose-200/80 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 text-sm px-4 py-3"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<section class="mb-8 rounded-3xl border border-slate-300/85 dark:border-slate-700/60 bg-gradient-to-br from-white via-slate-50 to-teal-50/40 dark:from-[#111827] dark:via-[#0f172a] dark:to-[#0d1424] p-6 shadow-[0_20px_50px_-24px_rgba(15,23,42,0.12)] dark:shadow-none">
    <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-400">Platform configuration</p>
    <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">Define account categories</h2>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400 max-w-2xl">Bank, cash, e-wallets and custom labels appear in user wallet forms and reporting. Slugs are stable identifiers; deactivate instead of deleting when types are referenced.</p>
</section>

<section class="rounded-3xl border border-slate-300/85 dark:border-slate-700/55 bg-white dark:bg-[#0d1424] p-6 mb-10 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.1)] dark:shadow-none">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">New custom type</h3>
    <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/store')) ?>" class="grid md:grid-cols-12 gap-3 items-end">
        <?= Csrf::field() ?>
        <div class="md:col-span-3">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Slug</label>
            <input name="slug" required pattern="[a-z0-9_]{1,64}" placeholder="brokerage_acct" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-3">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Label</label>
            <input name="label" required placeholder="Brokerage account" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Lucide icon</label>
            <input name="icon" value="wallet" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Sort</label>
            <input name="sort_order" type="number" value="50" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2 flex items-center gap-2">
            <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="is_active" value="1" checked /> Active
            </label>
            <button type="submit" class="ml-auto rounded-xl bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 text-sm font-bold shadow-sm">Add</button>
        </div>
    </form>
</section>

<div class="grid lg:grid-cols-2 gap-5">
    <?php foreach ($rows as $r):
        $isSys = ! empty($r['is_system']);
        $tid = (int) $r['id'];
        ?>
    <article class="rounded-2xl border border-slate-300/85 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] p-5 shadow-sm flex gap-4">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500/20 to-slate-200/40 dark:from-teal-500/25 dark:to-slate-800 flex items-center justify-center shrink-0 ring-1 ring-teal-500/20">
            <i data-lucide="<?= Str::e((string) ($r['icon'] ?: 'wallet')) ?>" class="w-6 h-6 text-teal-700 dark:text-teal-300"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <h4 class="text-base font-bold text-slate-900 dark:text-white"><?= Str::e((string) $r['label']) ?></h4>
                <span class="text-[10px] font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-md text-slate-600 dark:text-slate-300"><?= Str::e((string) $r['slug']) ?></span>
                <?php if ($isSys): ?>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-violet-700 dark:text-violet-300 bg-violet-100 dark:bg-violet-950/50 px-2 py-0.5 rounded-full">Core</span>
                <?php endif; ?>
                <?php if (empty($r['is_active'])): ?>
                    <span class="text-[10px] font-bold uppercase text-slate-500 bg-slate-200 dark:bg-slate-700 px-2 py-0.5 rounded-full">Inactive</span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/update')) ?>" class="space-y-3">
                <?= Csrf::field() ?>
                <input type="hidden" name="type_id" value="<?= $tid ?>" />
                <div class="grid sm:grid-cols-2 gap-2">
                    <div>
                        <label class="text-[10px] font-bold uppercase text-slate-500">Label</label>
                        <input name="label" required value="<?= Str::e((string) $r['label']) ?>" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-2 py-1.5 text-sm mt-0.5" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-slate-500">Icon</label>
                        <input name="icon" value="<?= Str::e((string) $r['icon']) ?>" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-2 py-1.5 text-sm mt-0.5" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-slate-500">Sort</label>
                        <input name="sort_order" type="number" value="<?= (int) $r['sort_order'] ?>" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-2 py-1.5 text-sm mt-0.5" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="is_active" value="1" <?= ! empty($r['is_active']) ? 'checked' : '' ?> /> Active
                        </label>
                    </div>
                </div>
                <button type="submit" class="rounded-lg bg-slate-800 dark:bg-teal-700 text-white text-xs font-bold px-3 py-1.5">Save changes</button>
            </form>
            <?php if (! $isSys): ?>
                <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/delete')) ?>" class="mt-3" onsubmit="return confirm('Delete this wallet type permanently? Zero wallets must use it.');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type_id" value="<?= $tid ?>" />
                    <button type="submit" class="rounded-lg border border-rose-300 dark:border-rose-700 text-rose-700 dark:text-rose-300 text-xs font-bold px-3 py-1.5">Delete custom type</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
