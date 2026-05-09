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

final class NotificationController
{
    public function markRead(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/notifications'));
        }
        $id = (int) (Request::post()['notification_id'] ?? 0);
        if ($id <= 0) {
            Response::redirect(Url::to('/app/notifications'));
        }
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?')->execute([$id, Auth::id()]);
        Response::redirect(Url::to('/app/notifications'));
    }

    public function markAllRead(): void
    {
        $this->guard();
        if (! Csrf::verify(Request::post()[Config::get('app.csrf_key')] ?? null)) {
            Response::redirect(Url::to('/app/notifications'));
        }
        Database::pdo()->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL')
            ->execute([Auth::id()]);
        Response::redirect(Url::to('/app/notifications'));
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
