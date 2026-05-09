<?php

declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;
use App\Services\WalletService;

final class DashboardWalletController
{
    public function store(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/dashboard/wallets'));
        }
        $uid = (int) Auth::id();
        try {
            (new WalletService())->createWallet($uid, [
                'name' => trim((string) (Request::post()['name'] ?? '')),
                'wallet_type_id' => (int) (Request::post()['wallet_type_id'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'opening_balance' => (float) (Request::post()['opening_balance'] ?? 0),
                'min_balance_threshold' => Request::post()['min_balance_threshold'] ?? '',
                'is_default' => ! empty(Request::post()['is_default']),
                'is_active' => ! empty(Request::post()['is_active']),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
            ]);
            Session::flash('message', 'Wallet created.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/dashboard/wallets'));
    }

    public function update(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/dashboard/wallets'));
        }
        $uid = (int) Auth::id();
        $wid = (int) (Request::post()['wallet_id'] ?? 0);
        try {
            (new WalletService())->updateWalletForOwner($uid, $wid, [
                'name' => trim((string) (Request::post()['name'] ?? '')),
                'wallet_type_id' => (int) (Request::post()['wallet_type_id'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'opening_balance' => (float) (Request::post()['opening_balance'] ?? 0),
                'min_balance_threshold' => Request::post()['min_balance_threshold'] ?? '',
                'is_default' => ! empty(Request::post()['is_default']),
                'is_active' => ! empty(Request::post()['is_active']),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
            ]);
            Session::flash('message', 'Wallet saved.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/dashboard/wallets'));
    }

    public function delete(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/dashboard/wallets'));
        }
        $uid = (int) Auth::id();
        $wid = (int) (Request::post()['wallet_id'] ?? 0);
        try {
            (new WalletService())->deleteWalletForOwner($uid, $wid);
            Session::flash('message', 'Wallet deleted.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/dashboard/wallets'));
    }

    private function guard(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
            exit;
        }
    }
}
