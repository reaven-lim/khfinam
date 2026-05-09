<?php

declare(strict_types=1);

namespace App\Helpers;

final class Url
{
    public static function basePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = dirname(str_replace('\\', '/', (string) $script));
        if ($dir === '/' || $dir === '.') {
            return '';
        }
        // When a root .htaccess rewrites into public/, Apache still reports
        // SCRIPT_NAME as e.g. /khfinam/public/index.php. Strip the trailing
        // /public segment so all generated URLs stay at /khfinam/*.
        $dir = preg_replace('#/public$#', '', $dir) ?? $dir;

        return rtrim($dir, '/');
    }

    public static function path(): string
    {
        $raw = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = self::basePath();
        if ($base !== '' && str_starts_with($raw, $base)) {
            $raw = substr($raw, strlen($base)) ?: '/';
        }

        return '/' . trim($raw, '/') ?: '/';
    }

    public static function to(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $base = self::basePath();

        return $base . ($path === '//' ? '/' : $path);
    }

    public static function asset(string $path): string
    {
        return self::to('assets/' . ltrim($path, '/'));
    }
}
