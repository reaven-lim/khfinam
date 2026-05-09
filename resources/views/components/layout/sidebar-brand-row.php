<?php
declare(strict_types=1);

use App\Helpers\Str;

/** @var string $appName */
/** @var string $brandSubtitle */
/** @var string $logoIcon Lucide icon name */
/** @var string $sbPrefix dash-sb | adm-sb */
/** @var string $brandRowClass Tailwind classes for the row */
/** @var string $brandTitleAttr title attribute */
/** @var string $toggleBtnId */
/** @var string $toggleIconId */
/** @var string $toggleOnclick JS function name */
?>
    <div class="<?= Str::e($brandRowClass) ?>" title="<?= Str::e($brandTitleAttr) ?>">
        <div class="flex items-center gap-2 min-w-0 flex-1 xl:flex-initial xl:gap-3">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-teal-400 to-teal-700 flex items-center justify-center shadow-lg shadow-teal-500/30 shrink-0">
                <i data-lucide="<?= Str::e($logoIcon) ?>" class="w-4 h-4 text-white"></i>
            </div>
            <div class="<?= Str::e($sbPrefix) ?>-brand min-w-0 flex-1 overflow-hidden text-left">
                <p class="font-bold text-sm text-slate-900 dark:text-white truncate"><?= Str::e($appName) ?></p>
                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-[0.12em]"><?= Str::e($brandSubtitle) ?></p>
            </div>
        </div>
        <button type="button" id="<?= Str::e($toggleBtnId) ?>" onclick="<?= Str::e($toggleOnclick) ?>()"
            class="hidden md:flex xl:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 shrink-0"
            title="Expand sidebar">
            <i data-lucide="chevrons-right" id="<?= Str::e($toggleIconId) ?>" class="w-4 h-4"></i>
        </button>
    </div>
