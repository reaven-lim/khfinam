<?php
declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;

/**
 * @var array<string, array<string, array{0: string, 1: string}>> $navGroups
 * @var string $here Current request URI
 * @var string $sbPrefix dash-sb | adm-sb
 * @var string $navExactRoot Path that matches with equality (e.g. /dashboard or /admin)
 * @var string $navWrapClass Classes on <nav>
 */
?>
    <nav class="<?= Str::e($navWrapClass) ?>">
        <?php foreach ($navGroups as $groupLabel => $links): ?>
        <div>
            <p class="<?= Str::e($sbPrefix) ?>-heading text-[10px] font-bold text-slate-500 dark:text-slate-600 uppercase tracking-[0.14em] px-3 mb-1.5"><?= Str::e((string) $groupLabel) ?></p>
            <?php foreach ($links as $path => [$label, $icon]):
                $pathStr = (string) $path;
                $on = ($pathStr === $navExactRoot)
                    ? ($here === $navExactRoot)
                    : str_starts_with($here, $pathStr);
            ?>
            <a href="<?= Str::e(Url::to($pathStr)) ?>" title="<?= Str::e($label) ?>" class="sidebar-link md:justify-center xl:justify-start <?= $on ? 'active' : '' ?>">
                <i data-lucide="<?= Str::e($icon) ?>" class="w-4 h-4 shrink-0 md:scale-[1.05]"></i>
                <span class="<?= Str::e($sbPrefix) ?>-text truncate"><?= Str::e($label) ?></span>
                <?php if ($on): ?><span class="<?= Str::e($sbPrefix) ?>-kpi-dot w-1.5 h-1.5 rounded-full bg-teal-500 shrink-0" aria-hidden="true"></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </nav>
