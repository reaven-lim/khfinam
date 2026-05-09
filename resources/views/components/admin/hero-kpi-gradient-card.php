<?php
declare(strict_types=1);

use App\Helpers\Str;

/**
 * @var string $gradientShell Full gradient + layout classes for the card shell
 * @var string $label Uppercase label
 * @var string $value Primary value (plain text — escaped below)
 * @var string $footnote Secondary line (plain text — escaped below)
 * @var string $icon Lucide icon name
 * @var string $valueClass Optional Tailwind classes for the main value line
 * @var string|null $trendChip Optional short label (e.g. MoM delta) — escaped
 * @var string $trendChipClass Tailwind for trend chip (default neutral glass)
 */
$valueClass = $valueClass ?? 'text-3xl font-extrabold mt-1 tabular-nums tracking-tight';
$trendChipClass = $trendChipClass ?? 'bg-white/14 text-white/95 ring-1 ring-white/25';
?>
    <div class="<?= Str::e($gradientShell) ?>">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_90%_80%_at_100%_0%,rgba(255,255,255,0.2),transparent_52%)] opacity-90"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/18 dark:from-black/14 to-transparent"></div>
        <?php if (!empty($trendChip)): ?>
        <span class="absolute top-3 right-3 z-[1] inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm <?= Str::e($trendChipClass) ?>"><?= Str::e((string) $trendChip) ?></span>
        <?php endif; ?>
        <div class="relative flex items-start justify-between">
            <div class="min-w-0">
                <p class="text-[10px] font-bold opacity-80 uppercase tracking-[0.14em] drop-shadow-sm"><?= Str::e($label) ?></p>
                <p class="<?= Str::e($valueClass) ?> drop-shadow-sm"><?= Str::e($value) ?></p>
                <p class="text-xs opacity-85 mt-1.5 font-medium leading-snug"><?= Str::e($footnote) ?></p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-white/18 ring-2 ring-white/25 shadow-inner flex items-center justify-center shrink-0 ml-2 backdrop-blur-[2px]">
                <i data-lucide="<?= Str::e($icon) ?>" class="w-[1.35rem] h-[1.35rem] text-white opacity-[0.97]" stroke-width="2.25"></i>
            </div>
        </div>
        <div class="absolute -bottom-5 -right-5 w-24 h-24 rounded-full bg-white/5 blur-[1px]"></div>
        <div class="absolute -top-3 -left-3 w-16 h-16 rounded-full bg-white/5"></div>
    </div>
