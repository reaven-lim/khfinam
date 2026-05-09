<?php

declare(strict_types=1);

namespace App\Controllers\Mobile;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Auth;
use App\Helpers\Config;
use App\Helpers\Url;

final class ProfileController
{
    public function save(): void
    {
        if (! Auth::check() || Auth::isSuperAdmin()) {
            Response::redirect(Url::to('/login'));

            return;
        }
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/profile'));
        }
        $theme = (string) (Request::post()['preference_theme'] ?? 'system');
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = 'system';
        }
        $mute = ! empty(Request::post()['preference_mute_low_balance']) ? 1 : 0;
        Database::pdo()->prepare(
            'UPDATE users SET preference_theme = ?, preference_mute_low_balance = ? WHERE id = ?'
        )->execute([$theme, $mute, Auth::id()]);
        Response::redirect(Url::to('/app/profile'));
    }
}
