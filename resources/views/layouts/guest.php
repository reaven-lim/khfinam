<?php

declare(strict_types=1);

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.395.0/dist/umd/lucide.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        };
    </script>
    <style>
        html { color-scheme: light dark; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        [data-lucide] { display: inline-block; vertical-align: middle; }
        .auth-noise {
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
        }
        .auth-grid {
            background-size: 56px 56px;
            background-image:
                linear-gradient(to right, rgba(15, 118, 110, 0.06) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(15, 118, 110, 0.06) 1px, transparent 1px);
        }
        .dark .auth-grid {
            background-image:
                linear-gradient(to right, rgba(45, 212, 191, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(45, 212, 191, 0.05) 1px, transparent 1px);
        }
        .auth-card {
            border-radius: 1.5rem;
            background: linear-gradient(155deg, rgba(255,255,255,0.72) 0%, rgba(248,250,252,0.55) 55%, rgba(255,255,255,0.5) 100%);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.55) inset,
                0 25px 70px -35px rgba(15, 23, 42, 0.28),
                0 0 80px -40px rgba(13, 148, 136, 0.35);
        }
        .dark .auth-card {
            background: linear-gradient(155deg, rgba(15,23,42,0.75) 0%, rgba(12,18,32,0.82) 48%, rgba(8,12,24,0.88) 100%);
            box-shadow:
                0 0 0 1px rgba(255,255,255,0.08) inset,
                0 25px 70px -38px rgba(0,0,0,0.75),
                0 0 100px -50px rgba(20,184,166,0.15);
        }
        .auth-input {
            font-size: 0.9375rem;
            line-height: 1.35;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.875rem;
            transition: border-color 0.15s, box-shadow 0.2s, background-color 0.15s;
            color: rgb(15 23 42);
            background-color: rgba(255,255,255,0.92);
            border: 1px solid rgba(148, 163, 184, 0.45);
        }
        .dark .auth-input {
            color: rgb(248 250 252);
            background-color: rgba(15,23,42,0.72);
            border-color: rgba(71,85,105,0.85);
        }
        .auth-input::placeholder {
            color: rgb(148 163 184);
        }
        .dark .auth-input::placeholder {
            color: rgb(100 116 139);
        }
        .auth-input:hover {
            border-color: rgba(13,148,136,0.45);
        }
        .dark .auth-input:hover {
            border-color: rgba(45,212,191,0.35);
        }
        .auth-input:focus {
            outline: none;
            border-color: rgba(13,148,136,0.75);
            box-shadow: 0 0 0 3px rgba(20,184,166,0.22), 0 12px 32px -20px rgba(13,148,136,0.35);
        }
        .dark .auth-input:focus {
            border-color: rgba(94,234,212,0.55);
            box-shadow: 0 0 0 3px rgba(45,212,191,0.15), 0 12px 40px -24px rgba(20,184,166,0.2);
        }
        .auth-label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 0.55rem;
            color: rgb(71 85 105);
        }
        .dark .auth-label {
            color: rgb(148 163 184);
        }
        .auth-btn-primary {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 0.875rem;
            padding: 0.8125rem 1.125rem;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #fff;
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 38%, #0f766e 100%);
            box-shadow: 0 10px 32px -10px rgba(13,148,136,0.65), 0 0 0 1px rgba(255,255,255,0.12) inset;
            transition: transform 0.15s ease, filter 0.15s ease, opacity 0.15s ease;
        }
        .auth-btn-primary:hover:not(:disabled) {
            filter: brightness(1.06);
            transform: translateY(-0.05rem);
            box-shadow: 0 16px 40px -14px rgba(13,148,136,0.75), 0 0 0 1px rgba(255,255,255,0.15) inset;
        }
        .auth-btn-primary:active:not(:disabled) {
            transform: translateY(0);
        }
        .auth-btn-primary:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(20,184,166,0.35), 0 10px 32px -10px rgba(13,148,136,0.65);
        }
        .auth-btn-primary:disabled {
            opacity: 0.82;
            cursor: wait;
        }
        .auth-btn-primary.auth-btn-loading .auth-btn-shimmer {
            position: absolute;
            inset: 0;
            background: linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.18) 50%, transparent 60%);
            background-size: 200% 100%;
            animation: auth-shimmer 1s ease infinite;
        }
        @keyframes auth-shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }
        .auth-glow-ring {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
    </style>
    <script>
    (function () {
        var k = 'khf_guest_theme';
        var s = localStorage.getItem(k);
        if (s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
    </script>
</head>
<body class="min-h-full antialiased text-slate-900 dark:text-slate-100">
<div class="fixed inset-0 -z-20 bg-[#f4f8f9] dark:bg-[#070b14]"></div>
<div class="fixed inset-0 -z-10 opacity-90 bg-gradient-to-br from-slate-100 via-teal-50/40 to-cyan-100/50 dark:from-[#061018] dark:via-[#0a1626] dark:to-[#08121f]"></div>
<div class="auth-glow-ring w-[520px] h-[520px] -top-44 -left-36 bg-teal-400/30 dark:bg-teal-600/22"></div>
<div class="auth-glow-ring w-[480px] h-[480px] top-1/3 -right-40 bg-cyan-400/22 dark:bg-cyan-900/35"></div>
<div class="auth-glow-ring w-[380px] h-[380px] bottom-[-6rem] left-1/3 bg-emerald-500/18 dark:bg-emerald-900/28"></div>
<div class="fixed inset-0 -z-[5] auth-grid opacity-[0.28] dark:opacity-[0.18] pointer-events-none"></div>
<div class="fixed inset-0 -z-[4] auth-noise opacity-40 pointer-events-none mix-blend-soft-light dark:opacity-25 dark:mix-blend-overlay"></div>
<div class="fixed bottom-10 left-6 right-auto z-40 hidden md:block opacity-[0.12] dark:opacity-[0.09] pointer-events-none" aria-hidden="true">
    <svg width="280" height="120" viewBox="0 0 280 120" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0 92 C52 92 72 52 118 54 C154 56 174 92 226 92" stroke="rgb(13,148,136)" stroke-width="2" stroke-linecap="round"/>
        <path d="M0 70 C56 74 104 98 174 74 C226 62 266 94 278 94" stroke="rgb(8,145,178)" stroke-width="1.25" opacity="0.55" stroke-linecap="round"/>
        <circle cx="118" cy="54" r="3" fill="rgb(13,148,136)"/><circle cx="226" cy="92" r="3" fill="rgb(34,211,238)"/><circle cx="174" cy="74" r="2.5" fill="rgb(20,184,166)"/>
    </svg>
</div>

<button type="button" onclick="toggleGuestTheme()" title="Toggle theme" class="fixed top-5 right-5 z-50 flex items-center gap-2 rounded-xl border border-slate-200/70 dark:border-slate-700/90 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md px-3 py-2 text-[11px] font-semibold text-slate-600 dark:text-slate-300 shadow-lg shadow-slate-900/5 hover:bg-white/95 dark:hover:bg-slate-800/90 transition-colors">
    <i data-lucide="sun" class="w-3.5 h-3.5 dark:hidden"></i>
    <i data-lucide="moon" class="w-3.5 h-3.5 hidden dark:inline"></i>
    <span class="hidden sm:inline"><span class="dark:hidden">Light</span><span class="hidden dark:inline">Dark</span></span>
</button>

<div class="relative z-10 min-h-screen flex flex-col items-center justify-center px-4 sm:px-6 py-12 sm:py-16 lg:py-20">
    <div class="w-full max-w-[440px]">
        <?php
        $vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
        if (is_file($vf)) {
            include $vf;
        }
        ?>
    </div>
    <p class="mt-auto pt-10 text-center text-[10px] font-medium uppercase tracking-[0.18em] text-slate-400 dark:text-slate-600 max-w-xs leading-relaxed">
        Secure authentication · <?= Str::e($appName) ?>
    </p>
</div>

<script>
function toggleGuestTheme() {
    var html = document.documentElement;
    html.classList.toggle('dark');
    try {
        localStorage.setItem('khf_guest_theme', html.classList.contains('dark') ? 'dark' : 'light');
    } catch (e) {}
}
function wireAuthForms() {
    document.querySelectorAll('.auth-submit-form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btn = form.querySelector('.auth-btn-primary');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            btn.classList.add('auth-btn-loading');
            btn.setAttribute('aria-busy', 'true');
        });
    });
}
wireAuthForms();
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
