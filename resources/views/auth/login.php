<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$msg = \App\Core\Session::getFlash('message');
$error = $error ?? null;

$authHeading = 'Sign in';
$authTagline = 'Personal Financial Intelligence';
include dirname(__DIR__) . '/auth/_auth-shell-brand.php';
?>
<div class="auth-card border border-white/60 dark:border-white/[0.08] backdrop-blur-2xl p-8 sm:p-10">
    <?php if ($msg): ?>
        <div class="mb-5 flex gap-3 rounded-xl border border-emerald-200/70 dark:border-emerald-800/70 bg-emerald-50/90 dark:bg-emerald-950/50 px-4 py-3 text-sm text-emerald-900 dark:text-emerald-100">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5"></i>
            <span class="leading-relaxed"><?= Str::e((string) $msg) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-5 flex gap-3 rounded-xl border border-rose-200/80 dark:border-rose-900/70 bg-rose-50/90 dark:bg-rose-950/45 px-4 py-3 text-sm text-rose-900 dark:text-rose-100">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5"></i>
            <span class="leading-relaxed"><?= Str::e((string) $error) ?></span>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= Str::e(Url::to('/login')) ?>" class="auth-submit-form space-y-5">
        <?= Csrf::field() ?>
        <div>
            <label class="auth-label">Username or email</label>
            <div class="relative">
                <i data-lucide="user" class="pointer-events-none absolute left-3.5 top-1/2 z-[1] h-[18px] w-[18px] -translate-y-1/2 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                <input type="text" name="login" required autocomplete="username" placeholder="your@email.com"
                    class="auth-input pl-10" />
            </div>
        </div>
        <div>
            <label class="auth-label">Password</label>
            <div class="relative">
                <i data-lucide="lock" class="pointer-events-none absolute left-3.5 top-1/2 z-[1] h-[18px] w-[18px] -translate-y-1/2 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"
                    class="auth-input pl-10" />
            </div>
        </div>
        <div class="flex items-center justify-between gap-4 pt-0.5">
            <label class="flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 select-none">
                <input type="checkbox" name="remember" value="1"
                    class="h-4 w-4 shrink-0 rounded-md border border-slate-300 text-teal-600 focus:ring-2 focus:ring-teal-500/40 dark:border-slate-600 dark:bg-slate-950/70" />
                Remember me
            </label>
        </div>
        <button type="submit" class="auth-btn-primary mt-6">
            <span class="auth-btn-shimmer pointer-events-none" aria-hidden="true"></span>
            <span class="relative flex items-center justify-center gap-2">
                <span>Continue to platform</span>
                <i data-lucide="arrow-right" class="w-4 h-4 opacity-90" stroke-width="2.5"></i>
            </span>
        </button>
    </form>
    <p class="mt-6 text-center">
        <a href="<?= Str::e(Url::to('/forgot-password')) ?>"
            class="text-sm font-semibold text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300 transition-colors inline-flex items-center gap-1.5">
            <span>Forgot password?</span>
            <i data-lucide="chevron-right" class="w-4 h-4 opacity-70"></i>
        </a>
    </p>
    <div class="mt-8 rounded-xl border border-dashed border-slate-200/90 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-950/35 px-4 py-3 text-center">
        <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400 dark:text-slate-500 mb-2">Demo access</p>
        <p class="text-xs text-slate-600 dark:text-slate-400 font-mono tabular-nums">
            <span class="text-slate-500 dark:text-slate-500">user</span>
            <span class="mx-1.5 text-slate-300 dark:text-slate-600">·</span>
            <code class="text-teal-700 dark:text-teal-300 font-semibold">demo</code>
            <span class="mx-2 text-slate-300 dark:text-slate-600">/</span>
            <code class="text-teal-700 dark:text-teal-300 font-semibold">Demo@123</code>
        </p>
    </div>
</div>
