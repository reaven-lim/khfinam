<?php

declare(strict_types=1);

namespace App\Helpers;

final class Str
{
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes(max(1, $bytes)));
    }

    public static function e(?string $value): string
    {
        return $value === null ? '' : htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
