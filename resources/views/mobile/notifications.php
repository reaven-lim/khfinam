<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
?>
<div class="flex justify-end gap-2 mb-3">
    <form method="post" action="<?= Str::e(Url::to('/app/notifications/read-all')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="text-xs text-teal-700 dark:text-teal-300 font-medium">Mark all read</button>
    </form>
</div>
<ul class="divide-y divide-slate-200 dark:divide-slate-800 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-900">
<?php foreach ($rows as $n): ?>
    <li class="p-4 <?= empty($n['read_at']) ? 'bg-teal-50/50 dark:bg-teal-950/30' : '' ?>">
        <p class="font-medium text-sm"><?= Str::e((string) $n['title']) ?></p>
        <?php if (! empty($n['body'])): ?><p class="text-sm text-slate-600 dark:text-slate-300 mt-1"><?= Str::e((string) $n['body']) ?></p><?php endif; ?>
        <div class="flex justify-between items-center mt-2 gap-2">
            <p class="text-xs text-slate-400"><?= Str::e((string) $n['created_at']) ?></p>
            <?php if (empty($n['read_at'])): ?>
            <form method="post" action="<?= Str::e(Url::to('/app/notifications/read')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>" />
                <button type="submit" class="text-xs text-teal-700 font-medium">Mark read</button>
            </form>
            <?php endif; ?>
        </div>
    </li>
<?php endforeach; ?>
</ul>
