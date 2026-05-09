<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Config;
use App\Helpers\Str;

$appName = Config::get('app.name', 'KHFinaM');
$titleText = isset($title) ? Str::e((string) $title) . ' · ' . Str::e($appName) : Str::e($appName);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titleText ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: { DEFAULT: '#0f766e' } } } } };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style> body { font-family: 'DM Sans', system-ui, sans-serif; } </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 antialiased">
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <?php
        $vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
        if (is_file($vf)) {
            include $vf;
        }
        ?>
    </div>
</div>
</body>
</html>
