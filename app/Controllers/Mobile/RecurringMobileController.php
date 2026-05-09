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
use App\Helpers\View;
use App\Repositories\CategoryRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\WalletRepository;
use App\Services\RecurringService;

final class RecurringMobileController
{
    public function createForm(): void
    {
        $this->guard();
        $uid = (int) Auth::id();
        View::renderLayout('mobile', 'mobile/recurring_new', [
            'title' => 'New recurring',
            'user' => Auth::user(),
            'wallets' => (new WalletRepository())->forUser($uid),
            'categoriesIncome' => (new CategoryRepository())->forUserIncludingGlobal($uid, 'income'),
            'categoriesExpense' => (new CategoryRepository())->forUserIncludingGlobal($uid, 'expense'),
            'currencies' => (new CurrencyRepository())->allActive(),
            'error' => Session::getFlash('error'),
        ]);
    }

    public function createStore(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Session::flash('error', 'Invalid session.');
            Response::redirect(Url::to('/app/recurring/new'));
        }
        try {
            (new RecurringService())->createSchedule((int) Auth::id(), [
                'wallet_id' => (int) (Request::post()['wallet_id'] ?? 0),
                'category_id' => (int) (Request::post()['category_id'] ?? 0),
                'type' => (string) (Request::post()['type'] ?? 'expense'),
                'title' => trim((string) (Request::post()['title'] ?? '')),
                'amount' => (float) (Request::post()['amount'] ?? 0),
                'currency_id' => (int) (Request::post()['currency_id'] ?? 0),
                'frequency' => (string) (Request::post()['frequency'] ?? 'monthly'),
                'interval_value' => (int) (Request::post()['interval_value'] ?? 1),
                'start_date' => (string) (Request::post()['start_date'] ?? date('Y-m-d')),
                'end_date' => (string) (Request::post()['end_date'] ?? ''),
                'notes' => trim((string) (Request::post()['notes'] ?? '')) ?: null,
            ]);
            Session::flash('message', 'Recurring schedule created.');
            Response::redirect(Url::to('/app/recurring'));
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(Url::to('/app/recurring/new'));
        }
    }

    public function pause(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/recurring'));
        }
        $id = (int) (Request::post()['schedule_id'] ?? 0);
        $paused = (string) (Request::post()['paused'] ?? '') === '1';
        (new RecurringService())->pause((int) Auth::id(), $id, $paused);
        Response::redirect(Url::to('/app/recurring'));
    }

    public function skip(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/recurring'));
        }
        $id = (int) (Request::post()['schedule_id'] ?? 0);
        (new RecurringService())->skipNext((int) Auth::id(), $id);
        Response::redirect(Url::to('/app/recurring'));
    }

    public function runNow(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/recurring'));
        }
        $id = (int) (Request::post()['schedule_id'] ?? 0);
        try {
            (new RecurringService())->runOne((int) Auth::id(), $id);
            Session::flash('message', 'Generated next occurrence.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(Url::to('/app/recurring'));
    }

    private function guard(): void
    {
        if (! Auth::check()) {
            Response::redirect(Url::to('/login'));
        }
        if (Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/admin'));
        }
    }
}
