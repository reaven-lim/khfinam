<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rowsFrag = $rowsFrag ?? [];

foreach ($rowsFrag as $r):
    $tid = (int) $r['id'];
    $isSys = ! empty($r['is_system']);
    $active = ! empty($r['is_active']);
    $wc = (int) ($r['wallet_count'] ?? 0);
    $rowShell = $isSys
        ? 'bg-gradient-to-r from-indigo-50/50 via-transparent to-transparent dark:from-indigo-950/20 dark:via-transparent'
        : '';
    ?>
<tr class="<?= Str::e($rowShell) ?> hover:bg-slate-50/80 dark:hover:bg-slate-800/35 transition-colors">
    <td class="px-4 py-3">
        <div class="flex items-center gap-3">
            <span class="<?= $isSys ? 'ring-2 ring-indigo-400/50 dark:ring-indigo-500/35' : 'ring-1 ring-slate-200 dark:ring-slate-700' ?> w-11 h-11 rounded-xl bg-gradient-to-br <?= $isSys ? 'from-indigo-500/25 to-violet-600/35' : 'from-slate-200/80 to-slate-300/40 dark:from-slate-800 dark:to-slate-700' ?> flex items-center justify-center shrink-0">
                <i data-lucide="<?= Str::e((string) ($r['icon'] ?: 'wallet')) ?>" class="w-5 h-5 <?= $isSys ? 'text-indigo-700 dark:text-indigo-300' : 'text-slate-700 dark:text-slate-200' ?>"></i>
            </span>
            <div class="min-w-0">
                <p class="font-bold text-slate-900 dark:text-white truncate max-w-[200px]"><?= Str::e((string) ($r['label'] ?? '')) ?></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Internal ID · <span class="font-mono"><?= Str::e((string) ($r['slug'] ?? '')) ?></span></p>
            </div>
        </div>
    </td>
    <td class="px-4 py-3 whitespace-nowrap">
        <?php if ($isSys): ?>
            <span class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-950/65 text-indigo-900 dark:text-indigo-200 px-2.5 py-0.5 text-[10px] font-bold uppercase ring-1 ring-indigo-300/60 dark:ring-indigo-800/50">Built-in</span>
        <?php else: ?>
            <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-0.5 text-[10px] font-bold uppercase ring-1 ring-slate-300/60 dark:ring-slate-600/50">Custom</span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3 text-right tabular-nums font-bold <?= $wc > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400' ?>"><?= number_format($wc) ?></td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300"><?= number_format((int) ($r['users_count'] ?? 0)) ?></td>
    <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-800 dark:text-slate-100"><?= number_format((float) ($r['balance_base_total'] ?? 0), 0) ?></td>
    <td class="px-4 py-3 text-right tabular-nums text-teal-700 dark:text-teal-300 font-semibold"><?= number_format((int) ($r['analytics_wallet_count'] ?? 0)) ?></td>
    <td class="px-4 py-3 text-right tabular-nums text-slate-600 dark:text-slate-400"><?= (int) ($r['sort_order'] ?? 0) ?></td>
    <td class="px-4 py-3">
        <?php if ($active): ?>
            <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 uppercase">Active</span>
        <?php else: ?>
            <span class="text-[10px] font-bold text-slate-500 uppercase">Disabled</span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3 text-xs text-slate-500 tabular-nums whitespace-nowrap"><?= Str::e(substr((string) ($r['updated_at'] ?? ''), 0, 16)) ?></td>
    <td class="px-4 py-3 text-right whitespace-nowrap">
        <div class="inline-flex flex-wrap gap-1 justify-end">
            <a href="<?= Str::e(Url::to('/admin/wallet-types/' . $tid)) ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:border-indigo-400/70" title="View details"><i data-lucide="external-link" class="w-4 h-4"></i></a>
            <?php if ($active): ?>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/status')) ?>" class="inline" onsubmit="return confirm('Turn off this wallet type for new wallets?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="type_id" value="<?= $tid ?>" />
                <input type="hidden" name="is_active" value="0" />
                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-amber-200 dark:border-amber-900/55 bg-amber-50 dark:bg-amber-950/30 text-amber-900 dark:text-amber-100" title="Deactivate"><i data-lucide="pause-circle" class="w-4 h-4"></i></button>
            </form>
            <?php else: ?>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/status')) ?>" class="inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="type_id" value="<?= $tid ?>" />
                <input type="hidden" name="is_active" value="1" />
                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-emerald-200 dark:border-emerald-900/55 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-100" title="Activate"><i data-lucide="play-circle" class="w-4 h-4"></i></button>
            </form>
            <?php endif; ?>
            <?php if (! $isSys && $wc === 0): ?>
            <form method="post" action="<?= Str::e(Url::to('/admin/wallet-types/delete')) ?>" class="inline" onsubmit="return confirm('Permanently remove this unused custom type?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="type_id" value="<?= $tid ?>" />
                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-rose-200 dark:border-rose-900/55 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300" title="Delete"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; ?>
