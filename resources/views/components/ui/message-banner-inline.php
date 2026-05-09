<?php
declare(strict_types=1);

use App\Helpers\Str;

/**
 * @var string $tone success|warning
 * @var string $message Plain text (escaped)
 * @var string $icon Lucide icon name
 */
$tone = $tone === 'warning' ? 'warning' : 'success';
$wrap = $tone === 'success'
    ? 'mb-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-200 text-sm px-4 py-3 flex items-center gap-2'
    : 'mb-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200 text-sm px-4 py-3 flex items-center gap-2';
$iconTone = $tone === 'success' ? 'text-emerald-500' : 'text-amber-500';
?>
<div class="<?= $wrap ?>">
    <i data-lucide="<?= Str::e($icon) ?>" class="w-4 h-4 shrink-0 <?= Str::e($iconTone) ?>"></i>
    <?= Str::e($message) ?>
</div>
