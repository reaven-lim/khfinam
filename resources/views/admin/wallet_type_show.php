<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;
use App\Helpers\View;

$typeRow = $typeRow ?? [];
$stats = $stats ?? ['wallet_count' => 0, 'users_count' => 0, 'balance_base_total' => 0.0, 'analytics_wallet_count' => 0];
$recentWallets = $recentWallets ?? [];
$baseCurrency = $baseCurrency ?? 'MYR';
$message = $message ?? null;
$error = $error ?? null;

$tid = (int) ($typeRow['id'] ?? 0);
$slug = (string) ($typeRow['slug'] ?? '');
$label = (string) ($typeRow['label'] ?? '');
$icon = (string) ($typeRow['icon'] ?? 'wallet');
$sortOrder = (int) ($typeRow['sort_order'] ?? 0);
$active = ! empty($typeRow['is_active']);
$isSys = ! empty($typeRow['is_system']);
$wc = (int) ($stats['wallet_count'] ?? 0);
$uc = (int) ($stats['users_count'] ?? 0);
$bbt = (float) ($stats['balance_base_total'] ?? 0);
$awc = (int) ($stats['analytics_wallet_count'] ?? 0);

$manageShell = 'rounded-2xl border-2 border-indigo-500/30 dark:border-indigo-600/25 bg-gradient-to-br from-white via-indigo-50/30 to-white dark:from-[#0d1424] dark:via-indigo-950/20 dark:to-[#0c1426] p-4 sm:p-6 lg:p-7 shadow-xl ring-1 ring-slate-900/[0.04] dark:ring-white/[0.06]';
$innerCard = 'rounded-xl border border-slate-200/95 dark:border-slate-700/70 bg-white/95 dark:bg-slate-900/50 p-4 sm:p-5';
$headerCardSys = 'rounded-2xl border-2 border-indigo-400/40 dark:border-indigo-500/30 bg-gradient-to-br from-indigo-50/95 via-white to-violet-50/50 dark:from-indigo-950/40 dark:via-[#0d1424] dark:to-violet-950/25 px-5 py-5 sm:px-6 shadow-lg ring-1 ring-indigo-900/[0.06] dark:ring-indigo-400/10';
$headerCardCustom = 'rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-white dark:bg-[#0d1424] px-5 py-5 sm:px-6 shadow-lg ring-1 ring-slate-900/[0.05] dark:ring-white/[0.05]';
?>

<?php if ($message): ?>
<div class="mb-4 rounded-xl border border-emerald-300/70 bg-emerald-50/90 dark:bg-emerald-950/40 px-4 py-3 text-sm font-semibold text-emerald-900 dark:text-emerald-200"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 rounded-xl border border-rose-300/70 bg-rose-50/90 dark:bg-rose-950/35 px-4 py-3 text-sm font-semibold text-rose-900 dark:text-rose-200"><?= Str::e((string) $error) ?></div>
<?php endif; ?>

<div class="mb-5 flex flex-wrap items-center gap-3 text-sm">
    <a href="<?= Str::e(Url::to('/admin/wallet-types')) ?>" class="inline-flex items-center gap-1.5 font-bold text-indigo-700 dark:text-indigo-300 hover:underline">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Wallet types
    </a>
    <span class="text-slate-300 dark:text-slate-600">/</span>
    <span class="font-semibold text-slate-800 dark:text-slate-200"><?= Str::e($label) ?></span>
</div>

<section class="mb-7 <?= $isSys ? $headerCardSys : $headerCardCustom ?>">
    <div class="flex flex-col md:flex-row md:items-start gap-5">
        <div class="w-16 h-16 rounded-2xl <?= $isSys ? 'bg-gradient-to-br from-indigo-600 to-violet-700 ring-2 ring-indigo-300/50 dark:ring-indigo-500/35' : 'bg-gradient-to-br from-slate-200 to-slate-400 dark:from-slate-700 dark:to-slate-900 ring-1 ring-slate-300 dark:ring-slate-600' ?> flex items-center justify-center text-white shrink-0 shadow-md">
            <i data-lucide="<?= Str::e($icon) ?>" class="w-8 h-8"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white"><?= Str::e($label) ?></h1>
                <?php if ($isSys): ?>
                    <span class="inline-flex rounded-full bg-indigo-600 text-white px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide ring-2 ring-indigo-300/35">Built-in type</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">Custom wallet type</span>
                <?php endif; ?>
                <?php if ($active): ?>
                    <span class="inline-flex rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Active</span>
                <?php else: ?>
                    <span class="inline-flex rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 text-[10px] font-bold uppercase">Inactive</span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-[11px] text-slate-600 dark:text-slate-400">
                Internal ID · <span class="font-mono"><?= Str::e($slug) ?></span>
                <span class="block text-xs text-slate-500 dark:text-slate-500 mt-1 font-sans">Used internally by the system. It does not change after the type is created.</span>
            </p>
            <?php if ($isSys): ?>
            <p class="text-xs text-indigo-800/90 dark:text-indigo-300/90 mt-3 leading-relaxed rounded-lg bg-indigo-50/90 dark:bg-indigo-950/40 px-3 py-2 border border-indigo-200/70 dark:border-indigo-900/50">
                This is a <strong>built-in</strong> KHFinaM type — it stays in the catalogue for consistency and cannot be deleted. You can still adjust the visible name, icon, order, and whether new wallets may use it.
            </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="mb-8 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-white dark:bg-[#0d1424] p-4 ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05]">
        <p class="text-[10px] font-bold uppercase text-slate-500 tracking-wide">Wallets</p>
        <p class="text-2xl font-black tabular-nums text-slate-900 dark:text-white mt-1"><?= number_format($wc) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-white dark:bg-[#0d1424] p-4 ring-1 ring-slate-900/[0.04] dark:ring-white/[0.05]">
        <p class="text-[10px] font-bold uppercase text-slate-500 tracking-wide">Users affected</p>
        <p class="text-2xl font-black tabular-nums text-slate-900 dark:text-white mt-1"><?= number_format($uc) ?></p>
    </div>
    <div class="rounded-2xl border border-teal-200/80 dark:border-teal-900/50 bg-teal-50/60 dark:bg-teal-950/25 p-4 ring-1 ring-teal-900/[0.04] dark:ring-teal-500/15">
        <p class="text-[10px] font-bold uppercase text-teal-800 dark:text-teal-300 tracking-wide">Balance (<?= Str::e($baseCurrency) ?>)</p>
        <p class="text-2xl font-black tabular-nums text-teal-950 dark:text-teal-100 mt-1"><?= number_format($bbt, 0) ?></p>
    </div>
    <div class="rounded-2xl border border-violet-200/80 dark:border-violet-900/50 bg-violet-50/60 dark:bg-violet-950/25 p-4 ring-1 ring-violet-900/[0.04] dark:ring-violet-500/15">
        <p class="text-[10px] font-bold uppercase text-violet-800 dark:text-violet-300 tracking-wide">Analytics cohort</p>
        <p class="text-2xl font-black tabular-nums text-violet-950 dark:text-violet-100 mt-1"><?= number_format($awc) ?></p>
        <p class="text-[10px] text-violet-700 dark:text-violet-400 mt-1">Wallets belonging to users who opted into analytics</p>
    </div>
</section>

<section class="mb-8 rounded-2xl border border-indigo-200/75 dark:border-indigo-900/40 bg-gradient-to-br from-indigo-50/80 to-white dark:from-indigo-950/30 dark:to-[#0d1424] p-4 sm:p-5 ring-1 ring-indigo-900/[0.04] dark:ring-indigo-400/10">
    <div class="flex gap-3">
        <div class="hidden sm:flex w-10 h-10 shrink-0 rounded-xl bg-indigo-600 text-white items-center justify-center shadow-md"><i data-lucide="bar-chart-2" class="w-5 h-5"></i></div>
        <div class="min-w-0">
            <h2 class="text-sm font-black text-indigo-950 dark:text-indigo-100">Reporting & dashboards</h2>
            <p class="text-xs text-indigo-900/85 dark:text-indigo-300/90 mt-1 leading-relaxed">
                Wallet types group accounts in charts and summaries using the visible name (<strong><?= Str::e($label) ?></strong>) while the stable internal ID keeps history aligned.
                If you deactivate a type, existing wallets stay on this category for history, but members cannot pick it for brand-new wallets.
            </p>
        </div>
    </div>
</section>

<section class="mb-8 <?= Str::e($manageShell) ?>">
    <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400 mb-3">Maintenance</p>
    <div class="grid lg:grid-cols-12 gap-5">
        <div class="lg:col-span-7 space-y-4">
            <div class="<?= Str::e($innerCard) ?>">
                <h2 class="text-sm font-black text-slate-900 dark:text-white mb-1">What people see</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Change the readable name, icon, list order, and whether this type appears when someone adds a wallet. The internal ID above never changes.</p>
                <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/update')) ?>" id="wtDetailEditForm" class="space-y-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type_id" value="<?= $tid ?>" />
                    <input type="hidden" name="_redirect" value="detail" />
                    <div>
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Wallet type name</label>
                        <input name="label" required value="<?= Str::e($label) ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm" />
                    </div>
                    <div class="flex gap-3 items-start">
                        <div class="shrink-0 w-11 h-11 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 flex items-center justify-center">
                            <i data-lucide="<?= Str::e($icon) ?>" class="w-5 h-5 text-slate-700 dark:text-slate-200" id="wt_detail_icon_preview"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="text-[11px] font-bold text-slate-500 uppercase">Icon</label>
                            <?php View::partial('components/admin/wallet-type-icon-select', [
                                'selectId' => 'detail_wt_icon',
                                'selected' => $icon,
                                'extrasIcon' => $icon,
                                'required' => true,
                            ]); ?>
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Sort order</label>
                        <input name="sort_order" type="number" value="<?= $sortOrder ?>" class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 text-sm tabular-nums" />
                    </div>
                    <input type="hidden" name="is_active" value="0" />
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="is_active" value="1" <?= $active ? 'checked' : '' ?> class="rounded border-slate-300" /> Active — appears when choosing a type for new wallets
                    </label>
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white px-5 py-2.5 text-sm font-bold shadow-md">Save changes</button>
                </form>
            </div>
        </div>
        <div class="lg:col-span-5 space-y-4">
            <div class="<?= Str::e($innerCard) ?> border-amber-200/70 dark:border-amber-900/45 bg-amber-50/50 dark:bg-amber-950/20">
                <h2 class="text-sm font-black text-slate-900 dark:text-white mb-2">Lifecycle controls</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400 mb-4"><?= $isSys ? 'Built-in types cannot be removed. Deactivating simply hides this label from new wallet setup while keeping old data consistent.' : 'Deactivate to stop new use while keeping history. You can delete a custom type only after no wallet uses it anymore.' ?></p>
                <?php if ($active): ?>
                <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/status')) ?>" class="mb-3" onsubmit="return confirm('Disable this type?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type_id" value="<?= $tid ?>" />
                    <input type="hidden" name="is_active" value="0" />
                    <input type="hidden" name="_redirect" value="detail" />
                    <button type="submit" class="w-full rounded-xl border border-amber-400/80 dark:border-amber-800/55 bg-white dark:bg-slate-900 px-4 py-2.5 text-xs font-bold text-amber-950 dark:text-amber-200">Deactivate for new wallets</button>
                </form>
                <?php else: ?>
                <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/status')) ?>" class="mb-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type_id" value="<?= $tid ?>" />
                    <input type="hidden" name="is_active" value="1" />
                    <input type="hidden" name="_redirect" value="detail" />
                    <button type="submit" class="w-full rounded-xl border border-emerald-400/80 dark:border-emerald-800/55 bg-emerald-50 dark:bg-emerald-950/30 px-4 py-2.5 text-xs font-bold text-emerald-900 dark:text-emerald-100">Activate for new wallets</button>
                </form>
                <?php endif; ?>
                <?php if (! $isSys && $wc === 0): ?>
                <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/delete')) ?>" onsubmit="return confirm('Permanently delete this unused custom type?');">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="type_id" value="<?= $tid ?>" />
                    <button type="submit" class="w-full rounded-xl border border-rose-300 dark:border-rose-800/55 bg-rose-50 dark:bg-rose-950/30 px-4 py-2.5 text-xs font-bold text-rose-900 dark:text-rose-100">Delete type (unused)</button>
                </form>
                <?php elseif (! $isSys): ?>
                <p class="text-xs text-slate-500 italic">Delete unlocks automatically when wallet count reaches zero.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="mb-4">
    <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-400 mb-2">Usage context</p>
    <h2 class="text-lg font-black text-slate-900 dark:text-white mb-4">Recently updated wallets using this type</h2>
    <?php if ($recentWallets === []): ?>
        <p class="text-sm text-slate-500 dark:text-slate-400 py-8 text-center rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/30">No wallets use this type yet.</p>
    <?php else: ?>
    <div class="rounded-2xl border border-slate-200/95 dark:border-slate-700/70 bg-white dark:bg-[#0d1424] overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/80 text-[10px] font-extrabold uppercase text-slate-500 tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Wallet</th>
                    <th class="px-4 py-3 text-left">Owner</th>
                    <th class="px-4 py-3 text-left">CCY</th>
                    <th class="px-4 py-3 text-left">Updated</th>
                    <th class="px-4 py-3 text-right">Open</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <?php foreach ($recentWallets as $rw):
                    $rwid = (int) ($rw['id'] ?? 0);
                    ?>
                <tr class="hover:bg-slate-50/90 dark:hover:bg-slate-800/40">
                    <td class="px-4 py-2.5 font-bold text-slate-900 dark:text-white"><?= Str::e((string) ($rw['name'] ?? '')) ?></td>
                    <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300"><?= Str::e((string) ($rw['username'] ?? '')) ?></td>
                    <td class="px-4 py-2.5"><?= Str::e((string) ($rw['currency_code'] ?? '')) ?></td>
                    <td class="px-4 py-2.5 text-xs tabular-nums text-slate-500"><?= Str::e(substr((string) ($rw['updated_at'] ?? ''), 0, 16)) ?></td>
                    <td class="px-4 py-2.5 text-right">
                        <a href="<?= Str::e(Url::to('/admin/wallets/' . $rwid)) ?>" class="text-xs font-bold text-teal-700 dark:text-teal-300 hover:underline">Wallet detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<script>
(function () {
    var sel = document.getElementById('detail_wt_icon');
    var ic = document.getElementById('wt_detail_icon_preview');
    if (!sel || !ic) return;
    sel.addEventListener('change', function () {
        ic.setAttribute('data-lucide', sel.value || 'wallet');
        if (window.lucide) lucide.createIcons();
    });
})();
</script>
