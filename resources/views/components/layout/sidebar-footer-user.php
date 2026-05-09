<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

/** @var string $sbPrefix dash-sb | adm-sb */
/** @var string $displayName */
/** @var string $initials */
/** @var string $badgeLine Plain text line under the name (escaped here) */
/** @var string $footerBadgeClass Tailwind classes for the badge line */
/** @var string $logoutButtonTitle */
$logoutButtonTitle = $logoutButtonTitle ?? 'Log out';
?>
    <div class="px-2 xl:px-3 py-2 xl:py-3 border-t border-slate-200/95 dark:border-slate-800 shrink-0 space-y-1 mt-auto">
        <button onclick="toggleDark()" class="w-full flex items-center justify-center xl:justify-start gap-3 px-2 xl:px-3 py-2 rounded-lg text-sm text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors" title="Theme">
            <i data-lucide="sun" class="w-4 h-4 shrink-0 dark:hidden"></i>
            <i data-lucide="moon" class="w-4 h-4 shrink-0 hidden dark:inline-block"></i>
            <span class="<?= Str::e($sbPrefix) ?>-text dark:hidden text-sm">Light mode</span>
            <span class="<?= Str::e($sbPrefix) ?>-text hidden dark:inline text-sm">Dark mode</span>
        </button>
        <div class="flex flex-col xl:flex-row items-center xl:items-start gap-2 xl:gap-3 px-1 xl:px-3 py-2">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-white text-xs font-bold shrink-0 border-2 border-white/10" title="<?= Str::e($displayName) ?>">
                <?= Str::e($initials) ?>
            </div>
            <div class="<?= Str::e($sbPrefix) ?>-user-meta flex-col min-w-0 overflow-hidden text-center xl:text-left">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate"><?= Str::e($displayName) ?></p>
                <p class="<?= Str::e($footerBadgeClass) ?>"><?= Str::e($badgeLine) ?></p>
            </div>
            <form action="<?= Str::e(Url::to('/logout')) ?>" method="post">
                <?= Csrf::field() ?>
                <button type="submit" title="<?= Str::e($logoutButtonTitle) ?>" class="p-1.5 xl:p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </div>
