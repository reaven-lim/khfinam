<?php
declare(strict_types=1);

use App\Helpers\Str;

/** @var string $icon Lucide icon name */
/** @var string $title */
/** @var string|null $subtitle */
?>
<div class="flex flex-col items-center justify-center py-10 text-slate-500 dark:text-slate-400">
    <i data-lucide="<?= Str::e($icon) ?>" class="w-8 h-8 mb-2 opacity-50 dark:opacity-40"></i>
    <p class="text-sm font-medium text-slate-600 dark:text-slate-300"><?= Str::e($title) ?></p>
    <?php if (!empty($subtitle)): ?>
    <p class="text-xs mt-1 text-slate-600 dark:text-slate-500"><?= Str::e((string) $subtitle) ?></p>
    <?php endif; ?>
</div>
