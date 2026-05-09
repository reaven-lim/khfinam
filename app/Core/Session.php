<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Config;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;

            return;
        }
        $secure = Config::get('app.session_secure', false);
        $lifetime = (int) Config::get('app.session_lifetime', 120);
        session_set_cookie_params([
            'lifetime' => $lifetime * 60,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('KHFINAMSSESSID');
        session_start();
        self::$started = true;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function id(): string
    {
        self::start();

        return session_id();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash_' . $key, $value);
    }

    public static function getFlash(string $key): mixed
    {
        $k = '_flash_' . $key;
        $v = self::get($k);
        self::forget($k);

        return $v;
    }

    /** Idle timeout: invalidate session if inactive longer than app.session_lifetime (minutes). */
    public static function enforceIdleTimeout(): void
    {
        self::start();
        if (empty($_SESSION['auth_user_id'])) {
            return;
        }
        $lifetimeSec = (int) Config::get('app.session_lifetime', 120) * 60;
        $last = (int) ($_SESSION['_idle_at'] ?? 0);
        if ($last > 0 && (time() - $last) > $lifetimeSec) {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            self::$started = false;
            self::start();

            return;
        }
        $_SESSION['_idle_at'] = time();
    }
}
