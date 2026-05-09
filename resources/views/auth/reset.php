<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$error = $error ?? null;
$token = $token ?? '';
?>
<div class="rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200/80 dark:border-slate-800 p-8">
    <h1 class="text-xl font-semibold mb-4">Set new password</h1>
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-rose-50 text-rose-800 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= Str::e(Url::to('/reset-password')) ?>" class="space-y-4">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= Str::e((string) $token) ?>" />
        <input type="password" name="password" required minlength="8" placeholder="New password" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
        <input type="password" name="password_confirm" required minlength="8" placeholder="Confirm" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
        <button type="submit" class="w-full rounded-xl bg-teal-600 text-white font-semibold py-2.5 text-sm">Update password</button>
    </form>
</div>
