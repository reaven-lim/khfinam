<?php
declare(strict_types=1);

use App\Helpers\Str;

/** @var string|null $title */
/** @var string $fallbackTitle */
$t = isset($title) ? (string) $title : '';
$heading = $t !== '' ? $t : $fallbackTitle;
?>
    <div class="hidden md:flex items-center justify-between px-5 lg:px-7 xl:px-8 pt-6 xl:pt-7 pb-1 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?= Str::e($heading) ?></h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium"><?= date('l, d F Y') ?></p>
        </div>
        <button onclick="toggleDark()" class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-300/90 bg-white shadow-sm shadow-slate-900/10 dark:bg-transparent dark:shadow-none dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <i data-lucide="sun" class="w-3.5 h-3.5 dark:hidden"></i>
            <i data-lucide="moon" class="w-3.5 h-3.5 hidden dark:inline-block"></i>
            <span class="dark:hidden">Light</span>
            <span class="hidden dark:inline">Dark</span>
        </button>
    </div>
