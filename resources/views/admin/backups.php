<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<?php if ($message): ?><div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-2 text-sm"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 rounded-lg bg-rose-50 text-rose-800 px-4 py-2 text-sm"><?= Str::e((string) $error) ?></div><?php endif; ?>

<form method="post" action="<?= Str::e(Url::to('/admin/backups/run')) ?>" class="inline">
    <?= Csrf::field() ?>
    <button type="submit" class="rounded-lg bg-teal-600 text-white px-4 py-2 text-sm font-semibold mb-6">Run backup now</button>
</form>
<p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Requires <code>mysqldump</code> on the server. Files: <code>storage/backups/</code>. Schedule: <code>php cron/backup.php</code>.</p>

<details class="mb-6 rounded-2xl border border-amber-200 dark:border-amber-900 bg-amber-50/50 dark:bg-amber-950/30 p-4">
    <summary class="cursor-pointer font-semibold text-amber-900 dark:text-amber-200 text-sm">Restore database (dangerous)</summary>
    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 mb-3">Overwrites the current database. Requires the <code>mysql</code> CLI (e.g. XAMPP <code>mysql.exe</code> on PATH or set <code>MYSQL_BIN</code>). Type <strong>RESTORE</strong> to confirm.</p>
    <form method="post" action="<?= Str::e(Url::to('/admin/backups/restore')) ?>" enctype="multipart/form-data" class="space-y-3 max-w-lg">
        <?= Csrf::field() ?>
        <label class="text-sm block">Confirm <input name="confirm" placeholder="RESTORE" class="mt-1 block w-full rounded border px-2 py-2 font-mono" autocomplete="off" /></label>
        <label class="text-sm block">Restore from existing backup
            <select name="backup_id" class="mt-1 block w-full rounded border px-2 py-2">
                <option value="0">— none —</option>
                <?php foreach ($rows as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"><?= Str::e((string) $b['filename']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="text-xs text-slate-500">Or upload .sql / .sql.gz</p>
        <input type="file" name="backup_file" accept=".sql,.gz,.sql.gz" class="text-sm" />
        <button type="submit" class="rounded-lg bg-rose-600 text-white px-4 py-2 text-sm font-semibold">Run restore</button>
    </form>
</details>

<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-3 text-left">File</th><th class="p-3">Size</th><th class="p-3">When</th><th class="p-3"></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $b): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-3 font-mono text-xs"><?= Str::e((string) $b['filename']) ?></td>
                <td class="p-3"><?= number_format((int) $b['size_bytes'] / 1024, 1) ?> KB</td>
                <td class="p-3"><?= Str::e((string) $b['created_at']) ?></td>
                <td class="p-3"><a class="text-teal-700 underline text-sm" href="<?= Str::e(Url::to('/admin/backups/download/' . (int) $b['id'])) ?>">Download</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
