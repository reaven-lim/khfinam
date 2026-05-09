<?php

declare(strict_types=1);

use App\Core\Request;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Str;
use App\Helpers\View;

$appName    = Config::get('app.name', 'KHFinaM');
$titleText  = isset($title) ? Str::e((string) $title) . ' · Admin · ' . Str::e($appName) : 'Admin · ' . Str::e($appName);
$currentUser = $user ?? Auth::user();
$adminUsername = is_array($currentUser) ? (string) ($currentUser['username'] ?? 'Admin') : 'Admin';
$adminInitials = strtoupper(substr($adminUsername, 0, 2));
$here = Request::uri();

$navGroups = [
    'Platform analytics' => [
        '/admin'              => ['System overview', 'layout-dashboard'],
        '/admin/reports'      => ['Global reports',  'bar-chart-2'],
        '/admin/transactions' => ['All transactions', 'arrow-left-right'],
    ],
    'Management' => [
        '/admin/users'         => ['Users',         'users'],
        '/admin/wallets'       => ['Wallets',       'wallet'],
        '/admin/wallet-types'  => ['Wallet types',  'layers'],
        '/admin/categories'    => ['Categories',    'tag'],
        '/admin/rates'         => ['Rates',         'percent'],
        '/admin/recurring'     => ['Recurring',     'repeat-2'],
        '/admin/notifications' => ['Notifications', 'bell'],
    ],
    'System' => [
        '/admin/audit'    => ['Audit log', 'shield-check'],
        '/admin/backups'  => ['Backups',   'hard-drive'],
        '/admin/settings' => ['Settings',  'settings'],
    ],
];

View::partial('components/layout/analytics-shell-head', [
    'titleText'            => $titleText,
    'themeLocalStorageKey' => 'khf_admin_theme',
    'sbPrefix'             => 'adm-sb',
]);
View::partial('components/layout/shell-body-open');
View::partial('components/layout/shell-overlay');
View::partial('components/layout/sidebar-aside-open');

View::partial('components/layout/sidebar-brand-row', [
    'appName'         => $appName,
    'brandSubtitle'   => 'Admin Console',
    'logoIcon'       => 'trending-up',
    'sbPrefix'       => 'adm-sb',
    'brandRowClass'  => 'h-16 px-3 xl:px-5 flex items-center gap-2 border-b border-slate-200/95 dark:border-slate-800 shrink-0 justify-between xl:justify-start min-h-[4rem]',
    'brandTitleAttr' => $appName . ' · Admin',
    'toggleBtnId'    => 'sidebarMidToggleAdmin',
    'toggleIconId'   => 'sidebarMidToggleIconAdmin',
    'toggleOnclick'  => 'toggleAdminSidebarMid',
]);

View::partial('components/layout/sidebar-nav-groups', [
    'navGroups'     => $navGroups,
    'here'         => $here,
    'sbPrefix'     => 'adm-sb',
    'navExactRoot' => '/admin',
    'navWrapClass' => 'flex-1 px-2 xl:px-3 py-3 xl:py-4 overflow-y-auto space-y-4 scrollbar-none',
]);

View::partial('components/layout/sidebar-footer-user', [
    'sbPrefix'         => 'adm-sb',
    'displayName'      => $adminUsername,
    'initials'         => $adminInitials,
    'badgeLine'        => 'Super Admin',
    'footerBadgeClass' => 'text-[10px] text-teal-600 dark:text-teal-400 font-bold uppercase tracking-wide',
    'logoutButtonTitle' => 'Logout',
]);

View::partial('components/layout/sidebar-aside-close');

View::partial('components/layout/shell-main-column-open');

View::partial('components/layout/mobile-shell-header', [
    'title' => $title ?? null,
    'fallbackTitle' => 'Admin',
]);

View::partial('components/layout/desktop-page-header', [
    'title' => $title ?? null,
    'fallbackTitle' => 'Overview',
]);

View::partial('components/layout/main-content-open', []);
$vf = dirname(__DIR__) . '/' . str_replace('.', '/', (string) $viewPath) . '.php';
if (is_file($vf)) {
    include $vf;
}
View::partial('components/layout/main-content-close');

View::partial('components/layout/shell-main-column-close');

View::partial('components/layout/shell-scripts', [
    'themeLocalStorageKey' => 'khf_admin_theme',
    'midToggleFunctionName' => 'toggleAdminSidebarMid',
    'midToggleButtonId' => 'sidebarMidToggleAdmin',
    'midToggleIconId' => 'sidebarMidToggleIconAdmin',
    'midToggleStorageKey' => 'khf_admin_sidebar_exp',
]);
?>
</body>
</html>
