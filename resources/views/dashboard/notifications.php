<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
?>
<div class="max-w-2xl mx-auto">
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Only notifications for your account appear here.</p>
    <div class="flex justify-end gap-2 mb-4">
        <form method="post" action="<?= Str::e(Url::to('/app/notifications/read-all')) ?>">
            <?= Csrf::field() ?>
            <button type="submit" class="text-xs text-teal-700 dark:text-teal-300 font-semibold hover:underline">Mark all read</button>
        </form>
    </div>
    <ul class="divide-y divide-slate-200 dark:divide-slate-800 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-[#0d1424]">
    <?php foreach ($rows as $n): ?>
        <li class="p-4 md:p-5 <?= empty($n['read_at']) ? 'bg-teal-50/50 dark:bg-teal-950/20' : '' ?>">
            <p class="font-semibold text-slate-900 dark:text-slate-100"><?= Str::e((string) $n['title']) ?></p>
            <?php if (! empty($n['body'])): ?><p class="text-sm text-slate-600 dark:text-slate-300 mt-1 leading-relaxed"><?= Str::e((string) $n['body']) ?></p><?php endif; ?>
            <div class="flex justify-between items-center mt-3 gap-2 flex-wrap">
                <p class="text-xs text-slate-400"><?= Str::e((string) $n['created_at']) ?></p>
                <?php if (empty($n['read_at'])): ?>
                <form method="post" action="<?= Str::e(Url::to('/app/notifications/read')) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>" />
                    <button type="submit" class="text-xs font-semibold text-teal-700 dark:text-teal-300 hover:underline">Mark read</button>
                </form>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <li class="p-12 text-center text-slate-500 dark:text-slate-400 text-sm font-medium">No notifications yet.</li>
    <?php endif; ?>
    </ul>
</div>
