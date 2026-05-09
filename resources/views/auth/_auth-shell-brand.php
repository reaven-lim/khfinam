<?php

declare(strict_types=1);

use App\Helpers\Config;
use App\Helpers\Str;

$appNm       = Config::get('app.name', 'KHFinaM');
$authHeading = $authHeading ?? '';
$authTagline = $authTagline ?? 'Personal Financial Intelligence';
?>
<div class="text-center mb-8">
    <div class="relative inline-flex">
        <div class="pointer-events-none absolute -inset-3 rounded-[1.75rem] bg-gradient-to-br from-teal-400/35 via-teal-600/20 to-transparent opacity-70 blur-xl dark:from-teal-500/25 dark:via-teal-400/15"></div>
        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-400 via-teal-600 to-teal-900 text-white shadow-lg shadow-teal-500/35 ring-1 ring-white/30 dark:shadow-teal-950/80 dark:ring-white/15">
            <i data-lucide="activity" class="h-7 w-7 opacity-95" stroke-width="2.25"></i>
        </div>
    </div>
    <h1 class="mt-7 text-[1.65rem] sm:text-[1.75rem] font-bold tracking-tight text-slate-900 dark:text-white"><?= Str::e((string) $authHeading) ?></h1>
    <p class="mt-2 text-sm font-medium text-transparent bg-clip-text bg-gradient-to-r from-teal-700 via-teal-600 to-teal-800 dark:from-teal-300 dark:via-teal-400 dark:to-teal-200 tracking-wide px-4"><?= Str::e((string) $authTagline) ?></p>
    <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500"><?= Str::e($appNm) ?></p>
</div>
