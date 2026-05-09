<?php
declare(strict_types=1);

use App\Helpers\Str;

/** @var string|null $title Page title */
/** @var string $fallbackTitle */
$t = isset($title) ? (string) $title : '';
$heading = $t !== '' ? $t : $fallbackTitle;
?>
    <header class="h-14 border-b border-slate-300/90 dark:border-slate-800 bg-white/92 dark:bg-[#0d1424]/90 backdrop-blur-md shadow-[0_1px_0_0_rgba(15,23,42,0.06)] dark:shadow-none flex items-center gap-3 px-4 md:hidden sticky top-0 z-30 shrink-0">
        <button onclick="openSidebar()" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <i data-lucide="menu" class="w-5 h-5 text-slate-600 dark:text-slate-300"></i>
        </button>
        <p class="font-semibold text-sm flex-1 truncate"><?= Str::e($heading) ?></p>
        <button onclick="toggleDark()" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-500 dark:text-slate-400">
            <i data-lucide="sun" class="w-4 h-4 dark:hidden"></i>
            <i data-lucide="moon" class="w-4 h-4 hidden dark:inline-block"></i>
        </button>
    </header>
