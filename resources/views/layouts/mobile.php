<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\Url;

$appName   = Config::get('app.name', 'KHFinaM');
$titleText = isset($title) ? Str::e((string) $title) . ' · ' . Str::e($appName) : Str::e($appName);
$base      = Url::basePath();
$u         = $user ?? \App\Helpers\Auth::user();
$themePref = is_array($u) ? (string) ($u['preference_theme'] ?? 'system') : 'system';
$username  = is_array($u) ? (string) ($u['username'] ?? '') : '';
$initials  = strtoupper(mb_substr($username, 0, 2));

$mobileNav = [
    '/app'               => ['Home',       'home',        'mobile/dashboard'],
    '/app/add'           => ['Add',        'plus-circle', 'mobile/add'],
    '/app/recurring'     => ['Bills',      'repeat-2',    'mobile/recurring'],
    '/dashboard'         => ['Intelligence','bar-chart-3','dashboard/overview'],
    '/app/profile'       => ['Profile',    'user-round',  'mobile/profile'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <title><?= $titleText ?></title>
    <link rel="manifest" href="<?= Str::e($base) ?>/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: { primary: { DEFAULT: '#0f766e' } }
                }
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.395.0/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; padding-bottom: env(safe-area-inset-bottom); }
        [data-lucide] { display: inline-block; vertical-align: middle; }
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        .nav-item-active { color: #0f766e; }
        .dark .nav-item-active { color: #2dd4bf; }
        .nav-item { color: #94a3b8; transition: color 0.15s; }
        .dark .nav-item { color: #64748b; }
        details > summary::-webkit-details-marker { display: none; }
        details[open] .chevron-icon { transform: rotate(180deg); }
        .chevron-icon { transition: transform 0.2s ease; }
        /*
         Native inputs/selects/textarea inherit poorly with dark UA themes + autofill:
         caret and typed text can match the background. Force readable colors in <main>.
         */
        main input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]),
        main select,
        main textarea {
            color-scheme: light dark;
            color: rgb(15 23 42);
            background-color: rgb(255 255 255);
        }
        .dark main input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="hidden"]),
        .dark main select,
        .dark main textarea {
            color: rgb(241 245 249);
            background-color: rgb(2 6 23);
        }
        main input::placeholder,
        main textarea::placeholder {
            opacity: 1;
            color: rgb(100 116 139);
        }
        .dark main input::placeholder,
        .dark main textarea::placeholder {
            color: rgb(148 163 184);
        }
        main input:-webkit-autofill,
        main input:-webkit-autofill:hover,
        main input:-webkit-autofill:focus {
            -webkit-text-fill-color: rgb(15 23 42);
            box-shadow: inset 0 0 0 1000px rgb(255 255 255);
            transition: background-color 99999s ease-out 0s;
        }
        .dark main input:-webkit-autofill,
        .dark main input:-webkit-autofill:hover,
        .dark main input:-webkit-autofill:focus {
            -webkit-text-fill-color: rgb(241 245 249);
            box-shadow: inset 0 0 0 1000px rgb(2 6 23);
            transition: background-color 99999s ease-out 0s;
        }
    </style>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= Str::e($base) ?>/sw.js').catch(function(){});
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
            function sync(){ mq.matches ? root.classList.add('dark') : root.classList.remove('dark'); }
            mq.addEventListener ? mq.addEventListener('change', sync) : mq.addListener(sync);
            sync();
        }
    })();
    </script>
</head>
<body class="min-h-full bg-slate-100 dark:bg-[#090e1a] text-slate-900 dark:text-slate-100">

<!-- ─── Sticky header ─────────────────────────────────────── -->
<header class="sticky top-0 z-20 border-b border-slate-200/80 dark:border-slate-800
    bg-white/90 dark:bg-[#0d1424]/90 backdrop-blur-md
    px-4 py-3 flex items-center justify-between" style="padding-top: max(12px, env(safe-area-inset-top))">
    <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-400 to-teal-700 flex items-center justify-center shadow-sm shadow-teal-500/30 shrink-0">
            <i data-lucide="trending-up" class="w-3.5 h-3.5 text-white"></i>
        </div>
        <h1 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">
            <?= isset($title) ? Str::e((string) $title) : Str::e($appName) ?>
        </h1>
    </div>
    <div class="flex items-center gap-1.5">
        <a href="<?= Str::e(Url::to('/app/notifications')) ?>"
            class="relative p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            aria-label="Notifications">
            <i data-lucide="bell" class="w-5 h-5 text-slate-600 dark:text-slate-300"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-[#0d1424]"></span>
        </a>
        <a href="<?= Str::e(Url::to('/dashboard')) ?>"
            class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-300"
            title="Desktop intelligence" aria-label="Desktop intelligence">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
        </a>
        <form action="<?= Str::e(Url::to('/logout')) ?>" method="post" class="inline">
            <?= Csrf::field() ?>
            <button type="submit"
                class="p-2 rounded-full text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-rose-600 dark:hover:text-rose-400 transition-colors"
                title="Log out" aria-label="Log out">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </button>
        </form>
        <a href="<?= Str::e(Url::to('/app/profile')) ?>"
            class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center text-white text-xs font-bold shadow-sm"
            aria-label="Profile">
            <?= Str::e($initials ?: '?') ?>
        </a>
    </div>
</header>

<!-- ─── Main content ──────────────────────────────────────── -->
<main class="max-w-lg mx-auto px-4 py-4 pb-28">
<?php
$vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
if (is_file($vf)) {
    include $vf;
}
?>
</main>

<!-- ─── Bottom navigation ─────────────────────────────────── -->
<nav class="fixed bottom-0 inset-x-0 z-30 border-t border-slate-200/80 dark:border-slate-800
    bg-white/95 dark:bg-[#0d1424]/95 backdrop-blur-md"
    style="padding-bottom: env(safe-area-inset-bottom)">
    <div class="max-w-lg mx-auto grid grid-cols-5 px-1 pt-1 pb-1">
        <?php foreach ($mobileNav as $path => [$label, $icon, $viewMatch]):
            $on = str_contains((string) $viewPath, $viewMatch);
        ?>
        <a href="<?= Str::e(Url::to($path)) ?>"
            class="flex flex-col items-center gap-0.5 py-2 px-1 rounded-xl transition-colors <?= $on ? 'nav-item-active' : 'nav-item' ?>">
            <i data-lucide="<?= $icon ?>" class="w-[22px] h-[22px] <?= $on ? 'stroke-[2.5]' : 'stroke-2' ?>"></i>
            <span class="text-[10px] <?= $on ? 'font-bold' : 'font-medium' ?>"><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<!-- ─── FAB ───────────────────────────────────────────────── -->
<a href="<?= Str::e(Url::to('/app/add')) ?>"
    class="fixed bottom-20 right-4 z-40 flex items-center justify-center w-14 h-14 rounded-full
        bg-gradient-to-br from-teal-500 to-teal-700
        text-white shadow-xl shadow-teal-500/30
        hover:scale-105 active:scale-95 transition-transform duration-150"
    style="margin-bottom: env(safe-area-inset-bottom)"
    title="Add transaction" aria-label="Add transaction">
    <i data-lucide="plus" class="w-6 h-6 stroke-[2.5]"></i>
</a>

<script>
lucide.createIcons();
</script>
</body>
</html>
