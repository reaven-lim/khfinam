<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;

$appName = Config::get('app.name', 'KHFinaM');
$titleText = isset($title) ? Str::e((string) $title) . ' · Admin · ' . Str::e($appName) : 'Admin · ' . Str::e($appName);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titleText ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
</head>
<body class="min-h-full bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100 flex">
<aside class="w-64 shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hidden md:flex flex-col">
    <div class="p-4 font-bold tracking-tight text-teal-700 dark:text-teal-300"><?= Str::e($appName) ?> Admin</div>
    <nav class="flex-1 px-2 space-y-1 text-sm">
        <?php
        $links = [
            '/admin' => 'Overview',
            '/admin/transactions' => 'Transactions',
            '/admin/users' => 'Users',
            '/admin/categories' => 'Categories',
            '/admin/rates' => 'Rates',
            '/admin/reports' => 'Reports',
            '/admin/recurring' => 'Recurring',
            '/admin/notifications' => 'Notifications',
            '/admin/audit' => 'Audit log',
            '/admin/backups' => 'Backups',
            '/admin/settings' => 'Settings',
        ];
        $here = \App\Core\Request::uri();
        foreach ($links as $path => $label):
            $on = $path === '/admin'
                ? $here === '/admin'
                : ($here === $path || str_starts_with($here, $path . '/'));
            $active = $on ? 'bg-teal-50 dark:bg-teal-950 text-teal-900' : 'hover:bg-slate-50 dark:hover:bg-slate-800'; ?>
            <a href="<?= Str::e(Url::to($path)) ?>" class="block rounded-lg px-3 py-2 <?= $active ?>"><?= Str::e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <form action="<?= Str::e(Url::to('/logout')) ?>" method="post" class="p-4 border-t border-slate-200 dark:border-slate-800">
        <?= Csrf::field() ?>
        <button type="submit" class="text-sm text-rose-600 font-medium w-full text-left">Logout</button>
    </form>
</aside>
<div class="flex-1 flex flex-col min-w-0">
    <header class="h-14 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 backdrop-blur flex items-center px-4 md:hidden">
        <span class="font-semibold"><?= isset($title) ? Str::e((string) $title) : 'Admin' ?></span>
    </header>
    <main class="p-4 md:p-8 max-w-7xl mx-auto w-full">
        <?php
        $vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
        if (is_file($vf)) {
            include $vf;
        }
        ?>
    </main>
</div>
</body>
</html>
