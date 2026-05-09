<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function redirect(string $to, int $code = 302): never
    {
        http_response_code($code);
        header('Location: ' . $to);
        exit;
    }

    public static function abort(int $code = 404, string $message = 'Not found'): never
    {
        http_response_code($code);
        if (Request::wantsJson()) {
            self::json(['error' => $message], $code);
        }
        echo '<h1>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</h1>';
        exit;
    }
}
