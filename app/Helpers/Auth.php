<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

final class Auth
{
    private const SESSION_USER_ID = 'auth_user_id';

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set(self::SESSION_USER_ID, $userId);
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_USER_ID);
        Session::regenerate();
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_USER_ID);
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $repo = new \App\Repositories\UserRepository();

        return $repo->findById((int) self::id());
    }

    public static function check(): bool
    {
        return self::id() !== null && self::id() > 0;
    }

    public static function isSuperAdmin(): bool
    {
        $u = self::user();

        return ($u['role'] ?? '') === 'super_admin';
    }
}
