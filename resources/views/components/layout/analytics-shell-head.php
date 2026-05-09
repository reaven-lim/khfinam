<?php
declare(strict_types=1);

use App\Helpers\View;

/** @var string $titleText Full <title> text (already escaped) */
/** @var string $themeLocalStorageKey localStorage key for light/dark preference */
/** @var string $sbPrefix dash-sb | adm-sb */
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
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.395.0/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        [data-lucide] { display: inline-block; vertical-align: middle; }
        <?php View::partial('components/layout/shell-sidebar-link-css'); ?>
        <?php View::partial('components/layout/shell-sidebar-slim-css', ['sbPrefix' => $sbPrefix]); ?>
        <?php View::partial('components/layout/shell-main-form-css'); ?>
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light,
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light .apexcharts-tooltip-title {
            background: #ffffff !important;
            border-color: rgb(226 232 240) !important;
            box-shadow:
                0 18px 44px -12px rgba(15,23,42,0.16),
                0 8px 20px -8px rgba(15,23,42,0.1),
                0 1px 0 rgba(255,255,255,0.92) inset !important;
            color: #0f172a !important;
        }
        html:not(.dark) .apexcharts-tooltip.apexcharts-theme-light .apexcharts-tooltip-title {
            border-bottom-color: rgb(226 232 240) !important;
        }
        html:not(.dark) .apexcharts-tooltip-text-y-value,
        html:not(.dark) .apexcharts-tooltip-text-y-label { color: #334155 !important; }
        html:not(.dark) .apexcharts-xaxis-label,
        html:not(.dark) .apexcharts-yaxis-label { fill: rgb(71,85,105) !important; }
        html:not(.dark) .apexcharts-legend-text { color: #334155 !important; fill: #334155 !important; }
    </style>
    <script>
    (function(){
        var s = localStorage.getItem(<?= json_encode($themeLocalStorageKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE) ?>);
        if (s === 'dark' || (!s && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
    </script>
</head>
