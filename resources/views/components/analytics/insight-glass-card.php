<?php
declare(strict_types=1);

use App\Helpers\Str;

/**
 * Glass-style insight / narrative card (admin transactions smart insights).
 *
 * @var string $icon Lucide icon name
 * @var string $iconClass Classes on the <i> (stroke, color)
 * @var string $iconBoxClass Classes for the icon tile
 * @var string $orbClass Decorative gradient orb (absolute span)
 * @var string $eyebrow Uppercase label
 * @var string $contentHtml Inner HTML from parent (must be trusted / pre-escaped by caller)
 * @var string $articleClass Optional extra classes on <article> (e.g. col spans)
 */
$articleClass = $articleClass ?? 'group relative isolate overflow-hidden rounded-3xl border border-slate-200/95 bg-white shadow-[0_10px_40px_-14px_rgba(15,23,42,0.18)] ring-1 ring-slate-900/[0.05]
        dark:border-slate-600/40 dark:bg-gradient-to-br dark:from-[#161f34] dark:via-[#10192b] dark:to-[#0a0f18]
        dark:shadow-[0_14px_48px_-14px_rgba(0,0,0,0.85),inset_0_1px_0_0_rgba(255,255,255,0.08)] dark:ring-white/[0.09]
        backdrop-blur-sm p-5 md:p-6 flex gap-4 md:gap-5 items-start';
?>
    <article class="<?= Str::e($articleClass) ?>">
        <span class="pointer-events-none absolute -right-14 -top-16 h-40 w-40 rounded-full <?= Str::e($orbClass) ?>" aria-hidden="true"></span>
        <div class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl <?= Str::e($iconBoxClass) ?>">
            <i data-lucide="<?= Str::e($icon) ?>" class="h-6 w-6 stroke-[2.25] <?= Str::e($iconClass) ?>"></i>
        </div>
        <div class="relative min-w-0 flex-1 pt-0.5">
            <h3 class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-teal-800 dark:text-teal-300"><?= Str::e($eyebrow) ?></h3>
            <?= $contentHtml ?>
        </div>
    </article>
