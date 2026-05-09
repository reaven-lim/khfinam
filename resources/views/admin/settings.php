<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$s = $settings ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<?php if ($message): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-800 px-4 py-2 text-sm"><?= Str::e((string) $message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-rose-50 text-rose-800 px-4 py-2 text-sm"><?= Str::e((string) $error) ?></div>
<?php endif; ?>
<form method="post" action="<?= Str::e(Url::to('/admin/settings')) ?>" class="max-w-xl space-y-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6">
    <?= Csrf::field() ?>
    <h2 class="text-sm font-semibold text-slate-500 uppercase">SMTP</h2>
    <label class="block text-sm">Host <input name="smtp_host" class="mt-1 w-full rounded border border-slate-300 dark:border-slate-700 px-2 py-1 bg-white dark:bg-slate-950" value="<?= Str::e($s['smtp_host'] ?? '') ?>" /></label>
    <label class="block text-sm">Port <input name="smtp_port" class="mt-1 w-full rounded border px-2 py-1" value="<?= Str::e($s['smtp_port'] ?? '587') ?>" /></label>
    <label class="block text-sm">User <input name="smtp_user" class="mt-1 w-full rounded border px-2 py-1" value="<?= Str::e($s['smtp_user'] ?? '') ?>" /></label>
    <label class="block text-sm">Password <input type="password" name="smtp_pass" class="mt-1 w-full rounded border px-2 py-1" value="<?= Str::e($s['smtp_pass'] ?? '') ?>" autocomplete="new-password" /></label>
    <label class="block text-sm">Encryption
        <select name="smtp_encryption" class="mt-1 w-full rounded border px-2 py-1">
            <option value="tls" <?= ($s['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
            <option value="ssl" <?= ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
        </select>
    </label>
    <button type="submit" class="rounded-lg bg-teal-600 text-white px-4 py-2 text-sm font-semibold">Save</button>
</form>
<form method="post" action="<?= Str::e(Url::to('/admin/settings/test-email')) ?>" class="max-w-xl mt-8 space-y-2 rounded-2xl border border-slate-200 p-6">
    <?= Csrf::field() ?>
    <h2 class="text-sm font-semibold text-slate-500 uppercase">Test email</h2>
    <input type="email" name="test_email" required placeholder="you@example.com" class="w-full rounded border px-2 py-1" />
    <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Send test</button>
</form>
