<?php

declare(strict_types=1);

use App\Core\Request;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\View;

$appName     = Config::get('app.name', 'KHFinaM');
$titleText   = isset($title) ? Str::e((string) $title) . ' · Personal · ' . Str::e($appName) : 'Personal · ' . Str::e($appName);
$currentUser = $user ?? Auth::user();
$dashUsername = is_array($currentUser) ? (string) ($currentUser['username'] ?? 'Member') : 'Member';
$dashInitials = strtoupper(substr($dashUsername, 0, 2));
$roleBadge = Auth::isSuperAdmin()
    ? 'Super admin · your data'
    : 'Personal account';

$here = Request::uri();

$navGroups = [
    'Intelligence' => [
        '/dashboard'              => ['Overview', 'layout-dashboard'],
        '/dashboard/transactions' => ['Transactions', 'arrow-left-right'],
        '/dashboard/wallets'       => ['Wallets', 'wallet'],
        '/dashboard/recurring'     => ['Recurring', 'repeat-2'],
        '/dashboard/reports'       => ['Reports', 'bar-chart-2'],
        '/dashboard/notifications' => ['Notifications', 'bell'],
    ],
];

if (Auth::isSuperAdmin()) {
    $navGroups['System console'] = [
        '/admin' => ['Administrator', 'shield'],
    ];
}

View::partial('components/layout/analytics-shell-head', [
    'titleText'             => $titleText,
    'themeLocalStorageKey'  => 'khf_dashboard_theme',
    'sbPrefix'             => 'dash-sb',
]);
View::partial('components/layout/shell-body-open');
View::partial('components/layout/shell-overlay');
View::partial('components/layout/sidebar-aside-open');

View::partial('components/layout/sidebar-brand-row', [
    'appName'         => $appName,
    'brandSubtitle'   => 'Personal Intelligence',
    'logoIcon'       => 'line-chart',
    'sbPrefix'       => 'dash-sb',
    'brandRowClass'  => 'h-14 xl:h-16 px-3 xl:px-5 flex items-center gap-2 border-b border-slate-200/95 dark:border-slate-800 shrink-0 justify-between xl:justify-start min-h-[3.5rem]',
    'brandTitleAttr' => $appName,
    'toggleBtnId'    => 'sidebarMidToggleDash',
    'toggleIconId'   => 'sidebarMidToggleIconDash',
    'toggleOnclick'  => 'toggleDashSidebarMid',
]);

View::partial('components/layout/sidebar-dash-app-shortcuts');

View::partial('components/layout/sidebar-nav-groups', [
    'navGroups'     => $navGroups,
    'here'         => $here,
    'sbPrefix'     => 'dash-sb',
    'navExactRoot' => '/dashboard',
    'navWrapClass' => 'flex-1 px-2 xl:px-3 py-2 xl:py-3 overflow-y-auto overflow-x-hidden space-y-3 xl:space-y-4 scrollbar-none',
]);

View::partial('components/layout/sidebar-footer-user', [
    'sbPrefix'         => 'dash-sb',
    'displayName'      => $dashUsername,
    'initials'        => $dashInitials,
    'badgeLine'        => $roleBadge,
    'footerBadgeClass' => 'text-[10px] text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wide truncate',
]);

View::partial('components/layout/sidebar-aside-close');

View::partial('components/layout/shell-main-column-open');

View::partial('components/layout/mobile-shell-header', [
    'title' => $title ?? null,
    'fallbackTitle' => 'Overview',
]);

View::partial('components/layout/desktop-page-header', [
    'title' => $title ?? null,
    'fallbackTitle' => 'Overview',
]);

View::partial('components/layout/main-content-open', [
    'mainClasses' => 'flex-1 overflow-y-auto p-4 md:px-5 md:py-5 lg:px-7 xl:px-8 lg:py-6 main-content-dashboard',
]);
$vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
if (is_file($vf)) {
    include $vf;
}
View::partial('components/layout/main-content-close');

View::partial('components/layout/shell-main-column-close');

View::partial('components/layout/shell-scripts', [
    'themeLocalStorageKey' => 'khf_dashboard_theme',
    'midToggleFunctionName' => 'toggleDashSidebarMid',
    'midToggleButtonId' => 'sidebarMidToggleDash',
    'midToggleIconId' => 'sidebarMidToggleIconDash',
    'midToggleStorageKey' => 'khf_dash_sidebar_exp',
]);
?>
</body>
</html>
