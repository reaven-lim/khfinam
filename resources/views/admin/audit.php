<?php

declare(strict_types=1);

use App\Helpers\Str;

$rows = $rows ?? [];
?>
<div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
    <table class="min-w-full text-xs font-mono">
        <thead class="bg-slate-50 dark:bg-slate-800 text-left">
            <tr><th class="p-2">Time</th><th class="p-2">Action</th><th class="p-2">IP</th><th class="p-2">Entity</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="border-t border-slate-100 dark:border-slate-800">
                <td class="p-2 whitespace-nowrap"><?= Str::e((string) $r['created_at']) ?></td>
                <td class="p-2"><?= Str::e((string) $r['action']) ?></td>
                <td class="p-2"><?= Str::e((string) ($r['ip_address'] ?? '')) ?></td>
                <td class="p-2"><?= Str::e((string) (($r['entity_type'] ?? '') . ' ' . ($r['entity_id'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
