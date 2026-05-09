<?php
declare(strict_types=1);

use App\Helpers\Str;

/** @var string $eyebrow Small uppercase label */
/** @var string $title */
/** @var string $description Already HTML-escaped snippet (may include entities from caller) */
/** @var string $icon Lucide icon name */
$icon = $icon ?? 'sliders-horizontal';
?>
            <div class="flex items-start gap-3 mb-5 pb-5 border-b border-slate-200/80 dark:border-slate-700/60">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center shrink-0 shadow-md shadow-teal-500/25 ring-2 ring-white/30 dark:ring-teal-400/20">
                    <i data-lucide="<?= Str::e($icon) ?>" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-teal-600 dark:text-teal-400 mb-1"><?= Str::e($eyebrow) ?></p>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight"><?= Str::e($title) ?></h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed"><?= $description ?></p>
                </div>
            </div>
