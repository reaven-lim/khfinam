<?php

declare(strict_types=1);

namespace App\Core;

use App\Helpers\Config;

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        $key = Config::get('app.csrf_key', '_csrf_token');
        if (!Session::get($key)) {
            Session::set($key, bin2hex(random_bytes(32)));
        }

        return (string) Session::get($key);
    }

    public static function verify(?string $token): bool
    {
        $key = Config::get('app.csrf_key', '_csrf_token');
        $session = Session::get($key);
        if (!is_string($session) || $session === '' || !is_string($token)) {
            return false;
        }

        return hash_equals($session, $token);
    }

    public static function field(): string
    {
        $name = Config::get('app.csrf_key', '_csrf_token');
        $t = self::token();

        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
}
