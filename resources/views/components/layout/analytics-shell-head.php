<?php
declare(strict_types=1);

use App\Helpers\Url;
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
    <script src="<?= htmlspecialchars(Url::to('/js/khf-apex-chart-theme.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.395.0/dist/umd/lucide.min.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        [data-lucide] { display: inline-block; vertical-align: middle; }
        <?php View::partial('components/layout/shell-sidebar-link-css'); ?>
        <?php View::partial('components/layout/shell-sidebar-slim-css', ['sbPrefix' => $sbPrefix]); ?>
        <?php View::partial('components/layout/shell-main-form-css'); ?>
        <?php View::partial('components/charts/apex-theme-css'); ?>
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
