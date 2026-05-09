<?php

declare(strict_types=1);

use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;

$appName = Config::get('app.name', 'KHFinaM');
$titleText = isset($title) ? Str::e((string) $title) . ' · ' . Str::e($appName) : Str::e($appName);
$base = Url::basePath();
$u = $user ?? \App\Helpers\Auth::user();
$themePref = is_array($u) ? (string) ($u['preference_theme'] ?? 'system') : 'system';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <title><?= $titleText ?></title>
    <link rel="manifest" href="<?= Str::e($base) ?>/manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { primary: { DEFAULT: '#0f766e' } } } } };
    </script>
    <style> body { font-family: system-ui, -apple-system, sans-serif; padding-bottom: env(safe-area-inset-bottom); } </style>
    <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('<?= Str::e($base) ?>/sw.js').catch(function () {});
    }
    </script>
    <script>
    (function(){
      var p = <?= json_encode($themePref) ?> || 'system';
      var root = document.documentElement;
      if (p === 'dark') { root.classList.add('dark'); }
      else if (p === 'light') { root.classList.remove('dark'); }
      else {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        function sync(){ if (mq.matches) root.classList.add('dark'); else root.classList.remove('dark'); }
        if (mq.addEventListener) mq.addEventListener('change', sync); else mq.addListener(sync);
        sync();
      }
    })();
    </script>
</head>
<body class="min-h-full bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100 pb-20">
<header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 px-4 py-3 flex items-center justify-between">
    <h1 class="text-lg font-semibold tracking-tight"><?= isset($title) ? Str::e((string) $title) : $appName ?></h1>
    <div class="flex items-center gap-3">
    <a href="<?= Str::e(Url::to('/app/notifications')) ?>" class="text-lg" title="Notifications" aria-label="Notifications">🔔</a>
    <form action="<?= Str::e(Url::to('/logout')) ?>" method="post" class="inline">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="text-sm text-teal-700 dark:text-teal-300 font-medium">Logout</button>
    </form>
    </div>
</header>
<main class="max-w-lg mx-auto px-4 py-4">
<?php
$vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
if (is_file($vf)) {
    include $vf;
}
?>
</main>
<nav class="fixed bottom-0 inset-x-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 pb-safe">
    <div class="max-w-lg mx-auto grid grid-cols-5 gap-1 px-2 py-2 text-center text-xs">
        <a class="p-2 rounded-lg <?= str_contains((string) $viewPath, 'mobile/dashboard') ? 'bg-teal-50 text-teal-800 dark:bg-teal-950 dark:text-teal-100' : 'text-slate-600 dark:text-slate-400' ?>" href="<?= Str::e(Url::to('/app')) ?>">Home</a>
        <a class="p-2 rounded-lg <?= str_contains((string) $viewPath, 'mobile/add') ? 'bg-teal-50 text-teal-800' : 'text-slate-600 dark:text-slate-400' ?>" href="<?= Str::e(Url::to('/app/add')) ?>">Add</a>
        <a class="p-2 rounded-lg <?= str_contains((string) $viewPath, 'mobile/recurring') ? 'bg-teal-50 text-teal-800' : 'text-slate-600 dark:text-slate-400' ?>" href="<?= Str::e(Url::to('/app/recurring')) ?>">Repeat</a>
        <a class="p-2 rounded-lg <?= str_contains((string) $viewPath, 'mobile/stats') ? 'bg-teal-50 text-teal-800' : 'text-slate-600 dark:text-slate-400' ?>" href="<?= Str::e(Url::to('/app/stats')) ?>">Stats</a>
        <a class="p-2 rounded-lg <?= str_contains((string) $viewPath, 'mobile/profile') ? 'bg-teal-50 text-teal-800' : 'text-slate-600 dark:text-slate-400' ?>" href="<?= Str::e(Url::to('/app/profile')) ?>">Profile</a>
    </div>
</nav>
<a href="<?= Str::e(Url::to('/app/add')) ?>" class="fixed bottom-20 right-4 z-40 inline-flex h-14 w-14 items-center justify-center rounded-full bg-teal-600 text-white shadow-lg hover:bg-teal-700 md:bottom-8" title="Quick add" aria-label="Quick add">+</a>
</body>
</html>
