<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<div class="mb-4">
    <a href="<?= Str::e(Url::to('/app/recurring/new')) ?>" class="inline-flex items-center justify-center w-full rounded-xl bg-teal-600 text-white font-semibold py-3 text-sm">+ New recurring schedule</a>
</div>
<?php if ($message): ?><div class="mb-3 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-3 py-2"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-3 rounded-lg bg-rose-50 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div><?php endif; ?>

<ul class="space-y-3">
<?php foreach ($rows as $r): ?>
    <li class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 p-4">
        <div class="flex justify-between gap-2">
            <p class="font-semibold text-sm"><?= Str::e((string) $r['title']) ?></p>
            <span class="text-sm <?= ($r['type'] ?? '') === 'income' ? 'text-emerald-600' : 'text-rose-600' ?>">RM <?= number_format((float) $r['amount'], 2) ?></span>
        </div>
        <p class="text-xs text-slate-500 mt-1"><?= Str::e((string) $r['frequency']) ?> · next <?= Str::e((string) $r['next_occurrence']) ?> · <?= Str::e((string) ($r['wallet_name'] ?? '')) ?></p>
        <?php if (! empty($r['is_paused'])): ?><span class="text-xs text-amber-700">Paused</span><?php endif; ?>
        <div class="flex flex-wrap gap-2 mt-3">
            <form method="post" action="<?= Str::e(Url::to('/app/recurring/pause')) ?>" class="inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="schedule_id" value="<?= (int) $r['id'] ?>" />
                <input type="hidden" name="paused" value="<?= empty($r['is_paused']) ? '1' : '0' ?>" />
                <button type="submit" class="text-xs px-2 py-1 rounded border border-slate-300"><?= ! empty($r['is_paused']) ? 'Resume' : 'Pause' ?></button>
            </form>
            <form method="post" action="<?= Str::e(Url::to('/app/recurring/skip')) ?>" class="inline" onsubmit="return confirm('Skip next occurrence?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="schedule_id" value="<?= (int) $r['id'] ?>" />
                <button type="submit" class="text-xs px-2 py-1 rounded border border-slate-300">Skip next</button>
            </form>
            <form method="post" action="<?= Str::e(Url::to('/app/recurring/run')) ?>" class="inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="schedule_id" value="<?= (int) $r['id'] ?>" />
                <button type="submit" class="text-xs px-2 py-1 rounded bg-teal-600 text-white">Generate now</button>
            </form>
        </div>
    </li>
<?php endforeach; ?>
</ul>
<?php if ($rows === []): ?>
    <p class="text-sm text-slate-500">No recurring schedules yet.</p>
<?php endif; ?>
