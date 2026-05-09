<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;

$msg = \App\Core\Session::getFlash('message');
$error = $error ?? null;
?>
<div class="rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-slate-200/80 dark:border-slate-800 p-8">
    <div class="mb-6 text-center">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-600 text-white text-xl font-bold mb-3">K</div>
        <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1"><?= Str::e(Config::get('app.name', 'KHFinaM')) ?></p>
    </div>
    <?php if ($msg): ?>
        <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 text-sm px-3 py-2"><?= Str::e((string) $msg) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-800 dark:text-rose-200 text-sm px-3 py-2"><?= Str::e((string) $error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= Str::e(Url::to('/login')) ?>" class="space-y-4">
        <?= Csrf::field() ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Username or email</label>
            <input type="text" name="login" required autocomplete="username" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-600 focus:border-teal-600" />
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
            <input type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-600" />
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300" />
            Remember me
        </label>
        <button type="submit" class="w-full rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 text-sm transition">Continue</button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-500">
        <a href="<?= Str::e(Url::to('/forgot-password')) ?>" class="text-teal-700 dark:text-teal-300 font-medium">Forgot password?</a>
    </p>
    <p class="mt-6 text-xs text-center text-slate-400">Demo: <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">demo</code> / <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded">Demo@123</code></p>
</div>
