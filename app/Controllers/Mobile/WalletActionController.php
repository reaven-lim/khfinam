<?php

declare(strict_types=1);

namespace App\Controllers\Mobile;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;
use App\Services\WalletService;

final class WalletActionController
{
    public function create(): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/wallets'));
        }
        try {
            (new WalletService())->createWallet((int) Auth::id(), [
                'name' => trim((string) (Request::post()['name'] ?? '')),
                'wallet_type' => (string) (Request::post()['wallet_type'] ?? 'cash'),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'opening_balance' => (float) (Request::post()['opening_balance'] ?? 0),
                'min_balance_threshold' => Request::post()['min_balance_threshold'] ?? '',
                'is_default' => ! empty(Request::post()['is_default']),
                'notes' => trim((string) (Request::post()['notes'] ?? '')),
                'sort_order' => (int) (Request::post()['sort_order'] ?? 0),
            ]);
            Response::redirect(Url::to('/app/wallets'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/wallets'));
        }
    }

    public function transfer(): void
    {
        $this->requireUser();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/wallets'));
        }
        try {
            (new WalletService())->transfer(
                (int) Auth::id(),
                (int) (Request::post()['from_wallet_id'] ?? 0),
                (int) (Request::post()['to_wallet_id'] ?? 0),
                (float) (Request::post()['amount'] ?? 0),
                (string) (Request::post()['transfer_date'] ?? date('Y-m-d')),
                trim((string) (Request::post()['notes'] ?? '')) ?: null
            );
            Session::flash('message', 'Transfer recorded.');
            Response::redirect(Url::to('/app/wallets'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/wallets'));
        }
    }

    private function requireUser(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
        }
        if (Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/admin'));
        }
    }
}
