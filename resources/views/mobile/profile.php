<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$u = $user ?? [];
?>
<div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 p-4 space-y-2 text-sm mb-4">
    <p><span class="text-slate-500">Name:</span> <?= Str::e((string) ($u['full_name'] ?? $u['username'] ?? '')) ?></p>
    <p><span class="text-slate-500">Email:</span> <?= Str::e((string) ($u['email'] ?? '')) ?></p>
</div>
<form method="post" action="<?= Str::e(Url::to('/app/profile')) ?>" class="rounded-2xl border border-slate-200 p-4 space-y-3">
    <?= Csrf::field() ?>
    <label class="block text-sm">Theme
        <select name="preference_theme" class="mt-1 w-full rounded border px-2 py-2">
            <option value="system" <?= ($u['preference_theme'] ?? '') === 'system' ? 'selected' : '' ?>>System</option>
            <option value="light" <?= ($u['preference_theme'] ?? '') === 'light' ? 'selected' : '' ?>>Light</option>
            <option value="dark" <?= ($u['preference_theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
        </select>
    </label>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="preference_mute_low_balance" value="1" <?= ! empty($u['preference_mute_low_balance']) ? 'checked' : '' ?> />
        Mute low-balance reminders (in-app + email)
    </label>
    <button type="submit" class="w-full rounded-xl bg-teal-600 text-white py-2 font-semibold text-sm">Save preferences</button>
</form>

<form method="post" action="<?= Str::e(Url::to('/logout')) ?>" class="mt-4">
    <?= Csrf::field() ?>
    <button type="submit"
        class="w-full rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/80 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 py-3 font-semibold text-sm hover:bg-rose-100 dark:hover:bg-rose-950/50 transition-colors">
        Log out
    </button>
</form>
