<?php

declare(strict_types=1);

use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminFormController;
use App\Controllers\Api\ReportApiController;
use App\Controllers\Dashboard\DashboardController;
use App\Controllers\Dashboard\DashboardWalletController;
use App\Controllers\AuthController;
use App\Controllers\Mobile\MobileAppController;
use App\Controllers\Mobile\NotificationController;
use App\Controllers\Mobile\ProfileController;
use App\Controllers\Mobile\RecurringMobileController;
use App\Controllers\Mobile\TxnController;
use App\Controllers\Mobile\WalletActionController;

return [
    'GET /' => [MobileAppController::class, 'home'],
    'GET /login' => [AuthController::class, 'showLogin'],
    'POST /login' => [AuthController::class, 'login'],
    'POST /logout' => [AuthController::class, 'logout'],
    'GET /logout' => [AuthController::class, 'logout'],
    'GET /forgot-password' => [AuthController::class, 'showForgot'],
    'POST /forgot-password' => [AuthController::class, 'forgotSubmit'],
    'GET /reset-password' => [AuthController::class, 'showReset'],
    'POST /reset-password' => [AuthController::class, 'resetSubmit'],

    'GET /app' => [MobileAppController::class, 'dashboard'],
    'GET /app/add' => [MobileAppController::class, 'add'],
    'POST /app/add' => [MobileAppController::class, 'add'],
    'GET /app/recurring' => [MobileAppController::class, 'recurring'],
    'GET /app/recurring/new' => [RecurringMobileController::class, 'createForm'],
    'POST /app/recurring/new' => [RecurringMobileController::class, 'createStore'],
    'POST /app/recurring/pause' => [RecurringMobileController::class, 'pause'],
    'POST /app/recurring/skip' => [RecurringMobileController::class, 'skip'],
    'POST /app/recurring/run' => [RecurringMobileController::class, 'runNow'],
    'GET /app/wallets' => [MobileAppController::class, 'wallets'],
    'POST /app/wallets' => [WalletActionController::class, 'create'],
    'POST /app/wallets/update' => [WalletActionController::class, 'update'],
    'POST /app/wallets/delete' => [WalletActionController::class, 'delete'],
    'POST /app/wallets/transfer' => [WalletActionController::class, 'transfer'],
    'GET /app/stats' => [MobileAppController::class, 'stats'],
    'GET /app/notifications' => [MobileAppController::class, 'notifications'],
    'POST /app/notifications/read' => [NotificationController::class, 'markRead'],
    'POST /app/notifications/read-all' => [NotificationController::class, 'markAllRead'],
    'GET /app/profile' => [MobileAppController::class, 'profile'],
    'POST /app/profile' => [ProfileController::class, 'save'],

    'GET /app/transaction/{id}' => [TxnController::class, 'show'],
    'POST /app/transaction/{id}/delete' => [TxnController::class, 'destroy'],
    'POST /app/transaction/{id}/attach' => [TxnController::class, 'attach'],
    'POST /app/transaction/{id}/attach-delete' => [TxnController::class, 'attachDeletePost'],
    'POST /app/transaction/{id}/child' => [TxnController::class, 'addChild'],
    'POST /app/transaction/{id}' => [TxnController::class, 'update'],

    'GET /dashboard' => [DashboardController::class, 'index'],
    'GET /dashboard/transactions' => [DashboardController::class, 'transactions'],
    'GET /dashboard/wallets' => [DashboardController::class, 'wallets'],
    'POST /dashboard/wallets/store' => [DashboardWalletController::class, 'store'],
    'POST /dashboard/wallets/update' => [DashboardWalletController::class, 'update'],
    'POST /dashboard/wallets/delete' => [DashboardWalletController::class, 'delete'],
    'GET /dashboard/recurring' => [DashboardController::class, 'recurring'],
    'GET /dashboard/reports' => [DashboardController::class, 'reports'],
    'GET /dashboard/reports/csv' => [DashboardController::class, 'reportsCsv'],
    'GET /dashboard/reports/pdf' => [DashboardController::class, 'reportsPdf'],
    'GET /dashboard/notifications' => [DashboardController::class, 'notifications'],

    'GET /admin' => [AdminDashboardController::class, 'index'],
    'GET /admin/transactions' => [AdminDashboardController::class, 'transactions'],
    'GET /admin/users' => [AdminDashboardController::class, 'users'],
    'GET /admin/users/{id}' => [AdminDashboardController::class, 'userShow'],
    'GET /admin/wallets' => [AdminDashboardController::class, 'wallets'],
    'GET /admin/wallets/{id}' => [AdminDashboardController::class, 'walletShow'],
    'POST /admin/wallets/store' => [AdminFormController::class, 'walletStore'],
    'POST /admin/wallets/update' => [AdminFormController::class, 'walletUpdate'],
    'POST /admin/wallets/status' => [AdminFormController::class, 'walletSetStatus'],
    'POST /admin/wallets/delete' => [AdminFormController::class, 'walletDelete'],
    'GET /admin/wallet-types' => [AdminDashboardController::class, 'walletTypes'],
    'POST /admin/wallet-types/store' => [AdminFormController::class, 'walletTypeStore'],
    'POST /admin/wallet-types/update' => [AdminFormController::class, 'walletTypeUpdate'],
    'POST /admin/wallet-types/delete' => [AdminFormController::class, 'walletTypeDelete'],
    'GET /admin/notifications' => [AdminDashboardController::class, 'notifications'],
    'GET /admin/settings' => [AdminDashboardController::class, 'settings'],
    'POST /admin/notifications/broadcast' => [AdminFormController::class, 'notificationBroadcast'],
    'POST /admin/settings' => [AdminFormController::class, 'saveSettings'],
    'POST /admin/settings/test-email' => [AdminFormController::class, 'testEmail'],
    'GET /admin/categories' => [AdminDashboardController::class, 'categories'],
    'POST /admin/categories' => [AdminFormController::class, 'categoryStore'],
    'POST /admin/categories/update' => [AdminFormController::class, 'categoryUpdate'],
    'POST /admin/categories/delete' => [AdminFormController::class, 'categoryDelete'],
    'GET /admin/rates' => [AdminDashboardController::class, 'rates'],
    'POST /admin/rates' => [AdminFormController::class, 'rateStore'],
    'POST /admin/rates/delete' => [AdminFormController::class, 'rateDelete'],
    'GET /admin/audit' => [AdminDashboardController::class, 'audit'],
    'GET /admin/backups' => [AdminDashboardController::class, 'backups'],
    'POST /admin/backups/run' => [AdminFormController::class, 'backupRun'],
    'POST /admin/backups/restore' => [AdminFormController::class, 'backupRestore'],
    'GET /admin/backups/download/{id}' => [AdminFormController::class, 'backupDownload'],
    'GET /admin/reports' => [AdminDashboardController::class, 'reports'],
    'GET /admin/recurring' => [AdminDashboardController::class, 'recurring'],
    'POST /admin/recurring/run' => [AdminFormController::class, 'recurringRun'],
    'POST /admin/recurring/create' => [AdminFormController::class, 'recurringCreate'],
    'POST /admin/users' => [AdminFormController::class, 'userStore'],
    'POST /admin/users/update' => [AdminFormController::class, 'userUpdate'],
    'POST /admin/users/status' => [AdminFormController::class, 'userSetStatus'],
    'POST /admin/users/prefs' => [AdminFormController::class, 'userPrefs'],

    'GET /api/reports/csv' => [ReportApiController::class, 'csvSummary'],
    'GET /api/reports/pdf' => [ReportApiController::class, 'pdfMonthly'],
    'GET /api/reports/heatmap' => [ReportApiController::class, 'heatmap'],
];
