<?php
declare(strict_types=1);

use App\Helpers\Str;
use App\Helpers\Url;
?>

    <div class="px-2 xl:px-3 pt-2 xl:pt-3 space-y-1 shrink-0">
        <a href="<?= Str::e(Url::to('/app')) ?>" class="sidebar-link text-teal-800 dark:text-teal-300 md:justify-center xl:justify-start" title="Mobile hub">
            <i data-lucide="smartphone" class="w-4 h-4 shrink-0 md:scale-[1.05]"></i>
            <span class="dash-sb-text truncate">Mobile hub</span>
        </a>
        <a href="<?= Str::e(Url::to('/app/add')) ?>" class="sidebar-link text-teal-800 dark:text-teal-300 md:justify-center xl:justify-start" title="Quick add transaction">
            <i data-lucide="plus-circle" class="w-4 h-4 shrink-0 md:scale-[1.05]"></i>
            <span class="dash-sb-text truncate">Quick add transaction</span>
        </a>
    </div>
