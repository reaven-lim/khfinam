<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$error = $error ?? null;
$token = $token ?? '';

$authHeading = 'Set new password';
$authTagline = 'Personal Financial Intelligence';
include dirname(__DIR__) . '/auth/_auth-shell-brand.php';
?>
<div class="auth-card border border-white/60 dark:border-white/[0.08] backdrop-blur-2xl p-8 sm:p-10">
    <p class="-mt-3 mb-6 text-center text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        Choose a strong password — at least 8 characters — to protect your finances.
    </p>
    <?php if ($error): ?>
        <div class="mb-5 flex gap-3 rounded-xl border border-rose-200/80 dark:border-rose-900/70 bg-rose-50/90 dark:bg-rose-950/45 px-4 py-3 text-sm text-rose-900 dark:text-rose-100">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5"></i>
            <span class="leading-relaxed"><?= Str::e((string) $error) ?></span>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= Str::e(Url::to('/reset-password')) ?>" class="auth-submit-form space-y-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= Str::e((string) $token) ?>" />
        <div>
            <label class="auth-label">New password</label>
            <div class="relative">
                <i data-lucide="key" class="pointer-events-none absolute left-3.5 top-1/2 z-[1] h-[18px] w-[18px] -translate-y-1/2 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters"
                    class="auth-input pl-10" />
            </div>
        </div>
        <div>
            <label class="auth-label">Confirm password</label>
            <div class="relative">
                <i data-lucide="shield-check" class="pointer-events-none absolute left-3.5 top-1/2 z-[1] h-[18px] w-[18px] -translate-y-1/2 text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="Repeat password"
                    class="auth-input pl-10" />
            </div>
        </div>
        <button type="submit" class="auth-btn-primary mt-2">
            <span class="auth-btn-shimmer pointer-events-none" aria-hidden="true"></span>
            <span class="relative flex items-center justify-center gap-2">
                <span>Update password</span>
                <i data-lucide="check" class="w-4 h-4 opacity-95" stroke-width="2.75"></i>
            </span>
        </button>
    </form>
    <p class="mt-7 text-center">
        <a href="<?= Str::e(Url::to('/login')) ?>"
            class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-slate-600 hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-400 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Back to login
        </a>
    </p>
</div>
