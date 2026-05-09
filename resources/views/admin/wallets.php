<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

/** @var array<int,array<string,mixed>> $rows */
/** @var array<int,array<string,mixed>> $users */
/** @var array<int,array<string,mixed>> $walletTypes */
/** @var array<int,array<string,mixed>> $currencies */
$rows = $rows ?? [];
$users = $users ?? [];
$walletTypes = $walletTypes ?? [];
$currencies = $currencies ?? [];
$filterUserId = (int) ($filterUserId ?? 0);
$message = $message ?? null;
$error = $error ?? null;

$nActive = count(array_filter($rows, static fn (array $w): bool => ! empty($w['is_active'])));
$nUsers = count(array_unique(array_map(static fn (array $w): int => (int) $w['user_id'], $rows)));
?>
<?php if ($message): ?>
    <div class="mb-4 rounded-xl border border-teal-200/80 bg-teal-50 dark:bg-teal-950/50 text-teal-900 dark:text-teal-200 text-sm px-4 py-3"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-xl border border-rose-200/80 bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-200 text-sm px-4 py-3"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<section class="mb-6 grid sm:grid-cols-3 gap-4">
    <div class="rounded-2xl border border-slate-300/85 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Wallets</p>
        <p class="text-3xl font-extrabold tabular-nums text-slate-900 dark:text-white mt-1"><?= count($rows) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-300/85 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Active</p>
        <p class="text-3xl font-extrabold tabular-nums text-emerald-700 dark:text-emerald-400 mt-1"><?= $nActive ?></p>
    </div>
    <div class="rounded-2xl border border-slate-300/85 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] p-5 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Users with wallets</p>
        <p class="text-3xl font-extrabold tabular-nums text-teal-700 dark:text-teal-400 mt-1"><?= $nUsers ?></p>
    </div>
</section>

<section class="mb-8 rounded-3xl border border-slate-300/85 dark:border-slate-700/55 bg-gradient-to-br from-white via-slate-50 to-teal-50/30 dark:from-[#111827] dark:via-[#0f172a] dark:to-[#0d1424] p-6 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-end gap-6">
        <form method="get" action="<?= Str::e(Url::to('/admin/wallets')) ?>" class="flex flex-wrap items-end gap-3 flex-1">
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Filter owner</label>
                <select name="user_id" class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm min-w-[200px]" onchange="this.form.submit()">
                    <option value="0">All users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $filterUserId === (int) $u['id'] ? 'selected' : '' ?>><?= Str::e((string) $u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <a href="<?= Str::e(Url::to('/admin/wallet-types')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 dark:border-slate-600 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-800">Manage types →</a>
    </div>
</section>

<section class="rounded-3xl border border-slate-300/85 dark:border-slate-700/55 bg-white dark:bg-[#0d1424] p-6 mb-10 shadow-[0_8px_30px_-12px_rgba(15,23,42,0.1)]">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Provision wallet</h3>
    <form method="post" action="<?= Str::e(Url::to('/admin/wallets/store')) ?>" class="grid md:grid-cols-12 gap-3">
        <?= Csrf::field() ?>
        <div class="md:col-span-3">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Owner</label>
            <select name="user_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-950 px-3 py-2 text-sm"><?php foreach ($users as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= Str::e((string) $u['username']) ?> · <?= Str::e((string) $u['email']) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Name</label>
            <input name="name" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm" placeholder="e.g. GrabPay" />
        </div>
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Type</label>
            <select name="wallet_type_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($walletTypes as $wt): if (empty($wt['is_active'])) {
                continue;
            } ?>
                <option value="<?= (int) $wt['id'] ?>"><?= Str::e((string) $wt['label']) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Currency</label>
            <select name="currency_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm"><?php foreach ($currencies as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= Str::e((string) $c['code']) ?></option>
            <?php endforeach; ?></select>
        </div>
        <div class="md:col-span-1">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Opening</label>
            <input name="opening_balance" type="number" step="0.01" value="0" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-2 py-2 text-sm" />
        </div>
        <div class="md:col-span-1">
            <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Min alert</label>
            <input name="min_balance_threshold" type="number" step="0.01" placeholder="—" class="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-2 py-2 text-sm" />
        </div>
        <div class="md:col-span-1 flex flex-col justify-end gap-2">
            <label class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="is_active" value="1" checked /> Active
            </label>
            <label class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-400">
                <input type="checkbox" name="is_default" value="1" /> Default
            </label>
            <button type="submit" class="rounded-xl bg-teal-600 text-white text-xs font-bold py-2 px-3 shadow-sm">Create</button>
        </div>
    </form>
</section>

<div class="grid xl:grid-cols-2 gap-5 mb-16">
<?php foreach ($rows as $w):
    $wid = (int) $w['id'];
    $uid = (int) $w['user_id'];
    ?>
    <article class="rounded-2xl border border-slate-300/85 dark:border-slate-700/60 bg-white dark:bg-[#0d1424] overflow-hidden shadow-sm flex flex-col">
        <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-wrap items-start justify-between gap-3 bg-slate-50/80 dark:bg-slate-900/40">
            <div>
                <p class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400"><?= Str::e((string) ($w['username'] ?? '')) ?></p>
                <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?= Str::e((string) $w['name']) ?></h4>
                <p class="text-xs text-slate-500 dark:text-slate-400"><?= Str::e((string) ($w['email'] ?? '')) ?></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 dark:bg-teal-950/50 text-teal-800 dark:text-teal-200 text-[11px] font-bold px-2.5 py-1 ring-1 ring-teal-200/70 dark:ring-teal-800/50">
                    <i data-lucide="<?= Str::e((string) ($w['type_icon'] ?? 'wallet')) ?>" class="w-3 h-3"></i>
                    <?= Str::e((string) ($w['type_label'] ?? '')) ?>
                </span>
                <span class="text-[11px] font-bold uppercase tracking-wide px-2.5 py-1 rounded-full <?= ! empty($w['is_active']) ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' ?>">
                    <?= ! empty($w['is_active']) ? 'Active' : 'Inactive' ?></span>
                <?php if (! empty($w['is_default'])): ?>
                    <span class="text-[11px] font-bold uppercase px-2.5 py-1 rounded-full bg-violet-100 dark:bg-violet-950/60 text-violet-800 dark:text-violet-200">Default</span>
                <?php endif; ?>
            </div>
        </header>
        <div class="p-5 space-y-3 flex-1">
            <dl class="grid grid-cols-3 gap-2 text-xs">
                <div>
                    <dt class="text-slate-500 font-semibold uppercase text-[10px]">Currency</dt>
                    <dd class="font-bold text-slate-900 dark:text-slate-100"><?= Str::e((string) ($w['currency_code'] ?? '')) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-semibold uppercase text-[10px]">Opening</dt>
                    <dd class="font-mono tabular-nums"><?= Str::e(number_format((float) ($w['opening_balance'] ?? 0), 2)) ?></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-semibold uppercase text-[10px]">Min thresh.</dt>
                    <dd class="font-mono tabular-nums"><?= isset($w['min_balance_threshold']) && $w['min_balance_threshold'] !== null && $w['min_balance_threshold'] !== '' ? Str::e(number_format((float) $w['min_balance_threshold'], 2)) : '—' ?></dd>
                </div>
            </dl>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/update')) ?>" class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                <input type="hidden" name="wallet_user_id" value="<?= $uid ?>" />
                <div class="grid sm:grid-cols-2 gap-2">
                    <input name="name" required value="<?= Str::e((string) $w['name']) ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" />
                    <select name="wallet_type_id" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm"><?php foreach ($walletTypes as $wt): ?>
                        <option value="<?= (int) $wt['id'] ?>" <?= (int) $w['wallet_type_id'] === (int) $wt['id'] ? 'selected' : '' ?>><?= Str::e((string) $wt['label']) ?><?= empty($wt['is_active']) ? ' (inactive)' : '' ?></option>
                    <?php endforeach; ?></select>
                    <select name="currency_id" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm"><?php foreach ($currencies as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $w['currency_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= Str::e((string) $c['code']) ?></option>
                    <?php endforeach; ?></select>
                    <input name="opening_balance" type="number" step="0.01" value="<?= Str::e((string) $w['opening_balance']) ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" />
                </div>
                <div class="grid sm:grid-cols-2 gap-2">
                    <input name="min_balance_threshold" type="number" step="0.01" value="<?= $w['min_balance_threshold'] !== null ? Str::e((string) $w['min_balance_threshold']) : '' ?>" placeholder="Min threshold" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" />
                    <input name="sort_order" type="number" value="<?= (int) $w['sort_order'] ?>" class="rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" />
                </div>
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 dark:border-slate-600 px-2 py-1.5 text-sm" placeholder="Notes"><?= Str::e((string) ($w['notes'] ?? '')) ?></textarea>
                <div class="flex flex-wrap gap-3 items-center">
                    <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="checkbox" name="is_active" value="1" <?= ! empty($w['is_active']) ? 'checked' : '' ?> /> Active
                    </label>
                    <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <input type="checkbox" name="is_default" value="1" <?= ! empty($w['is_default']) ? 'checked' : '' ?> /> Default wallet
                    </label>
                    <button type="submit" class="ml-auto rounded-lg bg-slate-900 dark:bg-teal-700 text-white text-xs font-bold px-4 py-2">Save</button>
                </div>
            </form>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallets/delete')) ?>" class="pt-2" onsubmit="return confirm('Permanently delete this wallet? Only allowed when empty (no transactions, no recurring rules).');">
                <?= Csrf::field() ?>
                <input type="hidden" name="wallet_id" value="<?= $wid ?>" />
                <input type="hidden" name="wallet_user_id" value="<?= $uid ?>" />
                <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 underline">Delete empty wallet</button>
            </form>
        </div>
    </article>
<?php endforeach; ?>
</div>

<?php if ($rows === []): ?>
    <p class="text-center py-16 text-slate-500 dark:text-slate-400 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40">No wallets match this filter · provision one above.</p>
<?php endif; ?>
