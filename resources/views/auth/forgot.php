<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$error = $error ?? null;
$message = $message ?? null;
?>
<div class="rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200/80 dark:border-slate-800 p-8">
    <h1 class="text-xl font-semibold mb-4">Forgot password</h1>
    <?php if ($message): ?>
        <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 text-sm px-3 py-2"><?= Str::e((string) $message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-rose-50 text-rose-800 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= Str::e(Url::to('/forgot-password')) ?>" class="space-y-4">
        <?= Csrf::field() ?>
        <input type="email" name="email" required placeholder="Email" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" />
        <button type="submit" class="w-full rounded-xl bg-teal-600 text-white font-semibold py-2.5 text-sm">Send reset link</button>
    </form>
    <p class="mt-4 text-center text-sm"><a href="<?= Str::e(Url::to('/login')) ?>" class="text-teal-700 font-medium">Back to login</a></p>
</div>
