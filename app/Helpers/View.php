<?php

declare(strict_types=1);

namespace App\Helpers;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');

            return;
        }
        include $file;
    }

    /** @param array<string, mixed> $data */
    public static function renderLayout(string $layout, string $view, array $data = []): void
    {
        $data['viewPath'] = $view;
        self::render('layouts/' . $layout, $data);
    }

    /**
     * Include a reusable view partial under resources/views/ (plain PHP, no framework).
     * Path uses forward slashes, e.g. "components/layout/mobile-header".
     *
     * @param array<string, mixed> $data Extracted as local variables inside the partial
     */
    public static function partial(string $relativePath, array $data = []): void
    {
        $sanitized = str_replace(['..', "\0"], '', $relativePath);
        $file = dirname(__DIR__, 2) . '/resources/views/' . str_replace('.', '/', $sanitized) . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'Partial not found: ' . htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');

            return;
        }
        extract($data, EXTR_SKIP);
        include $file;
    }
}
