<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        return \App\Helpers\Url::path();
    }

    /** @return array<string, string> */
    public static function post(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json') && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                /** @var array<string, string> $flat */
                $flat = [];
                foreach ($decoded as $k => $v) {
                    $flat[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
                }

                return array_merge($_POST, $flat);
            }
        }

        return $_POST;
    }

    public static function query(string $key, ?string $default = null): ?string
    {
        $v = $_GET[$key] ?? $default;

        return is_string($v) ? $v : $default;
    }

    public static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $v = $_SERVER[$h];
                if (is_string($v) && str_contains($v, ',')) {
                    return trim(explode(',', $v)[0]);
                }

                return is_string($v) ? $v : '0.0.0.0';
            }
        }

        return '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    }

    public static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        return str_contains((string) $accept, 'application/json');
    }
}
