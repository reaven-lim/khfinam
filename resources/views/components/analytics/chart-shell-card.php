<?php
declare(strict_types=1);

use App\Helpers\Str;

/**
 * Lightweight chart wrapper (analytics shell / dashboard charts).
 *
 * @var string      $title
 * @var string      $subtitle
 * @var string|null $chartId DOM id for the chart anchor (renders empty div when set)
 * @var string      $cardClass Outer card classes (optional overrides)
 * @var string|null $badgeText Pill label on the right; omit when empty
 * @var string      $badgeClass Classes for pill
 * @var string      $chartContainerClass Classes on chart anchor
 * @var bool        $headerSimple When true, use stacked header (no flex / no badge row)
 */
$cardClass = $cardClass ?? 'rounded-2xl bg-white dark:bg-[#0d1424] border border-slate-300/85 dark:border-slate-800 p-5 shadow-[0_4px_22px_-6px_rgba(15,23,42,0.1)] dark:shadow-sm ring-1 ring-slate-900/[0.06] dark:ring-0';
$chartContainerClass = $chartContainerClass ?? 'mt-2';
$badgeClass = $badgeClass ?? 'text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-1 rounded-full uppercase tracking-wide ring-1 ring-slate-200/90 dark:ring-0';
$headerSimple = !empty($headerSimple);
?>
<div class="<?= Str::e($cardClass) ?>">
    <?php if ($headerSimple): ?>
    <div class="mb-1">
        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><?= Str::e($title) ?></h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5"><?= Str::e($subtitle) ?></p>
    </div>
    <?php else: ?>
    <div class="flex items-center justify-between mb-1">
        <div>
            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100"><?= Str::e($title) ?></h3>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5"><?= Str::e($subtitle) ?></p>
        </div>
        <?php if (!empty($badgeText)): ?>
        <span class="<?= Str::e($badgeClass) ?>"><?= Str::e((string) $badgeText) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($chartId)): ?>
    <div id="<?= Str::e((string) $chartId) ?>" class="<?= Str::e($chartContainerClass) ?>"></div>
    <?php endif; ?>
</div>
