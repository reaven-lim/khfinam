<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Str;
use App\Helpers\Url;

$rows = $rows ?? [];
?>
<div class="grid lg:grid-cols-2 gap-8">
    <form method="post" action="<?= Str::e(Url::to('/admin/users')) ?>" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 space-y-2">
        <?= Csrf::field() ?>
        <h2 class="font-semibold mb-2">Create user</h2>
        <input name="username" required placeholder="Username" class="w-full rounded border px-2 py-1 text-sm" />
        <input type="email" name="email" required placeholder="Email" class="w-full rounded border px-2 py-1 text-sm" />
        <input name="full_name" placeholder="Full name" class="w-full rounded border px-2 py-1 text-sm" />
        <input type="password" name="password" required placeholder="Password (8+)" class="w-full rounded border px-2 py-1 text-sm" />
        <select name="role" class="w-full rounded border px-2 py-1 text-sm">
            <option value="user">User</option>
            <option value="super_admin">Super admin</option>
        </select>
        <button type="submit" class="rounded-lg bg-teal-600 text-white px-4 py-2 text-sm">Create</button>
    </form>
    <div class="rounded-2xl border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800"><tr><th class="p-2 text-left">User</th><th class="p-2">Role</th><th class="p-2">Edit</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="border-t border-slate-100 dark:border-slate-800 align-top">
                    <td class="p-2"><?= Str::e((string) $r['username']) ?><br/><span class="text-xs text-slate-500"><?= Str::e((string) $r['email']) ?></span></td>
                    <td class="p-2"><?= Str::e((string) $r['role']) ?></td>
                    <td class="p-2">
                        <form method="post" action="<?= Str::e(Url::to('/admin/users/update')) ?>" class="space-y-1">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>" />
                            <input name="email" value="<?= Str::e((string) $r['email']) ?>" class="w-full text-xs rounded border px-1 py-0.5" />
                            <input name="full_name" value="<?= Str::e((string) ($r['full_name'] ?? '')) ?>" placeholder="Name" class="w-full text-xs rounded border px-1 py-0.5" />
                            <select name="role" class="w-full text-xs rounded border">
                                <option value="user" <?= $r['role'] === 'user' ? 'selected' : '' ?>>user</option>
                                <option value="super_admin" <?= $r['role'] === 'super_admin' ? 'selected' : '' ?>>super_admin</option>
                            </select>
                            <label class="text-xs flex gap-1 items-center"><input type="checkbox" name="is_active" value="1" <?= ! empty($r['is_active']) ? 'checked' : '' ?> /> active</label>
                            <input type="password" name="new_password" placeholder="New password (optional)" class="w-full text-xs rounded border px-1" />
                            <button type="submit" class="text-xs text-teal-700 font-medium">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
