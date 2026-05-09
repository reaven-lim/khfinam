<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
$users = $users ?? [];
$message = $message ?? null;
$error = $error ?? null;
?>
<?php if ($message): ?><div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-2 text-sm"><?= Str::e((string) $message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 rounded-lg bg-rose-50 text-rose-800 px-4 py-2 text-sm"><?= Str::e((string) $error) ?></div><?php endif; ?>

<form method="post" action="<?= Str::e(Url::to('/admin/notifications/broadcast')) ?>" class="mb-8 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-3 max-w-xl">
    <?= Csrf::field() ?>
    <h2 class="font-semibold text-sm">Broadcast notification</h2>
    <label class="text-sm block">User (leave “All users” for broadcast)
        <select name="target_user_id" class="mt-1 block w-full rounded border px-2 py-2 text-sm">
            <option value="0">All active users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= (int) $u['id'] ?>"><?= Str::e((string) $u['username']) ?> (<?= Str::e((string) $u['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="text-sm block">Title<input name="title" required class="mt-1 block w-full rounded border px-2 py-2" /></label>
    <label class="text-sm block">Body<textarea name="body" rows="3" class="mt-1 block w-full rounded border px-2 py-2"></textarea></label>
    <button type="submit" class="rounded-lg bg-teal-600 text-white px-4 py-2 text-sm font-semibold">Send</button>
</form>

<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 dark:bg-slate-800 text-left"><tr>
            <th class="p-3">When</th><th class="p-3">User</th><th class="p-3">Title</th><th class="p-3">Read</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $n): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-3 whitespace-nowrap"><?= Str::e((string) $n['created_at']) ?></td>
                <td class="p-3"><?= Str::e((string) $n['username']) ?></td>
                <td class="p-3"><?= Str::e((string) $n['title']) ?></td>
                <td class="p-3"><?= ! empty($n['read_at']) ? Str::e((string) $n['read_at']) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
